<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/config/api'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const isRTL = computed(() => locale.value === 'ar')

const isSidebarOpen = ref(false)

const searchQuery = ref(route.query.q || '')
const filters = ref({
  gender: route.query.gender || '',
  experience: route.query.experience || '',
  city: route.query.city || '',
  specialties: route.query.specialties ? (Array.isArray(route.query.specialties) ? route.query.specialties : [route.query.specialties]) : [],
  category: route.query.category || '',
  status: route.query.status || ''
})

const doctors = ref([])
const specialtiesList = ref([])
const categoriesList = ref([])
const citiesList = ref([])
const loading = ref(false)
const currentPage = ref(parseInt(route.query.page) || 1)
const totalPages = ref(1)
const totalDoctors = ref(0)
let fetchTimeout = null

const fetchCategories = async () => {
  try {
    const res = await api('/categories')
    if (res.ok) {
      const { data } = await res.json()
      categoriesList.value = data
    }
  } catch (error) {
    console.error('Failed to load categories', error)
  }
}

const fetchSpecialties = async () => {
  try {
    const res = await api('/specialties')
    if (res.ok) {
      const { data } = await res.json()
      specialtiesList.value = data
    }
  } catch (error) {
    console.error('Failed to load specialties', error)
  }
}

const fetchCities = async () => {
  try {
    const res = await api('/cities')
    if (res.ok) {
      const { data } = await res.json()
      citiesList.value = data
    }
  } catch (error) {
    console.error('Failed to load cities', error)
  }
}

const applyFilters = () => {
  const query = {}
  if (searchQuery.value.trim()) query.q = searchQuery.value.trim()
  if (filters.value.gender) query.gender = filters.value.gender
  if (filters.value.experience) query.experience = filters.value.experience
  if (filters.value.city.trim()) query.city = filters.value.city.trim()
  if (filters.value.specialties.length > 0) query.specialties = filters.value.specialties
  if (filters.value.category) query.category = filters.value.category
  if (filters.value.status) query.status = filters.value.status
  currentPage.value = 1

  router.replace({ path: '/search', query })
}

const clearFilters = () => {
  searchQuery.value = ''
  filters.value = { gender: '', experience: '', city: '', specialties: [], category: '', status: '' }
  currentPage.value = 1
  router.replace({ path: '/search' })
}

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  const query = { ...route.query, page }
  if (page === 1) delete query.page
  router.replace({ path: '/search', query })
}

const fetchDoctors = async () => {
  loading.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
  try {
    const params = new URLSearchParams()
    if (route.query.q) params.append('q', route.query.q)
    if (route.query.gender) params.append('gender', route.query.gender)
    if (route.query.experience) params.append('experience', route.query.experience)
    if (route.query.city) params.append('city', route.query.city)
    if (route.query.specialties) {
      const specs = Array.isArray(route.query.specialties) ? route.query.specialties : [route.query.specialties]
      params.append('specialties', specs.join(','))
    }
    if (route.query.categories) {
      const cats = Array.isArray(route.query.categories) ? route.query.categories : [route.query.categories]
      params.append('categories', cats.join(','))
    }
    if (route.query.category) {
      params.append('categories', route.query.category)
    }
    if (route.query.status) {
      params.append('status', route.query.status)
    }
    params.append('page', currentPage.value)
    
    const endpoint = `/doctors?${params.toString()}`
    const res = await api(endpoint)
    if (res.ok) {
      const json = await res.json()
      const data = json.data
      
      const colors = [
        ['#667eea', '#764ba2'], ['#f093fb', '#f5576c'], ['#4facfe', '#00f2fe'],
        ['#43e97b', '#38f9d7'], ['#fa709a', '#fee140'], ['#a18cd1', '#fbc2eb'],
        ['#f7971e', '#ffd200'], ['#30cfd0', '#667eea']
      ]

      doctors.value = data.map(d => {
        const dp = d.doctor_profile || {}
        const spec = dp.specialty || {}
        
        let exp = 0
        if (dp.experience_start_date) {
            const start = new Date(dp.experience_start_date)
            exp = Math.max(0, new Date().getFullYear() - start.getFullYear())
        }

        const [c1, c2] = colors[d.id % colors.length]

        return {
          id: d.id,
          name: d.name,
          initials: d.name.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase(),
          profilePicture: dp.profile_picture,
          specialty: spec.name || 'Specialist',
          specialtyId: spec.id,
          rating: dp.avg_rating ? parseFloat(dp.avg_rating).toFixed(1) : 0,
          experience: exp,
          reviewsCount: dp.reviews_count || 0,
          status: dp.current_status || 'Unavailable',
          city: dp.city || 'Unknown',
          avatarGradient: `linear-gradient(135deg, ${c1}, ${c2})`,
          category: spec.category?.name || 'General'
        }
      })
      const meta = json.meta
      totalPages.value = meta?.last_page || 1
      totalDoctors.value = meta?.total || data.length
    }
  } catch (error) {
    console.error('Failed to load doctors:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCategories()
  fetchSpecialties()
  fetchCities()
  fetchDoctors()
})

onUnmounted(() => {
  if (fetchTimeout) clearTimeout(fetchTimeout)
})

watch(() => route.query, (newQuery) => {
  searchQuery.value = newQuery.q || ''
  filters.value.gender = newQuery.gender || ''
  filters.value.experience = newQuery.experience || ''
  filters.value.city = newQuery.city || ''
  filters.value.specialties = newQuery.specialties ? (Array.isArray(newQuery.specialties) ? newQuery.specialties : [newQuery.specialties]) : []
  filters.value.category = newQuery.category || ''
  filters.value.status = newQuery.status || ''
  currentPage.value = parseInt(newQuery.page) || 1
  
  if (fetchTimeout) clearTimeout(fetchTimeout)
  fetchTimeout = setTimeout(() => {
    fetchDoctors()
  }, 300)
}, { deep: true })
</script>

<template>
  <div class="pageLayout">
    <section class="searchSection">
      <div class="searchHeader">
        <h2 class="searchTitle">{{ t('search.title') }} <span class="searchHighlight">{{ t('search.results') }}</span></h2>
        <form class="searchBar" @submit.prevent="applyFilters">
          <input v-model="searchQuery" type="text" :placeholder="t('hero.searchPlaceholder')" @input="applyFilters" />
          <button type="submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </button>
        </form>
      </div>

      <div class="searchLayout">
        <button class="mobileSidebarToggle" @click="isSidebarOpen = !isSidebarOpen">
          <svg style="margin-right: 8px" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
          {{ isSidebarOpen ? t('search.hideFilters') : t('search.showFilters') }}
        </button>

        <aside class="sidebar" :class="{ 'sidebarOpen': isSidebarOpen }">
          <div class="filterBlock">
            <h3 class="filterTitle">{{ t('search.filters') }}</h3>
            
            <div class="filterGroup">
              <h4>{{ t('search.city') }}</h4>
              <select class="filterSelect" v-model="filters.city" @change="applyFilters">
                <option value="">{{ t('search.allCities') }}</option>
                <option v-for="city in citiesList" :key="city" :value="city">{{ city }}</option>
              </select>
            </div>

            <div class="filterGroup">
              <h4>{{ t('search.gender') }}</h4>
              <label class="radioLabel">
                <input type="radio" name="gender" value="" v-model="filters.gender" @change="applyFilters"> 
                <span>{{ t('search.any') }}</span>
              </label>
              <label class="radioLabel">
                <input type="radio" name="gender" value="male" v-model="filters.gender" @change="applyFilters"> 
                <span>{{ t('search.male') }}</span>
              </label>
              <label class="radioLabel">
                <input type="radio" name="gender" value="female" v-model="filters.gender" @change="applyFilters"> 
                <span>{{ t('search.female') }}</span>
              </label>
            </div>

            <div class="filterGroup">
              <h4>Status</h4>
              <label class="radioLabel">
                <input type="radio" name="status" value="" v-model="filters.status" @change="applyFilters"> 
                <span>{{ t('search.any') }}</span>
              </label>
              <label class="radioLabel">
                <input type="radio" name="status" value="Available" v-model="filters.status" @change="applyFilters"> 
                <span>{{ t('search.status.available') }}</span>
              </label>
              <label class="radioLabel">
                <input type="radio" name="status" value="Unavailable" v-model="filters.status" @change="applyFilters"> 
                <span>{{ t('search.status.unavailable') }}</span>
              </label>
            </div>

            <div class="filterGroup">
              <h4>{{ t('search.experience') }}</h4>
              <select class="filterSelect" v-model="filters.experience" @change="applyFilters">
                <option value="">{{ t('search.anyExperience') }}</option>
                <option value="2">2{{ t('search.yearsPlus') }}</option>
                <option value="5">5{{ t('search.yearsPlus') }}</option>
                <option value="10">10{{ t('search.yearsPlus') }}</option>
                <option value="15">15{{ t('search.yearsPlus') }}</option>
              </select>
            </div>

            <div class="filterGroup">
              <h4>{{ t('search.category') }}</h4>
              <select class="filterSelect" v-model="filters.category" @change="applyFilters">
                <option value="">{{ t('search.allCategories') }}</option>
                <option v-for="cat in categoriesList" :key="cat.id" :value="cat.id.toString()">{{ t(`categories.${cat.id}`) }}</option>
              </select>
            </div>

            <div class="filterGroup">
              <h4>{{ t('search.specialties') }}</h4>
              <div class="checkboxList">
                <label v-for="spec in specialtiesList" :key="spec.id" class="checkboxLabel">
                  <input type="checkbox" :value="spec.id.toString()" v-model="filters.specialties" @change="applyFilters">
                  <span>{{ t(`specialties.${spec.id}`) }}</span>
                </label>
              </div>
            </div>

            <button class="clearFiltersBtn" @click="clearFilters">{{ t('search.resetFilters') }}</button>
          </div>
        </aside>

        <div class="resultsArea">
          <div v-if="loading" class="doctorGrid">
            <div class="skeletonCard" v-for="n in 9" :key="n">
              <div class="skeletonBadge"></div>
              <div class="skeletonAvatar"></div>
              <div class="skeletonLine wide"></div>
              <div class="skeletonLine medium"></div>
              <div class="skeletonMeta">
                <div class="skeletonLine short"></div>
                <div class="skeletonLine short"></div>
              </div>
              <div class="skeletonBtn"></div>
            </div>
          </div>

          <div v-else-if="doctors.length > 0" class="doctorGrid">
            <div class="doctorCard" v-for="doctor in doctors" :key="doctor.id">
              <span class="doctorStatusBadge" :class="'status-' + doctor.status">
                {{ t('search.status.' + doctor.status.toLowerCase()) }}
              </span>

              <div class="doctorAvatarWrap">
                <div class="doctorAvatar" :style="{ background: doctor.avatarGradient }">
                  <img v-if="doctor.profilePicture" :src="doctor.profilePicture" :alt="doctor.name" class="doctorAvatarImg">
                  <span v-else>{{ doctor.initials }}</span>
                </div>
              </div>

              <div class="doctorInfo">
                <h4 class="doctorName">{{ doctor.name }}</h4>
                <span class="doctorSpecialty">{{ doctor.specialtyId ? t(`specialties.${doctor.specialtyId}`) : doctor.specialty }}</span>

                <div class="doctorMeta">
                  <span class="doctorRating">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="#F6D506" stroke="#F6D506" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    {{ doctor.rating }}
                  </span>
                  <span class="doctorExp">{{ doctor.experience }} {{ t('search.yearsExp') }}</span>
                </div>

                <div class="doctorMetrics">
                  <div class="doctorMetric">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span>{{ doctor.city }}</span>
                  </div>
                  <div class="doctorMetric">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span>{{ doctor.reviewsCount }} {{ doctor.reviewsCount === 1 ? t('search.review') : t('search.reviews') }}</span>
                  </div>
                </div>
              </div>

              <button class="doctorBookBtn" @click="router.push(`/doctor/${doctor.id}`)">{{ t('search.viewProfile') }}</button>
            </div>
          </div>

          <div v-else class="doctorsEmptyState">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F6D506" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            <h3>{{ t('search.noResults') }}</h3>
            <p>{{ t('search.noResultsDesc') }}</p>
          </div>

          <div v-if="!loading && totalPages > 1" class="pagination">
            <button class="pageBtn" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
              <svg v-if="!isRTL" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            <template v-for="page in totalPages" :key="page">
              <button
                v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                class="pageBtn"
                :class="{ active: page === currentPage }"
                @click="goToPage(page)"
              >{{ page }}</button>
              <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="pageDots">…</span>
            </template>

            <button class="pageBtn" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
              <svg v-if="!isRTL" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
          </div>


        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.searchSection {
  padding: 120px 0 80px;
  background-color: #f0f0f0;
  background-image: radial-gradient(rgba(0, 0, 0, 0.15) 2px, transparent 0);
  background-size: 24px 24px;
  min-height: calc(100vh - 80px);
}

.searchHeader {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 30px;
  margin-bottom: 50px;
  padding: 0 6%;
}

.searchTitle {
  font-size: 52px;
  font-weight: 900;
  color: #000;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: -1px;
}
.searchHighlight {
  background: #F6D506;
  padding: 0 12px;
  border: 4px solid #000;
  box-shadow: 6px 6px 0px #000;
  display: inline-block;
  transform: rotate(-2deg);
}

.searchBar {
  display: flex;
  align-items: center;
  width: 650px;
  max-width: 100%;
  background: #F6D506;
  border-radius: 50px;
  padding: 10px 10px 10px 28px;
  border: 3px solid black;
  box-shadow: 6px 6px 0px #000;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.searchBar:hover {
  transform: translate(-2px, -2px);
  box-shadow: 8px 8px 0px #000;
  background: #FFE55C;
}
.searchBar:focus-within {
  transform: translate(2px, 2px);
  box-shadow: 4px 4px 0px #000;
  background: #ffffff;
}
.searchBar input {
  flex: 1;
  padding: 14px 16px;
  font-size: 18px;
  border: none;
  background: transparent;
  outline: none;
  color: black;
  font-weight: 700;
  font-family: inherit;
}
.searchBar input::placeholder { color: rgba(0,0,0,0.55); font-weight: 600; transition: color 0.3s; }
.searchBar:hover input::placeholder { color: rgba(0,0,0,0.8); }

.searchBar button {
  width: 56px; height: 56px;
  display: flex; align-items: center; justify-content: center;
  background: #000;
  color: #F6D506;
  border: none; border-radius: 50%;
  cursor: pointer; flex-shrink: 0;
  transition: all 0.2s ease;
}
.searchBar button:hover {
  background: #222;
  transform: scale(1.05);
}
.searchBar button:active {
  transform: scale(0.95);
}

/* Layout */
.searchLayout {
  display: flex;
  gap: 40px;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 6%;
  flex-direction: row;
}

/* Sidebar styling */
.sidebar {
  width: 280px;
  flex-shrink: 0;
}
.mobileSidebarToggle {
  display: none;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 14px 0;
  border: 3px solid #000;
  background: #000;
  color: #F6D506;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 1px;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 4px 4px 0px #000;
  margin-bottom: 24px;
}
.mobileSidebarToggle:hover {
  background: #fff;
  color: #000;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}

.filterBlock {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 8px 8px 0px #000;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  color: #000;
}
.filterTitle {
  font-size: 24px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0;
  border-bottom: 3px solid #000;
  padding-bottom: 12px;
}

.filterGroup h4 {
  font-size: 16px;
  font-weight: 900;
  text-transform: uppercase;
  margin: 0 0 12px 0;
}

.filterInput {
  width: 100%;
  padding: 10px;
  border: 3px solid #000;
  font-weight: 700;
  font-size: 15px;
  background: #fff;
  color: #000;
  outline: none;
  box-shadow: 4px 4px 0px #000;
  transition: transform 0.1s, box-shadow 0.1s;
}
.filterInput:focus {
  transform: translate(2px, 2px);
  box-shadow: 2px 2px 0px #000;
}

/* Radio & Checkbox */
.radioLabel, .checkboxLabel {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 15px;
}
.radioLabel input, .checkboxLabel input {
  appearance: none;
  width: 20px;
  height: 20px;
  border: 3px solid #000;
  border-radius: 0;
  background: #fff;
  cursor: pointer;
  position: relative;
}
.radioLabel input:checked, .checkboxLabel input:checked {
  background: #F6D506;
}
.radioLabel input:checked::after, .checkboxLabel input:checked::after {
  content: '';
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 8px; height: 8px;
  background: #000;
}

.checkboxList {
  display: flex;
  flex-direction: column;
  max-height: 250px;
  overflow-y: auto;
  padding-right: 10px;
}
.checkboxList::-webkit-scrollbar { width: 6px; }
.checkboxList::-webkit-scrollbar-thumb { background: #000; }

.filterSelect {
  width: 100%;
  padding: 10px;
  border: 3px solid #000;
  font-weight: 700;
  font-size: 15px;
  background: #fff;
  color: #000;
  cursor: pointer;
  outline: none;
  box-shadow: 4px 4px 0px #000;
  transition: transform 0.1s, box-shadow 0.1s;
}
.filterSelect:focus {
  transform: translate(2px, 2px);
  box-shadow: 2px 2px 0px #000;
}

.clearFiltersBtn {
  width: 100%;
  padding: 12px 0;
  border: 3px solid #000;
  background: #fff;
  color: #000;
  font-size: 14px;
  font-weight: 900;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 4px 4px 0px #000;
}
.clearFiltersBtn:hover {
  background: #ff5252;
  color: #fff;
  transform: translate(-2px, -2px);
  box-shadow: 6px 6px 0px #000;
}

.resultsArea {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 32px;
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

.resultsCount {
  text-align: center;
  font-size: 13px;
  font-weight: 800;
  color: #000;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.doctorGrid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 32px;
}

/* Card Styling */
.doctorCard {
  width: 100%;
  background: #fff;
  border: 4px solid #000;
  padding: 32px 24px 24px;
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  position: relative;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  box-shadow: 12px 12px 0px rgba(0,0,0,1);
}
.doctorCard:hover {
  transform: translate(-6px, -6px);
  box-shadow: 18px 18px 0px rgba(0,0,0,1);
}

/* Status badge */
.doctorStatusBadge {
  position: absolute; top: 12px; right: 12px;
  padding: 6px 12px; font-size: 11px;
  font-weight: 900; text-transform: uppercase; letter-spacing: 1px;
  z-index: 2; border: 3px solid #000;
  box-shadow: 4px 4px 0px rgba(0,0,0,1);
}
.status-Available { background: #4caf50; color: #fff; }
.status-Busy { background: #ff9800; color: #000; }
.status-Unavailable { background: #9e9e9e; color: #000; }
.status-Offline { background: #9e9e9e; color: #000; }

/* Avatar */
.doctorAvatarWrap { position: relative; margin-top: 10px; }
.doctorAvatar {
  width: 90px; height: 90px; border: 4px solid #000;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; font-weight: 900; color: #fff;
  letter-spacing: 1px; text-shadow: 2px 2px 0 #000;
  position: relative; z-index: 1;
  overflow: hidden; box-shadow: 6px 6px 0px #000;
}
.doctorAvatarImg {
  width: 100%; height: 100%;
  object-fit: cover;
}

/* Card info */
.doctorInfo { display: flex; flex-direction: column; align-items: center; gap: 8px; width: 100%; text-align: center; margin-top: 8px; }
.doctorName { font-size: 20px; font-weight: 900; color: #000; margin: 0; line-height: 1.1; text-transform: uppercase; letter-spacing: -0.5px;}
.doctorSpecialty { font-size: 12px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #000; background: #F6D506; padding: 2px 8px; border: 2px solid #000;}
.doctorMeta { display: flex; align-items: center; gap: 16px; margin-top: 6px; }
.doctorRating { display: flex; align-items: center; gap: 4px; font-size: 14px; font-weight: 900; color: #000; }
.doctorExp { font-size: 13px; color: #000; font-weight: 800; }
.doctorMetrics { display: flex; align-items: center; gap: 16px; margin-top: 4px; flex-wrap: wrap; justify-content: center; }
.doctorMetric { display: flex; align-items: center; gap: 4px; font-size: 13px; color: #000; font-weight: 800; }

/* Book button */
.doctorBookBtn {
  width: 100%; padding: 14px 0; border: 3px solid #000; background: #000;
  color: #F6D506; font-size: 15px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase;
  cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); margin-top: 8px;
}
.doctorBookBtn:hover { background: #fff; color: #000; transform: translate(-4px, -4px); box-shadow: 6px 6px 0px #000; }

/* Empty state */
.doctorsEmptyState {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 20px;
  border: 4px dashed #000;
  background: #fff;
}
.doctorsEmptyState h3 {
  color: #000;
  text-transform: uppercase;
  font-size: 32px; font-weight: 900;
  margin: 16px 0 8px;
}
.doctorsEmptyState p {
  color: #000; font-weight: 700;
  font-size: 16px;
  line-height: 1.5;
  margin: 0;
}

/* Skeleton */
@keyframes shimmer {
  0% { background-position: -600px 0; }
  100% { background-position: 600px 0; }
}

.skeletonCard {
  width: 100%;
  background: #fff;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px rgba(0,0,0,1);
  padding: 32px 24px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  position: relative;
}

.skeletonBadge {
  position: absolute; top: 12px; right: 12px;
  width: 72px; height: 28px;
  background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
  background-size: 600px 100%;
  animation: shimmer 1.4s infinite linear;
  border: 3px solid #e8e8e8;
}

.skeletonAvatar {
  width: 90px; height: 90px;
  border: 4px solid #e8e8e8;
  background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
  background-size: 600px 100%;
  animation: shimmer 1.4s infinite linear;
  margin-top: 10px;
}

.skeletonLine {
  height: 14px;
  border-radius: 0;
  background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
  background-size: 600px 100%;
  animation: shimmer 1.4s infinite linear;
}
.skeletonLine.wide  { width: 80%; }
.skeletonLine.medium { width: 55%; }
.skeletonLine.short  { width: 38%; height: 12px; }

.skeletonMeta {
  display: flex;
  gap: 16px;
  width: 100%;
  justify-content: center;
}

.skeletonBtn {
  width: 100%; height: 46px;
  background: linear-gradient(90deg, #e8e8e8 25%, #f5f5f5 50%, #e8e8e8 75%);
  background-size: 600px 100%;
  animation: shimmer 1.4s infinite linear;
  margin-top: 8px;
  border: 3px solid #e8e8e8;
}

@media (max-width: 1024px) {
  .searchLayout { flex-direction: column; }
  .mobileSidebarToggle { display: flex; }
  .sidebar { width: 100%; display: none; }
  .sidebar.sidebarOpen { display: block; }
  .filterBlock { padding: 20px; }
  .doctorGrid { grid-template-columns: repeat(2, 1fr); align-items: stretch; justify-content: center; }
}

@media (max-width: 768px) {
  .searchTitle { font-size: 36px; }
  .searchBar { width: 100%; padding: 6px 6px 6px 18px; }
  .searchBar input { font-size: 14px; padding: 10px; }
  .searchBar button { width: 40px; height: 40px; }
  .doctorGrid { grid-template-columns: repeat(1, 1fr); }
}

@media (max-width: 480px) {
  .searchTitle { font-size: 28px; }
  .doctorCard { width: 100%; max-width: 320px; padding: 24px 20px 20px; box-shadow: 8px 8px 0px rgba(0,0,0,1); }
  .doctorAvatar { width: 72px; height: 72px; font-size: 22px; }
  .doctorStatusBadge { font-size: 10px; padding: 4px 8px; top: -12px; right: 14px; }
  .doctorName { font-size: 18px; }
  .doctorSpecialty { font-size: 11px; letter-spacing: 1px; }
  .doctorBookBtn { font-size: 14px; padding: 12px 0; }
}
</style>
