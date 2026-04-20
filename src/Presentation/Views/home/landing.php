<?php

declare(strict_types=1); ?>
<?php $basePath = $_ENV['BASE_PATH'] ?? ''; ?>
<div class="landing-container">
    <div class="landing-content">
        <div class="logo-section">
            <img src="<?= $basePath ?>/images/Logo.png" alt="Charos Dental Clinic Logo" class="landing-logo">
        </div>

        <div class="hero-section">
            <h1 class="hero-title">Welcome to Charos Dental Clinic</h1>
            <p class="hero-subtitle">Your trusted partner for dental care management</p>
        </div>

        <div class="cta-section">
            <a href="<?= $basePath ?>/auth/login" class="btn btn-primary btn-large">Login</a>
            <a href="<?= $basePath ?>/auth/login" class="btn btn-secondary btn-large">Register</a>
        </div>

        <div class="features-section">
            <div class="feature-item">
                <div class="feature-icon">Calendar</div>
                <h3>Easy Appointments</h3>
                <p>Book and manage your dental appointments with ease</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">Prescription</div>
                <h3>Prescription Tracking</h3>
                <p>Access your prescriptions and medication history</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">Security</div>
                <h3>Secure Records</h3>
                <p>Your medical data is protected with top-tier security</p>
            </div>
        </div>
    </div>
</div>