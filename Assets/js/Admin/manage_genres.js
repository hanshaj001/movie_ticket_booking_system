function confirmDelete(genreId) {
    if (confirm("Are you sure you want to delete this genre? This action cannot be undone unless it's assigned to a movie.")) {
        window.location.href = `manage_genres.php?action=delete&id=${genreId}`;
    }
}
