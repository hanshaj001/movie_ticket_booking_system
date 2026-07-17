/* Assets/js/index.js */
document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. Dynamic Hero Carousel Logic
    // ==========================================
    const slides = document.querySelectorAll(".carousel-slide");
    const dots = document.querySelectorAll(".indicator-dot");
    const prevBtn = document.getElementById("carousel-prev");
    const nextBtn = document.getElementById("carousel-next");
    
    let currentSlide = 0;
    let carouselInterval;
    
    function showSlide(index) {
        if (slides.length === 0) return;
        
        // Boundaries checks
        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;
        
        // Remove active states
        slides.forEach(slide => slide.classList.remove("active-slide"));
        dots.forEach(dot => dot.classList.remove("active-dot"));
        
        // Set active states
        slides[currentSlide].classList.add("active-slide");
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add("active-dot");
        }
    }
    
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    function prevSlide() {
        showSlide(currentSlide - 1);
    }
    
    // Start automated cycle
    function startCarousel() {
        if (slides.length > 1) {
            carouselInterval = setInterval(nextSlide, 5000);
        }
    }
    
    function resetCarouselTimer() {
        clearInterval(carouselInterval);
        startCarousel();
    }
    
    // Event listeners for controls
    if (nextBtn) {
        nextBtn.addEventListener("click", () => {
            nextSlide();
            resetCarouselTimer();
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
            prevSlide();
            resetCarouselTimer();
        });
    }
    
    // Dot indicators clicks
    dots.forEach((dot, idx) => {
        dot.addEventListener("click", () => {
            showSlide(idx);
            resetCarouselTimer();
        });
    });
    
    startCarousel();

    // ==========================================
    // 2. Click behavior for CTA Button (Smooth scroll)
    // ==========================================
    document.addEventListener("click", function(e) {
        // Target Buy Ticket button or link pointing to #movies-section
        const target = e.target.closest("a[href='#movies-section']");
        if (target) {
            e.preventDefault();
            const targetSection = document.querySelector("#movies-section");
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: "smooth"
                });
            }
        }
    });

    // ==========================================
    // 3. AJAX Date Tab Switcher
    // ==========================================
    const dateTabs = document.querySelectorAll(".date-tab-link");
    const gridContainer = document.getElementById("now-showing-grid");
    
    dateTabs.forEach(tab => {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            const dateVal = this.getAttribute("data-date");
            
            // 1. Highlight clicked tab
            dateTabs.forEach(t => t.classList.remove("selected-date-tab"));
            this.classList.add("selected-date-tab");
            
            // 2. Fetch movies grid HTML via AJAX
            if (gridContainer) {
                gridContainer.style.opacity = "0.4";
                gridContainer.style.transition = "opacity 0.2s ease";
                
                fetch(`index.php?ajax=1&date=${encodeURIComponent(dateVal)}`)
                    .then(response => {
                        if (!response.ok) throw new Error("HTTP error " + response.status);
                        return response.text();
                    })
                    .then(html => {
                        gridContainer.innerHTML = html;
                        gridContainer.style.opacity = "1";
                        
                        // 3. Update URL in browser query string without reloading
                        history.pushState(null, '', `index.php?date=${encodeURIComponent(dateVal)}`);
                    })
                    .catch(err => {
                        console.error("AJAX movie fetch failed: ", err);
                        gridContainer.innerHTML = `
                            <div class="empty-showtimes-placeholder">
                                <i class="fa-solid fa-triangle-exclamation empty-icon"></i>
                                <h4>Connection Error</h4>
                                <p>Failed to load showtimes. Please try refreshing the page.</p>
                            </div>
                        `;
                        gridContainer.style.opacity = "1";
                    });
            }
        });
    });

    // ==========================================
    // 4. Trailer Modal Popups
    // ==========================================
    const modal = document.getElementById("trailerModal");
    const closeBtn = document.getElementById("closeModal");
    
    document.addEventListener("click", function(e) {
        const trailerBtn = e.target.closest(".play-trailer-btn");
        if (trailerBtn) {
            if (modal) {
                modal.classList.add("open");
            }
        }
    });
    
    if (closeBtn && modal) {
        closeBtn.addEventListener("click", () => {
            modal.classList.remove("open");
        });
        
        // Close modal when clicking outside content area
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.remove("open");
            }
        });
    }
});
