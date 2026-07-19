{extends file='layouts/page.tpl'}

{block name=body}
{assign var='dis' value=$veicolo.disponibile|default:false}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta','url'=>'/flotta'],['label'=>'Veicolo aggiornato']]}
    {include file='partials/breadcrumb.tpl'}
    <div class="page-header">
        <div>
            <h1>Riepilogo</h1>
            <p class="lead">I dati del veicolo sono stati salvati correttamente.</p>
        </div>
    </div>

    <div class="notification is-success tp-flash mb-3">
        {flash_icon type='ok'}
        <span>Modifiche registrate con successo.</span>
    </div>

    <div class="card tp-panel-medium">
        <h3 class="card-title">Dati aggiornati</h3>
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

        <a class="button is-primary mt-3" href="{url path='/flotta'}">
            {icon name='arrow-left'} Torna a gestione flotta
        </a>
    </div>
</div>
{/block}
