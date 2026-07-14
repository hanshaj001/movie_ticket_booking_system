function confirmDelete(seatId) {
    if (confirm("Are you sure you want to delete this seat?")) {
        // Find the current screen_id from the URL to return to the same page
        const urlParams = new URLSearchParams(window.location.search);
        const screenId = urlParams.get('screen_id');
        window.location.href = `manage_seats.php?action=delete&id=${seatId}&screen_id=${screenId}`;
    }
}

function openEditModal(seatId, seatType, seatNum) {
    document.getElementById('modalSeatId').value = seatId;
    document.getElementById('modalSeatType').value = seatType;
    document.getElementById('modalSeatNum').textContent = seatNum;
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal if clicking outside the content
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
