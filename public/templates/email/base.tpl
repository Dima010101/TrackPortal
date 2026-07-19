<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$app_name}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f3f5; color:#1f2733; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; -webkit-font-smoothing:antialiased;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f3f5; padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(16,24,40,0.08);">
          <!-- Header -->
          <tr>
            <td style="background-color:#ef6c12; padding:22px 28px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:22px; font-weight:800; color:#ffffff; letter-spacing:0.5px;">
                    {$app_name}
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:32px 28px 8px 28px;">
              {block name=content}{/block}
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding:24px 28px 28px 28px;">
              <hr style="border:none; border-top:1px solid #e6e8eb; margin:0 0 16px 0;">
              <p style="margin:0; font-size:12px; line-height:1.6; color:#8a9099;">
                Email automatica inviata da {$app_name}. Per favore non rispondere a questo messaggio.<br>
                &copy; {$current_year} {$app_name}.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
