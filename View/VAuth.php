<?php

// view per login e registrazione
class VAuth extends VBase
{
    public static function login(): void
    {
        self::render('autenticazione/accesso.tpl', 'Accedi');
    }

    // form per il codice OTP del secondo fattore
    public static function verifica2FA(string $email, array $errors = []): void
    {
        self::render('autenticazione/verifica_2fa.tpl', 'Verifica accesso', null, [
            'email_mascherata' => self::mascheraEmail($email),
            'errors'           => $errors,
        ]);
    }

    // pagina mostrata al login quando l'email non è ancora confermata
    public static function verificaEmailRichiesta(string $email): void
    {
        self::render('autenticazione/verifica_email.tpl', 'Conferma email', null, [
            'email' => $email,
        ]);
    }

    // oscura parzialmente un indirizzo email
    private static function mascheraEmail(string $email): string
    {
        $at = strpos($email, '@');
        if ($at === false || $at < 1) {
            return $email;
        }
        $local   = substr($email, 0, $at);
        $dominio = substr($email, $at);
        $visibili = min(2, max(1, (int) floor(mb_strlen($local) / 2)));

        return mb_substr($local, 0, $visibili) . str_repeat('*', max(1, mb_strlen($local) - $visibili)) . $dominio;
    }

    public static function registrazione(array $errors = [], array $old = []): void
    {
        self::render('autenticazione/registrazione.tpl', 'Registrati', null, [
            'errors' => $errors,
            'old'    => $old,
        ]);
    }
}
