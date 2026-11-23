<?php
// src/guard.php
// Server-side brute-force protection (the lockout from the flowchart).
// Counters are stored per (visitor IP + email) in storage/guard/, outside the web root,
// so clearing cookies or browser storage does NOT reset them.
//
// Why IP + email and not just one of them:
//  - email only: an attacker could lock a real user out of their account on purpose.
//  - IP only: everyone on a shared network (e.g. school Wi-Fi) would be locked out together.

declare(strict_types=1);

function guard_dir(): string
{
    $dir = rtrim(config()['storage_dir'], '/') . '/guard';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create $dir, check permissions.");
    }
    return $dir;
}

function guard_path(string $email): string
{
    return guard_dir() . '/' . hash('sha256', client_ip() . '|' . strtolower($email)) . '.json';
}

/** Seconds left in a running lockout for this email (0 = not locked). Read-only, used for display. */
function guard_seconds_left(string $email): int
{
    $raw = @file_get_contents(guard_path($email));
    $state = $raw ? json_decode($raw, true) : null;
    return max(0, (int) ($state['locked_until'] ?? 0) - time());
}

/**
 * Run a login attempt under the lockout rules.
 * The counter file stays locked for the whole attempt, so firing many requests
 * in parallel can't sneak past the counter.
 *
 * Returns the result of supabase_login(), or ['ok' => false, 'error' => 'locked', 'seconds' => N].
 */
function guarded_login(string $email, string $password): array
{
    $sec = config()['security'];
    $path = guard_path($email);
    $fh = fopen($path, 'c+');
    if ($fh === false) {
        throw new RuntimeException("Cannot open $path, check permissions.");
    }

    try {
        flock($fh, LOCK_EX);
        $state = json_decode((string) stream_get_contents($fh), true);
        if (!is_array($state)) {
            $state = ['tries' => 0, 'locked_until' => 0];
        }

        $now = time();
        if ($state['locked_until'] > $now) {
            return ['ok' => false, 'error' => 'locked', 'seconds' => $state['locked_until'] - $now];
        }

        $result = supabase_login($email, $password);

        if ($result['ok']) {
            // Successful login resets the counter (Flowchart: Reset counter).
            $state = ['tries' => 0, 'locked_until' => 0];
        } elseif ($result['error'] === 'invalid') {
            // Only wrong credentials count; network errors and server rate limits don't.
            $state['tries']++;
            $max = $sec['max_tries'];
            error_log(sprintf('Unifit login: incorrect attempt %d (lockout after %d) from %s', $state['tries'], $max, client_ip()));

            // The counter keeps going up until a successful login, so after the first lockout
            // every wrong try triggers a new, longer lockout:
            // 3rd wrong try = BASE, 4th = BASE + INCREMENT, ... capped at MAX.
            if ($state['tries'] >= $max) {
                $timeout = min(
                    $sec['base_timeout_sec'] + $sec['timeout_increment_sec'] * ($state['tries'] - $max),
                    $sec['max_timeout_sec'],
                );
                $state['locked_until'] = $now + $timeout;
                $result = ['ok' => false, 'error' => 'locked', 'seconds' => $timeout];
            }
        }

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($state));
        fflush($fh);
        return $result;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
        guard_prune();
    }
}

/** Now and then, delete counters nobody has touched for a day. */
function guard_prune(): void
{
    if (random_int(1, 100) !== 1) {
        return;
    }
    foreach (glob(guard_dir() . '/*.json') ?: [] as $file) {
        if (filemtime($file) < time() - 86400) {
            @unlink($file);
        }
    }
}
