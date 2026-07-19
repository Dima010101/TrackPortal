<?php

// gestione dei file PDF dei documenti del pilota, certificato medico e licenza
class FDocumentoPilota
{
    // dimensione massima per singolo documento, 10 MB
    private const MAX_BYTES = 10_485_760;

    // tipi di documento gestiti
    public const TIPI = ['certificato_medico', 'licenza'];

    // cartella radice degli upload documenti, assoluta su disco
    private static function directoryBase(): string
    {
        return TRACKPORTAL_BASE_DIR . '/uploads/piloti';
    }

    private static function directoryPilota(int $pilotaId): string
    {
        return self::directoryBase() . '/' . $pilotaId;
    }

    private static function webPath(int $pilotaId, string $fileName): string
    {
        return '/uploads/piloti/' . $pilotaId . '/' . $fileName;
    }

    // true se il file rappresenta un input vuoto, nessun file selezionato
    public static function fileVuoto(array $file): bool
    {
        return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
    }

    // valida un singolo upload PDF, lancia eccezione in caso di errore
    public static function validaUpload(array $file): void
    {
        if (!isset($file['error'])) {
            throw new InvalidArgumentException('Upload documento non valido.');
        }

        $err = (int) $file['error'];
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('Il documento supera la dimensione massima consentita (10 MB).');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore durante il caricamento del documento (codice ' . $err . ').');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Upload non valido o sessione scaduta. Riprova.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Dimensione documento non valida (max 10 MB).');
        }

        $nome = (string) ($file['name'] ?? '');
        $ext  = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new InvalidArgumentException('Formato non consentito: è ammesso solo un file PDF.');
        }

        // verifica del MIME reale del contenuto, non ci si fida dell'estensione
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        if ($mime !== 'application/pdf') {
            throw new InvalidArgumentException(
                'Il file selezionato non è un PDF valido (tipo rilevato: ' . ($mime ?: 'sconosciuto') . ').'
            );
        }
    }

    // valida e salva un documento PDF per il pilota, ritorna il path pubblico
    public static function salva(int $pilotaId, string $tipo, array $file): string
    {
        if (!in_array($tipo, self::TIPI, true)) {
            throw new InvalidArgumentException('Tipo documento non valido.');
        }
        self::validaUpload($file);

        $dir = self::directoryPilota($pilotaId);
        self::ensureDir($dir);

        $fileName = $tipo . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $dest     = $dir . '/' . $fileName;
        $tmp      = (string) $file['tmp_name'];

        if (!@move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                throw new RuntimeException(
                    'Impossibile salvare il documento: verifica i permessi di scrittura sulla cartella uploads.'
                );
            }
            @unlink($tmp);
        }
        @chmod($dest, 0644);

        return self::webPath($pilotaId, $fileName);
    }

    // risolve il path reale di un documento solo se è nella cartella di quel pilota
    private static function percorsoRealeProtetto(int $pilotaId, ?string $webPath): ?string
    {
        if ($webPath === null || trim($webPath) === '') {
            return null;
        }
        if (!str_starts_with($webPath, '/uploads/piloti/')) {
            return null;
        }

        $real = realpath(TRACKPORTAL_BASE_DIR . $webPath);
        $base = realpath(self::directoryPilota($pilotaId));
        if ($real === false || $base === false
            || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($real) ? $real : null;
    }

    // invia al client un documento PDF del pilota, previa risoluzione protetta del percorso
    public static function invia(int $pilotaId, string $tipo, ?string $webPath): bool
    {
        $reale = self::percorsoRealeProtetto($pilotaId, $webPath);
        if ($reale === null) {
            return false;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $tipo . '-' . $pilotaId . '.pdf"');
        header('Content-Length: ' . (string) filesize($reale));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($reale);

        return true;
    }

    // elimina un documento dal filesystem, solo se contenuto in uploads/piloti
    public static function elimina(?string $webPath): void
    {
        if ($webPath === null || trim($webPath) === '') {
            return;
        }
        if (!str_starts_with($webPath, '/uploads/piloti/')) {
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

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Impossibile creare la cartella upload: ' . $dir);
        }
    }
}
