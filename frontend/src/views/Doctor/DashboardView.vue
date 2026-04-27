<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user } = useAuth()
const router = useRouter()

const appointments = ref([])
const loading = ref(true)
const error = ref(null)
const profile = ref(null)

const currentPage = ref({
  pending: 1,
  approved: 1,
  closed: 1
})
const itemsPerPage = 5

const selectedAppointment = ref(null)
const showModal = ref(false)
const proposedDate = ref('')
const proposedTime = ref('')
const actionLoading = ref(false)
const actionError = ref('')

const fetchAppointments = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api('/appointments')
    if (res.ok) {
      const json = await res.json()
      appointments.value = json.data
    } else {
      error.value = t('common.error')
    }
  } catch (e) {
    error.value = t('common.error')
  } finally {
    loading.value = false
  }
}

const fetchProfile = async () => {
  try {
    const res = await api('/doctor/profile')
    if (res.ok) {
      const { data } = await res.json()
      profile.value = data
    }
  } catch (e) {
    console.error('Error loading profile', e)
  }
}

onMounted(() => {
  if (!user.value || user.value.role !== 'doctor') {
    router.push('/')
    return
  }
  fetchAppointments()
  fetchProfile()
})

const pendingAppointments = computed(() => {
  return appointments.value.filter(app => app.status === 'pending')
})

const approvedAppointments = computed(() => {
  return appointments.value.filter(app => app.status === 'approved')
})

const closedAppointments = computed(() => {
  return appointments.value.filter(app => app.status === 'closed')
})

const paginatedPending = computed(() => {
  const start = (currentPage.value.pending - 1) * itemsPerPage
  const end = start + itemsPerPage
  return pendingAppointments.value.slice(start, end)
})

const paginatedApproved = computed(() => {
  const start = (currentPage.value.approved - 1) * itemsPerPage
  const end = start + itemsPerPage
  return approvedAppointments.value.slice(start, end)
})

const paginatedClosed = computed(() => {
  const start = (currentPage.value.closed - 1) * itemsPerPage
  const end = start + itemsPerPage
  return closedAppointments.value.slice(start, end)
})

const totalPages = computed(() => ({
  pending: Math.ceil(pendingAppointments.value.length / itemsPerPage),
  approved: Math.ceil(approvedAppointments.value.length / itemsPerPage),
  closed: Math.ceil(closedAppointments.value.length / itemsPerPage)
}))

const changePage = (section, page) => {
  currentPage.value[section] = page
}

const openModal = (appointment) => {
  selectedAppointment.value = appointment
  showModal.value = true
  proposedDate.value = ''
  proposedTime.value = ''
  actionError.value = ''
}

const closeModal = () => {
  showModal.value = false
  selectedAppointment.value = null
  proposedDate.value = ''
  proposedTime.value = ''
  actionError.value = ''
}

const approveAppointment = async (withProposal = false) => {
  if (!selectedAppointment.value) return
  
  actionLoading.value = true
  actionError.value = ''
  
  try {
    const payload = {
      status: 'approved'
    }
    
    if (withProposal && proposedDate.value && proposedTime.value) {
      payload.proposed_at = `${proposedDate.value} ${proposedTime.value}:00`
    }
    
    const res = await api(`/appointments/${selectedAppointment.value.id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload)
    })
    
    if (res.ok) {
      await fetchAppointments()
      closeModal()
    } else {
      const data = await res.json()
      if (res.status === 422) {
        const proposedAtError = Array.isArray(data.errors?.proposed_at) ? data.errors.proposed_at[0] : ''

        actionError.value = proposedAtError === 'appointment.must_be_one_hour_ahead'
          ? t('appointment.must_be_one_hour_ahead')
          : data.message || t('common.error')
      } else {
        actionError.value = data.message || t('common.error')
      }
    }
  } catch (e) {
    actionError.value = t('common.error')
  } finally {
    actionLoading.value = false
  }
}

const closeAppointment = async (appointmentId) => {
  if (!confirm(t('doctorDashboard.closeConfirm'))) return
  
  try {
    const res = await api(`/appointments/${appointmentId}`, {
      method: 'PATCH',
      body: JSON.stringify({ status: 'closed' })
    })
    
    if (res.ok) {
      await fetchAppointments()
    }
  } catch (e) {
    console.error('Failed to close appointment', e)
  }
}

const formatDate = (dateStr) => {
  const d = new Date(dateStr)
  const monthNames = [
    t('months.january'), t('months.february'), t('months.march'), t('months.april'),
    t('months.may'), t('months.june'), t('months.july'), t('months.august'),
    t('months.september'), t('months.october'), t('months.november'), t('months.december')
  ]
  
  const hours = d.getHours()
  const minutes = d.getMinutes().toString().padStart(2, '0')
  const ampm = hours >= 12 ? 'PM' : 'AM'
  const displayHours = hours % 12 || 12
  
  return `${monthNames[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}, ${displayHours}:${minutes} ${ampm}`
}

const getTodayString = () => {
  const d = new Date()
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().split('T')[0]
}
</script>

<template>
  <div class="pageLayout">
    <section class="dashboardSection">
      <div class="container">
        <h1 class="pageTitle">{{ t('doctorDashboard.title') }} <span class="highlightText">{{ t('doctorDashboard.titleHighlight') }}</span></h1>

        <!-- Verification Status Banner -->
        <div v-if="profile && !profile.is_verified" class="verificationStatusCard">
          <div class="cardHeader">
            <h2>{{ t('doctorDashboard.verificationStatus') }}</h2>
          </div>
          <div class="cardBody">
            <div v-if="profile.verification_requests && profile.verification_requests.length > 0" class="requestsList">
              <div v-for="req in profile.verification_requests" :key="req.id" class="requestItem">
                <div class="requestHeader">
                  <span class="requestStatus" :class="'status-' + req.status">{{ req.status }}</span>
                  <span class="requestDate">{{ new Date(req.created_at).toLocaleDateString() }}</span>
                </div>
                <p v-if="req.admin_notes" class="adminNotes"><strong>{{ t('doctorDashboard.adminNotes') }}</strong> {{ req.admin_notes }}</p>
              </div>
            </div>
            <div v-else class="noRequests">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <p>{{ t('doctorDashboard.noRequests') }}</p>
            </div>
          </div>
        </div>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('doctorDashboard.loadingAppointments') }}</h2>
        </div>

        <div v-else-if="error" class="stateCard errorState">
          <h2>{{ error }}</h2>
        </div>

        <div v-else class="dashboardContent">
          <!-- Pending Appointments -->
          <div class="appointmentSection">
            <h2 class="sectionTitle">
              <span class="badge pendingBadge">{{ pendingAppointments.length }}</span>
              {{ t('doctorDashboard.pendingRequests') }}
            </h2>
            
            <div v-if="pendingAppointments.length === 0" class="emptyState">
              <p>{{ t('doctorDashboard.noPending') }}</p>
            </div>
            
            <div v-else class="appointmentsList">
              <div class="appointmentCard pending" v-for="app in paginatedPending" :key="app.id">
                <div class="cardHeader">
                  <div class="patientInfo">
                    <h3>{{ app.client?.name || t('doctorDashboard.unknownPatient') }}</h3>
                    <p class="email">{{ app.client?.email }}</p>
                  </div>
                  <span class="statusBadge status-pending">{{ t('doctorDashboard.pending') }}</span>
                </div>
                
                <div class="cardBody">
                  <div v-if="app.client?.gender" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('search.gender') }}</strong> {{ app.client.gender === 'male' ? t('search.male') : t('search.female') }}</span>
                  </div>
                  <div v-if="app.client?.age !== null && app.client?.age !== undefined" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('common.age') }}</strong> {{ app.client.age }} {{ t('common.yearsOld') }}</span>
                  </div>
                  <div class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><strong>{{ t('doctorDashboard.requestedTime') }}</strong> {{ formatDate(app.preferred_at) }}</span>
                  </div>
                  <div v-if="app.proposed_at" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span><strong>{{ t('doctorDashboard.yourProposal') }}</strong> {{ formatDate(app.proposed_at) }}</span>
                  </div>
                </div>
                
                <div class="cardActions">
                  <button class="actionBtn approveBtn" @click="openModal(app)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ t('doctorDashboard.approvePropose') }}
                  </button>
                </div>
              </div>
            </div>
            
            <div v-if="totalPages.pending > 1" class="pagination">
              <button 
                class="pageBtn" 
                :disabled="currentPage.pending === 1" 
                @click="changePage('pending', currentPage.pending - 1)"
              >
                {{ t('doctorDashboard.previous') }}
              </button>
              <button 
                v-for="page in totalPages.pending" 
                :key="page" 
                class="pageBtn" 
                :class="{ active: currentPage.pending === page }"
                @click="changePage('pending', page)"
              >
                {{ page }}
              </button>
              <button 
                class="pageBtn" 
                :disabled="currentPage.pending === totalPages.pending" 
                @click="changePage('pending', currentPage.pending + 1)"
              >
                {{ t('doctorDashboard.next') }}
              </button>
            </div>
          </div>

          <!-- Approved Appointments -->
          <div class="appointmentSection">
            <h2 class="sectionTitle">
              <span class="badge approvedBadge">{{ approvedAppointments.length }}</span>
              {{ t('doctorDashboard.approvedAppointments') }}
            </h2>
            
            <div v-if="approvedAppointments.length === 0" class="emptyState">
              <p>{{ t('doctorDashboard.noApproved') }}</p>
            </div>
            
            <div v-else class="appointmentsList">
              <div class="appointmentCard approved" v-for="app in paginatedApproved" :key="app.id">
                <div class="cardHeader">
                  <div class="patientInfo">
                    <h3>{{ app.client?.name || t('doctorDashboard.unknownPatient') }}</h3>
                    <p class="email">{{ app.client?.email }}</p>
                  </div>
                  <span class="statusBadge status-approved">{{ t('doctorDashboard.approved') }}</span>
                </div>
                
                <div class="cardBody">
                  <div v-if="app.client?.gender" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('search.gender') }}</strong> {{ app.client.gender === 'male' ? t('search.male') : t('search.female') }}</span>
                  </div>
                  <div v-if="app.client?.age !== null && app.client?.age !== undefined" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('common.age') }}</strong> {{ app.client.age }} {{ t('common.yearsOld') }}</span>
                  </div>
                  <div class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><strong>{{ t('doctorDashboard.appointmentTime') }}</strong> {{ formatDate(app.proposed_at || app.preferred_at) }}</span>
                  </div>
                </div>
                
                <div class="cardActions">
                  <button class="actionBtn closeBtn" @click="closeAppointment(app.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ t('doctorDashboard.markClosed') }}
                  </button>
                </div>
              </div>
            </div>
            
            <div v-if="totalPages.approved > 1" class="pagination">
              <button 
                class="pageBtn" 
                :disabled="currentPage.approved === 1" 
                @click="changePage('approved', currentPage.approved - 1)"
              >
                {{ t('doctorDashboard.previous') }}
              </button>
              <button 
                v-for="page in totalPages.approved" 
                :key="page" 
                class="pageBtn" 
                :class="{ active: currentPage.approved === page }"
                @click="changePage('approved', page)"
              >
                {{ page }}
              </button>
              <button 
                class="pageBtn" 
                :disabled="currentPage.approved === totalPages.approved" 
                @click="changePage('approved', currentPage.approved + 1)"
              >
                {{ t('doctorDashboard.next') }}
              </button>
            </div>
          </div>

          <!-- Closed Appointments -->
          <div class="appointmentSection">
            <h2 class="sectionTitle">
              <span class="badge closedBadge">{{ closedAppointments.length }}</span>
              {{ t('doctorDashboard.closedAppointments') }}
            </h2>
            
            <div v-if="closedAppointments.length === 0" class="emptyState">
              <p>{{ t('doctorDashboard.noClosed') }}</p>
            </div>
            
            <div v-else class="appointmentsList">
              <div class="appointmentCard closed" v-for="app in paginatedClosed" :key="app.id">
                <div class="cardHeader">
                  <div class="patientInfo">
                    <h3>{{ app.client?.name || t('doctorDashboard.unknownPatient') }}</h3>
                    <p class="email">{{ app.client?.email }}</p>
                  </div>
                  <span class="statusBadge status-closed">{{ t('doctorDashboard.closed') }}</span>
                </div>
                
                <div class="cardBody">
                  <div v-if="app.client?.gender" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('search.gender') }}</strong> {{ app.client.gender === 'male' ? t('search.male') : t('search.female') }}</span>
                  </div>
                  <div v-if="app.client?.age !== null && app.client?.age !== undefined" class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('common.age') }}</strong> {{ app.client.age }} {{ t('common.yearsOld') }}</span>
                  </div>
                  <div class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><strong>{{ t('doctorDashboard.completed') }}</strong> {{ formatDate(app.proposed_at || app.preferred_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="totalPages.closed > 1" class="pagination">
              <button 
                class="pageBtn" 
                :disabled="currentPage.closed === 1" 
                @click="changePage('closed', currentPage.closed - 1)"
              >
                {{ t('doctorDashboard.previous') }}
              </button>
              <button 
                v-for="page in totalPages.closed" 
                :key="page" 
                class="pageBtn" 
                :class="{ active: currentPage.closed === page }"
                @click="changePage('closed', page)"
              >
                {{ page }}
              </button>
              <button 
                class="pageBtn" 
                :disabled="currentPage.closed === totalPages.closed" 
                @click="changePage('closed', currentPage.closed + 1)"
              >
                {{ t('doctorDashboard.next') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Approval Modal -->
    <div class="modalOverlay" v-if="showModal">
      <div class="modalContent">
        <button class="closeModal" @click="closeModal">&times;</button>
        <h2 class="modalTitle">{{ t('doctorDashboard.approveAppointment') }}</h2>
        
        <div class="modalBody">
          <div class="appointmentDetails">
            <p><strong>{{ t('doctorDashboard.patient') }}</strong> {{ selectedAppointment?.client?.name }}</p>
            <p><strong>{{ t('doctorDashboard.requestedTime') }}</strong> {{ formatDate(selectedAppointment?.preferred_at) }}</p>
          </div>

          <div class="modalOptions">
            <button class="optionBtn" @click="approveAppointment(false)" :disabled="actionLoading">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <div>
                <h3>{{ t('doctorDashboard.approveAsRequested') }}</h3>
                <p>{{ t('doctorDashboard.acceptPreferred') }}</p>
              </div>
            </button>

            <div class="divider">{{ t('doctorDashboard.or') }}</div>

            <div class="proposeSection">
              <h3>{{ t('doctorDashboard.proposeAlternative') }}</h3>
              <div class="formGroup">
                <label>{{ t('doctorDashboard.date') }}</label>
                <input type="date" class="brutalistInput" v-model="proposedDate" :min="getTodayString()" />
              </div>
              <div class="formGroup">
                <label>{{ t('doctorDashboard.time') }}</label>
                <input type="time" class="brutalistInput" v-model="proposedTime" />
              </div>
              <button class="optionBtn proposeBtn" @click="approveAppointment(true)" :disabled="!proposedDate || !proposedTime || actionLoading">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <div>
                  <h3>{{ t('doctorDashboard.approveWithProposed') }}</h3>
                  <p>{{ t('doctorDashboard.suggestDifferent') }}</p>
                </div>
              </button>
            </div>
          </div>

          <div v-if="actionError" class="alert errorAlert">{{ actionError }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboardSection {
  padding: 120px 0 80px;
  background-color: #f4f4f4;
  background-image: radial-gradient(rgba(0, 0, 0, 0.15) 2px, transparent 0);
  background-size: 24px 24px;
  min-height: calc(100vh - 80px);
  color: #000;
}

.container {
  max-width: 1200px;
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
  border: 4px solid #000;
  box-shadow: 6px 6px 0px #000;
  display: inline-block;
  transform: rotate(-2deg);
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
  color: #000;
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

.dashboardContent {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.appointmentSection {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 8px 8px 0px #000;
  padding: 30px;
}

.sectionTitle {
  font-size: 28px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 4px solid #000;
  padding-bottom: 16px;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 40px;
  padding: 0 12px;
  border: 3px solid #000;
  font-size: 18px;
  font-weight: 900;
  box-shadow: 4px 4px 0px #000;
}
.pendingBadge { background: #ff9800; color: #000; }
.approvedBadge { background: #4caf50; color: #fff; }
.closedBadge { background: #9e9e9e; color: #fff; }

.emptyState {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  font-weight: 700;
  color: #666;
}

.appointmentsList {
  display: grid;
  gap: 20px;
}

.appointmentCard {
  border: 3px solid #000;
  padding: 20px;
  background: #fdfdfd;
  box-shadow: 4px 4px 0px #000;
  transition: transform 0.2s;
}
.appointmentCard:hover {
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}

.cardHeader {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 2px solid #eee;
}

.patientInfo h3 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 900;
  text-transform: uppercase;
}
.patientInfo .email {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #666;
}

.statusBadge {
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  border: 3px solid #000;
  box-shadow: 2px 2px 0px #000;
}
.status-pending { background: #ff9800; color: #000; }
.status-approved { background: #4caf50; color: #fff; }
.status-closed { background: #9e9e9e; color: #fff; }

.cardBody {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.infoRow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
  font-weight: 700;
}

.cardActions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.actionBtn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.2s;
  font-size: 14px;
}
.actionBtn:hover:not(:disabled) {
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.actionBtn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.approveBtn {
  background: #4caf50;
  color: #fff;
}
.closeBtn {
  background: #2196f3;
  color: #fff;
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
  max-width: 600px;
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
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
  margin: 0 0 24px;
  border-bottom: 4px solid #000;
  padding-bottom: 12px;
  color: #000;
}

.modalBody {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.appointmentDetails {
  background: #f8f8f8;
  border: 3px solid #000;
  padding: 16px;
}
.appointmentDetails p {
  margin: 8px 0;
  font-weight: 700;
  font-size: 16px;
  color: #000;
}

.modalOptions {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.optionBtn {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  border: 3px solid #000;
  background: #fff;
  cursor: pointer;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.2s;
  text-align: left;
  width: 100%;
}
.optionBtn:hover:not(:disabled) {
  background: #4caf50;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.optionBtn:hover:not(:disabled) h3,
.optionBtn:hover:not(:disabled) p {
  color: #fff;
}
.optionBtn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.optionBtn h3 {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  color: inherit;
}
.optionBtn p {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: inherit;
}

.divider {
  text-align: center;
  font-weight: 900;
  font-size: 18px;
  color: #666;
  position: relative;
}
.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 40%;
  height: 2px;
  background: #000;
}
.divider::before { left: 0; }
.divider::after { right: 0; }

.proposeSection {
  border: 3px dashed #000;
  padding: 20px;
  background: #fffef0;
}
.proposeSection h3 {
  margin: 0 0 16px;
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
}

.formGroup {
  margin-bottom: 16px;
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

.proposeBtn {
  margin-top: 16px;
  background: #F6D506;
}
.proposeBtn h3,
.proposeBtn p {
  color: #000;
}
.proposeBtn:hover:not(:disabled) {
  background: #FFE55C;
}
.proposeBtn:hover:not(:disabled) h3,
.proposeBtn:hover:not(:disabled) p {
  color: #000;
}

.alert {
  padding: 12px;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  text-align: center;
}
.errorAlert {
  background: #ff5252;
  color: #fff;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 3px solid #000;
}

.pageBtn {
  padding: 10px 16px;
  background: #fff;
  border: 3px solid #000;
  font-weight: 900;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 3px 3px 0px #000;
  transition: all 0.2s;
  text-transform: uppercase;
}

.pageBtn:hover:not(:disabled) {
  background: #F6D506;
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px 0px #000;
}

.pageBtn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.pageBtn.active {
  background: #000;
  color: #F6D506;
}

.verificationStatusCard {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 8px 8px 0px #000;
  padding: 30px;
  margin-bottom: 40px;
}
.verificationStatusCard .cardHeader {
  border-bottom: 4px solid #000;
  padding-bottom: 16px;
  margin-bottom: 24px;
}
.verificationStatusCard .cardHeader h2 {
  font-size: 28px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
  color: #000;
}
.verificationStatusCard .cardBody {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.verificationStatusCard .requestsList {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.verificationStatusCard .requestItem {
  padding: 16px;
  border: 3px solid #000;
  background: #fdfdfd;
  box-shadow: 4px 4px 0px #000;
}
.verificationStatusCard .requestHeader {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  padding-bottom: 0;
  border-bottom: none;
}
.verificationStatusCard .requestStatus {
  padding: 6px 12px;
  border: 2px solid #000;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
}
.verificationStatusCard .status-pending {
  background: #ff9800;
  color: #000;
}
.verificationStatusCard .status-approved {
  background: #4caf50;
  color: #fff;
}
.verificationStatusCard .status-rejected {
  background: #ff5252;
  color: #fff;
}
.verificationStatusCard .requestDate {
  font-size: 14px;
  font-weight: 700;
  color: #666;
}
.verificationStatusCard .adminNotes {
  font-size: 15px;
  font-weight: 600;
  color: #444;
  margin: 0;
}
.verificationStatusCard .noRequests {
  text-align: center;
  padding: 40px 20px;
  border: 3px dashed #000;
  background: #f8f8f8;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.verificationStatusCard .noRequests svg {
  stroke: #666;
}
.verificationStatusCard .noRequests p {
  font-size: 16px;
  font-weight: 700;
  color: #666;
  margin: 0;
  max-width: 500px;
}

@media (max-width: 768px) {
  .pageTitle { font-size: 36px; }
  .sectionTitle { font-size: 22px; }
  .cardHeader { flex-direction: column; gap: 12px; }
  .modalContent { padding: 30px 20px; }
}
</style>
