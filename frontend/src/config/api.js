import router from '@/router'

const LOCAL_LOOPBACK_HOSTS = new Set(['localhost', '127.0.0.1'])

const resolveLocalUrl = (configuredUrl, fallbackPath) => {
  if (typeof window === 'undefined') {
    return configuredUrl || fallbackPath
  }

  if (!configuredUrl) {
    return `${window.location.origin}${fallbackPath}`
  }

  try {
    const currentUrl = new URL(window.location.origin)
    const targetUrl = new URL(configuredUrl, window.location.origin)
    const isLoopbackMismatch =
      LOCAL_LOOPBACK_HOSTS.has(currentUrl.hostname) &&
      LOCAL_LOOPBACK_HOSTS.has(targetUrl.hostname) &&
      currentUrl.hostname !== targetUrl.hostname

    if (
      isLoopbackMismatch &&
      currentUrl.protocol === targetUrl.protocol &&
      currentUrl.port === targetUrl.port
    ) {
      return `${window.location.origin}${fallbackPath}`
    }

    return targetUrl.toString()
  } catch {
    return configuredUrl
  }
}

const API_BASE_URL = resolveLocalUrl(import.meta.env.VITE_API_BASE_URL, '/api')
const AUTH_TOKEN_KEY = 'auth_token'

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
  const authToken = getAuthToken()

  const config = {
    ...options,
    credentials: 'omit',
    headers: {
      'Accept': 'application/json',
      ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
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
