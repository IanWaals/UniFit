<?php
// src/require_login.php
// Protects any PHP page (e.g. the dashboard) with the Unifit login.
// Put this as the VERY FIRST line of the page, before any HTML is sent:
//
//     <?php require '/srv/Unifit-login-portal/src/require_login.php';
//
// Visitors without a valid session are redirected to the login page.
// Afterwards the page can use:
//     $unifitUser['email'], $unifitUser['id']   -> the logged-in Supabase user
//     echo unifit_logout_form('Log uit');       -> a logout button (POST + CSRF)

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/supabase.php';

start_session();
header('Cache-Control: no-store'); // don't let the browser cache protected pages

try {
    // Validates the token with the Supabase server (not just the local session).
    $unifitUser = current_user();
} catch (SupabaseUnavailable) {
    http_response_code(503);
    exit('Could not reach the login server. Please try again later.');
}

if ($unifitUser === null) {
    redirect(config()['login_url']);
}

function unifit_logout_form(string $label = 'Log out'): string
{
    return '<form method="post" action="' . e(config()['logout_url']) . '" class="logout-form">'
        . csrf_field()
        . '<button type="submit">' . e($label) . '</button>'
        . '</form>';
}
