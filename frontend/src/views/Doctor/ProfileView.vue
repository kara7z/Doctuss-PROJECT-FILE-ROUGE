<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/config/api'
import { useAuth } from '@/composables/useAuth'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const { user } = useAuth()

const doctorId = route.params.id
const doctor = ref(null)
const loading = ref(true)
const error = ref(null)

const fetchDoctor = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api(`/doctors/${doctorId}`)
    if (res.ok) {
      const { data } = await res.json()
      doctor.value = data
    } else {
      if (res.status === 404) {
        error.value = t('doctorProfile.doctorNotFound')
      } else {
        error.value = t('doctorProfile.failedToLoad')
      }
    }
  } catch (err) {
    console.error(err)
    error.value = t('doctorProfile.errorOccurred')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDoctor()
})

const goBack = () => {
  router.back()
}

// Computed properties for display
const profile = computed(() => doctor.value?.doctor_profile || {})
const specialty = computed(() => profile.value?.specialty || {})
const category = computed(() => specialty.value?.category || {})

const avatarInitials = computed(() => {
  if (!doctor.value?.name) return 'DR'
  return doctor.value.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
})

const experienceYears = computed(() => {
  if (!profile.value?.experience_start_date) return 0
  const start = new Date(profile.value.experience_start_date)
  return Math.max(0, new Date().getFullYear() - start.getFullYear())
})

const colors = [
  ['#667eea', '#764ba2'], ['#f093fb', '#f5576c'], ['#4facfe', '#00f2fe'],
  ['#43e97b', '#38f9d7'], ['#fa709a', '#fee140'], ['#a18cd1', '#fbc2eb'],
  ['#f7971e', '#ffd200'], ['#30cfd0', '#667eea']
]

const avatarGradient = computed(() => {
  const [c1, c2] = colors[(doctor.value?.id || 0) % colors.length]
  return `linear-gradient(135deg, ${c1}, ${c2})`
})

// Booking Logic
const selectedDate = ref('')
const selectedTime = ref('')
const bookingLoading = ref(false)
const bookingSuccess = ref(false)
const bookingError = ref('')

const getTodayString = () => {
  const d = new Date()
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().split('T')[0]
}
const minDate = getTodayString()

const parseDateTime = (dateStr, timeStr) => {
  const [year, month, day] = dateStr.split('-').map(Number)
  const [hour, minute] = timeStr.split(':').map(Number)
  return new Date(year, month - 1, day, hour, minute, 0, 0)
}

const getMinimumBookableDateTime = () => {
  const now = new Date()
  return new Date(now.getTime() + 60 * 60 * 1000)
}

const isAtLeastOneHourAhead = (dateStr, timeStr) => {
  const selectedAt = parseDateTime(dateStr, timeStr)
  return selectedAt.getTime() >= getMinimumBookableDateTime().getTime()
}

const upcomingWorkingDates = computed(() => {
  const dates = []
  const today = new Date()
  let daysChecked = 0
  let daysFound = 0
  
  while (daysFound < 7 && daysChecked < 30) {
    const checkDate = new Date(today)
    checkDate.setDate(today.getDate() + daysChecked)
    const dateStr = checkDate.toISOString().split('T')[0]
    
    const hasShift = getShiftForDate(dateStr) !== null
    
    if (hasShift) {
      dates.push({
        date: dateStr,
        label: checkDate.toLocaleDateString(locale.value, { weekday: 'short', month: 'short', day: 'numeric' })
      })
      daysFound++
    }
    
    daysChecked++
  }
  
  return dates
})

const getShiftForDate = (dateStr) => {
  if (profile.value.schedules?.length) {
    const d = new Date(dateStr + 'T12:00:00')
    const dayOfWeek = d.getDay()
    const schedule = profile.value.schedules.find(s => parseInt(s.day_of_week) === dayOfWeek)
    if (schedule) {
      const startParts = schedule.start_time.split(':')
      const endParts = schedule.end_time.split(':')
      const result = {
        startHour: parseInt(startParts[0], 10),
        endHour: parseInt(endParts[0], 10),
      }
      return result
    }
  }
  return null
}

const availableSlots = computed(() => {
  if (!selectedDate.value) return []

  const shift = getShiftForDate(selectedDate.value)
  if (!shift) return []

  const slots = []
  for (let hour = shift.startHour; hour < shift.endHour; hour++) {
    const formattedHour = hour.toString().padStart(2, '0') + ':00'
    const isPastMinimumTime = !isAtLeastOneHourAhead(selectedDate.value, formattedHour)

    const bookingInfo = profile.value.appointments?.find(app => {
      const slotDateTime = app.status === 'approved'
        ? (app.proposed_at || app.preferred_at)
        : app.preferred_at

      if (!slotDateTime) return false
      
      let appDateStr, appTimeStr
      
      if (slotDateTime.includes('T')) {
        const parts = slotDateTime.split('T')
        appDateStr = parts[0]
        appTimeStr = parts[1].substring(0, 5)
      } else {
        const parts = slotDateTime.split(' ')
        appDateStr = parts[0]
        appTimeStr = parts[1] ? parts[1].substring(0, 5) : '00:00'
      }

      return appDateStr === selectedDate.value && appTimeStr === formattedHour
    })

    let isDisabled = false
    let reason = ''
    let isBooked = false

    if (isPastMinimumTime) {
      isDisabled = true
      reason = t('appointment.must_be_one_hour_ahead')
    } else if (bookingInfo) {
      if (bookingInfo.status === 'approved') {
        isDisabled = true
        isBooked = true
        reason = t('doctorProfile.booked')
      } else if (bookingInfo.status === 'pending' && user.value && bookingInfo.client_id === user.value.id) {
        isDisabled = true
        reason = t('doctorProfile.alreadyHavePending')
      }
    }

    slots.push({
      time: formattedHour,
      disabled: isDisabled,
      reason: reason,
      isBooked: isBooked
    })
  }
  
  return slots
})

const selectedDateHasShift = computed(() => {
  if (!selectedDate.value) return false
  return getShiftForDate(selectedDate.value) !== null
})

const bookAppointment = async () => {
    if (!selectedDate.value || !selectedTime.value) return;
    
    const selectedSlot = availableSlots.value.find(s => s.time === selectedTime.value)
    if (selectedSlot?.disabled) {
        bookingError.value = selectedSlot.reason || t('doctorProfile.alreadyBookedSlot')
        return
    }

    if (!isAtLeastOneHourAhead(selectedDate.value, selectedTime.value)) {
        bookingError.value = t('appointment.must_be_one_hour_ahead')
        return
    }
    
    bookingLoading.value = true
    bookingError.value = ''
    bookingSuccess.value = false
    
    try {
        const preferredAt = `${selectedDate.value} ${selectedTime.value}:00`
        
        const res = await api('/appointments', {
            method: 'POST',
            body: JSON.stringify({
                doctor_profile_id: profile.value.id,
                preferred_at: preferredAt
            })
        })
        
        if (res.ok) {
            bookingSuccess.value = true
            selectedTime.value = ''
            selectedDate.value = ''
            await fetchDoctor()
            
            setTimeout(() => {
                bookingSuccess.value = false
            }, 3000)
        } else {
            const data = await res.json()
            
            if (res.status === 422) {
                if (data.errors?.preferred_at) {
                    const preferredAtError = Array.isArray(data.errors.preferred_at) ? data.errors.preferred_at[0] : ''
                    bookingError.value = preferredAtError === 'appointment.must_be_one_hour_ahead'
                        ? t('appointment.must_be_one_hour_ahead')
                        : t('doctorProfile.slotAlreadyBookedBackend')
                } else if (data.errors?.doctor_profile_id) {
                    bookingError.value = t('doctorProfile.activeAppointmentLimit')
                } else {
                    bookingError.value = data.message || t('doctorProfile.slotNotAvailable')
                }
                await fetchDoctor()
            } else if (res.status === 403) {
                bookingError.value = t('doctorProfile.noPermission')
            } else if (res.status === 401) {
                bookingError.value = t('doctorProfile.loginToBook')
            } else {
                bookingError.value = data.message || t('doctorProfile.failedToBook')
            }
        }
    } catch (e) {
        console.error('Booking error:', e)
        bookingError.value = t('doctorProfile.bookingError')
    } finally {
        bookingLoading.value = false
    }
}

const formatDate = (dateStr) => {
    const d = new Date(dateStr)
    const monthNames = [
        t('months.january'), t('months.february'), t('months.march'), t('months.april'),
        t('months.may'), t('months.june'), t('months.july'), t('months.august'),
        t('months.september'), t('months.october'), t('months.november'), t('months.december')
    ]
    return `${monthNames[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`
}

const getDayName = (dayOfWeek) => {
  const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
  return days[parseInt(dayOfWeek)] || 'sunday'
}

const isToday = (dayOfWeek) => {
  const today = new Date().getDay()
  return parseInt(dayOfWeek) === today
}

const isCurrentlyUnavailable = (schedule) => {
  const now = new Date()
  const currentTime = now.getHours() * 60 + now.getMinutes()
  const endTime = parseInt(schedule.end_time.split(':')[0]) * 60 + parseInt(schedule.end_time.split(':')[1])
  return currentTime >= endTime
}

const isCurrentlyAvailable = (schedule) => {
  const now = new Date()
  const currentTime = now.getHours() * 60 + now.getMinutes()
  const startTime = parseInt(schedule.start_time.split(':')[0]) * 60 + parseInt(schedule.start_time.split(':')[1])
  const endTime = parseInt(schedule.end_time.split(':')[0]) * 60 + parseInt(schedule.end_time.split(':')[1])
  return currentTime >= startTime && currentTime < endTime
}
</script>

<template>
  <div class="pageLayout">
    <section class="profileSection">
      
      <div v-if="loading" class="stateCard loadingState">
        <div class="spinner"></div>
        <h2>{{ t('doctorProfile.loadingProfile') }}</h2>
      </div>

      <div v-else-if="error" class="stateCard errorState">
        <h2>{{ error }}</h2>
        <button class="brutalistBtn" @click="goBack">{{ t('doctorProfile.goBack') }}</button>
      </div>

      <div v-else-if="doctor" class="profileContainer">
        <button class="backBtn" @click="goBack">
           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
           {{ t('doctorProfile.back') }}
        </button>

        <!-- Huge Hero Section -->
        <div class="heroCard">
            <div class="heroBanner" :style="{ backgroundImage: profile.banner_picture ? 'url(' + profile.banner_picture + ')' : 'none', backgroundColor: profile.banner_picture ? 'transparent' : '#000' }">
                <span class="statusBadge" :class="'status-' + profile.current_status">{{ t('search.status.' + (profile.current_status || 'unavailable').toLowerCase()) }}</span>
            </div>
            <div class="heroContent">
                <div class="avatarWrap">
                  <div class="avatar" :style="{ background: avatarGradient }">
                    <img v-if="profile.profile_picture" :src="profile.profile_picture" :alt="doctor.name" class="avatarImg">
                    <span v-else>{{ avatarInitials }}</span>
                  </div>
                </div>
                <div class="heroDetails">
                    <h1 class="doctorName">{{ doctor.name }}</h1>
                    <div class="badges">
                        <span class="specialtyBadge">{{ specialty.id ? t('specialties.' + specialty.id) : specialty.name }}</span>
                        <span class="categoryBadge" v-if="category.name">{{ category.id ? t('categories.' + category.id) : category.name }}</span>
                    </div>
                </div>
                <div class="heroMetrics">
                    <div class="metricBox">
                        <span class="metricValue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#F6D506" stroke="#000" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            {{ parseFloat(profile.avg_rating || 0).toFixed(1) }}
                        </span>
                        <span class="metricLabel">{{ profile.reviews_count || 0 }} {{ t('doctorProfile.reviews') }}</span>
                    </div>
                    <div class="metricBox">
                        <span class="metricValue">{{ experienceYears }}+</span>
                        <span class="metricLabel">{{ t('doctorProfile.yearsExp') }}</span>
                    </div>
                    <div class="metricBox verifiedBox" :class="{ verified: profile.is_verified }">
                        <svg v-if="profile.is_verified" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#4caf50" stroke="#000" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#ff5252" stroke="#000" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <span class="metricLabel">{{ profile.is_verified ? t('doctorProfile.verified') : t('doctorProfile.notVerified') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profileLayout">
          <main class="leftColumn">
            
            <div class="infoBlock">
              <h2 class="blockTitle">{{ t('doctorProfile.aboutMe') }} <span class="highlightText">{{ t('doctorProfile.me') }}</span></h2>
              <p class="bioText">{{ profile.bio || t('doctorProfile.noBio') }}</p>
            </div>

            <div class="gridBlock">
              <div class="infoCard">
                <div class="iconWrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div>
                <div><h3 class="cardLabel">{{ t('doctorProfile.hospitalClinic') }}</h3><p class="cardValue">{{ profile.hospital_name || t('doctorProfile.notSpecified') }}</p></div>
              </div>
              <div class="infoCard">
                <div class="iconWrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></div>
                <div>
                  <h3 class="cardLabel">{{ t('doctorProfile.location') }}</h3>
                  <p class="cardValue">{{ profile.city || t('doctorProfile.unknown') }}</p>
                  <a 
                    v-if="profile.location_link" 
                    :href="profile.location_link" 
                    target="_blank" 
                    class="mapLink"
                  >
                    {{ t('doctorProfile.viewOnMap') }}
                  </a>
                </div>
              </div>
              <div class="infoCard" v-if="profile.phone_number">
                <div class="iconWrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                <div>
                  <h3 class="cardLabel">{{ t('myProfile.phone') }}</h3>
                  <p class="cardValue">{{ profile.phone_number }}</p>
                </div>
              </div>
              <div class="infoCard" v-if="doctor.gender">
                <div class="iconWrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                <div>
                  <h3 class="cardLabel">{{ t('search.gender') }}</h3>
                  <p class="cardValue">{{ doctor.gender === 'male' ? t('search.male') : t('search.female') }}</p>
                </div>
              </div>
              <div class="infoCard" v-if="doctor.age !== null && doctor.age !== undefined">
                <div class="iconWrap"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"></path><circle cx="12" cy="12" r="10"></circle></svg></div>
                <div>
                  <h3 class="cardLabel">{{ t('common.age') }}</h3>
                  <p class="cardValue">{{ doctor.age }} {{ t('common.yearsOld') }}</p>
                </div>
              </div>
            </div>

            <!-- Reviews Section -->
            <div class="infoBlock reviewsBlock">
                <h2 class="blockTitle">{{ t('doctorProfile.patientReviews') }} <span class="highlightText">{{ t('doctorProfile.reviewsTitle') }}</span></h2>
                
                <div v-if="!profile.reviews || profile.reviews.length === 0" class="noReviews">
                    {{ t('doctorProfile.noReviews') }}
                </div>
                <div v-else class="reviewsList">
                    <div class="reviewItem" v-for="review in profile.reviews" :key="review.id">
                        <div class="reviewHeader">
                            <h4 class="reviewerName">{{ review.user?.name || t('doctorProfile.anonymous') }}</h4>
                            <span class="reviewDate">{{ formatDate(review.created_at) }}</span>
                        </div>
                        <div class="reviewRating">
                                  <span v-for="i in 5" :key="i" class="star" :class="{ filled: review.rating >= i }">★</span>
                        </div>
                        <p class="reviewComment" v-if="review.comment">{{ review.comment }}</p>
                    </div>
                </div>
            </div>

          </main>

          <aside class="rightColumn">
            <!-- Schedules -->
            <div class="infoBlock schedulesBlock" v-if="profile.schedules && profile.schedules.length > 0">
                <h2 class="blockTitleSmall">{{ t('doctorProfile.workingHours') }} <span class="highlightText">{{ t('doctorProfile.hours') }}</span></h2>
                <div class="scheduleList">
                    <div class="scheduleItem" v-for="schedule in profile.schedules" :key="schedule.id" :class="{ 'currentDay': isToday(schedule.day_of_week) }">
                        <span class="scheduleDay">{{ t('days.' + getDayName(schedule.day_of_week)) }}</span>
                        <span class="scheduleHours">
                            {{ schedule.start_time.substring(0, 5) }} &mdash; {{ schedule.end_time.substring(0, 5) }}
                        </span>
                        <span v-if="isToday(schedule.day_of_week) && isCurrentlyUnavailable(schedule)" class="currentStatus unavailable">
                            {{ t('search.status.unavailable') }}
                        </span>
                        <span v-else-if="isToday(schedule.day_of_week) && isCurrentlyAvailable(schedule)" class="currentStatus available">
                            {{ t('search.status.available') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="infoBlock bookingSection">
                <h2 class="blockTitleSmall">{{ t('doctorProfile.bookAppointment') }}</h2>

                <div v-if="!user">
                    <p class="authText">{{ t('doctorProfile.pleaseLogin') }} <a href="#" @click.prevent="router.push('/login')">{{ t('doctorProfile.logIn') }}</a> {{ t('doctorProfile.toBookAppointment') }}.</p>
                </div>
                <div v-else-if="user.role !== 'client'">
                    <p class="authText warning">{{ t('doctorProfile.onlyClients') }}.</p>
                </div>
                <div v-else>
                    <!-- Upcoming working dates chips -->
                    <div v-if="upcomingWorkingDates.length > 0" class="workingDateHints">
                        <p class="slotsLabel">{{ t('doctorProfile.next7Days') }}</p>
                        <div class="workingDatesList">
                            <button
                                v-for="dateInfo in upcomingWorkingDates"
                                :key="dateInfo.date"
                                class="workingDateChip"
                                :class="{ 'activeChip': selectedDate === dateInfo.date }"
                                @click="selectedDate = dateInfo.date; selectedTime = ''"
                            >
                                {{ dateInfo.label }}
                            </button>
                        </div>
                    </div>

                    <div class="formGroup">
                        <label>{{ t('doctorProfile.orPickDate') }}</label>
                        <input type="date" class="brutalistInput" v-model="selectedDate" :min="minDate" @change="selectedTime = ''" />
                    </div>

                    <div v-if="selectedDate">
                        <template v-if="!selectedDateHasShift">
                            <p class="unavailableText">{{ t('doctorProfile.notWorkingDay') }}</p>
                        </template>
                        <template v-else-if="availableSlots.length > 0">
                            <label class="slotsLabel">{{ t('doctorProfile.availableSlots') }}</label>
                            <div class="slotsGrid">
                                <button
                                    v-for="slot in availableSlots"
                                    :key="slot.time"
                                    class="slotBtn"
                                    :class="{ 
                                        'activeSlot': selectedTime === slot.time,
                                        'disabledSlot': slot.disabled
                                    }"
                                    :disabled="slot.disabled"
                                    :title="slot.reason"
                                    @click="selectedTime = slot.time"
                                >
                                    {{ slot.time }}
                                    <span v-if="slot.disabled" class="disabledLabel">{{ slot.isBooked ? t('doctorProfile.booked') : t('doctorProfile.unavailable') }}</span>
                                </button>
                            </div>
                            <p v-if="availableSlots.every(s => s.disabled)" class="infoText">
                                {{ t('doctorProfile.alreadyBookedAll') }}
                            </p>
                        </template>
                        <template v-else>
                            <p class="unavailableText">{{ t('doctorProfile.allSlotsBooked') }}.</p>
                        </template>
                    </div>

                    <div v-if="bookingError" class="bookingAlert errorAlert">{{ bookingError }}</div>
                    <div v-if="bookingSuccess" class="bookingAlert successAlert">{{ t('doctorProfile.appointmentBooked') }}</div>

                    <button
                        v-if="selectedDate && selectedDateHasShift && availableSlots.length > 0"
                        class="bookBtn"
                        :disabled="!selectedTime || bookingLoading || availableSlots.find(s => s.time === selectedTime)?.disabled"
                        @click="bookAppointment"
                    >
                        {{ bookingLoading ? t('doctorProfile.booking') : (selectedTime ? t('doctorProfile.confirmFor') + ' ' + selectedTime : t('doctorProfile.selectTime')) }}
                    </button>
                </div>
            </div>
          </aside>

        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.profileSection {
  padding: 120px 0 80px;
  background-color: #f4f4f4;
  background-image: radial-gradient(rgba(0, 0, 0, 0.15) 2px, transparent 0);
  background-size: 24px 24px;
  min-height: calc(100vh - 80px);
  color: #000;
}

.profileContainer {
  /* BIGGER LAYOUT */
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 4%;
}

.stateCard {
  max-width: 600px;
  margin: 100px auto;
  background: #fff;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px #000;
  padding: 60px 40px;
  text-align: center;
  display: flex; flex-direction: column; align-items: center; gap: 20px;
}
.stateCard h2 { font-size: 28px; font-weight: 900; text-transform: uppercase; margin: 0; }
.spinner {
  width: 48px; height: 48px; border: 6px solid #000; border-bottom-color: #F6D506;
  border-radius: 50%; animation: rotation 1s linear infinite;
}
@keyframes rotation { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.backBtn {
  display: inline-flex; align-items: center; gap: 8px;
  background: #000; color: #fff; border: 3px solid #000;
  padding: 10px 20px; font-weight: 900; text-transform: uppercase;
  cursor: pointer; box-shadow: 4px 4px 0px #000; transition: all 0.2s;
  margin-bottom: 24px;
}
.backBtn:hover { background: #fff; color: #000; transform: translate(-2px, -2px); box-shadow: 6px 6px 0px #000; }

/* HUGE HERO SECTION */
.heroCard {
  background: #fff;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px #000;
  margin-bottom: 40px;
  position: relative;
}
.heroBanner {
  height: 200px;
  width: 100%;
  background-size: cover;
  background-position: center;
  border-bottom: 4px solid #000;
  position: relative;
}
.statusBadge {
  position: absolute;
  top: 16px; right: 16px;
  padding: 8px 24px; font-size: 16px; font-weight: 900; text-transform: uppercase;
  border: 4px solid #000; box-shadow: 6px 6px 0px #000;
}
.status-Available { background: #4caf50; color: #fff; }
.status-Busy { background: #ff9800; color: #000; }
.status-Unavailable { background: #fff; color: #000; }
.status-Offline { background: #fff; color: #000; }

.heroContent {
  display: flex;
  align-items: center;
  padding: 30px 40px;
  gap: 40px;
  position: relative;
}
@media (max-width: 900px) {
    .heroContent { flex-direction: column; text-align: center; gap: 20px; margin-top: -80px; }
    .heroBanner { height: 160px; }
}

.avatarWrap { margin-top: -100px; }
@media (max-width: 900px) { .avatarWrap { margin-top: 0; } }

.avatar {
  width: 180px; height: 180px;
  border: 6px solid #000; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 56px; font-weight: 900; color: #fff;
  box-shadow: 8px 8px 0px #000; overflow: hidden; text-shadow: 2px 2px 0 #000;
  background-color: #fff;
}
.avatarImg { width: 100%; height: 100%; object-fit: cover; }

.heroDetails { flex: 1; }
.doctorName {
  font-size: 48px; font-weight: 900; text-transform: uppercase;
  margin: 0 0 16px; line-height: 1; letter-spacing: -2px;
}
.badges { display: flex; gap: 12px; flex-wrap: wrap; }
@media (max-width: 900px) { .badges { justify-content: center; } }
.specialtyBadge {
  background: #F6D506; border: 3px solid #000; padding: 8px 20px;
  font-weight: 900; font-size: 16px; text-transform: uppercase; box-shadow: 4px 4px 0px #000;
}
.categoryBadge {
  background: #fff; border: 3px solid #000; padding: 8px 20px;
  font-weight: 900; font-size: 16px; text-transform: uppercase; box-shadow: 4px 4px 0px #000;
}

.heroMetrics {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.metricBox {
  background: #f8f8f8; border: 3px solid #000; padding: 20px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  box-shadow: 6px 6px 0px #000; min-width: 140px;
}
.metricBox.verifiedBox {
  background: #fff;
}
.metricBox.verifiedBox.verified {
  background: #e8f5e9;
}
.metricValue { 
  font-size: 32px; 
  font-weight: 900; 
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.metricValue svg {
  flex-shrink: 0;
}
.metricLabel { font-size: 14px; font-weight: 800; text-transform: uppercase; color: #555; }

/* Main Content Layout */
.profileLayout {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 40px;
  align-items: start;
}
@media (max-width: 1024px) { .profileLayout { grid-template-columns: 1fr; } }

.leftColumn { display: flex; flex-direction: column; gap: 40px; }
.rightColumn { display: flex; flex-direction: column; gap: 40px; }

.infoBlock {
  background: #fff; border: 4px solid #000; box-shadow: 10px 10px 0px #000; padding: 40px;
}
.blockTitle {
  font-size: 36px; font-weight: 900; text-transform: uppercase; margin: 0 0 24px;
  border-bottom: 4px solid #000; padding-bottom: 16px; letter-spacing: -1px;
}
.blockTitleSmall {
  font-size: 28px; font-weight: 900; text-transform: uppercase; margin: 0 0 24px;
  border-bottom: 4px solid #000; padding-bottom: 12px; letter-spacing: -1px;
}
.highlightText { background: #F6D506; padding: 0 8px; }

.bioText { font-size: 20px; line-height: 1.6; font-weight: 600; color: #222; white-space: pre-line; }

.gridBlock { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; }
@media (max-width: 600px) { .gridBlock { grid-template-columns: 1fr; } }

.infoCard {
  background: #fff; border: 4px solid #000; box-shadow: 6px 6px 0px #000; padding: 24px;
  display: flex; align-items: flex-start; gap: 16px; transition: transform 0.2s;
}
.infoCard:hover { transform: translate(-2px, -2px); box-shadow: 8px 8px 0px #000; }
.iconWrap {
  width: 54px; height: 54px; background: #F6D506; border: 3px solid #000;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cardLabel { font-size: 14px; font-weight: 900; text-transform: uppercase; color: #666; margin: 0 0 6px; }
.cardValue { font-size: 20px; font-weight: 900; color: #000; margin: 0; }
.mapLink {
  display: inline-block;
  margin-top: 8px;
  padding: 6px 12px;
  background: #4285F4;
  color: #fff;
  border: 2px solid #000;
  font-size: 12px;
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
.mapLinkBtn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  padding: 10px 16px;
  background: #4285F4;
  color: #fff;
  border: 3px solid #000;
  font-size: 14px;
  font-weight: 900;
  text-transform: uppercase;
  text-decoration: none;
  box-shadow: 3px 3px 0px #000;
  transition: all 0.2s;
}
.mapLinkBtn:hover {
  background: #357ae8;
  transform: translate(-2px, -2px);
  box-shadow: 5px 5px 0px #000;
}

/* Reviews */
.reviewsBlock { padding-bottom: 20px; }
.noReviews { font-size: 18px; font-weight: 700; color: #666; }
.reviewsList { display: flex; flex-direction: column; gap: 24px; }
.reviewItem {
    border: 3px solid #000; padding: 20px; background: #fdfdfd; box-shadow: 4px 4px 0px #000;
}
.reviewHeader { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.reviewerName { font-size: 20px; font-weight: 900; text-transform: uppercase; margin: 0; }
.reviewDate { font-size: 14px; font-weight: 700; color: #666; }
.reviewRating { margin-bottom: 12px; }
.star { font-size: 24px; color: #ddd; }
.star.filled { color: #F6D506; text-shadow: 1px 1px 0 #000; }
.reviewComment { font-size: 18px; font-weight: 600; line-height: 1.5; margin: 0; }

/* Schedules */
.schedulesBlock { padding: 30px; }
.scheduleList { display: flex; flex-direction: column; gap: 12px; }
.scheduleItem {
  background: #fdfdfd; border: 3px solid #000; padding: 12px 16px;
  display: flex; justify-content: space-between; align-items: center;
  box-shadow: 4px 4px 0px #000; transition: transform 0.2s;
  flex-wrap: wrap;
  gap: 8px;
}
.scheduleItem.currentDay {
  background: #fff9e6;
  border-color: #F6D506;
}
.scheduleItem:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0px #000; }
.scheduleDay { font-weight: 900; font-size: 18px; text-transform: uppercase; }
.scheduleHours { font-weight: 800; font-size: 16px; color: #000; }
.currentStatus {
  font-size: 12px;
  font-weight: 900;
  text-transform: uppercase;
  padding: 4px 12px;
  border: 2px solid #000;
  box-shadow: 2px 2px 0px #000;
}
.currentStatus.available {
  background: #4caf50;
  color: #fff;
}
.currentStatus.unavailable {
  background: #9e9e9e;
  color: #fff;
}

/* Booking Section */
.bookingSection { padding: 30px; }
.authText {
    text-align: center; font-weight: 800; font-size: 15px; border: 3px dashed #000;
    padding: 16px; background: #f8f8f8;
}
.authText a { color: #F6D506; background: #000; padding: 2px 8px; transition: all 0.2s; }
.authText a:hover { color: #000; background: #F6D506; }
.authText.warning { background: #ff5252; color: #fff; border-style: solid; }

.formGroup { margin-bottom: 20px; }
.formGroup label { display: block; font-weight: 900; text-transform: uppercase; font-size: 16px; margin-bottom: 10px; }
.brutalistInput {
    width: 100%; padding: 16px; border: 3px solid #000; font-weight: 700; font-size: 18px;
    font-family: inherit; box-shadow: 4px 4px 0px #000; outline: none; transition: transform 0.1s;
}
.brutalistInput:focus { transform: translate(2px, 2px); box-shadow: 2px 2px 0px #000; }

.slotsLabel { display: block; font-weight: 900; text-transform: uppercase; font-size: 16px; margin-bottom: 12px; }
.slotsGrid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
.slotBtn {
    padding: 12px 0; border: 3px solid #000; background: #fff; font-weight: 900; font-size: 16px;
    cursor: pointer; box-shadow: 2px 2px 0px #000; transition: all 0.1s;
    position: relative;
}
.slotBtn:hover:not(:disabled) { background: #e0e0e0; }
.slotBtn.activeSlot { background: #F6D506; transform: translate(2px, 2px); box-shadow: 0px 0px 0px #000; }
.slotBtn:disabled,
.slotBtn.disabledSlot {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
    opacity: 0.6;
    border-color: #ccc;
}
.disabledLabel {
    display: block;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    color: #ff5252;
    margin-top: 2px;
}
.unavailableText { font-weight: 800; font-size: 16px; color: #ff5252; margin-bottom: 24px; }
.infoText { 
    font-weight: 700; 
    font-size: 14px; 
    color: #666; 
    margin-top: 12px; 
    padding: 12px; 
    background: #fff3cd; 
    border: 2px solid #000; 
    text-align: center;
}

.bookingAlert { font-weight: 900; font-size: 14px; text-transform: uppercase; padding: 12px; border: 3px solid #000; margin-bottom: 16px; text-align: center; }
.errorAlert { background: #ff5252; color: #fff; }
.successAlert { background: #4caf50; color: #fff; }

.bookBtn {
  width: 100%; padding: 20px; background: #000; color: #F6D506; border: 4px solid #000;
  font-size: 20px; font-weight: 900; text-transform: uppercase; cursor: pointer;
  box-shadow: 6px 6px 0px #000; transition: all 0.2s;
}
.bookBtn:hover:not(:disabled) {
  background: #fff; color: #000; transform: translate(-4px, -4px); box-shadow: 10px 10px 0px #000;
}
.bookBtn:disabled { background: #333; color: #888; cursor: not-allowed; box-shadow: none; }

/* Working Date Chips */
.workingDateHints {
  margin-bottom: 20px;
}
.workingDatesList {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}
.workingDateChip {
  padding: 8px 14px;
  border: 3px solid #000;
  background: #fff;
  font-weight: 900;
  font-size: 13px;
  text-transform: uppercase;
  cursor: pointer;
  box-shadow: 3px 3px 0px #000;
  transition: all 0.15s;
  letter-spacing: 0.3px;
}
.workingDateChip:hover {
  background: #f0f0f0;
  transform: translate(-1px, -1px);
  box-shadow: 4px 4px;
}
</style>
