<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { user } = useAuth()
const router = useRouter()

const users = ref([])
const loading = ref(true)
const filter = ref('all')
const roleFilter = ref('all')
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

const selectedUser = ref(null)
const showModal = ref(false)
const modalAction = ref('')
const actionLoading = ref(false)
const actionError = ref('')

onMounted(async () => {
  if (!user.value || user.value.role !== 'admin') {
    router.push('/403')
    return
  }
  await fetchUsers()
  loading.value = false
})

const fetchUsers = async () => {
  try {
    const res = await api('/admin/users')
    if (res.ok) {
      const json = await res.json()
      users.value = json.data
    }
  } catch (e) {
    console.error('Error fetching users', e)
  }
}

const filteredUsers = computed(() => {
  let result = users.value

  // Role filter
  if (roleFilter.value === 'doctor') {
    result = result.filter(u => u.role === 'doctor')
  } else if (roleFilter.value === 'client') {
    result = result.filter(u => u.role === 'client')
  } else if (roleFilter.value === 'admin') {
    result = result.filter(u => u.role === 'admin')
  }

  // Status filter
  if (filter.value === 'verified') {
    result = result.filter(u => u.doctor_profile?.is_verified)
  } else if (filter.value === 'unverified') {
    result = result.filter(u => u.role === 'doctor' && !u.doctor_profile?.is_verified)
  } else if (filter.value === 'suspended') {
    result = result.filter(u => u.status === 'suspended')
  } else if (filter.value === 'active') {
    result = result.filter(u => u.status === 'active')
  }

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(u => 
      u.name?.toLowerCase().includes(query) ||
      u.email?.toLowerCase().includes(query) ||
      u.doctor_profile?.specialty?.name?.toLowerCase().includes(query) ||
      u.role?.toLowerCase().includes(query)
    )
  }

  return result
})

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredUsers.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage))

const changePage = (page) => {
  currentPage.value = page
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Reset to page 1 when filters change
watch([filter, roleFilter, searchQuery], () => {
  currentPage.value = 1
})

const totalCount = computed(() => users.value.length)
const doctorCount = computed(() => users.value.filter(u => u.role === 'doctor').length)
const clientCount = computed(() => users.value.filter(u => u.role === 'client').length)
const adminCount = computed(() => users.value.filter(u => u.role === 'admin').length)
const verifiedCount = computed(() => users.value.filter(u => u.doctor_profile?.is_verified).length)
const unverifiedCount = computed(() => users.value.filter(u => u.role === 'doctor' && !u.doctor_profile?.is_verified).length)
const suspendedCount = computed(() => users.value.filter(u => u.status === 'suspended').length)
const activeCount = computed(() => users.value.filter(u => u.status === 'active').length)

const openModal = (userObj, action) => {
  selectedUser.value = userObj
  modalAction.value = action
  actionError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedUser.value = null
  modalAction.value = ''
  actionError.value = ''
}

const handleAction = async () => {
  if (!selectedUser.value) return
  
  actionLoading.value = true
  actionError.value = ''
  
  try {
    const endpoint = `/admin/users/${selectedUser.value.id}/${modalAction.value}`
    const res = await api(endpoint, {
      method: 'PATCH'
    })
    
    if (res.ok) {
      await fetchUsers()
      closeModal()
    } else {
      const data = await res.json()
      actionError.value = data.message || `Failed to ${modalAction.value} user`
    }
  } catch (e) {
    actionError.value = 'An error occurred'
  } finally {
    actionLoading.value = false
  }
}

const toggleVerification = async (userObj) => {
  if (!userObj.doctor_profile) return
  
  if (!confirm(`Are you sure you want to ${userObj.doctor_profile.is_verified ? 'unverify' : 'verify'} this doctor?`)) return
  
  try {
    const action = userObj.doctor_profile.is_verified ? 'unverify' : 'verify'
    const res = await api(`/admin/doctor-profiles/${userObj.doctor_profile.id}/${action}`, {
      method: 'PATCH'
    })
    
    if (res.ok) {
      await fetchUsers()
    }
  } catch (e) {
    console.error('Failed to toggle verification', e)
  }
}
</script>

<template>
  <div class="pageLayout">
    <section class="pageSection">
      <div class="container">
        <div class="pageHeader">
          <h1 class="pageTitle">{{ t('admin.userManagement') }} <span class="highlightText">{{ t('admin.userManagement') }}</span></h1>
          <router-link to="/admin/dashboard" class="backBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            {{ t('admin.backToDashboard') }}
          </router-link>
        </div>

        <div v-if="loading" class="stateCard">
          <div class="spinner"></div>
          <h2>{{ t('admin.loadingUsers') }}</h2>
        </div>

        <div v-else>
          <!-- Search Bar -->
          <div class="searchBar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
            <input 
              type="text" 
              v-model="searchQuery" 
              :placeholder="t('admin.searchPlaceholder')"
              class="searchInput"
            />
          </div>

          <!-- Role Filter Tabs -->
          <div class="filterTabs">
            <button 
              class="filterTab" 
              :class="{ active: roleFilter === 'all' }"
              @click="roleFilter = 'all'"
            >
              {{ t('admin.allUsers') }} ({{ totalCount }})
            </button>
            <button 
              class="filterTab doctor-tab" 
              :class="{ active: roleFilter === 'doctor' }"
              @click="roleFilter = 'doctor'"
            >
              {{ t('admin.doctors') }} ({{ doctorCount }})
            </button>
            <button 
              class="filterTab client-tab" 
              :class="{ active: roleFilter === 'client' }"
              @click="roleFilter = 'client'"
            >
              {{ t('admin.clients') }} ({{ clientCount }})
            </button>
            <button 
              class="filterTab admin-tab" 
              :class="{ active: roleFilter === 'admin' }"
              @click="roleFilter = 'admin'"
            >
              {{ t('admin.admins') }} ({{ adminCount }})
            </button>
          </div>

          <!-- Status Filter Tabs -->
          <div class="filterTabs">
            <button 
              class="filterTab" 
              :class="{ active: filter === 'all' }"
              @click="filter = 'all'"
            >
              {{ t('admin.allStatus') }} ({{ totalCount }})
            </button>
            <button 
              class="filterTab active-tab" 
              :class="{ active: filter === 'active' }"
              @click="filter = 'active'"
            >
              {{ t('admin.active') }} ({{ activeCount }})
            </button>
            <button 
              class="filterTab suspended-tab" 
              :class="{ active: filter === 'suspended' }"
              @click="filter = 'suspended'"
            >
              {{ t('admin.suspended') }} ({{ suspendedCount }})
            </button>
            <button 
              class="filterTab verified-tab" 
              :class="{ active: filter === 'verified' }"
              @click="filter = 'verified'"
            >
              {{ t('admin.verified') }} ({{ verifiedCount }})
            </button>
            <button 
              class="filterTab unverified-tab" 
              :class="{ active: filter === 'unverified' }"
              @click="filter = 'unverified'"
            >
              {{ t('admin.unverified') }} ({{ unverifiedCount }})
            </button>
          </div>

          <!-- Users List -->
          <div v-if="filteredUsers.length === 0" class="emptyState">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <h2>{{ t('admin.noUsersFound') }}</h2>
            <p>{{ t('admin.noUsersDesc') }}</p>
          </div>

          <div v-else>
            <div class="resultsInfo">
              <p>{{ t('admin.showing') }} {{ ((currentPage - 1) * itemsPerPage) + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }} {{ t('admin.of') }} {{ filteredUsers.length }} {{ t('admin.users') }}</p>
            </div>

            <div class="usersList">
              <div v-for="userObj in paginatedUsers" :key="userObj.id" class="userCard">
                <div class="cardHeader">
                  <div class="userInfo">
                    <h3>{{ userObj.name || 'Unknown' }}</h3>
                    <p class="email">{{ userObj.email }}</p>
                    <p class="specialty" v-if="userObj.doctor_profile?.specialty">
                      {{ userObj.doctor_profile.specialty.category?.name }} - {{ userObj.doctor_profile.specialty.name }}
                    </p>
                    <p class="roleTag" :class="'role-' + userObj.role">
                      {{ t('admin.' + (userObj.role === 'admin' ? 'adminRole' : userObj.role)) }}
                    </p>
                  </div>
                  <div class="badges">
                    <span class="statusBadge" :class="'status-' + userObj.status">
                      {{ t(`admin.${userObj.status}`) }}
                    </span>
                    <span v-if="userObj.doctor_profile?.is_verified" class="verifiedBadge">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      {{ t('admin.verified') }}
                    </span>
                  </div>
                </div>

                <div class="cardBody">
                  <div class="infoRow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span><strong>{{ t('admin.role') }}:</strong> {{ t('admin.' + (userObj.role === 'admin' ? 'adminRole' : userObj.role)) }}</span>
                  </div>
                  <div class="infoRow" v-if="userObj.doctor_profile?.phone_number">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span><strong>{{ t('admin.phone') }}:</strong> {{ userObj.doctor_profile.phone_number }}</span>
                  </div>
                  <div class="infoRow" v-if="userObj.doctor_profile?.city">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><strong>{{ t('admin.city') }}:</strong> {{ userObj.doctor_profile.city }}</span>
                  </div>
                  <div class="infoRow" v-if="userObj.gender">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    <span><strong>{{ t('admin.gender') }}:</strong> {{ t('admin.' + userObj.gender) }}</span>
                  </div>
                </div>

                <div class="cardActions">
                  <router-link 
                    v-if="userObj.role === 'doctor'"
                    :to="`/doctor/${userObj.id}`" 
                    class="actionBtn viewBtn"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    {{ t('admin.viewProfile') }}
                  </router-link>
                  <button 
                    v-if="userObj.status === 'active' && userObj.role !== 'admin'" 
                    class="actionBtn suspendBtn" 
                    @click="openModal(userObj, 'suspend')"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    {{ t('admin.suspendUser') }}
                  </button>
                  <button 
                    v-else-if="userObj.status === 'suspended'" 
                    class="actionBtn activateBtn" 
                    @click="openModal(userObj, 'activate')"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    {{ t('admin.activateUser') }}
                  </button>
                  <button 
                    v-if="userObj.role === 'doctor' && userObj.doctor_profile"
                    class="actionBtn verifyBtn" 
                    @click="toggleVerification(userObj)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    {{ userObj.doctor_profile.is_verified ? t('admin.unverify') : t('admin.verify') }}
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
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                {{ t('admin.previous') }}
              </button>
              
              <button 
                v-for="page in totalPages" 
                :key="page" 
                class="pageBtn" 
                :class="{ active: currentPage === page }"
                @click="changePage(page)"
              >
                {{ page }}
              </button>
              
              <button 
                class="pageBtn" 
                :disabled="currentPage === totalPages" 
                @click="changePage(currentPage + 1)"
              >
                {{ t('admin.next') }}
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Action Modal -->
    <div class="modalOverlay" v-if="showModal">
      <div class="modalContent">
        <button class="closeModal" @click="closeModal">&times;</button>
        <h2 class="modalTitle">{{ modalAction === 'suspend' ? t('admin.suspendUserTitle') : t('admin.activateUserTitle') }}</h2>
        
        <div class="modalBody">
          <div class="userDetails">
            <p><strong>{{ t('admin.name') }}:</strong> {{ selectedUser?.name }}</p>
            <p><strong>{{ t('admin.email') }}:</strong> {{ selectedUser?.email }}</p>
            <p><strong>{{ t('admin.currentStatus') }}:</strong> {{ selectedUser?.status }}</p>
          </div>

          <div class="warningBox">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <p v-if="modalAction === 'suspend'">
              {{ t('admin.suspendWarning') }}
            </p>
            <p v-else>
              {{ t('admin.activateWarning') }}
            </p>
          </div>

          <div v-if="actionError" class="alert errorAlert">{{ actionError }}</div>

          <div class="modalActions">
            <button class="actionBtn cancelBtn" @click="closeModal" :disabled="actionLoading">
              {{ t('admin.cancel') }}
            </button>
            <button 
              class="actionBtn" 
              :class="modalAction === 'suspend' ? 'suspendBtn' : 'activateBtn'"
              @click="handleAction"
              :disabled="actionLoading"
            >
              <span v-if="actionLoading">{{ t('admin.processing') }}</span>
              <span v-else>{{ modalAction === 'suspend' ? t('admin.suspend') : t('admin.activate') }}</span>
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
.filterTab.active-tab.active { background: #4caf50; color: #fff; }
.filterTab.suspended-tab.active { background: #ff5252; color: #fff; }
.filterTab.verified-tab.active { background: #2196f3; color: #fff; }
.filterTab.unverified-tab.active { background: #ff9800; color: #000; }
.filterTab.doctor-tab.active { background: #2196f3; color: #fff; }
.filterTab.client-tab.active { background: #4caf50; color: #fff; }
.filterTab.admin-tab.active { background: #ff9800; color: #000; }

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
.filterTab.doctor-tab.active { background: #2196f3; color: #fff; }
.filterTab.client-tab.active { background: #4caf50; color: #fff; }
.filterTab.admin-tab.active { background: #ff9800; color: #000; }

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
.filterTab.doctor-tab.active { background: #2196f3; color: #fff; }
.filterTab.client-tab.active { background: #4caf50; color: #fff; }
.filterTab.admin-tab.active { background: #ff9800; color: #000; }

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

.usersList {
  display: grid;
  gap: 24px;
}

.userCard {
  background: #fff;
  border: 3px solid #000;
  box-shadow: 6px 6px 0px #000;
  padding: 24px;
  transition: transform 0.2s;
}
.userCard:hover {
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

.userInfo h3 {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 900;
  text-transform: uppercase;
}
.userInfo .email {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 700;
  color: #666;
}
.userInfo .specialty {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #2196f3;
}

.userInfo .roleTag {
  display: inline-block;
  margin: 4px 0 0;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  border: 2px solid #000;
  box-shadow: 2px 2px 0px #000;
}
.roleTag.role-doctor { background: #2196f3; color: #fff; }
.roleTag.role-client { background: #4caf50; color: #fff; }
.roleTag.role-admin { background: #ff9800; color: #000; }

.badges {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}

.statusBadge {
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  border: 3px solid #000;
  box-shadow: 2px 2px 0px #000;
}
.status-active { background: #4caf50; color: #fff; }
.status-suspended { background: #ff5252; color: #fff; }

.verifiedBadge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #2196f3;
  color: #fff;
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  border: 3px solid #000;
  box-shadow: 2px 2px 0px #000;
}

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

.cardActions {
  display: flex;
  gap: 12px;
  padding-top: 20px;
  border-top: 3px solid #eee;
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

.suspendBtn {
  background: #ff5252;
  color: #fff;
}
.suspendBtn span {
  color: #fff;
}
.activateBtn {
  background: #4caf50;
  color: #fff;
}
.activateBtn span {
  color: #fff;
}
.verifyBtn {
  background: #2196f3;
  color: #fff;
}
.verifyBtn span {
  color: #fff;
}
.viewBtn {
  background: #9c27b0;
  color: #fff;
  text-decoration: none;
}
.viewBtn span {
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

.userDetails {
  background: #f8f8f8;
  border: 3px solid #000;
  padding: 16px;
}
.userDetails p {
  margin: 8px 0;
  font-weight: 700;
  font-size: 16px;
  color: #000;
}
.userDetails strong {
  color: #000;
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

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 24px;
  padding: 20px;
  background: #fff;
  border: 3px solid #000;
  box-shadow: 4px 4px 0px #000;
}

.pageBtn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: #fff;
  border: 3px solid #000;
  font-weight: 900;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 3px 3px 0px #000;
  transition: all 0.2s;
  text-transform: uppercase;
  font-family: inherit;
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

@media (max-width: 768px) {
  .pageTitle { font-size: 36px; }
  .pageHeader { flex-direction: column; align-items: flex-start; }
  .cardHeader { flex-direction: column; gap: 12px; }
  .badges { align-items: flex-start; }
  .modalContent { padding: 30px 20px; }
}
</style>
