<?php

declare(strict_types=1); ?>
<div class="patient-dashboard">
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1>Patient Dashboard</h1>
                <p class="text-light">Welcome back, <?= $patientName ?? 'Patient' ?></p>
            </div>
            <button class="btn btn-danger" id="logout-btn">Logout</button>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Patient Profile Card -->
        <div class="card">
            <div class="card-header">
                <h2>Profile Information</h2>
            </div>
            <div class="card-body">
                <div class="profile-section">
                    <div class="profile-avatar">
                        <svg viewBox="0 0 24 24" width="80" height="80" fill="var(--primary-blue)">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                    </div>
                    <div class="profile-details">
                        <p><strong>Name:</strong> <?= $patientName ?? 'Not set' ?></p>
                        <p><strong>Email:</strong> <?= $patientEmail ?? 'Not set' ?></p>
                        <p><strong>Phone:</strong> <?= $patientPhone ?? 'Not provided' ?></p>
                        <p><strong>Date of Birth:</strong> <?= $patientDob ?? 'Not provided' ?></p>
                        <p><strong>Blood Type:</strong> <?= $bloodType ?? 'Not provided' ?></p>
                    </div>
                </div>
                <button class="btn btn-secondary mt-4">Edit Profile</button>
            </div>
        </div>

        <!-- Upcoming Appointments Card -->
        <div class="card">
            <div class="card-header">
                <h2>Upcoming Appointments</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($upcomingAppointments)): ?>
                    <div class="appointment-list">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-date">
                                    <span class="date-day"><?= date('d', strtotime($appointment['date'])) ?></span>
                                    <span class="date-month"><?= date('M', strtotime($appointment['date'])) ?></span>
                                </div>
                                <div class="appointment-details">
                                    <h3><?= $appointment['type'] ?? 'General Checkup' ?></h3>
                                    <p class="text-light"><?= date('g:i A', strtotime($appointment['time'])) ?> - <?= $appointment['duration'] ?? '30 mins' ?></p>
                                    <p class="text-light">Dr. <?= $appointment['doctor'] ?? 'Not assigned' ?></p>
                                </div>
                                <div class="appointment-actions">
                                    <button class="btn btn-secondary btn-sm">Reschedule</button>
                                    <button class="btn btn-danger btn-sm">Cancel</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-light">No upcoming appointments</p>
                    <button class="btn btn-primary btn-full mt-4">Book Appointment</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Prescriptions Card -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Prescriptions</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($recentPrescriptions)): ?>
                    <div class="prescription-list">
                        <?php foreach ($recentPrescriptions as $prescription): ?>
                            <div class="prescription-item">
                                <div class="prescription-icon">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="var(--primary-blue)">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z" />
                                    </svg>
                                </div>
                                <div class="prescription-details">
                                    <h3><?= $prescription['medication'] ?? 'Medication' ?></h3>
                                    <p class="text-light"><?= $prescription['dosage'] ?? 'Not specified' ?></p>
                                    <p class="text-light">Prescribed: <?= date('M d, Y', strtotime($prescription['date'])) ?></p>
                                </div>
                                <div class="prescription-status">
                                    <span class="badge badge-<?= $prescription['status'] ?? 'active' ?>">
                                        <?= $prescription['status'] ?? 'Active' ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-light">No recent prescriptions</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="/appointments/create" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                        </svg>
                        <span>Book Appointment</span>
                    </a>
                    <a href="/prescriptions" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-4v4h-4v-4H6v-4h4V6h4v4h4v4z" />
                        </svg>
                        <span>View Prescriptions</span>
                    </a>
                    <a href="/medical-records" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z" />
                        </svg>
                        <span>Medical Records</span>
                    </a>
                    <a href="/settings" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L5.09 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
                        </svg>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Health Summary Card -->
        <div class="card">
            <div class="card-header">
                <h2>Health Summary</h2>
            </div>
            <div class="card-body">
                <div class="health-summary">
                    <div class="health-item">
                        <span class="health-label">Allergies</span>
                        <span class="health-value"><?= $allergies ?? 'None reported' ?></span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">Blood Type</span>
                        <span class="health-value"><?= $bloodType ?? 'Not provided' ?></span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">Last Visit</span>
                        <span class="health-value"><?= $lastVisit ?? 'No visits recorded' ?></span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">Upcoming Visit</span>
                        <span class="health-value"><?= $nextVisit ?? 'None scheduled' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('logout-btn');

        logoutBtn.addEventListener('click', async function() {
            if (confirm('Are you sure you want to logout?')) {
                try {
                    const response = await fetch('/api/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.ok) {
                        window.location.href = '/auth/login';
                    } else {
                        alert('Logout failed. Please try again.');
                    }
                } catch (error) {
                    alert('Network error. Please try again.');
                }
            }
        });
    });
</script>