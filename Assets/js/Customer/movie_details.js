/**
 * MOVIE DETAILS PAGE - CLIENT SCRIPTS
 * Enhanced for better UX
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

    // Filter cards with smooth transitions
    document.querySelectorAll('.showtime-row-card').forEach(card => {
        const cardFormat = card.getAttribute('data-format');
        if (format === 'all' || cardFormat === format) {
            card.style.display = 'block';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
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
                shareMsg.style.animation = 'fadeIn 0.3s ease';
                
                // Hide message after 2.5 seconds
                setTimeout(() => {
                    shareMsg.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        shareMsg.style.display = 'none';
                        shareMsg.style.animation = '';
                    }, 300);
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

    // Add smooth hover effects to showtime cards
    const showtimeCards = document.querySelectorAll('.showtime-row-card:not(.card-soldout)');
    showtimeCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            // Optional: Add extra visual feedback
        });
        card.addEventListener('mouseleave', () => {
            // Optional: Reset visual feedback
        });
    });

    // Add fade-in animations for cards when they come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const fadeInObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all showtime cards
    document.querySelectorAll('.showtime-row-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        fadeInObserver.observe(card);
    });

    // Add similar movie cards animation
    document.querySelectorAll('.similar-movie-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
        fadeInObserver.observe(card);
    });
});

// Add CSS keyframes for animations via JavaScript (to keep everything in one place)
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);
