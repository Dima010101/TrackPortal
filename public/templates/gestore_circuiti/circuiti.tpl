{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>I miei circuiti</h1>
            <p class="lead">Elenco dei circuiti che gestisci. Puoi aggiungere nuovi tracciati o consultare il calendario sessioni.</p>
        </div>
        {if $can_create|default:true}
            <a class="button is-primary" href="{url path='/circuitiGestore/nuovo'}">
                {icon name='plus'} Aggiungi circuito
            </a>
        {/if}
    </div>

    {if $affiliazione_attesa|default:false}
        <div class="notification is-warning">
            {icon name='clock'}
            La tua richiesta di <strong>affiliazione è in attesa di approvazione</strong>.
            Potrai registrare circuiti non appena un amministratore avrà confermato il tuo profilo.
        </div>
    {elseif $affiliazione|default:'' == 'rifiutata'}
        <div class="notification is-danger">
            La tua richiesta di affiliazione è stata respinta. Contatta l'amministrazione per maggiori informazioni.
        </div>
    {/if}

    {if $empty_list|default:false}
        <div class="empty-state">
            {icon name='flag' size=56}
            <h3>Nessun circuito registrato</h3>
            {if $affiliazione_attesa|default:false}
                <p>In attesa dell'approvazione amministrativa non è possibile aggiungere tracciati.</p>
            {elseif $can_create|default:true}
                <p>Non hai ancora circuiti associati al tuo profilo. Aggiungi il primo tracciato per iniziare a pianificare le sessioni.</p>
                <a class="button is-primary" href="{url path='/circuitiGestore/nuovo'}">
                    {icon name='plus'} Aggiungi il primo circuito
                </a>
            {else}
                <p>Non hai ancora circuiti associati al tuo profilo.</p>
            {/if}
        </div>
    {else}
        <div class="grid-cards">
            {foreach from=$rows item=c}
                {assign var='cid' value=$c.id|default:0}
                <div class="entity-card">
                    <h3 class="entity-card-title">{$c.nome_circuito|default:''}</h3>
                    <div class="text-muted text-small">
                        {icon name='home' size=12} {$c.localita|default:''}
                        {if !empty($c.indirizzo)} — {$c.indirizzo}{/if}
                    </div>
                    <div class="text-muted text-small">
                        {icon name='car' size=12} {tp_ucfirst s=$c.tipologia_veicoli|default:''}
                        · {$c.numero_box|default:0} box
                    </div>
                    <div class="entity-card-footer">
                        <a class="button is-dark is-small" href="{url path="/circuiti/{$cid}"}">
                            Anteprima
                        </a>
                        <a class="button is-primary is-small" href="{url path="/calendario?circuito_id={$cid}"}">
                            {icon name='calendar'} Calendario
                        </a>
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
</div>
{/block}
