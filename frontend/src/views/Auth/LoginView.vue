<script setup>
import { ref, watch } from 'vue'
import { api } from '@/config/api'

const error    = ref('')
const success  = ref(false)
const loading  = ref(false)
const showPass = ref(false)
const shaking  = ref(false)

// Auto-clear error after 4 s and shake the card
watch(error, (val) => {
  if (!val) return
  shaking.value = true
  setTimeout(() => { shaking.value = false }, 500)
  setTimeout(() => { error.value = '' }, 4000)
})

const formData = ref({ email: '', password: '' })

const login = async (e) => {
  e.preventDefault()
  error.value = ''
  success.value = false
  loading.value = true
  try {
    const response = await api('/login', {
      method: 'POST',
      body: JSON.stringify(formData.value),
    })
    let data
    try { data = await response.json() } catch {
      error.value = 'Unexpected server response.'; return
    }
    if (!response.ok) {
      error.value = data.errors
        ? Object.values(data.errors).flat().join(' ')
        : data.message || 'Login failed. Please try again.'
      return
    }
    success.value = true
    setTimeout(() => { window.location.href = '/' }, 1500)
  } catch {
    error.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="loginPage">
    <div class="loginCard" :class="{ shake: shaking }">

      <router-link to="/" class="logo">doctuss</router-link>

      <div class="cardHeader">
        <h1 class="cardTitle">Welcome back</h1>
        <p class="cardSub">Sign in to continue</p>
      </div>

      <Transition name="slide">
        <p v-if="error" class="alertError">{{ error }}</p>
      </Transition>
      <Transition name="slide">
        <p v-if="success" class="alertSuccess">Login successful! Redirecting…</p>
      </Transition>

      <form @submit="login" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <div class="inputWrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input id="email" v-model="formData.email" type="email" placeholder="you@example.com" required autocomplete='off' />
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="inputWrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input id="password" v-model="formData.password" :type="showPass ? 'text' : 'password'" placeholder="••••••••" required autocomplete="off" />
            <button type="button" class="eyeBtn" @click="showPass = !showPass">
              <svg v-if="!showPass" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <div class="forgotRow">
          <a href="#" class="forgot">Forgot password?</a>
        </div>

        <button type="submit" class="submitBtn" :disabled="loading">
          <span v-if="!loading">Sign In</span>
          <span v-else class="spinner"></span>
        </button>
      </form>

      <p class="registerLink">No account? <router-link to="/register">Create one</router-link></p>

    </div>
  </div>
</template>

<style scoped>
/* Page — hero image bg */
.loginPage {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  overflow: hidden;
}
.loginPage::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('@/assets/pictures/medical1.jpg');
  background-size: cover;
  background-position: center;
  animation: zoomBg 12s ease-in-out infinite alternate;
  z-index: 0;
}
.loginPage::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 0;
}
@keyframes zoomBg {
  from { transform: scale(1); }
  to   { transform: scale(1.1); }
}

/* Card — brand yellow, hard black border like the navbar / step cards */
.loginCard {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
  background: #F6D506;
  border: 3px solid #000;
  border-radius: 28px;
  padding: 52px 52px;
  box-shadow: 8px 8px 0 #000;
  display: flex;
  flex-direction: column;
  gap: 26px;
}

/* Logo */
.logo {
  font-size: 30px;
  font-weight: 900;
  color: #000;
  text-decoration: none;
  letter-spacing: -1px;
  display: inline-block;
}

/* Header */
.cardHeader { display: flex; flex-direction: column; gap: 3px; }
.cardTitle  { font-size: 30px; font-weight: 900; color: #000; margin: 0; }
.cardSub    { font-size: 15px; color: rgba(0,0,0,0.5); margin: 0; font-weight: 500; }

/* Alerts — plain text only */
.alertError {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #900;
}
.alertSuccess {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #060;
}
.slide-enter-active { transition: all 0.25s ease; }
.slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-4px); }

/* Card shake */
@keyframes shake {
  0%,100% { transform: translateX(0); }
  15%     { transform: translateX(-7px); }
  30%     { transform: translateX(7px); }
  45%     { transform: translateX(-5px); }
  60%     { transform: translateX(5px); }
  75%     { transform: translateX(-3px); }
  90%     { transform: translateX(3px); }
}
.shake { animation: shake 0.45s ease; }

/* Form */
form { display: flex; flex-direction: column; gap: 14px; }

.field { display: flex; flex-direction: column; gap: 6px; }
.field label {
  font-size: 11px; font-weight: 800;
  color: rgba(0,0,0,0.55);
  letter-spacing: 1px; text-transform: uppercase;
}

/* Input wrap — white on yellow */
.inputWrap {
  display: flex; align-items: center;
  background: #fff;
  border: 2px solid #000;
  border-radius: 12px;
  padding: 0 14px;
  gap: 10px;
  transition: box-shadow 0.2s;
}
.inputWrap:focus-within { box-shadow: 4px 4px 0 #000; }
.inputWrap svg { color: rgba(0,0,0,0.3); flex-shrink: 0; transition: color 0.2s; }
.inputWrap:focus-within svg { color: #000; }

.inputWrap input {
  flex: 1; padding: 15px 0;
  background: transparent; border: none; outline: none;
  color: #000; font-size: 15px; font-weight: 600;
}
.inputWrap input::placeholder { color: #bbb; font-weight: 400; }

.eyeBtn {
  background: none; border: none; cursor: pointer;
  display: flex; align-items: center;
  color: rgba(0,0,0,0.3); transition: color 0.2s; padding: 0;
}
.eyeBtn:hover { color: #000; }

/* Forgot */
.forgotRow { display: flex; justify-content: flex-end; margin-top: -2px; }
.forgot {
  font-size: 12px; font-weight: 700;
  color: rgba(0,0,0,0.45); text-decoration: none; transition: color 0.2s;
}
.forgot:hover { color: #000; }

/* Submit — black pill, yellow text */
.submitBtn {
  width: 100%; padding: 16px;
  border-radius: 50px;
  border: 2px solid #000;
  background: #000;
  color: #F6D506; font-size: 15px; font-weight: 800; letter-spacing: 0.5px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
  margin-top: 4px;
}
.submitBtn:hover:not(:disabled) {
  background: #111;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
.submitBtn:active:not(:disabled) { transform: translateY(1px); }
.submitBtn:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(246,213,6,0.3);
  border-top-color: #F6D506;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Footer */
.registerLink { text-align: center; font-size: 13px; color: rgba(0,0,0,0.5); margin: 0; font-weight: 500; }
.registerLink a { color: #000; font-weight: 800; text-decoration: none; border-bottom: 2px solid #000; padding-bottom: 1px; }
.registerLink a:hover { opacity: 0.6; }

/* Mobile */
@media (max-width: 480px) {
  .loginCard { padding: 32px 24px; border-radius: 20px; box-shadow: 5px 5px 0 #000; }
  .cardTitle { font-size: 22px; }
}
</style>
