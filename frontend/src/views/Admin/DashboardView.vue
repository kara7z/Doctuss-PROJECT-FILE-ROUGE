<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user } = useAuth()
const router = useRouter()

const verificationRequests = ref([])
const loading = ref(true)

onMounted(async () => {
  if (!user.value || user.value.role !== 'admin') {
    router.push('/403')
    return
  }
  await fetchVerificationRequests()
  loading.value = false
})

const fetchVerificationRequests = async () => {
  try {
    const res = await api('/verification-requests')
    if (res.ok) {
      verificationRequests.value = await res.json()
    }
  } catch (e) {
    console.error('Error fetching verification requests', e)
  }
}

const pendingRequests = computed(() => 
  verificationRequests.value.filter(r => r.status === 'pending')
)

const approvedRequests = computed(() => 
  verificationRequests.value.filter(r => r.status === 'approved')
)

const rejectedRequests = computed(() => 
  verificationRequests.value.filter(r => r.status === 'rejected')
)
</script>

<template>
  <div class="pageLayout">
    <section class="dashboardSection">
      <div class="container">
        <h1 class="pageTitle">{{ t('admin.dashboard') }} <span class="highlightText">{{ t('admin.dashboard') }}</span></h1>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('admin.loadingDashboard') }}</h2>
        </div>

        <div v-else class="dashboardContent">
          <!-- Stats Overview -->
          <div class="statsGrid">
            <div class="statCard pending">
              <div class="statIcon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <div class="statInfo">
                <h3>{{ pendingRequests.length }}</h3>
                <p>{{ t('admin.pendingVerifications') }}</p>
              </div>
            </div>

            <div class="statCard approved">
              <div class="statIcon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
              <div class="statInfo">
                <h3>{{ approvedRequests.length }}</h3>
                <p>{{ t('admin.approvedVerifications') }}</p>
              </div>
            </div>

            <div class="statCard rejected">
              <div class="statIcon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              </div>
              <div class="statInfo">
                <h3>{{ rejectedRequests.length }}</h3>
                <p>{{ t('admin.rejectedVerifications') }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="quickActions">
            <h2 class="sectionTitle">{{ t('admin.quickActions') }}</h2>
            <div class="actionsGrid">
              <router-link to="/admin/verification-requests" class="actionCard">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <h3>{{ t('admin.verificationRequests') }}</h3>
                <p>{{ t('admin.verificationRequestsDesc') }}</p>
                <span v-if="pendingRequests.length > 0" class="badge">{{ pendingRequests.length }}</span>
              </router-link>

              <router-link to="/admin/users" class="actionCard">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <h3>{{ t('admin.userManagement') }}</h3>
                <p>{{ t('admin.userManagementDesc') }}</p>
              </router-link>

              <router-link to="/admin/reviews" class="actionCard">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <h3>{{ t('admin.reviewsManagement') }}</h3>
                <p>{{ t('admin.reviewsManagementDesc') }}</p>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>
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

.statsGrid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.statCard {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 30px;
  display: flex;
  align-items: center;
  gap: 20px;
  transition: transform 0.2s;
}
.statCard:hover {
  transform: translate(-2px, -2px);
  box-shadow: 8px 8px 0px #000;
}

.statCard.pending { border-left: 12px solid #ff9800; }
.statCard.approved { border-left: 12px solid #4caf50; }
.statCard.rejected { border-left: 12px solid #ff5252; }

.statIcon {
  flex-shrink: 0;
}
.statCard.pending .statIcon { color: #ff9800; }
.statCard.approved .statIcon { color: #4caf50; }
.statCard.rejected .statIcon { color: #ff5252; }

.statInfo h3 {
  font-size: 48px;
  font-weight: 900;
  margin: 0 0 4px;
  color: #000;
}
.statInfo p {
  font-size: 16px;
  font-weight: 700;
  text-transform: uppercase;
  margin: 0;
  color: #666;
}

.quickActions {
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
  border-bottom: 4px solid #000;
  padding-bottom: 16px;
  color: #000;
}

.actionsGrid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.actionCard {
  position: relative;
  background: #fdfdfd;
  border: 3px solid #000;
  box-shadow: 4px 4px 0px #000;
  padding: 30px;
  text-decoration: none;
  color: #000;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 16px;
  transition: all 0.2s;
}
.actionCard:hover {
  background: #F6D506;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}

.actionCard svg {
  stroke: #000;
}

.actionCard h3 {
  font-size: 22px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
}

.actionCard p {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: #666;
}

.actionCard .badge {
  position: absolute;
  top: -12px;
  right: -12px;
  background: #ff5252;
  color: #fff;
  border: 3px solid #000;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 900;
  box-shadow: 4px 4px 0px #000;
}

@media (max-width: 768px) {
  .pageTitle { font-size: 36px; }
  .statsGrid { grid-template-columns: 1fr; }
  .actionsGrid { grid-template-columns: 1fr; }
}
</style>
