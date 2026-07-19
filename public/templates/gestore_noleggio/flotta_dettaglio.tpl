{extends file='layouts/page.tpl'}

{block name=body}
{assign var='vid' value=$veicolo.id|default:0}
{assign var='cid' value=$circuito.id|default:0}
{assign var='dis' value=$veicolo.disponibile|default:false}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Dettaglio veicolo']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Dettaglio veicolo</h1>
            {if $circuito && $veicolo}
                <p class="lead">{$veicolo.marca|default:''|escape} {$veicolo.modello|default:''|escape} · {$circuito.nome_circuito|default:''|escape}</p>
            {/if}
        </div>
        {if $veicolo && $errors|@count == 0}
            <div class="actions">
                <a class="button is-primary" href="{url path="/flotta/veicolo/{$vid}/modifica"}">
                    {icon name='settings'} Modifica dati
                </a>
                {assign var='pren' value=$veicolo.pren_count|default:0}
                <form method="post" action="{url path="/flotta/veicolo/{$vid}/elimina"}"
                      style="display:inline;"
                      onsubmit="return confirm('Eliminare definitivamente questo veicolo dalla flotta? L\'operazione non è reversibile.');">
                    {csrf_field}
                    <button class="button is-danger" type="submit"{if $pren > 0} disabled title="Il veicolo ha prenotazioni associate: impostalo come non disponibile."{/if}>
                        {icon name='trash'} Elimina veicolo
                    </button>
                </form>
            </div>
        {/if}
    </div>

    {if $veicolo && $errors|@count == 0 && ($veicolo.pren_count|default:0) > 0}
        <div class="notification is-warning">
            Questo veicolo ha prenotazioni associate e non può essere eliminato.
            Per ritirarlo dalla flotta impostalo come «non disponibile» dalla pagina di modifica.
        </div>
    {/if}

    {if $errors|@count > 0}
        <div class="notification is-danger">
            <ul class="tp-error-list">
                {foreach from=$errors item=err}
                    <li>{$err|escape}</li>
                {/foreach}
            </ul>
        </div>
        <a class="button is-dark mt-2" href="{url path='/flotta'}">Torna ai circuiti</a>
    {elseif $veicolo}
        <div class="card tp-panel-medium">
            <dl class="confirm-panel-details">
                <div class="confirm-panel-detail">
                    <dt>Targa</dt><dd><code>{$veicolo.targa|default:''|escape}</code></dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Categoria</dt><dd>{tp_ucfirst s=$veicolo.categoria|default:''}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Marca / Modello</dt><dd>{$veicolo.marca|default:'—'|escape} {$veicolo.modello|default:''|escape}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Anno</dt><dd>{$veicolo.anno|default:'—'}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Potenza</dt><dd>{$veicolo.potenza_cv|default:'—'}{if $veicolo.potenza_cv} cv{/if}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Capienza</dt><dd>{$veicolo.capienza|default:1} posti</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Listino</dt>
                    <dd>{money value=$veicolo.prezzo|default:0}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Prenotazioni</dt><dd>{$veicolo.pren_count|default:0}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Disponibilità</dt>
                    <dd>
                        <span class="badge {if $dis}confermata{else}cancellata{/if}">
                            {if $dis}Disponibile{else}Non disponibile{/if}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    {/if}
</div>
{/block}
