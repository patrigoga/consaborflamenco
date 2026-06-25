(function () {
    "use strict";

    const menuToggle = document.querySelector(".menu-toggle");
    const navigation = document.querySelector("#main-nav");
    const accordions = Array.from(document.querySelectorAll(".nav-accordion"));

    if (!menuToggle || !navigation) {
        return;
    }

    function setAccordionState(accordion, isOpen) {
        const accordionToggle = accordion?.querySelector(".nav-accordion-toggle");
        if (!accordionToggle) {
            return;
        }
        accordionToggle.setAttribute("aria-expanded", String(isOpen));
        accordion.classList.toggle("is-open", isOpen);
    }

    function closeAccordions(except = null) {
        accordions.forEach((accordion) => {
            if (accordion !== except) {
                setAccordionState(accordion, false);
            }
        });
    }

    function isDesktopNavigation() {
        return window.innerWidth > 1360;
    }

    function setMenuState(isOpen) {
        menuToggle.setAttribute("aria-expanded", String(isOpen));
        menuToggle.classList.toggle("is-open", isOpen);
        navigation.classList.toggle("is-open", isOpen);
        if (!isOpen) {
            closeAccordions();
        }
    }

    menuToggle.addEventListener("click", () => {
        setMenuState(menuToggle.getAttribute("aria-expanded") !== "true");
    });

    accordions.forEach((accordion) => {
        const accordionToggle = accordion.querySelector(".nav-accordion-toggle");
        accordion.addEventListener("mouseenter", () => {
            if (!isDesktopNavigation()) {
                return;
            }
            closeAccordions(accordion);
            setAccordionState(accordion, true);
        });

        accordion.addEventListener("mouseleave", () => {
            if (!isDesktopNavigation()) {
                return;
            }
            setAccordionState(accordion, false);
        });

        accordionToggle?.addEventListener("click", () => {
            if (isDesktopNavigation()) {
                return;
            }
            const shouldOpen = accordionToggle.getAttribute("aria-expanded") !== "true";
            closeAccordions(accordion);
            setAccordionState(accordion, shouldOpen);
        });
    });

    navigation.addEventListener("click", (event) => {
        if (event.target.closest("a")) {
            setMenuState(false);
        }
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".nav-accordion")) {
            closeAccordions();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        const openAccordionToggle = document.querySelector(".nav-accordion.is-open .nav-accordion-toggle");
        setMenuState(false);
        if (window.innerWidth > 1360 && openAccordionToggle) {
            openAccordionToggle.focus();
        } else {
            menuToggle.focus();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 1360) {
            setMenuState(false);
            closeAccordions();
        }
    });
}());
