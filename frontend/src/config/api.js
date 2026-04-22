import router from '@/router'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'
const SANCTUM_URL  = import.meta.env.VITE_SANCTUM_URL  || '/sanctum/csrf-cookie'
const AUTH_TOKEN_KEY = 'auth_token'

const getCookie = (name) => {
  const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

const getAuthToken = () => localStorage.getItem(AUTH_TOKEN_KEY)

const setAuthToken = (token) => {
  if (token) {
    localStorage.setItem(AUTH_TOKEN_KEY, token)
  }
}

const clearAuthToken = () => {
  localStorage.removeItem(AUTH_TOKEN_KEY)
}

export const api = async (endpoint, options = {}) => {
  await fetch(SANCTUM_URL, { credentials: 'include' })
  
  const xsrfToken = getCookie('XSRF-TOKEN')
  const authToken = getAuthToken()

  const config = {
    ...options,
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {}),
      ...options.headers,
    },
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config)

  if (!response.ok) {
    const isAuthEndpoint = ['/login', '/register', '/me'].includes(endpoint)

    // Handle specific status codes
    if (response.status === 401 && !isAuthEndpoint) {
      router.push('/login')
    } else if (response.status === 403 && !isAuthEndpoint) {
      router.push('/403')
    } else if (response.status === 404 && !endpoint.startsWith('/appointments')) {
      router.push('/404')
    } else if (response.status >= 500 && !isAuthEndpoint) {
      router.push('/500')
    }
    // 422 validation errors and other 4xx errors are handled by components
  }

  return response
}

export { API_BASE_URL, clearAuthToken, getAuthToken, setAuthToken }
