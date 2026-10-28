/*
 * Anti double envoi : dès qu'un formulaire part, son bouton affiche un spinner et tous ses boutons d'envoi sont
 * désactivés (un second clic ou « Entrée » n'enregistre pas deux fois).
 *
 *  - Les formulaires GET (filtres, recherche), ceux qui s'ouvrent dans un autre onglet (target) et ceux marqués
 *    data-no-guard ne sont pas concernés.
 *  - Un envoi annulé par un autre script (confirmation, AJAX : preventDefault) n'est pas bloqué.
 *  - Les boutons sont désactivés juste après le départ, pour que le nom / la valeur du bouton cliqué soient envoyés.
 *  - Retour arrière vers la page (cache du navigateur) : boutons rétablis.
 */
(function () {
    'use strict';

    function buttonsOf(form) {
        var inside = Array.prototype.slice.call(form.querySelectorAll('button:not([type=button]):not([type=reset]), input[type=submit]'));
        var outside = form.id ? Array.prototype.slice.call(document.querySelectorAll('[form="' + form.id + '"][type=submit]')) : [];
        return inside.concat(outside);
    }

    function restore(form) {
        delete form.dataset.submitting;
        buttonsOf(form).forEach(function (button) {
            if (button.dataset.guardHtml !== undefined) {
                button.innerHTML = button.dataset.guardHtml;
                button.removeAttribute('aria-busy');
                delete button.dataset.guardHtml;
            }
            if (button.dataset.guardDisabled !== undefined) {
                button.disabled = false;
                delete button.dataset.guardDisabled;
            }
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-guard') || (form.target && form.target !== '_self') || (form.method || 'get').toLowerCase() === 'get') return;

        if (form.dataset.submitting) {
            event.preventDefault();
            return;
        }

        var clicked = event.submitter || buttonsOf(form)[0];
        setTimeout(function () {
            if (event.defaultPrevented) return;
            form.dataset.submitting = '1';
            buttonsOf(form).forEach(function (button) {
                if (button.disabled) return;
                button.disabled = true;
                button.dataset.guardDisabled = '1';
            });
            if (clicked && clicked.tagName === 'BUTTON' && !clicked.querySelector('.spinner-border')) {
                clicked.dataset.guardHtml = clicked.innerHTML;
                clicked.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + (clicked.textContent.trim() || '');
                clicked.setAttribute('aria-busy', 'true');
            }
        }, 0);
    });

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        Array.prototype.forEach.call(document.querySelectorAll('form[data-submitting]'), restore);
    });
})();
