{extends file='layouts/page.tpl'}

{block name=body}
{assign var='pid' value=$pren.id|default:0}
{assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}

<div class="container">
    {assign var='crumbs' value=[['label'=>'Prenotazioni','url'=>'/prenotazioni'],['label'=>'Annulla prenotazione']]}
    {include file='partials/breadcrumb.tpl'}

    <div class="page-header">
        <div>
            <h1>Annulla prenotazione</h1>
            <p class="lead">{$pren.codice_identificativo|default:''|escape} · {$pren.nome_circuito|default:''|escape}</p>
        </div>
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

    {valuta_switcher}

    {capture assign='cancel_details'}
        <dl class="confirm-panel-details">
            <div class="confirm-panel-detail">
                <dt>Prenotazione</dt>
                <dd>{if !empty($pren.codice_identificativo)}{$pren.codice_identificativo|escape}{else}#{$pid}{/if}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Circuito</dt>
                <dd>{$pren.nome_circuito|default:''|escape}</dd>
            </div>
            <div class="confirm-panel-detail">
                <dt>Importo pagato</dt>
                <dd>{prezzo value=$pren.prezzo_importo|default:0 currency=$pval}</dd>
            </div>
        </dl>
    {/capture}

    {capture assign='cancel_highlight'}Rimborso previsto: {prezzo value=$rimborso|default:0 currency=$pval} ({$rimborso_perc|default:70}% dell'importo){/capture}

    {capture assign='cancel_message'}La prenotazione verrà chiusa definitivamente. {if $pren.assicurazione}Con l'assicurazione il rimborso è pieno: ti verrà restituito il {$rimborso_perc|default:100}% dell'importo.{else}Senza assicurazione il rimborso dipende dall'anticipo: ora è del {$rimborso_perc|default:70}% (con l'assicurazione sarebbe stato il 100%).{/if} Indica il motivo della cancellazione.{/capture}

    {include file='partials/confirm_delete.tpl'
        confirm_panel_class='confirm-panel--booking'
        confirm_eyebrow='Azione irreversibile'
        confirm_title="Confermi l'annullamento?"
        confirm_message=$cancel_message
        confirm_highlight=$cancel_highlight
        confirm_highlight_icon='ticket'
        confirm_extra=$cancel_details
        confirm_form_action="{url path="/prenotazioni/{$pid}/cancella"}"
        confirm_cancel_url="{url path="/prenotazioni/{$pid}"}"
        confirm_cancel_label='No, torna al dettaglio'
        confirm_action='cancella'
        confirm_ok_label='Sì, annulla prenotazione'
        confirm_show_causa=true
        confirm_causa_value=$causa|default:''
    }
</div>
{/block}
