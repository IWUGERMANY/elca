<?php


namespace Elca\Model\Import\Csv;


use Elca\ElcaNumberFormat;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Service\Import\UnitNameMapper;
use Ramsey\Uuid\Uuid;

final class ImportElement
{
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

    public static function fromCsv(
        string $name,
        string $din276CodeString,
        string $quantityString,
        string $unitString,
        ?string $tplElementUuidString = null
    ): ImportElement {
        $name           = trim($name);
        $din276Code     = (int)$din276CodeString;
        $quantity       = self::parseQuantity($quantityString, $unitString);
        $tplElementUuid = null !== $tplElementUuidString ? Uuid::fromString($tplElementUuidString) : null;

        return new self($name, $din276Code, $quantity, $tplElementUuid);
    }


    public function __construct(string $name, int $din276Code = null, Quantity $quantity = null, Uuid $tplElementUuid = null)
    {
        $this->uuid           = (string)Uuid::uuid4();
        $this->name           = $name;
        $this->dinCode        = $din276Code;
        $this->quantity       = $quantity;
        $this->tplElementUuid = $tplElementUuid;
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