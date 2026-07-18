// Global Toast Notification System

function showToast(message, type = 'success') {
    // Dynamically create toast elements if they don't exist
    let toast = document.getElementById("popupToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "popupToast";
        toast.className = "toast-box";
        
        const icon = document.createElement("div");
        icon.id = "popupIcon";
        icon.className = "toast-icon";
        
        const text = document.createElement("div");
        text.id = "popupText";
        text.className = "toast-msg-text";
        
        toast.appendChild(icon);
        toast.appendChild(text);
        document.body.appendChild(toast);
    }

    const icon = document.getElementById("popupIcon");
    const text = document.getElementById("popupText");

    toast.className = "toast-box";
    
    if (type === 'error') {
        toast.classList.add("error");
        icon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
    } else if (type === 'warning') {
        toast.classList.add("warning");
        icon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
    } else {
        toast.classList.add("success");
        icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
    }

    text.textContent = message;
    
    // Trigger display
    setTimeout(() => {
        toast.classList.add("active");
    }, 50);

    // Auto dismiss after 5 seconds
    const timerId = setTimeout(() => {
        toast.classList.remove("active");
    }, 5000);
    
    // Store timer ID on element to allow clearing if a new toast is shown
    if (toast.dataset.timerId) {
        clearTimeout(parseInt(toast.dataset.timerId));
    }
    toast.dataset.timerId = timerId;
}

// Automatically process messages in URL and clear them to prevent refresh loops
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg') || urlParams.get('message');
    let type = urlParams.get('type') || (urlParams.get('error') ? 'error' : 'success');

    // Normalize type values from different pages (e.g., 'danger' -> 'error')
    if (type === 'danger') type = 'error';

    if (msg) {
        showToast(msg, type);
        
        // Clean URL parameters
        const newSearch = window.location.search
            .replace(/[\?\&](msg|message)=[^&]+/, '')
            .replace(/[\?\&]type=[^&]+/, '')
            .replace(/[\?\&]error=[^&]+/, '')
            .replace(/^&/, '?');
            
        const newUrl = window.location.pathname + (newSearch === '?' ? '' : newSearch);
        window.history.replaceState({}, document.title, newUrl);
    }
});
