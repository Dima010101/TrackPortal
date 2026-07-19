{extends file='layouts/page.tpl'}

{block name=body}
{assign var='sid' value=$sessione.id|default:0}
{assign var='cid' value=$circuito.id|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Veicolo proprio</h1>
            {if $circuito}
                <p class="lead">{$circuito.nome_circuito|default:''|escape} · {$sessione.inizio|default:''|escape}</p>
            {/if}
        </div>
    </div>

    {foreach from=$errors|default:[] item=err}
        <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
    {/foreach}

    {if $sessione && $circuito}
        <form method="post" action="{url path='/prenotazione/targa'}">
            {csrf_field}
            <div class="detail-grid detail-grid--booking">
                <div class="card form-stack">
                    <h3 class="card-title">Inserisci la targa</h3>
                    <p class="text-muted text-small">Opzione A — veicolo proprio.</p>
                    <div class="form-row">
                        <label for="targa">Targa</label>
                        <input type="text" id="targa" name="targa" value="{$state.targa|default:''|escape}"
                               required maxlength="10" placeholder="Es. AB123CD"
                               class="input is-uppercase" autocomplete="off" autocapitalize="characters"
                               spellcheck="false"
                               pattern="{literal}[A-Za-z]{2}[0-9]{3}[A-Za-z]{2}|[A-Za-z]{2}[0-9]{5}{/literal}"
                               title="Targa italiana: auto AA000AA oppure moto AA00000.">
                        <span class="help">Auto: AA000AA · Moto: AA00000.</span>
                    </div>
                    <button class="button is-primary" type="submit">
                        Continua al riepilogo {icon name='arrow-right'}
                    </button>
                </div>

                {include file='partials/box_assicurazione.tpl'
                    prezzo_assicurazione=$prezzo_assicurazione|default:0
                    assicurazione_on=$state.assicurazione|default:0}
            </div>
        </form>
    {/if}
</div>
{/block}
