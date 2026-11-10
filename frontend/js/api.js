// js/api.js
// This script does most of the communicating with the supabase backend.

// Browsers can't resolve bare imports like '@supabase/supabase-js' without a bundler,
// so we load the ES module build from a CDN (pinned version).
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.45.4/+esm';

import { CONFIG } from './config.js';

// "Keep me signed in" handling.
// Supabase stores the session token in a storage object. We route it to:
//  - localStorage   -> survives closing the browser   (remember me ON)
//  - sessionStorage -> survives refreshes in this tab only (remember me OFF)
const REMEMBER_KEY = 'unifit-remember-me';

function rememberMe() {
    if (!CONFIG.SECURITY.ALLOW_SESSION_COOKIES) return false;
    try {
        return localStorage.getItem(REMEMBER_KEY) === 'true';
    } catch {
        return false;
    }
}

export function setRememberMe(value) {
    try {
        localStorage.setItem(REMEMBER_KEY, value ? 'true' : 'false');
    } catch { /* storage blocked: falls back to session-only */ }
}

const authStorage = {
    getItem(key) {
        try {
            return localStorage.getItem(key) ?? sessionStorage.getItem(key);
        } catch {
            return null;
        }
    },
    setItem(key, value) {
        try {
            if (rememberMe()) {
                localStorage.setItem(key, value);
                sessionStorage.removeItem(key);
            } else {
                sessionStorage.setItem(key, value);
                localStorage.removeItem(key);
            }
        } catch { /* ignore */ }
    },
    removeItem(key) {
        try {
            localStorage.removeItem(key);
            sessionStorage.removeItem(key);
        } catch { /* ignore */ }
    },
};

export const supabase = createClient(CONFIG.SUPABASE_URL, CONFIG.SUPABASE_ANON_KEY, {
    auth: {
        storage: authStorage,
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: true,
    },
});

// ---------- Test mode (CONFIG.MOCK_AUTH) ----------
// Fakes the Supabase responses so the login flow can be tested without a server.
const MOCK_SESSION_KEY = 'unifit-mock-session';
const MOCK = CONFIG.MOCK_AUTH === true;
if (MOCK) console.warn('[api.js] MOCK_AUTH is ON: logins are faked. Disable it in config.js before deploying.');

function mockDelay() {
    return new Promise((resolve) => setTimeout(resolve, 400));
}

async function mockLogin(email, password) {
    await mockDelay();
    if (email === 'offline@unifit.nl') {
        return { data: {}, error: { name: 'AuthRetryableFetchError', status: 0, message: 'Failed to fetch' } };
    }
    if (email === 'limit@unifit.nl') {
        return { data: {}, error: { name: 'AuthApiError', status: 429, message: 'Request rate limit reached' } };
    }
    if (email === 'test@unifit.nl' && password === 'Test1234!') {
        const session = { access_token: 'mock-token', user: { id: 'mock-user', email } };
        authStorage.setItem(MOCK_SESSION_KEY, JSON.stringify(session));
        return { data: { session, user: session.user }, error: null };
    }
    return { data: {}, error: { name: 'AuthApiError', status: 400, code: 'invalid_credentials', message: 'Invalid login credentials' } };
}

function mockSession() {
    try {
        return JSON.parse(authStorage.getItem(MOCK_SESSION_KEY)) ?? null;
    } catch {
        return null;
    }
}

// ---------- Real API ----------

// Log in function.
export async function loginUser(email, password) {
    if (MOCK) return mockLogin(email, password);
    const { data, error } = await supabase.auth.signInWithPassword({
        email: email,
        password: password,
    });
    return { data, error };
}

// Fetch the current session from storage (fast, no network call).
// Good enough to decide on a redirect from the login page.
export async function getSession() {
    if (MOCK) return mockSession();
    const { data: { session } } = await supabase.auth.getSession();
    return session;
}

// Verify the user with the Supabase server (use this to protect pages).
export async function getUser() {
    if (MOCK) return mockSession()?.user ?? null;
    const { data: { user }, error } = await supabase.auth.getUser();
    if (error) return null;
    return user;
}

// Log out function.
export async function logoutUser() {
    if (MOCK) {
        authStorage.removeItem(MOCK_SESSION_KEY);
        return { error: null };
    }
    const { error } = await supabase.auth.signOut();
    return { error };
}
