<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1a1a1a; margin: 36px; }
        h1 { font-size: 18pt; margin: 0 0 2px; }
        .sub { color: #555; font-size: 9pt; margin-bottom: 18px; }
        .parti { width: 100%; border-collapse: collapse; margin: 12px 0 18px; }
        .parti td { width: 50%; vertical-align: top; padding: 8px 10px; border: 1px solid #ddd; }
        .parti .ruolo { color: #888; font-size: 8pt; text-transform: uppercase; letter-spacing: .5px; }
        .parti .den { font-weight: bold; font-size: 11pt; }
        table.righe { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.righe th, table.righe td { padding: 6px 8px; border-bottom: 1px solid #e2e2e2; text-align: left; }
        table.righe th { background: #f4f4f4; font-size: 8.5pt; text-transform: uppercase; color: #555; }
        table.righe td.num, table.righe th.num { text-align: right; white-space: nowrap; }
        .totali { width: 50%; margin-left: 50%; border-collapse: collapse; margin-top: 10px; }
        .totali td { padding: 4px 8px; }
        .totali td.lbl { color: #555; text-align: right; }
        .totali td.val { text-align: right; white-space: nowrap; }
        .totali tr.grand td { font-size: 13pt; font-weight: bold; border-top: 2px solid #333; }
        .note { margin-top: 22px; font-size: 8.5pt; color: #555; }
        .footer { margin-top: 26px; font-size: 8pt; color: #999; border-top: 1px solid #eee; padding-top: 8px; }
        .letterhead { margin-bottom: 14px; }
        .letterhead-logo { height: 26px; width: auto; }
    </style>
</head>
<body>
{assign var='val' value=$doc.valuta|default:'EUR'}
{assign var='isNota' value=($doc.tipo == 'nota_credito')}

{if $logo_data_uri}
<div class="letterhead">
    <img src="{$logo_data_uri}" alt="{$app_name|default:'TrackPortal'}" class="letterhead-logo">
</div>
{/if}

<h1>{if $isNota}Nota di credito{else}Fattura{/if} n. {$doc.numero_formattato|default:''}</h1>
<p class="sub">
    Data emissione: {date_pretty sql=$doc.data_emissione|default:''}
    {if $doc.causale} · {$doc.causale}{/if}
</p>

<table class="parti">
    <tr>
        <td>
            <div class="ruolo">Emittente</div>
            <div class="den">{$doc.emittente_denominazione|default:''}</div>
            {if $doc.emittente_piva}<div>P. IVA {$doc.emittente_piva}</div>{/if}
            {if $doc.emittente_cf}<div>C.F. {$doc.emittente_cf}</div>{/if}
            {if $doc.emittente_indirizzo}<div>{$doc.emittente_indirizzo}</div>{/if}
        </td>
        <td>
            <div class="ruolo">Cliente</div>
            <div class="den">{$doc.cliente_denominazione|default:''}</div>
            {if $doc.cliente_piva}<div>P. IVA {$doc.cliente_piva}</div>{/if}
            {if $doc.cliente_cf}<div>C.F. {$doc.cliente_cf}</div>{/if}
            {if $doc.cliente_indirizzo}<div>{$doc.cliente_indirizzo}</div>{/if}
        </td>
    </tr>
</table>

<table class="righe">
    <thead>
        <tr>
            <th>Descrizione</th>
            <th class="num">Imponibile</th>
            <th class="num">IVA</th>
            <th class="num">Imposta</th>
        </tr>
    </thead>
    <tbody>
        {foreach $righe as $r}
        <tr>
            <td>{$r.descrizione|default:''}</td>
            <td class="num">{money value=$r.imponibile|default:0 currency=$val}</td>
            <td class="num">
                {if $r.aliquota_iva > 0}{$r.aliquota_iva|default:0}%{else}0% ({$r.natura_iva|default:'N/A'}){/if}
            </td>
            <td class="num">{money value=$r.imposta|default:0 currency=$val}</td>
        </tr>
        {/foreach}
    </tbody>
</table>

<table class="totali">
    <tr><td class="lbl">Totale imponibile</td><td class="val">{money value=$doc.totale_imponibile|default:0 currency=$val}</td></tr>
    <tr><td class="lbl">Totale IVA</td><td class="val">{money value=$doc.totale_iva|default:0 currency=$val}</td></tr>
    <tr class="grand"><td class="lbl">{if $isNota}Totale accreditato{else}Totale documento{/if}</td><td class="val">{money value=$doc.totale_documento|default:0 currency=$val}</td></tr>
</table>

<div class="note">
    {if $doc.bollo > 0}
        <p>Imposta di bollo da {money value=$doc.bollo currency=$val} assolta sull'originale.</p>
    {/if}
</div>

<p class="footer">
    Documento generato da {$app_name|default:'TrackPortal'}.
    Non costituisce fattura elettronica valida finché non trasmesso al Sistema di Interscambio (SdI).
</p>
</body>
</html>
