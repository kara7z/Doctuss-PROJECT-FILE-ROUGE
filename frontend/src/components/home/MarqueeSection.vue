<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const marqueeItems = computed(() => [
  t('marquee.items[0]'),
  t('marquee.items[1]'),
  t('marquee.items[2]'),
  t('marquee.items[3]'),
  t('marquee.items[4]')
])
</script>

<template>
  <div class="marqueeWrap">
    <div class="marqueeContent">
      <span class="marqueeItem" v-for="(item, index) in [...marqueeItems, ...marqueeItems]" :key="index">⚕️ {{ item }}</span>
    </div>
  </div>
</template>

<style scoped>
.marqueeWrap {
  width: 100%;
  background: #F6D506;
  border-top: 5px solid #000;
  border-bottom: 5px solid #000;
  padding: 16px 0;
  overflow: hidden;
  position: relative;
  display: flex;
  white-space: nowrap;
  transform: rotate(-2deg);
  margin-top: 80px;
  margin-bottom: 80px;
  box-shadow: 0 10px 0 rgba(0,0,0,1);
  z-index: 10;
}

.marqueeContent {
  display: flex;
  animation: scrollText 20s linear infinite;
}

.marqueeItem {
  font-size: 24px;
  font-weight: 900;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 2px;
  padding: 0 40px;
  display: inline-block;
}

@keyframes scrollText {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

@media (max-width: 768px) {
  .marqueeItem { font-size: 18px; padding: 0 20px; }
  .marqueeWrap { transform: rotate(-1.5deg); margin-top: 60px; margin-bottom: 60px; }
}
</style>
