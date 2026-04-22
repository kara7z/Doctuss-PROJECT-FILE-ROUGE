<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const router = useRouter()

const isMenuOpen = ref(false)
const route = useRoute()

watch(route, () => {
  isMenuOpen.value = false
})

const { user, fetchUser, logout } = useAuth()

onMounted(() => {
  fetchUser()
})

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const scrollToContact = () => {
  if (route.path !== '/') {
    router.push('/')
    setTimeout(() => {
      const contactSection = document.querySelector('.contactSection')
      if (contactSection) {
        contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }, 300)
  } else {
    const contactSection = document.querySelector('.contactSection')
    if (contactSection) {
      contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
  isMenuOpen.value = false
}

const goToSearch = () => {
  router.push('/search')
  isMenuOpen.value = false
}

const pageName = computed(() => {
  const name = route.name
  if (!name) return 'home'
  return name.toString().charAt(0).toUpperCase() + name.toString().slice(1)
})

const fullName = computed(() => user.value?.name ?? null)
const avatarLetter = computed(() => user.value?.name?.[0]?.toUpperCase() ?? '?')
</script>

<template>
  <nav class="nav">
    <router-link to="/">{{ t('nav.brand') }}</router-link>

    <ul class="nav-links" :class="{ active: isMenuOpen }">
      <li class="dropdown">
        <span class="navCurrentPage">{{ pageName }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" class="dropdownChevron" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        <ul class="dropdown-menu">
          <li><router-link to="/">{{ t('nav.pages.home') }}</router-link></li>
          <li><a @click="goToSearch" style="cursor: pointer;">{{ t('nav.pages.services') }}</a></li>
          <li><a @click="scrollToContact" style="cursor: pointer;">{{ t('nav.pages.contact') }}</a></li>
          <li v-if="user && user.role === 'client'"><router-link to="/appointments">{{ t('nav.pages.appointments') }}</router-link></li>
          <li v-if="user && user.role === 'doctor'"><router-link to="/doctor-dashboard">{{ t('nav.pages.dashboard') }}</router-link></li>
          <li v-if="user && user.role === 'doctor'"><router-link to="/my-profile">{{ t('nav.pages.myProfile') }}</router-link></li>
          <li v-if="user && user.role === 'admin'"><router-link to="/admin/dashboard">{{ t('nav.pages.dashboard') }}</router-link></li>
        </ul>
      </li>

      <!-- Mobile auth -->
      <li class="mobile-login">
        <button v-if="fullName" type="button" class="userBtn" @click="logout">
          {{ fullName }}
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
        <router-link v-else to="/login">{{ t('nav.login') }}</router-link>
      </li>
    </ul>

    <!-- Desktop auth -->
    <template v-if="fullName">
      <div class="userMenu">
        <button type="button" class="userBtn" @click="logout">
          <span class="userAvatar">{{ avatarLetter }}</span>
          {{ fullName }}
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </template>
    <router-link v-else to="/login" class="desktop-login navAuthBtn">{{ t('nav.login') }}</router-link>

    <button type="button" class="burger" @click="toggleMenu" :class="{ active: isMenuOpen }">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </nav>
</template>

<style>
a {
  color: black;
  text-decoration: none;
}

.nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1000;
  display: grid;
  grid-template-columns: auto 1fr auto auto;
  align-items: center;
  gap: 20px;
  background-color: #F6D506;
  margin: 0 20px;
  padding: 6px 42px;
  font-size: 36px;
  border-radius: 0 0 24px 24px;
  border: black 3px solid;
  color: black;
  box-shadow: rgba(0, 0, 0, 0.25) 0px 4px 4px;
.nav > a {
  font-weight: 900;
  letter-spacing: -1px;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), text-shadow 0.2s ease;
}
}
.nav > a:hover {
  transform: translate(-2px, -2px) scale(1.02);
  text-shadow: 4px 4px 0px rgba(0,0,0,1);
}

.nav-links {
  display: flex;
  list-style: none;
  justify-self: center;
  margin: 0;
  padding: 0;
}


.navAuthBtn {
  display: inline-flex;
  align-items: center;
  background: #000;
  color: #F6D506;
  border: 3px solid #000;
  border-radius: 50px;
  padding: 6px 20px;
  font-size: 18px;
  font-weight: 800;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  white-space: nowrap;
}
.navAuthBtn:hover {
  background: #fff;
  color: #000;
  transform: translate(-3px, -3px);
  box-shadow: 6px 6px 0px rgba(0,0,0,1);
}

.userMenu { display: flex; align-items: center; }
.userBtn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #000;
  color: #F6D506;
  border: 3px solid #000;
  border-radius: 50px;
  padding: 6px 16px 6px 6px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  font-family: inherit;
  white-space: nowrap;
}
.userBtn:hover {
  background: #fff;
  color: #000;
  transform: translate(-3px, -3px);
  box-shadow: 6px 6px 0px rgba(0,0,0,1);
}
.userBtn:hover .userAvatar {
  background: #000;
  color: #F6D506;
}
.userAvatar {
  width: 28px; height: 28px;
  background: #F6D506;
  color: #000;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 900;
  flex-shrink: 0;
  transition: all 0.2s;
}

.burger { display: none; }
.mobile-login { display: none; }

.dropdown { 
  position: relative; 
  cursor: pointer; 
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  transition: color 0.2s ease;
}
.dropdown:hover { color: rgba(0,0,0,0.6); }
.dropdownChevron { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown:hover .dropdownChevron { transform: rotate(180deg); }

.dropdown-menu {
  position: fixed;
  top: 60px; left: 50%;
  transform: translateX(-50%) translateY(-15px) scale(0.95);
  background-color: #fff;
  border: black 3px solid;
  border-top: none;
  border-radius: 0 0 16px 16px;
  padding: 10px;
  list-style: none;
  box-shadow: 0px 8px 0px rgba(0, 0, 0, 1);
  display: flex; flex-direction: row; gap: 10px;
  opacity: 0; visibility: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  transform-origin: top center;
}
.dropdown:hover .dropdown-menu { 
  opacity: 1; 
  visibility: visible; 
  transform: translateX(-50%) translateY(0) scale(1);
}
.dropdown-menu li a { 
  padding: 10px 20px; 
  font-size: 20px; 
  font-weight: 800;
  display: block;
  border-radius: 8px;
  transition: all 0.15s ease;
}
.dropdown-menu li:hover a { 
  background-color: #F6D506; 
  transform: translateY(-2px);
  color: #000;
}

@media (max-width: 768px) {
  .nav {
    grid-template-columns: auto 1fr auto;
    padding: 6px 20px;
    font-size: 28px;
  }
  .desktop-login,
  .userMenu { display: none; }
  .mobile-login { display: block; font-size: 20px; padding: 10px; }
  .mobile-login .userBtn { font-size: 14px; padding: 5px 12px 5px 5px; }
  .mobile-login .userAvatar { width: 24px; height: 24px; font-size: 11px; }

  .burger {
    display: flex; flex-direction: column;
    background: none; border: none; cursor: pointer;
    padding: 0; gap: 5px; justify-self: end;
  }
  .burger span {
    width: 30px; height: 3px;
    background-color: black;
    transition: all 0.3s ease;
  }
  .burger.active span:nth-child(1) { transform: rotate(45deg) translate(8px, 8px); }
  .burger.active span:nth-child(2) { opacity: 0; }
  .burger.active span:nth-child(3) { transform: rotate(-45deg) translate(8px, -8px); }

  .nav-links {
    position: fixed;
    top: 0; left: 0;
    width: calc(100% - 40px);
    flex-direction: column;
    background-color: #F6D506;
    border: black 3px solid;
    border-top: none;
    border-radius: 0 0 20px 20px;
    padding: 80px 20px 20px 20px;
    gap: 20px;
    transform: translateY(-120%); /* HW-accelerated off-screen */
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); /* Premium, fluid ease-out */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
    margin: 0 20px;
    z-index: -1;
  }
  .nav-links.active { transform: translateY(0); }
  .dropdown-menu {
    position: static; transform: none;
    border: none; box-shadow: none;
    padding: 10px 0; margin-top: 10px;
    flex-direction: column;
    opacity: 1; visibility: visible;
  }
}
</style>
