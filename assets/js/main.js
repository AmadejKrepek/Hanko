(() => {
    const toggle = document.querySelector("[data-nav-toggle]");
    const nav = document.querySelector("[data-nav]");
    const overlay = document.querySelector("[data-nav-overlay]");

    const setOpen = (open) => {
        if (!nav || !toggle) return;
        nav.classList.toggle("is-open", open);
        document.body.classList.toggle("nav-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        toggle.setAttribute("aria-label", open ? "Zapri meni" : "Odpri meni");
        if (overlay) overlay.hidden = !open;
    };

    if (toggle && nav) {
        toggle.addEventListener("click", () => setOpen(!nav.classList.contains("is-open")));
        overlay?.addEventListener("click", () => setOpen(false));
        nav.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => setOpen(false));
        });
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") setOpen(false);
        });
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
