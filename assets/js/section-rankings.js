(function () {
    "use strict";

    const images = {
        academia: "assets/images/community/academia-flamenca.webp",
        artista: "assets/images/community/artista-bailaora.webp",
        evento: "assets/images/community/evento-flamenco.webp",
        pena: "assets/images/community/pena-flamenca.webp"
    };

    const rankings = {
        REVISTA: [
            { position: 1, type: "Baile", source: "Más votado", title: "La fuerza del baile flamenco actual", description: "Movimiento, escena y expresión en una generación que conecta raíz y presente.", image: images.artista, alt: "Bailaora sobre un escenario flamenco", href: "#revista", action: "Leer artículo" },
            { position: 2, type: "Cante", source: "Promocionado", title: "Nuevas voces del cante", image: images.evento, alt: "Cantaora y guitarrista en un festival", href: "#revista", action: "Leer artículo" },
            { position: 3, type: "Compás", source: "Más votado", title: "El compás como raíz", image: images.pena, alt: "Encuentro de cante y guitarra en una peña", href: "#revista", action: "Leer artículo" }
        ],
        ACADEMIAS: [
            { position: 1, type: "Baile", source: "Promocionado", title: "Academia de baile flamenco", description: "Formación técnica, expresión y compás para todos los niveles.", image: images.academia, alt: "Clase en una academia de baile flamenco", href: "#academias" },
            { position: 2, type: "Guitarra", source: "Más votado", title: "Escuela de guitarra", image: images.evento, alt: "Guitarrista durante una actuación", href: "#academias" },
            { position: 3, type: "Ritmo", source: "Más votado", title: "Compás y palmas", image: images.pena, alt: "Grupo reunido en una peña flamenca", href: "#academias" }
        ],
        CURSOS: [
            { position: 1, type: "Presencial", source: "Promocionado", title: "Curso de baile flamenco", description: "Técnica, compás y expresión en clases adaptadas a distintos niveles.", image: images.academia, alt: "Curso presencial de baile flamenco", href: "#cursos-presenciales" },
            { position: 2, type: "Online", source: "Más votado", title: "Flamenco desde casa", image: images.artista, alt: "Bailaora mostrando técnica flamenca", href: "#cursos-online" },
            { position: 3, type: "Intensivo", source: "Más votado", title: "Taller de cante y compás", image: images.pena, alt: "Taller intensivo en un encuentro flamenco", href: "#cursos-intensivos" }
        ],
        ARTISTAS: [
            { position: 1, type: "Bailaora", source: "Más votado", title: "Bailaora en directo", description: "Una propuesta escénica marcada por la fuerza, la elegancia y la raíz.", image: images.artista, alt: "Bailaora actuando en un tablao", href: "#artistas" },
            { position: 2, type: "Cantaora", source: "Promocionado", title: "Voz flamenca actual", image: images.evento, alt: "Cantaora acompañada por guitarra", href: "#artistas" },
            { position: 3, type: "Guitarrista", source: "Más votado", title: "Guitarra de concierto", image: images.pena, alt: "Guitarrista en un encuentro flamenco", href: "#artistas" }
        ],
        CONCURSOS: [
            { position: 1, type: "Cante", source: "Promocionado", title: "Concurso de cante joven", description: "Una convocatoria abierta para descubrir y proyectar nuevas voces.", image: images.evento, alt: "Cantaora en un escenario al aire libre", href: "#concursos" },
            { position: 2, type: "Baile", source: "Más votado", title: "Certamen de baile", image: images.artista, alt: "Bailaora durante una actuación", href: "#concursos" },
            { position: 3, type: "Formación", source: "Más votado", title: "Premio nuevos talentos", image: images.academia, alt: "Grupo practicando baile flamenco", href: "#concursos" }
        ],
        EVENTOS: [
            { position: 1, type: "Festival", source: "Más votado", title: "Festival flamenco", description: "Una noche de cante y guitarra en un entorno histórico y cercano.", image: images.evento, alt: "Festival flamenco al aire libre", href: "#eventos" },
            { position: 2, type: "Formación", source: "Promocionado", title: "Clase magistral", image: images.academia, alt: "Clase colectiva de baile flamenco", href: "#eventos" },
            { position: 3, type: "Peña", source: "Más votado", title: "Noche de peña", image: images.pena, alt: "Encuentro musical en una peña", href: "#eventos" }
        ],
        FESTIVALES: [
            { position: 1, type: "Programación", source: "Promocionado", title: "Festival de verano", description: "Una programación que reúne artistas, comunidad y patrimonio.", image: images.evento, alt: "Actuación flamenca en una plaza", href: "#festivales" },
            { position: 2, type: "Baile", source: "Más votado", title: "Noche de baile", image: images.artista, alt: "Bailaora en un escenario", href: "#festivales" },
            { position: 3, type: "Encuentro", source: "Más votado", title: "Festival de peñas", image: images.pena, alt: "Reunión de aficionados al flamenco", href: "#festivales" }
        ],
        PENAS: [
            { position: 1, type: "Peña", source: "Más votado", title: "Peña flamenca cultural", description: "Cante, guitarra y convivencia para mantener viva la cultura local.", image: images.pena, alt: "Reunión en una peña flamenca", href: "#penas" },
            { position: 2, type: "Recital", source: "Promocionado", title: "Ciclo de recitales", image: images.evento, alt: "Recital flamenco al aire libre", href: "#penas" },
            { position: 3, type: "Baile", source: "Más votado", title: "Encuentro de baile", image: images.artista, alt: "Bailaora actuando", href: "#penas" }
        ],
        SERVICIOS: [
            { position: 1, type: "Digital", source: "Promocionado", title: "Web profesional para miembros", description: "Presencia digital preparada para mostrar trayectoria y contratación.", image: images.academia, alt: "Profesionales en una academia flamenca", href: "#servicios" },
            { position: 2, type: "Dossier", source: "Más votado", title: "Dossier artístico", image: images.artista, alt: "Artista flamenca sobre un escenario", href: "#servicios" },
            { position: 3, type: "Promoción", source: "Promocionado", title: "Campaña en revista", image: images.evento, alt: "Actuación flamenca ante el público", href: "#servicios" }
        ],
        TABLAOS: [
            { position: 1, type: "Tablao", source: "Promocionado", title: "Flamenco en directo", description: "Una experiencia cercana con baile, cante y guitarra cada noche.", image: images.artista, alt: "Bailaora actuando en un tablao", href: "#tablaos" },
            { position: 2, type: "Programación", source: "Más votado", title: "Noches de cante", image: images.evento, alt: "Cantaora y guitarrista actuando", href: "#tablaos" },
            { position: 3, type: "Encuentro", source: "Más votado", title: "Tablao íntimo", image: images.pena, alt: "Encuentro flamenco en un espacio íntimo", href: "#tablaos" }
        ],
        MODA: [
            { position: 1, type: "Ropa", source: "Promocionado", title: "Colección flamenca actual", description: "Diseño, movimiento y tradición en una propuesta pensada para la escena.", image: images.artista, alt: "Vestuario flamenco durante una actuación", href: "#moda-ropa" },
            { position: 2, type: "Calzado", source: "Más votado", title: "Calzado para baile", image: images.academia, alt: "Artistas practicando baile flamenco", href: "#moda-calzado" },
            { position: 3, type: "Complementos", source: "Más votado", title: "Complementos con raíz", image: images.evento, alt: "Artistas con vestuario flamenco", href: "#moda-complementos" }
        ],
        FOTOGRAFIA: [
            { position: 1, type: "Escena", source: "Más votado", title: "La fuerza del baile", description: "Una mirada fotográfica al movimiento, la luz y la emoción del tablao.", image: images.artista, alt: "Fotografía de una bailaora", href: "#fotografia" },
            { position: 2, type: "Festival", source: "Promocionado", title: "Noches de cante", image: images.evento, alt: "Fotografía de un festival flamenco", href: "#fotografia" },
            { position: 3, type: "Comunidad", source: "Más votado", title: "Flamenco cercano", image: images.pena, alt: "Fotografía de una peña flamenca", href: "#fotografia" }
        ],
        FLAMENCO: [
            { id: "historia-flamenco", position: 1, type: "Historia", source: "Más votado", title: "Historia del flamenco", description: "Un recorrido por los orígenes, la evolución y las figuras esenciales.", image: images.pena, alt: "Reunión tradicional de aficionados al flamenco", href: "#historia-flamenco" },
            { id: "palos-flamenco", position: 2, type: "Palos", source: "Más votado", title: "Palos del flamenco", image: images.artista, alt: "Bailaora interpretando un palo flamenco", href: "#palos-flamenco" },
            { id: "llaves-oro", position: 3, type: "Reconocimientos", source: "Promocionado", title: "Llaves de Oro", image: images.evento, alt: "Cantaora durante una actuación", href: "#llaves-oro" }
        ]
    };

    function createTextElement(tagName, className, textContent) {
        const element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        element.textContent = textContent;
        return element;
    }

    function createStory(item, index) {
        const story = document.createElement("a");
        story.className = `editorial-story${index === 0 ? " editorial-story-main" : ""}`;
        story.href = item.href;
        if (item.id) {
            story.id = item.id;
        }

        const image = document.createElement("img");
        image.src = item.image;
        image.alt = item.alt;
        image.width = 640;
        image.height = 480;
        if (index > 0) {
            image.loading = "lazy";
        }

        const content = document.createElement("div");
        content.className = "editorial-story-content";

        const meta = document.createElement("span");
        meta.className = "editorial-meta";
        meta.append(
            createTextElement("strong", "", `${item.position}.º · ${item.type}`),
            createTextElement("span", item.source === "Promocionado" ? "ranking-source is-sponsored" : "ranking-source", item.source)
        );

        content.append(meta, createTextElement("h3", "", item.title));
        if (index === 0 && item.description) {
            content.append(createTextElement("p", "", item.description));
        }
        content.append(createTextElement("span", "editorial-read", `${item.action || "Ver más"} →`));
        story.append(image, content);
        return story;
    }

    document.querySelectorAll("[data-ranking-section]").forEach((container) => {
        const items = rankings[container.dataset.rankingSection] || [];
        const orderedItems = [...items].sort((first, second) => first.position - second.position).slice(0, 3);
        container.replaceChildren(...orderedItems.map(createStory));
    });

    if (window.location.hash) {
        window.requestAnimationFrame(() => {
            document.querySelector(window.location.hash)?.scrollIntoView();
        });
    }
}());
