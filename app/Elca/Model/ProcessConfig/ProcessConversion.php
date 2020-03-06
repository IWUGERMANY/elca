<?php


namespace Elca\Model\ProcessConfig;


use Elca\Model\Common\Unit;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;

class ProcessConversion
{
    /**
     * @var ConversionId
     */
    private $conversionId;

    /**
     * @var ProcessDbId
     */
    private $processDbId;

    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    /**
     * @var LinearConversion
     */
    private $conversion;

    /**
     * @var FlowReference|null
     */
    private $flowReference;

    public function __construct(
        ProcessDbId $processDbId,
        ProcessConfigId $processConfigId,
        LinearConversion $conversion,
        FlowReference $flowReference = null
    )
    {
        $this->processDbId     = $processDbId;
        $this->conversion      = $conversion;
        $this->processConfigId = $processConfigId;
        $this->flowReference = $flowReference;
    }

    public function conversionId(): ConversionId
    {
        return $this->conversionId;
    }

    /**
     * technical, internal use
     */
    public function setConversionId(ConversionId $conversionId)
    {
        $this->conversionId = $conversionId;
    }

    public function processDbId(): ProcessDbId
    {
        return $this->processDbId;
    }

    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }

    public function conversion(): LinearConversion
    {
        return $this->conversion;
    }

    public function changeConversion(LinearConversion $newConversion): void
    {
        if (!$newConversion->equals($this->conversion)) {
            $inverseConversion = $newConversion->invert();
            if (!$inverseConversion->equals($this->conversion)) {
                return;
            }

            $this->conversion = $inverseConversion;

            return;
        }

        $this->conversion = $newConversion;
    }

    public function equals(ProcessConversion $object)
    {
        return $this->conversionId->equals($object->conversionId());
    }

    public function isImported()
    {
        return $this->conversion instanceof ImportedLinearConversion;
    }

    public function fromUnit(): Unit
    {
        return $this->conversion->fromUnit();
    }

    public function toUnit(): Unit
    {
        return $this->conversion->toUnit();
    }

    public function factor(): float
    {
        return $this->conversion->factor();
    }

    public function type(): ConversionType
    {
        return $this->conversion->type();
    }

    public function changeFlowReference(FlowReference $flowReference = null)
    {
        // Do not remove the flow reference once it was saved
        if (null === $flowReference) {
            return;
        }

        $this->flowReference = $flowReference;
    }

    public function flowReference(): ?FlowReference
    {
        return $this->flowReference;
    }

    public function hasFlowReference()
    {
        return null !== $this->flowReference;
    }


}