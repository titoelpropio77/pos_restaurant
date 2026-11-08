/*
 * Menú digital público: resalta la categoría visible, busca platos y muestra el detalle de cada uno.
 * Sin dependencias: corre en cualquier celular moderno.
 */
(function () {
    'use strict';

    var dishes = JSON.parse(document.getElementById('menu-data').textContent || '{}');
    var nav = document.querySelector('.nav');
    var chips = document.querySelector('.js-chips');
    var sections = Array.prototype.slice.call(document.querySelectorAll('.js-category'));

    /* ---------- Categoría activa al desplazarse ---------- */
    function activate(id) {
        if (!chips) return;
        var current = chips.querySelector('.is-active');
        var next = chips.querySelector('[data-target="' + id + '"]');
        if (current === next || !next) return;
        if (current) current.classList.remove('is-active');
        next.classList.add('is-active');
        // Mantiene visible la categoría activa dentro de la barra, sin mover la página
        chips.scrollTo({ left: next.offsetLeft - chips.clientWidth / 2 + next.clientWidth / 2, behavior: 'smooth' });
    }

    if (sections.length && 'IntersectionObserver' in window) {
        var visible = {};
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) { visible[entry.target.id] = entry.isIntersecting; });
            var first = sections.find(function (s) { return visible[s.id] && !s.hidden; });
            if (first) activate(first.id);
        }, { rootMargin: '-80px 0px -55% 0px' });
        sections.forEach(function (s) { observer.observe(s); });
        activate(sections[0].id);
    }

    /* ---------- Búsqueda ---------- */
    var input = document.querySelector('.js-search');
    var noResults = document.querySelector('.js-no-results');

    function normalize(text) {
        return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    }

    function filter() {
        var term = normalize(input.value);
        var any = false;
        sections.forEach(function (section) {
            var shown = 0;
            section.querySelectorAll('.js-dish').forEach(function (li) {
                var match = !term || li.getAttribute('data-search').indexOf(term) !== -1;
                li.hidden = !match;
                if (match) shown++;
            });
            section.hidden = shown === 0;
            any = any || shown > 0;
        });
        noResults.hidden = any;
    }

    if (input) {
        input.addEventListener('input', filter);
        document.querySelector('.js-open-search').addEventListener('click', function () {
            nav.classList.add('is-searching');
            input.focus();
        });
        document.querySelector('.js-close-search').addEventListener('click', function () {
            input.value = '';
            filter();
            nav.classList.remove('is-searching');
        });
    }

    /* ---------- Detalle del plato ---------- */
    var sheet = document.querySelector('.js-sheet');
    var photo = sheet.querySelector('.js-sheet-photo');

    function open(id) {
        var dish = dishes[id];
        if (!dish) return;
        photo.hidden = !dish.image;
        photo.querySelector('img').src = dish.image || '';
        sheet.querySelector('.sheet-name').textContent = dish.name;
        var desc = sheet.querySelector('.sheet-desc');
        desc.textContent = dish.description;
        desc.hidden = !dish.description;
        var price = sheet.querySelector('.price');
        price.innerHTML = dish.price ? '<small>Bs</small>' : '';
        price.appendChild(document.createTextNode(dish.price));
        var tag = sheet.querySelector('.sheet-tag');
        tag.textContent = !dish.available
            ? (dish.schedule ? 'Disponible: ' + dish.schedule : 'No disponible ahora')
            : (dish.schedule || '');
        tag.classList.toggle('tag-off', !dish.available);
        if (typeof sheet.showModal === 'function') {
            sheet.showModal();
        } else {
            sheet.setAttribute('open', '');
        }
    }

    function close() {
        if (typeof sheet.close === 'function') sheet.close(); else sheet.removeAttribute('open');
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.dish');
        if (button) open(button.getAttribute('data-id'));
    });
    sheet.querySelector('.js-close-sheet').addEventListener('click', close);
    // Tocar fuera de la tarjeta (sobre el fondo oscurecido) también cierra
    sheet.addEventListener('click', function (event) {
        if (event.target === sheet) close();
    });
})();
