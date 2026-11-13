// js/guard.js
// Drop-in protection for any page outside the login portal (e.g. the dashboard).
//
// Usage — put these two lines in the <head> of the protected page:
//
//   <style id="auth-guard">body { visibility: hidden; }</style>
//   <script type="module" src="/auth/js/guard.js"></script>
//
// The <style> hides the page immediately while it loads. This script then checks the
// session with Supabase: not logged in -> redirect to the login page,
// logged in -> remove the <style> so the page becomes visible.
//
// Optional: a button with id="logout-btn" anywhere on the page becomes a logout button.
// Optional: an element with id="user-email" gets filled with the user's email address.
//
// NOTE: this only hides the page in the browser. The real protection of the data is
// Supabase Row Level Security, because client-side code can always be bypassed.

import { supabase, getUser, logoutUser } from './api.js';

const LOGIN_URL = '/auth/login.html';

function toLogin() {
    window.location.replace(LOGIN_URL);
}

function reveal() {
    document.getElementById('auth-guard')?.remove();
    document.body.style.visibility = 'visible';
}

async function init() {
    let user = null;
    try {
        // getUser() validates the token with the Supabase server (not just local storage).
        user = await getUser();
    } catch (err) {
        console.error('Could not verify session:', err);
    }

    if (!user) {
        toLogin();
        return;
    }

    const emailEl = document.getElementById('user-email');
    if (emailEl) emailEl.textContent = user.email;

    const logoutButton = document.getElementById('logout-btn');
    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            logoutButton.disabled = true;
            await logoutUser();
            toLogin();
        });
    }

    reveal();
}

// If the session ends elsewhere (other tab logged out, token expired), go back to login.
supabase.auth.onAuthStateChange((event) => {
    if (event === 'SIGNED_OUT') toLogin();
});

init();
