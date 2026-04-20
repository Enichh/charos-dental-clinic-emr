<?php declare(strict_types=1); ?>
<div class="appointments-container">
    <div class="page-header">
        <h1>Appointments</h1>
        <a href="/appointments/create" class="btn btn-primary">Schedule New Appointment</a>
    </div>

    <?php if (empty($appointments)): ?>
    <p class="no-data">No appointments found.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Patient</th>
                <th>Dentist</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
            <tr>
                <td><?= $appointment->getScheduledDateTime()->format('Y-m-d H:i') ?></td>
                <td><?= htmlspecialchars($appointment->getPatientId()) ?></td>
                <td><?= htmlspecialchars($appointment->getDentistId()) ?></td>
                <td>
                    <span class="status status-<?= strtolower($appointment->getStatus()->value) ?>">
                        <?= $appointment->getStatus()->value ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($appointment->getNotes() ?? '-') ?></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $appointment->getId() ?>)">Cancel</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
