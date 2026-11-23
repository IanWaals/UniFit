// js/lockout.js
// Purely cosmetic: counts down the lockout shown on the login page and re-enables the form.
// The real lockout is enforced by the server (src/guard.php), so editing this does nothing.

const form = document.getElementById('login-form');
const status = document.getElementById('form-status');
let secondsLeft = Number(form?.dataset.lockoutSeconds || 0);

if (form && status && secondsLeft > 0) {
    const fields = form.querySelectorAll('input:not([type=hidden]), button');
    const timer = setInterval(() => {
        secondsLeft--;
        if (secondsLeft > 0) {
            status.textContent = `Too many incorrect login attempts. Timeout reached! Please try again in ${secondsLeft} seconds.`;
            return;
        }
        clearInterval(timer);
        fields.forEach((field) => { field.disabled = false; });
        status.textContent = 'You can try to log in again.';
        status.className = 'info';
        document.getElementById('password')?.focus();
    }, 1000);
}
