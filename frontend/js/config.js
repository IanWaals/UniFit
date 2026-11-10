// js/config.js
// This script stores some configuration parameters for the login page. 

export const CONFIG = {
    SUPABASE_URL: 'http://JOUW_EDUCLOUD_IP:8000',
    SUPABASE_ANON_KEY: 'jouw_anon_key',
    APP_VERSION: '1.0.0',

    // Test mode: fakes the Supabase login so the page can be tested without a server.
    // SET TO false BEFORE DEPLOYING.
    // Test accounts (any other email/password counts as wrong credentials):
    //   test@unifit.nl    / Test1234!  -> successful login
    //   offline@unifit.nl / anything   -> simulates "server unreachable"
    //   limit@unifit.nl   / anything   -> simulates the Supabase rate limit (429)
    MOCK_AUTH: true,

    SECURITY: {
        MAX_TRIES: 3,
        BASE_TIMEOUT_SEC: 30,
        TIMEOUT_INCREMENT_SEC: 30,
        MAX_TIMEOUT_SEC: 3600, // lockout never lasts longer than 1 hour
        ALLOW_SESSION_COOKIES: true,
        ALLOW_SIGNUP: false
    }
};