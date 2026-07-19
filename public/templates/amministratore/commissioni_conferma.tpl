{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Commissioni','url'=>'/commissioni'],['label'=>'Esito modifica']]}
    {include file='partials/breadcrumb.tpl'}
    <div class="page-header">
        <div>
            <h1>{if $success}Modifica confermata{else}Conferma non riuscita{/if}</h1>
            <p class="lead">
                {if $success}
                    I parametri economici della piattaforma sono stati aggiornati.
                {else}
                    Non è stato possibile completare l'operazione.
                {/if}
            </p>
        </div>
    </div>

    {if $success}
        <div class="notification is-success tp-flash mb-3">
            {flash_icon type='ok'}
            <span>Parametri economici salvati correttamente.</span>
        </div>

        <div class="card tp-panel-medium">
            <h3 class="card-title">Valori applicati</h3>
            <dl class="confirm-panel-details">
                <div class="confirm-panel-detail">
                    <dt>Prezzo assicurazione</dt>
                    <dd>{money value=$prezzo}</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Commissione piattaforma</dt>
                    <dd>{$perc|string_format:"%.2f"}%</dd>
                </div>
                <div class="confirm-panel-detail">
                    <dt>Aliquota IVA</dt>
                    <dd>{$aliquota|default:0|string_format:"%.2f"}%</dd>
                </div>
            </dl>
            <a class="button is-primary mt-3" href="{url path='/commissioni'}">
                {icon name='gauge'} Torna alla dashboard
            </a>
        </div>
    {else}
        {foreach from=$errors|default:[] item=err}
            <div class="notification is-danger tp-flash mb-2">{flash_icon type='error'}<span>{$err|escape}</span></div>
        {/foreach}
        <a class="button is-primary" href="{url path='/commissioni'}">
            {icon name='arrow-left'} Torna alla dashboard
        </a>
    {/if}
</div>
{/block}
