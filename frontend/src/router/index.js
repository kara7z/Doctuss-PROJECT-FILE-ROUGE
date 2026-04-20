import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import SearchView from '../views/SearchView.vue'
import RegisterView from '../views/Auth/RegisterView.vue';
import LoginView from '../views/Auth/LoginView.vue';
import NotFoundView from '../views/Errors/NotFoundView.vue';
import ForbiddenView from '../views/Errors/ForbiddenView.vue';
import ServerErrorView from '../views/Errors/ServerErrorView.vue';
import DoctorProfileView from '../views/Doctor/ProfileView.vue';
import MyAppointmentsView from '../views/MyAppointmentsView.vue';
import DoctorDashboardView from '../views/Doctor/DashboardView.vue';
import DoctorMyProfileView from '../views/Doctor/MyProfileView.vue';
import AdminDashboardView from '../views/Admin/DashboardView.vue';
import AdminVerificationRequestsView from '../views/Admin/VerificationRequestsView.vue';
import AdminUsersView from '../views/Admin/UsersView.vue';
import AdminReviewsView from '../views/Admin/ReviewsView.vue';
import { useAuth } from '@/composables/useAuth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/search',
      name: 'search',
      component: SearchView,
    },
    {
      path: '/appointments',
      name: 'appointments',
      component: MyAppointmentsView,
      meta: { requiresAuth: true },
    },
    {
      path: '/doctor-dashboard',
      name: 'doctor-dashboard',
      component: DoctorDashboardView,
      meta: { requiresAuth: true, requiresDoctor: true },
    },
    {
      path: '/my-profile',
      name: 'my-profile',
      component: DoctorMyProfileView,
      meta: { requiresAuth: true, requiresDoctor: true },
    },
    {
      path: '/admin/dashboard',
      name: 'admin-dashboard',
      component: AdminDashboardView,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/verification-requests',
      name: 'admin-verification-requests',
      component: AdminVerificationRequestsView,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      component: AdminUsersView,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/reviews',
      name: 'admin-reviews',
      component: AdminReviewsView,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/doctor/:id',
      name: 'doctor-profile',
      component: DoctorProfileView,
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guestOnly: true },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/403',
      name: 'forbidden',
      component: ForbiddenView,
    },
    {
      path: '/404',
      name: 'not-found',
      component: NotFoundView,
    },
    {
      path: '/500',
      name: 'server-error',
      component: ServerErrorView,
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'catch-all',
      redirect: '/404',
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const { user, ready, fetchUser } = useAuth()
  
  if (!ready.value) {
    await fetchUser()
  }

  const suspendedMsg = localStorage.getItem('suspendedMessage')
  if (suspendedMsg && to.path !== '/login') {
    next('/login')
    return
  }

  if (to.meta.guestOnly && user.value) {
    next('/')
  } else if (to.meta.requiresAuth && !user.value) {
    next('/login')
  } else if (to.meta.requiresDoctor && user.value?.role !== 'doctor') {
    next('/403')
  } else if (to.meta.requiresAdmin && user.value?.role !== 'admin') {
    next('/403')
  } else {
    next()
  }
})

export default router
