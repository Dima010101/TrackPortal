<?php
/**
 * ECircuitoFoto - foto associata a un circuito (ad un circuito possono essere associate più foto).
 */
class ECircuitoFoto
{
    protected ?int $id;
    protected int $circuitoId;
    protected string $pathFile;

    public function __construct(int $circuitoId = 0, string $pathFile = '', ?int $id = null)
    {
        $this->id = $id;
        $this->circuitoId = $circuitoId;
        $this->pathFile = $pathFile;
    }

    public static function fromArray(array $r): self
    {
        return new self(
            (int)($r['circuito_id'] ?? 0),
            (string)($r['path_file'] ?? ''),
            isset($r['id']) ? (int)$r['id'] : null
        );
    }

    public function getId(): ?int            { return $this->id; }
    public function getCircuitoId(): int     { return $this->circuitoId; }
    public function setCircuitoId(int $v): void { $this->circuitoId = $v; }
    public function getPathFile(): string    { return $this->pathFile; }
    public function setPathFile(string $v): void { $this->pathFile = $v; }
}
