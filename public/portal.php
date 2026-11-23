<?php
// public/portal.php
// Protected page: only users with a valid Supabase session may see it.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/supabase.php';

start_session();
send_security_headers();

try {
    // Validates the token with the Supabase server (not just the local session).
    $user = current_user();
} catch (SupabaseUnavailable) {
    http_response_code(503);
    exit('Could not reach the login server. Please try again later.');
}

if ($user === null) {
    redirect('login.php');
}

$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Unifit – Portal</title>
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    </head>

    <body>
        <main id="portal">
            <h1>Welcome to Unifit</h1>
            <?php if ($flash): ?>
            <p class="<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
            <?php endif; ?>
            <p>Signed in as <strong id="user-email"><?= e((string) ($user['email'] ?? '')) ?></strong></p>

            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" id="logout-btn">Log out</button>
            </form>
        </main>
    </body>

</html>
