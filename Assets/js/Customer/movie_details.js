/**
 * MOVIE DETAILS PAGE - CLIENT SCRIPTS
 * Enhanced for better UX and Mobile Performance
 */

// Image fallback: called from onerror on <img> tags
function showFallback(imgElement) {
    imgElement.style.display = 'none';
    const fallback = imgElement.nextElementSibling;
    if (fallback) fallback.style.display = 'flex';
}

// Format filter: show/hide showtime cards by data-format attribute
function filterShows(format, btn) {
    // Update active button
    document.querySelectorAll('.format-filter-btn').forEach(b => b.classList.remove('active-filter'));
    btn.classList.add('active-filter');

    // Filter cards with smooth transitions without breaking mobile layout flow
    document.querySelectorAll('.showtime-row-card').forEach(card => {
        const cardFormat = card.getAttribute('data-format');
        if (format === 'all' || cardFormat === format) {
            card.style.display = 'flex'; // Use flex instead of block for the card layout
            setTimeout(() => {
                card.style.opacity = '1';
            }, 50);
        } else {
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300); // match standard transition timing
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Smooth scroll: Book Tickets → Show Times section
    const bookBtn = document.getElementById('bookTicketsButton');
    if (bookBtn) {
        bookBtn.addEventListener('click', () => {
            const target = document.getElementById('dateSelectionSection');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // Share button: copy current page URL to clipboard with better feedback
    const shareBtn = document.getElementById('shareBtn');
    const shareMsg = document.getElementById('shareMsg');
    if (shareBtn && shareMsg) {
        shareBtn.addEventListener('click', async () => {
            const url = window.location.href;
            
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                } else {
                    // Fallback for older browsers
                    const tmp = document.createElement('input');
                    tmp.value = url;
                    document.body.appendChild(tmp);
                    tmp.select();
                    document.execCommand('copy');
                    document.body.removeChild(tmp);
                }
                
                // Show success message
                shareMsg.style.display = 'inline-flex';
                
                // Hide message after 2.5 seconds
                setTimeout(() => {
                    shareMsg.style.display = 'none';
                }, 2500);
                
            } catch (err) {
                console.error('Failed to copy URL:', err);
                alert('Failed to copy URL. Please try again.');
            }
        });
    }

    // Seat booking links: validate show_id before navigating
    document.querySelectorAll('.seat-booking-link').forEach(link => {
        link.addEventListener('click', e => {
            const showId = parseInt(link.getAttribute('data-show-id'), 10);
            if (isNaN(showId) || showId <= 0) {
                e.preventDefault();
                alert('Invalid Show Selected. Please refresh the page and try again.');
            }
        });
    });

    // Mobile friendly intersection observer (only fades in opacity, prevents layout shifts)
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '50px' // Load slightly before it comes into view to prevent blank areas on fast scroll
    };

    const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                // Unobserve after fading in for better performance
                fadeInObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Only apply animations if user prefers motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion) {
        // Observe all showtime cards
        document.querySelectorAll('.showtime-row-card, .similar-movie-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transition = 'opacity 0.6s ease';
            fadeInObserver.observe(card);
        });
    }
});
