<script setup>
</script>

<template>
  <div class="errorPage">
    <div class="errorCard">
      <router-link to="/" class="logo">doctuss</router-link>

      <div class="cardHeader">
        <div class="errorCode">500</div>
        <h1 class="cardTitle">Server Error</h1>
        <p class="cardSub">Something went wrong on our end. Please try again later.</p>
      </div>

      <router-link to="/" class="actionBtn">Return Home</router-link>
    </div>
  </div>
</template>

<style scoped>
.errorPage {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  overflow: hidden;
}
.errorPage::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('@/assets/pictures/medical1.jpg');
  background-size: cover;
  background-position: center;
  animation: zoomBg 12s ease-in-out infinite alternate;
  z-index: 0;
}
.errorPage::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 0;
}
@keyframes zoomBg {
  from { transform: scale(1); }
  to   { transform: scale(1.1); }
}

.errorCard {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 460px;
  background: #F6D506;
  border: 3px solid #000;
  border-radius: 28px;
  padding: 52px 52px;
  box-shadow: 8px 8px 0 #000;
  display: flex;
  flex-direction: column;
  gap: 26px;
  text-align: center;
  align-items: center;
}

.logo {
  font-size: 30px;
  font-weight: 900;
  color: #000;
  text-decoration: none;
  letter-spacing: -1px;
}

.cardHeader { display: flex; flex-direction: column; gap: 8px; align-items: center; }
.errorCode { font-size: 72px; font-weight: 900; color: #000; line-height: 1; margin-bottom: 8px; }
.cardTitle  { font-size: 26px; font-weight: 900; color: #000; margin: 0; }
.cardSub    { font-size: 15px; color: rgba(0,0,0,0.6); margin: 0; font-weight: 600; }

.actionBtn {
  width: 100%; padding: 16px;
  border-radius: 50px;
  border: 2px solid #000;
  background: #000;
  color: #F6D506; font-size: 15px; font-weight: 800; letter-spacing: 0.5px;
  text-decoration: none;
  cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
  transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
  margin-top: 10px;
}
.actionBtn:hover {
  background: #111;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
.actionBtn:active { transform: translateY(1px); }

@media (max-width: 480px) {
  .errorCard { padding: 40px 24px; border-radius: 20px; box-shadow: 5px 5px 0 #000; }
  .errorCode { font-size: 60px; }
  .cardTitle { font-size: 22px; }
}
</style>
