# Unifit Login portal.
This repository contains the login page and login logic for the client side. Created for Unifit groups project [Unifit](https://github.com/IanWaals/UniFit) we do for our 1st year 1st semester at Fontys ICT. I am responsible for security, so not only protecting our web application against common attacks, but also creating and engineering the login logic to be as secure and safe as possible. 

# Login logic.
So the page is protected against SQL Injection attacks and password brute force attacks. Alternatively it communicates securely with our backend Supabase running on our webserver that i also manage. The page asks for your username and password. It also allows storing a cookie on browser that makes it so you dont need to login after refreshing the page and having already logged in. 

# Architecture.
<<<<<<< HEAD
Its primarily a client side application that gets served by our webserver to the browser of the client. It makes use of the supabase javascript module to handle most of the heavy lifting. It makes use of javascript and requests instead of PHP, so its also safe against the PHP2Shell attack method. 
=======
The login logic runs server side in PHP (8.1+, with the curl extension). PHP talks to the Supabase auth REST API, so the Supabase URL, key and login tokens never reach the browser: the browser only gets an HttpOnly session cookie.

- `public/` is the web root (point the webserver here). It holds `login.php`, `portal.php` and `logout.php`.
- `src/` holds the logic and config and sits outside the web root, so it can never be downloaded.
  - `config.php.template`: copy to `src/config.php` and fill in the Supabase URL and anon key.
  - `supabase.php`: login, token refresh, user verification and logout against Supabase.
  - `guard.php`: brute-force lockout, enforced on the server per IP + email, stored in `storage/`.
  - `bootstrap.php`: secure session cookies, CSRF tokens, security headers.
- `storage/` is created automatically and must be writable by PHP.
- `frontend/` is the old JavaScript-only version.

Run `./test.sh` to start a local dev server (uses podman if PHP isn't installed).
>>>>>>> e6f02c9 (PHP)
