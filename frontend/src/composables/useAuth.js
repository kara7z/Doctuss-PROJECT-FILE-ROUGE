import { ref } from 'vue'
import router from '@/router'
import { api, clearAuthToken, getAuthToken } from '@/config/api'

const user    = ref(null)
const loading = ref(false)
const ready   = ref(false)

let fetchPromise = null

const fetchUser = async () => {
  if (ready.value) return
  if (fetchPromise) return fetchPromise

  if (!getAuthToken()) {
    user.value = null
    ready.value = true
    return
  }

  fetchPromise = (async () => {
    loading.value = true
    try {
      const res = await api('/me')
      if (res.ok) {
        const data = await res.json()
        user.value = data.user
      } else if (res.status === 403) {
        // Account suspended - redirect to login with message
        try {
          const data = await res.json()
          localStorage.setItem('suspendedMessage', data.message || 'Your account has been suspended.')
        } catch {
          localStorage.setItem('suspendedMessage', 'Your account has been suspended.')
        }
        user.value = null
        window.location.href = '/login'
        return
      } else {
        user.value = null
      }
    } catch {
      user.value = null
    } finally {
      loading.value = false
      ready.value   = true
      fetchPromise  = null
    }
  })()

  return fetchPromise
}

const logout = async () => {
  try {
    await api('/logout', { method: 'POST' })
  } finally {
    clearAuthToken()
    localStorage.removeItem('suspendedMessage')
    user.value = null
    ready.value = true
    await router.push('/')
  }
}

export const useAuth = () => ({
  user,
  loading,
  ready,
  fetchUser,
  logout,
})
