// js/config.js
// This script stores some configuration parameters for the login page. 

export const CONFIG = {
    SUPABASE_URL: 'http://10.61.0.14:8000',
    SUPABASE_ANON_KEY: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyb2xlIjoiYW5vbiIsImlzcyI6InN1cGFiYXNlIiwiaWF0IjoxNzkzOTYxNDgwLCJleHAiOjE5NTE2NDE0ODB9.62DOezKfp2f0vLNi7rYNcTBepDrTgujTq6evmR12Cks',
    APP_VERSION: '1.0.0',

    // Test mode: fakes the Supabase login so the page can be tested without a server.
    // SET TO false BEFORE DEPLOYING.
    // Test accounts (any other email/password counts as wrong credentials):
    //   test@unifit.nl    / Test1234!  -> successful login
    //   offline@unifit.nl / anything   -> simulates "server unreachable"
    //   limit@unifit.nl   / anything   -> simulates the Supabase rate limit (429)
    MOCK_AUTH: false,

    SECURITY: {
        MAX_TRIES: 3,
        BASE_TIMEOUT_SEC: 30,
        TIMEOUT_INCREMENT_SEC: 30,
        MAX_TIMEOUT_SEC: 3600, // lockout never lasts longer than 1 hour
        ALLOW_SESSION_COOKIES: true, // allow the use of session cookies so if you refresh the page, you wont have to log in again after having already logged in.
        ALLOW_SIGNUP: false, // Allow account creation. Set to disabled for now.
        ALLOW_PASSWORD_RESET: true // Allow password reset. Set to enabled for now.
    }
};