<?php


namespace Elca\Model\ProcessConfig;


use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\Conversion;
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

    public function __construct(
        ProcessDbId $processDbId,
        ProcessConfigId $processConfigId,
        LinearConversion $conversion
    ) {
        $this->processDbId    = $processDbId;
        $this->conversion      = $conversion;
        $this->processConfigId = $processConfigId;
    }

    public function conversionId(): ConversionId
    {
        return $this->conversionId;
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
        $this->conversion = $newConversion;
    }

    public function equals(ProcessConversion $object)
    {
        return $this->conversionId->equals($object->conversionId());
    }

    /**
     * technical, internal use
     */
    public function setConversionId(ConversionId $conversionId)
    {
        $this->conversionId = $conversionId;
    }
}