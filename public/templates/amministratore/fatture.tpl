{extends file='layouts/page.tpl'}

{block name=body}
<div class="container tp-admin-fatturazione">
    {assign var='crumbs' value=[['label'=>'Fatturazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Documenti di fatturazione</h1>
            <p class="lead">Elenco delle ultime fatture emesse e layout dei documenti PDF.</p>
        </div>
    </div>

    {include file='amministratore/partials/sezione_template_fattura.tpl'}

    <div class="card">
        <h3 class="card-title">Ultime fatture emesse</h3>
        {if $ultime|@count == 0}
            <p class="text-muted mb-0">Nessuna fattura emessa.</p>
        {else}
            <div class="table-wrap is-ghost">
                <table class="table is-ghost">
                    <thead><tr><th>Numero</th><th>Cliente</th><th>Importo</th></tr></thead>
                    <tbody>
                    {foreach from=$ultime item=p}
                        {assign var='pval' value=$p.prezzo_valuta|default:'EUR'}
                        <tr>
                            <td><code>{$p.codice_identificativo|default:''}</code></td>
                            <td>{$p.nome|default:''} {$p.cognome|default:''}
                                <div class="text-muted text-small">{$p.nome_circuito|default:''}</div>
                            </td>
                            <td><strong>{money value=$p.prezzo_importo|default:0 currency=$pval}</strong></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
</div>
{/block}
