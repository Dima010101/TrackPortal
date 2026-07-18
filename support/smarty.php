<?php

declare(strict_types=1);

use Smarty\Smarty;

/**
 * Singleton Smarty + plugin che espongono gli helper procedurali nei template (.tpl).
 */
final class TrackPortalSmarty
{
    private static ?Smarty $engine = null;

    public static function environment(): Smarty
    {
        if (self::$engine instanceof Smarty) {
            return self::$engine;
        }

        $compileDir = TRACKPORTAL_BASE_DIR . '/var/smarty/compile';
        $cacheDir = TRACKPORTAL_BASE_DIR . '/var/smarty/cache';
        foreach ([$compileDir, $cacheDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Impossibile creare la directory Smarty: ' . $dir);
            }
        }

        $smarty = new Smarty();
        $smarty->setTemplateDir(TRACKPORTAL_BASE_DIR . '/public/templates');
        $smarty->setCompileDir($compileDir);
        $smarty->setCacheDir($cacheDir);
        $smarty->caching = Smarty::CACHING_OFF;
        $smarty->escape_html = true;

        self::registerPlugins($smarty);
        self::$engine = $smarty;

        return self::$engine;
    }

    private static function registerPlugins(Smarty $smarty): void
    {
        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'url', static function (array $params): string {
            return url((string) ($params['path'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'logo', static function (array $params): string {
            return logo_img(
                (string) ($params['class'] ?? 'tp-logo'),
                (string) ($params['alt'] ?? '')
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'icon', static function (array $params): string {
            return icon(
                (string) ($params['name'] ?? ''),
                (string) ($params['class'] ?? ''),
                (int) ($params['size'] ?? 16)
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'nav_active', static function (array $params): string {
            return nav_active((string) ($params['path'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'csrf_field', static function (): string {
            return csrf_field();
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'ruolo_label', static function (array $params): string {
            return ruolo_label((string) ($params['ruolo'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'flash_icon', static function (array $params): string {
            return flash_icon((string) ($params['type'] ?? 'info'));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'tp_ucfirst', static function (array $params): string {
            return tp_ucfirst(isset($params['s']) ? (string) $params['s'] : null);
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'money', static function (array $params): string {
            return money(
                (float) ($params['value'] ?? 0),
                (string) ($params['currency'] ?? 'EUR')
            );
        });