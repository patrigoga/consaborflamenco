(function () {
    "use strict";

    const title = document.querySelector("[data-landing-title]");
    if (title) {
        window.requestAnimationFrame(() => {
            title.classList.add("is-visible");
        });
    }

    const rotator = document.querySelector("[data-landing-rotator]");
    if (rotator) {
        const lines = [
            "Más visibilidad para tu proyecto flamenco.",
            "Una comunidad para conectar talento y oportunidades.",
            "Contenido, promoción y crecimiento en una sola plataforma."
        ];
        let index = 0;

        window.setInterval(() => {
            index = (index + 1) % lines.length;
            rotator.classList.add("is-switching");
            window.setTimeout(() => {
                rotator.textContent = lines[index];
                rotator.classList.remove("is-switching");
            }, 180);
        }, 3200);
    }

    const revealItems = Array.from(document.querySelectorAll("[data-landing-reveal]"));
    if (!revealItems.length || typeof IntersectionObserver === "undefined") {
        revealItems.forEach((item) => item.classList.add("is-visible"));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.2, rootMargin: "0px 0px -10%" });

    revealItems.forEach((item) => observer.observe(item));
}());
