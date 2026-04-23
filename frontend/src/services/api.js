import axios from 'axios'

export const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://192.168.16.101:8000/api' || 'http://localhost:8000/api'
export const backendBaseUrl = apiBaseUrl.replace(/\/api\/?$/, '')

export const resolveBackendAssetUrl = (path) => {
  const value = String(path || '').trim()

  if (!value) {
    return ''
  }

  if (/^(https?:)?\/\//i.test(value) || value.startsWith('data:') || value.startsWith('blob:')) {
    return value
  }

  try {
    return new URL(value.startsWith('/') ? value : `/${value}`, `${backendBaseUrl}/`).toString()
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
