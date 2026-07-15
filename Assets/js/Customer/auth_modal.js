/* Lightweight modal behavior separated from seat_selection.js
   Exposes `showAuthModal()` globally and keeps DOM wiring minimal. */
(function () {
    function init() {
        const modal = document.getElementById('authModal');
        if (!modal) return;

        const loginBtn = modal.querySelector('.auth-login');
        const registerBtn = modal.querySelector('.auth-register');
        const closeBtn = modal.querySelector('.auth-close');

        // Local paths relative to Customer/seat_selection.php
        if (loginBtn) loginBtn.addEventListener('click', () => { const redirect = encodeURIComponent(window.location.pathname + window.location.search); window.location.href = `../login.php?redirect=${redirect}`; });
        if (registerBtn) registerBtn.addEventListener('click', () => { const redirect = encodeURIComponent(window.location.pathname + window.location.search); window.location.href = `register.php?redirect=${redirect}`; });
        if (closeBtn) closeBtn.addEventListener('click', () => hide());

        modal.addEventListener('click', (ev) => { if (ev.target === modal) hide(); });
    }

    function show() {
        const modal = document.getElementById('authModal');
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
    }

    function hide() {
        const modal = document.getElementById('authModal');
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
    }

    window.showAuthModal = show;
    window.hideAuthModal = hide;
    document.addEventListener('DOMContentLoaded', init);
})();
