import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
})
// console.log('API URL:', api.defaults.baseURL)
// injecter le token s’il existe
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  console.log('Token dans api.js:', token)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api


