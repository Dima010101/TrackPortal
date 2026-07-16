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