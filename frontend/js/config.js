// js/config.js
// This script stores some configuration parameters for the login page. 

export const CONFIG = {
    SUPABASE_URL: 'http://JOUW_EDUCLOUD_IP:8000',
    SUPABASE_ANON_KEY: 'jouw_anon_key',
    APP_VERSION: '1.0.0',
    SECURITY: {
        MAX_TRIES: 3,
        BASE_TIMEOUT_SEC: 30,
        TIMEOUT_INCREMENT_SEC: 30,
        ALLOW_SESSION_COOKIES: true,
        ALLOW_SIGNUP: true
    }
};