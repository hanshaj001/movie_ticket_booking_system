function confirmDelete(genreId, csrfToken = '') {
    if (confirm("Are you sure you want to delete this genre? This action cannot be undone unless it's assigned to a movie.")) {
        let url = `manage_genres.php?action=delete&id=${genreId}`;
        if (csrfToken) {
            url += `&csrf_token=${csrfToken}`;
        }
        window.location.href = url;
    }
}
