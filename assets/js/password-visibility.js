(function () {
    "use strict";

    document.addEventListener('DOMContentLoaded', function () {
        const toggles = document.querySelectorAll('.password-toggle');

        toggles.forEach(function (toggle) {
            const field = toggle.closest('.password-field');
            if (!field) {
                return;
            }

            const input = field.querySelector('input[type="password"], input[type="text"]');
            if (!input) {
                return;
            }

            toggle.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggle.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.setAttribute('aria-pressed', String(isPassword));
                toggle.classList.toggle('is-active', isPassword);
            });
        });
    });
}());
