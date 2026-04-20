<?php

declare(strict_types=1); ?>
<div class="patient-dashboard">
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1>Patient Dashboard</h1>
                <p class="text-light">Welcome back, <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?></p>
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
                        <p><strong>Name:</strong> <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($patient->getPhoneNumber() ?? 'Not provided') ?></p>
                        <p><strong>Gender:</strong> <?= htmlspecialchars($patient->getGender()->value) ?></p>
                        <p><strong>Date of Birth:</strong> <?= $patient->getDateOfBirth() ? htmlspecialchars($patient->getDateOfBirth()->format('M d, Y')) : 'Not provided' ?></p>
                    </div>
                </div>
                <a href="/patient/profile" class="btn btn-secondary mt-4">Edit Profile</a>
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
                                    <span class="date-day"><?= $appointment->getAppointmentDate()->format('d') ?></span>
                                    <span class="date-month"><?= $appointment->getAppointmentDate()->format('M') ?></span>
                                </div>
                                <div class="appointment-details">
                                    <h3>Dental Appointment</h3>
                                    <p class="text-light"><?= $appointment->getStartTime() ?> - <?= $appointment->getEndTime() ?></p>
                                    <p class="text-light">Status: <?= htmlspecialchars($appointment->getStatus()->value) ?></p>
                                </div>
                                <div class="appointment-actions">
                                    <a href="/patient/appointment-status?id=<?= $appointment->getId() ?>" class="btn btn-secondary btn-sm">View Details</a>
                                    <button class="btn btn-danger btn-sm" onclick="cancelAppointment(<?= $appointment->getId() ?>)">Cancel</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-light">No upcoming appointments</p>
                    <a href="/patient/book-appointment" class="btn btn-primary btn-full mt-4">Book Appointment</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Appointments Card -->
        <div class="card">
            <div class="card-header">
                <h2>Past Appointments</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($pastAppointments)): ?>
                    <div class="appointment-list">
                        <?php foreach (array_slice($pastAppointments, 0, 5) as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-date">
                                    <span class="date-day"><?= $appointment->getAppointmentDate()->format('d') ?></span>
                                    <span class="date-month"><?= $appointment->getAppointmentDate()->format('M') ?></span>
                                </div>
                                <div class="appointment-details">
                                    <h3>Dental Appointment</h3>
                                    <p class="text-light"><?= $appointment->getStartTime() ?> - <?= $appointment->getEndTime() ?></p>
                                    <p class="text-light">Status: <?= htmlspecialchars($appointment->getStatus()->value) ?></p>
                                </div>
                                <div class="appointment-actions">
                                    <a href="/patient/appointment-status?id=<?= $appointment->getId() ?>" class="btn btn-secondary btn-sm">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-light">No past appointments</p>
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
                    <a href="/patient/book-appointment" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" />
                        </svg>
                        <span>Book Appointment</span>
                    </a>
                    <a href="/patient/profile" class="quick-action">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="var(--primary-blue)">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                        </svg>
                        <span>Edit Profile</span>
                    </a>
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

    async function cancelAppointment(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            try {
                const response = await fetch('/api/patient/appointments/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        csrf_token: '<?= $csrfToken ?>'
                    })
                });

                if (response.ok) {
                    alert('Appointment cancelled successfully');
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert('Failed to cancel appointment: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }
    }
</script>