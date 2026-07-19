<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a1a1a; margin: 36px; }
        h1 { font-size: 18pt; margin: 0 0 4px; }
        h2 { font-size: 12pt; margin: 20px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #555; font-size: 9pt; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        td { padding: 5px 8px; vertical-align: top; }
        td.lbl { width: 38%; color: #555; }
        .totale { font-size: 14pt; font-weight: bold; margin-top: 16px; }
        .footer { margin-top: 32px; font-size: 8pt; color: #888; }
        .letterhead { margin-bottom: 18px; }
        .letterhead-logo { height: 28px; width: auto; }
    </style>
</head>
<body>
{assign var='pval' value=$pren.prezzo_valuta|default:'EUR'}
{assign var='pst' value=$pren.stato|default:''}
{assign var='doc_tipo' value=($pst == 'cancellata') ? 'Ricevuta di annullamento' : 'Ricevuta'}

{if $logo_data_uri}
<div class="letterhead">
    <img src="{$logo_data_uri}" alt="{$app_name|default:'TrackPortal'}" class="letterhead-logo">
</div>
{/if}

<h1>{$doc_tipo} {$pren.codice_identificativo|default:''}</h1>
<p class="meta">
    {$app_name|default:'TrackPortal'} ·
    Emessa il {date_pretty sql=$pren.data_inserimento|default:''}
</p>

<h2>Prenotazione circuito</h2>
<table>
    <tr><td class="lbl">Circuito</td><td>{$pren.nome_circuito|default:''}</td></tr>
    <tr><td class="lbl">Inizio</td><td>{date_pretty sql=$pren.inizio_sessione|default:''}</td></tr>
    <tr><td class="lbl">Fine</td><td>{date_pretty sql=$pren.fine_sessione|default:''}</td></tr>
    <tr><td class="lbl">Assicurazione</td><td>{if $pren.assicurazione}Sì{else}No{/if}</td></tr>
    <tr><td class="lbl">Stato</td><td>{stato_label stato=$pst}</td></tr>
</table>

<h2>Noleggio / veicolo</h2>
<table>
    {if !empty($pren.veicolo_noleggio_id)}
        <tr><td class="lbl">Veicolo a noleggio</td>
            <td>{$pren.v_marca|default:''} {$pren.v_modello|default:''} ({$pren.v_targa|default:''})</td></tr>
    {else}
        <tr><td class="lbl">Veicolo</td><td>Proprio · targa {$pren.targa_veicolo|default:''}</td></tr>
    {/if}
</table>

{if !empty($pren.promozione_titolo)}
<h2>Promozione</h2>
<table>
    <tr><td class="lbl">Titolo</td><td>{$pren.promozione_titolo|default:''}</td></tr>
    <tr><td class="lbl">Sconto</td><td>{money value=$pren.sconto_importo|default:0 currency=$pval}</td></tr>
</table>
{/if}

<h2>Importi</h2>
{assign var='imp_acc' value=$pren.imponibile_accesso|default:0}
{assign var='imp_nol' value=$pren.imponibile_noleggio|default:0}
{assign var='imp_ass' value=$pren.imponibile_assicurazione|default:0}
{if $imp_acc > 0 || $imp_nol > 0 || $imp_ass > 0}
<table>
    {if $imp_acc > 0}
        <tr><td class="lbl">Accesso in pista</td><td>{money value=$imp_acc currency=$pval}</td></tr>
    {/if}
    {if !empty($pren.veicolo_noleggio_id) && $imp_nol > 0}
        <tr><td class="lbl">Noleggio veicolo</td><td>{money value=$imp_nol currency=$pval}</td></tr>
    {/if}
    {if $pren.assicurazione && $imp_ass > 0}
        <tr><td class="lbl">Assicurazione</td><td>{money value=$imp_ass currency=$pval}</td></tr>
    {/if}
</table>
{if $pren.sconto_importo|default:0 > 0}
<p class="meta">Importi al netto dello sconto promozione di {money value=$pren.sconto_importo|default:0 currency=$pval}, già applicato.</p>
{/if}
{/if}
<p class="totale">Totale: {money value=$pren.prezzo_importo|default:0 currency=$pval}</p>
{if $pst == 'cancellata'}
<p>Rimborso previsto: {money value=$pren.rimborso_previsto|default:0 currency=$pval}</p>
<p>{$pren.causa_cancellazione|default:''}</p>
{/if}

<p class="footer">Documento riepilogativo non fiscale, generato automaticamente da {$app_name|default:'TrackPortal'}.</p>
</body>
</html>
