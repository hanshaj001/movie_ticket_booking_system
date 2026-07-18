// Assets/js/global_loader.js

document.addEventListener("DOMContentLoaded", function() {
    // 1. Create the overlay HTML and append it to body
    const overlay = document.createElement('div');
    overlay.id = 'global-loader-overlay';
    overlay.innerHTML = `
        <div class="loader-spinner"></div>
        <div class="loader-text" id="global-loader-text">Processing... Please wait.</div>
    `;
    document.body.appendChild(overlay);

    const loaderTextElem = document.getElementById('global-loader-text');

    // 2. Attach submit event listener to all forms on the page
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Skip forms explicitly marked to bypass the loader
        if (form.hasAttribute('data-no-loader')) return;
        
        form.addEventListener('submit', function(e) {
            // Wait for all other submit listeners (like custom validation) to fire first
            setTimeout(() => {
                // If another script called preventDefault (e.g. client side validation failed), DO NOT show the loader!
                if (e.defaultPrevented) return;

                // Check for custom loading message
                const customMsg = form.getAttribute('data-loader-msg');
                if (customMsg) {
                    loaderTextElem.textContent = customMsg;
                } else {
                    loaderTextElem.textContent = "Processing... Please wait.";
                }
                
                // Activate the overlay
                overlay.classList.add('active');
                
                // Prevent double-clicking
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
            }, 0);
        });
    });
});
