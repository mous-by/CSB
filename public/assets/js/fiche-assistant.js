/*
 * Aide à la saisie des fiches du catalogue (matériel, logiciel) dans l'administration.
 *
 *  - CSBFiche.checklist : liste « Avant de publier » tenue à jour pendant la saisie (essentiel / conseillé) ;
 *  - CSBFiche.publishButton : « Enregistrer et publier » demande confirmation s'il manque l'essentiel ;
 *  - CSBFiche.materiel : marque, modèle, catégorie et format déduits de la désignation, usages du conseiller et
 *    résumé proposés à partir des caractéristiques (toujours modifiables : rien n'est imposé).
 *
 * jQuery écoute les changements : Select2 (catégorie, images) les signale par jQuery, pas par un événement natif.
 */
(function ($) {
    'use strict';

    var CSBFiche = window.CSBFiche = {};

    function val(form, selector) {
        var el = form.querySelector(selector);
        return el ? String($(el).val() || '').trim() : '';
    }

    function hasFile(form, selector) {
        var el = form.querySelector(selector);
        return !!(el && el.files && el.files.length);
    }

    function filledRows(form, name) {
        return Array.prototype.filter.call(form.querySelectorAll('[name^="' + name + '["]'), function (input) {
            return input.value.trim() !== '' && !/\[value\]$/.test(input.name);
        }).length;
    }

    CSBFiche.val = val;
    CSBFiche.hasFile = hasFile;
    CSBFiche.filledRows = filledRows;

    /**
     * checks : [{ label, ok(form) → bool, essential (bool), hint, when(form) → bool (facultatif) }]
     * Renvoie une fonction qui donne les libellés essentiels manquants.
     */
    CSBFiche.checklist = function (form, container, checks) {
        var render = function () {
            var active = checks.filter(function (c) { return !c.when || c.when(form); });
            var done = 0;
            var html = active.map(function (c) {
                var ok = c.ok(form);
                if (ok) done++;
                var icon = ok ? 'bx-check-circle text-success' : (c.essential ? 'bx-error-circle text-danger' : 'bx-circle text-warning');
                return '<li class="d-flex gap-2 mb-1"><i class="bx ' + icon + ' fs-5 flex-shrink-0"></i><span>' + c.label
                    + (!ok && c.hint ? '<span class="d-block text-muted">' + c.hint + '</span>' : '') + '</span></li>';
            }).join('');
            var percent = active.length ? Math.round(100 * done / active.length) : 100;
            container.innerHTML = '<div class="d-flex justify-content-between small fw-semibold mb-1"><span>Fiche prête à ' + percent + ' %</span><span>' + done + ' / ' + active.length + '</span></div>'
                + '<div class="progress mb-2" style="height:6px"><div class="progress-bar ' + (percent === 100 ? 'bg-success' : 'bg-warning') + '" style="width:' + percent + '%"></div></div>'
                + '<ul class="list-unstyled small mb-0">' + html + '</ul>';
        };
        var timer;
        var later = function () { clearTimeout(timer); timer = setTimeout(render, 150); };
        $(form).on('input change', later);
        // Images ajoutées ou retirées (Select2), lignes de caractéristiques : sauf la liste elle-même, redessinée ici.
        new MutationObserver(function (mutations) {
            if (mutations.some(function (m) { return !container.contains(m.target); })) later();
        }).observe(form, { childList: true, subtree: true });
        render();

        return function () {
            return checks.filter(function (c) { return c.essential && (!c.when || c.when(form)) && !c.ok(form); }).map(function (c) { return c.label; });
        };
    };

    /** « Enregistrer et publier » : s'il manque l'essentiel, confirmation avant l'envoi. */
    CSBFiche.publishButton = function (form, button, missing) {
        if (!button) return;
        button.addEventListener('click', function (event) {
            var list = missing();
            if (!list.length || !window.Swal) return;
            event.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Publier quand même ?',
                html: 'Il manque encore : <strong>' + list.join(', ') + '</strong>.<br>Les visiteurs verront la fiche incomplète.',
                showCancelButton: true,
                confirmButtonText: 'Publier quand même',
                cancelButtonText: 'Compléter la fiche',
            }).then(function (result) {
                if (result.isConfirmed) form.requestSubmit(button);
            });
        });
    };

    /* ——— Matériel ——— */

    var BRANDS = ['HP', 'Dell', 'Lenovo', 'Acer', 'Asus', 'Apple', 'Toshiba', 'Samsung', 'MSI', 'Huawei', 'Microsoft', 'Fujitsu',
        'Canon', 'Epson', 'Brother', 'Xerox', 'Ricoh', 'Kyocera', 'Pantum', 'TP-Link', 'Cisco', 'Ubiquiti', 'D-Link', 'Tenda',
        'MikroTik', 'Netgear', 'Logitech', 'Kingston', 'SanDisk', 'Seagate', 'Western Digital', 'APC', 'Hikvision', 'Xiaomi'];

    // Mot-clé de la désignation → mot présent dans le nom de la catégorie, et format pour le conseiller.
    var KINDS = [
        { re: /tout[- ]en[- ]un|all[- ]in[- ]one|\baio\b|imac/i, category: 'bureau', form: 'tout_en_un' },
        { re: /portable|laptop|notebook|ultrabook|elitebook|probook|thinkpad|latitude|vostro|inspiron|ideapad|vivobook|zenbook|macbook|pavilion|aspire|chromebook/i, category: 'portable', form: 'portable' },
        { re: /bureau|desktop|optiplex|prodesk|elitedesk|thinkcentre|unit[ée] centrale|\btour\b|mini pc/i, category: 'bureau', form: 'bureau' },
        { re: /imprimante|printer|multifonction|photocopieu|laserjet|deskjet|ecotank|pixma/i, category: 'imprimante' },
        { re: /routeur|router|switch|commutateur|wi-?fi|point d'acc[eè]s|c[âa]ble r[ée]seau|modem|antenne/i, category: 'réseau' },
        { re: /souris|clavier|casque|chargeur|sacoche|cl[ée] usb|disque dur externe|webcam|[ée]cran|moniteur|onduleur|haut-parleur/i, category: 'accessoire' },
    ];

    function autoFill(input, value) {
        if (!input || !value) return;
        if (input.value.trim() !== '' && input.dataset.auto !== '1') return;
        if (input.value === value) return;
        input.value = value;
        input.dataset.auto = '1';
        $(input).trigger('change');
    }

    function analyseSpecs(form) {
        var text = val(form, '#designation') + ' ';
        var ram = null;
        form.querySelectorAll('[name^="specs["][name$="[label]"]').forEach(function (label) {
            var value = form.querySelector('[name="' + label.name.replace('[label]', '[value]') + '"]');
            var line = label.value + ' : ' + (value ? value.value : '');
            text += line + ' ';
            var m = /(\d+)\s*(go|gb)/i.exec(line);
            if (m && /m[ée]moire|ram|ddr/i.test(line)) ram = parseInt(m[1], 10);
        });
        if (ram === null) {
            var m = /(\d+)\s*(go|gb)\s*(de\s*)?(ram|ddr\d?|m[ée]moire)/i.exec(text);
            if (m) ram = parseInt(m[1], 10);
        }
        var cpu = null, tier = 0;
        var c;
        if ((c = /core\s*(i[3579])|\b(i[3579])[- ]\d{3,5}/i.exec(text))) { cpu = 'Intel Core ' + (c[1] || c[2]).toLowerCase(); tier = parseInt((c[1] || c[2]).slice(1), 10); }
        else if ((c = /ryzen\s*([3579])/i.exec(text))) { cpu = 'AMD Ryzen ' + c[1]; tier = parseInt(c[1], 10); }
        else if ((c = /core\s*ultra\s*([579])/i.exec(text))) { cpu = 'Intel Core Ultra ' + c[1]; tier = parseInt(c[1], 10) + 1; }
        else if ((c = /\b(m[1-4])(\s*(pro|max))?\b/i.exec(text)) && /apple|macbook|imac/i.test(text)) { cpu = 'Apple ' + c[0].toUpperCase(); tier = 7; }
        else if ((c = /celeron|pentium|atom|athlon|core\s*2/i.exec(text))) { cpu = c[0]; tier = 1; }
        else if ((c = /xeon/i.exec(text))) { cpu = 'Intel Xeon'; tier = 5; }
        var storage = /(ssd|nvme)[^,;]*?(\d+\s*(go|gb|to|tb))|(\d+\s*(go|gb|to|tb))\s*(ssd|nvme)/i.exec(text);
        return {
            ram: ram, cpu: cpu, tier: tier,
            ssd: /ssd|nvme/i.test(text),
            storage: storage ? 'SSD ' + (storage[2] || storage[4]).replace(/\s+/, ' ').replace(/gb/i, 'Go').replace(/tb/i, 'To') : null,
            gpu: /rtx|gtx|geforce|radeon\s*(rx|pro)|quadro|carte graphique d[ée]di[ée]e/i.test(text),
        };
    }

    function suggestUsages(s) {
        if (s.ram === null && !s.cpu) return null;
        var ram = s.ram || 4, tier = s.tier || 3;
        var usages = [];
        if (ram >= 4) usages.push('internet', 'bureautique', 'etudes');
        if (ram >= 8 && tier >= 5 && s.ssd) usages.push('gestion');
        if ((ram >= 16 && tier >= 5) || (ram >= 8 && tier >= 7 && s.ssd)) usages.push('programmation');
        if (s.gpu && ram >= 16) usages.push('creation');
        if (s.gpu && ram >= 16 && tier >= 5) usages.push('jeux');
        return usages;
    }

    CSBFiche.materiel = function (form, options) {
        var designation = form.querySelector('#designation');
        var brand = form.querySelector('#brand'), model = form.querySelector('#model');
        var category = form.querySelector('#category_id'), advisorForm = form.querySelector('#advisor_form');

        [brand, model, advisorForm].forEach(function (el) { if (el) el.addEventListener('input', function () { delete el.dataset.auto; }); });
        if (category) $(category).on('select2:select', function () { delete category.dataset.auto; });

        designation.addEventListener('input', function () {
            var text = designation.value;
            var found = BRANDS.filter(function (b) { return new RegExp('(^|\\s)' + b.replace('-', '\\-') + '(\\s|$)', 'i').test(text); })[0];
            if (found) {
                autoFill(brand, found);
                var rest = text.split(new RegExp('(^|\\s)' + found.replace('-', '\\-') + '\\s', 'i')).pop();
                autoFill(model, rest.split(/\s+(avec|\+|-|–)\s+|\(|,/i)[0].trim());
            }
            var kind = KINDS.filter(function (k) { return k.re.test(text); })[0];
            if (!kind) return;
            if (category && (category.value === '' || category.dataset.auto === '1')) {
                var option = Array.prototype.filter.call(category.options, function (o) { return o.value && o.text.toLowerCase().indexOf(kind.category) !== -1; })[0];
                if (option && category.value !== option.value) {
                    $(category).val(option.value).trigger('change');
                    category.dataset.auto = '1';
                }
            }
            if (kind.form) autoFill(advisorForm, kind.form);
        });

        var usagesButton = form.querySelector('[data-suggest-usages]'), usagesHint = form.querySelector('[data-usages-hint]');
        if (usagesButton) usagesButton.addEventListener('click', function () {
            var s = analyseSpecs(form), usages = suggestUsages(s);
            if (!usages) {
                usagesHint.className = 'small text-warning mt-2';
                usagesHint.textContent = 'Indiquez au moins le processeur ou la mémoire dans les caractéristiques (ex. « Processeur : Intel Core i5 », « Mémoire : 8 Go »).';
                return;
            }
            form.querySelectorAll('[name="advisor_usages[]"]').forEach(function (box) { box.checked = usages.indexOf(box.value) !== -1; });
            $(form).trigger('change');
            var seen = [s.cpu, s.ram ? s.ram + ' Go de mémoire' : null, s.ssd ? 'SSD' : null, s.gpu ? 'carte graphique dédiée' : null].filter(Boolean).join(', ');
            usagesHint.className = 'small text-success mt-2';
            usagesHint.textContent = 'Proposé d\'après : ' + seen + '. Vérifiez et ajustez avant d\'enregistrer.';
        });

        var summaryButton = form.querySelector('[data-suggest-summary]'), summary = form.querySelector('#summary');
        if (summaryButton) summaryButton.addEventListener('click', function () {
            var s = analyseSpecs(form);
            var name = [val(form, '#brand'), val(form, '#model')].filter(Boolean).join(' ') || val(form, '#designation');
            var condition = form.querySelector('#condition');
            var state = condition && condition.value !== 'neuf' ? ' ' + condition.options[condition.selectedIndex].text.toLowerCase() : '';
            var parts = [s.cpu, s.ram ? s.ram + ' Go de mémoire' : null, s.storage, s.gpu ? 'carte graphique dédiée' : null].filter(Boolean);
            var text = name + state + (parts.length ? ' : ' + parts.join(', ') : '') + '.';
            if (summary.value.trim() !== '' && !window.confirm('Remplacer le résumé actuel par :\n« ' + text + ' » ?')) return;
            summary.value = text.charAt(0).toUpperCase() + text.slice(1);
            $(summary).trigger('input');
        });

        var isComputer = function () {
            var option = category && category.options[category.selectedIndex];
            return !!(option && /ordinateur/i.test(option.text));
        };
        var missing = CSBFiche.checklist(form, form.querySelector('[data-checklist]'), [
            { label: 'Désignation', essential: true, ok: function (f) { return val(f, '#designation') !== ''; } },
            { label: 'Catégorie', essential: true, ok: function (f) { return val(f, '#category_id') !== ''; } },
            { label: 'Photo principale', essential: true, ok: function (f) { return val(f, '#main_media_id') !== '' || hasFile(f, '[name="main_upload"]'); }, hint: 'La fiche s\'affiche avec une image grise sans elle.' },
            { label: 'Résumé', ok: function (f) { return val(f, '#summary') !== ''; }, hint: 'Une phrase sur la carte du catalogue (bouton « Proposer »).' },
            { label: 'Prix', ok: function (f) { return val(f, '#price') !== ''; }, hint: 'Sinon : « Prix sur demande ».' },
            { label: 'Au moins 3 caractéristiques', ok: function (f) { return filledRows(f, 'specs') >= 3; } },
            { label: 'Usages du conseiller d\'achat', when: isComputer, ok: function (f) { return f.querySelector('[name="advisor_usages[]"]:checked') !== null; }, hint: 'Sans usage coché, le conseiller ne propose jamais cet ordinateur.' },
        ]);
        CSBFiche.publishButton(form, form.querySelector('[data-publish]'), missing);
    };
})(window.jQuery);
