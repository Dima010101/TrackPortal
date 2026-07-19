{extends file='email/base.tpl'}

{block name=content}
  {if !empty($titolo)}
    <h1 style="margin:0 0 16px 0; font-size:22px; line-height:1.3; color:#1f2733; font-weight:700;">{$titolo}</h1>
  {/if}

  {if !empty($saluto)}
    <p style="margin:0 0 14px 0; font-size:15px; line-height:1.6; color:#1f2733;">{$saluto}</p>
  {/if}

  {if !empty($paragrafi)}
    {foreach $paragrafi as $p}
      <p style="margin:0 0 14px 0; font-size:15px; line-height:1.6; color:#3a424d;">{$p}</p>
    {/foreach}
  {/if}

  {if !empty($codice)}
    <div style="margin:20px 0; padding:18px; background-color:#fff5ec; border:1px solid #f6caa0; border-radius:10px; text-align:center;">
      <div style="font-size:13px; color:#8a5a2b; margin-bottom:6px;">Il tuo codice di verifica</div>
      <div style="font-size:32px; font-weight:700; letter-spacing:8px; color:#c2570b; font-family:'Courier New',Courier,monospace;">{$codice}</div>
    </div>
  {/if}

  {if !empty($dettagli)}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0; border-collapse:collapse; background-color:#f8f9fa; border-radius:10px; overflow:hidden;">
      {foreach $dettagli as $label => $valore}
        <tr>
          <td style="padding:10px 14px; font-size:13px; color:#6b7280; border-bottom:1px solid #eceef1; width:42%; vertical-align:top;">{$label}</td>
          <td style="padding:10px 14px; font-size:14px; color:#1f2733; border-bottom:1px solid #eceef1; font-weight:600;">{$valore}</td>
        </tr>
      {/foreach}
    </table>
  {/if}

  {if !empty($bottone) && !empty($bottone.url)}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0;">
      <tr>
        <td style="border-radius:8px; background-color:#ef6c12;">
          <a href="{$bottone.url}" target="_blank" style="display:inline-block; padding:13px 26px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:8px;">{$bottone.label}</a>
        </td>
      </tr>
    </table>
    <p style="margin:0 0 14px 0; font-size:12px; line-height:1.6; color:#8a9099; word-break:break-all;">
      Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:<br>{$bottone.url}
    </p>
  {/if}

  {if !empty($nota)}
    <p style="margin:18px 0 0 0; font-size:13px; line-height:1.6; color:#8a9099;">{$nota}</p>
  {/if}
{/block}
