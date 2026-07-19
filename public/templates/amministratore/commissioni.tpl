{extends file='layouts/page.tpl'}

{block name=body}
{assign var='form_prezzo' value=$form.prezzo_assicurazione|default:$prezzo}
{assign var='form_perc' value=$form.percentuale_commissione|default:$perc}
{assign var='form_aliquota' value=$form.aliquota_iva|default:$aliquota}

<div class="container tp-admin-commissioni">
    {assign var='crumbs' value=[['label'=>'Commissioni']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header tp-admin-page-header">
        <div class="tp-admin-page-header-text">
            <h1>Assicurazione e commissioni</h1>
            <p class="lead">Parametri economici e storico delle modifiche.</p>
        </div>
    </div>

    {include file='amministratore/partials/sezione_parametri_economici.tpl'}
</div>
{/block}
