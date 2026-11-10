// js/login.js
// This script handles the UI logic, flowchart states, and user interactions for the login page.

import { CONFIG } from './config.js';
import { loginUser, getSession, setRememberMe } from './api.js';

// Where to go after a successful login (relative, so it works in any subfolder).
const PORTAL_URL = 'portal.html';

// Lockout state is kept in localStorage so a page refresh does not reset the counter.
// NOTE: this is a UX layer only. Real brute-force protection is the rate limiting
// on the Supabase (GoTrue) auth server, since client-side code can always be bypassed.
const GUARD_KEY = 'unifit-login-guard';

let lockoutInterval = null;

// Elements from the HTML.
const loginForm = document.getElementById('login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const rememberInput = document.getElementById('kmsi');
const rememberRow = document.getElementById('kmsi-row');
const rememberNote = document.getElementById('kmsi-note');
const submitButton = loginForm.querySelector('button[type="submit"]');
const formStatus = document.getElementById('form-status');
const signupLink = document.getElementById('signup-link');
const passwordResetText = document.getElementById('passwd-rst-str');

// ---------- Lockout state helpers ----------

function loadGuard() {
    try {
        const raw = localStorage.getItem(GUARD_KEY);
        if (raw) return { tries: 0, lockedUntil: 0, ...JSON.parse(raw) };
    } catch { /* ignore corrupt/blocked storage */ }
    return { tries: 0, lockedUntil: 0 };
}

function saveGuard(guard) {
    try {
        localStorage.setItem(GUARD_KEY, JSON.stringify(guard));
    } catch { /* ignore */ }
}

function resetGuard() {
    try {
        localStorage.removeItem(GUARD_KEY);
    } catch { /* ignore */ }
}

function isLockedOut() {
    return loadGuard().lockedUntil > Date.now();
}

// ---------- 1. Page load: check config and active session (Flowchart: Valid session cookie?) ----------

async function init() {
    // Check if signup is allowed (parameter in the config file).
    if (!CONFIG.SECURITY.ALLOW_SIGNUP && signupLink) {
        signupLink.style.display = 'none';
    }

    // Hide the "Forgot your password?" link if password reset is disabled in the config.
    if (!CONFIG.SECURITY.ALLOW_PASSWORD_RESET && passwordResetText) {
        passwordResetText.style.display = 'none';
    }

    // Show a confirmation text next to the checkbox while "Keep me signed in" is ticked.
    if (rememberInput && rememberNote) {
        const updateRememberNote = () => { rememberNote.hidden = !rememberInput.checked; };
        rememberInput.addEventListener('change', updateRememberNote);
        updateRememberNote(); // browsers can restore the checkbox state after a refresh
    }

    // Hide "Keep me signed in" if persistent sessions are disabled in the config.
    if (!CONFIG.SECURITY.ALLOW_SESSION_COOKIES && rememberRow) {
        rememberRow.style.display = 'none';
    }

    // Resume a lockout that was still running before a refresh.
    if (isLockedOut()) {
        startLockoutTimer();
    }

    // Check if there is already an active session (prevents having to log in again after a refresh).
    try {
        const session = await getSession();
        if (session) {
            showStatus('Already signed in. Redirecting to the portal...', 'success');
            setTimeout(() => {
                window.location.replace(PORTAL_URL);
            }, 500);
        }
    } catch (err) {
        console.error('Could not check session:', err);
    }
}

init();

// ---------- 2. Submit the form and run the login logic ----------

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (isLockedOut()) return;

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!email || !password) {
        showStatus('Please put in your credentials.', 'error');
        return;
    }

    // Must be set BEFORE logging in, so Supabase stores the session in the right place.
    setRememberMe(CONFIG.SECURITY.ALLOW_SESSION_COOKIES && rememberInput?.checked);

    showStatus('Verifying...', 'info');
    setFormDisabled(true);

    // Send request to the API.
    let result;
    try {
        result = await loginUser(email, password);
    } catch (err) {
        result = { error: err };
    }
    const { error } = result;

    // 3. Error handling (Flowchart: Credentials correct? -> NO)
    if (error) {
        setFormDisabled(false);

        // Network/server problems are not the user's fault: don't count them as a try.
        if (isNetworkError(error)) {
            console.error('Login request failed:', error);
            showStatus('Could not reach the server. Please try again later.', 'error');
            return;
        }

        // Server-side rate limit (Supabase) kicked in.
        if (error.status === 429) {
            showStatus('Too many login attempts. Timeout reached! Please wait a moment and try again.', 'error');
            return;
        }

        if (error.code === 'email_not_confirmed') {
            showStatus('Your email address has not been confirmed yet. Check your inbox.', 'error');
            return;
        }

        const guard = loadGuard();
        guard.tries++;
        const maxTries = CONFIG.SECURITY.MAX_TRIES;
        console.warn(guard.tries <= maxTries
            ? `Incorrect attempt ${guard.tries}/${maxTries}`
            : `Incorrect attempt ${guard.tries} (lockout after ${maxTries})`);

        // Check if the maximum amount of tries has been reached (Flowchart: Is counter {Max_tries} >= 3?)
        // The counter keeps going up (only a successful login resets it), so after the
        // first lockout every single wrong try triggers a new, longer lockout.
        if (guard.tries >= maxTries) {
            // 3rd wrong try = BASE, 4th = BASE + INCREMENT, 5th = BASE + 2*INCREMENT, ... capped at MAX.
            const timeoutSec = Math.min(
                CONFIG.SECURITY.BASE_TIMEOUT_SEC + CONFIG.SECURITY.TIMEOUT_INCREMENT_SEC * (guard.tries - maxTries),
                CONFIG.SECURITY.MAX_TIMEOUT_SEC,
            );
            console.warn(`Locked out for ${timeoutSec} seconds`);
            guard.lockedUntil = Date.now() + timeoutSec * 1000;
            saveGuard(guard);
            startLockoutTimer();
        } else {
            saveGuard(guard);
            // Generic message on purpose: don't reveal whether the account exists.
            showStatus('Wrong credentials entered or invalid user.', 'error');
            passwordInput.value = '';
            passwordInput.focus();
        }
        return;
    }

    // 4. Successful login (Flowchart: Welcome back / Unlock portal / Reset counter)
    resetGuard();
    showStatus('Welcome back! Redirecting...', 'success');

    setTimeout(() => {
        window.location.replace(PORTAL_URL);
    }, 1000);
});

// ---------- Helpers ----------

// Helper function to show status messages in the HTML.
function showStatus(message, type) {
    if (formStatus) {
        formStatus.textContent = message;
        formStatus.className = type;
    }
}

function setFormDisabled(disabled) {
    emailInput.disabled = disabled;
    passwordInput.disabled = disabled;
    if (rememberInput) rememberInput.disabled = disabled;
    if (submitButton) submitButton.disabled = disabled;
}

function isNetworkError(error) {
    // supabase-js returns AuthRetryableFetchError (status 0 / 5xx) when the server can't be reached.
    return error.name === 'AuthRetryableFetchError'
        || error instanceof TypeError
        || error.status === 0
        || (typeof error.status === 'number' && error.status >= 500);
}

// Timeout / lockout logic based on the flowchart.
function startLockoutTimer() {
    clearInterval(lockoutInterval);

    // Block the input fields during the timeout.
    setFormDisabled(true);

    const tick = () => {
        const timeLeft = Math.ceil((loadGuard().lockedUntil - Date.now()) / 1000);
        if (timeLeft > 0) {
            showStatus(`Too many incorrect login attempts. Timeout reached! Please try again in ${timeLeft} seconds.`, 'error');
        } else {
            clearInterval(lockoutInterval);

            // Unblock the input fields.
            setFormDisabled(false);
            showStatus('You can try to log in again.', 'info');
        }
    };

    tick();
    lockoutInterval = setInterval(tick, 1000);
}
