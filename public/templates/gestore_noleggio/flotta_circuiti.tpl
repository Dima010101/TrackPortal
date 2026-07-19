{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Flotta']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Gestione flotta</h1>
            <p class="lead">Seleziona un circuito per visualizzare e gestire i veicoli della tua flotta.</p>
        </div>
        <a class="button is-primary" href="{url path='/flotta/nuovo'}">
            {icon name='plus'} Aggiungi veicolo
        </a>
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

    {if $circuiti|@count == 0}
        <div class="empty-state">
            {icon name='car' size=56}
            <h3>Nessun veicolo in flotta</h3>
            <p>Non risultano veicoli registrati su alcun circuito. Aggiungi il primo veicolo per iniziare.</p>
            <a class="button is-primary" href="{url path='/flotta/nuovo'}">
                {icon name='plus'} Aggiungi il primo veicolo
            </a>
        </div>
    {else}
        <div class="grid-cards">
            {foreach from=$circuiti item=c}
                {assign var='cid' value=$c.id|default:0}
                <div class="entity-card">
                    <h3 class="entity-card-title">{$c.nome_circuito|default:''|escape}</h3>
                    {if !empty($c.localita)}
                        <div class="text-muted text-small">{icon name='home' size=12} {$c.localita|escape}</div>
                    {/if}
                    <div class="flex">
                        <span class="badge confermata">{$c.veicoli_count|default:0} veicoli</span>
                        <span class="badge {if $c.veicoli_disponibili|default:0 > 0}confermata{else}cancellata{/if}">
                            {$c.veicoli_disponibili|default:0} disponibili
                        </span>
                    </div>
                    <div class="entity-card-footer">
                        <a class="button is-dark is-small" href="{url path="/flotta/circuito/{$cid}"}">
                            {icon name='car'} Vedi veicoli
                        </a>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
</div>
{/block}
