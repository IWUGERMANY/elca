<?php


namespace Elca\Model\Processing\Element;

use Elca\Db\ElcaElementComponent;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\ProcessConversion;

class ElementComponentQuantity
{
    /**
     * @var Quantity
     */
    private $quantity;

    /**
     * @var Conversion
     */
    private $toProcessConversion;

    /**
     * @var boolean
     */
    private $isLayer;

    /**
     * @var float
     */
    private $width;

    /**
     * @var float
     */
    private $length;

    /**
     * @var float
     */
    private $size;

    /**
     * @var float
     */
    private $areaRatio;

    public static function fromElcaElementComponent(ElcaElementComponent $elcaElementComponent,
        ProcessConversion $processConversion): ElementComponentQuantity
    {
        return new self(
            new Quantity(
                $elcaElementComponent->getElement()->getQuantity() * $elcaElementComponent->getQuantity(),
                $processConversion->fromUnit()
            ),
            $processConversion->conversion(),
            $elcaElementComponent->isLayer(),
            $elcaElementComponent->getLayerWidth(),
            $elcaElementComponent->getLayerLength(),
            $elcaElementComponent->getLayerSize(),
            $elcaElementComponent->getLayerAreaRatio()
        );
    }

    public function __construct(
        Quantity $quantity,
        Conversion $toProcessConversion,
        bool $isLayer = false,
        float $width = null,
        float $length = null,
        float $size = null,
        float $areaRatio = null
    )
    {
        $this->quantity            = $quantity;
        $this->toProcessConversion = $toProcessConversion;
        $this->isLayer             = $isLayer;
        $this->width               = $width;
        $this->length              = $length;
        $this->size                = $size;
        $this->areaRatio           = $areaRatio ?: 1;
    }

    public function convertedQuantity(): Quantity
    {
        $quantity = $this->isLayer()
            ? Quantity::inM3($this->quantity->value()
                             * $this->length
                             * $this->width
                             * $this->size
                             * $this->areaRatio
            )
            : $this->quantity;

        return $this->toProcessConversion->convertQuantity($quantity);
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function isLayer(): bool
    {
        return $this->isLayer;
    }
}