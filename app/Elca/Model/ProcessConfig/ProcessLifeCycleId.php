<?php


namespace Elca\Model\ProcessConfig;


use Elca\Model\Process\ProcessDbId;

class ProcessLifeCycleId
{
    /**
     * @var ProcessDbId
     */
    private $processDbId;

    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    public function __construct(ProcessDbId $processDbId, ProcessConfigId $processConfigId)
    {
        $this->processDbId     = $processDbId;
        $this->processConfigId = $processConfigId;
    }

    public function processDbId(): ProcessDbId
    {
        return $this->processDbId;
    }

    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }

    public function equals(ProcessLifeCycleId $object)
    {
        return $this->processConfigId->equals($object->processConfigId()) &&
               $this->processDbId->equals($object->processDbId());
    }
}