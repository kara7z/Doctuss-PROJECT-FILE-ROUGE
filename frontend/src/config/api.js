import router from '@/router'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const SANCTUM_URL  = import.meta.env.VITE_SANCTUM_URL  || 'http://localhost:8000/sanctum/csrf-cookie'

const getCookie = (name) => {
  const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

export const api = async (endpoint, options = {}) => {
  await fetch(SANCTUM_URL, { credentials: 'include' })
  
  const xsrfToken = getCookie('XSRF-TOKEN')

  const config = {
    ...options,
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...options.headers,
    },
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config)

  if (!response.ok) {
    const isAuthEndpoint = ['/login', '/register', '/me'].includes(endpoint)

    if (response.status === 401 && !isAuthEndpoint) {
      router.push('/login')
    } else if (response.status === 403) {
      router.push('/403')
    } else if (response.status === 404) {
      router.push('/404')
    } else if (response.status >= 500) {
      router.push('/500')
    }
  }

  return response
}

export { API_BASE_URL }
