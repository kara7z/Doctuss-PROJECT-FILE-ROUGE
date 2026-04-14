<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

const isMenuOpen = ref(false)
const route = useRoute()

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const pageName = computed(() => {
  const name = route.name
  if (!name) return 'home'
  return name.toString().charAt(0).toUpperCase() + name.toString().slice(1)
})
</script>
<template>
   <nav class="nav">
    <router-link to="/">doctuss</router-link>
    
    <ul class="nav-links" :class="{ active: isMenuOpen }">
      <li class="dropdown">
        {{ pageName }}
        <ul class="dropdown-menu">
          <li><router-link to="/about">About</router-link></li>
          <li><router-link to="/services">Services</router-link></li>
          <li><router-link to="/contact">Contact</router-link></li>
        </ul>
      </li>
      <li class="mobile-login"><router-link to="/login">login</router-link></li>
    </ul>

    <router-link to="/login" class="desktop-login">login</router-link>
    
    <button class="burger" @click="toggleMenu" :class="{ active: isMenuOpen }">
      <span></span>
      <span></span>
      <span></span>
    </button>
   </nav>
    
</template>
<style>
a{
    color:black;
    text-decoration: none;
}

.nav{
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
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
}



.nav-links {
    display: flex;
    list-style: none;
    justify-self: center;
    margin: 0;
    padding: 0;
}

.burger {
    display: none;
}

.mobile-login {
    display: none;
}

.dropdown {
    position: relative;
    cursor: pointer;
}

.dropdown-menu {
    position: fixed;
    top: 60px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #F6D506;
    border: black 3px solid;
    border-top: none;
    border-radius: 0 0 10px 10px;
    padding: 10px;
    list-style: none;
    box-shadow: rgba(0, 0, 0, 0.25) 0px 4px 4px;
    gap: 20px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s;
    display: flex;
    flex-direction: row;
}

.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
}

.dropdown-menu li {
    padding: 10px;
    font-size: 24px;
}

.dropdown-menu li:hover {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 5px;
}

@media (max-width: 768px) {
    .nav {
        grid-template-columns: auto 1fr auto;
        padding: 6px 20px;
        font-size: 28px;
    }

    .desktop-login {
        display: none;
    }

    .mobile-login {
        display: block;
        font-size: 24px;
        padding: 10px;
    }

    .burger {
        display: flex;
        flex-direction: column;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        gap: 5px;
        justify-self: end;
    }

    .burger span {
        width: 30px;
        height: 3px;
        background-color: black;
        transition: all 0.3s ease;
    }

    .burger.active span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
    }

    .burger.active span:nth-child(2) {
        opacity: 0;
    }

    .burger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(8px, -8px);
    }

    .nav-links {
        position: fixed;
        top: 60px;
        left: -100%;
        width: calc(100% - 40px);
        flex-direction: column;
        background-color: #F6D506;
        border: black 3px solid;
        border-top: none;
        border-radius: 0 0 20px 20px;
        padding: 20px;
        gap: 20px;
        transition: left 0.3s ease;
        box-shadow: rgba(0, 0, 0, 0.25) 0px 4px 4px;
        margin: 0 20px;
    }

    .nav-links.active {
        left: 0;
    }

    .dropdown-menu {
        position: static;
        transform: none;
        border: none;
        box-shadow: none;
        padding: 10px 0;
        margin-top: 10px;
        flex-direction: column;
        opacity: 1;
        visibility: visible;
    }
}
</style>