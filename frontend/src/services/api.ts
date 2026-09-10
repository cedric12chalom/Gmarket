import axios from 'axios'

const api = axios.create({ baseURL: '' })

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('gm_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('gm_token')
      window.location.href = '/connexion'
    }
    return Promise.reject(err)
  },
)

export default api
