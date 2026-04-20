<?php

declare(strict_types=1); ?>
<?php $basePath = $_ENV['BASE_PATH'] ?? ''; ?>
<div class="auth-layout">
    <div class="auth-card">
        <div class="logo text-center mb-4">
            <svg viewBox="0 0 24 24" width="60" height="60" fill="var(--primary-blue)">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
            </svg>
        </div>

        <h1>Verify Email</h1>

        <div class="alert alert-info mb-4">
            <strong>Check your email</strong><br>
            We've sent a 6-digit verification code to <strong id="email-display"></strong>
        </div>

        <div id="verify-error" class="alert alert-error d-none"></div>
        <div id="verify-success" class="alert alert-success d-none"></div>

        <form id="verify-form-element">
            <input type="hidden" id="verify-type" name="type" value="">
            <input type="hidden" id="verify-email" name="email" value="">
            <input type="hidden" id="verify-password" name="password" value="">
            <input type="hidden" id="verify-name" name="name" value="">

            <div class="form-group">
                <label for="verify-code">Verification Code</label>
                <input type="text" id="verify-code" name="code" placeholder="Enter 6-digit code" required maxlength="6" pattern="[0-9]{6}">
                <small class="text-light" style="display: block; margin-top: 5px;">Enter the 6-digit code sent to your email</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="verify-submit-btn">Verify</button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-light">Didn't receive the code?</p>
            <button type="button" class="btn btn-secondary mt-2" id="resend-btn">Resend Code</button>
        </div>

        <p class="auth-footer mt-4">
            <a href="<?= $basePath ?>/auth/login" class="text-primary">Back to Login</a>
        </p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch CSRF token and wait for it before enabling form
        let csrfToken = '';
        let csrfTokenLoaded = false;
        const basePath = '<?= $basePath ?>';

        // Disable form initially
        document.getElementById('verify-form-element').disabled = true;

        fetch(basePath + '/api/csrf-token')
            .then(response => response.json())
            .then(data => {
                csrfToken = data.csrf_token;
                csrfTokenLoaded = true;
                // Enable form after token is loaded
                document.getElementById('verify-form-element').disabled = false;
            })
            .catch(error => {
                console.error('Failed to fetch CSRF token:', error);
                showAlert('verify-error', 'Failed to load security token. Please refresh the page.');
            });

        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type') || 'signup';
        const email = urlParams.get('email') || '';
        const password = urlParams.get('password') || '';
        const name = urlParams.get('name') || '';

        // Set form values
        document.getElementById('verify-type').value = type;
        document.getElementById('verify-email').value = email;
        document.getElementById('verify-password').value = password;
        document.getElementById('verify-name').value = name;
        document.getElementById('email-display').textContent = email;

        // Alert helpers
        function showAlert(elementId, message) {
            const alert = document.getElementById(elementId);
            alert.textContent = message;
            alert.classList.remove('d-none');
        }

        function hideAlerts() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.classList.add('d-none');
            });
        }

        // Verify form handling
        const verifyForm = document.getElementById('verify-form-element');

        verifyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlerts();

            if (!csrfTokenLoaded) {
                showAlert('verify-error', 'Security token not loaded. Please wait and try again.');
                return;
            }

            const type = document.getElementById('verify-type').value;
            const email = document.getElementById('verify-email').value;
            const code = document.getElementById('verify-code').value;
            const password = document.getElementById('verify-password').value;
            const name = document.getElementById('verify-name').value;

            const endpoint = type === 'signup' ? basePath + '/api/auth/verify-signup' : basePath + '/api/auth/verify-login';
            const body = type === 'signup' ? {
                email,
                code,
                password,
                name,
                csrf_token: csrfToken
            } : {
                email,
                code,
                csrf_token: csrfToken
            };

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (response.ok) {
                    showAlert('verify-success', data.message);

                    if (type === 'signup') {
                        setTimeout(() => {
                            window.location.href = basePath + '/patient/dashboard';
                        }, 2000);
                    } else {
                        setTimeout(() => {
                            window.location.href = basePath + '/patient/dashboard';
                        }, 1500);
                    }
                } else {
                    showAlert('verify-error', data.error || 'Invalid verification code');
                }
            } catch (error) {
                showAlert('verify-error', 'Network error. Please try again.');
            }
        });

        // Resend code handling
        const resendBtn = document.getElementById('resend-btn');

        resendBtn.addEventListener('click', async function() {
            hideAlerts();

            if (!csrfTokenLoaded) {
                showAlert('verify-error', 'Security token not loaded. Please wait and try again.');
                return;
            }

            const type = document.getElementById('verify-type').value;
            const email = document.getElementById('verify-email').value;
            const password = document.getElementById('verify-password').value;
            const name = document.getElementById('verify-name').value;

            const endpoint = type === 'signup' ? basePath + '/api/auth/send-signup-code' : basePath + '/api/auth/send-login-code';
            const body = type === 'signup' ? {
                email,
                password,
                name,
                csrf_token: csrfToken
            } : {
                email,
                csrf_token: csrfToken
            };

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (response.ok) {
                    showAlert('verify-success', 'Verification code resent successfully!');
                } else {
                    showAlert('verify-error', data.error || 'Failed to resend code');
                }
            } catch (error) {
                showAlert('verify-error', 'Network error. Please try again.');
            }
        });
    });
</script>