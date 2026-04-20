<script setup>
import { ref, onMounted, computed } from 'vue'
import { api, API_BASE_URL } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user } = useAuth()
const router = useRouter()

const requests = ref([])
const loading = ref(true)
const filter = ref('all')

const selectedRequest = ref(null)
const showModal = ref(false)
const modalAction = ref('')
const adminNotes = ref('')
const actionLoading = ref(false)
const actionError = ref('')

onMounted(async () => {
  if (!user.value || user.value.role !== 'admin') {
    router.push('/403')
    return
  }
  await fetchRequests()
  loading.value = false
})

const fetchRequests = async () => {
  try {
    const res = await api('/verification-requests')
    if (res.ok) {
      requests.value = await res.json()
    }
  } catch (e) {
    console.error('Error fetching requests', e)
  }
}

const filteredRequests = computed(() => {
  if (filter.value === 'all') return requests.value
  return requests.value.filter(r => r.status === filter.value)
})

const pendingCount = computed(() => requests.value.filter(r => r.status === 'pending').length)
const approvedCount = computed(() => requests.value.filter(r => r.status === 'approved').length)
const rejectedCount = computed(() => requests.value.filter(r => r.status === 'rejected').length)

const openModal = (request, action) => {
  selectedRequest.value = request
  modalAction.value = action
  adminNotes.value = ''
  actionError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedRequest.value = null
  modalAction.value = ''
  adminNotes.value = ''
  actionError.value = ''
}

const handleAction = async () => {
  if (!selectedRequest.value) return
  
  actionLoading.value = true
  actionError.value = ''
  
  try {
    const endpoint = `/verification-requests/${selectedRequest.value.id}/${modalAction.value}`
    const res = await api(endpoint, {
      method: 'PATCH',
      body: JSON.stringify({ admin_notes: adminNotes.value || null })
    })
    
    if (res.ok) {
      await fetchRequests()
      closeModal()
    } else {
      const data = await res.json()
      actionError.value = data.message || `Failed to ${modalAction.value} request`
    }
  } catch (e) {
    actionError.value = 'An error occurred'
  } finally {
    actionLoading.value = false
  }
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })
}

const getDocumentUrl = (path) => {
  return `${API_BASE_URL.replace('/api', '')}/storage/${path}`
}
</script>

<template>
  <div class="pageLayout">
    <section class="pageSection">
      <div class="container">
        <div class="pageHeader">
          <h1 class="pageTitle">{{ t('admin.verificationRequests') }} <span class="highlightText">{{ t('admin.verificationRequests') }}</span></h1>
          <router-link to="/admin/dashboard" class="backBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            {{ t('admin.backToDashboard') }}
          </router-link>
        </div>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('admin.loadingRequests') }}</h2>
        </div>

        <div v-else>
          <!-- Filter Tabs -->
          <div class="filterTabs">
            <button 
              class="filterTab" 
              :class="{ active: filter === 'all' }"
              @click="filter = 'all'"
            >
              {{ t('admin.all') }} ({{ requests.length }})
            </button>
            <button 
              class="filterTab pending" 
              :class="{ active: filter === 'pending' }"
              @click="filter = 'pending'"
            >
              {{ t('admin.pending') }} ({{ pendingCount }})
            </button>
            <button 
              class="filterTab approved" 
              :class="{ active: filter === 'approved' }"
              @click="filter = 'approved'"
            >
              {{ t('admin.approved') }} ({{ approvedCount }})
            </button>
            <button 
              class="filterTab rejected" 
              :class="{ active: filter === 'rejected' }"
              @click="filter = 'rejected'"
            >
              {{ t('admin.rejected') }} ({{ rejectedCount }})
            </button>
          </div>

          <!-- Requests List -->
          <div v-if="filteredRequests.length === 0" class="emptyState">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <h2>{{ t('admin.noRequestsFound') }}</h2>
            <p>{{ t('admin.noRequestsDesc') }}</p>
          </div>

          <div v-else class="requestsList">
            <div v-for="request in filteredRequests" :key="request.id" class="requestCard">
              <div class="cardHeader">
                <div class="doctorInfo">
                  <h3>{{ request.doctor_profile?.user?.name || t('admin.unknownDoctor') }}</h3>
                  <p class="email">{{ request.doctor_profile?.user?.email }}</p>
                  <p class="specialty">
                    {{ request.doctor_profile?.specialty?.category?.name }} - 
                    {{ request.doctor_profile?.specialty?.name }}
                  </p>
                </div>
                <span class="statusBadge" :class="'status-' + request.status">{{ t(`admin.${request.status}`) }}</span>
              </div>

              <div class="cardBody">
                <div class="infoRow">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  <span><strong>{{ t('admin.submitted') }}:</strong> {{ formatDate(request.proposed_at) }}</span>
                </div>

                <div v-if="request.reviewed_at" class="infoRow">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                  <span><strong>{{ t('admin.reviewedBy') }}:</strong> {{ request.reviewer?.name }} {{ t('admin.on') }} {{ formatDate(request.reviewed_at) }}</span>
                </div>

                <div v-if="request.admin_notes" class="adminNotes">
                  <strong>{{ t('admin.adminNotes') }}</strong> {{ request.admin_notes }}
                </div>

                <div class="documentSection">
                  <strong>{{ t('admin.verificationDocument') }}</strong>
                  <a :href="getDocumentUrl(request.document_path)" target="_blank" class="documentLink">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    {{ t('admin.viewDocument') }}
                  </a>
                </div>
              </div>

              <div v-if="request.status === 'pending'" class="cardActions">
                <button class="actionBtn approveBtn" @click="openModal(request, 'approve')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  {{ t('admin.approve') }}
                </button>
                <button class="actionBtn rejectBtn" @click="openModal(request, 'reject')">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  {{ t('admin.reject') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Action Modal -->
    <div class="modalOverlay" v-if="showModal">
      <div class="modalContent">
        <button class="closeModal" @click="closeModal">&times;</button>
        <h2 class="modalTitle">{{ modalAction === 'approve' ? t('admin.approveRequest') : t('admin.rejectRequest') }}</h2>
        
        <div class="modalBody">
          <div class="requestDetails">
            <p><strong>{{ t('admin.doctor') }}:</strong> {{ selectedRequest?.doctor_profile?.user?.name }}</p>
            <p><strong>{{ t('admin.email') }}:</strong> {{ selectedRequest?.doctor_profile?.user?.email }}</p>
            <p><strong>{{ t('admin.specialty') }}:</strong> {{ selectedRequest?.doctor_profile?.specialty?.name }}</p>
          </div>

          <div class="formGroup">
            <label>{{ t('admin.adminNotesOptional') }}</label>
            <textarea 
              v-model="adminNotes" 
              class="brutalistTextarea" 
              rows="4"
              :placeholder="t('admin.adminNotesPlaceholder')"
            ></textarea>
          </div>

          <div v-if="actionError" class="alert errorAlert">{{ actionError }}</div>

          <div class="modalActions">
            <button class="actionBtn cancelBtn" @click="closeModal" :disabled="actionLoading">
              {{ t('admin.cancel') }}
            </button>
            <button 
              class="actionBtn" 
              :class="modalAction === 'approve' ? 'approveBtn' : 'rejectBtn'"
              @click="handleAction"
              :disabled="actionLoading"
            >
              <span v-if="actionLoading">{{ t('admin.processing') }}</span>
              <span v-else>{{ modalAction === 'approve' ? t('admin.approve') : t('admin.reject') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pageSection {
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

.pageHeader {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  flex-wrap: wrap;
  gap: 20px;
}

.pageTitle {
  font-size: 48px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
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

.backBtn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: #fff;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  text-decoration: none;
  color: #000;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.2s;
  font-size: 14px;
}
.backBtn:hover {
  background: #000;
  color: #F6D506;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
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

.filterTabs {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filterTab {
  padding: 12px 24px;
  background: #fff;
  border: 3px solid #000;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.2s;
  font-size: 14px;
}
.filterTab:hover {
  transform: translate(-1px, -1px);
  box-shadow: 5px 5px 0px #000;
}
.filterTab.active {
  background: #000;
  color: #F6D506;
}
.filterTab.pending.active { background: #ff9800; color: #000; }
.filterTab.approved.active { background: #4caf50; color: #fff; }
.filterTab.rejected.active { background: #ff5252; color: #fff; }

.emptyState {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 8px 8px 0px #000;
  padding: 60px 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.emptyState svg {
  stroke: #666;
}
.emptyState h2 {
  font-size: 28px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
  color: #000;
}
.emptyState p {
  font-size: 16px;
  font-weight: 600;
  color: #666;
  margin: 0;
}

.requestsList {
  display: grid;
  gap: 24px;
}

.requestCard {
  background: #fff;
  border: 3px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 24px;
  transition: transform 0.2s;
}
.requestCard:hover {
  transform: translate(-2px, -2px);
  box-shadow: 8px 8px 0px #000;
}

.cardHeader {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 3px solid #eee;
}

.doctorInfo h3 {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 900;
  text-transform: uppercase;
}
.doctorInfo .email {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 700;
  color: #666;
}
.doctorInfo .specialty {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #2196f3;
}

.statusBadge {
  padding: 8px 16px;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  border: 3px solid #000;
  box-shadow: 3px 3px 0px #000;
}
.status-pending { background: #ff9800; color: #000; }
.status-approved { background: #4caf50; color: #fff; }
.status-rejected { background: #ff5252; color: #fff; }

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
  font-size: 15px;
  font-weight: 600;
}

.adminNotes {
  padding: 12px;
  background: #fffef0;
  border: 2px dashed #000;
  font-size: 15px;
  font-weight: 600;
  color: #444;
}

.documentSection {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-weight: 700;
}

.documentLink {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #2196f3;
  color: #fff;
  border: 3px solid #000;
  text-decoration: none;
  font-weight: 900;
  text-transform: uppercase;
  box-shadow: 3px 3px 0px #000;
  transition: all 0.2s;
  font-size: 14px;
  width: fit-content;
}
.documentLink:hover {
  background: #1976d2;
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px 0px #000;
}

.cardActions {
  display: flex;
  gap: 12px;
  padding-top: 20px;
  border-top: 3px solid #eee;
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
  font-family: inherit;
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
.approveBtn span {
  color: #fff;
}
.rejectBtn {
  background: #ff5252;
  color: #fff;
}
.rejectBtn span {
  color: #fff;
}
.cancelBtn {
  background: #9e9e9e;
  color: #fff;
}
.cancelBtn span {
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
  z-index: 9999;
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
  z-index: 10000;
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

.requestDetails {
  background: #f8f8f8;
  border: 3px solid #000;
  padding: 16px;
}
.requestDetails p {
  margin: 8px 0;
  font-weight: 700;
  font-size: 16px;
  color: #000;
}
.requestDetails strong {
  color: #000;
}

.formGroup {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.formGroup label {
  font-weight: 900;
  text-transform: uppercase;
  font-size: 14px;
  color: #000;
}
.brutalistTextarea {
  width: 100%;
  padding: 12px;
  border: 3px solid #000;
  font-weight: 600;
  font-size: 16px;
  font-family: inherit;
  box-shadow: 4px 4px 0px #000;
  outline: none;
  background: #fff;
  color: #000;
  resize: vertical;
}
.brutalistTextarea::placeholder {
  color: #666;
  opacity: 1;
}
.brutalistTextarea:focus {
  transform: translate(2px, 2px);
  box-shadow: 2px 2px 0px #000;
}

.modalActions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}
.modalActions button {
  font-family: inherit;
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

@media (max-width: 768px) {
  .pageTitle { font-size: 36px; }
  .pageHeader { flex-direction: column; align-items: flex-start; }
  .cardHeader { flex-direction: column; gap: 12px; }
  .modalContent { padding: 30px 20px; }
}
</style>
