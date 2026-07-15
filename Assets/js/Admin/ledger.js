document.addEventListener('DOMContentLoaded', () => {
    const movieSelect = document.getElementById('filter_movie');
    const showSelect = document.getElementById('filter_show');

    if (movieSelect && showSelect) {
        // Store all original options
        const allShows = Array.from(showSelect.options).slice(1); // skip "All Shows"
        
        // When movie changes, filter shows
        movieSelect.addEventListener('change', function() {
            const selectedMovieText = this.options[this.selectedIndex].text;
            const selectedMovieId = this.value;
            
            // Clear current options except first
            while(showSelect.options.length > 1) {
                showSelect.remove(1);
            }

            // Repopulate
            allShows.forEach(option => {
                if (!selectedMovieId || option.text.includes(selectedMovieText)) {
                    showSelect.appendChild(option.cloneNode(true));
                }
            });
        });
    }
});
