<?php
/*
 promozione destinata ad un utente. 
 questa classe ci permette di gestire le promozioni in modo flessibile, facendo un match tra l'utente  e la promozione.
 */

class EPromozioneDestinatario
{
    protected int $promozioneID;
    protected int $utenteId;
    protected bool $inviata;

    public function __construct(int $promozioneID, int $utenteId, bool $inviata)
    {
        $this->promozioneID = $promozioneID;
        $this->utenteId = $utenteId;
        $this->inviata = $inviata;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['promozioneID'] ?? 0),
            (int)($r['utenteId'] ?? 0),
            (bool)($r['inviata'] ?? false)
        );
    }

    public function getPromozioneID(): int
    {
        return $this->promozioneID;
    }

    public function getUtenteId(): int
    {
        return $this->utenteId;
    }

    public function isInviata(): bool
    {
        return $this->inviata;
    }
    public function setInviata(bool $v): void
    {
        $this->inviata = $v;
    }
}