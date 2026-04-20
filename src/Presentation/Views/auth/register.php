<?php

declare(strict_types=1); ?>
<?php $basePath = $_ENV['BASE_PATH'] ?? ''; ?>
<div class="auth-layout">
    <div class="auth-card">
        <div class="logo text-center mb-4">
            <svg viewBox="0 0 24 24" width="60" height="60" fill="var(--primary-blue)">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
            </svg>
        </div>

        <h1>Register</h1>

        <div class="tabs">
            <div class="tab active" data-tab="login">Login</div>
            <div class="tab" data-tab="register">Register</div>
        </div>

        <!-- Login Form -->
        <div class="tab-content active" id="login-form">
            <div id="login-error" class="alert alert-error d-none"></div>
            <div id="login-success" class="alert alert-success d-none"></div>

            <form id="login-form-element">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required minlength="8">
                    <small class="text-light" style="display: block; margin-top: 5px;">Minimum 8 characters</small>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="login-submit-btn">Login</button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="#" class="switch-tab" data-tab="register">Register</a>
            </p>
        </div>

        <!-- Register Form -->
        <div class="tab-content" id="register-form">
            <div id="register-error" class="alert alert-error d-none"></div>
            <div id="register-success" class="alert alert-success d-none"></div>

            <form id="register-form-element">
                <div class="form-group">
                    <label for="register-first-name">First Name <span class="text-danger">*</span></label>
                    <input type="text" id="register-first-name" name="first_name" required minlength="2">
                </div>

                <div class="form-group">
                    <label for="register-last-name">Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="register-last-name" name="last_name" required minlength="2">
                </div>

                <div class="form-group">
                    <label for="register-email">Email <span class="text-danger">*</span></label>
                    <input type="email" id="register-email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="register-password">Password <span class="text-danger">*</span></label>
                    <input type="password" id="register-password" name="password" required minlength="8">
                    <small class="text-light" style="display: block; margin-top: 5px;">Minimum 8 characters</small>
                </div>

                <div class="form-group">
                    <label for="register-date-of-birth">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" id="register-date-of-birth" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label for="register-gender">Gender <span class="text-danger">*</span></label>
                    <select id="register-gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="register-phone-number">Phone Number <span class="text-light">(Optional)</span></label>
                    <input type="tel" id="register-phone-number" name="phone_number">
                </div>

                <div class="form-group">
                    <label for="register-address">Address <span class="text-light">(Optional)</span></label>
                    <textarea id="register-address" name="address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="register-blood-type">Blood Type <span class="text-light">(Optional)</span></label>
                    <select id="register-blood-type" name="blood_type">
                        <option value="">Select Blood Type</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="register-allergies">Allergies <span class="text-light">(Optional)</span></label>
                    <textarea id="register-allergies" name="allergies" rows="2" placeholder="List any known allergies"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="register-submit-btn">Send Verification Code</button>
            </form>

            <p class="auth-footer">
                Already have an account? <a href="#" class="switch-tab" data-tab="login">Login</a>
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch CSRF token and wait for it before enabling forms
        let csrfToken = '';
        let csrfTokenLoaded = false;
        const basePath = '<?= $basePath ?>';

        // Disable forms initially
        document.getElementById('login-form-element').disabled = true;
        document.getElementById('register-form-element').disabled = true;

        fetch(basePath + '/api/csrf-token')
            .then(response => response.json())
            .then(data => {
                csrfToken = data.csrf_token;
                csrfTokenLoaded = true;
                // Enable forms after token is loaded
                document.getElementById('login-form-element').disabled = false;
                document.getElementById('register-form-element').disabled = false;
            })
            .catch(error => {
                console.error('Failed to fetch CSRF token:', error);
                showAlert('login-error', 'Failed to load security token. Please refresh the page.');
            });

        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        const switchTabLinks = document.querySelectorAll('.switch-tab');

        function switchTab(tabName) {
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.tab === tabName) {
                    tab.classList.add('active');
                }
            });

            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === tabName + '-form') {
                    content.classList.add('active');
                }
            });

            // Reset forms
            document.getElementById('login-form-element').reset();
            document.getElementById('register-form-element').reset();
            hideAlerts();
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                switchTab(this.dataset.tab);
            });
        });

        switchTabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                switchTab(this.dataset.tab);
            });
        });

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

        // Login form handling
        const loginForm = document.getElementById('login-form-element');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlerts();

            if (!csrfTokenLoaded) {
                showAlert('login-error', 'Security token not loaded. Please wait and try again.');
                return;
            }

            const submitBtn = document.getElementById('login-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            // Send login with password and redirect to verify page
            try {
                const response = await fetch(basePath + '/api/auth/send-login-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email,
                        password,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = `${basePath}/auth/verify?type=login&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`;
                } else {
                    showAlert('login-error', data.error || 'Failed to send login code');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Login';
                }
            } catch (error) {
                showAlert('login-error', 'Network error. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        });

        // Register form handling
        const registerForm = document.getElementById('register-form-element');

        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlerts();

            if (!csrfTokenLoaded) {
                showAlert('register-error', 'Security token not loaded. Please wait and try again.');
                return;
            }

            const submitBtn = document.getElementById('register-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            const firstName = document.getElementById('register-first-name').value;
            const lastName = document.getElementById('register-last-name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const dateOfBirth = document.getElementById('register-date-of-birth').value;
            const gender = document.getElementById('register-gender').value;
            const phoneNumber = document.getElementById('register-phone-number').value;
            const address = document.getElementById('register-address').value;
            const bloodType = document.getElementById('register-blood-type').value;
            const allergies = document.getElementById('register-allergies').value;

            // Send signup code and redirect to verify page
            try {
                const response = await fetch(basePath + '/api/auth/send-signup-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
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
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    const params = new URLSearchParams({
                        type: 'signup',
                        email,
                        password,
                        first_name: firstName,
                        last_name: lastName,
                        date_of_birth: dateOfBirth,
                        gender,
                        phone_number: phoneNumber,
                        address,
                        blood_type: bloodType,
                        allergies
                    });
                    window.location.href = `${basePath}/auth/verify?${params.toString()}`;
                } else {
                    showAlert('register-error', data.error || 'Failed to send verification code');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Verification Code';
                }
            } catch (error) {
                showAlert('register-error', 'Network error. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Verification Code';
            }
        });
    });
</script>