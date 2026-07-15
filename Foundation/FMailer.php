<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Invio email della piattaforma (PHPMailer).
 *
 * Il trasporto lo sceglie MAIL_TRANSPORT: 'smtp' invia davvero (costanti
 * MAIL_*), 'file' scrive l'email in var/mail/ — in sviluppo codici 2FA e link
 * si leggono da lì, senza server SMTP. Best-effort: niente eccezioni verso il
 * chiamante, un invio fallito viene loggato e restituisce false.
 */
class FMailer
{
    /**
     * Invia una email HTML, con eventuali allegati.
     *
     * @param list<array{data: string, name: string, mime?: string}> $allegati
     */
    public static function invia(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $allegati = []
    ): bool {
        if (!self::abilitato()) {
            return false;
        }

        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('FMailer: destinatario non valido (' . $toEmail . ')');
            return false;
        }

        try {
            return self::trasporto() === 'smtp'
                ? self::inviaSmtp($toEmail, $toName, $subject, $htmlBody, $allegati)
                : self::inviaFile($toEmail, $toName, $subject, $htmlBody, $allegati);
        } catch (Throwable $e) {
            error_log('FMailer: invio fallito a ' . $toEmail . ' — ' . $e->getMessage());
            return false;
        }
    }

    /** Renderizza un template email Smarty. */
    public static function render(string $template, array $vars): string
    {
        $smarty = TrackPortalSmarty::environment();
        if (!$smarty->templateExists($template)) {
            throw new RuntimeException('Template email mancante: ' . $template);
        }

        $smarty->clearAllAssign();
        $smarty->assign(array_merge([
            'app_name'     => defined('APP_NAME') ? constant('APP_NAME') : 'TrackPortal',
            'current_year' => (int) date('Y'),
        ], $vars));

        return $smarty->fetch($template);
    }

    // -----------------------------------------------------------------------

    /** @param list<array{data: string, name: string, mime?: string}> $allegati */
    private static function inviaSmtp(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $allegati
    ): bool {
        $mail = new PHPMailer(true);
        $mail->CharSet  = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_BASE64;

        $mail->isSMTP();
        $mail->Host = (string) self::cfg('MAIL_HOST', '');
        $mail->Port = (int) self::cfg('MAIL_PORT', 587);

        $user = (string) self::cfg('MAIL_USERNAME', '');
        if ($user !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = (string) self::cfg('MAIL_PASSWORD', '');
        }

        $enc = strtolower((string) self::cfg('MAIL_ENCRYPTION', 'tls'));
        if ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom(
            (string) self::cfg('MAIL_FROM_ADDRESS', 'no-reply@trackportal.test'),
            (string) self::cfg('MAIL_FROM_NAME', 'TrackPortal')
        );
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = self::testoSemplice($htmlBody);

        foreach ($allegati as $a) {
            if (!isset($a['data'], $a['name'])) {
                continue;
            }
            $mail->addStringAttachment(
                $a['data'],
                $a['name'],
                PHPMailer::ENCODING_BASE64,
                $a['mime'] ?? 'application/octet-stream'
            );
        }

        $mail->send();

        return true;
    }

    /**
     * Trasporto di sviluppo: scrive l'email in var/mail/; gli allegati vengono
     * salvati come file a fianco.
     *
     * @param list<array{data: string, name: string, mime?: string}> $allegati
     */
    private static function inviaFile(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array $allegati
    ): bool {
        $dir = self::spoolDir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossibile creare la directory mail: ' . $dir);
        }

        $prefix = (new DateTimeImmutable())->format('Ymd-His') . '-' . bin2hex(random_bytes(3));

        $allegatiNote = '';
        foreach ($allegati as $a) {
            if (!isset($a['data'], $a['name'])) {
                continue;
            }
            $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $a['name']) ?: 'allegato';
            $allegatoFile = $dir . '/' . $prefix . '__' . $safe;
            file_put_contents($allegatoFile, (string) $a['data']);
            $allegatiNote .= '  - ' . $a['name'] . ' (' . strlen((string) $a['data']) . ' byte) -> '
                . basename($allegatoFile) . "\n";
        }

        $intestazione = "From: " . self::cfg('MAIL_FROM_NAME', 'TrackPortal')
            . ' <' . self::cfg('MAIL_FROM_ADDRESS', 'no-reply@trackportal.test') . ">\n"
            . 'To: ' . ($toName !== '' ? $toName . ' ' : '') . '<' . $toEmail . ">\n"
            . 'Subject: ' . $subject . "\n"
            . 'Date: ' . date('r') . "\n"
            . 'Content-Type: text/html; charset=UTF-8' . "\n";
        if ($allegatiNote !== '') {
            $intestazione .= "Allegati:\n" . $allegatiNote;
        }

        $contenuto = $intestazione . "\n" . str_repeat('-', 70) . "\n" . $htmlBody;

        $ok = file_put_contents($dir . '/' . $prefix . '.eml', $contenuto) !== false;
        if (!$ok) {
            throw new RuntimeException('Scrittura email su file fallita.');
        }

        return true;
    }

    private static function abilitato(): bool
    {
        return (bool) self::cfg('MAIL_ENABLED', true);
    }

    private static function trasporto(): string
    {
        $t = strtolower((string) self::cfg('MAIL_TRANSPORT', 'file'));

        return $t === 'smtp' ? 'smtp' : 'file';
    }

    private static function spoolDir(): string
    {
        $base = defined('TRACKPORTAL_BASE_DIR') ? constant('TRACKPORTAL_BASE_DIR') : __DIR__ . '/..';

        return rtrim((string) $base, '/\\') . '/var/mail';
    }

    private static function testoSemplice(string $html): string
    {
        $txt = preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/tr)\s*\/?>/i', "\n", $html) ?? $html;

        return trim(html_entity_decode(strip_tags($txt), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function cfg(string $name, mixed $default): mixed
    {
        return defined($name) ? constant($name) : $default;
    }
}
