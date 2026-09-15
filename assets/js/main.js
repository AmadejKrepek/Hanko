(() => {
    const toggle = document.querySelector("[data-nav-toggle]");
    const nav = document.querySelector("[data-nav]");
    if (toggle && nav) {
        toggle.addEventListener("click", () => nav.classList.toggle("is-open"));
    }

    const checkIn = document.querySelector("input[name='check_in']");
    const checkOut = document.querySelector("input[name='check_out']");
    if (checkIn && checkOut) {
        checkIn.addEventListener("change", () => {
            if (checkIn.value) {
                const next = new Date(checkIn.value);
                next.setDate(next.getDate() + 1);
                checkOut.min = next.toISOString().slice(0, 10);
                if (checkOut.value && checkOut.value <= checkIn.value) {
                    checkOut.value = checkOut.min;
                }
            }
        });
    }
})();
