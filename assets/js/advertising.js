(function () {
    "use strict";

    const STORAGE_KEY = "csf_ad_location";
    const NATIONAL_SCOPE = "Toda España";
    const categoryLabels = {
        INICIO: "Inicio",
        REVISTA: "Revista",
        FOTOGRAFIA: "Fotografía",
        MODA: "Moda",
        ARTISTAS: "Artistas",
        ACADEMIAS: "Academias",
        CURSOS: "Cursos",
        EVENTOS: "Eventos",
        FLAMENCO: "Flamenco",
        HISTORIA: "Historia",
        PALOS_FLAMENCO: "Palos del flamenco",
        LLAVES_ORO: "Llaves de Oro",
        PENAS: "Peñas",
        TABLAOS: "Tablaos",
        FESTIVALES: "Festivales",
        CONCURSOS: "Concursos",
        GENERAL: "Selección local"
    };
    const provinces = [
        "A Coruña", "Álava", "Albacete", "Alicante", "Almería", "Asturias", "Ávila",
        "Badajoz", "Barcelona", "Bizkaia", "Burgos", "Cáceres", "Cádiz", "Cantabria",
        "Castellón", "Ceuta", "Ciudad Real", "Córdoba", "Cuenca", "Gipuzkoa", "Girona",
        "Granada", "Guadalajara", "Huelva", "Huesca", "Illes Balears", "Jaén", "La Rioja",
        "Las Palmas", "León", "Lleida", "Lugo", "Madrid", "Málaga", "Melilla", "Murcia",
        "Navarra", "Ourense", "Palencia", "Pontevedra", "Salamanca", "Santa Cruz de Tenerife",
        "Segovia", "Sevilla", "Soria", "Tarragona", "Teruel", "Toledo", "Valencia",
        "Valladolid", "Zamora", "Zaragoza"
    ];
    const campaignTemplates = {
        INICIO: ["Descubre el flamenco de {province}", "Conecta tu marca con la comunidad local"],
        REVISTA: ["Patrocina la actualidad flamenca", "Tu marca junto a historias que dejan huella"],
        FOTOGRAFIA: ["El flamenco a través del objetivo", "Conecta tu marca con historias visuales"],
        MODA: ["Moda flamenca en {province}", "Ropa, calzado y complementos para una comunidad con estilo"],
        ARTISTAS: ["Profesionales flamencos en {province}", "Promociona contratación, producción y talento"],
        ACADEMIAS: ["Formación flamenca en {province}", "Cursos y matrículas cerca de tu público"],
        CURSOS: ["Cursos de flamenco en {province}", "Formación presencial y online para nuevos alumnos"],
        EVENTOS: ["Planes flamencos en {province}", "Destaca tu próxima fecha en la agenda"],
        FLAMENCO: ["Cultura flamenca con profundidad", "Tu marca junto a la historia, los palos y el cante"],
        HISTORIA: ["Historia flamenca con contexto", "Tu marca junto a contenidos culturales de largo recorrido"],
        PALOS_FLAMENCO: ["Palos flamencos para aprender y descubrir", "Conecta con lectores que buscan compÃ¡s, estilo y raÃ­z"],
        LLAVES_ORO: ["Grandes nombres del cante", "Patrocina contenidos sobre legado, memoria y excelencia flamenca"],
        PENAS: ["La comunidad flamenca de {province}", "Haz visible tu peña y su programación"],
        TABLAOS: ["Flamenco en directo en {province}", "Llega a quienes buscan una noche especial"],
        FESTIVALES: ["Festivales que se viven", "Promociona cartel, entradas y patrocinadores"],
        CONCURSOS: ["Convocatorias para nuevos talentos", "Lleva tu certamen a toda la comunidad"],
        GENERAL: ["Empresas con sabor local", "Publicidad relevante para visitantes de {province}"]
    };

    let activeCategory = "INICIO";
    let selectedProvince = NATIONAL_SCOPE;
    let lastFocusedElement = null;

    const modal = document.querySelector("[data-province-modal]");
    const provinceSelect = document.querySelector("#province-select");
    const adSlots = document.querySelectorAll("[data-ad-slots]");

    function readLocationPreference() {
        try {
            const stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
            return stored && typeof stored.province === "string" ? stored : null;
        } catch (error) {
            return null;
        }
    }

    function saveLocationPreference(province, source) {
        const preference = { province, source, updatedAt: new Date().toISOString() };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(preference));
        } catch (error) {
            return preference;
        }
        return preference;
    }

    function populateProvinceSelect() {
        const fragment = document.createDocumentFragment();
        provinces.forEach((province) => {
            const option = document.createElement("option");
            option.value = province;
            option.textContent = province;
            fragment.appendChild(option);
        });
        provinceSelect.appendChild(fragment);
    }

    function setProvince(province, source = "visitor") {
        selectedProvince = provinces.includes(province) ? province : NATIONAL_SCOPE;
        saveLocationPreference(selectedProvince, source);
        document.querySelectorAll("[data-current-province]").forEach((element) => {
            element.textContent = selectedProvince;
        });
        renderAds();
        closeModal();
    }

    function setCategory(category) {
        activeCategory = Object.prototype.hasOwnProperty.call(categoryLabels, category) ? category : "GENERAL";
        document.querySelectorAll("[data-ad-category-label]").forEach((element) => {
            element.textContent = categoryLabels[activeCategory];
        });
        document.querySelectorAll("[data-ad-nav]").forEach((link) => {
            link.classList.toggle("is-active", link.dataset.adNav === activeCategory);
        });
        renderAds();
    }

    function buildBanner(slot, title, description, scope, category) {
        const banner = document.createElement("article");
        banner.className = `ad-banner ad-banner-${slot}`;
        banner.innerHTML = `
            <span class="ad-label">Publicidad · ${scope}</span>
            <div class="ad-banner-content">
                <span class="ad-category">${categoryLabels[category]}</span>
                <h3>${title}</h3>
                <p>${description}</p>
                <span class="ad-cta">Reservar este espacio →</span>
            </div>
        `;
        return banner;
    }

    function renderAds() {
        if (!adSlots.length) {
            return;
        }

        const category = campaignTemplates[activeCategory] ? activeCategory : "GENERAL";
        const provinceLabel = selectedProvince === NATIONAL_SCOPE ? "España" : selectedProvince;
        const [titleTemplate, descriptionTemplate] = campaignTemplates[category];
        const localTitle = titleTemplate.replace("{province}", provinceLabel);
        const localDescription = descriptionTemplate.replace("{province}", provinceLabel);
        const scopeLabel = selectedProvince === NATIONAL_SCOPE ? "Nacional" : provinceLabel;

        document.querySelectorAll("[data-ad-province]").forEach((element) => {
            element.textContent = selectedProvince === NATIONAL_SCOPE ? "España" : selectedProvince;
        });

        adSlots.forEach((container) => {
            container.replaceChildren(
                buildBanner("premium", localTitle, localDescription, scopeLabel, category),
                buildBanner("standard", `Tu negocio puede destacar en ${provinceLabel}`, "Campaña segmentada por provincia y categoría.", scopeLabel, category)
            );
        });
    }

    function openModal() {
        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        document.body.classList.add("modal-open");
        window.setTimeout(() => provinceSelect.focus(), 0);
    }

    function closeModal() {
        if (modal.hidden) {
            return;
        }
        modal.hidden = true;
        document.body.classList.remove("modal-open");
        if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
            lastFocusedElement.focus();
        }
    }

    function observeSections() {
        const sections = document.querySelectorAll("[data-ad-category]");
        const observer = new IntersectionObserver((entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((first, second) => second.intersectionRatio - first.intersectionRatio)[0];
            if (visible) {
                setCategory(visible.target.dataset.adCategory);
            }
        }, { rootMargin: "-20% 0px -55%", threshold: [0.05, 0.25, 0.5] });

        sections.forEach((section) => observer.observe(section));
    }

    function bindEvents() {
        document.querySelectorAll("[data-open-province]").forEach((button) => {
            button.addEventListener("click", openModal);
        });
        document.querySelectorAll("[data-close-province]").forEach((button) => {
            button.addEventListener("click", closeModal);
        });
        document.querySelector("[data-province-form]").addEventListener("submit", (event) => {
            event.preventDefault();
            if (provinceSelect.value) {
                setProvince(provinceSelect.value);
            }
        });
        document.querySelector("[data-skip-province]").addEventListener("click", () => {
            setProvince(NATIONAL_SCOPE, "visitor-skipped");
        });
        document.querySelectorAll("[data-ad-nav]").forEach((link) => {
            link.addEventListener("click", () => setCategory(link.dataset.adNav));
        });
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !modal.hidden) {
                closeModal();
            }
        });
    }

    populateProvinceSelect();
    bindEvents();
    observeSections();

    const storedPreference = readLocationPreference();
    if (storedPreference) {
        selectedProvince = provinces.includes(storedPreference.province) ? storedPreference.province : NATIONAL_SCOPE;
        document.querySelectorAll("[data-current-province]").forEach((element) => {
            element.textContent = selectedProvince;
        });
        renderAds();
    } else {
        renderAds();
        openModal();
    }

    window.CSFAdvertising = {
        setCategory,
        setProvince: (province) => setProvince(province, "member-profile")
    };
}());
