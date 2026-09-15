(() => {
    const apiPost = async (endpoint, payload) => {
        const res = await fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ...payload, csrf: window.HANKO.csrf }),
        });
        return res.json();
    };

    document.querySelectorAll("[data-admin-form]").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            const status = form.querySelector("[data-form-status]");
            const payload = Object.fromEntries(new FormData(form).entries());
            status.hidden = false;
            status.className = "form-status";
            status.textContent = "Shranjujem…";
            const data = await apiPost(form.dataset.endpoint, payload);
            if (!data.ok) {
                status.classList.add("is-error");
                status.textContent = data.error || "Shranjevanje ni uspelo.";
                return;
            }
            window.location.reload();
        });
    });

    document.querySelectorAll("[data-delete]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            if (!confirm("Res želite izbrisati ta vnos?")) return;
            const data = await apiPost(btn.dataset.endpoint, { action: "delete", id: Number(btn.dataset.id) });
            if (!data.ok) {
                alert(data.error || "Brisanje ni uspelo.");
                return;
            }
            btn.closest("[data-row]")?.remove();
        });
    });

    document.querySelectorAll("[data-status-select]").forEach((select) => {
        select.addEventListener("change", async () => {
            const data = await apiPost(select.dataset.endpoint, {
                action: "status",
                id: Number(select.dataset.id),
                status: select.value,
            });
            if (!data.ok) {
                alert(data.error || "Status ni shranjen.");
            }
        });
    });
})();
