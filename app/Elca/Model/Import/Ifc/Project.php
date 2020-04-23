<?php


namespace Elca\Model\Import\Ifc;


class Project
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $constrMeasure;

    /**
     * @var string
     */
    private $postcode;

    /**
     * @var int
     */
    private $constrClassId;

    /**
     * @var int
     */
    private $benchmarkVersionId;

    /**
     * @var float
     */
    private $netFloorSpace;

    /**
     * @var float
     */
    private $grossFloorSpace;

    /**
     * @var ImportElement[]
     */
    private $importElements;

    public function __construct(
        string $name,
        int $constrMeasure,
        string $postcode,
        int $constrClassId,
        int $benchmarkVersionId,
        float $netFloorSpace,
        float $grossFloorSpace
    ) {
        $this->name               = $name;
        $this->constrMeasure      = $constrMeasure;
        $this->postcode           = $postcode;
        $this->constrClassId      = $constrClassId;
        $this->benchmarkVersionId = $benchmarkVersionId;
        $this->netFloorSpace      = $netFloorSpace;
        $this->grossFloorSpace    = $grossFloorSpace;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function constrMeasure(): int
    {
        return $this->constrMeasure;
    }

    public function postcode(): string
    {
        return $this->postcode;
    }

    public function constrClassId(): int
    {
        return $this->constrClassId;
    }

    public function benchmarkVersionId(): int
    {
        return $this->benchmarkVersionId;
    }

    public function netFloorSpace(): float
    {
        return $this->netFloorSpace;
    }

    public function grossFloorSpace(): float
    {
        return $this->grossFloorSpace;
    }

    /**
     * @return ImportElement[]
     */
    public function importElements(): array
    {
        return $this->importElements;
    }

    public function setImportElements(array $importElements): void
    {
        $this->importElements = $importElements;
    }

    public function findElementByUuid(string $uuid)
    {
        foreach ($this->importElements as $importElement) {
            if ($importElement->uuid() === $uuid) {
                return $importElement;
            }
        }

        return null;
    }

    public function replaceElement(ImportElement $oldImportElement, ImportElement $newElement)
    {
        $oldUuid = $oldImportElement->uuid();

        foreach ($this->importElements as $index => $importElement) {
            if ($importElement->uuid() === $oldUuid) {
                $this->importElements[$index] = $newElement;
                break;
            }
        }
    }

    public function wasModifiedDuringImport(): bool
    {
        foreach ($this->importElements as $importElement) {
            if ($importElement->isModified()) {
                return true;
            }
        }

        return false;
    }
}