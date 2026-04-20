<?php

declare(strict_types=1); ?>
<div class="appointment-status-container">
    <div class="page-header">
        <h1>Appointment Details</h1>
        <a href="/patient/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="appointment-details-card">
        <div class="card">
            <div class="card-header">
                <h2>Appointment Information</h2>
                <span class="status status-<?= strtolower($appointment->status) ?>"><?= ucfirst($appointment->status) ?></span>
            </div>
            <div class="card-body">
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment->appointmentDate) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment->startTime) ?> - <?= htmlspecialchars($appointment->endTime) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dentist ID:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment->adminId) ?></span>
                </div>
                <?php if ($appointment->notes): ?>
                    <div class="detail-row">
                        <span class="detail-label">Notes:</span>
                        <span class="detail-value"><?= htmlspecialchars($appointment->notes) ?></span>
                    </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Booked On:</span>
                    <span class="detail-value"><?= htmlspecialchars($appointment->createdAt) ?></span>
                </div>
                <?php if ($appointment->updatedAt !== $appointment->createdAt): ?>
                    <div class="detail-row">
                        <span class="detail-label">Last Updated:</span>
                        <span class="detail-value"><?= htmlspecialchars($appointment->updatedAt) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($appointment->status === 'cancelled'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Cancellation Details</h2>
                </div>
                <div class="card-body">
                    <?php if ($appointment->cancelledBy): ?>
                        <div class="detail-row">
                            <span class="detail-label">Cancelled By:</span>
                            <span class="detail-value"><?= htmlspecialchars($appointment->cancelledBy) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($appointment->cancellationReason): ?>
                        <div class="detail-row">
                            <span class="detail-label">Reason:</span>
                            <span class="detail-value"><?= htmlspecialchars($appointment->cancellationReason) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($appointment->status === 'pending' || $appointment->status === 'confirmed'): ?>
            <div class="card-actions">
                <button class="btn btn-danger" onclick="cancelAppointment(<?= $appointment->appointmentId ?>)">Cancel Appointment</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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
                    window.location.href = '/patient/dashboard';
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

<style>
    .appointment-status-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .appointment-details-card {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .detail-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: bold;
        width: 150px;
        color: var(--text-dark);
    }

    .detail-value {
        flex: 1;
        color: var(--text-light);
    }

    .status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-pending {
        background: #e3f2fd;
        color: #1976d2;
    }

    .status-confirmed {
        background: #c8e6c9;
        color: #388e3c;
    }

    .status-cancelled {
        background: #ffcdd2;
        color: #d32f2f;
    }

    .status-completed {
        background: #e1f5fe;
        color: #0288d1;
    }

    .status-no_show {
        background: #fff3e0;
        color: #f57c00;
    }

    .card-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
</style>