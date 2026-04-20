<?php

declare(strict_types=1); ?>
<div class="patient-profile-container">
    <div class="page-header">
        <h1>My Profile</h1>
        <a href="/patient/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="profile-form-card">
        <form id="profile-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($patient->getFirstName()) ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($patient->getLastName()) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($patient->getPhoneNumber() ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="male" <?= $patient->getGender()->value === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $patient->getGender()->value === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="other" <?= $patient->getGender()->value === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?= $patient->getDateOfBirth() ? $patient->getDateOfBirth()->format('Y-m-d') : '' ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?= htmlspecialchars($patient->getAddress() ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="/patient/dashboard" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profile-form');

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                phone: document.getElementById('phone').value,
                gender: document.getElementById('gender').value,
                date_of_birth: document.getElementById('date_of_birth').value,
                address: document.getElementById('address').value,
                csrf_token: '<?= $csrfToken ?>'
            };

            try {
                const response = await fetch('/api/patient/profile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok) {
                    alert('Profile updated successfully!');
                    window.location.href = '/patient/dashboard';
                } else {
                    alert('Failed to update profile: ' + (data.error || data.errors ? JSON.stringify(data.errors) : 'Unknown error'));
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        });
    });
</script>

<style>
    .patient-profile-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    .profile-form-card {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: var(--text-dark);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .form-group input:disabled {
        background: #f5f5f5;
        cursor: not-allowed;
    }

    .form-group small {
        display: block;
        margin-top: 5px;
        color: var(--text-light);
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 30px;
    }
</style>