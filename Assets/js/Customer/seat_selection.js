document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.seat-checkbox');
    const selectedSeatsEl = document.getElementById('selectedSeats');
    const seatCountEl = document.getElementById('seatCount');
    const totalAmountEl = document.getElementById('totalAmount');
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    const timerCard = document.getElementById('timerCard');
    const timerValueEl = document.getElementById('timerValue');
    const confirmBtn = document.getElementById('confirmBtn');
    const bookingForm = document.getElementById('bookingForm');
    const sessionPopup = document.getElementById('sessionPopup');

    let selectedSeats = [];
    let totalPrice = 0;
    let countdownInterval = null;
    let expiryTimestamp = null;
    let refreshInterval = null;

    function init() {
        // Initialize state directly from what PHP rendered on the page
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                addSeatToState(checkbox.value, checkbox);
            }
        });

        // If PHP passed an existing session time, start the timer instantly
        if (typeof initialExpiryTime !== 'undefined' && initialExpiryTime) {
            expiryTimestamp = new Date(initialExpiryTime).getTime();
            startCountdown();
        }

        setupEventListeners();
        refreshInterval = setInterval(refreshSeatStatus, 5000);
    }

    function setupEventListeners() {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleSeatToggle);
        });

        document.getElementById('cancelBtn')?.addEventListener('click', () => {
            if (confirm('Cancel selection and return to movie details?')) {
                window.location.href = `movie_details.php?movie_id=${movieId}`;
            }
        });

        bookingForm?.addEventListener('submit', (e) => {
            if (selectedSeats.length === 0) {
                e.preventDefault();
                alert('Please select at least one seat before confirming!');
            } else {
                clearInterval(countdownInterval);
                clearInterval(refreshInterval);
            }
        });
    }

    async function handleSeatToggle(e) {
        const checkbox = e.target;
        const seatId = checkbox.value;
        const isSelected = checkbox.checked;

        if (isSelected) {
            addSeatToState(seatId, checkbox);
        } else {
            removeSeatFromState(seatId);
        }

        try {
            const formData = new FormData();
            formData.append('show_seat_id', seatId);
            formData.append('show_id', showId);
            formData.append('selected', isSelected ? '1' : '0');
            formData.append('csrf_token', csrfToken);

            const response = await fetch(`seat_selection.php?action=toggle_seat`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (isSelected && selectedSeats.length === 1 && !countdownInterval) {
                    expiryTimestamp = data.expiry_time ? new Date(data.expiry_time.replace(/-/g, "/")).getTime() : Date.now() + 300000;
                    startCountdown();
                    if (sessionPopup) {
                        sessionPopup.classList.add('show');
                        setTimeout(() => sessionPopup.classList.remove('show'), 4000);
                    }
                }
            } else {
                revertSeatState(checkbox, seatId, isSelected);
                alert(data.error || 'Seat is no longer available.');
            }
        } catch (error) {
            console.error('Error:', error);
            revertSeatState(checkbox, seatId, isSelected);
            // If an error occurs (likely not logged in), show a centered modal
            // prompting the user to Login or Register instead of a simple alert.
            showAuthModal();
        }
    }

    function revertSeatState(checkbox, seatId, wasSelected) {
        checkbox.checked = !wasSelected;
        if (wasSelected) removeSeatFromState(seatId);
        else addSeatToState(seatId, checkbox);
    }

    function addSeatToState(seatId, checkbox) {
        const id = parseInt(seatId);
        const num = checkbox.getAttribute('data-seat-number');
        const price = parseFloat(checkbox.getAttribute('data-price')) || ticketPrice;
       
        if (!selectedSeats.some(s => s.id === id)) {
            selectedSeats.push({ id, number: num, price });
            totalPrice += price;
            updateSummary();
        }
    }

    function removeSeatFromState(seatId) {
        const id = parseInt(seatId);
        const idx = selectedSeats.findIndex(s => s.id === id);
        if (idx !== -1) {
            totalPrice -= selectedSeats[idx].price;
            selectedSeats.splice(idx, 1);
            updateSummary();
        }
    }

    function updateSummary() {
        if (selectedSeats.length === 0) {
            selectedSeatsEl.textContent = 'None';
            seatCountEl.textContent = '0';
            totalAmountEl.textContent = 'Rs. 0.00';
            confirmBtn.disabled = true;
        } else {
            selectedSeatsEl.textContent = selectedSeats.map(s => s.number).join(', ');
            seatCountEl.textContent = selectedSeats.length;
            totalAmountEl.textContent = `Rs. ${totalPrice.toFixed(2)}`;
            confirmBtn.disabled = false;
        }
        if (selectedSeatsInput) {
            selectedSeatsInput.value = JSON.stringify(selectedSeats.map(s => s.id));
        }
    }

    // Auth modal markup and styles are provided by a shared partial and Assets/css/Customer/auth_modal.css
    // Behavior is handled by Assets/js/Customer/auth_modal.js which exposes `showAuthModal()` globally.

    function startCountdown() {
        if (timerCard) timerCard.style.display = 'flex';
        const updateTimer = () => {
            const diff = expiryTimestamp - Date.now();
            if (diff <= 0) {
                clearInterval(countdownInterval);
                alert("Your booking session has expired!");
                window.location.href = `movie_details.php?movie_id=${movieId}`;
                return;
            }
            const mins = Math.floor(diff / 60000);
            const secs = Math.floor((diff % 60000) / 1000);
            if (timerValueEl) timerValueEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        };
        updateTimer();
        countdownInterval = setInterval(updateTimer, 1000);
    }

    async function refreshSeatStatus() {
        try {
            const response = await fetch(`seat_selection.php?action=get_seats&show_id=${showId}`);
            const data = await response.json();
           
            if (data && Array.isArray(data.seats)) {
                data.seats.forEach(seat => {
                    const checkbox = document.querySelector(`#seat-${seat.show_seat_id}`);
                    if (!checkbox) return;
                   
                    const label = checkbox.nextElementSibling;
                    const isSelectedByMe = selectedSeats.some(s => s.id === parseInt(seat.show_seat_id));
                   
                    if (isSelectedByMe && !seat.is_locked_by_me && seat.seat_status !== 'AVAILABLE') {
                        removeSeatFromState(seat.show_seat_id);
                        checkbox.checked = false;
                        alert(`Seat ${seat.seat_number} reservation timed out or was claimed by another customer.`);
                    }

                    if (label) {
                        const type = (seat.seat_type || 'REGULAR').toLowerCase();
                        let classes = ['seat-label', type];
                        if (seat.is_locked_by_me || checkbox.checked) classes.push('selected');
                        else if (seat.seat_status === 'SOLD') classes.push('sold');
                        else if (seat.seat_status === 'LOCKED') classes.push('locked');
                        else classes.push('available');
                        label.className = classes.join(' ');
                    }

                    checkbox.disabled = (!seat.is_locked_by_me && seat.seat_status !== 'AVAILABLE');
                });
            }
        } catch (e) { console.error('Poller error:', e); }
    }

    init();
});