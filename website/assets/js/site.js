(function () {
    'use strict';

    var dataLayer = (window.dataLayer = window.dataLayer || []);
    var pageId = document.body.getAttribute('data-page') || location.pathname;

    function track(event, detail) {
        var payload = Object.assign({ event: event, page: pageId }, detail || {});
        dataLayer.push(payload);
        document.dispatchEvent(new CustomEvent('rumu:track', { detail: payload }));
    }

    // Mobile navigation
    var navToggle = document.querySelector('[data-nav-toggle]');
    var nav = document.getElementById('site-nav');
    if (navToggle && nav) {
        var closeNav = function () {
            nav.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
        };
        navToggle.addEventListener('click', function () {
            var open = navToggle.getAttribute('aria-expanded') === 'true';
            nav.classList.toggle('is-open', !open);
            navToggle.setAttribute('aria-expanded', String(!open));
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeNav();
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) closeNav();
        });
    }

    // Edition toggle (home hero)
    var editionButtons = Array.prototype.slice.call(document.querySelectorAll('[data-edition-btn]'));
    if (editionButtons.length) {
        var setEdition = function (edition) {
            editionButtons.forEach(function (button) {
                button.setAttribute('aria-selected', String(button.getAttribute('data-edition-btn') === edition));
            });
            document.querySelectorAll('[data-edition-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-edition-panel') !== edition;
            });
            track('edition_toggle', { edition: edition });
        };
        editionButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setEdition(button.getAttribute('data-edition-btn'));
            });
        });

        // Keyboard support for the tab-style toggle
        editionButtons.forEach(function (button, index) {
            button.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                var next = editionButtons[(index + (event.key === 'ArrowRight' ? 1 : editionButtons.length - 1)) % editionButtons.length];
                next.focus();
                setEdition(next.getAttribute('data-edition-btn'));
            });
        });
    }

    // Generic tabs (features, pricing)
    var tabGroups = document.querySelectorAll('[data-tabs]');
    tabGroups.forEach(function (group) {
        var buttons = Array.prototype.slice.call(group.querySelectorAll('[data-tab-target]'));
        var panels = Array.prototype.slice.call(group.parentElement.querySelectorAll('[data-tab-panel]'));
        var activate = function (value, emit) {
            buttons.forEach(function (button) {
                var selected = button.getAttribute('data-tab-target') === value;
                button.setAttribute('aria-selected', String(selected));
                button.tabIndex = selected ? 0 : -1;
            });
            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-tab-panel') !== value;
            });
            if (emit) track('tab_change', { tab: value });
        };

        buttons.forEach(function (button, index) {
            button.addEventListener('click', function () {
                activate(button.getAttribute('data-tab-target'), true);
            });
            button.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                var next = buttons[(index + (event.key === 'ArrowRight' ? 1 : buttons.length - 1)) % buttons.length];
                next.focus();
                activate(next.getAttribute('data-tab-target'), true);
            });
        });

        var params = new URLSearchParams(window.location.search);
        var requested = params.get('edition') || window.location.hash.replace('#', '');
        var available = buttons.map(function (button) { return button.getAttribute('data-tab-target'); });
        if (requested && available.indexOf(requested) !== -1) {
            activate(requested, false);
        }
    });

    // FAQ tracking
    document.querySelectorAll('.faq-item').forEach(function (item, index) {
        item.addEventListener('toggle', function () {
            if (item.open) track('faq_open', { index: index, question: item.querySelector('summary') ? item.querySelector('summary').textContent.trim() : '' });
        });
    });

    // CTA + login + plan tracking
    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-event]');
        if (!target) return;
        var name = target.getAttribute('data-event');
        if (name === 'cta_click') {
            track('cta_click', { cta: target.getAttribute('data-cta') || target.textContent.trim(), edition: currentEdition() });
        } else if (name === 'login_click') {
            track('login_click', { edition: currentEdition() });
        } else if (name === 'plan_cta') {
            track('plan_cta', { plan: target.getAttribute('data-plan') || '', edition: currentEdition() });
        } else if (name === 'pricing_view') {
            track('pricing_view');
        }
    });

    // Screenshot engagement
    document.querySelectorAll('.product-preview').forEach(function (figure) {
        figure.addEventListener('click', function () {
            track('screenshot_open', { screenshot: figure.className.replace('product-preview', '').trim() });
        }, { once: true });
    });

    // Header elevation
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    function currentEdition() {
        var active = document.querySelector('[data-edition-btn][aria-selected="true"]');
        if (active) return active.getAttribute('data-edition-btn');
        var tab = document.querySelector('[data-tabs] [aria-selected="true"]');
        if (tab) return tab.getAttribute('data-tab-target');
        return document.body.getAttribute('data-edition') || '';
    }

    if (document.body.getAttribute('data-page') === 'pricing') {
        track('pricing_view');
    }
})();
