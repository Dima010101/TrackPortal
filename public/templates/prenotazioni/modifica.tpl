{extends file='layouts/page.tpl'}

{block name=body}
{assign var='pid' value=$pren.id|default:0}
{assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Prenotazioni','url'=>'/prenotazioni'],['label'=>'Modifica prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Modifica prenotazione</h1>
            <p class="lead">{$pren.codice_identificativo|default:''|escape} · {$pren.nome_circuito|default:''|escape}</p>
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

    {valuta_switcher}

    <div class="card tp-panel-medium">
        <form method="post" action="{url path="/prenotazioni/{$pid}/modifica"}" class="form-stack is-wide">
            {csrf_field}

            <dl class="confirm-panel-details mb-4">
                <div class="confirm-panel-detail">
                    <dt>Circuito</dt>
                    <dd>{$pren.nome_circuito|default:''|escape}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Fascia oraria</dt>
                    <dd>{date_pretty sql=$pren.inizio_sessione|default:''} – {date_pretty sql=$pren.fine_sessione|default:''}</dd>
                </div>
            </dl>

            {if empty($pren.veicolo_noleggio_id)}
                <div class="form-row">
                    <label for="targa_veicolo">Targa veicolo proprio</label>
                    <input id="targa_veicolo" name="targa_veicolo" required class="input is-uppercase"
                           maxlength="10" autocapitalize="characters" autocomplete="off" spellcheck="false"
                           pattern="{literal}[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{2}[0-9]{5}{/literal}"
                           title="Targa italiana: auto AA000AA oppure moto AA00000."
                           placeholder="Es. AB123CD"
                           value="{$form.targa_veicolo|default:''|escape}">
                    <span class="help">Auto: AA000AA · Moto: AA00000.</span>
                </div>
            {else}
                <p class="text-muted mb-3">
                    Veicolo a noleggio: <strong>{$pren.v_marca|default:''|escape} {$pren.v_modello|default:''|escape}</strong>
                    ({$pren.v_targa|default:''|escape}) — non modificabile.
                </p>
            {/if}

            <div class="form-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="assicurazione" value="1"
                           {if $form.assicurazione|default:'0' == '1'}checked{/if}>
                    Aggiungi o mantieni assicurazione ({prezzo value=$prezzo_assicurazione|default:0 currency=$pval})
                </label>
            </div>

            <div class="btn-group mt-3">
                <button class="button is-primary" type="submit">{icon name='check'} Conferma modifiche</button>
                <a class="button is-dark" href="{url path="/prenotazioni/{$pid}"}">Annulla</a>
            </div>
        </form>
    </div>
</div>
{/block}
