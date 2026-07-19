<?php

// dati layout comuni e rendering template Smarty
abstract class VBase
{
    // nome applicazione, costante APP_NAME da index.php
    protected static function appName(): string
    {
        $name = constant('APP_NAME');
        if (!is_string($name) || $name === '') {
            throw new RuntimeException('Costante APP_NAME non definita: avviare l\'app da index.php.');
        }

        return $name;
    }

    // variabili usate da partials/header.tpl e partials/footer.tpl
    protected static function layoutData(string $pageTitle, ?string $pageDescription = null): array
    {
        $user = Session::get('user');
        $flash = flash_all();

        $userInitials = '';
        if ($user) {
            $userInitials = strtoupper(
                mb_substr($user['nome'] ?: '?', 0, 1) .
                mb_substr($user['cognome'] ?? '', 0, 1)
            );
        }

        $cssPath   = TRACKPORTAL_BASE_DIR . '/public/css/style.css';
        $bulmaPath = TRACKPORTAL_BASE_DIR . '/public/css/bulma.min.css';
        $jsPath    = TRACKPORTAL_BASE_DIR . '/public/js/main.js';
        $logoPath  = logo_path();

        return [
            'app_name'          => self::appName(),
            'logo_url'          => logo_url(),
            'logo_version'      => is_file($logoPath) ? filemtime($logoPath) : time(),
            'page_title'        => $pageTitle,
            'page_description'  => $pageDescription
                ?: 'TrackPortal: prenota sessioni in pista, gestisci circuiti e flotte di noleggio.',
            'user'              => $user,
            'user_initials'     => $userInitials,
            'flash_messages'    => $flash,
            'bulma_css_version' => is_file($bulmaPath) ? filemtime($bulmaPath) : time(),
            'css_version'       => is_file($cssPath) ? filemtime($cssPath) : time(),
            'js_version'        => is_file($jsPath) ? filemtime($jsPath) : time(),
            'current_year'      => (int) date('Y'),
        ];
    }

    // renderizza un template Smarty sotto public/templates/
    protected static function render(string $templateRelativePath, string $pageTitle, ?string $pageDescription = null, array $vars = []): void
    {
        $smarty = TrackPortalSmarty::environment();
        if (!$smarty->templateExists($templateRelativePath)) {
            throw new RuntimeException('Template mancante: ' . $templateRelativePath);
        }

        $data = array_merge(self::layoutData($pageTitle, $pageDescription), $vars);
        $smarty->clearAllAssign();
        $smarty->assign($data);
        $smarty->display($templateRelativePath);
    }
}
