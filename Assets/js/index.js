/* Assets/js/index.js */
document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. Premium Showcase Slider
    // ==========================================
    const sSlides = document.querySelectorAll(".showcase-slide");
    const sDots = document.querySelectorAll(".s-dot");
    const sPrevBtn = document.getElementById("showcase-prev");
    const sNextBtn = document.getElementById("showcase-next");
    
    if (sSlides.length > 0) {
        let currentSSlide = 0;
        let sInterval;
        
        function updateShowcase(index) {
            sSlides.forEach(slide => slide.classList.remove("active"));
            sDots.forEach(dot => dot.classList.remove("active"));
            
            if (index >= sSlides.length) currentSSlide = 0;
            else if (index < 0) currentSSlide = sSlides.length - 1;
            else currentSSlide = index;
            
            sSlides[currentSSlide].classList.add("active");
            if(sDots[currentSSlide]) sDots[currentSSlide].classList.add("active");
        }
        
        function sNext() { updateShowcase(currentSSlide + 1); }
        function sPrev() { updateShowcase(currentSSlide - 1); }
        
        function startSTimer() {
            if(sSlides.length > 1) sInterval = setInterval(sNext, 5000);
        }
        function resetSTimer() {
            clearInterval(sInterval);
            startSTimer();
        }
        
        if (sNextBtn) sNextBtn.addEventListener("click", () => { sNext(); resetSTimer(); });
        if (sPrevBtn) sPrevBtn.addEventListener("click", () => { sPrev(); resetSTimer(); });
        
        sDots.forEach((dot, idx) => {
            dot.addEventListener("click", () => {
                updateShowcase(idx);
                resetSTimer();
            });
        });
        
        startSTimer();
    }
    
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
