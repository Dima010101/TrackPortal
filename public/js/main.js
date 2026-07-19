/* TrackPortal · script comuni front-end (Bulma) */

(function () {
    'use strict';

    const userMenu = document.getElementById('user-menu');
    const userTrigger = document.getElementById('user-trigger');
    if (userMenu && userTrigger) {
        userTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = userMenu.classList.toggle('is-active');
            userTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('is-active');
                userTrigger.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && userMenu.classList.contains('is-active')) {
                userMenu.classList.remove('is-active');
                userTrigger.setAttribute('aria-expanded', 'false');
                userTrigger.focus();
            }
        });
    }

    setTimeout(function () {
        document.querySelectorAll('.tp-flash').forEach(function (el) {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function () { el.remove(); }, 500);
        });
    }, 7000);

    const live = document.getElementById('live-search');
    if (live) {
        const targets = document.querySelectorAll('[data-search-target]');
        const onInput = function () {
            const q = live.value.trim().toLowerCase();
            let visible = 0;
            targets.forEach(function (card) {
                const text = (card.getAttribute('data-search-target') || '').toLowerCase();
                const show = !q || text.includes(q);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            const empty = document.getElementById('live-search-empty');
            if (empty) {
                if (visible === 0) empty.classList.remove('tp-hidden');
                else empty.classList.add('tp-hidden');
            }
        };
        live.addEventListener('input', onInput);
    }

    /* Uploader foto circuito: righe dinamiche (aggiungi/rimuovi file + nome). */
    document.querySelectorAll('[data-foto-uploader]').forEach(function (uploader) {
        const template = uploader.parentElement
            ? uploader.parentElement.querySelector('[data-foto-row-template]')
            : null;
        const addBtn = uploader.parentElement
            ? uploader.parentElement.querySelector('[data-foto-add]')
            : null;
        const max = parseInt(uploader.getAttribute('data-foto-max') || '12', 10);

        const rowCount = function () {
            return uploader.querySelectorAll('[data-foto-row]').length;
        };

        const syncAddState = function () {
            if (addBtn) addBtn.disabled = rowCount() >= max;
        };

        const addRow = function () {
            if (!template || rowCount() >= max) return;
            const fragment = template.content.cloneNode(true);
            uploader.appendChild(fragment);
            syncAddState();
            const lastRow = uploader.querySelector('[data-foto-row]:last-child');
            const fileInput = lastRow && lastRow.querySelector('input[type="file"]');
            if (fileInput) fileInput.focus();
        };

        if (addBtn) {
            addBtn.addEventListener('click', addRow);
        }

        uploader.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-foto-remove]');
            if (!btn || !uploader.contains(btn)) return;
            const row = btn.closest('[data-foto-row]');
            if (row) row.remove();
            syncAddState();
        });

        syncAddState();
    });

    document.querySelectorAll('form').forEach(function (form) {
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') return;

        form.addEventListener('submit', function () {
            const btns = form.querySelectorAll('button[type="submit"]');
            btns.forEach(function (b) {
                if (b.dataset.noLock) return;
                setTimeout(function () { b.disabled = true; }, 0);
            });
        });
    });

    /* Abilita/disabilita un campo in base al valore di un <select>.
       Sul select: data-toggle-target="#id" data-toggle-enable-when="val1|val2".
       Es. la "Fine sospensione" è cliccabile solo se il provvedimento è una
       sospensione (per il ban, permanente, resta disabilitata). */
    document.querySelectorAll('[data-toggle-target]').forEach(function (ctrl) {
        const target = document.querySelector(ctrl.getAttribute('data-toggle-target'));
        if (!target) return;
        const enableWhen = (ctrl.getAttribute('data-toggle-enable-when') || '').split('|');
        const sync = function () {
            const on = enableWhen.indexOf(ctrl.value) !== -1;
            target.disabled = !on;
            const row = target.closest('.form-row');
            if (row) row.classList.toggle('is-disabled', !on);
        };
        ctrl.addEventListener('change', sync);
        sync();
    });

    /* ------------------------------------------------------------------ *
     * Carta di credito: formattazione e validazione (schermate pagamento)
     * Le stesse regole sono applicate lato server in CPrenotazioneSessione.
     * ------------------------------------------------------------------ */
    function luhnValido(cifre) {
        if (!cifre) return false;
        var somma = 0, alt = false;
        for (var i = cifre.length - 1; i >= 0; i--) {
            var d = parseInt(cifre.charAt(i), 10);
            if (isNaN(d)) return false;
            if (alt) { d *= 2; if (d > 9) d -= 9; }
            somma += d; alt = !alt;
        }
        return somma % 10 === 0;
    }

    document.querySelectorAll('[data-cc-form]').forEach(function (form) {
        var elNum = form.querySelector('[data-cc-number]');
        var elExp = form.querySelector('[data-cc-exp]');
        var elCvv = form.querySelector('[data-cc-cvv]');
        var elNome = form.querySelector('[data-cc-name]');
        var elCognome = form.querySelector('[data-cc-surname]');

        function setError(el, msg) {
            if (!el) return;
            el.setAttribute('aria-invalid', 'true');
            var row = el.closest('.form-row');
            if (!row) return;
            var fb = row.querySelector('.tp-field-error');
            if (!fb) {
                fb = document.createElement('span');
                fb.className = 'help tp-field-error';
                row.appendChild(fb);
            }
            fb.textContent = msg;
        }
        function clearError(el) {
            if (!el) return;
            el.removeAttribute('aria-invalid');
            var row = el.closest('.form-row');
            var fb = row && row.querySelector('.tp-field-error');
            if (fb) fb.remove();
        }

        if (elNum) {
            elNum.addEventListener('input', function () {
                var digits = elNum.value.replace(/\D/g, '').slice(0, 19);
                elNum.value = digits.replace(/(.{4})/g, '$1 ').trim();
                clearError(elNum);
            });
        }
        if (elExp) {
            elExp.addEventListener('input', function () {
                var digits = elExp.value.replace(/\D/g, '').slice(0, 6);
                elExp.value = digits.length >= 3
                    ? digits.slice(0, 2) + '/' + digits.slice(2)
                    : digits;
                clearError(elExp);
            });
            elExp.addEventListener('keydown', function (e) {
                // Backspace sulla barra: rimuovi anche la cifra del mese.
                if (e.key === 'Backspace' && elExp.value.slice(-1) === '/') {
                    elExp.value = elExp.value.slice(0, -1);
                }
            });
        }
        if (elCvv) {
            elCvv.addEventListener('input', function () {
                elCvv.value = elCvv.value.replace(/\D/g, '').slice(0, 4);
                clearError(elCvv);
            });
        }

        form.addEventListener('submit', function (e) {
            // Con una carta salvata selezionata i campi della nuova carta sono
            // nascosti e disabilitati: si paga con quella, niente da validare qui
            // (la proprietà della carta viene verificata lato server).
            var scelta = form.querySelector('[data-carta-radio]:checked');
            if (scelta && scelta.value !== '0') {
                [elNome, elCognome, elNum, elExp, elCvv].forEach(clearError);
                return;
            }

            var ok = true, primo = null;
            function fail(el, msg) { setError(el, msg); ok = false; if (!primo) primo = el; }

            if (elNome && elNome.value.trim().length < 2) {
                fail(elNome, 'Inserisci il nome del titolare.');
            }
            if (elCognome && elCognome.value.trim().length < 2) {
                fail(elCognome, 'Inserisci il cognome del titolare.');
            }
            if (elNum) {
                var d = elNum.value.replace(/\D/g, '');
                if (d.length < 13 || d.length > 19 || !luhnValido(d)) {
                    fail(elNum, 'Numero carta non valido.');
                }
            }
            if (elExp) {
                var m = elExp.value.match(/^(\d{2})\/(\d{4})$/);
                if (!m) {
                    fail(elExp, 'Formato scadenza non valido (MM/AAAA).');
                } else {
                    var mm = parseInt(m[1], 10), yyyy = parseInt(m[2], 10);
                    var oggi = new Date();
                    var annoC = oggi.getFullYear(), meseC = oggi.getMonth() + 1;
                    if (mm < 1 || mm > 12) {
                        fail(elExp, 'Mese di scadenza non valido.');
                    } else if (yyyy < annoC || (yyyy === annoC && mm < meseC) || yyyy > annoC + 20) {
                        fail(elExp, 'La carta risulta scaduta.');
                    }
                }
            }
            if (elCvv) {
                var c = elCvv.value.replace(/\D/g, '');
                if (c.length < 3 || c.length > 4) {
                    fail(elCvv, 'CVV non valido (3 o 4 cifre).');
                }
            }

            if (!ok) {
                e.preventDefault();
                // Riabilita il bottone bloccato dal lock-submit (eseguito dopo, via setTimeout).
                setTimeout(function () {
                    form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                        b.disabled = false;
                    });
                }, 0);
                if (primo) primo.focus();
            }
        });
    });

    /* ------------------------------------------------------------------ *
     * Selettore valuta (display-only) per le schermate di prenotazione.
     * I tassi (base EUR) arrivano dall'endpoint same-origin /api/valute,
     * che fa da proxy verso il web service gratuito Frankfurter.
     * ------------------------------------------------------------------ */
    (function () {
        var switchers = document.querySelectorAll('[data-tp-valuta]');
        if (!switchers.length) return;

        var moneyEls = document.querySelectorAll('.tp-money');
        var endpoint = switchers[0].getAttribute('data-endpoint') || '';
        var STORE_KEY = 'tp-valuta';
        var RATES_KEY = 'tp-valuta-rates';
        var LOCALI = { EUR: 'it-IT', USD: 'en-US', GBP: 'en-GB', CHF: 'de-CH', JPY: 'ja-JP' };
        var ratesCache = null;

        function formatta(importo, valuta) {
            try {
                return new Intl.NumberFormat(LOCALI[valuta] || 'it-IT', {
                    style: 'currency', currency: valuta
                }).format(importo);
            } catch (err) {
                return importo.toFixed(2) + ' ' + valuta;
            }
        }

        moneyEls.forEach(function (el) {
            if (!el.dataset.tpOriginal) el.dataset.tpOriginal = el.textContent;
        });

        function applica(valuta, rates) {
            moneyEls.forEach(function (el) {
                var importo = parseFloat(el.getAttribute('data-amount'));
                var base = (el.getAttribute('data-base') || 'EUR').toUpperCase();
                if (isNaN(importo)) return;
                if (valuta === base || !rates) {
                    el.textContent = el.dataset.tpOriginal;
                    el.removeAttribute('title');
                    return;
                }
                var rB = rates[base], rT = rates[valuta];
                if (!rB || !rT) { el.textContent = el.dataset.tpOriginal; return; }
                el.textContent = formatta(importo / rB * rT, valuta);
                el.title = 'Importo originale: ' + el.dataset.tpOriginal;
            });
        }

        function getRates() {
            if (ratesCache) return Promise.resolve(ratesCache);
            try {
                var raw = sessionStorage.getItem(RATES_KEY);
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (parsed && parsed.rates && (Date.now() - parsed.t) < 3600000) {
                        ratesCache = parsed.rates;
                        return Promise.resolve(ratesCache);
                    }
                }
            } catch (err) { /* ignora cache corrotta */ }

            return fetch(endpoint, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('HTTP')); })
                .then(function (data) {
                    var rates = (data && data.rates) ? data.rates : null;
                    if (!rates) throw new Error('no rates');
                    if (!rates.EUR) rates.EUR = 1;
                    ratesCache = rates;
                    try { sessionStorage.setItem(RATES_KEY, JSON.stringify({ t: Date.now(), rates: rates })); } catch (err) {}
                    return rates;
                });
        }

        function imposta(valuta) {
            switchers.forEach(function (s) { s.value = valuta; });
            try { localStorage.setItem(STORE_KEY, valuta); } catch (err) {}
            if (valuta === 'EUR') { applica('EUR', null); return; }
            getRates()
                .then(function (rates) { applica(valuta, rates); })
                .catch(function () { applica('EUR', null); });
        }

        var iniziale = 'EUR';
        try { iniziale = localStorage.getItem(STORE_KEY) || 'EUR'; } catch (err) {}

        switchers.forEach(function (s) {
            s.addEventListener('change', function () { imposta(s.value); });
        });

        imposta(iniziale);
    })();

    /* ------------------------------------------------------------------ *
     * Filtro promozioni (riepilogo circuiti): mostra Tutte / Attive / Passate.
     * Tutto il filtraggio è lato client sui dati già presenti in pagina.
     * Sul contenitore: data-promo-filter-root
     * Sui bottoni: data-promo-filter="tutte|attive|passate"
     * Sulle sezioni: data-promo-group="attive|passate"
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-promo-filter-root]').forEach(function (root) {
        var buttons = root.querySelectorAll('[data-promo-filter]');
        var groups = root.querySelectorAll('[data-promo-group]');
        if (!buttons.length || !groups.length) return;

        var applica = function (valore) {
            groups.forEach(function (g) {
                var match = valore === 'tutte' || g.getAttribute('data-promo-group') === valore;
                g.classList.toggle('tp-hidden', !match);
            });
            buttons.forEach(function (b) {
                var on = b.getAttribute('data-promo-filter') === valore;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        };

        buttons.forEach(function (b) {
            b.addEventListener('click', function () {
                applica(b.getAttribute('data-promo-filter'));
            });
        });

        applica('tutte');
    });

    /* ------------------------------------------------------------------ *
     * Combobox di ricerca: trasforma un elenco in una barra di ricerca.
     * L'utente digita per filtrare; la scelta popola l'<input hidden>
     * effettivamente inviato col form (così il backend riceve un id).
     * Markup: contenitore [data-combobox] con
     *   - [data-combobox-input]  : <input type="text"> di ricerca
     *   - [data-combobox-value]  : <input type="hidden"> inviato col form
     *   - [data-combobox-list]   : <ul role="listbox"> (attributo hidden)
     *   - [data-combobox-option] : <li role="option"> con data-value,
     *                              data-label (testo mostrato) e data-search
     *   - [data-combobox-empty]  : messaggio "nessun risultato" (opzionale)
     * Usata nella schermata Sanzioni per scegliere il pilota da sanzionare.
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-combobox]').forEach(function (root) {
        var input = root.querySelector('[data-combobox-input]');
        var hidden = root.querySelector('[data-combobox-value]');
        var list = root.querySelector('[data-combobox-list]');
        if (!input || !hidden || !list) return;

        var options = Array.prototype.slice.call(root.querySelectorAll('[data-combobox-option]'));
        var empty = root.querySelector('[data-combobox-empty]');
        var activeEl = null;

        var visibili = function () {
            return options.filter(function (o) { return !o.hidden; });
        };

        var setActive = function (el) {
            if (activeEl) activeEl.classList.remove('is-active');
            activeEl = el || null;
            if (activeEl) {
                activeEl.classList.add('is-active');
                activeEl.scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', activeEl.id || '');
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        };

        var apri = function () {
            if (!list.hidden) return;
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        var chiudi = function () {
            if (list.hidden) return;
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            setActive(null);
        };

        var pulisciErrore = function () {
            input.removeAttribute('aria-invalid');
            var row = input.closest('.form-row');
            var fb = row && row.querySelector('.tp-combobox-error');
            if (fb) fb.remove();
        };

        var filtra = function () {
            var q = input.value.trim().toLowerCase();
            options.forEach(function (o) {
                var hay = (o.getAttribute('data-search') || '').toLowerCase();
                o.hidden = q !== '' && hay.indexOf(q) === -1;
            });
            if (empty) empty.hidden = visibili().length !== 0;
            if (activeEl && activeEl.hidden) setActive(null);
        };

        var scegli = function (opt) {
            hidden.value = opt.getAttribute('data-value') || '';
            input.value = opt.getAttribute('data-label') || '';
            pulisciErrore();
            chiudi();
        };

        // Preselezione: arrivo da ?pilota_id=N o re-render dopo un errore.
        if (hidden.value) {
            var pre = options.filter(function (o) {
                return o.getAttribute('data-value') === hidden.value;
            })[0];
            if (pre) input.value = pre.getAttribute('data-label') || '';
        }

        input.addEventListener('focus', function () { filtra(); apri(); });
        input.addEventListener('input', function () {
            hidden.value = '';            // digitare annulla la scelta precedente
            pulisciErrore();
            filtra();
            apri();
            setActive(visibili()[0] || null);
        });
        input.addEventListener('keydown', function (e) {
            var vis = visibili();
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                apri();
                if (!vis.length) return;
                var i = vis.indexOf(activeEl);
                setActive(vis[Math.min(i + 1, vis.length - 1)] || vis[0]);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!vis.length) return;
                var j = vis.indexOf(activeEl);
                setActive(j <= 0 ? vis[0] : vis[j - 1]);
            } else if (e.key === 'Enter') {
                if (!list.hidden && activeEl) {
                    e.preventDefault();
                    scegli(activeEl);
                }
            } else if (e.key === 'Escape') {
                chiudi();
            }
        });

        // mousedown: seleziona prima che l'input perda il focus (blur).
        list.addEventListener('mousedown', function (e) {
            var opt = e.target.closest('[data-combobox-option]');
            if (!opt || !list.contains(opt)) return;
            e.preventDefault();
            scegli(opt);
        });

        // Chiudi cliccando fuori o spostando il focus altrove (Tab).
        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) chiudi();
        });
        root.addEventListener('focusout', function () {
            setTimeout(function () {
                if (!root.contains(document.activeElement)) chiudi();
            }, 0);
        });

        // Impedisci l'invio senza un pilota scelto dall'elenco (sostituisce il
        // required del vecchio <select>); il backend resta comunque l'autorità.
        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (hidden.value) return;
                e.preventDefault();
                input.setAttribute('aria-invalid', 'true');
                var row = input.closest('.form-row');
                if (row && !row.querySelector('.tp-combobox-error')) {
                    var fb = document.createElement('p');
                    fb.className = 'help tp-field-error tp-combobox-error';
                    fb.textContent = 'Scegli un pilota dall’elenco.';
                    row.appendChild(fb);
                }
                input.focus();
                apri();
                // Riabilita i bottoni bloccati dal lock-submit globale.
                setTimeout(function () {
                    form.querySelectorAll('button[type="submit"]').forEach(function (b) {
                        b.disabled = false;
                    });
                }, 0);
            });
        }
    });
})();
