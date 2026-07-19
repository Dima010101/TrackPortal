{extends file='layouts/page.tpl'}

{block name=body}
{assign var='ru' value=$profilo.ruolo|default:$user.ruolo|default:''}
{assign var='f' value=$form|default:[]}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Il mio account']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Il mio account</h1>
            <p class="lead">Aggiorna i tuoi dati personali. Le modifiche verranno confermate in un secondo step.</p>
        </div>
    </div>

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <form method="post" action="{url path='/account/riepilogo'}" class="card form-stack is-wide">
        {csrf_field}
        <h3 class="card-title">Dati anagrafici</h3>
        <div class="grid grid-2">
            <div class="form-row">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" required maxlength="80" autocomplete="given-name"
                       autocapitalize="words"
                       value="{$f.nome|default:$profilo.nome|default:''|escape}">
            </div>
            <div class="form-row">
                <label for="cognome">Cognome</label>
                <input id="cognome" name="cognome" required maxlength="80" autocomplete="family-name"
                       autocapitalize="words"
                       value="{$f.cognome|default:$profilo.cognome|default:''|escape}">
            </div>
        </div>
        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
                   value="{$f.email|default:$profilo.email|default:''|escape}">
        </div>

        <h3 class="card-title mt-3">Cambia password (facoltativo)</h3>
        <p class="text-muted text-small">Lascia vuoti i campi se non desideri modificare la password.</p>
        <div class="form-row">
            <label for="password_vec">Password attuale</label>
            <input type="password" id="password_vec" name="password_vec" autocomplete="current-password">
        </div>
        <div class="grid grid-2">
            <div class="form-row">
                <label for="password_new">Nuova password</label>
                <input type="password" id="password_new" name="password_new" minlength="8" autocomplete="new-password">
            </div>
            <div class="form-row">
                <label for="password_confirm">Conferma nuova password</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="8" autocomplete="new-password">
            </div>
        </div>

        {if $ru == 'pilota' && $extra}
            <h3 class="card-title mt-3">Profilo pilota</h3>
            {assign var='cat' value=$f.categoria|default:$extra.categoria|default:'amatoriale'}
            <div class="form-row">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="amatoriale" {if $cat == 'amatoriale'}selected{/if}>Amatoriale</option>
                    <option value="professionista" {if $cat == 'professionista'}selected{/if}>Professionista</option>
                </select>
            </div>
            <div class="grid grid-2">
                <div class="form-row">
                    <label for="licenza">Licenza</label>
                    <input id="licenza" name="licenza" maxlength="60" autocomplete="off"
                           value="{$f.licenza|default:$extra.licenza|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="scadenza_licenza">Scadenza licenza</label>
                    <input type="date" id="scadenza_licenza" name="scadenza_licenza"
                           value="{$f.scadenza_licenza|default:$extra.scadenza_licenza|default:''|escape}">
                </div>
            </div>
            <div class="form-row">
                <label for="codice_fiscale">Codice fiscale</label>
                <input id="codice_fiscale" name="codice_fiscale" maxlength="16"
                       pattern="{literal}[A-Za-z0-9]{16}{/literal}" placeholder="es. RSSMRA85M01F205X"
                       title="Codice fiscale italiano di 16 caratteri."
                       style="text-transform:uppercase" autocomplete="off" spellcheck="false"
                       value="{$f.codice_fiscale|default:$extra.codice_fiscale|default:''|escape}">
                <span class="help">Intesta le fatture delle tue prenotazioni.</span>
            </div>

            <h3 class="card-title mt-3">Indirizzo di residenza</h3>
            <div class="grid grid-2">
                <div class="form-row">
                    <label for="indirizzo">Indirizzo</label>
                    <input id="indirizzo" name="indirizzo" maxlength="255" autocomplete="street-address"
                           placeholder="Via/Piazza e numero civico"
                           value="{$f.indirizzo|default:$extra.indirizzo|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="cap">CAP</label>
                    <input id="cap" name="cap" inputmode="numeric" maxlength="5"
                           pattern="{literal}\d{5}{/literal}" placeholder="5 cifre"
                           title="Il CAP è composto da 5 cifre."
                           value="{$f.cap|default:$extra.cap|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="comune">Comune</label>
                    <input id="comune" name="comune" maxlength="120" autocomplete="address-level2"
                           value="{$f.comune|default:$extra.comune|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="provincia">Provincia</label>
                    <input id="provincia" name="provincia" maxlength="2"
                           pattern="{literal}[A-Za-z]{2}{/literal}" placeholder="es. MI"
                           title="Sigla provincia di 2 lettere (es. MI)."
                           style="text-transform:uppercase"
                           value="{$f.provincia|default:$extra.provincia|default:''|escape}">
                </div>
            </div>

            <h3 class="card-title mt-3">Indirizzo di fatturazione</h3>
            <p class="text-muted text-small">È l'indirizzo che comparirà nelle fatture.</p>
            <div class="grid grid-2">
                <div class="form-row">
                    <label for="fatt_indirizzo">Indirizzo</label>
                    <input id="fatt_indirizzo" name="fatt_indirizzo" maxlength="255" autocomplete="billing street-address"
                           placeholder="Via/Piazza e numero civico"
                           value="{$f.fatt_indirizzo|default:$extra.fatt_indirizzo|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="fatt_cap">CAP</label>
                    <input id="fatt_cap" name="fatt_cap" inputmode="numeric" maxlength="5"
                           pattern="{literal}\d{5}{/literal}" placeholder="5 cifre"
                           title="Il CAP è composto da 5 cifre."
                           value="{$f.fatt_cap|default:$extra.fatt_cap|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="fatt_comune">Comune</label>
                    <input id="fatt_comune" name="fatt_comune" maxlength="120" autocomplete="billing address-level2"
                           value="{$f.fatt_comune|default:$extra.fatt_comune|default:''|escape}">
                </div>
                <div class="form-row">
                    <label for="fatt_provincia">Provincia</label>
                    <input id="fatt_provincia" name="fatt_provincia" maxlength="2"
                           pattern="{literal}[A-Za-z]{2}{/literal}" placeholder="es. MI"
                           title="Sigla provincia di 2 lettere (es. MI)."
                           style="text-transform:uppercase"
                           value="{$f.fatt_provincia|default:$extra.fatt_provincia|default:''|escape}">
                </div>
            </div>
        {elseif ($ru == 'gestore_circuiti' || $ru == 'gestore_noleggio') && $extra}
            {assign var='aff' value=$extra.affiliazione|default:''}
            {assign var='aff_badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
            <div class="mt-3">
                <h3 class="card-title">Profilo aziendale (sola lettura)</h3>
                <p><strong>Società:</strong> {$extra.nome_societa|default:''}</p>
                <p><strong>Partita IVA:</strong> <code>{$extra.partita_iva|default:''}</code></p>
                <p><strong>Stato affiliazione:</strong>
                   <span class="badge {$aff_badge}">{tp_ucfirst s=$aff}</span>
                </p>
            </div>
        {/if}

        <div class="mt-3">
            <button class="button is-primary" type="submit">{icon name='check'} Procedi al riepilogo</button>
        </div>
    </form>

    {if $ru == 'pilota' && $extra}
        {assign var='certPath' value=$extra.certificato_medico_path|default:''}
        {assign var='licPath' value=$extra.licenza_path|default:''}
        {assign var='docStato' value=$extra.documenti_stato|default:'in_attesa'}
        {assign var='docCompleti' value=($certPath != '' && $licPath != '')}
        {assign var='catSel' value=$f.categoria|default:$extra.categoria|default:'amatoriale'}
        {assign var='licLabel' value=($catSel == 'professionista') ? 'Licenza ACI' : 'Patente di guida'}
        {if !$docCompleti}
            {assign var='docBadge' value='in_attesa'}
            {assign var='docEsito' value='Documenti incompleti'}
        {else}
            {assign var='docBadge' value=($docStato == 'approvati') ? 'confermata' : (($docStato == 'respinti') ? 'cancellata' : 'in_attesa')}
            {assign var='docEsito' value=($docStato == 'approvati') ? 'Approvati' : (($docStato == 'respinti') ? 'Respinti' : 'In attesa di convalida')}
        {/if}

        <div class="card is-wide mt-3">
            <div class="tp-doc-head">
                <h3 class="card-title mb-0">Documenti (PDF)</h3>
                <span class="badge {$docBadge}">{$docEsito}</span>
            </div>

            {if !$docCompleti}
                <div class="notification is-warning mb-3" role="alert">
                    {flash_icon type='warn'}
                    <span>Devi caricare <strong>entrambi i documenti</strong> (certificato medico e {$licLabel}) per poter prenotare le sessioni.</span>
                </div>
            {elseif $docStato == 'approvati'}
                <div class="notification is-success tp-flash mb-3" role="status">
                    {flash_icon type='ok'}
                    <span>I tuoi documenti sono stati convalidati: puoi prenotare le sessioni.</span>
                </div>
            {elseif $docStato == 'respinti'}
                <div class="notification is-danger mb-3" role="alert">
                    {flash_icon type='error'}
                    <span>I tuoi documenti sono stati <strong>respinti</strong>. Caricane di nuovi validi per poter prenotare le sessioni.</span>
                </div>
            {else}
                <div class="notification is-warning mb-3" role="alert">
                    {flash_icon type='warn'}
                    <span>I tuoi documenti sono <strong>in attesa di convalida</strong> da parte dell'amministrazione: non puoi ancora prenotare.</span>
                </div>
            {/if}

            <p class="text-muted text-small">
                Come <strong>{tp_ucfirst s=$catSel}</strong> devi fornire il <strong>certificato medico</strong>
                e la <strong>{$licLabel}</strong>. Ogni modifica richiede una nuova convalida dell'amministrazione.
                Formato PDF, max 10 MB.
            </p>

            {* certificato medico, stato ed eliminazione *}
            <div class="tp-doc-row">
                <div>
                    <strong>Certificato medico</strong>
                    {if $certPath != ''}
                        <div class="text-small">
                            <a href="{url path="/account/{$profilo.id|default:$user.id}/certificato_medico"}" target="_blank" rel="noopener">{icon name='receipt'} Visualizza PDF</a>
                        </div>
                    {else}
                        <div class="text-small text-muted">Nessun certificato medico caricato.</div>
                    {/if}
                </div>
                {if $certPath != ''}
                    <form method="post" action="{url path='/account/documenti/elimina'}" class="tp-doc-del">
                        {csrf_field}
                        <input type="hidden" name="tipo" value="certificato_medico">
                        <button class="button is-danger is-small" type="submit">{icon name='trash'} Elimina</button>
                    </form>
                {/if}
            </div>

            {* patente di guida o licenza ACI, stato ed eliminazione *}
            <div class="tp-doc-row">
                <div>
                    <strong>{$licLabel}</strong>
                    {if $licPath != ''}
                        <div class="text-small">
                            <a href="{url path="/account/{$profilo.id|default:$user.id}/licenza"}" target="_blank" rel="noopener">{icon name='receipt'} Visualizza PDF</a>
                        </div>
                    {else}
                        <div class="text-small text-muted">Nessun documento caricato.</div>
                    {/if}
                </div>
                {if $licPath != ''}
                    <form method="post" action="{url path='/account/documenti/elimina'}" class="tp-doc-del">
                        {csrf_field}
                        <input type="hidden" name="tipo" value="licenza">
                        <button class="button is-danger is-small" type="submit">{icon name='trash'} Elimina</button>
                    </form>
                {/if}
            </div>

            <hr class="divider">

            {* caricamento o sostituzione documenti *}
            <form method="post" action="{url path='/account/documenti'}"
                  class="form-stack" enctype="multipart/form-data">
                {csrf_field}
                <p class="text-muted text-small">Carica un nuovo file solo per i documenti che vuoi aggiungere o sostituire.</p>
                <div class="form-row">
                    <label for="certificato_medico">Certificato medico</label>
                    <input type="file" id="certificato_medico" name="certificato_medico" accept="application/pdf">
                </div>
                <div class="form-row">
                    <label for="licenza_pdf">{$licLabel}</label>
                    <input type="file" id="licenza_pdf" name="licenza_pdf" accept="application/pdf">
                </div>
                <div class="mt-2">
                    <button class="button is-primary" type="submit">{icon name='check'} Aggiorna documenti</button>
                </div>
            </form>
        </div>
    {/if}
</div>
{/block}
