import axios from 'axios'

export const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://192.168.16.101:8001/api' || 'http://localhost:8001/api'
export const backendBaseUrl = apiBaseUrl.replace(/\/api\/?$/, '')
export const backendAssetBaseUrl = import.meta.env.VITE_PUBLIC_BACKEND_URL
  || backendBaseUrl
  || (typeof window !== 'undefined' ? window.location.origin : '')
export const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || ''
export const supabaseBucket = import.meta.env.VITE_SUPABASE_BUCKET || 'avatars'

const supabaseAssetUrl = (path) => {
  if (!supabaseUrl) {
    return ''
  }

  const cleanPath = String(path || '').trim().replace(/^\/+/, '')

  if (!cleanPath) {
    return ''
  }

  return `${supabaseUrl.replace(/\/+$/, '')}/storage/v1/object/public/${supabaseBucket}/${cleanPath}`
}

const looksLikeSupabaseAssetPath = (path) => /^(entreprise|employes)\//.test(path)

export const resolveBackendAssetUrl = (path) => {
  const value = String(path || '').trim()

  if (!value) {
    return ''
  }

  if (value.startsWith('data:') || value.startsWith('blob:')) {
    return value
  }

  if (looksLikeSupabaseAssetPath(value.replace(/^\/+/, ''))) {
    return supabaseAssetUrl(value)
  }

  try {
    const isAbsoluteUrl = /^(https?:)?\/\//i.test(value)
    const url = isAbsoluteUrl
      ? new URL(value, `${backendAssetBaseUrl}/`)
      : new URL(value.startsWith('/') ? value : `/${value}`, `${backendAssetBaseUrl}/`)

    if (isAbsoluteUrl) {
      return url.toString()
    }

    if (url.pathname.startsWith('/storage/')) {
      const storagePath = url.pathname.replace(/^\/storage\/+/, '')

      if (looksLikeSupabaseAssetPath(storagePath)) {
        return supabaseAssetUrl(storagePath)
      }

      return new URL(url.pathname + url.search + url.hash, `${backendAssetBaseUrl}/`).toString()
    }

    return url.toString()
  } catch (error) {
    return value
  }
}

const api = axios.create({
  baseURL: apiBaseUrl,
  timeout: 10000,
})

const pageCache = new Map()
const PAGE_CACHE_TTL_MS = 30000

const stableParams = (params = {}) => Object.keys(params)
  .sort()
  .reduce((acc, key) => {
    const value = params[key]
    if (value !== undefined && value !== null && value !== '') {
      acc[key] = value
    }
    return acc
  }, {})

const cacheKey = (url, config = {}) =>
  `${String(config.method || 'get').toLowerCase()}:${url}:${JSON.stringify(stableParams(config.params || {}))}`

const cloneData = (data) => {
  if (typeof structuredClone === 'function') {
    return structuredClone(data)
  }

  return JSON.parse(JSON.stringify(data))
}

const cachedResponse = (data) => ({ data: cloneData(data), status: 200, statusText: 'OK', headers: {}, config: {} })

export const getCachedApi = async (url, config = {}) => {
  const key = cacheKey(url, config)
  const entry = pageCache.get(key)

  if (entry?.data && Date.now() - entry.cachedAt < PAGE_CACHE_TTL_MS) {
    return cachedResponse(entry.data)
  }

  if (entry?.promise) {
    return entry.promise.then((response) => cachedResponse(response.data))
  }

  const promise = api.get(url, config)
    .then((response) => {
      pageCache.set(key, { data: cloneData(response.data), cachedAt: Date.now() })
      return response
    })
    .catch((error) => {
      pageCache.delete(key)
      throw error
    })

  pageCache.set(key, { promise })
  return promise
}

export const prefetchApiGet = (url, config = {}) => {
  const key = cacheKey(url, config)
  const entry = pageCache.get(key)

  if (entry?.data && Date.now() - entry.cachedAt < PAGE_CACHE_TTL_MS) return
  if (entry?.promise) return

  const promise = api.get(url, config)
    .then((response) => {
      pageCache.set(key, { data: cloneData(response.data), cachedAt: Date.now() })
      return response
    })
    .catch(() => {
      pageCache.delete(key)
    })

  pageCache.set(key, { promise })
}

export const prefetchNextPage = (url, params = {}, pagination = {}, config = {}) => {
  const currentPage = Number(pagination.page ?? pagination.current_page ?? params.page ?? 1)
  const lastPage = Number(pagination.last_page ?? 1)

  if (!Number.isFinite(currentPage) || !Number.isFinite(lastPage) || currentPage >= lastPage) {
    return
  }

  prefetchApiGet(url, {
    ...config,
    params: {
      ...params,
      page: currentPage + 1,
    },
  })
}

// injecter le token s’il existe
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(response => {
  if (String(response.config?.method || '').toLowerCase() !== 'get') {
    pageCache.clear()
  }

  return response
})

export default api
