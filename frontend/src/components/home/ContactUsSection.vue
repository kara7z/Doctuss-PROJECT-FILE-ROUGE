<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const formData = ref({
  name: '',
  email: '',
  message: ''
})

const isSubmitted = ref(false)

const submitForm = (e) => {
  e.preventDefault()
  if (formData.value.name && formData.value.email && formData.value.message) {
    isSubmitted.value = true
    setTimeout(() => {
      isSubmitted.value = false
      formData.value = { name: '', email: '', message: '' }
    }, 4000)
  }
}
</script>

<template>
  <section class="contactSection">
    <div class="contactContainer">
      <div class="contactInfoBlock">
        <h2 class="contactTitle">{{ t('contact.title') }} <span class="contactHighlight">{{ t('contact.titleHighlight') }}</span></h2>
        <p class="contactSub">{{ t('contact.subtitle') }}</p>

        <div class="contactMethods">
          <div class="contactMethod">
            <div class="contactIcon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <h4 class="methodTitle">{{ t('contact.phoneSupport') }}</h4>
              <span class="methodDetail">+1 (800) 123-4567</span>
            </div>
          </div>

          <div class="contactMethod">
            <div class="contactIcon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <h4 class="methodTitle">{{ t('contact.emailUs') }}</h4>
              <span class="methodDetail">support@doctuss.com</span>
            </div>
          </div>

          <div class="contactMethod">
            <div class="contactIcon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <h4 class="methodTitle">{{ t('contact.headquarters') }}</h4>
              <span class="methodDetail">123 Health Ave, New York</span>
            </div>
          </div>
        </div>
      </div>

      <div class="contactFormBlock">
        <div class="brutalistFormWrapper">
          <form @submit="submitForm" class="contactForm" v-if="!isSubmitted">
            <div class="formRow">
              <div class="formGroup">
                <label>{{ t('contact.form.yourName') }}</label>
                <input type="text" v-model="formData.name" :placeholder="t('contact.form.namePlaceholder')" required />
              </div>
              <div class="formGroup">
                <label>{{ t('contact.form.emailAddress') }}</label>
                <input type="email" v-model="formData.email" :placeholder="t('contact.form.emailPlaceholder')" required />
              </div>
            </div>
            
            <div class="formGroup">
              <label>{{ t('contact.form.message') }}</label>
              <textarea v-model="formData.message" rows="5" :placeholder="t('contact.form.messagePlaceholder')" required></textarea>
            </div>

            <button type="submit" class="brutalistSubmitBtn">{{ t('contact.form.sendMessage') }}</button>
          </form>
          
          <div class="formSuccessMessage" v-else>
            <div class="successIconWrap">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3>{{ t('contact.form.messageSent') }}</h3>
            <p>{{ t('contact.form.successMessage') }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.contactSection {
  background: #000;
  padding: 100px 6%;
}

.contactContainer {
  max-width: 1280px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 80px;
  align-items: center;
}

/* Info side */
.contactInfoBlock {
  color: #fff;
}
.contactTitle {
  font-size: 52px;
  font-weight: 900;
  letter-spacing: -1.5px;
  text-transform: uppercase;
  line-height: 1.1;
  margin-bottom: 20px;
}
.contactHighlight {
  color: #F6D506;
}
.contactSub {
  font-size: 18px;
  color: rgba(255,255,255,0.7);
  line-height: 1.6;
  margin-bottom: 50px;
}

.contactMethods {
  display: flex;
  flex-direction: column;
  gap: 30px;
}
.contactMethod {
  display: flex;
  align-items: center;
  gap: 20px;
}
.contactIcon {
  width: 54px; height: 54px;
  border-radius: 12px;
  background: rgba(246,213,6,0.1);
  border: 1px solid rgba(246,213,6,0.3);
  display: flex; align-items: center; justify-content: center;
  color: #F6D506;
}
.methodTitle {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 4px;
}
.methodDetail {
  font-size: 15px;
  color: rgba(255,255,255,0.6);
}

/* Form side (Brutalist style) */
.contactFormBlock {
  position: relative;
}
.brutalistFormWrapper {
  background: #F6D506;
  padding: 50px;
  border-radius: 0px;
  border: 4px solid #000;
  box-shadow: 12px 12px 0px rgba(255,255,255,1);
  position: relative;
}

.formRow {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
.formGroup {
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
}
.formGroup label {
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
  color: #000;
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}
.formGroup input,
.formGroup textarea {
  background: #fff;
  border: 3px solid #000;
  padding: 16px;
  font-size: 16px;
  color: #000;
  font-family: inherit;
  outline: none;
  transition: all 0.2s ease;
  border-radius: 0;
}
.formGroup input:focus,
.formGroup textarea:focus {
  transform: translate(-3px, -3px);
  box-shadow: 6px 6px 0px #000;
}

.brutalistSubmitBtn {
  width: 100%;
  background: #000;
  color: #F6D506;
  border: 3px solid #000;
  padding: 18px;
  font-size: 18px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  margin-top: 10px;
}
.brutalistSubmitBtn:hover {
  background: #fff;
  color: #000;
  transform: translate(-4px, -4px);
  box-shadow: 8px 8px 0px #000;
}
.brutalistSubmitBtn:active {
  transform: translate(0px, 0px);
  box-shadow: 0px 0px 0px #000;
}

/* Success state */
.formSuccessMessage {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 0;
}
.successIconWrap {
  width: 80px; height: 80px;
  background: #000;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 24px;
}
.successIconWrap svg { stroke: #F6D506; }
.formSuccessMessage h3 {
  font-size: 32px;
  font-weight: 900;
  color: #000;
  margin-bottom: 10px;
  text-transform: uppercase;
}
.formSuccessMessage p {
  color: rgba(0,0,0,0.8);
  font-size: 16px;
  font-weight: 500;
}

/* Tablet */
@media (max-width: 968px) {
  .contactContainer {
    grid-template-columns: 1fr;
    gap: 60px;
  }
}
/* Mobile */
@media (max-width: 600px) {
  .contactSection { padding: 60px 5%; }
  .contactTitle { font-size: 40px; }
  .formRow { grid-template-columns: 1fr; }
  .brutalistFormWrapper {
    padding: 30px 20px;
    box-shadow: 6px 6px 0px rgba(255,255,255,1);
  }
}
</style>
