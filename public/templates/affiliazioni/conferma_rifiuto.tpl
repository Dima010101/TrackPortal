{extends file='layouts/page.tpl'}

{block name=body}
{assign var='r' value=$richiesta|default:[]}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Convalida','url'=>'/affiliazioni'],['label'=>'Richiesta respinta']]}
    {include file='partials/breadcrumb.tpl'}

    {assign var='isPilota' value=($r.tipo|default:'') == 'pilota'}
    <div class="notification is-warning">
        {icon name='x'}
        {if $isPilota}
            <strong>Documenti respinti.</strong>
            I documenti di
            <strong>{$r.nome|default:''|escape} {$r.cognome|default:''|escape}</strong>
            sono stati respinti: dovrà caricarne di nuovi validi per poter prenotare.
        {else}
            <strong>Richiesta respinta.</strong>
            La registrazione di
            <strong>{if ($r.nome_societa|default:'') != ''}{$r.nome_societa|escape}{else}{$r.nome|default:''|escape} {$r.cognome|default:''|escape}{/if}</strong>
            è stata rifiutata.
        {/if}
    </div>

    <div class="card">
        <h3 class="card-title">Riepilogo</h3>
        <dl class="tp-dl">
            {if ($r.nome_societa|default:'') != ''}
                <dt>Società</dt>
                <dd>{$r.nome_societa|default:''|escape}</dd>
            {else}
                <dt>Nominativo</dt>
                <dd>{$r.nome|default:''|escape} {$r.cognome|default:''|escape}</dd>
            {/if}
            <dt>Tipo</dt>
            <dd>{$r.tipo_label|default:''|escape}</dd>
            <dt>Stato</dt>
            <dd><span class="badge cancellata">Respinta</span></dd>
            {if $motivo|default:'' != ''}
                <dt>Motivazione</dt>
                <dd>{$motivo|escape|nl2br nofilter}</dd>
            {/if}
        </dl>
        <a class="button is-primary mt-2" href="{url path='/affiliazioni'}">
            Torna alle richieste
        </a>
    </div>
</div>
{/block}
