{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {assign var='crumbs' value=[['label'=>'Commissioni','url'=>'/commissioni'],['label'=>'Riepilogo modifica']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Riepilogo modifica</h1>
            <p class="lead">Verifica le variazioni prima di renderle definitive.</p>
        </div>
    </div>

    <div class="card tp-panel-medium mb-3">
        <h3 class="card-title">Confronto parametri</h3>
        <dl class="confirm-panel-details">
            <div class="confirm-panel-detail">
                <dt>Prezzo assicurazione (attuale)</dt>
                <dd>{money value=$prezzo_attuale}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Prezzo assicurazione (nuovo)</dt>
                <dd><strong>{money value=$prezzo_nuovo}</strong></dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Commissione (attuale)</dt>
                <dd>{$perc_attuale|string_format:"%.2f"}%</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Commissione (nuova)</dt>
                <dd><strong>{$perc_nuova|string_format:"%.2f"}%</strong></dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Aliquota IVA (attuale)</dt>
                <dd>{$aliquota_attuale|default:0|string_format:"%.2f"}%</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Aliquota IVA (nuova)</dt>
                <dd><strong>{$aliquota_nuova|default:0|string_format:"%.2f"}%</strong></dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Ricavi assicurazione stimati (attuale)</dt>
                <dd>{money value=$ricavo_attuale}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Ricavi assicurazione stimati (nuovo)</dt>
                <dd><strong>{money value=$ricavo_nuovo}</strong></dd>
            </div>
        </dl>
    </div>

    <form method="post" action="{url path='/commissioni/conferma'}" class="card form-stack tp-panel-narrow">
        {csrf_field}
        <p class="text-muted mb-2">Confermando, i nuovi valori saranno applicati a tutte le prenotazioni future.</p>
        <div class="btn-group">
            <button class="button is-primary" type="submit">{icon name='check'} Conferma modifiche</button>
            <a class="button is-dark" href="{url path='/commissioni'}">Annulla</a>
        </div>
    </form>
</div>
{/block}
