<?php declare(strict_types=1); ?>
<div class="auth-container">
    <div class="auth-card">
        <h1>Login</h1>
        <form method="POST" action="/api/auth/login">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="auth-footer">
            Don't have an account? <a href="/auth/register">Register</a>
        </p>
    </div>
</div>
