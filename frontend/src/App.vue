<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Footer from './components/Footer.vue';
import Navbar from './components/Navbar.vue';
import { useAuth } from '@/composables/useAuth';

const { ready, fetchUser } = useAuth();

onMounted(() => {
  fetchUser();
})
</script>

<template>
  <div v-if="!ready" class="global-loader">
    <div class="loader-spinner"></div>
    <div class="logo-text">doctuss</div>
  </div>

  <template v-else>
    <Navbar/>
    <RouterView />
    <Footer/>
  </template>
</template>

<style>
  *{
    padding:0;
    margin:0;
    box-sizing: border-box; 
    text-decoration: none;
    list-style: none;
  }
  body{
    background-color: #042464;
    color: white;
  }

  
  .global-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    background-color: #0a0a0a;
    gap: 20px;
  }

  .loader-spinner {
    width: 48px;
    height: 48px;
    border: 5px solid rgba(246, 213, 6, 0.15);
    border-top-color: #F6D506;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  .logo-text {
    font-size: 28px;
    font-weight: 900;
    color: #F6D506;
    letter-spacing: -1px;
    font-family: inherit;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>
