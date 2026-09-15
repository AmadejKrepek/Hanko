(() => {
    const monthNames = ["januar", "februar", "marec", "april", "maj", "junij", "julij", "avgust", "september", "oktober", "november", "december"];
    const dayNames = ["pon", "tor", "sre", "čet", "pet", "sob", "ned"];

    const api = (path) => `${window.HANKO?.baseUrl || ""}/${path}`.replace(/\/+/g, "/").replace(":/", "://");

    const iso = (d) => {
        const z = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
        return z.toISOString().slice(0, 10);
    };

    const parseIso = (value) => {
        const [y, m, d] = value.split("-").map(Number);
        return new Date(y, m - 1, d);
    };

    const nightsBetween = (a, b) => Math.round((parseIso(b) - parseIso(a)) / 86400000);

    const money = (n) => `${Number(n).toFixed(2).replace(".", ",")} €`;

    const inRange = (day, start, end) => start && end && day >= start && day < end;

    async function fetchRanges(apartmentId) {
        const from = iso(new Date());
        const toDate = new Date();
        toDate.setMonth(toDate.getMonth() + 8);
        const url = `${api("api/availability.php")}?apartment_id=${apartmentId}&from=${from}&to=${iso(toDate)}`;
        const res = await fetch(url);
        const data = await res.json();
        return data.ranges || [];
    }

    function isBooked(day, ranges) {
        return ranges.some((range) => day >= range.check_in && day < range.check_out);
    }

    function rangeFree(start, end, ranges) {
        if (!start || !end) return false;
        let cursor = parseIso(start);
        const last = parseIso(end);
        while (cursor < last) {
            if (isBooked(iso(cursor), ranges)) return false;
            cursor.setDate(cursor.getDate() + 1);
        }
        return true;
    }

    function renderCalendar(root, state) {
        const year = state.view.getFullYear();
        const month = state.view.getMonth();
        const first = new Date(year, month, 1);
        const startOffset = (first.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const nav = document.createElement("div");
        nav.className = "cal-nav";
        nav.innerHTML = `<button type="button" data-prev aria-label="Prejšnji mesec">‹</button>
            <strong>${monthNames[month]} ${year}</strong>
            <button type="button" data-next aria-label="Naslednji mesec">›</button>`;

        const grid = document.createElement("div");
        grid.className = "cal-grid";
        dayNames.forEach((name) => {
            const el = document.createElement("span");
            el.textContent = name;
            grid.appendChild(el);
        });
        for (let i = 0; i < startOffset; i += 1) {
            grid.appendChild(document.createElement("span"));
        }

        const today = iso(new Date());
        for (let day = 1; day <= daysInMonth; day += 1) {
            const value = iso(new Date(year, month, day));
            const btn = document.createElement("button");
            btn.type = "button";
            btn.textContent = String(day);
            const booked = isBooked(value, state.ranges) || value < today;
            if (booked) {
                btn.disabled = true;
                btn.classList.add("is-booked");
            }
            if (value === state.checkIn) btn.classList.add("is-in");
            if (value === state.checkOut) btn.classList.add("is-in");
            if (state.checkIn && state.checkOut && inRange(value, state.checkIn, state.checkOut)) {
                btn.classList.add("is-range");
            }
            btn.addEventListener("click", () => {
                if (!state.checkIn || (state.checkIn && state.checkOut)) {
                    state.checkIn = value;
                    state.checkOut = "";
                } else if (value <= state.checkIn) {
                    state.checkIn = value;
                    state.checkOut = "";
                } else if (!rangeFree(state.checkIn, value, state.ranges)) {
                    state.checkIn = value;
                    state.checkOut = "";
                } else {
                    state.checkOut = value;
                }
                syncForm(root, state);
                paint(root, state);
            });
            grid.appendChild(btn);
        }

        root.replaceChildren(nav, grid);
        nav.querySelector("[data-prev]").addEventListener("click", () => {
            state.view.setMonth(state.view.getMonth() - 1);
            paint(root, state);
        });
        nav.querySelector("[data-next]").addEventListener("click", () => {
            state.view.setMonth(state.view.getMonth() + 1);
            paint(root, state);
        });
    }

    function paint(root, state) {
        renderCalendar(root, state);
    }

    function syncForm(root, state) {
        const form = root.closest(".panel, article, section")?.querySelector("[data-booking-form]");
        if (!form) return;
        const inInput = form.querySelector("[data-check-in]");
        const outInput = form.querySelector("[data-check-out]");
        const quote = form.querySelector("[data-quote]");
        if (inInput) inInput.value = state.checkIn;
        if (outInput) outInput.value = state.checkOut;
        if (!quote) return;
        if (!state.checkIn || !state.checkOut) {
            quote.textContent = "Izberite prihod in odhod na koledarju.";
            return;
        }
        const nights = nightsBetween(state.checkIn, state.checkOut);
        const price = Number(root.dataset.price || 0);
        const cleaning = Number(root.dataset.cleaning || 0);
        const total = nights * price + cleaning;
        quote.textContent = `${nights} nočitev · ${money(nights * price)} + čiščenje ${money(cleaning)} = ${money(total)}`;
    }

    async function initCalendar(el) {
        const state = {
            view: new Date(),
            checkIn: el.dataset.checkIn || "",
            checkOut: el.dataset.checkOut || "",
            ranges: [],
        };
        try {
            state.ranges = await fetchRanges(el.dataset.apartment);
        } catch (error) {
            state.ranges = [];
        }
        paint(el, state);
        syncForm(el, state);
    }

    async function submitBooking(form) {
        const status = form.querySelector("[data-form-status]");
        const payload = Object.fromEntries(new FormData(form).entries());
        payload.csrf = window.HANKO.csrf;
        payload.guests = Number(payload.guests || 1);
        payload.apartment_id = Number(payload.apartment_id);
        status.hidden = false;
        status.className = "form-status";
        status.textContent = "Pošiljam rezervacijo…";
        const res = await fetch(api("api/book.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.ok) {
            status.classList.add("is-error");
            status.textContent = data.error || "Rezervacija ni uspela.";
            return;
        }
        window.location.href = data.redirect;
    }

    document.querySelectorAll("[data-calendar]").forEach(initCalendar);
    document.querySelectorAll("[data-booking-form]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            const checkIn = form.querySelector("[data-check-in]")?.value;
            const checkOut = form.querySelector("[data-check-out]")?.value;
            const status = form.querySelector("[data-form-status]");
            if (!checkIn || !checkOut) {
                status.hidden = false;
                status.className = "form-status is-error";
                status.textContent = "Najprej izberite dneve na koledarju.";
                return;
            }
            submitBooking(form);
        });
    });
})();
