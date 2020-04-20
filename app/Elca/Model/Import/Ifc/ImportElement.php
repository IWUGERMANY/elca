<?php


namespace Elca\Model\Import\Ifc;


use Elca\Db\ElcaElement;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Service\Import\UnitNameMapper;
use Ramsey\Uuid\Uuid;

final class ImportElement
{
    const DINCODE_MISMATCH = 0x1;
    const UNIT_MISMATCH = 0x2;

    /**
     * Internal id
     *
     * @var string
     */
    private $uuid;

    private $name;

    private $dinCode;

    /**
     * @var Quantity
     */
    private $quantity;

    private $tplElementUuid;

    private $isModified;

    private $modificationReason;

    public static function fromCsv(
        string $name,
        string $din276CodeString,
        string $quantityString,
        string $unitString,
        ?string $tplElementUuidString = null
    ): ImportElement
    {
        $name           = trim($name);
        $din276Code     = (int)$din276CodeString;
        $quantity       = self::parseQuantity($quantityString, $unitString);
        $tplElementUuid = null !== $tplElementUuidString ? Uuid::fromString($tplElementUuidString) : null;

        return new self($name, $din276Code, $quantity, $tplElementUuid);
    }

    public function __construct(string $name, int $din276Code = null, Quantity $quantity = null,
        Uuid $tplElementUuid = null, bool $isModified = false, int $modificationReason = 0)
    {
        $this->uuid               = (string)Uuid::uuid4();
        $this->name               = $name;
        $this->dinCode            = $din276Code;
        $this->quantity           = $quantity;
        $this->tplElementUuid     = $tplElementUuid;
        $this->isModified         = $isModified;
        $this->modificationReason = $modificationReason;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function dinCode(): ?int
    {
        return $this->dinCode;
    }

    public function changeDinCode(?int $dinCode)
    {
        $this->dinCode = $dinCode;
    }

    public function quantity(): ?Quantity
    {
        return $this->quantity;
    }

    public function changeQuantity(Quantity $quantity)
    {
        $this->quantity = $quantity;
    }

    public function tplElementUuid(): ?string
    {
        return null !== $this->tplElementUuid ? (string)$this->tplElementUuid : null;
    }

    public function changeTplElementUuid(?string $uuid)
    {
        $this->tplElementUuid = null !== $uuid ? Uuid::fromString($uuid) : null;
    }

    public function isValid()
    {
        return null !== $this->quantity && $this->dinCode && $this->tplElementUuid();
    }

    public function isModified(): bool
    {
        return $this->isModified;
    }

    public function modificationReason(): int
    {
        return $this->modificationReason;
    }

    public function hasModificationReason($reason) : bool
    {
        return $this->modificationReason & $reason;
    }

    public function harmonizeWithTemplateElement(?ElcaElement $tplElement): ImportElement
    {
        if (null === $tplElement) {
            return $this;
        }

        $dinCode = $this->dinCode;
        $quantity = $this->quantity;
        $reason = 0;

        $tplElementDinCode = $tplElement->getElementTypeNode()->getDinCode();
        if ($tplElementDinCode !== $this->dinCode) {
            $dinCode = $tplElementDinCode;
            $reason |= self::DINCODE_MISMATCH;
        }

        if ($tplElement->getRefUnit() !== $this->quantity->unit()->value()) {
            $quantity = Quantity::fromValue($this->quantity->value(), $tplElement->getRefUnit());
            $reason |= self::UNIT_MISMATCH;
        }

        if ($reason > 0) {
            return new ImportElement($this->name, $dinCode, $quantity, $this->tplElementUuid, !empty($reason), $reason);
        }

        return $this;
    }

    private static function parseQuantity(string $quantityString = null, string $unit = null)
    {
        if (empty($quantityString) || empty($unit)) {
            return null;
        }

        return new Quantity(
            ElcaNumberFormat::fromString($quantityString, 2),
            UnitNameMapper::unitByName($unit)
        );
    }
}