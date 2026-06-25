(function () {
    "use strict";

    const title = document.querySelector("[data-landing-title]");
    if (title) {
        window.requestAnimationFrame(() => {
            title.classList.add("is-visible");
        });
    }

    const wizard = document.querySelector("[data-landing-wizard]");
    if (!wizard) {
        return;
    }

    const steps = Array.from(wizard.querySelectorAll("[data-landing-step]"));
    const chips = Array.from(wizard.querySelectorAll("[data-step-target]"));
    const prevButton = wizard.querySelector("[data-step-prev]");
    const nextButton = wizard.querySelector("[data-step-next]");
    let currentStep = 0;

    function setStep(index) {
        const safeIndex = Math.max(0, Math.min(index, steps.length - 1));
        currentStep = safeIndex;

        steps.forEach((step, stepIndex) => {
            const isActive = stepIndex === currentStep;
            step.classList.toggle("is-active", isActive);
            step.setAttribute("aria-hidden", isActive ? "false" : "true");
        });

        chips.forEach((chip, chipIndex) => {
            chip.classList.toggle("is-active", chipIndex === currentStep);
        });

        if (prevButton) {
            prevButton.disabled = currentStep === 0;
        }

        if (nextButton) {
            nextButton.textContent = currentStep === steps.length - 1 ? "Ir al registro" : "Siguiente";
        }
    }

    chips.forEach((chip) => {
        chip.addEventListener("click", () => {
            const target = Number(chip.dataset.stepTarget);
            if (!Number.isNaN(target)) {
                setStep(target);
            }
        });
    });

    prevButton?.addEventListener("click", () => {
        setStep(currentStep - 1);
    });

    nextButton?.addEventListener("click", () => {
        if (currentStep === steps.length - 1) {
            window.location.href = "registro.php";
            return;
        }
        setStep(currentStep + 1);
    });

    setStep(0);
}());
