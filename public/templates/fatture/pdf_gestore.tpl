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
    {$app_name|default:'TrackPortal'} · Accesso circuito ·
    Emessa il {date_pretty sql=$pren.data_inserimento|default:''}
</p>

<h2>Cliente</h2>
<p><strong>{$pren.pilota_nome|default:''} {$pren.pilota_cognome|default:''}</strong></p>

<h2>Prenotazione</h2>
<table>
    <tr><td class="lbl">Circuito</td><td>{$pren.nome_circuito|default:''}</td></tr>
    <tr><td class="lbl">Inizio disponibilità</td><td>{date_pretty sql=$pren.inizio_sessione|default:''}</td></tr>
    <tr><td class="lbl">Fine disponibilità</td><td>{date_pretty sql=$pren.fine_sessione|default:''}</td></tr>
    <tr><td class="lbl">Assicurazione</td><td>{if $pren.assicurazione}Sì{else}No{/if}</td></tr>
    <tr><td class="lbl">Stato</td><td>{stato_label stato=$pst}</td></tr>
</table>

{if !empty($pren.promozione_titolo)}
<h2>Promozione applicata</h2>
<table>
    <tr><td class="lbl">Titolo</td><td>{$pren.promozione_titolo|default:''}</td></tr>
    <tr><td class="lbl">Sconto</td><td>{money value=$pren.sconto_importo|default:0 currency=$pval}</td></tr>
</table>
{/if}

<h2>Importo</h2>
<p class="totale">Quota accesso pista: {money value=$pren.imponibile_accesso|default:0 currency=$pval}</p>
{if $pst == 'cancellata'}
<p>Prenotazione cancellata · rimborso previsto al cliente: {money value=$pren.rimborso_previsto|default:0 currency=$pval}</p>
{/if}

<p class="footer">Documento riepilogativo non fiscale, generato automaticamente da {$app_name|default:'TrackPortal'}.</p>
</body>
</html>
