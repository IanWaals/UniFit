// js/login.js
// This script handles the UI logic, flowchart states, and user interactions for the login page.

import { CONFIG } from './config.js';
import { loginUser, getSession } from './api.js';

let currentTries = 0;
let isLockedOut = false;
let lockoutInterval = null;

// Elements from the HTML.
const loginForm = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const formStatus = document.getElementById('form-status');
const signupLink = document.querySelector('a[href="/signup.html"]');

// 1. Bij het laden van de pagina: check configuratie én actieve sessie (Flowchart: Valid session cookie?)
window.addEventListener('DOMContentLoaded', async () => {
    // Check if signup is allowed (parameter in the config file).
    if (!CONFIG.SECURITY.ALLOW_SIGNUP && signupLink) {
        signupLink.style.display = 'none';
    }

    // Check of er al een actieve sessie is (voorkomt dat je opnieuw moet inloggen na refresh)
    if (CONFIG.SECURITY.ALLOW_SESSION_COOKIES) {
        const session = await getSession();
        if (session) {
            showStatus("Reeds ingelogd. Doorverwijzen naar portaal...", "success");
            setTimeout(() => {
                window.location.href = '/portal.html';
            }, 500);
        }
    }
});

// 2. Formulier verzenden en inloglogica uitvoeren
loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (isLockedOut) return;

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!email || !password) {
        showStatus("Vul alstublieft alle velden in.", "error");
        return;
    }

    showStatus("Bezig met verifiëren...", "info");

    // Verstuur request naar de API
    const { data, error } = await loginUser(email, password);

    // 3. Foutafhandeling (Flowchart: Credentials correct? -> NO)
    if (error) {
        currentTries++;
        console.warn(`Incorrecte poging ${currentTries}/${CONFIG.SECURITY.MAX_TRIES}`);

        // Check of het maximum aantal pogingen is bereikt (Flowchart: Is counter {Max_tries} >= 3?)
        if (currentTries >= CONFIG.SECURITY.MAX_TRIES) {
            startLockoutTimer();
        } else {
            // Specifieke/duidelijke melding bij foutieve invoer of ongeldige gebruiker
            showStatus("Wrong credentials entered or invalid user.", "error");
        }
        return;
    }

    // 4. Succesvolle login (Flowchart: Welkom terug / Unlock portal / Reset counter)
    currentTries = 0;
    showStatus("Welkom terug gebruiker! Doorverwijzen...", "success");

    setTimeout(() => {
        window.location.href = "/portal.html";
    }, 1000);
});

// Hulpfunctie om statusberichten te tonen in de HTML
function showStatus(message, type) {
    if (formStatus) {
        formStatus.textContent = message;
        formStatus.className = type;
    }
}

// Timeout / Lockout logica gebaseerd op de flowchart
function startLockoutTimer() {
    isLockedOut = true;
    let timeLeft = CONFIG.SECURITY.BASE_TIMEOUT_SEC;

    // Blokkeer invoervelden tijdens de timeout
    emailInput.disabled = true;
    passwordInput.disabled = true;

    showStatus(`Te veel incorrecte inlogpogingen. Timeout voor ${timeLeft} seconden.`, "error");

    lockoutInterval = setInterval(() => {
        timeLeft--;
        if (timeLeft > 0) {
            showStatus(`Te veel incorrecte inlogpogingen. Timeout voor ${timeLeft} seconden.`, "error");
        } else {
            clearInterval(lockoutInterval);
            isLockedOut = false;
            currentTries = 0;

            // Deblokkeer de invoervelden
            emailInput.disabled = false;
            passwordInput.disabled = false;
            showStatus("Je kunt opnieuw proberen in te loggen.", "info");
        }
    }, 1000);
}