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