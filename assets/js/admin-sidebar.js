document.addEventListener('DOMContentLoaded', function () {
    const sidebarLinks = document.querySelectorAll('.admin-sidebar-link');
    const sidebarToggle = document.getElementById('admin-sidebar-toggle');
    const sidebarClose = document.querySelector('.admin-sidebar-close');
    const adminSidebar = document.querySelector('.admin-sidebar');
    const mainContent = document.querySelector('.admin-main-content');

    // Toggle sidebar en móvil
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            adminSidebar.classList.toggle('is-open');
        });
    }

    // Cerrar sidebar
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function () {
            adminSidebar.classList.remove('is-open');
        });
    }

    // Manejar clicks en opciones del sidebar
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('data-target');
            const section = targetId ? document.getElementById(targetId) : null;

            // Actualizar estado activo del link
            sidebarLinks.forEach(l => l.classList.remove('is-active'));
            this.classList.add('is-active');

            // Obtener todas las secciones
            const pageIntro = document.querySelector('.page-intro');
            const allAdminShells = document.querySelectorAll('.admin-shell');
            const metricGrid = document.querySelector('.admin-metric-grid');

            // Ocultar todo por defecto
            if (pageIntro) pageIntro.style.display = 'none';
            if (metricGrid) metricGrid.style.display = 'none';
            allAdminShells.forEach(s => {
                s.style.display = 'none';
            });

            // Mostrar sección seleccionada
            if (targetId === 'general') {
                // Para vista general, mostrar intro y métricas
                if (pageIntro) pageIntro.style.display = 'block';
                if (metricGrid) metricGrid.style.display = 'grid';
            } else if (section) {
                // Para otras secciones, mostrar solo esa
                section.style.display = 'block';
            }

            // Cerrar sidebar en móvil después de seleccionar
            if (window.innerWidth < 1080) {
                adminSidebar.classList.remove('is-open');
            }
        });
    });

    // Activar primera opción por defecto
    const firstLink = document.querySelector('.admin-sidebar-link[data-target="general"]');
    if (firstLink) {
        firstLink.click();
    }
});

