function confirmDelete(screenId) {
    if (confirm("Are you sure you want to delete this screen? This action cannot be undone and will fail if future shows exist.")) {
        window.location.href = `manage_screens.php?action=delete&id=${screenId}`;
    }
}

// Toggle dropdown menu
function toggleDropdown(id) {
    // Close any other open dropdowns
    const allDropdowns = document.querySelectorAll('.dropdown-content');
    allDropdowns.forEach(dd => {
        if (dd.id !== 'dropdown-' + id) {
            dd.classList.remove('show');
        }
    });

    document.getElementById("dropdown-" + id).classList.toggle("show");
}

// Close dropdown if clicked outside
window.onclick = function(event) {
    if (!event.target.matches('.dropdown-btn') && !event.target.matches('.dropdown-btn *')) {
        const dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
            const openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}
