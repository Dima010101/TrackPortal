<?php

/**
 * Gestione dei file fisici delle foto legate ai circuiti (upload, validazione, rimozione).
 *
 * I metadati restano nell'aggregato ECircuito gestito da doctrine.
 * Questa classe si occupa solo del filesystem sotto
 * `uploads/circuiti/{id}/`
 */
class FCircuitoFoto
{
    /** Dimensione massima per singola immagine: 5 MB */
    private const MAX_BYTES = 5_242_880;

    /** Numero massimo di foto per circuito */
    public const MAX_FOTO = 12;

    /**lista estensioni ammesse per le foto */
    private const ESTENSIONI_AMMESSE = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** MIME type ammesso associato all'estensione */
    private const MIME_AMMESSI = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /** definizione cartella radice degli upload foto */
    private static function directoryBase(): string
    {
        return TRACKPORTAL_BASE_DIR . '/uploads/circuiti';
    }

    /** definizione cartella su disco per le foto di un singolo circuito */
    private static function directoryCircuito(int $circuitoId): string
    {
        return self::directoryBase() . '/' . $circuitoId;
    }

    /** definizione percorso pubblico di un file foto. */
    private static function webPath(int $circuitoId, string $fileName): string
    {
        return '/uploads/circuiti/' . $circuitoId . '/' . $fileName;
    }

    /**
     * Normalizza la struttura multi-file di PHP ($_FILES['campo'] con name="campo[]") in una lista di array per singolo file */
    public static function normalizzaFiles(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            // se c'è un file singolo: lo incapsulo comunque come lista.
            return isset($files['name']) ? [$files] : [];
        }

        $out   = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name'     => $files['name'][$i] ?? '',
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i] ?? 0,
            ];
        }

        return $out;
    }

    /** ritorna True se il file rappresenta un input vuoto (nessun file selezionato) */
    public static function fileVuoto(array $file): bool
    {
        return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
    }

    /** Valida un singolo upload immagine; lancia eccezione in caso di errore.
     * Verifica: dimensione, estensione, MIME type reale, decodifica immagine
    */
    public static function validaUpload(array $file): string
    {
        if (!isset($file['error'])) {
            throw new InvalidArgumentException('Upload immagine non valido.');
        }

        $err = (int) $file['error'];
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('L\'immagine supera la dimensione massima consentita (5 MB).');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore durante il caricamento dell\'immagine (codice ' . $err . ').');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Upload non valido o sessione scaduta. Riprova.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Dimensione immagine non valida (max 5 MB).');
        }

        $nome = (string) ($file['name'] ?? '');
        $ext  = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ESTENSIONI_AMMESSE, true)) {
            throw new InvalidArgumentException(
                'Formato non consentito. Sono ammesse solo immagini JPG, PNG, GIF o WEBP.'
            );
        }

        // Verifica del MIME reale del contenuto
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        if (!isset(self::MIME_AMMESSI[$mime])) {
            throw new InvalidArgumentException(
                'Il file selezionato non è un\'immagine valida (tipo rilevato: ' . ($mime ?: 'sconosciuto') . ').'
            );
        }

        // Conferma che sia decodificabile come immagine
        if (@getimagesize($tmp) === false) {
            throw new InvalidArgumentException('Il file selezionato non è un\'immagine leggibile.');
        }

        return self::MIME_AMMESSI[$mime];
    }

    /**Valida e salva un'immagine per il circuito; ritorna il path pubblico*/
    public static function salva(int $circuitoId, array $file): string
    {
        $ext = self::validaUpload($file);

        $dir = self::directoryCircuito($circuitoId);
        self::ensureDir($dir);

        $fileName = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = $dir . '/' . $fileName;
        $tmp      = (string) $file['tmp_name'];

        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                throw new RuntimeException(
                    'Impossibile salvare l\'immagine: verifica i permessi di scrittura sulla cartella uploads.'
                );
            }
            @unlink($tmp);
        }
        @chmod($dest, 0644);

        return self::webPath($circuitoId, $fileName);
    }

    /** Elimina dal filesystem il file associato a un path pubblico, solo se contenuto nella cartella uploads/circuiti*/
    public static function elimina(?string $webPath): void
    {
        if ($webPath === null || trim($webPath) === '') {
            return;
        }

        // Solo i file gestiti dall'app hanno questo prefisso.
        if (!str_starts_with($webPath, '/uploads/circuiti/')) {
            return;
        }

        $assoluto = TRACKPORTAL_BASE_DIR . $webPath;
        $real     = realpath($assoluto);
        $base     = realpath(self::directoryBase());
        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            return;
        }

        @unlink($real);
    }

    /**verifica se esiste la cartella per il circuito e la crea se non esiste, altrimenti lancia eccezione */
    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossibile creare la cartella upload: ' . $dir);
        }
    }
}
