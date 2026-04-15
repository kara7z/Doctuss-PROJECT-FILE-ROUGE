import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import RegisterView from '../views/Auth/RegisterView.vue';
import LoginView from '../views/Auth/LoginView.vue';
import NotFoundView from '../views/Errors/NotFoundView.vue';
import ForbiddenView from '../views/Errors/ForbiddenView.vue';
import ServerErrorView from '../views/Errors/ServerErrorView.vue';
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

  if (to.meta.guestOnly && user.value) {
    next('/')
  } else {
    next()
  }
})

export default router
