{extends file='layouts/page.tpl'}

{block name=body}
<div class="container">
    {back_button href='/dashboard' label='Dashboard'}

    <div class="page-header">
        <div>
            <h1>Richieste di affiliazione</h1>
            <p class="lead">Approva o rifiuta le aziende che hanno richiesto di affiliarsi a TrackPortal.</p>
        </div>
    </div>

    <section class="tp-section-block mt-3">
        <h2 class="section-title">Gestori di circuiti</h2>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Società</th><th>Referente</th><th>Stato</th><th class="text-right">Azioni</th></tr></thead>
            <tbody>
            {foreach from=$gestori item=r}
                {assign var='aff' value=$r.affiliazione|default:''}
                {assign var='badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
                <tr>
                    <td><strong>{$r.nome_societa|default:''}</strong>
                        <div class="text-muted text-small">P.IVA {$r.partita_iva|default:''}</div>
                    </td>
                    <td>{$r.nome|default:''} {$r.cognome|default:''}
                        <div class="text-muted text-small">{$r.email|default:''}</div>
                    </td>
                    <td><span class="badge {$badge}">{tp_ucfirst s=$aff}</span></td>
                    <td class="actions">
                        <form method="post" class="inline-form">
                            {csrf_field}
                            <input type="hidden" name="tipo"  value="gestore_circuiti">
                            <input type="hidden" name="id"    value="{$r.id|default:0}">
                            <input type="hidden" name="stato" value="approvata">
                            <button class="button is-primary is-small" type="submit">{icon name='check'} Approva</button>
                        </form>
                        <form method="post" class="inline-form">
                            {csrf_field}
                            <input type="hidden" name="tipo"  value="gestore_circuiti">
                            <input type="hidden" name="id"    value="{$r.id|default:0}">
                            <input type="hidden" name="stato" value="rifiutata">
                            <button class="button is-danger is-small" type="submit">{icon name='x'} Rifiuta</button>
                        </form>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
    </section>

    <section class="tp-section-block">
        <h2 class="section-title">Aziende di noleggio</h2>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Società</th><th>Referente</th><th>Stato</th><th class="text-right">Azioni</th></tr></thead>
            <tbody>
            {foreach from=$aziende item=r}
                {assign var='aff' value=$r.affiliazione|default:''}
                {assign var='badge' value=($aff == 'approvata') ? 'confermata' : (($aff == 'rifiutata') ? 'cancellata' : 'in_attesa')}
                <tr>
                    <td><strong>{$r.nome_societa|default:''}</strong>
                        <div class="text-muted text-small">P.IVA {$r.partita_iva|default:''}</div>
                    </td>
                    <td>{$r.nome|default:''} {$r.cognome|default:''}
                        <div class="text-muted text-small">{$r.email|default:''}</div>
                    </td>
                    <td><span class="badge {$badge}">{tp_ucfirst s=$aff}</span></td>
                    <td class="actions">
                        <form method="post" class="inline-form">
                            {csrf_field}
                            <input type="hidden" name="tipo"  value="gestore_noleggio">
                            <input type="hidden" name="id"    value="{$r.id|default:0}">
                            <input type="hidden" name="stato" value="approvata">
                            <button class="button is-primary is-small" type="submit">{icon name='check'} Approva</button>
                        </form>
                        <form method="post" class="inline-form">
                            {csrf_field}
                            <input type="hidden" name="tipo"  value="gestore_noleggio">
                            <input type="hidden" name="id"    value="{$r.id|default:0}">
                            <input type="hidden" name="stato" value="rifiutata">
                            <button class="button is-danger is-small" type="submit">{icon name='x'} Rifiuta</button>
                        </form>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        </div>
    </section>
</div>
{/block}
