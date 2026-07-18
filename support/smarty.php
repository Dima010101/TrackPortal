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


        // Prezzo convertibile lato client (selettore valuta delle prenotazioni).
        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'prezzo', static function (array $params): string {
            return prezzo_convertibile(
                (float) ($params['value'] ?? 0),
                (string) ($params['currency'] ?? 'EUR')
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'valuta_switcher', static function (array $params): string {
            return valuta_switcher((string) ($params['class'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'stato_label', static function (array $params): string {
            return stato_label((string) ($params['stato'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'date_pretty', static function (array $params): string {
            return date_pretty((string) ($params['sql'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'date_short', static function (array $params): string {
            return date_short((string) ($params['sql'] ?? ''));
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'tp_time_hm', static function (array $params): string {
            return tp_time_hm(isset($params['t']) ? (string) $params['t'] : null);
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'combo_chart', static function (array $params): string {
            $serie = $params['data'] ?? [];
            if (!is_array($serie)) {
                $serie = [];
            }

            return combo_chart($serie, [
                'bar_label'     => (string) ($params['bar_label'] ?? 'Prenotazioni'),
                'line_label'    => (string) ($params['line_label'] ?? 'Ricavi'),
                'line_currency' => isset($params['line_currency']) ? (string) $params['line_currency'] : null,
                'empty_text'    => (string) ($params['empty_text'] ?? 'Nessun dato disponibile per il periodo.'),
                'bar_value_pos' => (string) ($params['bar_value_pos'] ?? 'above'),
                'axis_titles'   => (bool) ($params['axis_titles'] ?? true),
                'bar_values'    => (bool) ($params['bar_values'] ?? true),
            ]);
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'donut_chart', static function (array $params): string {
            $data = $params['data'] ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            return donut_chart($data, [
                'center_label' => (string) ($params['center_label'] ?? ''),
                'empty_text'   => (string) ($params['empty_text'] ?? 'Nessun dato disponibile.'),
                'unit'         => (string) ($params['unit'] ?? ''),
            ]);
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'back_button', static function (array $params): string {
            return back_button(
                (string) ($params['href'] ?? '/'),
                (string) ($params['label'] ?? 'Indietro')
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'prenotazione_lista_url', static function (array $params): string {
            return prenotazione_lista_url(
                (string) ($params['stato'] ?? 'attive'),
                (string) ($params['q'] ?? '')
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_FUNCTION, 'flotta_qs', static function (array $params): string {
            return flotta_qs(
                (int) ($params['circuito_id'] ?? 0),
                (string) ($params['cat'] ?? '')
            );
        });

        $smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, 'tp_nl2br', static function (?string $s): string {
            if ($s === null || $s === '') {
                return '';
            }

            return nl2br(e($s), false);
        });

        $smarty->registerPlugin(Smarty::PLUGIN_MODIFIER, 'trim', static function (?string $s): string {
            return trim((string) $s);
        });
    }
}