<?php


namespace Elca\Service\ProcessConfig;


use Elca\Db\ElcaProcessConversionAudit;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\ProcessConversion;

class ConversionsAudit
{
    public function recordNewConversion(ProcessConversion $processConversion, string $callee) {
        ElcaProcessConversionAudit::recordNew($processConversion, $callee);
    }

    public function recordUpdatedConversion(ProcessConversion $processConversion, LinearConversion $oldConversion,
        ?FlowReference $oldFlowReference, string $callee)
    {
        ElcaProcessConversionAudit::recordUpdate($processConversion, $oldConversion, $oldFlowReference, $callee);
    }

    public function recordRemovedConversion(ProcessConversion $processConversion, string $callee)
    {
        ElcaProcessConversionAudit::recordRemoval($processConversion, $callee);
    }
}