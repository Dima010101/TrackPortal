<?php

/*
 * Controller per la gestione della commissione e dell'aliquota IVA dell'assicurazione.
 */
class CGestioneAssicurazioneCommissioneIva
{
    /**
     * Azione di default (GET) se non specificata nella richiesta.
     */
    public const DEFAULT_ACTION = 'gestisciCommissioni';

    /**
     * Mappa delle rotte (metodo HTTP + azione) verso i metodi del controller.
     */
    public const ROUTES = [
        'POST anteprima' => 'anteprimaModifica',
        'POST riepilogo' => 'riepilogaModifica',
        'POST conferma' => 'confermaModifica',
    ];

    private const SESSION_KEY = 'gestione_commissioni_pending';

    /**
     * GET — visualizza la dashboard con i valori attuali .
     */
    public function gestisciCommissioni(): void
    {
        self::richiediAdmin();

        [$prezzo, $perc, $aliquota] = self::valoriAttuali();
        self::renderDashboard($prezzo, $perc, $aliquota, false, []);
    }

    /**
     * POST — visualizza l'anteprima della modifica .
     */
    public function anteprimaModifica(float|int|string|null $nuovoPrezzo = null): void
    {
        self::richiediAdmin();

        [$prezzo, $perc, $aliquota] = self::valoriAttuali();
        $errors = self::erroriRichiestaPost('usa il form di modifica per visualizzare l\'anteprima');
        if ($errors !== []) {
            self::renderDashboard($prezzo, $perc, $aliquota, false, $errors, self::valoriFormDaPost());
            return;
        }

        try {
            $proposti = self::parseProposti(
                self::leggiPrezzoInput($nuovoPrezzo),
                (string) post('percentuale_commissione', (string) $perc),
                (string) post('aliquota_iva', (string) $aliquota)
            );
        } catch (InvalidArgumentException $e) {
            self::renderDashboard($prezzo, $perc, $aliquota, false, [$e->getMessage()], self::valoriFormDaPost());
            return;
        }

        self::renderDashboard($proposti[0], $proposti[1], $proposti[2], true, [], self::formDaValori($proposti));
    }