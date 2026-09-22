(() => {
    'use strict';
    const revealElements = [...document.querySelectorAll('[data-reveal]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.documentElement.classList.add('reveal-ready');
    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach(element => element.classList.add('is-visible'));
    } else {
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
        revealElements.forEach(element => revealObserver.observe(element));
    }
    const heroSlider = document.querySelector('[data-hero-slider]');
    if (heroSlider) {
        const slides = [...heroSlider.querySelectorAll('[data-hero-slide]')];
        const previousButton = heroSlider.querySelector('[data-hero-prev]');
        const nextButton = heroSlider.querySelector('[data-hero-next]');
        const currentLabel = heroSlider.querySelector('[data-hero-current]');
        let currentSlide = 0;
        let autoplayTimer = null;
        let autoplayStopped = false;
        let touchStartX = 0;

        const showSlide = index => {
            currentSlide = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === currentSlide;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', String(!active));
                slide.inert = !active;
            });
            currentLabel.textContent = String(currentSlide + 1).padStart(2, '0');
        };
        const stopAutoplay = () => {
            if (autoplayTimer) window.clearInterval(autoplayTimer);
            autoplayTimer = null;
        };
        const startAutoplay = () => {
            stopAutoplay();
            if (reducedMotion || autoplayStopped || document.hidden || slides.length < 2) return;
            autoplayTimer = window.setInterval(() => showSlide(currentSlide + 1), 6500);
        };
        const useManualControl = direction => {
            autoplayStopped = true;
            stopAutoplay();
            showSlide(currentSlide + direction);
        };

        previousButton.addEventListener('click', () => useManualControl(-1));
        nextButton.addEventListener('click', () => useManualControl(1));
        heroSlider.addEventListener('mouseenter', stopAutoplay);
        heroSlider.addEventListener('mouseleave', startAutoplay);
        heroSlider.addEventListener('focusin', stopAutoplay);
        heroSlider.addEventListener('focusout', event => {
            if (!heroSlider.contains(event.relatedTarget)) startAutoplay();
        });
        heroSlider.addEventListener('keydown', event => {
            if (event.key === 'ArrowLeft') useManualControl(-1);
            if (event.key === 'ArrowRight') useManualControl(1);
        });
        heroSlider.addEventListener('touchstart', event => {
            touchStartX = event.changedTouches[0].clientX;
            stopAutoplay();
        }, { passive: true });
        heroSlider.addEventListener('touchend', event => {
            const distance = event.changedTouches[0].clientX - touchStartX;
            if (Math.abs(distance) > 45) useManualControl(distance > 0 ? -1 : 1);
            else startAutoplay();
        }, { passive: true });
        document.addEventListener('visibilitychange', () => document.hidden ? stopAutoplay() : startAutoplay());
        showSlide(0);
        startAutoplay();
    }
    const menu = document.querySelector('.menu-toggle');
    const nav = document.querySelector('#main-nav');
    const closeMenu = () => { nav.classList.remove('is-open'); menu.setAttribute('aria-expanded', 'false'); menu.setAttribute('aria-label', 'Abrir menú'); };
    menu.addEventListener('click', () => {
        const open = menu.getAttribute('aria-expanded') !== 'true';
        menu.setAttribute('aria-expanded', String(open));
        menu.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        nav.classList.toggle('is-open', open);
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
    document.addEventListener('click', event => { if (!event.target.closest('.header')) closeMenu(); });
    const form = document.querySelector('#lot-search');
    const locationSelect = form.querySelector('[name="location"]');
    const projectSelect = form.querySelector('[name="project_id"]');
    const priceSelect = form.querySelector('#price-range');
    const searchButton = form.querySelector('[type="submit"]');
    const searchLabel = searchButton.querySelector('[data-search-label]');
    const resultsSection = document.querySelector('#resultados-lotes');
    const resultsSummary = document.querySelector('#lot-results-summary');
    const resultsFeedback = document.querySelector('#lot-results-feedback');
    const resultsGrid = document.querySelector('#lot-results-grid');
    const clearSearch = document.querySelector('#reset-lot-search');
    const projectOptions = [...projectSelect.options].slice(1).map(option => ({
        value: option.value,
        label: option.textContent,
        location: option.dataset.location || '',
    }));
    const moneyFormatter = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN', minimumFractionDigits: 2 });
    const numberFormatter = new Intl.NumberFormat('es-PE', { maximumFractionDigits: 2 });
    let priceRangesRequest = null;
    let isSearching = false;
    let loadingPriceRanges = false;

    const updateProjectOptions = () => {
        const location = locationSelect.value;
        const selected = projectSelect.value;
        projectSelect.replaceChildren(new Option('Todos los proyectos', ''));
        projectOptions
            .filter(project => !location || project.location === location)
            .forEach(project => {
                const option = new Option(project.label, project.value);
                option.dataset.location = project.location;
                projectSelect.add(option);
            });
        projectSelect.value = [...projectSelect.options].some(option => option.value === selected) ? selected : '';
    };
    const renderPriceRanges = ranges => {
        priceSelect.replaceChildren(new Option('Todos los precios', ''));
        ranges.forEach((range, index) => {
            const option = new Option(range.label, `range-${index + 1}`);
            if (range.min_price !== null) option.dataset.minPrice = range.min_price;
            if (range.max_price !== null) option.dataset.maxPrice = range.max_price;
            priceSelect.add(option);
        });
    };
    const syncSearchState = () => {
        searchButton.disabled = isSearching || loadingPriceRanges;
        searchButton.setAttribute('aria-busy', String(isSearching || loadingPriceRanges));
    };
    const updatePriceRanges = async () => {
        priceRangesRequest?.abort();
        const request = new AbortController();
        priceRangesRequest = request;
        loadingPriceRanges = true;
        priceSelect.disabled = true;
        priceSelect.replaceChildren(new Option('Consultando precios...', ''));
        syncSearchState();
        const parameters = new URLSearchParams();
        if (locationSelect.value) parameters.set('location', locationSelect.value);
        if (projectSelect.value) parameters.set('project_id', projectSelect.value);
        try {
            const response = await fetch(`${form.dataset.priceRangesUrl}?${parameters.toString()}`, {
                signal: request.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('price_ranges_failed');
            const result = await response.json();
            renderPriceRanges(result.data || []);
        } catch (error) {
            if (error.name !== 'AbortError') renderPriceRanges([]);
        } finally {
            if (priceRangesRequest === request) {
                priceRangesRequest = null;
                loadingPriceRanges = false;
                priceSelect.disabled = false;
                syncSearchState();
            }
        }
    };
    const scrollToResults = () => {
        resultsSection.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
    };
    const showResultMessage = (title, message, allowReset = false) => {
        resultsFeedback.replaceChildren();
        const card = document.createElement('div');
        card.className = 'lot-results-message';
        const heading = document.createElement('h3');
        heading.textContent = title;
        const copy = document.createElement('p');
        copy.textContent = message;
        card.append(heading, copy);
        if (allowReset) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button';
            button.textContent = 'Limpiar filtros';
            button.addEventListener('click', () => clearSearch.click());
            card.append(button);
        }
        resultsFeedback.append(card);
    };
    const lotHeading = lot => {
        const block = /^mz\b/i.test(lot.block) ? lot.block : `MZ ${lot.block}`;
        return { block, text: `${block} · Lote ${lot.lot_number}` };
    };
    const createLotCard = (lot, index) => {
        const card = document.createElement('article');
        card.className = 'lot-result-card';
        card.dataset.reveal = 'up';
        card.style.setProperty('--reveal-delay', `${Math.min(index * 55, 330)}ms`);

        const top = document.createElement('div');
        top.className = 'lot-card-top';
        const badge = document.createElement('span');
        badge.className = 'lot-status';
        badge.textContent = 'Disponible';
        const location = document.createElement('span');
        location.className = 'lot-location';
        location.textContent = lot.location || 'Ubicación por confirmar';
        top.append(badge, location);

        const project = document.createElement('h3');
        project.textContent = lot.project;
        const heading = lotHeading(lot);
        const identifier = document.createElement('p');
        identifier.className = 'lot-identifier';
        identifier.textContent = heading.text;
        const code = document.createElement('p');
        code.className = 'lot-code';
        code.textContent = `Código: ${lot.code}`;

        const facts = document.createElement('dl');
        facts.className = 'lot-facts';
        if (lot.area !== null) {
            const area = document.createElement('div');
            const areaLabel = document.createElement('dt');
            const areaValue = document.createElement('dd');
            areaLabel.textContent = 'Área';
            areaValue.textContent = `${numberFormatter.format(lot.area)} ${lot.unit_measure === 'm2' ? 'm²' : lot.unit_measure}`;
            area.append(areaLabel, areaValue);
            facts.append(area);
        }
        const price = document.createElement('div');
        const priceLabel = document.createElement('dt');
        const priceValue = document.createElement('dd');
        priceLabel.textContent = 'Precio';
        priceValue.textContent = moneyFormatter.format(lot.cash_price);
        price.append(priceLabel, priceValue);
        facts.append(price);

        const contact = document.createElement('a');
        contact.className = 'lot-whatsapp';
        const message = `Hola, estoy interesado en el lote ${heading.block} / Lote ${lot.lot_number} del proyecto ${lot.project}. ¿Podrían brindarme más información?`;
        contact.href = `https://wa.me/${resultsSection.dataset.whatsapp}?text=${encodeURIComponent(message)}`;
        contact.target = '_blank';
        contact.rel = 'noopener noreferrer';
        contact.textContent = 'Consultar por WhatsApp →';
        card.append(top, project, identifier, code, facts, contact);
        return card;
    };
    const renderLots = result => {
        const lots = result.data || [];
        const total = result.meta?.total ?? lots.length;
        const shown = result.meta?.shown ?? lots.length;
        resultsGrid.replaceChildren();
        resultsFeedback.replaceChildren();
        resultsSummary.textContent = total === 1
            ? '1 lote disponible según tu búsqueda.'
            : (shown < total ? `Mostrando ${shown} de ${total} lotes disponibles.` : `${total} lotes disponibles según tu búsqueda.`);
        if (!lots.length) {
            showResultMessage('No encontramos lotes disponibles con estos filtros.', 'Prueba cambiando la ubicación, el proyecto o el presupuesto.', true);
            return;
        }
        lots.forEach((lot, index) => resultsGrid.append(createLotCard(lot, index)));
        window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
            resultsGrid.querySelectorAll('[data-reveal]').forEach(card => card.classList.add('is-visible'));
        }));
    };
    const setSearching = active => {
        isSearching = active;
        searchLabel.textContent = active ? 'Buscando lotes...' : 'Buscar lotes';
        syncSearchState();
    };

    locationSelect.addEventListener('change', () => {
        updateProjectOptions();
        updatePriceRanges();
    });
    projectSelect.addEventListener('change', updatePriceRanges);
    clearSearch.addEventListener('click', () => {
        form.reset();
        updateProjectOptions();
        updatePriceRanges();
        resultsGrid.replaceChildren();
        resultsFeedback.replaceChildren();
        resultsSummary.textContent = '';
        resultsSection.hidden = true;
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (searchButton.disabled) return;
        setSearching(true);
        resultsSection.hidden = false;
        resultsGrid.replaceChildren();
        resultsSummary.textContent = 'Consultando disponibilidad...';
        showResultMessage('Buscando lotes...', 'Estamos revisando la disponibilidad según tus filtros.');
        try {
            const parameters = new URLSearchParams();
            new FormData(form).forEach((value, key) => {
                if (String(value).trim() !== '') parameters.set(key, value);
            });
            const selectedPrice = priceSelect.selectedOptions[0];
            if (selectedPrice?.dataset.minPrice) parameters.set('min_price', selectedPrice.dataset.minPrice);
            if (selectedPrice?.dataset.maxPrice) parameters.set('max_price', selectedPrice.dataset.maxPrice);
            const response = await fetch(`${form.action}?${parameters.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('search_failed');
            renderLots(await response.json());
        } catch (error) {
            resultsGrid.replaceChildren();
            resultsSummary.textContent = '';
            showResultMessage('No pudimos consultar la disponibilidad en este momento.', 'Inténtalo nuevamente en unos minutos.');
        } finally {
            setSearching(false);
            scrollToResults();
        }
    });
    const runProjectLotSearch = async (projectId, location = '') => {
        locationSelect.value = location;
        updateProjectOptions();
        projectSelect.value = String(projectId);
        priceSelect.value = '';
        await updatePriceRanges();
        form.requestSubmit();
    };
    document.querySelectorAll('[data-project-lots]').forEach(button => button.addEventListener('click', () => {
        runProjectLotSearch(button.dataset.projectLots, button.dataset.location || '');
    }));
    const projectDialog = document.querySelector('#project-dialog');
    const projectContact = document.querySelector('#project-contact');
    const contactBase = projectContact.href;
    document.querySelectorAll('.project-open').forEach(button => button.addEventListener('click', () => {
        document.querySelector('#project-dialog-title').textContent = button.dataset.project;
        document.querySelector('#project-dialog-description').textContent = button.dataset.description;
        const url = new URL(contactBase);
        if (url.hostname === 'wa.me') url.searchParams.set('text', `Hola, quiero información sobre el proyecto ${button.dataset.project}.`);
        projectContact.href = url.href;
        projectDialog.showModal();
    }));
    projectContact.addEventListener('click', () => projectDialog.close());
    const mapSelect = document.querySelector('#map-project');
    const mapDialog = document.querySelector('#map-dialog');
    const availabilityModule = document.querySelector('#project-availability');
    if (mapSelect && availabilityModule) {
        const dynamic = document.querySelector('#availability-dynamic');
        const content = document.querySelector('#availability-content');
        const loading = document.querySelector('#availability-loading');
        const counts = document.querySelector('#availability-counts');
        const metrics = document.querySelector('#availability-metrics');
        const lotsButton = document.querySelector('#availability-lots-button');
        const contactButton = document.querySelector('#availability-contact');
        const planPicture = document.querySelector('#plan-picture');
        const planImage = document.querySelector('#plan-image');
        const planMobile = document.querySelector('#plan-mobile-source');
        const planEmpty = document.querySelector('#plan-empty');
        const openMap = document.querySelector('#open-map');
        const lotExplorer = document.querySelector('#project-lot-explorer');
        const lotExplorerTitle = document.querySelector('#project-lot-explorer-title');
        const lotExplorerSummary = document.querySelector('#project-lot-explorer-summary');
        const lotExplorerFeedback = document.querySelector('#project-lot-explorer-feedback');
        const lotExplorerGrid = document.querySelector('#project-lot-explorer-grid');
        const lotExplorerClose = document.querySelector('#project-lot-explorer-close');
        const lotExplorerAll = document.querySelector('#project-lot-explorer-all');
        let summaryRequest = null;
        let lotsRequest = null;
        let currentSummary = null;
        let explorerOpen = false;
        let explorerProjectId = null;

        const animateCount = (element, finalValue) => {
            if (reducedMotion) { element.textContent = finalValue; return; }
            const startedAt = performance.now();
            const tick = now => {
                const progress = Math.min(1, (now - startedAt) / 520);
                element.textContent = Math.round(finalValue * (1 - Math.pow(1 - progress, 3)));
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const commercialMessage = status => ({
            coming_soon: 'Un nuevo proyecto está por llegar.',
            presale: 'Sé de los primeros en conocer este proyecto.',
            sold_out: 'Actualmente no contamos con lotes disponibles en este proyecto.',
        })[status] || 'Consulta con un asesor la disponibilidad de este proyecto.';
        const noPlanMessage = status => ({
            coming_soon: 'Próximamente conocerás todos los detalles de este desarrollo.',
            presale: 'Muy pronto podrás conocer el plano y disponibilidad de este proyecto.',
        })[status] || 'Estamos preparando la información de disponibilidad de este proyecto.';
        const explorerButtonText = projectName => `Ver disponibilidad de ${projectName} ↓`;
        const syncExplorerButton = () => {
            const available = currentSummary?.availability?.available || 0;
            if (available < 1) return;
            lotsButton.textContent = explorerOpen ? 'Ocultar lotes ↑' : explorerButtonText(currentSummary?.project?.name || 'este proyecto');
        };
        const clearExplorer = () => {
            lotExplorerGrid.replaceChildren();
            lotExplorerFeedback.replaceChildren();
            lotExplorerSummary.textContent = '';
            lotExplorerAll.hidden = true;
            lotExplorerAll.removeAttribute('href');
        };
        const closeLotExplorer = (immediate = false) => {
            lotsRequest?.abort();
            lotsRequest = null;
            explorerOpen = false;
            explorerProjectId = null;
            lotExplorer.classList.remove('is-open');
            syncExplorerButton();

            const finish = () => {
                if (!explorerOpen) {
                    lotExplorer.hidden = true;
                    clearExplorer();
                }
            };

            if (immediate || reducedMotion) finish();
            else window.setTimeout(finish, 280);
        };
        const showExplorerMessage = (title, message, retry = null) => {
            lotExplorerFeedback.replaceChildren();
            const box = document.createElement('div');
            box.className = 'project-lot-explorer-message';
            const heading = document.createElement('strong');
            heading.textContent = title;
            const copy = document.createElement('p');
            copy.textContent = message;
            box.append(heading, copy);

            if (retry) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'text-button';
                button.textContent = 'Intentar nuevamente →';
                button.addEventListener('click', retry, { once: true });
                box.append(button);
            }

            lotExplorerFeedback.append(box);
        };
        const explorerBlockLabel = value => {
            const block = String(value || '').trim();
            if (/^mz\b/i.test(block)) return block.replace(/^mz\b/i, 'MANZANA');
            if (/^manzana\b/i.test(block)) return block.toUpperCase();
            return block ? `MANZANA ${block}` : 'MANZANA';
        };
        const createProjectLotCard = (lot, index) => {
            const card = document.createElement('article');
            card.className = 'project-lot-card';
            card.style.setProperty('--lot-delay', `${Math.min(index * 45, 225)}ms`);

            const block = document.createElement('span');
            block.className = 'project-lot-block';
            block.textContent = explorerBlockLabel(lot.block);

            const title = document.createElement('h4');
            title.textContent = `Lote ${lot.lot_number}`;

            card.append(block, title);

            if (lot.code) {
                const code = document.createElement('span');
                code.className = 'project-lot-code';
                code.textContent = lot.code;
                card.append(code);
            }

            const facts = document.createElement('div');
            facts.className = 'project-lot-facts';

            if (lot.area !== null) {
                const area = document.createElement('span');
                const areaLabel = document.createElement('small');
                const areaValue = document.createElement('strong');
                const unit = lot.unit_measure === 'm2' ? 'm²' : (lot.unit_measure || 'm²');
                areaLabel.textContent = 'ÁREA';
                areaValue.textContent = `${numberFormatter.format(lot.area)} ${unit}`;
                area.append(areaLabel, areaValue);
                facts.append(area);
            }

            if (lot.cash_price !== null) {
                const price = document.createElement('span');
                const priceLabel = document.createElement('small');
                const priceValue = document.createElement('strong');
                priceLabel.textContent = 'PRECIO CONTADO';
                priceValue.textContent = moneyFormatter.format(lot.cash_price);
                price.append(priceLabel, priceValue);
                facts.append(price);
            }

            const status = document.createElement('span');
            status.className = 'project-lot-status';
            const dot = document.createElement('i');
            status.append(dot, document.createTextNode(' Disponible'));

            card.append(facts, status);

            if (lot.whatsapp_url) {
                const contact = document.createElement('a');
                contact.className = 'project-lot-contact';
                contact.href = lot.whatsapp_url;
                contact.target = '_blank';
                contact.rel = 'noopener noreferrer';
                contact.textContent = 'Me interesa este lote →';
                card.append(contact);
            }

            return card;
        };
        const renderAvailableLots = result => {
            const lots = result.lots || [];
            const pagination = result.pagination || {};
            const total = pagination.total ?? lots.length;
            const option = mapSelect.selectedOptions[0];

            lotExplorerGrid.replaceChildren();
            lotExplorerFeedback.replaceChildren();
            lotExplorerTitle.textContent = result.project?.name || currentSummary?.project?.name || option?.textContent || 'Proyecto';

            const shown = Math.min(lots.length, total);
            lotExplorerSummary.textContent = total === 1
                ? '1 oportunidad disponible'
                : (total > shown ? `Mostrando ${shown} de ${total} oportunidades disponibles` : `${total} oportunidades disponibles`);

            if (!lots.length) {
                showExplorerMessage(
                    'La disponibilidad acaba de actualizarse.',
                    'En este momento no encontramos lotes disponibles en este proyecto. Puedes consultar nuevamente o comunicarte con un asesor.'
                );
                lotExplorerAll.hidden = true;
                loadAvailability();
                return;
            }

            lots.forEach((lot, index) => lotExplorerGrid.append(createProjectLotCard(lot, index)));
            window.requestAnimationFrame(() => {
                lotExplorerGrid.querySelectorAll('.project-lot-card').forEach(card => card.classList.add('is-visible'));
            });

            if (total > 3 && option?.dataset.allLotsUrl) {
                lotExplorerAll.href = option.dataset.allLotsUrl;
                lotExplorerAll.textContent = `Ver los ${total} lotes disponibles →`;
                lotExplorerAll.hidden = false;
            } else {
                lotExplorerAll.hidden = true;
                lotExplorerAll.removeAttribute('href');
            }
        };
        const loadAvailableLots = async () => {
            const option = mapSelect.selectedOptions[0];
            if (!option?.dataset.lotsUrl || !explorerOpen) return;

            lotsRequest?.abort();
            const request = new AbortController();
            lotsRequest = request;

            lotExplorerGrid.replaceChildren();
            showExplorerMessage('Consultando lotes disponibles...', 'Estamos revisando la disponibilidad actual del proyecto.');

            try {
                const url = new URL(option.dataset.lotsUrl, window.location.origin);
                url.searchParams.set('page', 1);
                url.searchParams.set('per_page', 3);
                const response = await fetch(url, {
                    signal: request.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error('available_lots_failed');
                if (!explorerOpen || explorerProjectId !== option.value) return;

                const result = await response.json();
                lotExplorerFeedback.replaceChildren();
                renderAvailableLots(result);
            } catch (error) {
                if (error.name === 'AbortError') return;
                showExplorerMessage(
                    'No pudimos consultar los lotes disponibles en este momento.',
                    'Inténtalo nuevamente en unos instantes.',
                    () => loadAvailableLots()
                );
            } finally {
                if (lotsRequest === request) lotsRequest = null;
            }
        };
        const openLotExplorer = () => {
            const option = mapSelect.selectedOptions[0];
            if (!option || !currentSummary?.availability?.available) return;

            explorerOpen = true;
            explorerProjectId = option.value;
            clearExplorer();
            lotExplorerTitle.textContent = currentSummary.project.name;
            lotExplorerSummary.textContent = currentSummary.availability.available === 1
                ? '1 oportunidad disponible'
                : `${currentSummary.availability.available} oportunidades disponibles`;
            lotExplorer.hidden = false;
            window.requestAnimationFrame(() => lotExplorer.classList.add('is-open'));
            syncExplorerButton();
            loadAvailableLots();
        };
        const renderAvailability = data => {
            currentSummary = data;
            const available = data.availability.available;
            const hasPublicCounts = available > 0;
            document.querySelector('#availability-status').textContent = data.project.commercial_status_label;
            document.querySelector('#map-title').textContent = data.project.name;
            document.querySelector('#map-status').textContent = data.project.commercial_status_label;
            document.querySelector('#availability-message').textContent = available > 0 ? '' : commercialMessage(data.project.commercial_status);
            counts.hidden = !hasPublicCounts;
            document.querySelector('#availability-current-label').hidden = !hasPublicCounts;
            counts.querySelectorAll('[data-count]').forEach(element => animateCount(element, data.availability[element.dataset.count]));
            const priceBlock = metrics.querySelector('[data-metric="price"]');
            const areaBlock = metrics.querySelector('[data-metric="area"]');
            priceBlock.hidden = available === 0 || data.availability.minimum_price === null;
            areaBlock.hidden = available === 0 || data.availability.minimum_area === null;
            metrics.hidden = priceBlock.hidden && areaBlock.hidden;
            if (!priceBlock.hidden) document.querySelector('#availability-price').textContent = moneyFormatter.format(data.availability.minimum_price);
            if (!areaBlock.hidden) document.querySelector('#availability-area').textContent = `${numberFormatter.format(data.availability.minimum_area)} m²`;
            lotsButton.hidden = available === 0;
            lotsButton.disabled = false;
            if (available > 0) lotsButton.textContent = explorerButtonText(data.project.name);
            contactButton.hidden = available > 0;
            if (available === 0) {
                const message = `Hola, estoy interesado en conocer más sobre el proyecto ${data.project.name}. ¿Podrían brindarme información?`;
                contactButton.href = `https://wa.me/${availabilityModule.dataset.whatsapp}?text=${encodeURIComponent(message)}`;
                contactButton.textContent = data.project.commercial_status === 'sold_out' ? 'Consultar nuevos proyectos →' : 'Quiero información →';
            }
            planPicture.hidden = !data.plan.url;
            planEmpty.hidden = Boolean(data.plan.url);
            openMap.hidden = !data.plan.url;
            document.querySelector('#plan-caption').textContent = data.plan.caption || '';
            document.querySelector('#plan-empty-message').textContent = noPlanMessage(data.project.commercial_status);
            if (data.plan.url) {
                planImage.src = data.plan.url;
                planImage.alt = data.plan.alt;
                planMobile.srcset = data.plan.mobile_url || data.plan.url;
            }
        };
        const loadAvailability = async () => {
            summaryRequest?.abort();
            const request = new AbortController();
            summaryRequest = request;
            dynamic.classList.add('is-loading');
            document.querySelector('#project-plan').classList.add('is-loading');
            loading.hidden = false;
            loading.textContent = 'Actualizando disponibilidad...';
            try {
                const option = mapSelect.selectedOptions[0];
                const response = await fetch(option.dataset.summaryUrl, { signal: request.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('availability_failed');
                renderAvailability(await response.json());
                content.hidden = false;
                loading.hidden = true;
            } catch (error) {
                if (error.name === 'AbortError') return;
                content.hidden = true;
                loading.hidden = false;
                loading.textContent = 'No pudimos consultar la disponibilidad de este proyecto en este momento.';
            } finally {
                if (summaryRequest === request) {
                    summaryRequest = null;
                    dynamic.classList.remove('is-loading');
                    document.querySelector('#project-plan').classList.remove('is-loading');
                }
            }
        };
        mapSelect.addEventListener('change', () => {
            closeLotExplorer(true);
            currentSummary = null;
            loadAvailability();
        });
        lotsButton.addEventListener('click', () => explorerOpen ? closeLotExplorer() : openLotExplorer());
        lotExplorerClose.addEventListener('click', () => closeLotExplorer());
        openMap.addEventListener('click', () => {
            if (!currentSummary?.plan.url) return;
            document.querySelector('#map-dialog-title').textContent = currentSummary.project.name;
            const dialogImage = document.querySelector('#map-dialog-image');
            dialogImage.src = window.innerWidth <= 760 ? currentSummary.plan.mobile_url : currentSummary.plan.url;
            dialogImage.alt = currentSummary.plan.alt;
            document.querySelector('#map-dialog-caption').textContent = currentSummary.plan.caption || '';
            mapDialog.showModal();
        });
        loadAvailability();
    }
    const investSection = document.querySelector('[data-invest-section]');
    if (investSection) {
        const locationDialog = document.querySelector('#invest-location-dialog');
        const simulatorDialog = document.querySelector('#invest-simulator-dialog');
        const processDialog = document.querySelector('#invest-process-dialog');
        const locationProject = document.querySelector('#invest-location-project');
        const locationCompany = document.querySelector('#invest-location-company');
        const locationPlace = document.querySelector('#invest-location-place');
        const locationStatus = document.querySelector('#invest-location-status');
        const locationAvailability = document.querySelector('#invest-location-availability');
        const simulatorProject = document.querySelector('#invest-simulator-project');
        const simulatorAmount = document.querySelector('#invest-simulator-amount');
        const simulatorInitial = document.querySelector('#invest-simulator-initial');
        const simulatorMonths = document.querySelector('#invest-simulator-months');
        const simulatorBalance = document.querySelector('#invest-simulator-balance');
        const simulatorMonthly = document.querySelector('#invest-simulator-monthly');
        const simulatorSource = document.querySelector('#invest-simulator-source');
        const simulatorLots = document.querySelector('#invest-simulator-lots');
        let locationRequest = null;
        let simulatorRequest = null;

        const openInvestDialog = name => {
            const dialog = { location: locationDialog, simulator: simulatorDialog, process: processDialog }[name];
            if (!dialog) return;
            dialog.showModal();
            if (name === 'location' && locationProject) loadInvestLocation();
            if (name === 'simulator' && simulatorProject) loadSimulatorProject();
        };

        const focusAvailabilityProject = projectId => {
            const availabilitySection = document.querySelector('#disponibilidad');
            if (mapSelect && projectId) {
                const exists = [...mapSelect.options].some(option => option.value === String(projectId));
                if (exists) {
                    const changed = mapSelect.value !== String(projectId);
                    mapSelect.value = String(projectId);
                    if (changed) mapSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            availabilitySection?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        };

        const loadInvestLocation = async () => {
            if (!locationProject) return;
            locationRequest?.abort();
            const request = new AbortController();
            locationRequest = request;
            locationCompany.textContent = 'Consultando...';
            locationPlace.textContent = '—';
            locationStatus.textContent = '—';
            locationAvailability.disabled = true;
            try {
                const option = locationProject.selectedOptions[0];
                const response = await fetch(option.dataset.summaryUrl, {
                    signal: request.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('invest_location_failed');
                const result = await response.json();
                if (locationRequest !== request) return;
                locationCompany.textContent = result.project.company || 'Información comercial disponible';
                locationPlace.textContent = result.project.location || 'Ubicación por confirmar';
                locationStatus.textContent = result.project.commercial_status_label || 'PROYECTO PUBLICADO';
                locationAvailability.disabled = false;
            } catch (error) {
                if (error.name === 'AbortError') return;
                locationCompany.textContent = 'No pudimos consultar el proyecto';
                locationPlace.textContent = 'Inténtalo nuevamente';
                locationStatus.textContent = '—';
            } finally {
                if (locationRequest === request) locationRequest = null;
            }
        };

        const calculateBudget = () => {
            if (!simulatorAmount || !simulatorInitial || !simulatorMonths) return;
            const amount = Math.max(0, Number(simulatorAmount.value) || 0);
            const initial = Math.min(amount, Math.max(0, Number(simulatorInitial.value) || 0));
            const months = Math.max(1, Number(simulatorMonths.value) || 1);
            const balance = Math.max(0, amount - initial);
            simulatorBalance.textContent = moneyFormatter.format(balance);
            simulatorMonthly.textContent = moneyFormatter.format(balance / months);
        };

        const loadSimulatorProject = async () => {
            if (!simulatorProject) return;
            simulatorRequest?.abort();
            const request = new AbortController();
            simulatorRequest = request;
            simulatorSource.textContent = 'Consultando precio disponible del proyecto...';
            simulatorLots.hidden = true;
            try {
                const option = simulatorProject.selectedOptions[0];
                const response = await fetch(option.dataset.summaryUrl, {
                    signal: request.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('invest_simulator_failed');
                const result = await response.json();
                if (simulatorRequest !== request) return;
                const minimumPrice = result.availability?.minimum_price;
                if (minimumPrice !== null && minimumPrice !== undefined) {
                    simulatorAmount.value = Number(minimumPrice).toFixed(2);
                    simulatorInitial.value = '0';
                    simulatorSource.textContent = `Referencia cargada desde el precio contado mínimo disponible de ${result.project.name}: ${moneyFormatter.format(minimumPrice)}.`;
                } else {
                    simulatorAmount.value = '';
                    simulatorInitial.value = '0';
                    simulatorSource.textContent = `Actualmente ${result.project.name} no publica un precio disponible. Puedes ingresar manualmente un monto para organizarlo.`;
                }
                simulatorLots.href = option.dataset.lotsUrl || '#disponibilidad';
                simulatorLots.hidden = !(option.dataset.lotsUrl && Number(result.availability?.available || 0) > 0);
                calculateBudget();
            } catch (error) {
                if (error.name === 'AbortError') return;
                simulatorSource.textContent = 'No pudimos cargar una referencia del proyecto. Puedes ingresar manualmente un monto.';
                simulatorLots.hidden = true;
                calculateBudget();
            } finally {
                if (simulatorRequest === request) simulatorRequest = null;
            }
        };

        document.querySelectorAll('[data-invest-open]').forEach(button => {
            button.addEventListener('click', () => openInvestDialog(button.dataset.investOpen));
        });
        document.querySelectorAll('[data-invest-scroll]').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();
                focusAvailabilityProject(null);
            });
        });
        locationProject?.addEventListener('change', loadInvestLocation);
        locationAvailability?.addEventListener('click', () => {
            const projectId = locationProject?.value;
            locationDialog?.close();
            focusAvailabilityProject(projectId);
        });
        simulatorProject?.addEventListener('change', loadSimulatorProject);
        [simulatorAmount, simulatorInitial, simulatorMonths].forEach(control => control?.addEventListener('input', calculateBudget));
        simulatorLots?.addEventListener('click', () => simulatorDialog?.close());
        document.querySelectorAll('[data-dialog-scroll]').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault();
                const target = document.querySelector(`#${link.dataset.dialogScroll}`);
                link.closest('dialog')?.close();
                target?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
            });
        });
    }
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) { const bounds = dialog.getBoundingClientRect(); if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close(); } });
    });
})();
