{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Tutti i circuiti','url'=>'/circuiti'],['label'=>'Circuito non trovato']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="empty-state">
        {icon name='alert' size=56}
        <h3>Circuito non trovato</h3>
        {if $id_circuito > 0}
            <p>Il circuito con identificativo <strong>{$id_circuito}</strong> non esiste o non è più disponibile.</p>
        {else}
            <p>L'identificativo del circuito non è valido.</p>
        {/if}
        <div class="flex tp-empty-actions mt-2">
            <a class="button is-primary" href="{url path='/'}">
                {icon name='home'} Torna alla home
            </a>
        </div>
    </div>
</div>
{/block}
