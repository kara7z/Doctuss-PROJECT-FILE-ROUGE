<template>
  <section class="section">
    <div class="sectionL">WHY CHOSE US?</div>
    <div class="sectionB">
      <ul>
        <li>1. Easy online booking</li>
        <li>2. Available 24/7</li>
        <li>3. Instant confirmation</li>
        <li>4. Verified &amp; Trusted Doctors</li>
      </ul>
    </div>
  </section>
    <button class="explore-btn">Explore Doctors</button>

</template>

<style scoped>
.explore-btn{
  width: 100%;
  padding: .7rem;
  background-color: #F6D506;
  border: black 3px solid;
  font-size: 24px;
  font-weight: 900;
  cursor: pointer;
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
