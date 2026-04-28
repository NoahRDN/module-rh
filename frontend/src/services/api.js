import axios from 'axios'

export const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://192.168.16.101:8001/api' || 'http://localhost:8001/api'
export const backendBaseUrl = apiBaseUrl.replace(/\/api\/?$/, '')
export const backendAssetBaseUrl = import.meta.env.VITE_PUBLIC_BACKEND_URL
  || backendBaseUrl
  || (typeof window !== 'undefined' ? window.location.origin : '')

export const resolveBackendAssetUrl = (path) => {
  const value = String(path || '').trim()

  if (!value) {
    return ''
  }

  if (value.startsWith('data:') || value.startsWith('blob:')) {
    return value
  }

  try {
    const url = /^(https?:)?\/\//i.test(value)
      ? new URL(value, `${backendAssetBaseUrl}/`)
      : new URL(value.startsWith('/') ? value : `/${value}`, `${backendAssetBaseUrl}/`)

    if (url.pathname.startsWith('/storage/')) {
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
