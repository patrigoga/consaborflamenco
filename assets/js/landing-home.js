(function () {
    "use strict";

    function initializeSlider() {
        var title = document.querySelector("[data-landing-title]");
        if (title) {
            var scheduleFrame = window.requestAnimationFrame || function (callback) {
                return window.setTimeout(callback, 0);
            };

            scheduleFrame(function () {
                title.classList.add("is-visible");
            });
        }

        var sliders = Array.prototype.slice.call(document.querySelectorAll("[data-story-slider]"));
        if (!sliders.length) {
            return;
        }

        sliders.forEach(setupSlider);
    }

    function setupSlider(slider) {
        if (typeof slider.storySliderCleanup === "function") {
            slider.storySliderCleanup();
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll("[data-story-slide]"));
        var dots = Array.prototype.slice.call(slider.querySelectorAll("[data-story-dot]"));
        var prevButton = slider.querySelector("[data-story-prev]");
        var nextButton = slider.querySelector("[data-story-next]");
        var currentIndex = 0;
        var autoTimer = null;
        var cleanupCallbacks = [];

        if (!slides.length || !dots.length) {
            return;
        }

        function listen(element, eventName, callback) {
            if (!element) {
                return;
            }

            element.addEventListener(eventName, callback);
            cleanupCallbacks.push(function () {
                element.removeEventListener(eventName, callback);
            });
        }

        function paint(index, direction) {
            var safeIndex = (index % slides.length + slides.length) % slides.length;
            currentIndex = safeIndex;
            var movement = typeof direction === "number" ? direction : 1;

            slides.forEach(function (slide, slideIndex) {
                var isActive = slideIndex === currentIndex;
                slide.classList.toggle("is-active", isActive);
                slide.classList.remove("is-copy-from-left", "is-copy-from-right");
                if (isActive) {
                    var fromRight = movement < 0 ? slideIndex % 2 === 0 : slideIndex % 2 !== 0;
                    slide.classList.add(fromRight ? "is-copy-from-right" : "is-copy-from-left");
                }
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
            });

            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle("is-active", dotIndex === currentIndex);
            });
        }

        function next() {
            paint(currentIndex + 1, 1);
        }

        function resetAuto() {
            if (autoTimer) {
                window.clearInterval(autoTimer);
            }

            if (slides.length < 2) {
                return;
            }

            autoTimer = window.setInterval(next, 6500);
        }

        listen(prevButton, "click", function () {
            paint(currentIndex - 1, -1);
            resetAuto();
        });

        listen(nextButton, "click", function () {
            next();
            resetAuto();
        });

        dots.forEach(function (dot) {
            listen(dot, "click", function () {
                var index = Number(dot.getAttribute("data-story-dot"));
                if (isNaN(index)) {
                    return;
                }
                paint(index, index >= currentIndex ? 1 : -1);
                resetAuto();
            });
        });

        paint(0, 1);
        resetAuto();

        slider.storySliderCleanup = function () {
            if (autoTimer) {
                window.clearInterval(autoTimer);
                autoTimer = null;
            }

            cleanupCallbacks.forEach(function (cleanup) {
                cleanup();
            });
            cleanupCallbacks = [];
        };
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeSlider);
    } else {
        initializeSlider();
    }

    window.addEventListener("pageshow", initializeSlider);
}());
