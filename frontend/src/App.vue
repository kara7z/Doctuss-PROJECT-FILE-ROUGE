<script setup>
import { RouterView } from 'vue-router'
import Footer from './components/Footer.vue';
import Navbar from './components/Navbar.vue';

</script>

<template>
  <Navbar/>
  <RouterView />
  <Footer/>
</template>

<style>
  *{
    padding:0;
    margin:0;
    box-sizing: none;
    text-decoration: none;
    list-style: none;
  }
  body{
    background-color: #042464;
    color: white;
  }
  
</style>
