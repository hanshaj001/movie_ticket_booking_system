<?php
// Reusable auth modal partial - simple markup only. Styles and behavior live in Assets.
?>
<div id="authModal" class="auth-modal" aria-hidden="true" style="display:none;">
    <div class="auth-card">
        <button class="auth-close" aria-label="Close">&times;</button>
        <h3 class="auth-title">Login required</h3>
        <p class="auth-desc">To select a seat you must be logged in.</p>
        <div class="auth-actions">
            <button class="auth-login">Login</button>
            <button class="auth-register">Register</button>
        </div>
    </div>
</div>
