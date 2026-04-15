<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { api } from '@/config/api'

const error   = ref('')
const success = ref(false)
const loading = ref(false)
const shaking = ref(false)
const showPass     = ref(false)
const showConfirm  = ref(false)
const specialties  = ref([])

const cities = [
  'Casablanca', 'Rabat', 'Marrakech', 'Fes', 'Tangier', 
  'Agadir', 'Meknes', 'Oujda', 'Kenitra', 'Tetouan', 'Other'
]

const formData = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  gender: '',
  birthday: '',
  role: 'client',
  doctor_specialty_id: '',
  experience_start_date: '',
  hospital_name: '',
  city: '',
  phone_number: '',
  bio: '',
})

onMounted(async () => {
  try {
    const res = await api('/specialties')
    if (res.ok) {
      const json = await res.json()
      specialties.value = json.data || []
    }
  } catch (e) {
    console.error('Failed to load specialties', e)
  }
})

const maxBirthday = computed(() => {
  const d = new Date()
  if (formData.value.role === 'doctor') {
    d.setFullYear(d.getFullYear() - 18)
  }
  return d.toISOString().split('T')[0]
})

const minExperienceDate = computed(() => {
  if (formData.value.birthday) {
    const d = new Date(formData.value.birthday)
    d.setFullYear(d.getFullYear() + 18)
    return d.toISOString().split('T')[0]
  }
  return ''
})

const maxExperienceDate = new Date().toISOString().split('T')[0]

watch(error, (val) => {
  if (!val) return
  shaking.value = true
  setTimeout(() => { shaking.value = false }, 500)
  setTimeout(() => { error.value = '' }, 4000)
})

const register = async (e) => {
  e.preventDefault()
  error.value = ''
  success.value = false
  loading.value = true

  try {
    const response = await api('/register', {
      method: 'POST',
      body: JSON.stringify(formData.value),
    })

    let data
    try { data = await response.json() } catch {
      error.value = 'Unexpected server response.';
      return;
    }

    if (!response.ok) {
      error.value = data.errors
        ? Object.values(data.errors).flat().join(' ')
        : data.message || 'Registration failed. Please try again.'
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
  <div class="registerPage">
    <div class="registerCard" :class="{ shake: shaking }">

      <router-link to="/" class="logo">doctuss</router-link>

      <div class="cardHeader">
        <h1 class="cardTitle">Create account</h1>
        <p class="cardSub">Join Doctuss and find your doctor</p>
      </div>

      <Transition name="slide">
        <p v-if="error" class="alertError">{{ error }}</p>
      </Transition>
      <Transition name="slide">
        <p v-if="success" class="alertSuccess">Account created! Redirecting…</p>
      </Transition>

      <form @submit="register" novalidate>

        <!-- Role Toggle -->
        <div class="roleToggle">
          <label class="roleBtn" :class="{ active: formData.role === 'client' }">
            <input type="radio" v-model="formData.role" value="client" style="display:none">
            I am a Patient
          </label>
          <label class="roleBtn" :class="{ active: formData.role === 'doctor' }">
             <input type="radio" v-model="formData.role" value="doctor" style="display:none">
             I am a Doctor
          </label>
        </div>

        <!-- Name -->
        <div class="field">
          <label for="reg-name">Full name</label>
          <div class="inputWrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input id="reg-name" v-model="formData.name" type="text" placeholder="Your full name" required autocomplete="name" />
          </div>
        </div>

        <!-- Email -->
        <div class="field">
          <label for="reg-email">Email</label>
          <div class="inputWrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input id="reg-email" v-model="formData.email" type="email" placeholder="you@example.com" required autocomplete="email" />
          </div>
        </div>

        <!-- Password row -->
        <div class="fieldRow">
          <div class="field">
            <label for="reg-password">Password</label>
            <div class="inputWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input id="reg-password" v-model="formData.password" :type="showPass ? 'text' : 'password'" placeholder="••••••••" required autocomplete="new-password" />
              <button type="button" class="eyeBtn" @click="showPass = !showPass">
                <svg v-if="!showPass" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>

          <div class="field">
            <label for="reg-confirm">Confirm</label>
            <div class="inputWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input id="reg-confirm" v-model="formData.password_confirmation" :type="showConfirm ? 'text' : 'password'" placeholder="••••••••" required autocomplete="new-password" />
              <button type="button" class="eyeBtn" @click="showConfirm = !showConfirm">
                <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Gender + Birthday row -->
        <div class="fieldRow">
          <div class="field">
            <label for="reg-gender">Gender</label>
            <div class="inputWrap selectWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M18.66 5.34l1.41-1.41"/></svg>
              <select id="reg-gender" v-model="formData.gender" required>
                <option value="" disabled>Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="reg-birthday">Birthday</label>
            <div class="inputWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <input id="reg-birthday" v-model="formData.birthday" type="date" :max="maxBirthday" required />
            </div>
          </div>
        </div>

        <!-- DOCTOR EXTRA FIELDS -->
        <div v-if="formData.role === 'doctor'" class="doctorFieldsWrapper">
          <h3 class="doctorFieldsTitle">Professional Information</h3>
          
          <div class="fieldRow">
            <div class="field">
              <label>Specialty</label>
              <div class="inputWrap selectWrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <select v-model="formData.doctor_specialty_id" required>
                   <option value="" disabled>Select Specialty</option>
                   <option v-for="spec in specialties" :key="spec.id" :value="spec.id">{{ spec.name }}</option>
                </select>
              </div>
            </div>

            <div class="field">
               <label>Experience Start Date</label>
               <div class="inputWrap">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                 <input type="date" v-model="formData.experience_start_date" :min="minExperienceDate" :max="maxExperienceDate" required />
               </div>
            </div>
          </div>

          <div class="fieldRow">
            <div class="field">
              <label>Hospital/Clinic Name</label>
              <div class="inputWrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/><path d="M10 9h4"/><path d="M12 7v4"/></svg>
                <input type="text" v-model="formData.hospital_name" placeholder="City General Hospital" required />
              </div>
            </div>
            <div class="field">
              <label>City</label>
              <div class="inputWrap selectWrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <select v-model="formData.city" required>
                  <option value="" disabled>Select City</option>
                  <option v-for="city in cities" :key="city" :value="city">{{ city }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="field">
            <label>Phone Number</label>
            <div class="inputWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <input type="text" v-model="formData.phone_number" placeholder="+1 234 567 8900" required />
            </div>
          </div>

          <div class="field">
            <label>Professional Bio</label>
            <div class="inputWrap" style="padding: 12px 12px 12px 14px; align-items: flex-start;">
              <textarea v-model="formData.bio" rows="4" placeholder="Briefly describe your experience and practice..." required style="width:100%; border:none; outline:none; resize:vertical; font-family:inherit; font-weight:600; font-size:14px; color:#000;"></textarea>
            </div>
          </div>
        </div>

        <button type="submit" class="submitBtn" :disabled="loading">
          <span v-if="!loading">Create Account</span>
          <span v-else class="spinner"></span>
        </button>

      </form>

      <p class="loginLink">Already have an account? <router-link to="/login">Sign in</router-link></p>

    </div>
  </div>
</template>

<style scoped>
/* Page — same hero background as login */
.registerPage {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  overflow: hidden;
}
.registerPage::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('@/assets/pictures/medical1.jpg');
  background-size: cover;
  background-position: center;
  animation: zoomBg 12s ease-in-out infinite alternate;
  z-index: 0;
}
.registerPage::after {
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

/* Card */
.registerCard {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 560px;
  background: #F6D506;
  border: 3px solid #000;
  border-radius: 28px;
  padding: 52px 52px;
  box-shadow: 8px 8px 0 #000;
  display: flex;
  flex-direction: column;
  gap: 22px;
  overflow: hidden;
}

/* Logo */
.logo {
  font-size: 28px;
  font-weight: 900;
  color: #000;
  text-decoration: none;
  letter-spacing: -1px;
}

/* Header */
.cardHeader { display: flex; flex-direction: column; gap: 3px; }
.cardTitle  { font-size: 28px; font-weight: 900; color: #000; margin: 0; }
.cardSub    { font-size: 15px; color: rgba(0,0,0,0.5); margin: 0; font-weight: 500; }

/* Alerts — plain text */
.alertError   { margin: 0; font-size: 13px; font-weight: 700; color: #900; }
.alertSuccess { margin: 0; font-size: 13px; font-weight: 700; color: #060; }
.slide-enter-active { transition: all 0.25s ease; }
.slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-4px); }

/* Form */
form { display: flex; flex-direction: column; gap: 14px; }

/* Role Toggle */
.roleToggle {
  display: flex;
  background: rgba(0,0,0,0.05);
  border: 3px solid #000;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 6px;
}
.roleBtn {
  flex: 1;
  text-align: center;
  padding: 12px 0;
  font-size: 14px;
  font-weight: 800;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.2s;
  opacity: 0.6;
}
.roleBtn.active {
  background: #000;
  color: #F6D506;
  opacity: 1;
}

/* Doctor Fields wrapper */
.doctorFieldsWrapper {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-top: 10px;
  padding-top: 16px;
  border-top: 3px dashed #000;
}
.doctorFieldsTitle {
  font-size: 15px; font-weight: 900; color: #000; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 0.5px;
}

/* Two-col row */
.fieldRow {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  min-width: 0;
}

.field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.field label {
  font-size: 11px; font-weight: 800;
  color: rgba(0,0,0,0.55);
  letter-spacing: 1px; text-transform: uppercase;
}

/* Input wrap */
.inputWrap {
  display: flex; align-items: center;
  background: #fff;
  border: 2px solid #000;
  border-radius: 12px;
  padding: 0 12px;
  gap: 8px;
  transition: box-shadow 0.2s;
  min-width: 0;
}
.inputWrap:focus-within { box-shadow: 4px 4px 0 #000; }
.inputWrap svg { color: rgba(0,0,0,0.3); flex-shrink: 0; transition: color 0.2s; }
.inputWrap:focus-within svg { color: #000; }

.inputWrap input,
.inputWrap select {
  flex: 1; min-width: 0;
  padding: 15px 0;
  background: transparent; border: none; outline: none;
  color: #000; font-size: 15px; font-weight: 600;
  font-family: inherit;
}
.inputWrap input::placeholder { color: #bbb; font-weight: 400; }

/* Select */
.selectWrap select { cursor: pointer; appearance: none; }

/* Date input */
input[type="date"]::-webkit-calendar-picker-indicator {
  opacity: 0.4; cursor: pointer;
}

.eyeBtn {
  background: none; border: none; cursor: pointer;
  display: flex; align-items: center;
  color: rgba(0,0,0,0.3); transition: color 0.2s; padding: 0; flex-shrink: 0;
}
.eyeBtn:hover { color: #000; }

/* Submit */
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
.loginLink { text-align: center; font-size: 13px; color: rgba(0,0,0,0.5); margin: 0; font-weight: 500; }
.loginLink a { color: #000; font-weight: 800; text-decoration: none; border-bottom: 2px solid #000; padding-bottom: 1px; }
.loginLink a:hover { opacity: 0.6; }

/* Card shake on error */
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

/* Mobile */
@media (max-width: 480px) {
  .registerCard { padding: 32px 24px; border-radius: 20px; box-shadow: 5px 5px 0 #000; }
  .cardTitle { font-size: 22px; }
  .fieldRow { grid-template-columns: 1fr; gap: 14px; }
}
</style>
