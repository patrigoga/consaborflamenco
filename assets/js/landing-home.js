(function () {
    "use strict";

    const title = document.querySelector("[data-landing-title]");
    if (title) {
        window.requestAnimationFrame(() => {
            title.classList.add("is-visible");
        });
    }

    const slider = document.querySelector("[data-story-slider]");
    if (!slider) {
        return;
    }

    const slides = Array.from(slider.querySelectorAll("[data-story-slide]"));
    const dots = Array.from(slider.querySelectorAll("[data-story-dot]"));
    const prevButton = slider.querySelector("[data-story-prev]");
    const nextButton = slider.querySelector("[data-story-next]");
    let currentIndex = 0;
    let autoTimer = null;

    function paint(index) {
        const safeIndex = (index + slides.length) % slides.length;
        currentIndex = safeIndex;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === currentIndex;
            slide.classList.toggle("is-active", isActive);
            slide.setAttribute("aria-hidden", isActive ? "false" : "true");
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle("is-active", dotIndex === currentIndex);
        });
    }

    function next() {
        paint(currentIndex + 1);
    }

    function resetAuto() {
        if (autoTimer) {
            window.clearInterval(autoTimer);
        }
        autoTimer = window.setInterval(next, 6500);
    }

    prevButton?.addEventListener("click", () => {
        paint(currentIndex - 1);
        resetAuto();
    });

    nextButton?.addEventListener("click", () => {
        next();
        resetAuto();
    });

    dots.forEach((dot) => {
        dot.addEventListener("click", () => {
            const index = Number(dot.dataset.storyDot);
            if (Number.isNaN(index)) {
                return;
            }
            paint(index);
            resetAuto();
        });
    });

    paint(0);
    resetAuto();
}());
