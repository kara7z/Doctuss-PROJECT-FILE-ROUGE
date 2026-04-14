const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'
const SANCTUM_URL = import.meta.env.VITE_SANCTUM_URL || 'http://localhost:8000/sanctum/csrf-cookie'

export const api = async (endpoint, options = {}) => {
  await fetch(SANCTUM_URL, {
    credentials: 'include',
  })

  const config = {
    ...options,
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...options.headers,
    },
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, config)
  return response
}

export { API_BASE_URL }
