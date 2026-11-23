<?php
// public/login.php
// Login page + login logic (flowchart states). Runs entirely on the server.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/supabase.php';
require __DIR__ . '/../src/guard.php';

start_session();
send_security_headers();

$sec = config()['security'];
const PORTAL_URL = 'portal.php';

// ---------- 1. Already logged in? (Flowchart: Valid session cookie?) ----------
if (has_auth()) {
    redirect(PORTAL_URL);
}

// ---------- 2. Form submitted: run the login logic ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = $sec['allow_session_cookies'] && !empty($_POST['kmsi']);

    $_SESSION['old_email'] = $email;
    unset($_SESSION['lockout_email']);

    if ($email === '' || $password === '' || strlen($email) > 254 || strlen($password) > 1024) {
        flash('Please put in your credentials.', 'error');
        redirect('login.php');
    }

    $result = guarded_login($email, $password);

    // 3. Error handling (Flowchart: Credentials correct? -> NO)
    if (!$result['ok']) {
        switch ($result['error']) {
            case 'locked':
                $_SESSION['lockout_email'] = $email;
                break;
            case 'network':
                flash('Could not reach the server. Please try again later.', 'error');
                break;
            case 'rate_limited':
                flash('Too many login attempts. Timeout reached! Please wait a moment and try again.', 'error');
                break;
            case 'email_not_confirmed':
                flash('Your email address has not been confirmed yet. Check your inbox.', 'error');
                break;
            default:
                // Generic message on purpose: don't reveal whether the account exists.
                flash('Wrong credentials entered or invalid user.', 'error');
        }
        redirect('login.php');
    }

    // 4. Successful login (Flowchart: Welcome back / Unlock portal / Reset counter)
    session_regenerate_id(true); // new session ID after login (prevents session fixation)
    store_auth($result['session']);
    $_SESSION['remember'] = $remember;
    set_session_cookie($remember);
    unset($_SESSION['old_email'], $_SESSION['lockout_email']);

    flash('Welcome back!', 'success');
    redirect(PORTAL_URL);
}

// ---------- Render the page ----------
$flash = take_flash();
$oldEmail = $_SESSION['old_email'] ?? '';

$lockoutSeconds = 0;
if (!empty($_SESSION['lockout_email'])) {
    $lockoutSeconds = guard_seconds_left($_SESSION['lockout_email']);
    if ($lockoutSeconds === 0) {
        unset($_SESSION['lockout_email']);
        $flash = ['message' => 'You can try to log in again.', 'type' => 'info'];
    }
}
if ($lockoutSeconds > 0) {
    $flash = [
        'message' => "Too many incorrect login attempts. Timeout reached! Please try again in {$lockoutSeconds} seconds.",
        'type'    => 'error',
    ];
}
$disabled = $lockoutSeconds > 0 ? ' disabled' : '';
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="Version" content="<?= e(config()['app_version']) ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Unifit – Log in portal</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
        <link rel="stylesheet" href="css/login-page.css">
        <!-- Optional: only makes the lockout countdown tick. The page works without JavaScript. -->
        <script src="js/lockout.js" defer></script>
    </head>

    <body>

        <main class="login-box">
            <h1 id="login-box-title">Please sign in with your Unifit account.</h1>

            <form id="login-form" method="post" action="login.php" novalidate
                  data-lockout-seconds="<?= $lockoutSeconds ?>">
                <?= csrf_field() ?>

                <div>
                    <label for="email">Username</label>
                    <input class="usernameinput-box" type="email" id="email" name="email"
                       value="<?= e($oldEmail) ?>"
                       placeholder="u123456@unifit.org" autocomplete="username" required<?= $disabled ?>>
                </div>

                <div>
                    <label for="password">Password</label>
                    <input class="password-input-box" type="password" id="password" name="password"
                       placeholder="Your password" autocomplete="current-password" required<?= $disabled ?>
                       <?= $oldEmail !== '' && !$disabled ? 'autofocus' : '' ?>>
                </div>

                <?php if ($sec['allow_session_cookies']): ?>
                <div id="kmsi-row">
                    <label for="kmsi">Keep me Signed in</label>
                    <input class="checkbox" type="checkbox" id="kmsi" name="kmsi" value="1"<?= $disabled ?>>
                </div>
                <?php endif; ?>

                <p id="form-status" role="status" aria-live="polite"
                   class="<?= e($flash['type'] ?? '') ?>"><?= e($flash['message'] ?? '') ?></p>

                <div id="submission-area">
                    <button type="submit" class="login-btn login-btn-primary"<?= $disabled ?>>Log in</button>
                    <?php if ($sec['allow_signup']): ?>
                    <a href="signup.php" id="signup-link" class="login-btn">Sign up</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($sec['allow_password_reset']): ?>
            <p id="passwd-rst-str">
                Forgot your password? Click <a href="resetpass.php">here</a> to recover it.
            </p>
            <?php endif; ?>

            <p id="copyright-str">&copy; <?= date('Y') ?> Unifit.org</p>

        </main>

    </body>

</html>
