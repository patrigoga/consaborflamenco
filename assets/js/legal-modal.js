(function () {
    "use strict";

    const modal = document.querySelector("[data-legal-modal]");
    if (!modal) {
        return;
    }

    const dialog = modal.querySelector(".legal-dialog");
    const title = modal.querySelector("[data-legal-title]");
    const date = modal.querySelector("[data-legal-date]");
    const content = modal.querySelector("[data-legal-content]");
    const fullLink = modal.querySelector("[data-legal-full]");
    const printButton = modal.querySelector("[data-legal-print]");
    const closeButtons = modal.querySelectorAll("[data-legal-close]");
    let lastFocused = null;
    let lastUrl = window.location.href;

    function focusableElements() {
        return Array.from(modal.querySelectorAll("a[href], button:not([disabled]), [tabindex]:not([tabindex='-1'])"))
            .filter((element) => !element.hasAttribute("hidden"));
    }

    function setLoading(pageUrl) {
        title.textContent = "Cargando documento";
        date.textContent = "Última actualización: pendiente";
        content.innerHTML = "<p>Cargando contenido legal...</p>";
        fullLink.href = pageUrl;
    }

    function openModal(trigger, documentKey, pageUrl) {
        lastFocused = trigger;
        lastUrl = window.location.href;
        modal.hidden = false;
        document.body.classList.add("modal-open");
        setLoading(pageUrl);

        window.requestAnimationFrame(() => {
            dialog.focus?.();
            const first = focusableElements()[0];
            if (first) {
                first.focus();
            }
        });

        if (window.history && window.history.pushState) {
            window.history.pushState({ legalDocument: documentKey }, "", pageUrl);
        }

        fetch(`api/legal-document.php?document=${encodeURIComponent(documentKey)}`, {
            headers: { "Accept": "application/json" }
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((payload) => {
                if (!payload.ok || !payload.document) {
                    throw new Error("Documento no disponible");
                }
                title.textContent = payload.document.title;
                date.textContent = `Última actualización: ${payload.document.updated_at}`;
                content.innerHTML = payload.document.content;
                fullLink.href = payload.document.url || pageUrl;
            })
            .catch(() => {
                title.textContent = "Documento no disponible";
                content.innerHTML = "<p>No se pudo cargar este documento legal. Puedes abrir la página completa o intentarlo más tarde.</p>";
            });
    }

    function closeModal(restoreHistory = true) {
        if (modal.hidden) {
            return;
        }
        modal.hidden = true;
        document.body.classList.remove("modal-open");
        if (restoreHistory && window.history && window.history.pushState && window.location.href !== lastUrl) {
            window.history.pushState(null, "", lastUrl);
        }
        if (lastFocused && typeof lastFocused.focus === "function") {
            lastFocused.focus();
        }
    }

    document.addEventListener("click", (event) => {
        const link = event.target.closest("[data-legal-document]");
        if (!link) {
            return;
        }
        const documentKey = link.getAttribute("data-legal-document");
        const href = link.getAttribute("href");
        if (!documentKey || !href) {
            return;
        }
        event.preventDefault();
        openModal(link, documentKey, href);
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", () => closeModal());
    });

    printButton?.addEventListener("click", () => window.print());

    document.addEventListener("keydown", (event) => {
        if (modal.hidden) {
            return;
        }
        if (event.key === "Escape") {
            event.preventDefault();
            closeModal();
            return;
        }
        if (event.key !== "Tab") {
            return;
        }
        const focusable = focusableElements();
        if (!focusable.length) {
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    window.addEventListener("popstate", () => {
        if (!modal.hidden) {
            closeModal(false);
        }
    });

    window.CSFLegalModal = {
        close: closeModal
    };
}());
