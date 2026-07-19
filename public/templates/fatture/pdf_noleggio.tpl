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
    {$app_name|default:'TrackPortal'} · Noleggio veicolo ·
    Emessa il {date_pretty sql=$pren.data_inserimento|default:''}
</p>

<h2>Veicolo noleggiato</h2>
<table>
    <tr><td class="lbl">Marca / modello</td><td>{$pren.v_marca|default:''} {$pren.v_modello|default:''}</td></tr>
    <tr><td class="lbl">Targa</td><td>{$pren.v_targa|default:''}</td></tr>
    <tr><td class="lbl">Categoria</td><td>{tp_ucfirst s=$pren.v_categoria|default:'auto'}</td></tr>
    {if !empty($pren.v_anno)}
        <tr><td class="lbl">Anno</td><td>{$pren.v_anno}</td></tr>
    {/if}
    {if !empty($pren.v_potenza_cv)}
        <tr><td class="lbl">Potenza</td><td>{$pren.v_potenza_cv} CV</td></tr>
    {/if}
    <tr><td class="lbl">Posti</td><td>{$pren.v_capienza|default:1}</td></tr>
</table>

<h2>Periodo noleggio</h2>
<table>
    <tr><td class="lbl">Consegna</td><td>{date_pretty sql=$pren.inizio_sessione|default:''}</td></tr>
    <tr><td class="lbl">Riconsegna</td><td>{date_pretty sql=$pren.fine_sessione|default:''}</td></tr>
    <tr><td class="lbl">Stato</td><td>{stato_label stato=$pst}</td></tr>
</table>

{if !empty($pren.sconto_importo) && $pren.sconto_importo > 0}
<h2>Sconto applicato</h2>
<p>{money value=$pren.sconto_importo|default:0 currency=$pval}</p>
{/if}

<h2>Importo noleggio</h2>
<p class="totale">Quota noleggio: {money value=$pren.imponibile_noleggio|default:0 currency=$pval}</p>
{if $pst == 'cancellata'}
<p>Noleggio annullato · rimborso previsto: {money value=$pren.rimborso_previsto|default:0 currency=$pval}</p>
{/if}

<p class="footer">Documento riepilogativo non fiscale, generato automaticamente da {$app_name|default:'TrackPortal'}.</p>
</body>
</html>
