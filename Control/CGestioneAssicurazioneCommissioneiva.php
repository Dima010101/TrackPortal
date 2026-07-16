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

    /**
     * POST — visualizza il riepilogo della modifica e richiede conferma prima del salvataggio definitivo.
     */
    public function riepilogaModifica(array $datiModifica = []): void
    {
        self::richiediAdmin();

        $datiModifica = $datiModifica !== [] ? $datiModifica : self::datiDaPost();
        $attuali      = self::valoriAttuali();
        $nuovi        = $attuali;

        $errors = self::erroriRichiestaPost(
            'invia il form per procedere al salvataggio',
            isset($datiModifica['csrf_token']) ? (string) $datiModifica['csrf_token'] : null
        );
        if ($errors === []) {
            try {
                $nuovi = self::parseProposti(
                    (string) ($datiModifica['prezzo_assicurazione'] ?? ''),
                    (string) ($datiModifica['percentuale_commissione'] ?? ''),
                    (string) ($datiModifica['aliquota_iva'] ?? '')
                );
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        self::concludiRiepilogo($attuali, $nuovi, $errors, $datiModifica);
    }

    /**
     * POST — conferma le modifiche e salva i nuovi valori in piattaforma.
     */
    public function confermaModifica(): void
    {
        self::richiediAdmin();

        $errors  = self::erroriRichiestaPost('conferma le modifiche dal riepilogo');
        $pending = $_SESSION[self::SESSION_KEY] ?? null;
        if ($errors === [] && !self::pendingValido($pending)) {
            $errors[] = 'Nessuna modifica in sospeso da confermare. Ripeti la procedura di aggiornamento.';
        }

        if ($errors !== []) {
            VAmministratore::commissioniConferma(false, $errors);
            return;
        }

        self::salvaPending($pending);
    }

    private static function richiediAdmin(): void
    {
        CAuth::richiediRuolo(EAmministratore::$ruolo);
    }

    /**
     * Restituisce i valori attuali della piattaforma (prezzo assicurazione,
     * percentuale commissione, aliquota IVA).
     */
    private static function valoriAttuali(): array
    {
        return [
            FPersistentManager::configurazionePiattaformaPrezzoAssicurazione(),
            FPersistentManager::configurazionePiattaformaPercentualeCommissione(),
            FPersistentManager::configurazionePiattaformaAliquotaIva(),
        ];
    }

    /**
     * Controlla che la richiesta sia POST e che il token CSRF sia valido.
     */
    private static function erroriRichiestaPost(string $istruzione, ?string $token = null): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['Richiesta non valida: ' . $istruzione . '.'];
        }
        if (!csrf_check($token ?? post('csrf_token'))) {
            return ['Token CSRF non valido. Ricarica la pagina e riprova.'];
        }

        return [];
    }

    /** Parsing dei tre valori proposti (prezzo, percentuale, aliquota). */
    private static function parseProposti(string $prezzo, string $percentuale, string $aliquota): array
    {
        return [
            self::parseValore(
                $prezzo,
                'Il prezzo assicurazione non è valido: inserisci un importo numerico (es. 12,50).',
                'Il prezzo assicurazione deve essere maggiore o uguale a zero.',
                null
            ),
            self::parseValore(
                $percentuale,
                'La percentuale commissione non è valida: inserisci un valore numerico (es. 5,00).',
                'La percentuale commissione deve essere compresa tra 0 e 100.',
                100.0
            ),
            self::parseValore(
                $aliquota,
                'L\'aliquota IVA non è valida: inserisci un valore numerico (es. 22,00).',
                'L\'aliquota IVA deve essere compresa tra 0 e 100.',
                100.0
            ),
        ];
    }

    /** Parsing comune dei tre valori economici (virgola ammessa, range 0..$max). */
    private static function parseValore(string $raw, string $msgNonValido, string $msgIntervallo, ?float $max): float
    {
        $normalizzato = trim(str_replace(',', '.', $raw));
        if ($normalizzato === '' || !is_numeric($normalizzato)) {
            throw new InvalidArgumentException($msgNonValido);
        }

        $valore = (float) $normalizzato;
        if ($valore < 0 || ($max !== null && $valore > $max)) {
            throw new InvalidArgumentException($msgIntervallo);
        }

        return round($valore, 2);
    }

    /**
     * Chiude il passo di riepilogo: errori → dashboard; nessuna modifica →
     * avviso; altrimenti valori in sessione + schermata di riepilogo.
     */
    private static function concludiRiepilogo(array $attuali, array $nuovi, array $errors, array $datiModifica): void
    {
        if ($errors !== []) {
            self::renderDashboard($attuali[0], $attuali[1], $attuali[2], true, $errors, self::valoriFormDaArray($datiModifica));
            return;
        }
        if ($attuali === $nuovi) {
            self::renderDashboard($attuali[0], $attuali[1], $attuali[2], false, ['Nessuna modifica rispetto ai valori attuali.']);
            return;
        }

        self::mostraRiepilogo($attuali, $nuovi);
    }

    /**
     * Mostra la schermata di riepilogo con i valori attuali e quelli proposti.
     * Salva i valori proposti in sessione per il passo successivo di conferma.
     */
    private static function mostraRiepilogo(array $attuali, array $nuovi): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'prezzo_assicurazione'    => $nuovi[0],
            'percentuale_commissione' => $nuovi[1],
            'aliquota_iva'            => $nuovi[2],
            'prezzo_precedente'       => $attuali[0],
            'percentuale_precedente'  => $attuali[1],
            'aliquota_precedente'     => $attuali[2],
        ];

        $totAssic = FPersistentManager::prenotazioneCountConAssicurazione();
        VAmministratore::commissioniRiepilogo(
            $attuali[0], $attuali[1], $nuovi[0], $nuovi[1],
            (float) $totAssic * $attuali[0], (float) $totAssic * $nuovi[0],
            $attuali[2], $nuovi[2]
        );
    }

    private static function pendingValido(mixed $pending): bool
    {
        return is_array($pending)
            && isset($pending['prezzo_assicurazione'], $pending['percentuale_commissione'], $pending['aliquota_iva']);
    }

    /**
     * Salva i valori in sospeso in piattaforma e mostra la schermata di conferma.
     * In caso di errore, mostra la schermata di conferma con l'errore.
     */
    private static function salvaPending(array $pending): void
    {
        try {
            $salvato = FPersistentManager::configurazionePiattaformaImpostaEconomici(
                (float) $pending['prezzo_assicurazione'],
                (float) $pending['percentuale_commissione'],
                (float) $pending['aliquota_iva']
            );
            unset($_SESSION[self::SESSION_KEY]);

            $salvato
                ? VAmministratore::commissioniConferma(true, [], (float) $pending['prezzo_assicurazione'], (float) $pending['percentuale_commissione'], (float) $pending['aliquota_iva'])
                : VAmministratore::commissioniConferma(false, ['Le modifiche non sono state applicate: i valori risultano già aggiornati.']);
        } catch (InvalidArgumentException $e) {
            VAmministratore::commissioniConferma(false, [$e->getMessage()]);
        } catch (Throwable) {
            VAmministratore::commissioniConferma(false, ['Errore durante il salvataggio dei parametri. Riprova tra qualche istante.']);
        }
    }

    /**
     * Renderizza la dashboard con i valori attuali o proposti, eventuali errori
     * e valori del form.
     */
    private static function renderDashboard(
        float $prezzo,
        float $perc,
        float $aliquota,
        bool $anteprima,
        array $errors,
        array $formValori = []
    ): void {
        $totAssic    = FPersistentManager::prenotazioneCountConAssicurazione();
        $ricavoAssic = (float) $totAssic * $prezzo;
        $grafici     = [
            'tot_assicurazioni' => $totAssic,
            'ricavo_assic'      => $ricavoAssic,
            'commissione_pct'   => round($perc, 2),
        ];

        VAmministratore::commissioni(
            $prezzo,
            $perc,
            $aliquota,
            $ricavoAssic,
            FPersistentManager::configurazionePiattaformaStoricoUltimi(10),
            $grafici,
            $anteprima,
            $errors,
            $formValori
        );
    }

    private static function leggiPrezzoInput(float|int|string|null $nuovoPrezzo): string
    {
        if ($nuovoPrezzo !== null && $nuovoPrezzo !== '') {
            return (string) $nuovoPrezzo;
        }

        return (string) post('prezzo_assicurazione', '');
    }

    /**
     * Restituisce i dati inviati tramite POST come array associativo.
     */
    private static function datiDaPost(): array
    {
        return [
            'csrf_token'              => post('csrf_token'),
            'prezzo_assicurazione'    => post('prezzo_assicurazione'),
            'percentuale_commissione' => post('percentuale_commissione'),
            'aliquota_iva'            => post('aliquota_iva'),
        ];
    }

    /**
     * Restituisce i valori del form come array associativo, con valori di default
     * se non presenti nei dati POST.
     */
    private static function valoriFormDaPost(): array
    {
        return self::valoriFormDaArray(self::datiDaPost());
    }

    /**
     * Restituisce i valori del form come array associativo, con valori di default
     * se non presenti nei dati forniti.
     */
    private static function valoriFormDaArray(array $dati): array
    {
        return [
            'prezzo_assicurazione'    => (string) ($dati['prezzo_assicurazione'] ?? ''),
            'percentuale_commissione' => (string) ($dati['percentuale_commissione'] ?? ''),
            'aliquota_iva'            => (string) ($dati['aliquota_iva'] ?? ''),
        ];
    }

    /**
     * Restituisce i valori del form come array associativo a partire da un array
     * di valori numerici.
     */
    private static function formDaValori(array $valori): array
    {
        return [
            'prezzo_assicurazione'    => $valori[0],
            'percentuale_commissione' => $valori[1],
            'aliquota_iva'            => $valori[2],
        ];
    }
}