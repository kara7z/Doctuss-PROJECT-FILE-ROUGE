<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user } = useAuth()
const router = useRouter()

const profile = ref(null)
const loading = ref(true)
const error = ref(null)

const editMode = ref(false)
const editForm = ref({
  profile_picture: '',
  banner_picture: '',
  bio: '',
  phone_number: '',
  location_link: ''
})
const updating = ref(false)
const updateError = ref(null)
const updateSuccess = ref(false)

const verificationModal = ref(false)
const verificationForm = ref({
  document: null,
  documentPreview: null,
  submitting: false,
  error: null,
  success: false
})

const schedules = ref([])
const workingDates = ref([])
const scheduleModal = ref(false)
const scheduleForm = ref({
  id: null,
  day_of_week: 0,
  start_time: '',
  end_time: '',
  submitting: false,
  error: null
})
const workingDateModal = ref(false)
const workingDateForm = ref({
  working_date: '',
  start_time: '',
  end_time: '',
  submitting: false,
  error: null
})

const dayNames = computed(() => [
  t('days.sunday'),
  t('days.monday'),
  t('days.tuesday'),
  t('days.wednesday'),
  t('days.thursday'),
  t('days.friday'),
  t('days.saturday')
])

const fetchProfile = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api('/doctor/profile')
    if (res.ok) {
      const { data } = await res.json()
      profile.value = data
      editForm.value = {
        profile_picture: data.profile_picture || '',
        banner_picture: data.banner_picture || '',
        bio: data.bio || '',
        phone_number: data.phone_number || '',
        location_link: data.location_link || ''
      }
    } else {
      error.value = t('myProfile.loadingProfile')
    }
  } catch (e) {
    error.value = t('myProfile.loadingProfile')
  } finally {
    loading.value = false
  }
}

const fetchSchedules = async () => {
  try {
    const res = await api('/schedules')
    if (res.ok) {
      const { data } = await res.json()
      schedules.value = data
    }
  } catch (e) {
    console.error('Error loading schedules', e)
  }
}

const openScheduleModal = (schedule = null) => {
  if (schedule) {
    scheduleForm.value = {
      id: schedule.id,
      day_of_week: schedule.day_of_week,
      start_time: schedule.start_time.substring(0, 5),
      end_time: schedule.end_time.substring(0, 5),
      submitting: false,
      error: null
    }
  } else {
    scheduleForm.value = {
      id: null,
      day_of_week: 0,
      start_time: '',
      end_time: '',
      submitting: false,
      error: null
    }
  }
  scheduleModal.value = true
}

const saveSchedule = async () => {
  scheduleForm.value.submitting = true
  scheduleForm.value.error = null
  
  if (!scheduleForm.value.id) {
    const existingSchedule = schedules.value.find(
      s => s.day_of_week === scheduleForm.value.day_of_week
    )
    if (existingSchedule) {
      scheduleForm.value.error = `${dayNames.value[scheduleForm.value.day_of_week]} ${t('myProfile.dayAlreadyScheduled')}`
      scheduleForm.value.submitting = false
      return
    }
  }
  
  try {
    const method = scheduleForm.value.id ? 'PATCH' : 'POST'
    const url = scheduleForm.value.id ? `/schedules/${scheduleForm.value.id}` : '/schedules'
    
    const payload = {
      day_of_week: scheduleForm.value.day_of_week,
      start_time: scheduleForm.value.start_time.substring(0, 5),
      end_time: scheduleForm.value.end_time.substring(0, 5)
    }
    
    const res = await api(url, {
      method,
      body: JSON.stringify(payload)
    })
    if (res.ok) {
      scheduleModal.value = false
      await fetchSchedules()
    } else {
      const data = await res.json()
      scheduleForm.value.error = data.message || t('common.error')
    }
  } catch (e) {
    scheduleForm.value.error = t('common.error')
  } finally {
    scheduleForm.value.submitting = false
  }
}

const deleteSchedule = async (id) => {
  if (!confirm(t('myProfile.deleteConfirm'))) return
  try {
    const res = await api(`/schedules/${id}`, { method: 'DELETE' })
    if (res.ok) {
      await fetchSchedules()
    }
  } catch (e) {
    console.error('Error deleting schedule', e)
  }
}

const openWorkingDateModal = () => {
  workingDateForm.value = {
    working_date: '',
    start_time: '',
    end_time: '',
    submitting: false,
    error: null
  }
  workingDateModal.value = true
}

const saveWorkingDate = async () => {
  workingDateForm.value.submitting = true
  workingDateForm.value.error = null
  try {
    const payload = {
      working_date: workingDateForm.value.working_date,
      start_time: workingDateForm.value.start_time.substring(0, 5),
      end_time: workingDateForm.value.end_time.substring(0, 5)
    }
    
    const res = await api('/working-dates', {
      method: 'POST',
      body: JSON.stringify(payload)
    })
    if (res.ok) {
      workingDateModal.value = false
      alert('Working date added successfully')
    } else {
      const data = await res.json()
      workingDateForm.value.error = data.message || t('common.error')
    }
  } catch (e) {
    workingDateForm.value.error = t('common.error')
  } finally {
    workingDateForm.value.submitting = false
  }
}

const updateProfile = async () => {
  updating.value = true
  updateError.value = null
  updateSuccess.value = false
  try {
    const res = await api('/doctor/profile', {
      method: 'PATCH',
      body: JSON.stringify(editForm.value)
    })
    if (res.ok) {
      updateSuccess.value = true
      await fetchProfile()
      setTimeout(() => {
        editMode.value = false
        updateSuccess.value = false
      }, 2000)
    } else {
      const data = await res.json()
      updateError.value = data.message || t('common.error')
    }
  } catch (e) {
    updateError.value = t('common.error')
  } finally {
    updating.value = false
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (!file.type.startsWith('image/')) {
      verificationForm.value.error = t('myProfile.selectImage')
      return
    }
    if (file.size > 5 * 1024 * 1024) {
      verificationForm.value.error = t('myProfile.fileTooLarge')
      return
    }
    verificationForm.value.document = file
    verificationForm.value.error = null
    
    const reader = new FileReader()
    reader.onload = (e) => {
      verificationForm.value.documentPreview = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const submitVerificationRequest = async () => {
  if (!verificationForm.value.document) {
    verificationForm.value.error = t('myProfile.selectDocument')
    return
  }
  
  verificationForm.value.submitting = true
  verificationForm.value.error = null
  try {
    const formData = new FormData()
    formData.append('document', verificationForm.value.document)
    
    const res = await api('/verification-requests', {
      method: 'POST',
      body: formData
    })
    if (res.ok) {
      verificationForm.value.success = true
      setTimeout(() => {
        verificationModal.value = false
        verificationForm.value = {
          document: null,
          documentPreview: null,
          submitting: false,
          error: null,
          success: false
        }
        fetchProfile()
      }, 2000)
    } else {
      const data = await res.json()
      verificationForm.value.error = data.message || t('common.error')
    }
  } catch (e) {
    verificationForm.value.error = t('common.error')
  } finally {
    verificationForm.value.submitting = false
  }
}

const hasPendingRequest = computed(() => {
  return profile.value?.verification_requests?.some(req => req.status === 'pending')
})

const experienceYears = computed(() => {
  if (!profile.value?.experience_start_date) return 0
  const start = new Date(profile.value.experience_start_date)
  return Math.max(0, new Date().getFullYear() - start.getFullYear())
})

onMounted(() => {
  if (!user.value || user.value.role !== 'doctor') {
    router.push('/')
    return
  }
  fetchProfile()
  fetchSchedules()
})
</script>

<template>
  <div class="pageLayout">
    <section class="profileSection">
      <div class="container">
        <h1 class="pageTitle">{{ t('myProfile.title') }} <span class="highlightText">{{ t('myProfile.titleHighlight') }}</span></h1>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('myProfile.loadingProfile') }}</h2>
        </div>

        <div v-else-if="error" class="stateCard errorState">
          <h2>{{ error }}</h2>
        </div>

        <div v-else-if="profile" class="profileContent">
          <!-- Banner Section -->
          <div v-if="profile.banner_picture" class="bannerSection" :style="{ backgroundImage: `url(${profile.banner_picture})` }">
            <div class="bannerOverlay">
              <span v-if="!editMode && profile.is_verified" class="verificationBadge verified">
                {{ t('myProfile.verified') }}
              </span>
            </div>
          </div>

          <!-- Profile Info -->
          <div class="profileCard" :class="{ noBanner: !profile.banner_picture }">
            <span v-if="!profile.banner_picture && !editMode && profile.is_verified" class="verificationBadge verified">
              {{ t('myProfile.verified') }}
            </span>
            <div class="profileHeader">
              <div class="avatarSection">
                <div class="avatar" v-if="profile.profile_picture">
                  <img :src="profile.profile_picture" :alt="user.name">
                </div>
                <div class="avatar placeholder" v-else>
                  {{ user.name.substring(0, 2).toUpperCase() }}
                </div>
              </div>
              <div class="profileInfo">
                <h2>{{ user.name }}</h2>
                <p class="specialty">{{ profile.specialty?.name }}</p>
                <div class="stats">
                  <div class="stat">
                    <span class="statValue">{{ experienceYears }}+</span>
                    <span class="statLabel">{{ t('myProfile.yearsExp') }}</span>
                  </div>
                  <div class="stat">
                    <span class="statValue">{{ parseFloat(profile.avg_rating || 0).toFixed(1) }}</span>
                    <span class="statLabel">{{ t('myProfile.rating') }}</span>
                  </div>
                </div>
              </div>
              <div class="actions">
                <button v-if="!editMode" class="actionBtn" @click="editMode = true">{{ t('myProfile.editProfile') }}</button>
                <button v-if="!profile.is_verified && !hasPendingRequest && !editMode" class="actionBtn verifyBtn" @click="verificationModal = true">
                  {{ t('myProfile.requestVerification') }}
                </button>
                <span v-if="hasPendingRequest && !editMode" class="pendingLabel">{{ t('myProfile.verificationPending') }}</span>
              </div>
            </div>

            <!-- Edit Mode -->
            <div v-if="editMode" class="editSection">
              <h3>{{ t('myProfile.editProfileTitle') }}</h3>
              
              <div v-if="updateSuccess" class="alert successAlert">{{ t('myProfile.profileUpdated') }}</div>
              <div v-if="updateError" class="alert errorAlert">{{ updateError }}</div>

              <div class="formGroup">
                <label>{{ t('myProfile.profilePictureUrl') }}</label>
                <input type="text" class="brutalistInput" v-model="editForm.profile_picture" placeholder="https://example.com/image.jpg">
              </div>

              <div class="formGroup">
                <label>{{ t('myProfile.bannerPictureUrl') }}</label>
                <input type="text" class="brutalistInput" v-model="editForm.banner_picture" placeholder="https://example.com/banner.jpg">
              </div>

              <div class="formGroup">
                <label>{{ t('myProfile.phoneNumber') }}</label>
                <input type="text" class="brutalistInput" v-model="editForm.phone_number" placeholder="+1234567890">
              </div>

              <div class="formGroup">
                <label>{{ t('myProfile.bio') }}</label>
                <textarea class="brutalistInput" v-model="editForm.bio" rows="5" :placeholder="t('myProfile.bioPlaceholder')"></textarea>
              </div>

              <div class="formGroup">
                <label>{{ t('myProfile.locationLink') }}</label>
                <input type="url" class="brutalistInput" v-model="editForm.location_link" placeholder="https://maps.google.com/...">
                <p class="fieldHint">{{ t('myProfile.locationHint') }}</p>
              </div>

              <div class="formActions">
                <button class="actionBtn" :disabled="updating" @click="updateProfile">
                  {{ updating ? t('myProfile.saving') : t('myProfile.saveChanges') }}
                </button>
                <button class="actionBtn cancelBtn" @click="editMode = false; updateError = null">{{ t('myProfile.cancel') }}</button>
              </div>
            </div>

            <!-- View Mode -->
            <div v-else class="viewSection">
              <div class="infoBlock">
                <h3>{{ t('myProfile.about') }}</h3>
                <p>{{ profile.bio || t('myProfile.noBio') }}</p>
              </div>

              <div class="infoGrid">
                <div class="infoItem">
                  <span class="infoLabel">{{ t('myProfile.hospital') }}</span>
                  <span class="infoValue">{{ profile.hospital_name }}</span>
                </div>
                <div class="infoItem">
                  <span class="infoLabel">{{ t('myProfile.city') }}</span>
                  <span class="infoValue">{{ profile.city }}</span>
                </div>
                <div class="infoItem">
                  <span class="infoLabel">{{ t('myProfile.phone') }}</span>
                  <span class="infoValue">{{ profile.phone_number || t('myProfile.notProvided') }}</span>
                </div>
                <div class="infoItem">
                  <span class="infoLabel">{{ t('myProfile.specialty') }}</span>
                  <span class="infoValue">{{ profile.specialty?.name }}</span>
                </div>
                <div class="infoItem">
                  <span class="infoLabel">{{ t('myProfile.location') }}</span>
                  <span class="infoValue">{{ profile.city }}</span>
                  <a 
                    v-if="profile.location_link" 
                    :href="profile.location_link" 
                    target="_blank" 
                    class="mapLink"
                  >
                    {{ t('myProfile.viewOnMap') }}
                  </a>
                </div>
              </div>

              <!-- Verification Requests History -->
              <div v-if="profile.verification_requests?.length > 0" class="verificationHistory">
                <h3>{{ t('myProfile.verificationRequests') }}</h3>
                <div class="requestsList">
                  <div v-for="req in profile.verification_requests" :key="req.id" class="requestItem">
                    <div class="requestHeader">
                      <span class="requestStatus" :class="'status-' + req.status">{{ req.status }}</span>
                      <span class="requestDate">{{ new Date(req.created_at).toLocaleDateString() }}</span>
                    </div>
                    <p v-if="req.admin_notes" class="adminNotes"><strong>{{ t('myProfile.adminNotes') }}</strong> {{ req.admin_notes }}</p>
                  </div>
                </div>
              </div>

              <!-- Working Schedule Section -->
              <div class="scheduleSection">
                <div class="sectionHeader">
                  <h3>{{ t('myProfile.weeklySchedule') }}</h3>
                  <button v-if="profile.is_verified" class="actionBtn" @click="openScheduleModal()">{{ t('myProfile.addSchedule') }}</button>
                </div>
                <div v-if="!profile.is_verified" class="verificationWarning">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                  <p>{{ t('myProfile.verificationWarning') }}</p>
                </div>
                <div v-else-if="schedules.length === 0" class="emptyState">
                  <p>{{ t('myProfile.noSchedule') }}</p>
                </div>
                <div v-else class="scheduleGrid">
                  <div v-for="schedule in schedules" :key="schedule.id" class="scheduleCard">
                    <div class="scheduleDay">{{ dayNames[schedule.day_of_week] }}</div>
                    <div class="scheduleTime">{{ schedule.start_time.substring(0, 5) }} - {{ schedule.end_time.substring(0, 5) }}</div>
                    <div class="scheduleActions">
                      <button class="iconBtn editBtn" @click="openScheduleModal(schedule)">{{ t('myProfile.edit') }}</button>
                      <button class="iconBtn deleteBtn" @click="deleteSchedule(schedule.id)">{{ t('myProfile.delete') }}</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Schedule Modal -->
    <div class="modalOverlay" v-if="scheduleModal">
      <div class="modalContent">
        <button class="closeModal" @click="scheduleModal = false">&times;</button>
        <h2 class="modalTitle">{{ scheduleForm.id ? t('myProfile.editScheduleTitle') : t('myProfile.addScheduleTitle') }}</h2>
        
        <div v-if="scheduleForm.error" class="alert errorAlert">{{ scheduleForm.error }}</div>
        
        <div class="formGroup">
          <label>{{ t('myProfile.dayOfWeek') }}</label>
          <select class="brutalistInput" v-model.number="scheduleForm.day_of_week">
            <option v-for="(day, index) in dayNames" :key="index" :value="index">{{ day }}</option>
          </select>
        </div>

        <div class="formGroup">
          <label>{{ t('myProfile.startTime') }}</label>
          <input type="time" class="brutalistInput" v-model="scheduleForm.start_time" required>
        </div>

        <div class="formGroup">
          <label>{{ t('myProfile.endTime') }}</label>
          <input type="time" class="brutalistInput" v-model="scheduleForm.end_time" required>
        </div>

        <button class="submitBtn" :disabled="scheduleForm.submitting" @click="saveSchedule">
          {{ scheduleForm.submitting ? t('myProfile.saving') : t('myProfile.saveSchedule') }}
        </button>
      </div>
    </div>

    <!-- Verification Modal -->
    <div class="modalOverlay" v-if="verificationModal">
      <div class="modalContent">
        <button class="closeModal" @click="verificationModal = false">&times;</button>
        <h2 class="modalTitle">{{ t('myProfile.requestVerificationTitle') }}</h2>
        
        <div v-if="verificationForm.success" class="alert successAlert">
          {{ t('myProfile.verificationSuccess') }}
        </div>
        <div v-else>
          <div v-if="verificationForm.error" class="alert errorAlert">{{ verificationForm.error }}</div>
          
          <p class="modalDesc">{{ t('myProfile.uploadDocument') }}</p>
          
          <div class="formGroup">
            <label>{{ t('myProfile.documentImage') }}</label>
            <input type="file" accept="image/*" class="fileInput" @change="handleFileSelect">
            <p class="fileHint">{{ t('myProfile.fileHint') }}</p>
          </div>

          <div v-if="verificationForm.documentPreview" class="imagePreview">
            <img :src="verificationForm.documentPreview" alt="Document preview">
          </div>

          <button class="submitBtn" :disabled="verificationForm.submitting || !verificationForm.document" @click="submitVerificationRequest">
            {{ verificationForm.submitting ? t('myProfile.submitting') : t('myProfile.submitRequest') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profileSection {
  padding: 120px 0 80px;
  background-color: #f4f4f4;
  background-image: radial-gradient(rgba(0, 0, 0, 0.15) 2px, transparent 0);
  background-size: 24px 24px;
  min-height: calc(100vh - 80px);
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 6%;
}

.pageTitle {
  font-size: 48px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 40px;
  text-align: center;
  letter-spacing: -2px;
  color: #000;
}
.highlightText {
  background: #F6D506;
  padding: 0 10px;
}

.stateCard {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px #000;
  padding: 60px 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.stateCard h2 {
  font-size: 28px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 6px solid #000;
  border-bottom-color: #F6D506;
  border-radius: 50%;
  animation: rotation 1s linear infinite;
}
@keyframes rotation {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.profileContent {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.bannerSection {
  height: 250px;
  background-color: #fff;
  background-size: cover;
  background-position: center;
  border: 4px solid #000;
  position: relative;
}
.bannerOverlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.1));
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  padding: 20px;
}

.verificationBadge {
  padding: 10px 20px;
  background: #ff5252;
  color: #fff;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  font-size: 14px;
  box-shadow: 4px 4px 0px #000;
}
.verificationBadge.verified {
  background: #4caf50;
}

.profileCard {
  background: #fff;
  border: 4px solid #000;
  border-top: none;
  box-shadow: 12px 12px 0px #000;
  padding: 40px;
}
.profileCard.noBanner {
  border-top: 4px solid #000;
}

.profileHeader {
  display: flex;
  gap: 30px;
  align-items: flex-start;
  margin-top: -80px;
  margin-bottom: 30px;
  position: relative;
  z-index: 1;
}
.profileCard.noBanner .profileHeader {
  margin-top: 0;
}
.profileCard.noBanner .verificationBadge {
  position: absolute;
  top: 20px;
  right: 20px;
}
.profileCard {
  position: relative;
}

.avatarSection {
  flex-shrink: 0;
  position: relative;
  z-index: 2;
}
.avatar {
  width: 140px;
  height: 140px;
  border: 6px solid #000;
  border-radius: 50%;
  overflow: hidden;
  box-shadow: 8px 8px 0px #000;
  background: #F6D506;
}
.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar.placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  font-weight: 900;
  color: #000;
}

.profileInfo {
  flex: 1;
}
.profileInfo h2 {
  font-size: 36px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 8px;
  color: #000;
}
.specialty {
  font-size: 18px;
  font-weight: 800;
  color: #666;
  text-transform: uppercase;
  margin: 0 0 20px;
}

.stats {
  display: flex;
  gap: 30px;
}
.stat {
  display: flex;
  flex-direction: column;
}
.statValue {
  font-size: 28px;
  font-weight: 900;
  color: #000;
}
.statLabel {
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  color: #666;
}

.actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: relative;
  z-index: 1;
}
.actionBtn {
  padding: 12px 24px;
  background: #000;
  color: #F6D506;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.2s;
  white-space: nowrap;
  font-family: inherit;
}
.actionBtn:hover:not(:disabled) {
  background: #fff;
  color: #000;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.actionBtn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.verifyBtn {
  background: #4caf50;
  color: #fff;
}
.verifyBtn:hover:not(:disabled) {
  background: #45a049;
  color: #fff;
}
.cancelBtn {
  background: #ff5252;
  color: #fff;
}
.cancelBtn:hover:not(:disabled) {
  background: #ff1744;
  color: #fff;
}

.pendingLabel {
  padding: 12px 24px;
  background: #ff9800;
  color: #000;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  text-align: center;
  box-shadow: 4px 4px 0px #000;
}

.editSection, .viewSection {
  border-top: 3px solid #000;
  padding-top: 30px;
}

.editSection h3, .viewSection h3 {
  font-size: 24px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 20px;
  color: #000;
}

.formGroup {
  margin-bottom: 20px;
}
.formGroup label {
  display: block;
  font-weight: 900;
  text-transform: uppercase;
  font-size: 14px;
  margin-bottom: 8px;
  color: #000;
}
.brutalistInput {
  width: 100%;
  padding: 12px;
  border: 3px solid #000;
  font-weight: 700;
  font-size: 16px;
  font-family: inherit;
  box-shadow: 4px 4px 0px #000;
  outline: none;
  background: #fff;
  color: #000;
}
.brutalistInput:focus {
  transform: translate(2px, 2px);
  box-shadow: 2px 2px 0px #000;
}

.formActions {
  display: flex;
  gap: 16px;
  margin-top: 30px;
}

.alert {
  padding: 12px;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 20px;
  text-align: center;
}
.successAlert {
  background: #4caf50;
  color: #fff;
}
.errorAlert {
  background: #ff5252;
  color: #fff;
}

.infoBlock {
  margin-bottom: 30px;
}
.infoBlock p {
  font-size: 18px;
  line-height: 1.6;
  font-weight: 600;
  color: #222;
}

.infoGrid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}
.infoItem {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 16px;
  border: 3px solid #000;
  background: #f8f8f8;
  box-shadow: 4px 4px 0px #000;
}
.infoLabel {
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  color: #666;
}
.infoValue {
  font-size: 18px;
  font-weight: 900;
  color: #000;
}

.verificationHistory h3 {
  margin-bottom: 16px;
}
.requestsList {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.requestItem {
  padding: 16px;
  border: 3px solid #000;
  background: #fff;
  box-shadow: 4px 4px 0px #000;
}
.requestHeader {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.requestStatus {
  padding: 4px 12px;
  border: 2px solid #000;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
}
.status-pending {
  background: #ff9800;
  color: #000;
}
.status-approved {
  background: #4caf50;
  color: #fff;
}
.status-rejected {
  background: #ff5252;
  color: #fff;
}
.requestDate {
  font-size: 13px;
  font-weight: 700;
  color: #666;
}
.adminNotes {
  font-size: 14px;
  font-weight: 600;
  color: #444;
  margin: 0;
}

.scheduleSection {
  margin-top: 30px;
  padding-top: 30px;
  border-top: 3px solid #000;
  position: relative;
  z-index: 1;
}
.sectionHeader {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.sectionHeader h3 {
  margin: 0;
}
.sectionHeader .actionBtn {
  flex-shrink: 0;
}
.emptyState {
  padding: 40px;
  text-align: center;
  border: 3px dashed #000;
  background: #f8f8f8;
}
.emptyState p {
  font-size: 16px;
  font-weight: 700;
  color: #666;
  margin: 0;
}
.scheduleGrid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}
.scheduleCard {
  padding: 20px;
  border: 3px solid #000;
  background: #fff;
  box-shadow: 4px 4px 0px #000;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.scheduleDay {
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  color: #000;
}
.scheduleTime {
  font-size: 16px;
  font-weight: 700;
  color: #666;
}
.scheduleActions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}
.iconBtn {
  padding: 8px 12px;
  background: #F6D506;
  border: 2px solid #000;
  cursor: pointer;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  box-shadow: 2px 2px 0px #000;
  transition: all 0.2s;
}
.iconBtn:hover {
  transform: translate(-1px, -1px);
  box-shadow: 3px 3px 0px #000;
}
.iconBtn.editBtn {
  background: #2196F3;
  color: #fff;
}
.iconBtn.deleteBtn {
  background: #ff5252;
  color: #fff;
}

select.brutalistInput {
  cursor: pointer;
}

.fieldHint {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  margin: 6px 0 0;
}
.fieldHint a {
  color: #4285F4;
  text-decoration: underline;
  font-weight: 700;
}
.fieldHint a:hover {
  color: #357ae8;
}

.mapLink {
  display: inline-block;
  margin-top: 8px;
  padding: 6px 12px;
  background: #4285F4;
  color: #fff;
  border: 2px solid #000;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  text-decoration: none;
  box-shadow: 2px 2px 0px #000;
  transition: all 0.2s;
}
.mapLink:hover {
  background: #357ae8;
  transform: translate(-1px, -1px);
  box-shadow: 3px 3px 0px #000;
}

.verificationWarning {
  padding: 20px;
  background: #fff3cd;
  border: 3px solid #000;
  box-shadow: 4px 4px 0px #000;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.verificationWarning svg {
  flex-shrink: 0;
  stroke: #856404;
}
.verificationWarning p {
  font-size: 16px;
  font-weight: 700;
  color: #856404;
  margin: 0;
}

/* Modal */
.modalOverlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}
.modalContent {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px #F6D506;
  padding: 40px;
  width: 100%;
  max-width: 500px;
  position: relative;
}
.closeModal {
  position: absolute;
  top: -20px;
  right: -20px;
  width: 48px;
  height: 48px;
  background: #000;
  color: #fff;
  border: 4px solid #fff;
  font-size: 30px;
  font-weight: bold;
  cursor: pointer;
  box-shadow: 4px 4px 0px #F6D506;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}
.closeModal:hover {
  background: #f44336;
  transform: scale(1.1);
}
.modalTitle {
  font-size: 28px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 16px;
  border-bottom: 4px solid #000;
  padding-bottom: 12px;
  color: #000;
}
.modalDesc {
  font-size: 15px;
  font-weight: 600;
  color: #444;
  margin-bottom: 20px;
}
.submitBtn {
  width: 100%;
  padding: 16px;
  background: #F6D506;
  border: 4px solid #000;
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 6px 6px 0px #000;
  transition: all 0.2s;
  margin-top: 10px;
}
.submitBtn:hover:not(:disabled) {
  transform: translate(-2px, -2px);
  box-shadow: 8px 8px 0px #000;
}
.submitBtn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.fileInput {
  width: 100%;
  padding: 12px;
  border: 3px solid #000;
  font-weight: 700;
  font-size: 14px;
  font-family: inherit;
  box-shadow: 4px 4px 0px #000;
  outline: none;
  background: #fff;
  color: #000;
  cursor: pointer;
}
.fileInput::-webkit-file-upload-button {
  padding: 8px 16px;
  background: #F6D506;
  border: 2px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  margin-right: 12px;
}
.fileHint {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  margin: 8px 0 0;
}
.imagePreview {
  margin: 20px 0;
  border: 3px solid #000;
  padding: 10px;
  background: #f8f8f8;
  box-shadow: 4px 4px 0px #000;
}
.imagePreview img {
  width: 100%;
  height: auto;
  display: block;
  border: 2px solid #000;
}

@media (max-width: 768px) {
  .profileHeader {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  .profileInfo h2 {
    font-size: 28px;
  }
  .stats {
    justify-content: center;
  }
  .infoGrid {
    grid-template-columns: 1fr;
  }
}
</style>
