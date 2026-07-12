document.addEventListener("DOMContentLoaded", () => {

    const cards = document.querySelectorAll(
        ".feature-card, .facility-card, .why-box"
    );

    cards.forEach(card => {

        card.addEventListener("mouseenter", () => {
            card.style.transform = "translateY(-8px)";
        });

        card.addEventListener("mouseleave", () => {
            card.style.transform = "translateY(0)";
        });

    });

});