(() => {
    const wizard = document.querySelector("[data-transfer-wizard]");
    if (!wizard) return;

    const api = (path) => `${window.HANKO?.baseUrl || ""}/${path}`.replace(/\/+/g, "/").replace(":/", "://");
    const money = (n) => `${Number(n).toFixed(2).replace(".", ",")} €`;
    const step1 = wizard.querySelector('[data-step="1"]');
    const step2 = wizard.querySelector('[data-step="2"]');
    const step3 = wizard.querySelector('[data-step="3"]');
    const labels = wizard.querySelectorAll("[data-step-label]");
    const returnFields = wizard.querySelectorAll(".return-fields");
    const state = {};

    const show = (n) => {
        [step1, step2, step3].forEach((el, i) => {
            el.hidden = i !== n - 1;
        });
        labels.forEach((el, i) => el.classList.toggle("is-active", i === n - 1));
    };

    const directionInputs = step1.querySelectorAll("input[name='direction']");
    const syncDirection = () => {
        const direction = step1.querySelector("input[name='direction']:checked")?.value;
        returnFields.forEach((el) => {
            el.hidden = direction !== "round_trip";
        });
    };
    directionInputs.forEach((input) => input.addEventListener("change", syncDirection));
    syncDirection();

    const collectSearch = () => Object.fromEntries(new FormData(step1).entries());

    step1.addEventListener("submit", async (event) => {
        event.preventDefault();
        const payload = collectSearch();
        payload.quote_only = true;
        payload.vehicle_type = "shared";
        payload.csrf = window.HANKO.csrf;
        payload.passengers = Number(payload.passengers || 1);
        const res = await fetch(api("api/transfer.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.ok) {
            alert(data.error || "Iskanje ni uspelo.");
            return;
        }
        Object.assign(state, payload, { quotes: data.quotes, route: data.route });
        wizard.querySelector("[data-offer-summary]").textContent =
            `${data.route.name} · ${payload.date_outbound} ob ${payload.time_outbound} · ${payload.passengers} potnikov`;
        wizard.querySelector("[data-shared-price]").textContent = money(data.quotes.shared.price);
        wizard.querySelector("[data-private-price]").textContent = money(data.quotes.private.price);
        show(2);
    });

    wizard.querySelectorAll("[data-vehicle]").forEach((btn) => {
        btn.addEventListener("click", () => {
            state.vehicle_type = btn.dataset.vehicle;
            const quote = state.quotes?.[state.vehicle_type];
            const label = state.vehicle_type === "private" ? "Zasebni prevoz" : "Skupni prevoz";
            wizard.querySelector("[data-final-quote]").textContent =
                `${label} · ${state.route?.name || ""} · ${money(quote?.price || 0)}`;
            show(3);
        });
    });

    wizard.querySelectorAll("[data-back]").forEach((btn) => {
        btn.addEventListener("click", () => {
            if (!step3.hidden) show(2);
            else show(1);
        });
    });

    step3.addEventListener("submit", async (event) => {
        event.preventDefault();
        const status = step3.querySelector("[data-form-status]");
        const guest = Object.fromEntries(new FormData(step3).entries());
        const payload = { ...state, ...guest, csrf: window.HANKO.csrf, quote_only: false };
        delete payload.quotes;
        delete payload.route;
        status.hidden = false;
        status.className = "form-status";
        status.textContent = "Naročam prevoz…";
        const res = await fetch(api("api/transfer.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!data.ok) {
            status.classList.add("is-error");
            status.textContent = data.error || "Naročilo ni uspelo.";
            return;
        }
        window.location.href = data.redirect;
    });
})();
