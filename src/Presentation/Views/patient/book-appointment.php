<?php

declare(strict_types=1); ?>
<div class="book-appointment-container">
    <div class="page-header">
        <h1>Book New Appointment</h1>
        <a href="/patient/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="appointment-booking-form">
        <form id="appointment-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="admin_id">Select Dentist</label>
                <select id="admin_id" name="admin_id" required>
                    <option value="">-- Select a Dentist --</option>
                    <option value="1">Dr. Charos</option>
                </select>
            </div>

            <div class="form-group">
                <label for="appointment_date">Select Date</label>
                <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group" id="time-slots-container" style="display: none;">
                <label>Available Time Slots</label>
                <div id="time-slots" class="time-slots-grid">
                    <p class="text-light">Select a dentist and date to see available slots</p>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Any specific concerns or requirements..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Schedule Appointment</button>
        </form>
    </div>

    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const adminSelect = document.getElementById('admin_id');
        const dateInput = document.getElementById('appointment_date');
        const timeSlotsContainer = document.getElementById('time-slots-container');
        const timeSlotsDiv = document.getElementById('time-slots');
        const form = document.getElementById('appointment-form');
        const submitBtn = document.getElementById('submit-btn');
        const loadingOverlay = document.getElementById('loading-overlay');

        let selectedTimeSlot = null;

        function showLoading() {
            loadingOverlay.style.display = 'flex';
        }

        function hideLoading() {
            loadingOverlay.style.display = 'none';
        }

        async function loadAvailableSlots() {
            const adminId = adminSelect.value;
            const date = dateInput.value;

            if (!adminId || !date) {
                timeSlotsContainer.style.display = 'none';
                return;
            }

            showLoading();
            timeSlotsDiv.innerHTML = '<p class="text-light">Loading available slots...</p>';
            timeSlotsContainer.style.display = 'block';

            try {
                const response = await fetch('/api/patient/appointments/available-slots', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        admin_id: parseInt(adminId),
                        date: date,
                        csrf_token: '<?= $csrfToken ?>'
                    })
                });

                const data = await response.json();

                if (response.ok && data.slots && data.slots.length > 0) {
                    timeSlotsDiv.innerHTML = '';

                    data.slots.forEach(slot => {
                        const slotButton = document.createElement('button');
                        slotButton.type = 'button';
                        slotButton.className = 'time-slot-btn';
                        slotButton.textContent = `${slot.start_time} - ${slot.end_time}`;
                        slotButton.disabled = !slot.is_available;

                        if (!slot.is_available) {
                            slotButton.classList.add('unavailable');
                        } else {
                            slotButton.addEventListener('click', function() {
                                document.querySelectorAll('.time-slot-btn').forEach(btn => btn.classList.remove('selected'));
                                slotButton.classList.add('selected');
                                selectedTimeSlot = slot;
                                submitBtn.disabled = false;
                            });
                        }

                        timeSlotsDiv.appendChild(slotButton);
                    });
                } else {
                    timeSlotsDiv.innerHTML = '<p class="text-light">No available slots for the selected date</p>';
                    submitBtn.disabled = true;
                }
            } catch (error) {
                timeSlotsDiv.innerHTML = '<p class="text-light">Error loading available slots. Please try again.</p>';
                submitBtn.disabled = true;
            } finally {
                hideLoading();
            }
        }

        adminSelect.addEventListener('change', loadAvailableSlots);
        dateInput.addEventListener('change', loadAvailableSlots);

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!selectedTimeSlot) {
                alert('Please select a time slot');
                return;
            }

            showLoading();

            const formData = {
                admin_id: parseInt(adminSelect.value),
                patient_id: <?= $patient->getId() ?>,
                appointment_date: dateInput.value,
                start_time: selectedTimeSlot.start_time,
                end_time: selectedTimeSlot.end_time,
                notes: document.getElementById('notes').value,
                csrf_token: '<?= $csrfToken ?>'
            };

            try {
                const response = await fetch('/api/patient/appointments/schedule', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok) {
                    alert('Appointment scheduled successfully!');
                    window.location.href = '/patient/dashboard';
                } else {
                    alert('Failed to schedule appointment: ' + (data.error || data.errors ? JSON.stringify(data.errors) : 'Unknown error'));
                }
            } catch (error) {
                alert('Network error. Please try again.');
            } finally {
                hideLoading();
            }
        });
    });
</script>

<style>
    .book-appointment-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .appointment-booking-form {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .time-slots-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .time-slot-btn {
        padding: 10px;
        border: 2px solid var(--primary-blue);
        background: white;
        color: var(--primary-blue);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .time-slot-btn:hover:not(:disabled) {
        background: var(--primary-blue);
        color: white;
    }

    .time-slot-btn.selected {
        background: var(--primary-blue);
        color: white;
    }

    .time-slot-btn.unavailable {
        border-color: #ccc;
        color: #ccc;
        cursor: not-allowed;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary-blue);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loading-overlay p {
        color: white;
        margin-top: 20px;
    }
</style>