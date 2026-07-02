document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.seat-checkbox');
    const selectedSeatsEl = document.getElementById('selectedSeats');
    const seatCountEl = document.getElementById('seatCount');
    const totalAmountEl = document.getElementById('totalAmount');
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    const timerCard = document.getElementById('timerCard');
    const timerValueEl = document.getElementById('timerValue');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const bookingForm = document.getElementById('bookingForm');
    const sessionPopup = document.getElementById('sessionPopup');

    let selectedSeats = [];
    let totalPrice = 0;
    let countdownInterval = null;
    let expiryTimestamp = null;
    let refreshInterval = null;
    let sessionStarted = false;
    let navigatingAway = false;

    function init() {
        // Initialize selected seats state from checked checkboxes
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                addSeatToState(checkbox.value, checkbox);
            }
        });
        
        checkExistingSession();
        setupEventListeners();
        setupNavigationWarnings();
    }

    function setupNavigationWarnings() {
        // Add beforeunload listener - shows native browser warning
        window.addEventListener('beforeunload', async (e) => {
            if (selectedSeats.length > 0 && !navigatingAway) {
                e.preventDefault();
                e.returnValue = 'You have selected seats! Are you sure you want to leave? Your selected seats will be unselected.';
                // Try to clean up in case user confirms
                try {
                    await cleanupSessionOnLeave();
                } catch (err) {
                    console.error('Error cleaning up on beforeunload:', err);
                }
                return e.returnValue;
            }
        });

        // Add warning to back to movie button
        const backBtn = document.querySelector('.btn-back');
        if (backBtn) {
            backBtn.addEventListener('click', async (e) => {
                if (selectedSeats.length > 0) {
                    e.preventDefault();
                    if (confirm('You have selected seats! Are you sure you want to leave? Your selected seats will be unselected.')) {
                        navigatingAway = true;
                        await handleCancel();
                    }
                }
            });
        }

        // Handle home button in navbar
        const navbarHomeLink = document.querySelector('a[href="home.php"]');
        if (navbarHomeLink) {
            navbarHomeLink.addEventListener('click', async (e) => {
                if (selectedSeats.length > 0) {
                    e.preventDefault();
                    if (confirm('You have selected seats! Are you sure you want to leave? Your selected seats will be unselected.')) {
                        navigatingAway = true;
                        await handleCancel();
                    }
                }
            });
        }
    }

    // Function to clean up session when leaving
    async function cleanupSessionOnLeave() {
        try {
            const formData = new FormData();
            formData.append('show_id', showId);
            await fetch('seat_selection.php?action=cancel_session', {
                method: 'POST',
                body: formData,
                keepalive: true
            });
        } catch (error) {
            console.error('Error cleaning up session on leave:', error);
        }
    }

    function showSessionPopup() {
        if (sessionPopup) {
            sessionPopup.classList.add('show');
            setTimeout(() => {
                sessionPopup.classList.remove('show');
            }, 5000); // Hide after 5 seconds
        }
    }

    function setupEventListeners() {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleSeatToggle);
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', handleCancel);
        }

        if (bookingForm) {
            bookingForm.addEventListener('submit', handleFormSubmit);
        }
    }

    async function checkExistingSession() {
        try {
            const response = await fetch(`seat_selection.php?action=check_session&show_id=${showId}`);
            const data = await response.json();
            
            if (data.has_session) {
                expiryTimestamp = new Date(data.expiry_time).getTime();
                startCountdown();
                sessionStarted = true;
            }
            // Start refresh regardless
            startSeatRefresh();
        } catch (error) {
            console.error('Error checking session:', error);
            startSeatRefresh();
        }
    }

    async function handleSeatToggle(e) {
        const checkbox = e.target;
        const seatId = checkbox.value;
        const isSelected = checkbox.checked;

        // Optimistic update
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

            const response = await fetch('seat_selection.php?action=toggle_seat', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (isSelected && selectedSeats.length === 1 && !sessionStarted) {
                    // First seat selected - start session and show popup
                    if (data.expiry_time) {
                        expiryTimestamp = new Date(data.expiry_time).getTime();
                    } else {
                        expiryTimestamp = Date.now() + (5 * 60 * 1000);
                    }
                    startCountdown();
                    sessionStarted = true;
                    showSessionPopup();
                }
            } else {
                // Revert UI on error
                if (isSelected) {
                    checkbox.checked = false;
                    removeSeatFromState(seatId);
                } else {
                    checkbox.checked = true;
                    addSeatToState(seatId, checkbox);
                }
                alert(data.error || 'Failed to update seat selection');
            }
        } catch (error) {
            console.error('Error toggling seat:', error);
            // Revert
            if (isSelected) {
                checkbox.checked = false;
                removeSeatFromState(seatId);
            } else {
                checkbox.checked = true;
                addSeatToState(seatId, checkbox);
            }
            alert('Failed to update seat selection');
        }
    }

    function addSeatToState(seatId, checkbox) {
        const seatNumber = checkbox.getAttribute('data-seat-number');
        const price = parseFloat(checkbox.getAttribute('data-price'));
        
        if (!selectedSeats.some(s => s.id === parseInt(seatId))) {
            selectedSeats.push({ id: parseInt(seatId), number: seatNumber, price: price });
            totalPrice += price;
            updateSummary();
        }
    }

    function removeSeatFromState(seatId) {
        const seatIndex = selectedSeats.findIndex(s => s.id === parseInt(seatId));
        if (seatIndex !== -1) {
            totalPrice -= selectedSeats[seatIndex].price;
            selectedSeats.splice(seatIndex, 1);
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
            const seatNumbers = selectedSeats.map(s => s.number).join(', ');
            selectedSeatsEl.textContent = seatNumbers;
            seatCountEl.textContent = selectedSeats.length;
            totalAmountEl.textContent = `Rs. ${totalPrice.toFixed(2)}`;
            confirmBtn.disabled = false;
        }
        if (selectedSeatsInput) {
            selectedSeatsInput.value = JSON.stringify(selectedSeats.map(s => s.id));
        }
    }

    function startCountdown() {
        if (timerCard) {
            timerCard.style.display = 'flex';
        }
        updateTimerDisplay();
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        countdownInterval = setInterval(updateTimerDisplay, 1000);
    }

    function updateTimerDisplay() {
        const now = Date.now();
        const diff = expiryTimestamp - now;

        if (diff <= 0) {
            clearInterval(countdownInterval);
            alert('Your booking session has expired! Please select seats again.');
            window.location.href = `movie_details.php?movie_id=${showId}`;
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        const formattedMinutes = String(minutes).padStart(2, '0');
        const formattedSeconds = String(seconds).padStart(2, '0');
        
        if (timerValueEl) {
            timerValueEl.textContent = `${formattedMinutes}:${formattedSeconds}`;
        }

        if (timerCard) {
            timerCard.classList.remove('warning', 'danger');
            if (diff <= 60000) {
                timerCard.classList.add('danger');
            } else if (diff <= 120000) {
                timerCard.classList.add('warning');
            }
        }
    }

    function startSeatRefresh() {
        refreshInterval = setInterval(refreshSeatStatus, 5000);
    }

    async function refreshSeatStatus() {
        try {
            const response = await fetch(`seat_selection.php?action=get_seats&show_id=${showId}`);
            const data = await response.json();
            
            if (data.seats) {
                data.seats.forEach(seat => {
                    const checkbox = document.querySelector(`#seat-${seat.show_seat_id}`);
                    const label = checkbox ? checkbox.nextElementSibling : null;
                    
                    if (checkbox && label) {
                        // Check if this seat was selected by us
                        const isSeatSelected = selectedSeats.some(s => s.id === seat.show_seat_id);
                        
                        if (isSeatSelected && !seat.is_locked_by_me) {
                            // Our seat was taken by someone else!
                            removeSeatFromState(seat.show_seat_id);
                            checkbox.checked = false;
                            alert(`Seat ${seat.seat_number} is no longer available!`);
                        }

                        // Update UI
                        const seatType = seat.seat_type.toLowerCase();
                        let newClass = 'seat-label ' + seatType;
                        
                        if (seat.is_locked_by_me || checkbox.checked) {
                            newClass += ' selected';
                        } else if (seat.seat_status === 'SOLD') {
                            newClass += ' sold';
                        } else if (seat.seat_status === 'LOCKED') {
                            newClass += ' locked';
                        } else {
                            newClass += ' available';
                        }

                        label.className = newClass;
                        
                        // Update disabled status
                        if (seat.is_locked_by_me || checkbox.checked) {
                            checkbox.disabled = false;
                        } else if (seat.seat_status !== 'AVAILABLE') {
                            checkbox.disabled = true;
                        } else {
                            checkbox.disabled = false;
                        }

                        checkbox.setAttribute('data-status', seat.seat_status);
                        checkbox.setAttribute('data-is-locked-by-me', seat.is_locked_by_me ? 'true' : 'false');
                    }
                });
            }
        } catch (error) {
            console.error('Error refreshing seats:', error);
        }
    }

    async function handleCancel() {
        if (confirm('Are you sure you want to cancel your seat selection?')) {
            if (selectedSeats.length > 0) {
                try {
                    const formData = new FormData();
                    formData.append('show_id', showId);
                    await fetch('seat_selection.php?action=cancel_session', {
                        method: 'POST',
                        body: formData
                    });
                } catch (error) {
                    console.error('Error canceling session:', error);
                }
            }
            window.location.href = `movie_details.php?movie_id=${showId}`;
        }
    }

    function handleFormSubmit(e) {
        if (selectedSeats.length === 0) {
            e.preventDefault();
            alert('Please select at least one seat!');
            return;
        }
        const confirmMessage = selectedSeats.length === 1 
            ? 'Are you sure you want to book this seat?' 
            : `Are you sure you want to book these ${selectedSeats.length} seats?`;
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        } else {
            navigatingAway = true;
        }
    }

    init();
});
