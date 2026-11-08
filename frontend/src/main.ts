import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'
import router from './router'
import App from './App.vue'
import './style.css'

// Configure global Axios baseURL so all direct axios.get('/api/v1/...') calls reach the Railway backend
const rawApiUrl = (import.meta.env.VITE_API_BASE_URL as string) || 'https://moto-car-custom-production.up.railway.app'
axios.defaults.baseURL = rawApiUrl.replace(/\/api\/?$/, '').replace(/\/$/, '')

// Attach auth token to all axios calls automatically
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

app.mount('#app')
