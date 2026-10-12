/*
 * Site public de CSB — interactions (cahier §8 « présentation moderne », §11.2 « scripts limités »).
 * JavaScript local, sans bibliothèque ni CDN (D15). Chaque comportement est une amélioration : sans JavaScript, les
 * liens, filtres et formulaires fonctionnent normalement (rechargement de page).
 *
 *  - en-tête réduit au défilement, barre de progression, bouton « retour en haut » ;
 *  - apparition progressive des blocs (IntersectionObserver), compteurs animés [data-count] ;
 *  - catalogues filtrés en AJAX : form[data-ajax-filter] → zone [data-ajax-results] (adresse et historique à jour) ;
 *  - formulaires envoyés en AJAX : form[data-ajax-form] (réponse JSON, erreurs sous les champs, message de succès) ;
 *  - galerie plein écran (clavier, balayage), notifications (CSB.toast).
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var CSB = window.CSB = window.CSB || {};

    /* ——— Utilitaires ——— */
    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function announce(message) {
        var live = document.getElementById('csb-live');
        if (!live) {
            live = document.createElement('div');
            live.id = 'csb-live';
            live.className = 'visually-hidden';
            live.setAttribute('aria-live', 'polite');
            document.body.appendChild(live);
        }
        live.textContent = '';
        setTimeout(function () { live.textContent = message; }, 50);
    }

    /* ——— Notifications ——— */
    CSB.toast = function (message, type) {
        var stack = document.querySelector('.csb-toasts');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'csb-toasts';
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        var toast = document.createElement('div');
        toast.className = 'csb-toast csb-toast-' + (type || 'success');
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        var icon = document.createElement('i');
        icon.className = 'bx ' + (type === 'error' ? 'bx-error-circle' : 'bx-check-circle');
        icon.setAttribute('aria-hidden', 'true');
        var text = document.createElement('span');
        text.textContent = message;
        var close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Fermer');
        close.innerHTML = '<i class="bx bx-x" aria-hidden="true"></i>';
        close.addEventListener('click', function () { toast.remove(); });
        toast.append(icon, text, close);
        stack.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('show'); });
        setTimeout(function () { toast.classList.remove('show'); setTimeout(function () { toast.remove(); }, 400); }, 6000);
    };

    /* ——— En-tête, progression, retour en haut ——— */
    function initScroll() {
        var header = document.querySelector('.site-header');
        var progress = document.createElement('div');
        progress.className = 'scroll-progress';
        progress.setAttribute('aria-hidden', 'true');
        document.body.appendChild(progress);

        var top = document.createElement('button');
        top.type = 'button';
        top.className = 'back-top';
        top.setAttribute('aria-label', 'Revenir en haut de la page');
        top.innerHTML = '<i class="bx bx-chevron-up" aria-hidden="true"></i>';
        top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); });
        document.body.appendChild(top);

        var ticking = false;
        function update() {
            var y = window.scrollY;
            var max = document.documentElement.scrollHeight - window.innerHeight;
            if (header) header.classList.toggle('is-scrolled', y > 40);
            progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
            top.classList.toggle('show', y > 600);
            ticking = false;
        }
        window.addEventListener('scroll', function () {
            if (!ticking) { requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    }

    /* ——— Apparition progressive et compteurs ——— */
    var revealSelector = '[data-reveal], .section-title, .csb-card, .testimonial, .video-testimonial, .share-cta, .steps li, .detail-panel, .cta-band, .stat, .hero-tile, .filter-bar';

    function initReveal(root) {
        var items = (root || document).querySelectorAll(revealSelector);
        if (reduceMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        items.forEach(function (el) {
            if (el.classList.contains('is-visible')) return;
            // Décalage en cascade des colonnes d'une même grille.
            var col = el.closest('[class*="col-"]');
            var index = col && col.parentElement ? Array.prototype.indexOf.call(col.parentElement.children, col) : 0;
            el.style.setProperty('--reveal-delay', (index > 0 ? Math.min(index, 6) * 70 : 0) + 'ms');
            el.classList.add('reveal');
            observer.observe(el);
        });
    }

    function initCounters(root) {
        (root || document).querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (reduceMotion || !('IntersectionObserver' in window)) { el.textContent = target.toLocaleString('fr-FR'); return; }
            var observer = new IntersectionObserver(function (entries) {
                if (!entries[0].isIntersecting) return;
                observer.disconnect();
                var start = performance.now(), duration = 1200;
                (function frame(now) {
                    var p = Math.min(1, (now - start) / duration);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('fr-FR');
                    if (p < 1) requestAnimationFrame(frame);
                })(start);
            });
            observer.observe(el);
        });
    }

    /* ——— Catalogues filtrés en AJAX ——— */
    function initAjaxFilters() {
        document.querySelectorAll('form[data-ajax-filter]').forEach(function (form) {
            var target = document.querySelector(form.getAttribute('data-ajax-filter'));
            if (!target || !window.fetch || !window.history.pushState) return;
            var controller = null;

            function load(url, push) {
                if (controller) controller.abort();
                controller = new AbortController();
                target.classList.add('is-loading');
                target.setAttribute('aria-busy', 'true');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, signal: controller.signal })
                    .then(function (response) {
                        if (!response.ok) throw new Error(response.status);
                        return response.text();
                    })
                    .then(function (html) {
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.querySelector(form.getAttribute('data-ajax-filter'));
                        if (!fresh) { window.location.href = url; return; }
                        target.innerHTML = fresh.innerHTML;
                        if (doc.title) document.title = doc.title;
                        if (push) window.history.pushState({ csbFilter: true }, '', url);
                        initReveal(target);
                        var count = target.querySelector('[data-results-count]');
                        announce(count ? count.textContent.trim() : 'Résultats mis à jour.');
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') window.location.href = url;
                    })
                    .finally(function () {
                        target.classList.remove('is-loading');
                        target.removeAttribute('aria-busy');
                    });
            }

            function urlFromForm() {
                var params = new URLSearchParams();
                new FormData(form).forEach(function (value, key) { if (String(value).trim() !== '') params.append(key, value); });
                var query = params.toString();
                return form.action + (query ? '?' + query : '');
            }

            form.addEventListener('submit', function (event) { event.preventDefault(); load(urlFromForm(), true); });
            form.querySelectorAll('select, input[type=radio], input[type=checkbox]').forEach(function (input) {
                input.addEventListener('change', function () { load(urlFromForm(), true); });
            });
            form.querySelectorAll('input[type=search], input[type=text]').forEach(function (input) {
                input.addEventListener('input', debounce(function () { load(urlFromForm(), true); }, 350));
            });

            // Pagination, pastilles et « Effacer les filtres » dans la zone de résultats.
            target.addEventListener('click', function (event) {
                var link = event.target.closest('a[data-ajax-link], .pagination a');
                if (!link || link.target === '_blank') return;
                event.preventDefault();
                load(link.href, true);
                syncForm(link.href);
                if (link.closest('.pagination')) {
                    var top = target.getBoundingClientRect().top + window.scrollY - 110;
                    window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
                }
            });

            function syncForm(url) {
                var params = new URL(url, window.location.origin).searchParams;
                form.querySelectorAll('[name]').forEach(function (input) {
                    if (input.name === 'page') return;
                    input.value = params.get(input.name) || '';
                });
            }

            window.addEventListener('popstate', function () {
                syncForm(window.location.href);
                load(window.location.href, false);
            });
        });
    }

    /* ——— Formulaires envoyés en AJAX ——— */
    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); el.removeAttribute('aria-invalid'); });
        form.querySelectorAll('[data-ajax-error]').forEach(function (el) { el.remove(); });
        var summary = form.querySelector('[data-error-summary]');
        if (summary) summary.hidden = true;
    }

    function showErrors(form, errors) {
        var first = null;
        Object.keys(errors).forEach(function (key) {
            var name = key.replace(/\.(\d+)$/, '[]').replace(/\.(\w+)/g, '[$1]');
            var field = form.querySelector('[name="' + key + '"], [name="' + name + '"], [name="' + key + '[]"]');
            if (!field) return;
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            var feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.setAttribute('data-ajax-error', '');
            feedback.id = (field.id || field.name) + '-error';
            feedback.textContent = errors[key][0];
            var anchor = field.closest('.form-check, .input-group') || field;
            anchor.insertAdjacentElement('afterend', feedback);
            field.setAttribute('aria-describedby', feedback.id);
            first = first || field;
        });
        var summary = form.querySelector('[data-error-summary]');
        if (summary) { summary.hidden = false; summary.focus(); }
        else if (first) first.focus();
    }

    function initAjaxForms() {
        document.querySelectorAll('form[data-ajax-form]').forEach(function (form) {
            if (!window.fetch) return;
            form.addEventListener('submit', function (event) {
                if (form.dataset.sending === '1') { event.preventDefault(); return; }
                event.preventDefault();
                clearErrors(form);
                var button = form.querySelector('[type=submit]');
                var label = button ? button.innerHTML : '';
                form.dataset.sending = '1';
                if (button) { button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Envoi en cours…'; }

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                })
                    .then(function (response) {
                        return response.json().catch(function () { return {}; }).then(function (data) { return { status: response.status, data: data }; });
                    })
                    .then(function (result) {
                        if (result.status === 422) {
                            showErrors(form, result.data.errors || {});
                            var count = Object.keys(result.data.errors || {}).length;
                            CSB.toast(count > 1 ? count + ' champs sont à corriger : ils sont signalés en rouge.' : 'Un champ est à corriger : il est signalé en rouge.', 'error');
                            return;
                        }
                        if (result.status === 429) {
                            CSB.toast('Trop d\'envois en peu de temps : patientez une minute avant de réessayer.', 'error');
                            return;
                        }
                        if (result.status >= 400 || !result.data.message) {
                            CSB.toast('L\'envoi a échoué. Vos informations sont conservées : réessayez, ou contactez-nous par téléphone ou WhatsApp.', 'error');
                            return;
                        }
                        var success = document.createElement('div');
                        success.className = 'form-success';
                        success.setAttribute('role', 'status');
                        success.setAttribute('tabindex', '-1');
                        success.innerHTML = '<span class="form-success-icon"><i class="bx bx-check" aria-hidden="true"></i></span><h2 class="h4"></h2><p></p>';
                        success.querySelector('h2').textContent = result.data.title || 'Message envoyé';
                        success.querySelector('p').textContent = result.data.message;
                        form.replaceWith(success);
                        success.focus();
                        success.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
                    })
                    .catch(function () {
                        CSB.toast('Connexion impossible. Vos informations sont conservées : réessayez dans un instant.', 'error');
                    })
                    .finally(function () {
                        delete form.dataset.sending;
                        if (button && document.body.contains(button)) { button.disabled = false; button.innerHTML = label; }
                    });
            });
        });
    }

    /* ——— Galerie plein écran ——— */
    function initLightbox() {
        document.addEventListener('click', function (event) {
            var main = event.target.closest('[data-gallery-main]');
            if (!main) return;
            var gallery = main.closest('[data-gallery]');
            var buttons = gallery.querySelectorAll('.gallery-thumbs button');
            var images = buttons.length
                ? Array.prototype.map.call(buttons, function (b) { return { src: b.dataset.src, alt: b.dataset.alt }; })
                : [{ src: main.src, alt: main.alt }];
            var index = Math.max(0, images.findIndex(function (img) { return main.src.indexOf(img.src) !== -1 || img.src.indexOf(main.getAttribute('src')) !== -1; }));
            openLightbox(images, index, main);
        });
    }

    function openLightbox(images, index, opener) {
        var box = document.createElement('div');
        box.className = 'lightbox';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', 'Image en plein écran');
        box.innerHTML = '<button type="button" class="lightbox-close" aria-label="Fermer"><i class="bx bx-x" aria-hidden="true"></i></button>'
            + (images.length > 1 ? '<button type="button" class="lightbox-nav prev" aria-label="Image précédente"><i class="bx bx-chevron-left" aria-hidden="true"></i></button><button type="button" class="lightbox-nav next" aria-label="Image suivante"><i class="bx bx-chevron-right" aria-hidden="true"></i></button>' : '')
            + '<figure><img alt=""><figcaption></figcaption></figure>';
        document.body.appendChild(box);
        document.body.style.overflow = 'hidden';
        var img = box.querySelector('img'), caption = box.querySelector('figcaption');

        function show(i) {
            index = (i + images.length) % images.length;
            img.src = images[index].src;
            img.alt = images[index].alt || '';
            caption.textContent = (images[index].alt || '') + (images.length > 1 ? ' — ' + (index + 1) + ' / ' + images.length : '');
        }
        function close() {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
            box.remove();
            if (opener) opener.focus();
        }
        function onKey(e) {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowRight') show(index + 1);
            if (e.key === 'ArrowLeft') show(index - 1);
        }
        box.addEventListener('click', function (e) {
            if (e.target === box || e.target.closest('.lightbox-close')) close();
            if (e.target.closest('.prev')) show(index - 1);
            if (e.target.closest('.next')) show(index + 1);
        });
        var startX = null;
        box.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        box.addEventListener('touchend', function (e) {
            if (startX === null) return;
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
            startX = null;
        });
        document.addEventListener('keydown', onKey);
        show(index);
        requestAnimationFrame(function () { box.classList.add('show'); });
        box.querySelector('.lightbox-close').focus();
    }

    /* ——— Recherche en direct ——— */
    function initSearch() {
        var overlay = document.getElementById('search-overlay');
        if (!overlay || !window.fetch) return;
        var input = overlay.querySelector('input');
        var results = overlay.querySelector('.search-suggestions');
        var opener = null, controller = null;

        function open(event) {
            event.preventDefault();
            opener = event.currentTarget;
            overlay.hidden = false;
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function () { overlay.classList.add('show'); input.focus(); });
        }
        function close() {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            setTimeout(function () { overlay.hidden = true; }, 200);
            if (opener) opener.focus();
        }
        function render(data) {
            results.innerHTML = '';
            if (!data.groups || !data.groups.length) {
                results.innerHTML = input.value.trim().length >= 2 ? '<p class="search-empty">Aucun résultat. Appuyez sur Entrée pour une recherche complète.</p>' : '';
                return;
            }
            data.groups.forEach(function (group) {
                var title = document.createElement('div');
                title.className = 'search-group';
                title.textContent = group.label;
                results.appendChild(title);
                group.items.forEach(function (item) {
                    var a = document.createElement('a');
                    a.href = item.url;
                    a.className = 'search-item';
                    var strong = document.createElement('strong');
                    strong.textContent = item.title;
                    var span = document.createElement('span');
                    span.textContent = item.text;
                    a.append(strong, span);
                    results.appendChild(a);
                });
            });
            var all = document.createElement('a');
            all.href = data.all;
            all.className = 'search-all';
            all.textContent = 'Voir tous les résultats →';
            results.appendChild(all);
        }
        var suggest = debounce(function () {
            var term = input.value.trim();
            if (controller) controller.abort();
            if (term.length < 2) { results.innerHTML = ''; return; }
            controller = new AbortController();
            fetch(overlay.dataset.suggestUrl + '?q=' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' }, signal: controller.signal })
                .then(function (r) { return r.json(); }).then(render).catch(function () {});
        }, 220);

        document.querySelectorAll('[data-search-open]').forEach(function (el) { el.addEventListener('click', open); });
        overlay.querySelector('[data-search-close]').addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        input.addEventListener('input', suggest);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.hidden) close();
            // « / » ouvre la recherche (hors champ de saisie).
            if (e.key === '/' && overlay.hidden && !/input|textarea|select/i.test(document.activeElement.tagName)) {
                e.preventDefault();
                open({ preventDefault: function () {}, currentTarget: document.querySelector('[data-search-open]') });
            }
        });
    }

    /* ——— Effets visuels (site-fx.css) ——— */

    // Réseau de particules du héros : points reliés qui dérivent et s'attirent vers la souris. Mis en pause hors écran.
    function initParticles() {
        var canvas = document.querySelector('.hero-particles');
        if (!canvas || reduceMotion || !canvas.getContext) return;
        var ctx = canvas.getContext('2d'), hero = canvas.parentElement, dpr = Math.min(window.devicePixelRatio || 1, 2);
        var points = [], mouse = { x: -9999, y: -9999 }, running = true, w = 0, h = 0;

        function resize() {
            w = hero.clientWidth; h = hero.clientHeight;
            canvas.width = w * dpr; canvas.height = h * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            var count = Math.min(90, Math.round(w * h / 16000));
            points = [];
            for (var i = 0; i < count; i++) {
                points.push({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - .5) * .35, vy: (Math.random() - .5) * .35, r: Math.random() * 1.8 + .6, o: Math.random() < .2 });
            }
        }
        function frame() {
            if (!running) return;
            ctx.clearRect(0, 0, w, h);
            for (var i = 0; i < points.length; i++) {
                var p = points[i];
                var dxm = mouse.x - p.x, dym = mouse.y - p.y, dm = Math.sqrt(dxm * dxm + dym * dym);
                if (dm < 180) { p.vx += dxm / dm * .02; p.vy += dym / dm * .02; }
                p.vx *= .985; p.vy *= .985;
                p.vx += (Math.random() - .5) * .02; p.vy += (Math.random() - .5) * .02;
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0 || p.x > w) p.vx *= -1;
                if (p.y < 0 || p.y > h) p.vy *= -1;
                for (var j = i + 1; j < points.length; j++) {
                    var q = points[j], dx = p.x - q.x, dy = p.y - q.y, d = dx * dx + dy * dy;
                    if (d < 15000) {
                        ctx.strokeStyle = 'rgba(147, 197, 253,' + (1 - d / 15000) * .35 + ')';
                        ctx.lineWidth = 1;
                        ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
                    }
                }
                ctx.fillStyle = p.o ? 'rgba(255, 166, 87, .95)' : 'rgba(255, 255, 255, .85)';
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill();
            }
            requestAnimationFrame(frame);
        }
        hero.addEventListener('mousemove', function (e) { var b = hero.getBoundingClientRect(); mouse.x = e.clientX - b.left; mouse.y = e.clientY - b.top; });
        hero.addEventListener('mouseleave', function () { mouse.x = mouse.y = -9999; });
        new IntersectionObserver(function (entries) {
            var was = running; running = entries[0].isIntersecting;
            if (running && !was) requestAnimationFrame(frame);
        }).observe(hero);
        window.addEventListener('resize', debounce(resize, 200));
        resize();
        requestAnimationFrame(frame);
    }

    // Mots qui défilent sous le titre (data-rotator='["vos écoles", …]').
    function initRotator() {
        document.querySelectorAll('[data-rotator]').forEach(function (el) {
            var words = JSON.parse(el.getAttribute('data-rotator') || '[]'), i = 0;
            var word = el.querySelector('.rotator-word');
            if (words.length < 2 || reduceMotion) return;
            setInterval(function () {
                word.classList.remove('in'); word.classList.add('out');
                setTimeout(function () {
                    i = (i + 1) % words.length;
                    word.textContent = words[i];
                    word.classList.remove('out'); word.classList.add('in');
                }, 330);
            }, 2600);
        });
    }

    // Inclinaison 3D qui suit la souris, avec reflet (cartes [data-tilt], maquette [data-tilt-stage]).
    function initTilt() {
        if (reduceMotion || !window.matchMedia('(hover: hover)').matches) return;
        document.querySelectorAll('[data-tilt]').forEach(function (card) {
            if (!card.querySelector('.domain-glare, .tilt-glare')) {
                var glare = document.createElement('span');
                glare.className = 'tilt-glare';
                card.appendChild(glare);
            }
            card.addEventListener('mousemove', function (e) {
                var b = card.getBoundingClientRect(), x = (e.clientX - b.left) / b.width, y = (e.clientY - b.top) / b.height;
                card.style.transform = 'perspective(900px) rotateX(' + ((.5 - y) * 10) + 'deg) rotateY(' + ((x - .5) * 12) + 'deg) translateY(-6px)';
                card.style.setProperty('--gx', x * 100 + '%');
                card.style.setProperty('--gy', y * 100 + '%');
            });
            card.addEventListener('mouseleave', function () { card.style.transform = ''; });
        });
        document.querySelectorAll('[data-tilt-stage]').forEach(function (stage) {
            var mockup = stage.querySelector('.mockup'), hero = stage.closest('section') || stage;
            hero.addEventListener('mousemove', function (e) {
                var x = e.clientX / window.innerWidth - .5, y = e.clientY / window.innerHeight - .5;
                mockup.style.transform = 'rotateY(' + (-14 + x * 16) + 'deg) rotateX(' + (8 - y * 12) + 'deg)';
                stage.querySelectorAll('.float-chip, .float-orb').forEach(function (el, n) {
                    var depth = (n % 3 + 1) * 10;
                    el.style.translate = (x * depth) + 'px ' + (y * depth) + 'px';
                });
            });
        });
    }

    // Boutons magnétiques et onde au clic.
    function initButtons() {
        var fine = window.matchMedia('(hover: hover)').matches;
        document.querySelectorAll('[data-magnetic]').forEach(function (btn) {
            if (reduceMotion || !fine) return;
            btn.addEventListener('mousemove', function (e) {
                var b = btn.getBoundingClientRect();
                btn.style.transform = 'translate(' + (e.clientX - b.left - b.width / 2) * .18 + 'px,' + (e.clientY - b.top - b.height / 2) * .28 + 'px)';
            });
            btn.addEventListener('mouseleave', function () { btn.style.transform = ''; });
        });
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn');
            if (!btn || reduceMotion) return;
            var b = btn.getBoundingClientRect(), size = Math.max(b.width, b.height), ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - b.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - b.top - size / 2) + 'px';
            if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
            btn.style.overflow = 'hidden';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 650);
        });
    }

    // Halo lumineux qui suit le curseur dans le héros, et légère parallaxe des formes au défilement.
    function initSpotlight() {
        document.querySelectorAll('[data-spotlight]').forEach(function (hero) {
            hero.addEventListener('mousemove', function (e) {
                var b = hero.getBoundingClientRect();
                hero.style.setProperty('--spot-x', ((e.clientX - b.left) / b.width * 100) + '%');
                hero.style.setProperty('--spot-y', ((e.clientY - b.top) / b.height * 100) + '%');
            });
        });
        if (reduceMotion) return;
        var blobs = document.querySelectorAll('.hero-blob');
        if (!blobs.length) return;
        window.addEventListener('scroll', function () {
            var y = window.scrollY;
            if (y > 900) return;
            blobs.forEach(function (blob, i) { blob.style.translate = '0 ' + (y * (i + 1) * .12) + 'px'; });
        }, { passive: true });
    }

    // Frise de la méthode qui se dessine quand elle apparaît.
    function initTimeline() {
        document.querySelectorAll('[data-timeline]').forEach(function (line) {
            if (reduceMotion || !('IntersectionObserver' in window)) { line.classList.add('is-drawn'); return; }
            var observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) { line.classList.add('is-drawn'); observer.disconnect(); }
            }, { threshold: .4 });
            observer.observe(line);
        });
    }

    // Transition douce vers une autre page du site (liens internes ordinaires).
    function initPageTransitions() {
        if (reduceMotion) return;
        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href]');
            if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || a.target || a.hasAttribute('download')
                || a.hasAttribute('data-ajax-link') || a.closest('[data-ajax-results]') || a.origin !== window.location.origin
                || a.getAttribute('href').charAt(0) === '#' || (a.pathname === window.location.pathname && a.hash)) return;
            e.preventDefault();
            document.body.classList.add('is-leaving');
            setTimeout(function () { window.location.href = a.href; }, 180);
        });
        window.addEventListener('pageshow', function () { document.body.classList.remove('is-leaving'); });
    }

    /* ——— Copier un lien (partage d'un article) ——— */
    function initCopyLinks() {
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy-link]');
            if (!button) return;
            var url = button.getAttribute('data-copy-link');
            var done = function () { CSB.toast('Lien copié dans le presse-papiers.'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copiez le lien :', url); });
            } else {
                window.prompt('Copiez le lien :', url);
            }
        });
    }

    /* ——— Carrousels (réalisations, matériel du moment) ———
       [data-carousel] : défilement automatique toutes les data-interval ms, flèches, points ou vignettes,
       glissement au doigt ou à la souris, pause au survol, au focus et quand la page est cachée ; mouvement réduit respecté. */
    function initCarousels() {
        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-carousel]').forEach(function (root) {
            var track = root.querySelector('.csb-carousel-track');
            var slides = Array.prototype.slice.call(track.children);
            if (!slides.length) return;
            var interval = parseInt(root.dataset.interval, 10) || 5000;
            var dotsBox = root.querySelector('[data-carousel-dots]');
            var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-goto]'));
            var bar = root.querySelector('.csb-carousel-progress span');
            var index = 0, timer = null, paused = false;
            root.style.setProperty('--carousel-interval', interval + 'ms');

            function perView() {
                var w = slides[0].getBoundingClientRect().width;
                return w > 0 ? Math.max(1, Math.round(track.parentElement.clientWidth / w)) : 1;
            }
            function pages() { return Math.max(1, slides.length - perView() + 1); }

            function buildDots() {
                if (!dotsBox) return;
                dotsBox.innerHTML = '';
                for (var i = 0; i < pages(); i++) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.setAttribute('aria-label', 'Aller à la position ' + (i + 1));
                    b.addEventListener('click', (function (n) { return function () { go(n, true); }; })(i));
                    dotsBox.appendChild(b);
                }
            }

            function render() {
                var max = pages() - 1;
                if (index > max) index = 0;
                if (index < 0) index = max;
                track.style.transform = 'translateX(' + (-index * slides[0].getBoundingClientRect().width) + 'px)';
                var pv = perView();
                slides.forEach(function (s, i) {
                    var visible = i >= index && i < index + pv;
                    s.classList.toggle('is-active', visible);
                    s.setAttribute('aria-hidden', visible ? 'false' : 'true');
                    s.querySelectorAll('a, button').forEach(function (el) { if (visible) { el.removeAttribute('tabindex'); } else { el.setAttribute('tabindex', '-1'); } });
                });
                if (dotsBox) Array.prototype.forEach.call(dotsBox.children, function (d, i) { d.classList.toggle('active', i === index); });
                thumbs.forEach(function (t, i) {
                    t.classList.toggle('active', i === index);
                    t.setAttribute('aria-current', i === index ? 'true' : 'false');
                });
                var activeThumb = thumbs[index];
                if (activeThumb && activeThumb.parentElement.scrollWidth > activeThumb.parentElement.clientWidth) {
                    activeThumb.parentElement.scrollTo({ left: activeThumb.offsetLeft - activeThumb.parentElement.clientWidth / 2 + activeThumb.clientWidth / 2, behavior: reduced ? 'auto' : 'smooth' });
                }
                restartBar();
            }

            function restartBar() {
                if (!bar) return;
                bar.classList.remove('run');
                void bar.offsetWidth;
                if (!paused && !reduced) bar.classList.add('run');
            }

            function go(n, user) { index = n; render(); if (user) schedule(); }
            function schedule() {
                clearInterval(timer);
                if (reduced || slides.length <= perView()) return;
                timer = setInterval(function () { if (!paused) go(index + 1); }, interval);
            }
            function pause(v) { paused = v; restartBar(); }

            var prev = root.querySelector('[data-carousel-prev]');
            var next = root.querySelector('[data-carousel-next]');
            if (prev) prev.addEventListener('click', function () { go(index - 1, true); });
            if (next) next.addEventListener('click', function () { go(index + 1, true); });
            thumbs.forEach(function (t) { t.addEventListener('click', function () { go(parseInt(t.dataset.carouselGoto, 10), true); }); });
            root.addEventListener('mouseenter', function () { pause(true); });
            root.addEventListener('mouseleave', function () { pause(false); });
            root.addEventListener('focusin', function () { pause(true); });
            root.addEventListener('focusout', function () { pause(false); });
            root.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') { go(index - 1, true); }
                if (e.key === 'ArrowRight') { go(index + 1, true); }
            });
            document.addEventListener('visibilitychange', function () { pause(document.hidden); });

            // Glissement (doigt ou souris) : au-delà de 50 px, diapositive suivante ou précédente ; un simple clic reste un clic.
            var startX = null, delta = 0, base = 0;
            track.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                startX = e.clientX; delta = 0;
                base = -index * slides[0].getBoundingClientRect().width;
            });
            window.addEventListener('pointermove', function (e) {
                if (startX === null) return;
                delta = e.clientX - startX;
                if (Math.abs(delta) > 6) {
                    root.classList.add('dragging');
                    track.style.transform = 'translateX(' + (base + delta) + 'px)';
                }
            });
            window.addEventListener('pointerup', function () {
                if (startX === null) return;
                root.classList.remove('dragging');
                var moved = delta;
                startX = null;
                if (Math.abs(moved) > 50) { go(index + (moved < 0 ? 1 : -1), true); } else { render(); }
                if (Math.abs(moved) > 6) {
                    var block = function (ev) { ev.preventDefault(); ev.stopPropagation(); };
                    track.addEventListener('click', block, true);
                    setTimeout(function () { track.removeEventListener('click', block, true); }, 0);
                }
            });
            track.addEventListener('dragstart', function (e) { e.preventDefault(); });

            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () { buildDots(); render(); schedule(); }, 150);
            });

            buildDots();
            render();
            schedule();
        });
    }

    /* ——— Bandes en boucle continue ([data-loop]) ———
       Les cartes sont dupliquées (copies cachées aux lecteurs d'écran et au clavier) jusqu'à remplir deux fois la largeur ;
       la piste glisse de la moitié de sa largeur puis repart sans saut visible. data-speed : pixels par seconde,
       data-reverse : sens inverse. Pause au survol et au focus ; mouvement réduit : simple défilement horizontal. */
    function initLoops() {
        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('[data-loop]').forEach(function (rail) {
            var track = rail.querySelector('.loop-track');
            var originals = Array.prototype.slice.call(track.children);
            if (!originals.length) return;
            if (reduced) { rail.classList.add('loop-static'); return; }

            function setWidth() { return originals.reduce(function (w, el) { return w + el.getBoundingClientRect().width; }, 0) + originals.length * parseFloat(getComputedStyle(track).columnGap || 0); }
            function fill() {
                track.querySelectorAll('[data-loop-clone]').forEach(function (c) { c.remove(); });
                var one = setWidth();
                if (!one) return;
                // Un jeu complet doit couvrir au moins la largeur visible, puis on double l'ensemble pour la boucle.
                var copies = Math.max(1, Math.ceil(rail.clientWidth / one));
                var group = [];
                for (var i = 1; i < copies; i++) { originals.forEach(function (el) { group.push(el); }); }
                var half = originals.concat(group);
                half.forEach(function (el) { appendClone(el); });
                group.forEach(function (el) { appendClone(el); });
                var distance = one * copies;
                var speed = parseFloat(rail.dataset.speed) || 40;
                track.style.setProperty('--loop-distance', '-' + distance + 'px');
                track.style.setProperty('--loop-duration', (distance / speed) + 's');
                rail.classList.add('loop-ready');
                if (rail.hasAttribute('data-reverse')) rail.classList.add('loop-reverse');
            }
            function appendClone(el) {
                var c = el.cloneNode(true);
                c.setAttribute('data-loop-clone', '');
                c.setAttribute('aria-hidden', 'true');
                c.querySelectorAll('a, button').forEach(function (a) { a.setAttribute('tabindex', '-1'); });
                track.appendChild(c);
            }
            rail.addEventListener('focusin', function () { rail.classList.add('loop-paused'); });
            rail.addEventListener('focusout', function () { rail.classList.remove('loop-paused'); });

            var timer;
            window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(fill, 200); });
            // Les images chargées changent parfois la largeur des cartes : on recalcule une fois tout chargé.
            window.addEventListener('load', fill);
            fill();
        });
    }

    /* ——— Boutique : panier en AJAX ———
       Ajout (form[data-cart-add]) sans quitter la page, quantités (+ / −, saisie) et retrait mis à jour en direct sur la
       page du panier ; sans JavaScript, les mêmes formulaires s'envoient normalement. */
    function initCart() {
        if (!window.fetch) return;

        function send(form, extra) {
            var data = new FormData(form);
            Object.keys(extra || {}).forEach(function (k) { data.set(k, extra[k]); });
            return fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw r; return r.json(); });
        }
        function updateCount(count) {
            document.querySelectorAll('[data-cart-count]').forEach(function (el) {
                el.textContent = count;
                el.hidden = !count;
                el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump');
            });
        }
        function fail() { CSB.toast('Action impossible pour le moment. Réessayez.', 'error'); }

        document.querySelectorAll('form[data-cart-add]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = form.querySelector('button[type=submit]');
                if (btn) btn.disabled = true;
                send(form).then(function (res) {
                    updateCount(res.count);
                    CSB.toast(res.message);
                    if (btn) { btn.classList.remove('added'); void btn.offsetWidth; btn.classList.add('added'); }
                }).catch(fail).finally(function () { if (btn) btn.disabled = false; });
            });
        });

        var page = document.querySelector('[data-cart-page]');
        if (!page) return;
        var totalEl = page.querySelector('[data-cart-total]');
        function apply(res) {
            updateCount(res.count);
            if (totalEl) totalEl.textContent = res.total;
            page.querySelectorAll('[data-cart-line]').forEach(function (line) {
                var info = res.lines[line.dataset.cartLine];
                if (!info) { line.classList.add('removing'); setTimeout(function () { line.remove(); if (!page.querySelector('[data-cart-line]')) window.location.reload(); }, 300); return; }
                line.querySelector('[data-line-total]').textContent = info.total;
                var input = line.querySelector('input[name=quantity]');
                if (document.activeElement !== input) input.value = info.quantity;
            });
        }

        page.querySelectorAll('form[data-cart-qty]').forEach(function (form) {
            var input = form.querySelector('input[name=quantity]');
            var timer;
            function push() { clearTimeout(timer); timer = setTimeout(function () { send(form).then(apply).catch(fail); }, 350); }
            form.querySelectorAll('[data-qty-step]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var v = Math.min(parseInt(input.max, 10) || 20, Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(b.dataset.qtyStep, 10)));
                    input.value = v; push();
                });
            });
            input.addEventListener('change', push);
            form.addEventListener('submit', function (e) { e.preventDefault(); push(); });
        });
        page.querySelectorAll('form[data-cart-remove]').forEach(function (form) {
            form.addEventListener('submit', function (e) { e.preventDefault(); send(form).then(function (res) { apply(res); CSB.toast(res.message); }).catch(fail); });
        });
    }

    /* Commande : l'adresse n'est demandée que pour une livraison. */
    function initCheckout() {
        var radios = document.querySelectorAll('[data-delivery]');
        if (!radios.length) return;
        function sync() {
            var checked = document.querySelector('[data-delivery]:checked');
            var delivery = checked && checked.value === 'livraison';
            document.querySelectorAll('[data-delivery-fields]').forEach(function (el) { el.hidden = !delivery; });
        }
        radios.forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    }

    /* ——— Témoignages vidéo : lecture en grand ([data-video-play]) ———
       Lecteur HTML5 du navigateur (vidéo hébergée par le site, aucun script externe) ; Échap ou clic hors de la vidéo ferme. */
    function initVideoLightbox() {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-video-play]');
            if (!trigger) return;
            e.preventDefault();
            var box = document.createElement('div');
            box.className = 'video-lightbox';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            box.setAttribute('aria-label', 'Témoignage de ' + (trigger.dataset.videoTitle || ''));
            var video = document.createElement('video');
            video.src = trigger.dataset.videoPlay;
            if (trigger.dataset.videoPoster) video.poster = trigger.dataset.videoPoster;
            video.controls = true;
            video.autoplay = true;
            video.setAttribute('playsinline', '');
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'video-lightbox-close';
            close.setAttribute('aria-label', 'Fermer la vidéo');
            close.innerHTML = "<i class='bx bx-x' aria-hidden='true'></i>";
            var title = document.createElement('div');
            title.className = 'video-lightbox-title';
            title.textContent = trigger.dataset.videoTitle || '';
            box.appendChild(video);
            box.appendChild(close);
            box.appendChild(title);
            document.body.appendChild(box);
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function () { box.classList.add('open'); });
            close.focus();
            function shut() {
                video.pause();
                box.remove();
                document.body.style.overflow = '';
                document.removeEventListener('keydown', onKey);
                trigger.focus();
            }
            function onKey(ev) { if (ev.key === 'Escape') shut(); }
            close.addEventListener('click', shut);
            box.addEventListener('click', function (ev) { if (ev.target === box) shut(); });
            document.addEventListener('keydown', onKey);
        });
    }

    /* ——— Démarrage ——— */
    document.addEventListener('DOMContentLoaded', function () {
        initScroll();
        initReveal();
        initCounters();
        initAjaxFilters();
        initAjaxForms();
        initLightbox();
        initCopyLinks();
        initSearch();
        initParticles();
        initRotator();
        initTilt();
        initButtons();
        initSpotlight();
        initTimeline();
        initPageTransitions();
        initCarousels();
        initLoops();
        initCart();
        initCheckout();
        initVideoLightbox();
    });

    CSB.initReveal = initReveal;
})();
