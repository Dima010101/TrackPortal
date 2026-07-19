{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'I miei circuiti','url'=>'/circuitiGestore'],['label'=>'Calendario']]}
    {include file='partials/breadcrumb.tpl'}

    {assign var='nome_circuito' value=''}
    {foreach from=$circuiti item=c}
        {if $c.id|default:0 == $circuito_id|default:0}{assign var='nome_circuito' value=$c.nome_circuito|default:''}{/if}
    {/foreach}

    <div class="page-header">
        <div>
            <h1>Calendario settimanale{if $nome_circuito != ''} · {$nome_circuito|escape}{/if}</h1>
            <p class="lead">Gestisci le sessioni di pista del circuito. Clicca uno slot libero per aprire o modificare una sessione.</p>
        </div>
    </div>

    {if $affiliazione_attesa|default:false}
        <div class="notification is-warning">
            {icon name='clock'}
            Affiliazione in attesa: non puoi ancora aggiungere nuovi circuiti finché un amministratore non approva il tuo profilo.
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
    {/if}

    <div class="detail-grid__main">
        {if $circuiti|@count == 0}
            <div class="empty-state">
                {icon name='flag' size=56}
                <h3>Nessun circuito registrato</h3>
                {if $affiliazione_attesa|default:false}
                    <p>In attesa dell'approvazione amministrativa non è possibile aggiungere tracciati.</p>
                {elseif $can_create|default:true}
                    <p>Aggiungi un circuito per pianificare le sessioni settimanali.</p>
                    <a class="button is-primary" href="{url path='/circuitiGestore/nuovo'}">
                        {icon name='plus'} Aggiungi circuito
                    </a>
                {else}
                    <p>Non hai circuiti registrati.</p>
                {/if}
            </div>
        {else}
            {include file='partials/calendario_settimana.tpl'
                cal_readonly=$cal_readonly|default:false
                cal_nav_base=$cal_nav_base|default:'/calendario'
            }
        {/if}
    </div>
</div>
{/block}

