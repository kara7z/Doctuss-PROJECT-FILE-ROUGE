<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { api } from '@/config/api'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const activeTab = ref('rated')
const doctorsScrollRef = ref(null)

const tabs = [
  {
    key: 'rated',
    label: computed(() => t('doctors.tabs.mostRated')),
    svg: '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
  },
  {
    key: 'experience',
    label: computed(() => t('doctors.tabs.experience')),
    svg: '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>',
  },
  {
    key: 'popular',
    label: computed(() => t('doctors.tabs.popular')),
    svg: '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
  },
]

const doctors = ref([])

const getDoctorEndpoint = () => {
  const params = new URLSearchParams()

  if (activeTab.value === 'rated') {
    params.append('sort', 'rating')
    params.append('per_page', '15')
  } else if (activeTab.value === 'experience') {
    params.append('sort', 'experience')
    params.append('per_page', '15')
  } else {
    params.append('per_page', '100')
  }

  return `/doctors?${params.toString()}`
}

const fetchDoctors = async () => {
  try {
    const res = await api(getDoctorEndpoint())
    if (res.ok) {
      const json = await res.json()
      const data = json.data || []
      
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
    }
  } catch (error) {
    console.error('Failed to load doctors:', error)
  }
}

onMounted(() => {
  fetchDoctors()
})

watch(activeTab, () => {
  fetchDoctors()
})

const popularCategoryDoctors = computed(() => {
  const seen = new Set()
  return doctors.value.filter(d => {
    if (!d.category || seen.has(d.category)) return false
    seen.add(d.category)
    return true
  })
})

const filteredDoctors = computed(() => {
  let result = doctors.value
  if (activeTab.value === 'rated') {
    result = doctors.value
  } else if (activeTab.value === 'experience') {
    result = doctors.value
  } else if (activeTab.value === 'popular') {
    result = popularCategoryDoctors.value
  }
  return result.slice(0, 15)
})

const scrollDoctors = (dir) => {
  if (doctorsScrollRef.value) {
    doctorsScrollRef.value.scrollBy({ left: dir * 300, behavior: 'smooth' })
  }
}
</script>

<template>
  <section class="doctorsSection">
    <!-- Section header -->
    <div class="doctorsHeader">
      <div class="doctorsTitleBlock">
        <h2 class="doctorsSectionTitle">Find Your <span class="doctorsTitleHighlight">Doctor</span></h2>
        <p class="doctorsSectionSub">Browse through our verified specialists.</p>
      </div>

      <!-- Filter pills -->
      <div class="filterTabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="['filterTab', { active: activeTab === tab.key }]"
          @click="activeTab = tab.key"
        >
          <span class="filterTabIcon" v-html="tab.svg"></span>
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Scroll track with arrows -->
    <div class="doctorsTrackWrapper" v-if="filteredDoctors.length > 0">
      <button class="scrollArrow scrollArrowLeft" @click="scrollDoctors(-1)" aria-label="Scroll left">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      </button>

      <div class="doctorsScroll" ref="doctorsScrollRef">
        <div class="doctorCard" v-for="doctor in filteredDoctors" :key="doctor.id" @click="$router.push(`/doctor/${doctor.id}`)"  role="button" :aria-label="`View profile of ${doctor.name}`">
          <!-- Status badge -->
          <span class="doctorStatusBadge" :class="'status-' + doctor.status">
            {{ t('search.status.' + doctor.status.toLowerCase()) }}
          </span>

          <!-- Avatar -->
          <div class="doctorAvatarWrap">
            <div class="doctorAvatar" :style="{ background: doctor.avatarGradient }">
              <img v-if="doctor.profilePicture" :src="doctor.profilePicture" :alt="doctor.name" class="doctorAvatarImg">
              <span v-else>{{ doctor.initials }}</span>
            </div>
            <div class="doctorAvatarRing"></div>
          </div>

          <!-- Info -->
          <div class="doctorInfo">
            <h4 class="doctorName">{{ doctor.name }}</h4>
            <span class="doctorSpecialty">{{ doctor.specialtyId ? t(`specialties.${doctor.specialtyId}`) : doctor.specialty }}</span>

            <div class="doctorMeta">
              <span class="doctorRating">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="#F6D506" stroke="#F6D506" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                {{ doctor.rating }}
              </span>
              <span class="doctorExp">{{ doctor.experience }} {{ t('doctors.yearsExp') }}</span>
            </div>

            <div class="doctorMetrics">
              <div class="doctorMetric">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span>{{ doctor.city }}</span>
              </div>
              <div class="doctorMetric">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                <span>{{ doctor.reviewsCount }} {{ doctor.reviewsCount === 1 ? t('doctors.review') : t('doctors.reviews') }}</span>
              </div>
            </div>
          </div>

          <!-- Action -->
          <button class="doctorBookBtn" @click.stop="$router.push(`/doctor/${doctor.id}`)">{{ t('doctors.bookAppointment') }}</button>
        </div>
      </div>

      <button class="scrollArrow scrollArrowRight" @click="scrollDoctors(1)" aria-label="Scroll right">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
      </button>
    </div>
    
    <!-- Empty State -->
    <div class="doctorsEmptyState" v-else>
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F6D506" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="8" y1="12" x2="16" y2="12"></line>
      </svg>
      <h3>{{ t('doctors.noResults') }}</h3>
      <p>{{ t('doctors.noResultsDesc') }}</p>
    </div>
  </section>
</template>

<style scoped>
.doctorsSection {
  padding: 80px 0;
  background: #F6D506;
  position: relative;
  overflow: hidden;
  border-top: 5px solid #000;
  border-bottom: 5px solid #000;
}

/* Header */
.doctorsHeader {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 24px;
  padding: 0 6% 40px;
}
.doctorsTitleBlock { display: flex; flex-direction: column; gap: 8px; }
.doctorsSectionLabel {
  font-size: 14px; font-weight: 900; letter-spacing: 3px;
  text-transform: uppercase; color: #000;
  padding: 4px 10px; background: #fff; border: 3px solid #000; align-self: flex-start;
  box-shadow: 4px 4px 0px #000;
}
.doctorsSectionTitle { font-size: 48px; font-weight: 900; color: #000; margin: 0; line-height: 1.1; text-transform: uppercase; letter-spacing: -1px; }
.doctorsTitleHighlight { color: #fff; text-shadow: 3px 3px 0 #000; -webkit-text-stroke: 2px #000; }
.doctorsSectionSub { font-size: 18px; color: #000; font-weight: 700; margin: 0; max-width: 420px; line-height: 1.4; }

/* Filter pills */
.filterTabs { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.filterTab {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 24px; border-radius: 0px;
  border: 3px solid #000;
  background: #fff;
  color: #000;
  font-size: 14px; font-weight: 900; cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  letter-spacing: 0.5px; white-space: nowrap; text-transform: uppercase;
  box-shadow: 6px 6px 0px rgba(0,0,0,1);
}
.filterTabIcon { display: flex; align-items: center; flex-shrink: 0; color: inherit; }
.filterTab:hover { transform: translate(-3px, -3px); box-shadow: 9px 9px 0px rgba(0,0,0,1); }
.filterTab.active { background: #000; color: #F6D506; }

/* Track wrapper */
.doctorsTrackWrapper { position: relative; display: flex; align-items: center; padding: 20px 0; }

/* Arrow buttons */
.scrollArrow {
  flex-shrink: 0; width: 56px; height: 56px; border-radius: 0px;
  border: 4px solid #000; background: #fff; color: #000;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  z-index: 10; position: absolute; top: 50%; transform: translateY(-50%);
  box-shadow: 6px 6px 0px #000;
}
.scrollArrow:hover { background: #000; color: #F6D506; transform: translateY(-50%) translate(-3px,-3px); box-shadow: 9px 9px 0px #F6D506; }
.scrollArrowLeft  { left: 20px; }
.scrollArrowRight { right: 20px; }

/* Scroll track */
.doctorsScroll {
  display: flex; gap: 32px;
  overflow-x: auto; overflow-y: visible;
  scroll-snap-type: x mandatory;
  scroll-padding-inline: 50%;
  padding: 16px 40px;
  scrollbar-width: none; -ms-overflow-style: none; width: 100%;
}
.doctorsScroll::-webkit-scrollbar { display: none; }

/* Doctor card */
.doctorCard {
  flex-shrink: 0; width: 300px;
  scroll-snap-align: center;
  background: #fff;
  border: 4px solid #000;
  border-radius: 0px; padding: 32px 24px 24px;
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
  padding: 6px 12px; border-radius: 0px; font-size: 11px;
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
  width: 90px; height: 90px; border-radius: 0px; border: 4px solid #000;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; font-weight: 900; color: #fff;
  letter-spacing: 1px; text-shadow: 2px 2px 0 #000;
  position: relative; z-index: 1;
  overflow: hidden; box-shadow: 6px 6px 0px #000;
}
.doctorAvatarImg {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 0px;
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
  width: 100%; padding: 14px 0; border-radius: 0px;
  border: 3px solid #000; background: #000;
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
  border-radius: 0px;
  margin: 0 6%;
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

/* ── Tablet ── */
@media (max-width: 768px) {
  .doctorsSection { padding: 56px 0; }
  .doctorsHeader { flex-direction: column; align-items: flex-start; gap: 20px; padding: 0 5% 28px; }
  .doctorsSectionTitle { font-size: 36px; }
  .doctorsSectionSub { font-size: 16px; max-width: 100%; }
  .filterTabs { width: 100%; overflow-x: auto; flex-wrap: nowrap; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 8px; }
  .filterTabs::-webkit-scrollbar { display: none; }
  .filterTab { font-size: 13px; padding: 10px 18px; flex-shrink: 0; }
  .doctorsScroll { gap: 24px; }
  .doctorCard { width: 280px; }
}

/* ── Mobile ── */
@media (max-width: 480px) {
  .doctorsSection { padding: 40px 0 48px; border-width: 3px; }
  .doctorsHeader { padding: 0 4% 22px; gap: 16px; }
  .doctorsSectionTitle { font-size: 28px; }
  .doctorsSectionSub { display: none; }
  .filterTabs { gap: 8px; }
  .filterTab { font-size: 12px; padding: 8px 16px; box-shadow: 4px 4px 0px rgba(0,0,0,1); }
  .filterTab:hover { box-shadow: 4px 4px 0px rgba(0,0,0,1); transform: translate(-2px, -2px); }
  .doctorsScroll { gap: 16px; }
  .doctorCard { width: 85vw; max-width: 300px; padding: 24px 20px 20px; box-shadow: 8px 8px 0px rgba(0,0,0,1); }
  .doctorAvatar { width: 72px; height: 72px; font-size: 22px; }
  .doctorStatusBadge { font-size: 10px; padding: 4px 8px; top: -12px; right: 14px; }
  .doctorName { font-size: 18px; }
  .doctorSpecialty { font-size: 11px; letter-spacing: 1px; }
  .doctorBookBtn { font-size: 14px; padding: 12px 0; }
  .scrollArrow { display: none; }
}
</style>
