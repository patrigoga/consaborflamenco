document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.admin-container');
    const sidebarLinks = Array.from(document.querySelectorAll('.admin-sidebar-link'));
    const sidebarToggle = document.getElementById('admin-sidebar-toggle');
    const sidebarClose = document.querySelector('.admin-sidebar-close');
    const adminSidebar = document.querySelector('.admin-sidebar');
    const pageIntro = document.querySelector('.page-intro');
    const pageTitle = pageIntro ? pageIntro.querySelector('h1') : null;
    const allAdminShells = Array.from(document.querySelectorAll('.admin-shell'));

    const showSection = function (targetId, title) {
        const section = targetId ? document.getElementById(targetId) : null;

        sidebarLinks.forEach(function (link) {
            const isActive = link.getAttribute('data-target') === targetId;
            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        if (pageIntro) {
            pageIntro.style.display = 'flex';
        }

        allAdminShells.forEach(function (adminShell) {
            adminShell.style.display = 'none';
        });

        if (section) {
            section.style.display = 'grid';
        }

        if (pageTitle && title) {
            pageTitle.textContent = title;
        }
    };

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function () {
            const isOpen = adminSidebar.classList.toggle('is-open');
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (sidebarClose && adminSidebar) {
        sidebarClose.addEventListener('click', function () {
            adminSidebar.classList.remove('is-open');
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', 'false');
                sidebarToggle.focus();
            }
        });
    }

    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            const targetId = link.getAttribute('data-target');
            const href = link.getAttribute('href') || '';

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || href === '') {
                return;
            }

            event.preventDefault();
            showSection(targetId, link.textContent.trim());
            if (href !== '#') {
                window.history.pushState({}, '', href);
            }

            if (adminSidebar && window.innerWidth < 1080) {
                adminSidebar.classList.remove('is-open');
                if (sidebarToggle) {
                    sidebarToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });

    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        const sectionKey = params.get('section') || 'general';
        const matchingLink = sidebarLinks.find(function (link) {
            return link.getAttribute('href') && link.getAttribute('href').indexOf('section=' + encodeURIComponent(sectionKey)) !== -1;
        });
        showSection(matchingLink ? matchingLink.getAttribute('data-target') : 'general', matchingLink ? matchingLink.textContent.trim() : 'Vista general');
    });

    const initialTarget = container ? container.getAttribute('data-admin-current-section') || 'general' : 'general';
    const initialLink = sidebarLinks.find(function (link) {
        return link.getAttribute('data-target') === initialTarget && link.closest('.admin-sidebar-nav-modern');
    });
    showSection(initialTarget, initialLink ? initialLink.textContent.trim() : null);
});
