/**
 * Panel de miembro - Fase 1 red social.
 *
 * Modales de confirmacion de gasto de puntos, paquetes de compra y vista previa
 * del cartel del evento.
 *
 * Mejora progresiva: sin JavaScript los formularios siguen enviandose (los
 * modales estan en el HTML y solo cambia que no se abren en capa). Ninguna
 * decision de negocio vive aqui: el coste y el saldo los valida el servidor.
 */
(function () {
    'use strict';

    var abierto = null;

    function abrir(modal) {
        if (!modal) {
            return;
        }
        cerrar();
        modal.hidden = false;
        document.body.classList.add('modal-open');
        abierto = modal;

        var foco = modal.querySelector('button, [href], input, select, textarea');
        if (foco) {
            foco.focus();
        }
    }

    function cerrar() {
        if (!abierto) {
            return;
        }
        abierto.hidden = true;
        abierto = null;
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('click', function (evento) {
        var disparadorConfirmar = evento.target.closest('[data-abrir-confirmar]');
        if (disparadorConfirmar) {
            evento.preventDefault();
            var id = disparadorConfirmar.getAttribute('data-abrir-confirmar');
            abrir(document.querySelector('[data-confirmar-modal="' + id + '"]'));
            return;
        }

        if (evento.target.closest('[data-abrir-paquetes]')) {
            evento.preventDefault();
            abrir(document.querySelector('[data-paquetes-modal]'));
            return;
        }

        if (evento.target.closest('[data-cerrar-confirmar], [data-cerrar-paquetes]')) {
            evento.preventDefault();
            cerrar();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrar();
        }
    });

    // Vista previa de una imagen antes de subirla. Generico: cada par
    // campo/vista se declara con sus propios atributos data-* (el cartel de
    // evento y la foto de producto de tienda usan pares distintos porque
    // ambos formularios pueden convivir en la misma pagina del panel).
    function activarVistaPrevia(atributoCampo, atributoVista) {
        var campoImagen = document.querySelector('[' + atributoCampo + ']');
        var vistaPrevia = document.querySelector('[' + atributoVista + ']');
        if (!campoImagen || !vistaPrevia) {
            return;
        }

        campoImagen.addEventListener('change', function () {
            var fichero = campoImagen.files && campoImagen.files[0];
            if (!fichero) {
                return;
            }
            var lector = new FileReader();
            lector.onload = function () {
                vistaPrevia.src = String(lector.result);
                vistaPrevia.hidden = false;
            };
            lector.readAsDataURL(fichero);
        });
    }

    activarVistaPrevia('data-evento-imagen', 'data-evento-preview');
    activarVistaPrevia('data-producto-imagen', 'data-producto-preview');
})();
