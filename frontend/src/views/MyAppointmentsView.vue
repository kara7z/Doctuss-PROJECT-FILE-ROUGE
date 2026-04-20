<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user, ready } = useAuth()
const router = useRouter()

const appointments = ref([])
const loading = ref(true)
const error = ref(null)

const reviewForm = ref({
    show: false,
    appointmentId: null,
    rating: 5,
    comment: '',
    submitting: false,
    error: null,
    success: false
})

const fetchAppointments = async () => {
    if (!user.value || user.value.role !== 'client') {
        router.push('/')
        return
    }

    loading.value = true
    error.value = null
    try {
        const res = await api('/appointments')
        if (res.ok) {
            const json = await res.json()
            appointments.value = json.data
        } else {
            error.value = 'Failed to load appointments.'
        }
    } catch (e) {
        error.value = 'Error fetching appointments.'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    if (ready.value) {
        fetchAppointments()
    }
})

const openReviewDialog = (app) => {
    reviewForm.value = {
        show: true,
        appointmentId: app.id,
        rating: 5,
        comment: '',
        submitting: false,
        error: null,
        success: false
    }
}

const closeReviewDialog = () => {
    reviewForm.value.show = false
}

const submitReview = async () => {
    reviewForm.value.submitting = true
    reviewForm.value.error = null
    try {
        const res = await api('/reviews', {
            method: 'POST',
            body: JSON.stringify({
                appointment_id: reviewForm.value.appointmentId,
                rating: reviewForm.value.rating,
                comment: reviewForm.value.comment
            })
        })

        if (res.ok) {
            reviewForm.value.success = true
            setTimeout(() => {
                closeReviewDialog()
                fetchAppointments()
            }, 1500)
        } else {
            const data = await res.json()
            reviewForm.value.error = data.message || 'Failed to submit review'
        }
    } catch (e) {
         reviewForm.value.error = 'An error occurred.'
    } finally {
        reviewForm.value.submitting = false
    }
}

const formatDate = (dateStr) => {
    const d = new Date(dateStr)
    return d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })
}

</script>

<template>
  <div class="pageLayout">
    <section class="appointmentsSection">
        <div class="container">
            <h1 class="pageTitle">{{ t('appointments.title') }} <span class="highlightText">{{ t('appointments.titleHighlight') }}</span></h1>

            <div v-if="loading" class="stateCard">
                <div class="spinner"></div>
                <h2>{{ t('appointments.loadingAppointments') }}</h2>
            </div>

            <div v-else-if="error" class="stateCard errorState">
                <h2>{{ error }}</h2>
            </div>
            
            <div v-else-if="appointments.length === 0" class="stateCard">
                <h2>{{ t('appointments.noAppointments') }}</h2>
                <p>{{ t('appointments.noAppointmentsDesc') }}</p>
                <button class="brutalistBtn" style="margin-top:20px;" @click="router.push('/search')">{{ t('appointments.findDoctor') }}</button>
            </div>

            <div v-else class="appointmentsList">
                <div class="appointmentCard" v-for="app in appointments" :key="app.id">
                    <div class="appHeader">
                        <span class="statusBadge" :class="'status-' + app.status">{{ app.status }}</span>
                        <h3>Dr. {{ app.doctor_profile?.user?.name || t('appointments.unknownDoctor') }}</h3>
                    </div>
                    <div class="appBody">
                        <p class="dateText">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            {{ formatDate(app.preferred_at) }}
                        </p>
                    </div>
                    <div class="appFooter">
                        <router-link :to="`/doctor/${app.doctor_profile?.user?.id}`" class="actionBtn outlineBtn">{{ t('appointments.viewDoctor') }}</router-link>
                        <button v-if="app.status === 'closed' && !app.review" class="actionBtn" @click="openReviewDialog(app)">{{ t('appointments.leaveReview') }}</button>
                        <span v-if="app.status === 'closed' && app.review" class="reviewedLabel">{{ t('appointments.reviewed') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Review Modal -->
    <div class="modalOverlay" v-if="reviewForm.show">
        <div class="modalContent">
            <button class="closeModal" @click="closeReviewDialog">&times;</button>
            <h2 class="modalTitle">{{ t('appointments.reviewModal.title') }}</h2>
            
            <div v-if="reviewForm.success" class="alert successAlert">
                {{ t('appointments.reviewModal.success') }}
            </div>
            <div v-else>
                <div v-if="reviewForm.error" class="alert errorAlert">{{ reviewForm.error }}</div>
                
                <div class="formGroup">
                    <label>{{ t('appointments.reviewModal.rating') }}</label>
                    <select v-model="reviewForm.rating" class="brutalistInput">
                        <option value="5">{{ t('appointments.reviewModal.ratingOptions.5') }}</option>
                        <option value="4">{{ t('appointments.reviewModal.ratingOptions.4') }}</option>
                        <option value="3">{{ t('appointments.reviewModal.ratingOptions.3') }}</option>
                        <option value="2">{{ t('appointments.reviewModal.ratingOptions.2') }}</option>
                        <option value="1">{{ t('appointments.reviewModal.ratingOptions.1') }}</option>
                    </select>
                </div>
                
                <div class="formGroup">
                    <label>{{ t('appointments.reviewModal.comment') }}</label>
                    <textarea v-model="reviewForm.comment" rows="4" class="brutalistInput" :placeholder="t('appointments.reviewModal.commentPlaceholder')"></textarea>
                </div>

                <button class="submitReviewBtn" :disabled="reviewForm.submitting" @click="submitReview">
                    {{ reviewForm.submitting ? t('appointments.reviewModal.submitting') : t('appointments.reviewModal.submitReview') }}
                </button>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.appointmentsSection {
  padding: 120px 0 80px;
  background-color: #f4f4f4;
  background-image: radial-gradient(rgba(0, 0, 0, 0.15) 2px, transparent 0);
  background-size: 24px 24px;
  min-height: calc(100vh - 80px);
  color: #000;
}

.container {
  max-width: 900px;
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
    color: #000;
}
.stateCard p {
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

.appointmentsList {
    display: grid;
    gap: 24px;
}

.appointmentCard {
    background: #fff;
    border: 4px solid #000;
    box-shadow: 8px 8px 0px #000;
    padding: 24px;
    transition: transform 0.2s;
}
.appointmentCard:hover {
    transform: translate(-2px, -2px);
    box-shadow: 10px 10px 0px #000;
}

.appHeader {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    border-bottom: 2px solid #eee;
    padding-bottom: 16px;
}
.appHeader h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
    text-transform: uppercase;
    color: #000;
}

.statusBadge {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    border: 3px solid #000;
    box-shadow: 2px 2px 0px #000;
}
.status-approved { background: #4caf50; color: #fff; }
.status-pending { background: #ff9800; color: #000; }
.status-closed { background: #2196f3; color: #fff; }
.status-cancelled { background: #f44336; color: #fff; }

.reviewedLabel {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: #4caf50;
    color: #fff;
    border: 3px solid #000;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 14px;
    box-shadow: 2px 2px 0px #000;
}

.appBody {
    margin-bottom: 24px;
}
.dateText {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 800;
    margin: 0;
    color: #000;
}

.appFooter {
    display: flex;
    gap: 16px;
}

.actionBtn {
    padding: 10px 20px;
    background: #000;
    color: #F6D506;
    border: 3px solid #000;
    font-weight: 900;
    text-transform: uppercase;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 4px 4px 0px #000;
    transition: all 0.2s;
}
.actionBtn:hover {
    background: #fff;
    color: #000;
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px #000;
}
.outlineBtn {
    background: #fff;
    color: #000;
    box-shadow: 2px 2px 0px #000;
}
.brutalistBtn {
  padding: 14px 32px;
  background: #F6D506;
  border: 3px solid #000;
  color: #000;
  font-weight: 900;
  font-size: 16px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 6px 6px 0px #000;
  transition: all 0.2s;
}
.brutalistBtn:hover {
  transform: translate(-2px, -2px);
  box-shadow: 8px 8px 0px #000;
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
    margin: 0 0 24px;
    border-bottom: 4px solid #000;
    padding-bottom: 12px;
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

.submitReviewBtn {
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
.submitReviewBtn:hover {
    transform: translate(-2px, -2px);
    box-shadow: 8px 8px 0px #000;
}
.submitReviewBtn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: 6px 6px 0px #000;
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

</style>
