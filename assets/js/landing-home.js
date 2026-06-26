(function () {
    "use strict";

    function initializeSlider() {
        var title = document.querySelector("[data-landing-title]");
        if (title) {
            window.requestAnimationFrame(function () {
                title.classList.add("is-visible");
            });
        }

        var slider = document.querySelector("[data-story-slider]");
        if (!slider) {
            return;
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll("[data-story-slide]"));
        var dots = Array.prototype.slice.call(slider.querySelectorAll("[data-story-dot]"));
        var prevButton = slider.querySelector("[data-story-prev]");
        var nextButton = slider.querySelector("[data-story-next]");
        var currentIndex = 0;
        var autoTimer = null;

        if (!slides.length || !dots.length) {
            return;
        }

        function paint(index) {
            var safeIndex = (index % slides.length + slides.length) % slides.length;
            currentIndex = safeIndex;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;
                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
            });

            dots.forEach(function (dot, dotIndex) {
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

        if (prevButton) {
            prevButton.addEventListener("click", function () {
                paint(currentIndex - 1);
                resetAuto();
            });
        }

        if (nextButton) {
            nextButton.addEventListener("click", function () {
                next();
                resetAuto();
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener("click", function () {
                var index = Number(dot.getAttribute("data-story-dot"));
                if (isNaN(index)) {
                    return;
                }
                paint(index);
                resetAuto();
            });
        });

        paint(0);
        resetAuto();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeSlider);
    } else {
        initializeSlider();
    }
}());
