// js/api.js
// This script does most of the communicating with the supabase backend. 

import { createClient } from '@supabase/supabase-js'

import { CONFIG } from './config.js';

export const supabase = createClient(CONFIG.SUPABASE_URL, CONFIG.SUPABASE_ANON_KEY);

// Log in functions.
export async function loginUser(email, password) {
    const { data, error } = await supabase.auth.signInWithPassword({
        email: email,
        password: password,
    });
    return { data, error };
}

// Fetch the current session.
export async function getSession() {
    const { data: { session } } = await supabase.auth.getSession();
    return session;
}