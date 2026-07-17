<?php

/** Gestione template HTML/Smarty per fatture PDF  */
class FTemplateFattura
{
    public const VISTA_PILOTA   = 'pilota';
    public const VISTA_GESTORE  = 'gestore';
    public const VISTA_NOLEGGIO = 'noleggio';
    public const VISTA_FATTURA  = 'fattura';

    private const MAX_BYTES = 524_288; // 512 KB

    
    private const ESTENSIONI_AMMESSE = ['html', 'htm', 'tpl'];

    
    private const MIME_AMMESSI = [
        'text/html',
        'text/plain',
        'application/octet-stream',
    ];

    
    private const DEFAULT_TPL = [
        self::VISTA_PILOTA   => 'fatture/pdf_pilota.tpl',
        self::VISTA_GESTORE  => 'fatture/pdf_gestore.tpl',
        self::VISTA_NOLEGGIO => 'fatture/pdf_noleggio.tpl',
        self::VISTA_FATTURA  => 'fatture/documento_fiscale.tpl',
    ];

    /**
     * Metadati delle viste, con etichetta e raggruppamento, nell'ordine di visualizzazione.
     */
    private const VISTE_META = [
        self::VISTA_PILOTA   => ['label' => 'Pilota',           'gruppo' => 'Ricevute'],
        self::VISTA_GESTORE  => ['label' => 'Gestore circuito', 'gruppo' => 'Ricevute'],
        self::VISTA_NOLEGGIO => ['label' => 'Noleggio',         'gruppo' => 'Ricevute'],
        self::VISTA_FATTURA  => ['label' => 'Fattura fiscale',  'gruppo' => 'Fatture'],
    ];

    private static function directoryCustom(): string
    {
        return TRACKPORTAL_BASE_DIR . '/public/templates/fatture/custom';
    }

    private static function directoryBackup(): string
    {
        return TRACKPORTAL_BASE_DIR . '/var/template_fattura/backup';
    }

    private static function pathManifest(): string
    {
        return TRACKPORTAL_BASE_DIR . '/var/template_fattura/manifest.json';
    }

    /**
     * Viste valide per la fatturazione.
     */
    private static function visteValide(): array
    {
        return [self::VISTA_PILOTA, self::VISTA_GESTORE, self::VISTA_NOLEGGIO, self::VISTA_FATTURA];
    }

    /**
     * Restituisce la descrizione delle viste, con etichetta e raggruppamento, nell'ordine di visualizzazione.
     */
    public static function descrizioneViste(): array
    {
        $out = [];
        foreach (self::VISTE_META as $key => $info) {
            $out[] = ['key' => $key, 'label' => $info['label'], 'gruppo' => $info['gruppo']];
        }

        return $out;
    }

    public static function etichettaVista(string $vista): string
    {
        $vista = self::normalizzaVista($vista);

        return self::VISTE_META[$vista]['label'] ?? ucfirst($vista);
    }

    /** Indica se la vista governa un documento fiscale (fattura/nota di credito). */
    public static function isVistaFattura(string $vista): bool
    {
        return self::normalizzaVista($vista) === self::VISTA_FATTURA;
    }

    public static function normalizzaVista(string $vista): string
    {
        $vista = strtolower(trim($vista));

        return in_array($vista, self::visteValide(), true) ? $vista : self::VISTA_PILOTA;
    }

    /**
     * Carica i metadati del template attivo per la vista indicata.
     */
    public static function caricaAttivo(string $vista = self::VISTA_PILOTA): array
    {
        $vista   = self::normalizzaVista($vista);
        $custom  = self::pathCustom($vista);
        $default = self::DEFAULT_TPL[$vista];
        $manifest = self::leggiManifest();

        if ($custom !== null && is_readable($custom)) {
            $meta = $manifest[$vista] ?? [];

            return [
                'vista'          => $vista,
                'origine'        => 'personalizzato',
                'smarty_path'    => 'fatture/custom/pdf_' . $vista . '.tpl',
                'path_assoluto'  => $custom,
                'nome_file'      => basename($custom),
                'dimensione'     => (int) filesize($custom),
                'modificato_il'  => date('Y-m-d H:i:s', (int) filemtime($custom)),
                'nome_originale' => (string) ($meta['nome_originale'] ?? basename($custom)),
                'aggiornato_da'  => isset($meta['aggiornato_da']) ? (int) $meta['aggiornato_da'] : null,
                'default_path'   => $default,
            ];
        }

        $defaultAbs = TRACKPORTAL_BASE_DIR . '/public/templates/' . $default;

        return [
            'vista'          => $vista,
            'origine'        => 'predefinito',
            'smarty_path'    => $default,
            'path_assoluto'  => $defaultAbs,
            'nome_file'      => basename($default),
            'dimensione'     => is_readable($defaultAbs) ? (int) filesize($defaultAbs) : 0,
            'modificato_il'  => is_readable($defaultAbs)
                ? date('Y-m-d H:i:s', (int) filemtime($defaultAbs))
                : null,
            'nome_originale' => basename($default),
            'aggiornato_da'  => null,
            'default_path'   => $default,
        ];
    }

    /**
     * Restituisce il path Smarty del template attivo per la vista indicata.
     */
    public static function templateSmarty(string $vista): string
    {
        return (string) self::caricaAttivo($vista)['smarty_path'];
    }

    /**
     * Restituisce l'anteprima HTML del template attivo per la vista indicata, insieme ai dati di esempio.
     */
    public static function anteprimaRender(string $vista): array
    {
        $vista = self::normalizzaVista($vista);
        $tpl   = self::templateSmarty($vista);

        $smarty = TrackPortalSmarty::environment();
        if (!$smarty->templateExists($tpl)) {
            throw new RuntimeException('Template non trovato: ' . $tpl);
        }

        $comune = [
            'app_name'      => defined('APP_NAME') ? APP_NAME : 'TrackPortal',
            'logo_data_uri' => logo_data_uri(),
        ];

        $smarty->clearAllAssign();

        if (self::isVistaFattura($vista)) {
            $dati = self::datiEsempioDocumento();
            $smarty->assign($comune + [
                'doc'   => $dati['doc'],
                'righe' => $dati['righe'],
            ]);
        } else {
            $dati = self::datiEsempio($vista);
            $smarty->assign($comune + ['pren' => $dati]);
        }

        return [
            'html' => $smarty->fetch($tpl),
            'dati' => $dati,
        ];
    }

    /**
     * Salva il file caricato come template personalizzato per la vista indicata, aggiornando il manifest.
     */
    public static function salvaUpload(string $vista, array $file, int $adminId): array
    {
        $vista = self::normalizzaVista($vista);
        self::validaUpload($file);

        $tmp  = (string) $file['tmp_name'];
        $body = (string) file_get_contents($tmp);
        self::validaContenuto($body);

        self::ensureDirectories();

        $dest = self::pathCustom($vista, true);
        $prev = self::pathCustom($vista);
        if ($prev !== null && is_readable($prev)) {
            $backup = self::directoryBackup() . '/pdf_' . $vista . '_'
                . date('Ymd_His') . '.tpl';
            if (!@copy($prev, $backup)) {
                throw new RuntimeException('Impossibile creare il backup del template precedente.');
            }
        }

        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                throw new RuntimeException(
                    'Impossibile salvare il file: verifica i permessi di scrittura sulla cartella template.'
                );
            }
            @unlink($tmp);
        }

        @chmod($dest, 0644);

        $manifest = self::leggiManifest();
        $manifest[$vista] = [
            'nome_originale' => (string) $file['name'],
            'aggiornato_il'  => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'aggiornato_da'  => $adminId,
            'dimensione'     => (int) filesize($dest),
        ];
        self::scriviManifest($manifest);

        return self::caricaAttivo($vista);
    }