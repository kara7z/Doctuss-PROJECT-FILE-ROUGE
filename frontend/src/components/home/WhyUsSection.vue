<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const $router = useRouter()
const { t } = useI18n()

const reasons = computed(() => [
  t('whyUs.reasons[0]'),
  t('whyUs.reasons[1]'),
  t('whyUs.reasons[2]'),
  t('whyUs.reasons[3]')
])
</script>

<template>
  <section class="section">
    <div class="sectionL">{{ t('whyUs.title') }}</div>
    <div class="sectionB">
      <ul>
        <li v-for="(reason, index) in reasons" :key="index">{{ reason }}</li>
      </ul>
    </div>
  </section>
  <button class="explore-btn" @click="$router.push('/search')">
    <span>{{ t('whyUs.exploreButton') }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
  </button>
</template>

<style scoped>
.explore-btn{
  width: 100%;
  padding: 1.2rem;
  background-color: #F6D506;
  border: black 4px solid;
  font-size: 28px;
  font-weight: 900;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 1px;
  box-shadow: 8px 8px 0px #000;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.explore-btn:hover{
  background-color: #000;
  color: #F6D506;
  transform: translate(-4px, -4px);
  box-shadow: 12px 12px 0px #F6D506;
}
.explore-btn svg {
  transition: transform 0.2s;
}
.explore-btn:hover svg {
  transform: translateX(6px);
}
.section {
  display: flex;
  align-items: stretch;
  padding: 64px 6%;
  gap: 48px;
  background: black;
  border-top: 3px solid black;
  border-bottom: 3px solid black;
}
.sectionL {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 200px;
  font-size: 36px;
  font-weight: 900;
  letter-spacing: 2px;
  text-transform: uppercase;
  writing-mode: vertical-rl;
  text-orientation: mixed;
  transform: rotate(180deg);
  border-right: 3px solid #F6D506;
  padding-right: 32px;
  user-select: none;
  color: #F6D506;
}
.sectionB {
  user-select: none;
  flex: 1;
  min-width: 0;
  width: 100%;
  display: flex;
  justify-content: center;
}
.sectionB li {
  font-size: 4rem;
  font-weight: bold;
}

@media (max-width: 768px) {
  .section { flex-direction: column; padding: 40px 5%; gap: 28px; }
  .sectionL {
    writing-mode: horizontal-tb;
    transform: none;
    border-right: none;
    border-bottom: 3px solid #F6D506;
    padding-right: 0;
    padding-bottom: 20px;
    font-size: 26px;
    min-width: unset;
  }
  .sectionB { width: 100%; }
  .sectionB li { font-size: 2rem; }
}
@media (max-width: 480px) {
  .sectionL { font-size: 20px; }
  .sectionB li { font-size: 1.5rem; }
}
</style>
