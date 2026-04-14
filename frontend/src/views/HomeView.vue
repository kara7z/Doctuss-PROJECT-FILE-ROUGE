<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const messages = [
  'Connect with trusted healthcare professionals near you',
  'Your health journey starts with finding the right doctor',
  'Discover experienced doctors and specialists in your area',
  'Book appointments with top-rated healthcare providers',
  'Find the perfect doctor for you and your family',
  'Access quality healthcare from certified medical professionals',
  'Search thousands of doctors across all specialties',
  'Get connected to the best medical care in your community',
  'Expert medical care is just a search away',
  'Find doctors who accept your insurance and fit your schedule',
  'Trusted healthcare professionals ready to serve you',
  'Your wellness matters - find the right doctor today',
  'Connecting patients with compassionate healthcare providers',
  'Quality medical care tailored to your needs',
  'Browse verified doctors and read real patient reviews',
  'Schedule appointments with specialists near you instantly',
  'Find doctors by specialty, location, or availability',
  'Healthcare made simple - search, compare, and book',
  'Meet doctors who truly care about your health',
  'From routine checkups to specialized care, we connect you',
  'Discover healthcare providers who speak your language',
  'Find pediatricians, dentists, therapists, and more',
  'Your trusted partner in finding quality medical care',
  'Compare doctors, read reviews, and make informed choices',
  'Access telehealth and in-person appointments easily',
  'Find doctors accepting new patients in your area',
  'Expert care for every stage of your life',
  'Connect with doctors who understand your unique needs',
  'Quality healthcare starts with the right provider',
  'Search by symptoms, conditions, or medical specialties'
]

const currentMessage = ref('')
const searchInput = ref(null)

const handleSlash = (e) => {
  if (e.key === '/' && document.activeElement !== searchInput.value) {
    e.preventDefault()
    searchInput.value.focus()
  }
}

onMounted(() => {
  const randomIndex = Math.floor(Math.random() * messages.length)
  currentMessage.value = messages[randomIndex]
  window.addEventListener('keydown', handleSlash)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleSlash)
})
</script>
<template>
  <section class="heroSection">
    <div class="heroContent">
      <div class="titleWrapper">
        <h1>
          <span class="word" v-for="(word, index) in ['Find', 'Your', 'Doctor']" :key="index" :style="{animationDelay: `${index * 0.1}s`}">
            {{ word }}
          </span>
        </h1>
      </div>
      <p class="subtitle">{{ currentMessage }}</p>
      <div class="searchBar">
        <input ref="searchInput" type="text" placeholder="Search for doctors, specialties, or conditions..." />
        <button>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </button>
      </div>
    </div>
  </section>
  
  <div>

  </div>
</template>
<style>
.heroSection{
    width: 100%;
    height: 100vh;
    background-image: url('@/assets/pictures/medical1.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}
.heroContent{
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 30px;
    position: relative;
    z-index: 10;
    animation: fadeInUp 1s ease-out;
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.1);
    padding: 60px 40px;
    border-radius: 30px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 
        0 20px 60px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.titleWrapper{
    perspective: 1000px;
}
.heroSection h1{
    color: white;
    font-size: 82px;
    margin: 0;
    font-weight: 900;
    text-shadow: 
        2px 2px 0px rgba(0, 0, 0, 0.3),
        4px 4px 0px rgba(0, 0, 0, 0.25),
        6px 6px 0px rgba(0, 0, 0, 0.2),
        8px 8px 20px rgba(0, 0, 0, 0.4),
        0 0 40px rgba(246, 213, 6, 0.5),
        0 0 80px rgba(246, 213, 6, 0.3);
    letter-spacing: 3px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}
.word{
    display: inline-block;
    animation: wordPop 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) backwards;
    position: relative;
}
.word::after{
    content: attr(data-text);
    position: absolute;
    left: 0;
    top: 0;
    z-index: -1;
    filter: blur(10px);
    opacity: 0.7;
}
@keyframes wordPop {
    0% {
        transform: translateY(100px) rotateX(-90deg);
        opacity: 0;
    }
    100% {
        transform: translateY(0) rotateX(0deg);
        opacity: 1;
    }
}
.subtitle{
    color: white;
    font-size: 26px;
    margin: 0;
    text-shadow: 
        1px 1px 0px rgba(0, 0, 0, 0.4),
        2px 2px 0px rgba(0, 0, 0, 0.3),
        3px 3px 0px rgba(0, 0, 0, 0.2),
        4px 4px 15px rgba(0, 0, 0, 0.4),
        0 0 30px rgba(0, 0, 0, 0.2);
    animation: fadeInUp 1s ease-out 0.3s backwards;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-align: center;
    max-width: 700px;
}
.searchBar{
    display: flex;
    align-items: center;
    width: 600px;
    max-width: 90%;
    background: linear-gradient(135deg, #F6D506 0%, #FFE55C 100%);
    border-radius: 50px;
    padding: 8px 8px 8px 24px;
    border: 3px solid black;
    box-shadow: 
        rgba(0, 0, 0, 0.25) 0px 4px 4px,
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    position: relative;
    animation: fadeInUp 1s ease-out 0.4s backwards;
}
.searchBar::before{
    content: '';
    position: absolute;
    inset: -6px;
    border-radius: 50px;
    background: linear-gradient(45deg, #F6D506, #FFE55C, #F6D506, #FFE55C);
    background-size: 300% 300%;
    opacity: 0;
    transition: opacity 0.5s;
    animation: borderGlow 3s ease infinite;
    z-index: -1;
    filter: blur(8px);
}
@keyframes borderGlow {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.searchBar:hover::before{
    opacity: 0.6;
}
.searchBar:hover{
    transform: translateY(-8px) scale(1.03);
    box-shadow: 
        rgba(0, 0, 0, 0.5) 0px 20px 40px,
        rgba(246, 213, 6, 0.6) 0px 0px 40px,
        inset 0 1px 0 rgba(255, 255, 255, 0.5),
        inset 0 -1px 0 rgba(0, 0, 0, 0.2);
    border-color: #000;
}
.searchBar:focus-within{
    transform: translateY(-8px) scale(1.03);
    box-shadow: 
        rgba(0, 0, 0, 0.5) 0px 20px 40px,
        rgba(246, 213, 6, 0.9) 0px 0px 60px,
        rgba(246, 213, 6, 0.5) 0px 0px 100px,
        inset 0 1px 0 rgba(255, 255, 255, 0.5),
        inset 0 -1px 0 rgba(0, 0, 0, 0.2);
    animation: focusPulse 2s ease-in-out infinite;
}
.searchBar:focus-within::before{
    opacity: 0.8;
    animation: borderGlow 1.5s ease infinite;
}
@keyframes focusPulse {
    0%, 100% {
        box-shadow: 
            rgba(0, 0, 0, 0.5) 0px 20px 40px,
            rgba(246, 213, 6, 0.9) 0px 0px 60px,
            rgba(246, 213, 6, 0.5) 0px 0px 100px,
            inset 0 1px 0 rgba(255, 255, 255, 0.5),
            inset 0 -1px 0 rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow: 
            rgba(0, 0, 0, 0.5) 0px 20px 40px,
            rgba(246, 213, 6, 1) 0px 0px 80px,
            rgba(246, 213, 6, 0.7) 0px 0px 120px,
            inset 0 1px 0 rgba(255, 255, 255, 0.5),
            inset 0 -1px 0 rgba(0, 0, 0, 0.2);
    }
}
.searchBar input{
    flex: 1;
    padding: 12px 16px;
    font-size: 16px;
    border: none;
    background: transparent;
    outline: none;
    color: black;
    position: relative;
    z-index: 1;
    font-weight: 500;
}
.searchBar input::placeholder{
    color: rgba(0, 0, 0, 0.5);
    transition: color 0.3s;
}
.searchBar:hover input::placeholder{
    color: rgba(0, 0, 0, 0.7);
}
.searchBar button{
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #000 0%, #333 100%);
    color: #F6D506;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    position: relative;
    z-index: 1;
    box-shadow: 
        0 4px 15px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}
.searchBar button::before{
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.3), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.searchBar button:hover::before{
    opacity: 1;
}
.searchBar button:hover{
    background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
    transform: rotate(360deg) scale(1.2);
    box-shadow: 
        0 8px 25px rgba(0, 0, 0, 0.5),
        0 0 30px rgba(246, 213, 6, 0.6),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}
.searchBar button:active{
    transform: rotate(360deg) scale(0.95);
    box-shadow: 
        0 2px 10px rgba(0, 0, 0, 0.4),
        inset 0 2px 5px rgba(0, 0, 0, 0.3);
}
@media (max-width: 768px) {
    .heroContent {
        padding: 40px 24px;
        margin: 0 16px;
        gap: 20px;
    }
    .heroSection h1 {
        font-size: 52px;
        gap: 12px;
    }
    .subtitle {
        font-size: 18px;
    }
    .searchBar {
        width: 100%;
        padding: 6px 6px 6px 18px;
    }
    .searchBar input {
        font-size: 14px;
        padding: 10px 10px;
    }
    .searchBar button {
        width: 40px;
        height: 40px;
    }
}
@media (max-width: 480px) {
    .heroContent {
        padding: 30px 16px;
        margin: 0 12px;
    }
    .heroSection h1 {
        font-size: 38px;
        gap: 8px;
    }
    .subtitle {
        font-size: 15px;
    }
    .searchBar {
        width: 100%;
        padding: 5px 5px 5px 14px;
    }
    .searchBar input {
        font-size: 13px;
        padding: 8px 8px;
    }
    .searchBar input::placeholder {
        font-size: 12px;
    }
    .searchBar button {
        width: 36px;
        height: 36px;
    }
}
</style>
