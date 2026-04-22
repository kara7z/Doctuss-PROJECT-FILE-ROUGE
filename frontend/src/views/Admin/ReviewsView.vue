<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const { user } = useAuth()
const router = useRouter()
const isRTL = computed(() => locale.value === 'ar')

const doctors = ref([])
const loading = ref(true)
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

const selectedReview = ref(null)
const showModal = ref(false)
const actionLoading = ref(false)
const actionError = ref('')

onMounted(async () => {
  if (!user.value || user.value.role !== 'admin') {
    router.push('/403')
    return
  }
  await fetchDoctors()
  loading.value = false
})

const fetchDoctors = async () => {
  try {
    const res = await api('/doctors?per_page=100')
    if (res.ok) {
      const json = await res.json()
      doctors.value = json.data.map(user => ({
        id: user.id,
        user: {
          id: user.id,
          name: user.name,
          email: user.email
        },
        specialty: user.doctor_profile?.specialty,
        reviews: user.doctor_profile?.reviews || []
      }))
    } else {
      console.error('Failed to fetch doctors:', res.status)
    }
  } catch (e) {
    console.error('Error fetching doctors:', e)
  }
}

const allReviews = computed(() => {
  const reviews = []
  doctors.value.forEach(doctor => {
    if (doctor.reviews && doctor.reviews.length > 0) {
      doctor.reviews.forEach(review => {
        reviews.push({
          ...review,
          doctor_id: doctor.id,
          doctor_name: doctor.user?.name,
          doctor_specialty: doctor.specialty?.name,
          user: review.user || { id: null, name: 'Anonymous' }
        })
      })
    }
  })
  return reviews.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const filteredReviews = computed(() => {
  if (!searchQuery.value) return allReviews.value
  
  const query = searchQuery.value.toLowerCase()
  return allReviews.value.filter(r => 
    r.doctor_name?.toLowerCase().includes(query) ||
    r.user?.name?.toLowerCase().includes(query) ||
    r.comment?.toLowerCase().includes(query)
  )
})

const paginatedReviews = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredReviews.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredReviews.value.length / itemsPerPage))

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Reset to page 1 when search changes
watch(searchQuery, () => {
  currentPage.value = 1
})

const openModal = (review) => {
  selectedReview.value = review
  actionError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedReview.value = null
  actionError.value = ''
}

const deleteReview = async () => {
  if (!selectedReview.value) return
  
  actionLoading.value = true
  actionError.value = ''
  
  try {
    const res = await api(`/admin/reviews/${selectedReview.value.id}`, {
      method: 'DELETE'
    })
    
    if (res.ok) {
      await fetchDoctors()
      closeModal()
    } else {
      const data = await res.json()
      actionError.value = data.message || 'Failed to delete review'
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

const renderStars = (rating) => {
  return '★'.repeat(rating) + '☆'.repeat(5 - rating)
}
</script>

<template>
  <div class="pageLayout">
    <section class="pageSection">
      <div class="container">
        <div class="pageHeader">
          <h1 class="pageTitle">{{ t('admin.reviewsManagement') }} <span class="highlightText">{{ t('admin.reviewsManagement') }}</span></h1>
          <router-link to="/admin/dashboard" class="backBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            {{ t('admin.backToDashboard') }}
          </router-link>
        </div>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('admin.loadingReviews') }}</h2>
        </div>

        <div v-else>
          <!-- Stats -->
          <div class="statsCard">
            <div class="statItem">
              <h3>{{ allReviews.length }}</h3>
              <p>{{ t('admin.totalReviews') }}</p>
            </div>
            <div class="statItem">
              <h3>{{ doctors.filter(d => d.reviews && d.reviews.length > 0).length }}</h3>
              <p>{{ t('admin.doctorsWithReviews') }}</p>
            </div>
          </div>

          <!-- Search Bar -->
          <div class="searchBar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
            <input 
              type="text" 
              v-model="searchQuery" 
              :placeholder="t('admin.searchReviewsPlaceholder')"
              class="searchInput"
            />
          </div>

          <!-- Reviews List -->
          <div v-if="filteredReviews.length === 0" class="emptyState">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            <h2>{{ t('admin.noReviewsFound') }}</h2>
            <p>{{ t('admin.noReviewsDesc') }}</p>
          </div>

          <div v-else>
            <div class="resultsInfo">
              <p>{{ t('admin.showing') }} {{ ((currentPage - 1) * itemsPerPage) + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredReviews.length) }} {{ t('admin.of') }} {{ filteredReviews.length }} {{ t('admin.reviews') }}</p>
            </div>

            <div class="reviewsList">
              <div v-for="review in paginatedReviews" :key="review.id" class="reviewCard">
                <div class="cardHeader">
                  <div class="reviewerInfo">
                    <h3>{{ review.user?.name || t('admin.anonymous') }}</h3>
                    <p class="email" v-if="review.user?.email">{{ review.user.email }}</p>
                  </div>
                  <div class="rating">
                    <span class="stars">{{ renderStars(review.rating) }}</span>
                    <span class="ratingNumber">{{ review.rating }}/5</span>
                  </div>
                </div>

                <div class="cardBody">
                  <div class="doctorInfo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span><strong>{{ t('admin.doctor') }}:</strong> {{ review.doctor_name }} ({{ review.doctor_specialty }})</span>
                    <router-link 
                      :to="`/doctor/${review.doctor_id}`" 
                      class="viewProfileLink"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      {{ t('admin.viewProfile') }}
                    </router-link>
                  </div>

                  <div class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><strong>{{ t('admin.posted') }}:</strong> {{ formatDate(review.created_at) }}</span>
                  </div>

                  <div v-if="review.comment" class="reviewComment">
                    <strong>{{ t('admin.comment') }}:</strong>
                    <p>{{ review.comment }}</p>
                  </div>
                </div>

                <div class="cardActions">
                  <button class="actionBtn deleteBtn" @click="openModal(review)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    {{ t('admin.deleteReview') }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination">
              <button 
                class="pageBtn" 
                :disabled="currentPage === 1" 
                @click="changePage(currentPage - 1)"
              >
                <svg v-if="!isRTL" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
              
              <template v-for="page in totalPages" :key="page">
                <button
                  v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                  class="pageBtn"
                  :class="{ active: currentPage === page }"
                  @click="changePage(page)"
                >{{ page }}</button>
                <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="pageDots">…</span>
              </template>
              
              <button 
                class="pageBtn" 
                :disabled="currentPage === totalPages" 
                @click="changePage(currentPage + 1)"
              >
                <svg v-if="!isRTL" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modalOverlay" v-if="showModal">
      <div class="modalContent">
        <button class="closeModal" @click="closeModal">&times;</button>
        <h2 class="modalTitle">{{ t('admin.deleteReviewTitle') }}</h2>
        
        <div class="modalBody">
          <div class="reviewDetails">
            <p><strong>{{ t('admin.reviewer') }}:</strong> {{ selectedReview?.user?.name }}</p>
            <p><strong>{{ t('admin.doctor') }}:</strong> {{ selectedReview?.doctor_name }}</p>
            <p><strong>{{ t('admin.rating') }}:</strong> {{ selectedReview?.rating }}/5</p>
            <div v-if="selectedReview?.comment" class="commentPreview">
              <strong>{{ t('admin.comment') }}:</strong>
              <p>{{ selectedReview.comment }}</p>
            </div>
          </div>

          <div class="warningBox">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <p>{{ t('admin.deleteWarning') }}</p>
          </div>

          <div v-if="actionError" class="alert errorAlert">{{ actionError }}</div>

          <div class="modalActions">
            <button class="actionBtn cancelBtn" @click="closeModal" :disabled="actionLoading">
              {{ t('admin.cancel') }}
            </button>
            <button 
              class="actionBtn deleteBtn"
              @click="deleteReview"
              :disabled="actionLoading"
            >
              <span v-if="actionLoading">{{ t('admin.deleting') }}</span>
              <span v-else>{{ t('admin.deleteReview') }}</span>
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

.statsCard {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 24px;
  display: flex;
  gap: 40px;
  margin-bottom: 24px;
}

.statItem {
  text-align: center;
}
.statItem h3 {
  font-size: 48px;
  font-weight: 900;
  margin: 0 0 8px;
  color: #F6D506;
  text-shadow: 3px 3px 0px #000;
}
.statItem p {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  margin: 0;
  color: #666;
}

.searchBar {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}
.searchBar svg {
  flex-shrink: 0;
}
.searchInput {
  flex: 1;
  border: none;
  outline: none;
  font-size: 16px;
  font-weight: 700;
  font-family: inherit;
  background: transparent;
}

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

.reviewsList {
  display: grid;
  gap: 24px;
}

.reviewCard {
  background: #fff;
  border: 3px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 24px;
  transition: transform 0.2s;
}
.reviewCard:hover {
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

.reviewerInfo h3 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 900;
  text-transform: uppercase;
}
.reviewerInfo .email {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #666;
}

.rating {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}
.stars {
  font-size: 24px;
  color: #F6D506;
  text-shadow: 1px 1px 0px #000;
}
.ratingNumber {
  font-size: 14px;
  font-weight: 900;
  color: #000;
}

.cardBody {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.doctorInfo {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
  font-weight: 600;
  padding: 12px;
  background: #f0f8ff;
  border: 2px solid #2196f3;
  flex-wrap: wrap;
}

.viewProfileLink {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #9c27b0;
  color: #fff;
  border: 2px solid #000;
  text-decoration: none;
  font-weight: 900;
  text-transform: uppercase;
  box-shadow: 2px 2px 0px #000;
  transition: all 0.2s;
  font-size: 12px;
  margin-left: auto;
}
.viewProfileLink:hover {
  background: #7b1fa2;
  transform: translate(-1px, -1px);
  box-shadow: 3px 3px 0px #000;
}

.infoRow {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
  font-weight: 600;
}

.reviewComment {
  padding: 16px;
  background: #fffef0;
  border: 2px dashed #000;
}
.reviewComment strong {
  display: block;
  margin-bottom: 8px;
  font-weight: 900;
  text-transform: uppercase;
  font-size: 14px;
}
.reviewComment p {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.6;
  color: #444;
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
}
.actionBtn:hover:not(:disabled) {
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.actionBtn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.deleteBtn {
  background: #ff5252;
  color: #fff;
}
.cancelBtn {
  background: #9e9e9e;
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

.reviewDetails {
  background: #f8f8f8;
  border: 3px solid #000;
  padding: 16px;
}
.reviewDetails p {
  margin: 8px 0;
  font-weight: 700;
  font-size: 16px;
  color: #000;
}
.reviewDetails strong {
  color: #000;
}
.commentPreview {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 2px solid #ddd;
}
.commentPreview strong {
  display: block;
  margin-bottom: 8px;
  color: #000;
}
.commentPreview p {
  font-weight: 600;
  color: #444;
  font-size: 15px;
}

.warningBox {
  background: #fff3cd;
  border: 3px solid #000;
  padding: 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.warningBox svg {
  flex-shrink: 0;
  stroke: #ff9800;
}
.warningBox p {
  margin: 0;
  font-weight: 700;
  font-size: 15px;
  color: #000;
}

.modalActions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
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

.resultsInfo {
  background: #fff;
  border: 3px solid #000;
  padding: 12px 20px;
  margin-bottom: 16px;
  box-shadow: 4px 4px 0px #000;
}
.resultsInfo p {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  color: #666;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

.pageBtn {
  min-width: 44px;
  height: 44px;
  padding: 0 12px;
  border: 3px solid #000;
  background: #fff;
  color: #000;
  font-size: 15px;
  font-weight: 900;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 4px 4px 0px #000;
  transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
  font-family: inherit;
}
.pageBtn:hover:not(:disabled) {
  background: #F6D506;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.pageBtn.active {
  background: #000;
  color: #F6D506;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}
.pageBtn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
  box-shadow: 2px 2px 0px #000;
}

.pageDots {
  font-size: 18px;
  font-weight: 900;
  color: #000;
  display: flex;
  align-items: center;
  padding: 0 4px;
}

@media (max-width: 768px) {
  .pageTitle { font-size: 36px; }
  .pageHeader { flex-direction: column; align-items: flex-start; }
  .cardHeader { flex-direction: column; gap: 12px; }
  .rating { align-items: flex-start; }
  .statsCard { flex-direction: column; gap: 20px; }
  .modalContent { padding: 30px 20px; }
}
</style>
