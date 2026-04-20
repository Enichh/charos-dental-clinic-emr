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

        <h1>Two-Factor Authentication</h1>

        <div class="alert alert-info mb-4">
            <strong>Verification Required</strong><br>
            We've sent a 6-digit verification code to <strong id="email-display"></strong><br>
            Enter the code below to complete your login.
        </div>

        <div id="verify-error" class="alert alert-error d-none"></div>
        <div id="verify-success" class="alert alert-success d-none"></div>

        <form id="verify-form-element">
            <input type="hidden" id="verify-type" name="type" value="">
            <input type="hidden" id="verify-email" name="email" value="">
            <input type="hidden" id="verify-password" name="password" value="">
            <input type="hidden" id="verify-first-name" name="first_name" value="">
            <input type="hidden" id="verify-last-name" name="last_name" value="">
            <input type="hidden" id="verify-date-of-birth" name="date_of_birth" value="">
            <input type="hidden" id="verify-gender" name="gender" value="">
            <input type="hidden" id="verify-phone-number" name="phone_number" value="">
            <input type="hidden" id="verify-address" name="address" value="">
            <input type="hidden" id="verify-blood-type" name="blood_type" value="">
            <input type="hidden" id="verify-allergies" name="allergies" value="">
            <input type="hidden" id="verify-csrf-token" name="csrf_token" value="">

            <div class="form-group">
                <label for="verify-code">Verification Code</label>
                <input type="text" id="verify-code" name="code" placeholder="123456" required maxlength="6" pattern="[0-9]{6}" style="letter-spacing: 0.5em; text-align: center; font-size: 1.5em; font-weight: bold;">
                <small class="text-light" style="display: block; margin-top: 5px;">Enter the 6-digit code sent to your email</small>
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="verify-submit-btn">Verify & Login</button>
        </form>

        <div class="mt-4 text-center">
            <p class="text-light">Didn't receive the code?</p>
            <button type="button" class="btn btn-secondary mt-2" id="resend-btn">Resend Code</button>
            <p class="text-light mt-3" style="font-size: 0.9em;">
                Code expires in 15 minutes
            </p>
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
        const firstName = urlParams.get('first_name') || '';
        const lastName = urlParams.get('last_name') || '';
        const dateOfBirth = urlParams.get('date_of_birth') || '';
        const gender = urlParams.get('gender') || '';
        const phoneNumber = urlParams.get('phone_number') || '';
        const address = urlParams.get('address') || '';
        const bloodType = urlParams.get('blood_type') || '';
        const allergies = urlParams.get('allergies') || '';

        // Set form values
        document.getElementById('verify-type').value = type;
        document.getElementById('verify-email').value = email;
        document.getElementById('verify-password').value = password;
        document.getElementById('verify-first-name').value = firstName;
        document.getElementById('verify-last-name').value = lastName;
        document.getElementById('verify-date-of-birth').value = dateOfBirth;
        document.getElementById('verify-gender').value = gender;
        document.getElementById('verify-phone-number').value = phoneNumber;
        document.getElementById('verify-address').value = address;
        document.getElementById('verify-blood-type').value = bloodType;
        document.getElementById('verify-allergies').value = allergies;
        document.getElementById('verify-csrf-token').value = csrfToken;
        document.getElementById('email-display').textContent = email;

        // Update page title based on type
        const pageTitle = type === 'signup' ? 'Verify Email - Registration' : 'Two-Factor Authentication - Login';
        document.title = pageTitle;

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

        // Auto-verify when 6 digits are entered
        const codeInput = document.getElementById('verify-code');
        codeInput.addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;

            // Auto-submit when 6 digits are entered
            if (value.length === 6) {
                verifyForm.dispatchEvent(new Event('submit'));
            }
        });

        // Verify form handling
        const verifyForm = document.getElementById('verify-form-element');

        verifyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlerts();

            if (!csrfTokenLoaded) {
                showAlert('verify-error', 'Security token not loaded. Please wait and try again.');
                return;
            }

            const submitBtn = document.getElementById('verify-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';

            const type = document.getElementById('verify-type').value;
            const email = document.getElementById('verify-email').value;
            const code = document.getElementById('verify-code').value;
            const password = document.getElementById('verify-password').value;
            const firstName = document.getElementById('verify-first-name').value;
            const lastName = document.getElementById('verify-last-name').value;
            const dateOfBirth = document.getElementById('verify-date-of-birth').value;
            const gender = document.getElementById('verify-gender').value;
            const phoneNumber = document.getElementById('verify-phone-number').value;
            const address = document.getElementById('verify-address').value;
            const bloodType = document.getElementById('verify-blood-type').value;
            const allergies = document.getElementById('verify-allergies').value;

            const endpoint = type === 'signup' ? basePath + '/api/auth/verify-signup' : basePath + '/api/auth/verify-login';
            const body = type === 'signup' ? {
                email,
                code,
                password,
                first_name: firstName,
                last_name: lastName,
                date_of_birth: dateOfBirth,
                gender,
                phone_number: phoneNumber,
                address,
                blood_type: bloodType,
                allergies,
                csrf_token: csrfToken
            } : {
                email,
                code,
                password,
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

                    // Redirect to patient dashboard (admin dashboard not implemented yet)
                    setTimeout(() => {
                        window.location.href = basePath + '/patient/dashboard';
                    }, 1500);
                } else {
                    showAlert('verify-error', data.error || 'Invalid verification code');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Verify & Login';
                }
            } catch (error) {
                console.error('Verification error:', error);
                showAlert('verify-error', 'Network error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Verify & Login';
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

            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';

            const type = document.getElementById('verify-type').value;
            const email = document.getElementById('verify-email').value;
            const password = document.getElementById('verify-password').value;
            const firstName = document.getElementById('verify-first-name').value;
            const lastName = document.getElementById('verify-last-name').value;
            const dateOfBirth = document.getElementById('verify-date-of-birth').value;
            const gender = document.getElementById('verify-gender').value;
            const phoneNumber = document.getElementById('verify-phone-number').value;
            const address = document.getElementById('verify-address').value;
            const bloodType = document.getElementById('verify-blood-type').value;
            const allergies = document.getElementById('verify-allergies').value;

            const endpoint = type === 'signup' ? basePath + '/api/auth/send-signup-code' : basePath + '/api/auth/send-login-code';
            const body = type === 'signup' ? {
                email,
                password,
                first_name: firstName,
                last_name: lastName,
                date_of_birth: dateOfBirth,
                gender,
                phone_number: phoneNumber,
                address,
                blood_type: bloodType,
                allergies,
                csrf_token: csrfToken
            } : {
                email,
                password,
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
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend Code';
                } else {
                    showAlert('verify-error', data.error || 'Failed to resend code');
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend Code';
                }
            } catch (error) {
                showAlert('verify-error', 'Network error. Please try again.');
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend Code';
            }
        });
    });
</script>