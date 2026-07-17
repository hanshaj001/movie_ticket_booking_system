/**
 * Admin Booking Monitoring - Cancel Seats Modal & AJAX
 */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cancelModal');
    const overlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('modalClose');
    const cancelForm = document.getElementById('cancelForm');
    const submitBtn = document.getElementById('cancelSubmitBtn');
    const selectAllCheckbox = document.getElementById('selectAllSeats');

    // Open modal
    document.querySelectorAll('.btn-cancel-seats-admin').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            
            // Set booking id in hidden field
            document.getElementById('cancelBookingId').value = bookingId;

            // Show/hide seats for this booking
            document.querySelectorAll('.modal-seat-group').forEach(function(group) {
                group.style.display = group.dataset.bookingId === bookingId ? 'block' : 'none';
            });

            // Reset checkboxes
            const visibleGroup = document.querySelector('.modal-seat-group[data-booking-id="' + bookingId + '"]');
            if (visibleGroup) {
                visibleGroup.querySelectorAll('.seat-checkbox:not(:disabled)').forEach(function(cb) {
                    cb.checked = false;
                });
            }

            // Reset select all
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('btn-disabled');

            modal.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // Select All toggle
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const visibleGroup = document.querySelector('.modal-seat-group[style*="display: block"]');
            if (!visibleGroup) return;
            const checkboxes = visibleGroup.querySelectorAll('.seat-checkbox:not(:disabled)');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateSubmitState();
        });
    }

    // Individual checkbox change
    document.querySelectorAll('.seat-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateSubmitState);
    });

    function updateSubmitState() {
        const visibleGroup = document.querySelector('.modal-seat-group[style*="display: block"]');
        if (!visibleGroup) return;
        const checked = visibleGroup.querySelectorAll('.seat-checkbox:checked');
        submitBtn.disabled = checked.length === 0;
    }

    // Submit cancellation
    if (cancelForm) {
        cancelForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const bookingId = document.getElementById('cancelBookingId').value;
            const visibleGroup = document.querySelector('.modal-seat-group[style*="display: block"]');
            if (!visibleGroup) return;

            const checked = visibleGroup.querySelectorAll('.seat-checkbox:checked');
            if (checked.length === 0) {
                showToast('Please select at least one seat to cancel.', 'error');
                return;
            }

            const seatNames = [];
            checked.forEach(function(cb) { seatNames.push(cb.dataset.seatName); });

            const confirmMsg = 'Are you sure you want to cancel seat' + (seatNames.length > 1 ? 's' : '') + ' ' + seatNames.join(', ') + ' for this customer?';
            if (!confirm(confirmMsg)) return;

            // Build form data
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            checked.forEach(function(cb) {
                formData.append('seat_ids[]', cb.value);
            });

            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelling...';

            fetch('cancel_seats_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    showToast(data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Cancel Selected Seats';
                }
            })
            .catch(function() {
                showToast('Something went wrong. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Cancel Selected Seats';
            });
        });
    }

    // Toast notification
    function showToast(message, type) {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-notification toast-' + type;
        toast.innerHTML = '<span>' + message + '</span>';
        document.body.appendChild(toast);

        setTimeout(function() { toast.classList.add('show'); }, 10);
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 300);
        }, 3500);
    }
});
