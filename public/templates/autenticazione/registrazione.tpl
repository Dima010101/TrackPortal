{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    <div class="auth-wrapper wide">
        <div class="card tp-auth-card">
            <div class="card-content">
            <div class="tp-auth-brand">{logo class='tp-logo tp-logo--auth'}</div>
            <h1 class="title is-4">Crea il tuo account</h1>
            <p class="subtitle is-6 text-muted">Pilota, gestore di circuiti o azienda di noleggio: scegli il tuo profilo.</p>
            <p class="text-muted text-small">I campi contrassegnati con <span class="has-text-danger">*</span> sono obbligatori.</p>

            {foreach from=$errors|default:[] item=err}
                <div class="notification is-danger tp-flash">{flash_icon type='error'}<span>{$err}</span></div>
            {/foreach}

            {assign var='o' value=$old|default:[]}
            {assign var='ruoloSel' value=$o.ruolo|default:'pilota'}
            <form method="post" class="form-stack is-wide" id="reg-form" enctype="multipart/form-data">
                {csrf_field}
                <div class="form-row">
                    <label for="ruolo">Tipo di account <span class="has-text-danger">*</span></label>
                    <select id="ruolo" name="ruolo">
                        <option value="pilota" {if $ruoloSel == 'pilota'}selected{/if}>Pilota</option>
                        <option value="gestore_circuiti" {if $ruoloSel == 'gestore_circuiti'}selected{/if}>Gestore di circuiti</option>
                        <option value="gestore_noleggio" {if $ruoloSel == 'gestore_noleggio'}selected{/if}>Azienda di noleggio</option>
                    </select>
                </div>

                <div class="grid grid-2">
                    <div class="form-row">
                        <label>Nome <span class="has-text-danger">*</span></label>
                        <input type="text" name="nome" required autocomplete="given-name"
                               maxlength="80" autocapitalize="words"
                               value="{$o.nome|default:''|escape}">
                    </div>
                    <div class="form-row">
                        <label>Cognome <span class="has-text-danger">*</span></label>
                        <input type="text" name="cognome" required autocomplete="family-name"
                               maxlength="80" autocapitalize="words"
                               value="{$o.cognome|default:''|escape}">
                    </div>
                </div>

                <div class="form-row">
                    <label>Email <span class="has-text-danger">*</span></label>
                    <input type="email" name="email" required autocomplete="email"
                           value="{$o.email|default:''|escape}">
                </div>

                <div class="grid grid-2">
                    <div class="form-row">
                        <label>Password <span class="has-text-danger">*</span></label>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password">
                        <span class="help">Minimo 8 caratteri.</span>
                    </div>
                    <div class="form-row">
                        <label>Conferma password <span class="has-text-danger">*</span></label>
                        <input type="password" name="password_conf" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div id="fields-pilota" data-fields="pilota" class="grid grid-2">
                    {assign var='catSel' value=$o.categoria|default:'amatoriale'}
                    <div class="form-row">
                        <label>Categoria <span class="has-text-danger">*</span></label>
                        <select id="categoria" name="categoria">
                            <option value="amatoriale" {if $catSel == 'amatoriale'}selected{/if}>Amatoriale</option>
                            <option value="professionista" {if $catSel == 'professionista'}selected{/if}>Professionista</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Licenza</label>
                        <input type="text" name="licenza" placeholder="Riferimento licenza"
                               maxlength="60" autocomplete="off"
                               value="{$o.licenza|default:''|escape}">
                    </div>
                    <div class="form-row">
                        <label>Scadenza licenza</label>
                        <input type="date" name="scadenza_licenza" value="{$o.scadenza_licenza|default:''|escape}">
                    </div>
                    <div class="form-row">
                        <label>Codice fiscale <span class="has-text-danger">*</span></label>
                        <input type="text" name="codice_fiscale" maxlength="16"
                               pattern="{literal}[A-Za-z0-9]{16}{/literal}" placeholder="es. RSSMRA85M01F205X"
                               title="Codice fiscale italiano di 16 caratteri."
                               style="text-transform:uppercase" autocomplete="off" spellcheck="false"
                               value="{$o.codice_fiscale|default:''|escape}">
                        <span class="help">Serve per intestare le fatture delle tue prenotazioni.</span>
                    </div>
                    <div class="form-row">
                        <label>Certificato medico (PDF) <span class="has-text-danger">*</span></label>
                        <input type="file" name="certificato_medico" accept="application/pdf">
                        <span class="help">Obbligatorio per tutti i piloti. Max 10 MB.</span>
                    </div>
                    <div class="form-row">
                        <label><span id="lic-doc-label">{if $catSel == 'professionista'}Licenza ACI{else}Patente di guida{/if}</span> (PDF) <span class="has-text-danger">*</span></label>
                        <input type="file" name="licenza_pdf" accept="application/pdf">
                        <span class="help">Amatoriale: patente di guida · Professionista: licenza ACI. Max 10 MB.</span>
                    </div>
                </div>

                <div data-fields="pilota">
                    <h2 class="title is-6 mt-2 mb-2">Indirizzo di residenza</h2>
                    <div class="grid grid-2">
                        <div class="form-row">
                            <label>Indirizzo <span class="has-text-danger">*</span></label>
                            <input type="text" name="indirizzo" maxlength="255" autocomplete="street-address"
                                   placeholder="Via/Piazza e numero civico"
                                   value="{$o.indirizzo|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>CAP <span class="has-text-danger">*</span></label>
                            <input type="text" name="cap" inputmode="numeric" maxlength="5"
                                   pattern="{literal}\d{5}{/literal}" placeholder="5 cifre"
                                   title="Il CAP è composto da 5 cifre."
                                   value="{$o.cap|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>Comune <span class="has-text-danger">*</span></label>
                            <input type="text" name="comune" maxlength="120" autocomplete="address-level2"
                                   value="{$o.comune|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>Provincia <span class="has-text-danger">*</span></label>
                            <input type="text" name="provincia" maxlength="2"
                                   pattern="{literal}[A-Za-z]{2}{/literal}" placeholder="es. MI"
                                   title="Sigla provincia di 2 lettere (es. MI)."
                                   style="text-transform:uppercase"
                                   value="{$o.provincia|default:''|escape}">
                        </div>
                    </div>
                </div>

                <div data-fields="pilota">
                    <h2 class="title is-6 mt-2 mb-2">Indirizzo di fatturazione</h2>
                    <p class="text-muted text-small">È l'indirizzo che comparirà nelle fatture.</p>
                    <div class="grid grid-2">
                        <div class="form-row">
                            <label>Indirizzo <span class="has-text-danger">*</span></label>
                            <input type="text" name="fatt_indirizzo" maxlength="255" autocomplete="billing street-address"
                                   placeholder="Via/Piazza e numero civico"
                                   value="{$o.fatt_indirizzo|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>CAP <span class="has-text-danger">*</span></label>
                            <input type="text" name="fatt_cap" inputmode="numeric" maxlength="5"
                                   pattern="{literal}\d{5}{/literal}" placeholder="5 cifre"
                                   title="Il CAP è composto da 5 cifre."
                                   value="{$o.fatt_cap|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>Comune <span class="has-text-danger">*</span></label>
                            <input type="text" name="fatt_comune" maxlength="120" autocomplete="billing address-level2"
                                   value="{$o.fatt_comune|default:''|escape}">
                        </div>
                        <div class="form-row">
                            <label>Provincia <span class="has-text-danger">*</span></label>
                            <input type="text" name="fatt_provincia" maxlength="2"
                                   pattern="{literal}[A-Za-z]{2}{/literal}" placeholder="es. MI"
                                   title="Sigla provincia di 2 lettere (es. MI)."
                                   style="text-transform:uppercase"
                                   value="{$o.fatt_provincia|default:''|escape}">
                        </div>
                    </div>
                </div>

                <div id="fields-azienda" data-fields="azienda" class="grid grid-2 tp-hidden">
                    <div class="form-row">
                        <label>Nome società <span class="has-text-danger">*</span></label>
                        <input type="text" name="nome_societa" maxlength="150" autocomplete="organization"
                               value="{$o.nome_societa|default:''|escape}">
                    </div>
                    <div class="form-row">
                        <label>Partita IVA <span class="has-text-danger">*</span></label>
                        <input type="text" name="partita_iva" inputmode="numeric" maxlength="11"
                               pattern="{literal}\d{11}{/literal}" placeholder="11 cifre"
                               title="La partita IVA italiana è composta da 11 cifre."
                               value="{$o.partita_iva|default:''|escape}">
                    </div>
                </div>

                <button type="submit" class="button is-primary is-medium is-fullwidth">
                    {icon name='check'} Crea account
                </button>
            </form>

            <hr class="divider">
            <p class="text-center text-muted mb-0">
                Hai già un account?
                <a href="{url path='/auth/accesso'}"><strong>Accedi</strong></a>.
            </p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const sel = document.getElementById('ruolo');
    const pilotaEls = document.querySelectorAll('[data-fields="pilota"]');
    const aziendaEls = document.querySelectorAll('[data-fields="azienda"]');
    function update() {
        const isPilota = sel.value === 'pilota';
        pilotaEls.forEach(function (el) { el.classList.toggle('tp-hidden', !isPilota); });
        aziendaEls.forEach(function (el) { el.classList.toggle('tp-hidden', isPilota); });
    }
    sel.addEventListener('change', update);
    update();

    // Etichetta del secondo documento in base alla categoria pilota.
    const cat = document.getElementById('categoria');
    const licLabel = document.getElementById('lic-doc-label');
    if (cat && licLabel) {
        const syncDoc = function () {
            licLabel.textContent = cat.value === 'professionista' ? 'Licenza ACI' : 'Patente di guida';
        };
        cat.addEventListener('change', syncDoc);
        syncDoc();
    }
})();
</script>
{/block}
