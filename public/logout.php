<?php
// public/logout.php
// Logs out on Supabase and destroys the PHP session. POST + CSRF only,
// so another site can't log users out with a hidden link or image.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/supabase.php';

start_session();
send_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('portal.php');
}
csrf_check();

if (has_auth()) {
    supabase_logout($_SESSION['auth']['access_token']);
}
destroy_session();
redirect('login.php');
