<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { watch } from 'vue'

const { t, locale } = useI18n()
const router = useRouter()
const route = useRoute()

const changeLanguage = (lang) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
  document.documentElement.setAttribute('lang', lang)
  document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr')
}

const scrollToContact = () => {
  if (route.path !== '/') {
    router.push('/')
    setTimeout(() => {
      const contactSection = document.querySelector('.contactSection')
      if (contactSection) {
        contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }, 300)
  } else {
    const contactSection = document.querySelector('.contactSection')
    if (contactSection) {
      contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }
}

const goToSearch = () => {
  router.push('/search')
}

watch(locale, (newLocale) => {
  document.documentElement.setAttribute('lang', newLocale)
  document.documentElement.setAttribute('dir', newLocale === 'ar' ? 'rtl' : 'ltr')
}, { immediate: true })
</script>

<template>
    <footer>
        <div class="footerGrid">
            <div class="footerBrand">
                <span class="footerLogo">{{ t('footer.brand') }}</span>
                <p>{{ t('footer.description') }}</p>
                <div class="footerSocials">
                    <a href="#" aria-label="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622 5.911-5.622Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="#" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                </div>
            </div>
            <div class="footerCol">
                <h4>{{ t('footer.company') }}</h4>
                <ul>
                    <li><router-link to="/">{{ t('footer.links.home') }}</router-link></li>
                    <li><a @click="goToSearch" style="cursor: pointer;">{{ t('footer.links.services') }}</a></li>
                    <li><a @click="scrollToContact" style="cursor: pointer;">{{ t('footer.links.contact') }}</a></li>
                </ul>
            </div>
            <div class="footerCol">
                <h4>{{ t('footer.patients') }}</h4>
                <ul>
                    <li><router-link to="/login">{{ t('footer.links.signIn') }}</router-link></li>
                    <li><router-link to="/register">{{ t('footer.links.createAccount') }}</router-link></li>
                    <li><a @click="goToSearch" style="cursor: pointer;">{{ t('footer.links.findDoctor') }}</a></li>
                    <li><a @click="goToSearch" style="cursor: pointer;">{{ t('footer.links.bookAppointment') }}</a></li>
                    <li><a @click="scrollToContact" style="cursor: pointer;">{{ t('footer.links.patientSupport') }}</a></li>
                </ul>
            </div>
            <div class="footerCol">
                <h4>{{ t('footer.getInTouch') }}</h4>
                <ul class="contactList">
                    <li>
                        <span class="contactLabel">{{ t('footer.email') }}</span>
                        <span>support@doctuss.com</span>
                    </li>
                    <li>
                        <span class="contactLabel">{{ t('footer.phone') }}</span>
                        <span>+1 (800) 000-0000</span>
                    </li>
                    <li>
                        <span class="contactLabel">{{ t('footer.address') }}</span>
                        <span>{{ t('footer.location') }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footerBottom">
            <span>© {{ new Date().getFullYear() }} {{ t('footer.copyright') }}</span>
            <div class="footerActions">
                <div class="footerLegal">
                    <a href="#">{{ t('footer.privacyPolicy') }}</a>
                    <span class="divider">|</span>
                    <a href="#">{{ t('footer.termsOfService') }}</a>
                </div>
                <div class="languageSelector">
                    <label for="language">{{ t('footer.language') }}:</label>
                    <select id="language" v-model="locale" @change="changeLanguage(locale)" class="langSelect">
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="ar">العربية</option>
                    </select>
                </div>
            </div>
        </div>
    </footer>
</template>
<style>
footer {
    margin: 40px 20px 0;
    padding: 60px 60px 0;
    border-radius: 24px 24px 0 0;
    background-color: #F6D506;
    border: black 4px solid;
    border-bottom: none;
    color: black;
    box-shadow: 0px -8px 0px rgba(0,0,0,1);
    position: relative;
    z-index: 10;
}
.footerGrid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.2fr;
    gap: 48px;
    padding-bottom: 40px;
    border-bottom: 4px solid #000;
}
.footerLogo {
    font-size: 36px;
    font-weight: 900;
    display: block;
    margin-bottom: 16px;
    letter-spacing: -1.5px;
    text-transform: uppercase;
}
.footerBrand p {
    font-size: 15px;
    line-height: 1.6;
    font-weight: 600;
    max-width: 250px;
    margin: 0 0 24px;
}
.footerSocials {
    display: flex;
    gap: 12px;
}
.footerSocials a {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 3px solid #000;
    border-radius: 0px;
    color: black;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.footerSocials a:hover {
    background: #000;
    color: #F6D506;
    transform: translate(-3px, -3px);
    box-shadow: 6px 6px 0px rgba(0,0,0,1);
}
.footerCol h4 {
    font-size: 14px;
    font-weight: 900;
    margin: 0 0 20px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}
.footerCol ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-size: 16px;
}
.footerCol ul li a {
    color: black;
    text-decoration: none;
    font-weight: 700;
    display: inline-block;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.footerCol ul li a:hover {
    transform: translateX(5px);
    text-shadow: 2px 2px 0px rgba(255,255,255,0.8);
}
.contactList li {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.contactLabel {
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.contactList span:last-child {
    font-weight: 700;
    font-size: 15px;
}
.footerBottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 0;
    font-size: 15px;
    font-weight: 700;
    flex-wrap: wrap;
    gap: 16px;
}
.footerLegal {
    display: flex;
    align-items: center;
    gap: 16px;
}
.footerLegal a {
    color: black;
    text-decoration: none;
    transition: all 0.2s;
}
.footerLegal a:hover {
    text-decoration: underline;
    background: #000;
    color: #F6D506;
    padding: 0 4px;
}
.divider { font-weight: 900; }
.footerActions {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}
.languageSelector {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
}
.langSelect {
    padding: 6px 12px;
    border: 3px solid #000;
    background: #fff;
    color: #000;
    font-weight: 900;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 3px 3px 0px #000;
}
.langSelect:hover {
    background: #000;
    color: #F6D506;
    transform: translate(-2px, -2px);
    box-shadow: 5px 5px 0px #000;
}
.langSelect:focus {
    outline: none;
    transform: translate(1px, 1px);
    box-shadow: 2px 2px 0px #000;
}
@media (max-width: 968px) {
    .footerGrid { grid-template-columns: 1fr 1fr; gap: 40px; }
    .footerBrand { grid-column: 1 / -1; }
}
@media (max-width: 600px) {
    footer { padding: 40px 24px 0; margin: 20px 12px 0; }
    .footerGrid { grid-template-columns: 1fr; gap: 30px; }
    .footerBottom { flex-direction: column; text-align: center; }
    .footerActions { flex-direction: column; gap: 16px; }
}
</style>