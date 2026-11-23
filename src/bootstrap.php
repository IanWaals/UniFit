<?php
// src/bootstrap.php
// Shared setup for every page: config, session, security headers, CSRF and small helpers.

declare(strict_types=1);

function config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('Missing src/config.php. Copy src/config.php.template to src/config.php and fill it in.');
        }
        $config = require $file;
    }
    return $config;
}

// ---------- Request info ----------

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return config()['trust_proxy']
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (config()['trust_proxy'] && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // The last entry is the one added by our own proxy, so it can't be spoofed.
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $last = end($parts);
        if (filter_var($last, FILTER_VALIDATE_IP)) {
            $ip = $last;
        }
    }
    return $ip;
}

// ---------- Session ----------

function session_cookie_options(bool $remember): array
{
    $days = config()['security']['remember_me_days'];
    return [
        'expires'  => $remember ? time() + $days * 86400 : 0, // 0 = deleted when the browser closes
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,   // JavaScript can't read the cookie (protects against XSS token theft)
        'samesite' => 'Lax',  // not sent on cross-site POSTs (extra CSRF protection)
    ];
}

function set_session_cookie(bool $remember): void
{
    setcookie(session_name(), session_id(), session_cookie_options($remember));
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $sec = config()['security'];

    ini_set('session.use_strict_mode', '1'); // reject session IDs the server didn't create
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) ($sec['remember_me_days'] * 86400));

    session_name('unifit_session');
    $opts = session_cookie_options(false);
    unset($opts['expires']);
    session_set_cookie_params(['lifetime' => 0] + $opts);
    session_start();

    $remember = !empty($_SESSION['remember']);

    // Sessions without "Keep me signed in" expire after a period of inactivity.
    $lastSeen = $_SESSION['last_seen'] ?? null;
    if (!$remember && $lastSeen !== null && time() - $lastSeen > $sec['session_idle_minutes'] * 60) {
        destroy_session();
        session_start();
    }
    $_SESSION['last_seen'] = time();

    // Slide the expiry of a remembered session forward on every visit.
    if ($remember) {
        set_session_cookie(true);
    }
}

function destroy_session(): void
{
    $_SESSION = [];
    setcookie(session_name(), '', ['expires' => time() - 3600] + session_cookie_options(false));
    session_destroy();
}

// ---------- Security headers ----------

function send_security_headers(): void
{
    header("Content-Security-Policy: default-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
}

// ---------- CSRF ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(400);
        exit('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

// ---------- Helpers ----------

// Escape text for HTML output (prevents XSS).
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

// One-time status message shown on the next page load.
function flash(string $message, string $type): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
