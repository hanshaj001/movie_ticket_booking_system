document.addEventListener("DOMContentLoaded", function(){

    const cards =
    document.querySelectorAll(".movie-card");

    cards.forEach(function(card){

        card.addEventListener("mouseenter", function(){

            card.style.transition = "0.3s";

        });

    });

});