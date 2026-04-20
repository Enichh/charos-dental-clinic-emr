<?php declare(strict_types=1); ?>
<div class="appointments-container">
    <div class="page-header">
        <h1>Schedule New Appointment</h1>
        <a href="/appointments" class="btn btn-secondary">Back to List</a>
    </div>

    <form method="POST" action="/api/appointments" class="appointment-form">
        <div class="form-group">
            <label for="patient_id">Patient ID</label>
            <input type="number" id="patient_id" name="patient_id" required>
        </div>
        <div class="form-group">
            <label for="dentist_id">Dentist ID</label>
            <input type="number" id="dentist_id" name="dentist_id" required>
        </div>
        <div class="form-group">
            <label for="scheduled_datetime">Date & Time</label>
            <input type="datetime-local" id="scheduled_datetime" name="scheduled_datetime" required>
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Schedule Appointment</button>
    </form>
</div>
