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
})
// injecter le token s’il existe
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
