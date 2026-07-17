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

     /**
     * Renderizza il template Smarty del documento fiscale persistito (fattura o nota di credito) in HTML.
     */
    private static function renderDocumento(array $doc, array $righe): string
    {
        $tpl    = FTemplateFattura::templateSmarty(FTemplateFattura::VISTA_FATTURA);
        $smarty = TrackPortalSmarty::environment();
        if (!$smarty->templateExists($tpl)) {
            throw new RuntimeException('Template documento mancante: ' . $tpl);
        }

        $smarty->clearAllAssign();
        $smarty->assign([
            'doc'           => $doc,
            'righe'         => $righe,
            'app_name'      => defined('APP_NAME') ? APP_NAME : 'TrackPortal',
            'logo_data_uri' => logo_data_uri(),
        ]);

        return $smarty->fetch($tpl);
    }

    public static function invia(array $pren, string $vista): never
    {
        $codice = preg_replace('/[^A-Za-z0-9\-_]/', '', (string)($pren['codice_identificativo'] ?? 'documento'));
        $pdf    = self::genera($pren, $vista);
        $nome   = 'ricevuta-' . ($codice !== '' ? $codice : 'documento') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    private static function renderHtml(array $pren, string $vista): string
    {
        $tplFile = FTemplateFattura::templateSmarty($vista);

        $smarty = TrackPortalSmarty::environment();
        if (!$smarty->templateExists($tplFile)) {
            throw new RuntimeException('Template PDF mancante: ' . $tplFile);
        }

        $smarty->clearAllAssign();
        $smarty->assign([
            'pren'          => $pren,
            'app_name'      => defined('APP_NAME') ? APP_NAME : 'TrackPortal',
            'logo_data_uri' => logo_data_uri(),
        ]);

        return $smarty->fetch($tplFile);
    }
}