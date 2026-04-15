<template>
<section class="DiscoverSectionContainer">
  <div class="Discover-Text">
    <span>
      Discover the <h1>online</h1> appointment!
    </span>
    <p>Find and book appointments with trusted doctors near you. Browse by specialty, read patient reviews, and get the care you need — all in one place.</p>
  </div>

  <div class="stepsSection">
    <div class="stepCard">
      <div class="stepIcon">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </div>
      <span class="stepNumber">01</span>
      <h3>Find a Doctor</h3>
      <p>Search by specialty, location, or name to find the right doctor for your needs.</p>
    </div>
    
    <div class="stepDivider">
      <!-- Huge graphical block arrow -->
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,40 L60,40 L60,20 L100,50 L60,80 L60,60 L0,60 Z" fill="#000"/></svg>
    </div>
    
    <div class="stepCard">
      <div class="stepIcon">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <span class="stepNumber">02</span>
      <h3>View Profile</h3>
      <p>Read reviews, check qualifications, and explore availability before deciding.</p>
    </div>
    
    <div class="stepDivider">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,40 L60,40 L60,20 L100,50 L60,80 L60,60 L0,60 Z" fill="#000"/></svg>
    </div>
    
    <div class="stepCard">
      <div class="stepIcon">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <span class="stepNumber">03</span>
      <h3>Book a Visit</h3>
      <p>Pick a time that works for you and confirm your appointment in seconds.</p>
    </div>
  </div>
</section>
</template>

<style scoped>
.DiscoverSectionContainer {
  background-image: radial-gradient(#d4d4d4 2px, transparent 2px);
  background-size: 32px 32px;
  background-position: center top;
  padding-bottom: 20px;
  width: 100%;
}

.Discover-Text {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 60px 20% 0;
  gap: 16px;
  user-select: none;
}
.Discover-Text span {
  display: flex;
  font-size: 48px;
  gap: 12px;
  font-weight: 900;
  text-transform: uppercase;
  color: #000;
  letter-spacing: -2px;

}
.Discover-Text h1 {
  font-size: 48px;
  font-weight: 900;
  background: #F6D506;
  padding: 2px 16px;
  border: 5px solid #000;
  box-shadow: 8px 8px 0px #000;
  margin: 0;
  line-height: 1.1;
  transform: rotate(-3deg);
  transition: transform 0.2s;
  cursor: pointer;
}
.Discover-Text span:hover h1 {
  transform: rotate(-1deg) scale(1.05);
}
.Discover-Text p {
  font-size: 20px;
  font-weight: 700;
  color: #000;
  margin-top: 16px;
  background: #fff;
  padding: 10px 20px;
  border: 3px solid #000;
  box-shadow: 6px 6px 0px #000;
}

.stepsSection {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 24px;
  padding: 60px 6% 100px;
  flex-wrap: wrap;
}
.stepCard {
  user-select: none;
  flex: 1;
  min-width: 250px;
  max-width: 320px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 40px 24px;
  border-radius: 0px;
  border: 4px solid #000;
  background: #fff;
  box-shadow: 12px 12px 0px rgba(0,0,0,1);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  gap: 16px;
  position: relative;
  z-index: 2;
  cursor: pointer;
}
.stepCard:nth-of-type(1) { transform: rotate(-1.5deg); }
.stepCard:nth-of-type(2) { margin-top: 50px; transform: rotate(1deg); }
.stepCard:nth-of-type(3) { margin-top: 100px; transform: rotate(-1deg); }

.stepCard:hover {
  transform: translate(-10px, -10px) rotate(0deg) !important;
  box-shadow: 22px 22px 0px rgba(0,0,0,1);
  background: #F6D506;
  z-index: 5;
}
.stepCard:hover .stepIcon {
  background: #fff;
  color: #000;
  transform: rotate(5deg) scale(1.1);
}
.stepIcon {
  width: 80px; height: 80px;
  background: #000; color: #F6D506;
  border-radius: 0px;
  border: 4px solid #000;
  box-shadow: 6px 6px 0px rgba(0,0,0,1);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 8px;
  transition: all 0.2s;
}
.stepIcon svg { width: 36px; height: 36px; }
.stepNumber {
  position: absolute;
  top: -20px; left: -20px;
  background: #F6D506;
  border: 4px solid #000;
  padding: 8px 16px;
  font-size: 18px; font-weight: 900;
  box-shadow: 6px 6px 0px #000;
  transform: rotate(-5deg);
  transition: transform 0.2s;
}
.stepCard:hover .stepNumber {
  background: #000; color: #F6D506;
  transform: rotate(0deg) scale(1.1);
}
.stepCard h3 { font-size: 26px; font-weight: 900; margin: 0; text-transform: uppercase; color: #000; }
.stepCard p  { font-size: 15px; font-weight: 700; margin: 0; line-height: 1.6; color: rgba(0,0,0,0.8); }

.stepDivider {
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  align-self: center;
  z-index: 1;
}
.stepDivider svg { 
  width: 50px; height: 40px; 
  transform: scale(1.2); 
  filter: drop-shadow(4px 4px 0px rgba(0,0,0,0.2)); 
}

@media (max-width: 968px) {
  .Discover-Text span { flex-direction: column; align-items: center; gap: 8px; }
  .Discover-Text span h1 { margin-top: 12px; }
  .stepCard:nth-of-type(1), .stepCard:nth-of-type(2), .stepCard:nth-of-type(3), .stepCard { margin-top: 0; transform: none; }
  .Discover-Text p { margin: 16px 20px 0; }
}
@media (max-width: 768px) {
  .Discover-Text { padding: 40px 5% 0; }
  .Discover-Text span { font-size: 36px; }
  .Discover-Text h1 { font-size: 38px; transform: rotate(0); }
  .Discover-Text p { font-size: 16px; }
  .stepsSection { padding: 40px 4%; gap: 30px; }
  .stepDivider { display: none; }
}
@media (max-width: 600px) {
  .stepsSection { flex-direction: column; align-items: center; gap: 40px; }
  .stepDivider { display: none; }
  .stepCard { max-width: 100%; width: 100%; margin-top: 20px; }
}
@media (max-width: 480px) {
  .Discover-Text { padding: 30px 5% 0; }
  .Discover-Text span { font-size: 28px; }
  .Discover-Text h1 { font-size: 30px; }
  .Discover-Text p { font-size: 15px; }
}
</style>
