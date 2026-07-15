document.addEventListener("DOMContentLoaded", function(){

    const phone = document.getElementById("phone");

    phone.addEventListener("input", function(){

        this.value = this.value.replace(/[^0-9]/g, '');

    });

});