<?php

/** Accesso alla configurazione globale (singleton) e relativo audit. */
class FConfigurazionePiattaforma extends FRepository
{
    protected static function entityClass(): string
    {
        return EConfigurazionePiattaforma::class;
    }

    public static function carica(): EConfigurazionePiattaforma
    {
        $config = FPersistentManager::find(
            EConfigurazionePiattaforma::class,
            EConfigurazionePiattaforma::ID_SISTEMA
        );

        if ($config instanceof EConfigurazionePiattaforma) {
            return $config;
        }

        $config = new EConfigurazionePiattaforma();
        FPersistentManager::persist($config);

        return $config;
    }

    public static function prezzoAssicurazione(): float
    {
        return self::carica()->getPrezzoAssicurazione();
    }

    public static function percentualeCommissione(): float
    {
        return self::carica()->getPercentualeCommissione();
    }

    public static function aliquotaIva(): float
    {
        return self::carica()->getAliquotaIva();
    }

    public static function impostaEconomici(
        float $prezzoAssicurazione,
        float $percentualeCommissione,
        float $aliquotaIva
    ): bool {
        self::validaPrezzoAssicurazione($prezzoAssicurazione);
        self::validaPercentualeCommissione($percentualeCommissione);
        self::validaAliquotaIva($aliquotaIva);

        $config = self::carica();

        if (self::stessiValoriEconomici($config, $prezzoAssicurazione, $percentualeCommissione, $aliquotaIva)) {
            return false;
        }

        $config->setPrezzoAssicurazione($prezzoAssicurazione);
        $config->setPercentualeCommissione($percentualeCommissione);
        $config->setAliquotaIva($aliquotaIva);
        self::salva($config);

        return true;
    }

    private static function validaPrezzoAssicurazione(float $v): void
    {
        if ($v < 0) {
            throw new InvalidArgumentException('Il prezzo assicurazione deve essere ≥ 0.');
        }
    }

    private static function validaPercentualeCommissione(float $v): void
    {
        if ($v < 0 || $v > 100) {
            throw new InvalidArgumentException('La percentuale commissione deve essere tra 0 e 100.');
        }
    }

    private static function validaAliquotaIva(float $v): void
    {
        if ($v < 0 || $v > 100) {
            throw new InvalidArgumentException('L\'aliquota IVA deve essere compresa tra 0 e 100.');
        }
    }

    private static function stessiValoriEconomici(
        EConfigurazionePiattaforma $config,
        float $prezzoAssicurazione,
        float $percentualeCommissione,
        float $aliquotaIva
    ): bool {
        return round($config->getPrezzoAssicurazione(), 2) === round($prezzoAssicurazione, 2)
            && round($config->getPercentualeCommissione(), 2) === round($percentualeCommissione, 2)
            && round($config->getAliquotaIva(), 2) === round($aliquotaIva, 2);
    }

    private static function salva(EConfigurazionePiattaforma $config): void
    {
        $config->setAggiornatoIl((new DateTimeImmutable())->format('Y-m-d H:i:s'));

        FPersistentManager::transaction(static function () use ($config): void {
            FPersistentManager::persist($config, flush: false);
            self::registraStorico($config);
            FPersistentManager::flush();
        });
    }

    private static function registraStorico(EConfigurazionePiattaforma $config): void
    {
        FDataBase::executeStatement(
            'INSERT INTO storico_configurazione
                (prezzo_assicurazione, percentuale_commissione, aliquota_iva, aggiornato_il)
             VALUES
                (:prezzo, :percentuale, :aliquota, :aggiornato_il)',
            [
                'prezzo'        => $config->getPrezzoAssicurazione(),
                'percentuale'   => $config->getPercentualeCommissione(),
                'aliquota'      => $config->getAliquotaIva(),
                'aggiornato_il' => $config->getAggiornatoIl(),
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function storicoUltimi(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        return FDataBase::executeQuery(
            'SELECT id, prezzo_assicurazione, percentuale_commissione, aliquota_iva,
                    aggiornato_il
             FROM storico_configurazione
             ORDER BY aggiornato_il DESC, id DESC
             LIMIT ' . $limit
        )->fetchAllAssociative();
    }
}