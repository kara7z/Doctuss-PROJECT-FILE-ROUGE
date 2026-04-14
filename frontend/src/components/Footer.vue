<template>
    <footer>
        <div class="footerGrid">
            <div class="footerBrand">
                <span class="footerLogo">doctuss</span>
                <p>Connecting patients with trusted healthcare professionals. Quality care starts with finding the right doctor.</p>
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
                <h4>Company</h4>
                <ul>
                    <li><router-link to="/">Home</router-link></li>
                    <li><router-link to="/about">About Us</router-link></li>
                    <li><router-link to="/services">Services</router-link></li>
                    <li><router-link to="/contact">Contact</router-link></li>
                </ul>
            </div>
            <div class="footerCol">
                <h4>Patients</h4>
                <ul>
                    <li><router-link to="/login">Sign In</router-link></li>
                    <li><router-link to="/register">Create Account</router-link></li>
                    <li><a href="#">Find a Doctor</a></li>
                    <li><a href="#">Book Appointment</a></li>
                    <li><a href="#">Patient Support</a></li>
                </ul>
            </div>
            <div class="footerCol">
                <h4>Get in Touch</h4>
                <ul class="contactList">
                    <li>
                        <span class="contactLabel">Email</span>
                        <span>support@doctuss.com</span>
                    </li>
                    <li>
                        <span class="contactLabel">Phone</span>
                        <span>+1 (800) 000-0000</span>
                    </li>
                    <li>
                        <span class="contactLabel">Address</span>
                        <span>Rabat, Morocco</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footerBottom">
            <span>© {{ new Date().getFullYear() }} Doctuss. All rights reserved.</span>
            <div class="footerLegal">
                <a href="#">Privacy Policy</a>
                <span class="divider">|</span>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>
</template>
<style>
footer{
    margin: 0 20px;
    padding: 48px 52px 0;
    border-radius: 24px 24px 0 0;
    background-color: #F6D506;
    border: black 3px solid;
    color: black;
    box-shadow: rgba(0, 0, 0, 0.25) 0px 4px 4px;
}
.footerGrid{
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.2fr;
    gap: 48px;
    padding-bottom: 32px;
    border-bottom: 2px solid rgba(0,0,0,0.2);
}
.footerLogo{
    font-size: 30px;
    font-weight: 900;
    display: block;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}
.footerBrand p{
    font-size: 13.5px;
    line-height: 1.7;
    max-width: 230px;
    opacity: 0.75;
    margin: 0 0 20px;
}
.footerSocials{
    display: flex;
    gap: 10px;
}
.footerSocials a{
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.08);
    border-radius: 8px;
    color: black;
    transition: background 0.2s;
}
.footerSocials a:hover{
    background: rgba(0,0,0,0.18);
}
.footerCol h4{
    font-size: 11px;
    font-weight: 800;
    margin: 0 0 16px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.5;
}
.footerCol ul{
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    font-size: 14px;
}
.footerCol ul li a{
    color: black;
    text-decoration: none;
    font-weight: 500;
    opacity: 0.8;
    transition: opacity 0.2s;
}
.footerCol ul li a:hover{
    opacity: 1;
    text-decoration: underline;
}
.contactList li{
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.contactLabel{
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.5;
}
.footerBottom{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 0;
    font-size: 13px;
    opacity: 0.65;
    flex-wrap: wrap;
    gap: 8px;
}
.footerLegal{
    display: flex;
    align-items: center;
    gap: 10px;
}
.footerLegal a{
    color: black;
    text-decoration: none;
    font-weight: 500;
}
.footerLegal a:hover{
    text-decoration: underline;
}
.divider{
    opacity: 0.4;
}
@media (max-width: 768px) {
    footer{
        padding: 32px 24px 0;
        margin: 0 12px;
    }
    .footerGrid{
        grid-template-columns: 1fr 1fr;
        gap: 28px;
    }
    .footerBrand{
        grid-column: 1 / -1;
    }
}
@media (max-width: 480px) {
    .footerGrid{
        grid-template-columns: 1fr;
    }
    .footerBottom{
        flex-direction: column;
        text-align: center;
    }
}
</style>