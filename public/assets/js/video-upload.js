/* Envoi d'une vidéo par morceaux de 1 Mo (témoignages) : [data-video-upload] contient un input[type=file], un champ caché
   input[name=video_token], une barre de progression et une zone d'aperçu. Chaque morceau est réessayé 3 fois (connexion
   mobile) ; le bouton d'envoi du formulaire est bloqué pendant l'envoi. Utilisé par le site public et l'administration. */
(function () {
    'use strict';
    var CHUNK = 1024 * 1024;
    var MAX = 300 * 1024 * 1024;

    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function setup(box) {
        if (box.dataset.ready) return;
        box.dataset.ready = '1';
        var input = box.querySelector('input[type=file]');
        var token = box.querySelector('input[name=video_token]');
        var bar = box.querySelector('[data-video-bar]');
        var status = box.querySelector('[data-video-status]');
        var preview = box.querySelector('[data-video-preview]');
        var cancel = box.querySelector('[data-video-cancel]');
        var form = box.closest('form');
        var submits = form ? form.querySelectorAll('[type=submit]') : [];
        var csrf = (form && form.querySelector('input[name=_token]')) ? form.querySelector('input[name=_token]').value : '';
        var aborted = false;

        function say(text, kind) {
            status.textContent = text;
            status.className = 'video-upload-status small ' + (kind === 'error' ? 'text-danger' : (kind === 'ok' ? 'text-success' : 'text-muted'));
        }
        function lock(on) { Array.prototype.forEach.call(submits, function (b) { b.disabled = on; }); }
        function progress(p) { bar.style.width = p + '%'; bar.parentElement.hidden = false; bar.setAttribute('aria-valuenow', String(p)); }

        function sendChunk(id, index, total, file, attempt) {
            var data = new FormData();
            data.append('upload_id', id);
            data.append('index', index);
            data.append('total', total);
            data.append('chunk', file.slice(index * CHUNK, Math.min(file.size, (index + 1) * CHUNK)), 'chunk');
            return fetch(box.dataset.url, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    return r.json().catch(function () { return {}; }).then(function (json) {
                        if (!r.ok) {
                            var err = new Error((json.errors && (json.errors.video || json.errors.chunk || [])[0]) || json.message || 'Erreur ' + r.status);
                            err.retry = r.status >= 500 || r.status === 429;
                            throw err;
                        }
                        return json;
                    });
                })
                .catch(function (e) {
                    if (aborted) throw e;
                    if (attempt < 3 && e.retry !== false) {
                        return new Promise(function (res) { setTimeout(res, 1500 * attempt); }).then(function () { return sendChunk(id, index, total, file, attempt + 1); });
                    }
                    throw e;
                });
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            token.value = '';
            if (!file) return;
            if (file.size > MAX) { say('Vidéo trop lourde : 300 Mo au plus.', 'error'); input.value = ''; return; }
            if (file.type && file.type.indexOf('video/') !== 0) { say('Choisissez un fichier vidéo (MP4, MOV, WebM).', 'error'); input.value = ''; return; }

            if (preview) {
                preview.hidden = false;
                preview.src = URL.createObjectURL(file);
            }
            var id = uuid();
            var total = Math.max(1, Math.ceil(file.size / CHUNK));
            var index = 0;
            aborted = false;
            lock(true);
            if (cancel) cancel.hidden = false;
            say('Envoi de la vidéo… 0 %');
            progress(0);

            (function next() {
                if (aborted) return;
                sendChunk(id, index, total, file, 1).then(function (res) {
                    index++;
                    var pct = Math.round(index / total * 100);
                    progress(pct);
                    if (index < total) { say('Envoi de la vidéo… ' + pct + ' %'); next(); return; }
                    token.value = res.token || '';
                    lock(false);
                    if (cancel) cancel.hidden = true;
                    say('Vidéo envoyée ✓ (' + (file.size / 1048576).toFixed(1) + ' Mo). Vous pouvez enregistrer.', 'ok');
                }).catch(function (e) {
                    lock(false);
                    if (cancel) cancel.hidden = true;
                    input.value = '';
                    if (!aborted) say('Envoi interrompu : ' + e.message + ' Réessayez.', 'error');
                });
            })();
        });

        if (cancel) {
            cancel.addEventListener('click', function () {
                aborted = true;
                input.value = '';
                token.value = '';
                cancel.hidden = true;
                lock(false);
                progress(0);
                bar.parentElement.hidden = true;
                if (preview) { preview.hidden = true; preview.removeAttribute('src'); }
                say('Envoi annulé.');
            });
        }
    }

    function init() { document.querySelectorAll('[data-video-upload]').forEach(setup); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    window.CSBVideoUpload = { init: init };
})();
