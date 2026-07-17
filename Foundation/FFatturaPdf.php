<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/** Genera PDF fattura dai dati della prenotazione (nessuna persistenza). */
class FFatturaPdf
{
    public static function genera(array $pren, string $vista): string
    {
        return self::pdfDaHtml(self::renderHtml($pren, $vista));
    }

    private static function pdfDaHtml(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Invia il PDF di un documento fiscale persistito (fattura o nota di credito).
     */
    public static function inviaDocumento(array $doc, array $righe): never
    {
        $num    = preg_replace('/[^A-Za-z0-9\-_]/', '', str_replace('/', '-', (string) ($doc['numero_formattato'] ?? 'documento')));
        $pdf    = self::pdfDaHtml(self::renderDocumento($doc, $righe));
        $prefix = (($doc['tipo'] ?? '') === 'nota_credito') ? 'nota-credito-' : 'fattura-';
        $nome   = $prefix . ($num !== '' ? $num : 'documento') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }