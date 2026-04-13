import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import RegisterVue from '../views/Auth/RegisterVue.vue';
import LoginVue from '../views/Auth/LoginVue.vue';

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
      component: RegisterVue,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginVue,
    },

  ],
})

export default router
