// js/portal.js
// Protects the portal page: only users with a valid Supabase session may see it.

import { supabase, getUser, logoutUser, getSessionSource } from './api.js';

const LOGIN_URL = '/auth/login.html';

const portal = document.getElementById('portal');
const userEmail = document.getElementById('user-email');
const logoutButton = document.getElementById('logout-btn');

async function init() {
    // getUser() validates the token with the Supabase server (not just local storage).
    const user = await getUser();
    if (!user) {
        window.location.replace(LOGIN_URL);
        return;
    }

    const source = getSessionSource();
    if (source) console.log(`Cookie loaded. Session restored (from ${source}) for ${user.email}`);

    userEmail.textContent = user.email;
    portal.hidden = false;
}

logoutButton.addEventListener('click', async () => {
    logoutButton.disabled = true;
    await logoutUser();
    window.location.replace(LOGIN_URL);
});

// If the session ends elsewhere (other tab logged out, token expired), go back to login.
supabase.auth.onAuthStateChange((event) => {
    if (event === 'SIGNED_OUT') {
        window.location.replace(LOGIN_URL);
    }
});

init();
