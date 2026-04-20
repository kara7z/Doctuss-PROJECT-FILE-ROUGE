<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Footer from './components/Footer.vue';
import Navbar from './components/Navbar.vue';
import { useAuth } from '@/composables/useAuth';
import { useLanguage } from '@/composables/useLanguage';

const { ready, fetchUser } = useAuth();
const { initLanguage } = useLanguage();

onMounted(() => {
  fetchUser();
  initLanguage();
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
  html{
    overflow: hidden;
    width: 100%;
    height: 100%;
  }
  body{
    overflow: hidden;
    width: 100%;
    height: 100%;
    background-color: #042464;
    color: white;
    position: fixed;
  }
  
  #app {
    width: 100%;
    height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .pageLayout {
    overflow-x: hidden;
    width: 100%;
    max-width: 100vw;
    min-height: 100vh;
  }
  
  * {
    overscroll-behavior-x: none;
  }
  
  html, body, #app {
    overscroll-behavior-x: none;
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
