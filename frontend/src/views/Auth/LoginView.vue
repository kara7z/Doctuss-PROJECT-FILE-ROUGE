<script setup>
import { ref } from 'vue'
import { api } from '@/config/api'

const error = ref('')
const success = ref(false)
const formData = ref({
  email: '',
  password: '',
})
const login = async (e) => {
  e.preventDefault()
  error.value = ''
  success.value = false

  try {
    const response = await api('/login', {
      method: 'POST',
      body: JSON.stringify(formData.value),
    })
    let data;
    try {
      data = await response.json()
    } catch {
      error.value = 'Unexpected server response.'
      return
    }
    if (!response.ok) {
      if (data.errors) {
        error.value = Object.values(data.errors).flat().join(', ')
      } else if (data.message) {
        error.value = data.message
      } else {
        error.value = 'Login failed. Please try again.'
      }
      console.error('Error:', data)
      return
    }

    console.log('Success:', data)
    success.value = true

    setTimeout(() => {
      window.location.href = '/'
    }, 1500)
  } catch (err) {
    error.value = 'Network error. Please try again.';
    console.error('Error:', err);
  }
}
</script>
<template>
  <div>Login Page</div>
  <form @submit="login">
    <div v-if="success" style="color: green; font-size: 16px">{{ success }}</div>
    <div v-if="error" style="color: red; font-size: 16px">{{ error }}</div>
    <input
      v-model="formData.email"
      type="email"
      placeholder="enter your email"
      style="font-size: 16px; padding: 12px"
      required
    /><br />
    <input
      v-model="formData.password"
      type="password"
      placeholder="enter your password"
      style="font-size: 16px; padding: 12px"
      required
    /><br />

    <input type="submit" value="submit" />
  </form>
</template>
