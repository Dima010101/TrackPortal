{extends file='layouts/page.tpl'}

{block name=body}
{assign var='dis' value=$veicolo.disponibile|default:false}
{assign var='vid' value=$veicolo.id|default:0}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Veicolo aggiunto']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Veicolo aggiunto</h1>
            <p class="lead">Il nuovo veicolo è stato registrato nella tua flotta.</p>
        </div>
    </div>

    <div class="notification is-success">
        {icon name='check'}
        <strong>Conferma aggiunta:</strong>
        il veicolo <em>{$veicolo.marca|default:''|escape} {$veicolo.modello|default:''|escape}</em>
        (<code>{$veicolo.targa|default:''|escape}</code>)
        {if $circuito} è ora noleggiabile sul circuito <em>{$circuito.nome_circuito|default:''|escape}</em>{/if}.
    </div>

    <div class="card tp-panel-medium">
        <h3 class="card-title">Riepilogo</h3>
        <dl class="confirm-panel-details">
            <div class="confirm-panel-detail">
                <dt>Circuito</dt><dd>{$circuito.nome_circuito|default:'—'|escape}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Veicolo</dt><dd>{$veicolo.marca|default:''|escape} {$veicolo.modello|default:''|escape}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Targa</dt><dd><code>{$veicolo.targa|default:''|escape}</code></dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Categoria</dt><dd>{tp_ucfirst s=$veicolo.categoria|default:''}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Anno / Potenza</dt>
                <dd>{$veicolo.anno|default:'—'} · {$veicolo.potenza_cv|default:'—'}{if $veicolo.potenza_cv} cv{/if}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Capienza</dt><dd>{$veicolo.capienza|default:1} posti</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Listino</dt>
                <dd>{money value=$veicolo.prezzo|default:0}</dd>
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

        <div class="flex mt-3">
            <a class="button is-primary" href="{url path='/flotta'}">
                {icon name='arrow-left'} Vai a gestione flotta
            </a>
            {if $vid > 0}
                <a class="button is-dark" href="{url path="/flotta/veicolo/{$vid}"}">
                    {icon name='eye'} Dettaglio veicolo
                </a>
            {/if}
            <a class="button is-dark" href="{url path='/flotta/nuovo'}">
                {icon name='plus'} Aggiungi un altro
            </a>
        </div>
    </div>
</div>
{/block}
