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

    /**
     * Valida il file caricato per il template, lanciando eccezioni in caso di problemi.
     */
    public static function validaUpload(array $file): void
    {
        if ($file === [] || !isset($file['error'])) {
            throw new InvalidArgumentException('Nessun file caricato.');
        }

        $err = (int) $file['error'];
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Seleziona un file template da caricare.');
        }
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('Il file supera la dimensione massima consentita (512 KB).');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore durante l\'upload del file (codice ' . $err . ').');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Upload non valido o sessione scaduta. Riprova.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Dimensione file non valida (max 512 KB).');
        }

        $nome = (string) ($file['name'] ?? '');
        $ext  = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ESTENSIONI_AMMESSE, true)) {
            throw new InvalidArgumentException(
                'Formato non consentito. Sono ammessi solo file .html, .htm o .tpl.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        if ($mime !== '' && !in_array($mime, self::MIME_AMMESSI, true)) {
            throw new InvalidArgumentException(
                'Tipo MIME non consentito (' . $mime . '). Carica un documento HTML o template testuale.'
            );
        }
    }

    public static function validaContenuto(string $body): void
    {
        if (trim($body) === '') {
            throw new InvalidArgumentException('Il file template è vuoto.');
        }

        $lower = strtolower($body);
        $vietati = ['<?php', '<?=', '<script', 'javascript:', 'eval(', 'base64_decode('];
        foreach ($vietati as $token) {
            if (str_contains($lower, $token)) {
                throw new InvalidArgumentException(
                    'Il contenuto del template contiene elementi non consentiti per motivi di sicurezza.'
                );
            }
        }
    }

    private static function pathCustom(string $vista, bool $ensureDir = false): ?string
    {
        $vista = self::normalizzaVista($vista);
        if ($ensureDir) {
            self::ensureDirectories();
        }

        $path = self::directoryCustom() . '/pdf_' . $vista . '.tpl';

        return is_readable($path) || $ensureDir ? $path : null;
    }

    private static function ensureDirectories(): void
    {
        foreach ([
            dirname(self::pathManifest()),
            self::directoryCustom(),
            self::directoryBackup(),
        ] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Impossibile creare la directory: ' . $dir);
            }
        }
    }

    /**
     * Legge il manifest dei template personalizzati, restituendo un array associativo.
     */
    private static function leggiManifest(): array
    {
        $path = self::pathManifest();
        if (!is_readable($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : [];
    }

    /**
     * Scrive il manifest dei template personalizzati, sovrascrivendo il file esistente.
     */
    private static function scriviManifest(array $data): void
    {
        self::ensureDirectories();
        $path = self::pathManifest();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($path, $json . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Impossibile aggiornare il manifest dei template.');
        }
    }

    /**
     * Dati di esempio per l'anteprima del template, a seconda della vista.
     */
    private static function datiEsempio(string $vista): array
    {
        $base = [
            'id'                    => 0,
            'codice_identificativo' => 'TP-DEMO0001',
            'prezzo_importo'        => 189.50,
            'imponibile_accesso'    => 139.34,
            'imponibile_noleggio'   => 25.17,
            'prezzo_valuta'         => 'EUR',
            'stato'                 => 'confermata',
            'data_inserimento'      => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'inizio_sessione'  => (new DateTimeImmutable('+3 days'))->format('Y-m-d 09:00:00'),
            'fine_sessione'    => (new DateTimeImmutable('+3 days'))->format('Y-m-d 12:00:00'),
            'assicurazione'         => true,
            'nome_circuito'         => 'Autodromo Demo',
            'circuito_id'           => 1,
            'sconto_importo'        => 15.00,
            'promozione_titolo'     => 'Promo benvenuto',
            'promozione_descrizione'=> 'Sconto dimostrativo per anteprima template.',
            'targa_veicolo'         => 'AB123CD',
            'veicolo_noleggio_id'   => null,
            'v_marca'               => 'Porsche',
            'v_modello'             => '911 GT3',
            'v_targa'               => 'GT3-DEMO',
            'v_categoria'           => 'auto',
            'v_anno'                => 2022,
            'v_potenza_cv'          => 510,
            'v_capienza'            => 2,
            'pilota_nome'           => 'Mario',
            'pilota_cognome'        => 'Verdi',
        ];

        if ($vista === self::VISTA_NOLEGGIO) {
            $base['veicolo_noleggio_id'] = 1;
            $base['targa_veicolo']       = null;
        }

        return $base;
    }

    /**
     * Dati di esempio per l'anteprima del template di documento fiscale (fattura), con righe e totali.
     */
    private static function datiEsempioDocumento(): array
    {
        $doc = [
            'tipo'                    => 'fattura',
            'numero_formattato'       => '2026/0001',
            'data_emissione'          => (new DateTimeImmutable())->format('Y-m-d'),
            'causale'                 => 'Sessione in pista — documento dimostrativo',
            'valuta'                  => 'EUR',
            'emittente_denominazione' => 'TrackPortal S.r.l.',
            'emittente_piva'          => '01234567890',
            'emittente_cf'            => '01234567890',
            'emittente_indirizzo'     => 'Via dei Circuiti 1, 20100 Milano (MI)',
            'cliente_denominazione'   => 'Mario Verdi',
            'cliente_piva'            => '',
            'cliente_cf'              => 'VRDMRA85M01F205X',
            'cliente_indirizzo'       => 'Via Roma 10, 00100 Roma (RM)',
            'totale_imponibile'       => 155.33,
            'totale_iva'              => 34.17,
            'totale_documento'        => 189.50,
            'bollo'                   => 0,
        ];

        $righe = [
            [
                'descrizione'  => 'Sessione in pista — Autodromo Demo',
                'imponibile'   => 139.34,
                'aliquota_iva' => 22,
                'natura_iva'   => '',
                'imposta'      => 30.66,
            ],
            [
                'descrizione'  => 'Assicurazione giornaliera',
                'imponibile'   => 15.99,
                'aliquota_iva' => 22,
                'natura_iva'   => '',
                'imposta'      => 3.51,
            ],
        ];

        return ['doc' => $doc, 'righe' => $righe];
    }
}