<?php
// src/supabase.php
// Talks to the Supabase (GoTrue) auth REST API from the server.
// The browser never sees the Supabase URL, key or tokens: they live in the PHP session.

declare(strict_types=1);

class SupabaseUnavailable extends RuntimeException
{
}

/**
 * Low-level request. Returns ['status' => int, 'body' => array|null].
 * status 0 means the server could not be reached.
 */
function supabase_request(string $method, string $path, ?array $body = null, ?string $accessToken = null): array
{
    $cfg = config();
    $ch = curl_init(rtrim($cfg['supabase_url'], '/') . $path);
    $headers = [
        'apikey: ' . $cfg['supabase_anon_key'],
        'Authorization: Bearer ' . ($accessToken ?? $cfg['supabase_anon_key']),
        'Accept: application/json',
        // Lets Supabase rate-limit per real visitor instead of per PHP server
        // (only used if GOTRUE_RATE_LIMIT_HEADER=X-Forwarded-For is set on the Supabase side).
        'X-Forwarded-For: ' . client_ip(),
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $raw = curl_exec($ch);
    $status = $raw === false ? 0 : (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : null];
}

/**
 * Log in with email + password.
 * Returns ['ok' => true, 'session' => [...]] or ['ok' => false, 'error' => network|rate_limited|email_not_confirmed|invalid].
 */
function supabase_login(string $email, string $password): array
{
    if (config()['mock_auth']) {
        return mock_login($email, $password);
    }

    $res = supabase_request('POST', '/auth/v1/token?grant_type=password', [
        'email'    => $email,
        'password' => $password,
    ]);

    if ($res['status'] === 200 && isset($res['body']['access_token'])) {
        return ['ok' => true, 'session' => $res['body']];
    }
    if ($res['status'] === 0 || $res['status'] >= 500) {
        error_log('Supabase login request failed with status ' . $res['status']);
        return ['ok' => false, 'error' => 'network'];
    }
    if ($res['status'] === 429) {
        return ['ok' => false, 'error' => 'rate_limited'];
    }
    $code = $res['body']['error_code'] ?? '';
    $msg  = strtolower((string) ($res['body']['msg'] ?? $res['body']['error_description'] ?? ''));
    if ($code === 'email_not_confirmed' || str_contains($msg, 'email not confirmed')) {
        return ['ok' => false, 'error' => 'email_not_confirmed'];
    }
    return ['ok' => false, 'error' => 'invalid'];
}

function supabase_refresh(string $refreshToken): ?array
{
    if (config()['mock_auth']) {
        return null;
    }
    $res = supabase_request('POST', '/auth/v1/token?grant_type=refresh_token', ['refresh_token' => $refreshToken]);
    if ($res['status'] === 0 || $res['status'] >= 500) {
        throw new SupabaseUnavailable('Supabase unreachable');
    }
    return ($res['status'] === 200 && isset($res['body']['access_token'])) ? $res['body'] : null;
}

/** Verify an access token with the server. Returns the user, or null if the token is invalid. */
function supabase_get_user(string $accessToken): ?array
{
    if (config()['mock_auth']) {
        return $accessToken === 'mock-token' ? ($_SESSION['auth']['user'] ?? null) : null;
    }
    $res = supabase_request('GET', '/auth/v1/user', null, $accessToken);
    if ($res['status'] === 0 || $res['status'] >= 500) {
        throw new SupabaseUnavailable('Supabase unreachable');
    }
    return $res['status'] === 200 ? $res['body'] : null;
}

function supabase_logout(string $accessToken): void
{
    if (config()['mock_auth']) {
        return;
    }
    supabase_request('POST', '/auth/v1/logout', null, $accessToken);
}

// ---------- Session helpers ----------

function store_auth(array $session): void
{
    $_SESSION['auth'] = [
        'access_token'  => $session['access_token'],
        'refresh_token' => $session['refresh_token'] ?? null,
        'expires_at'    => time() + (int) ($session['expires_in'] ?? 3600),
        'user'          => [
            'id'    => $session['user']['id'] ?? null,
            'email' => $session['user']['email'] ?? null,
        ],
    ];
}

/** Cheap check (no network call): is there a stored session? Good enough to skip the login page. */
function has_auth(): bool
{
    return !empty($_SESSION['auth']['access_token']);
}

/**
 * Verify the logged-in user with Supabase (use this to protect pages).
 * Refreshes an expired access token automatically.
 * Returns the user array, or null if not logged in. Throws SupabaseUnavailable on network problems.
 */
function current_user(): ?array
{
    if (!has_auth()) {
        return null;
    }
    $auth = $_SESSION['auth'];

    // Access tokens last ~1 hour; refresh them shortly before they expire.
    if ($auth['expires_at'] - 30 < time()) {
        $fresh = $auth['refresh_token'] ? supabase_refresh($auth['refresh_token']) : null;
        if ($fresh === null) {
            unset($_SESSION['auth']);
            return null;
        }
        store_auth($fresh);
        $auth = $_SESSION['auth'];
    }

    $user = supabase_get_user($auth['access_token']);
    if ($user === null) {
        unset($_SESSION['auth']);
    }
    return $user;
}

// ---------- Test mode (config 'mock_auth') ----------

function mock_login(string $email, string $password): array
{
    usleep(400_000);
    if ($email === 'offline@unifit.nl') {
        return ['ok' => false, 'error' => 'network'];
    }
    if ($email === 'limit@unifit.nl') {
        return ['ok' => false, 'error' => 'rate_limited'];
    }
    if ($email === 'test@unifit.nl' && $password === 'Test1234!') {
        return ['ok' => true, 'session' => [
            'access_token'  => 'mock-token',
            'refresh_token' => null,
            'expires_in'    => 3600,
            'user'          => ['id' => 'mock-user', 'email' => $email],
        ]];
    }
    return ['ok' => false, 'error' => 'invalid'];
}
