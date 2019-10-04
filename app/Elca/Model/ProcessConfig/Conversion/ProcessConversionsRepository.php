<?php

namespace Elca\Model\ProcessConfig\Conversion;

use Elca\Model\Common\Unit;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\ConversionId;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConversion;

interface ProcessConversionsRepository
{
    public function findById(ConversionId $conversionId, ProcessDbId $processDbId): ?ProcessConversion;

    public function findByConversion(ProcessConfigId $processConfigId, ProcessDbId $processDbId, Unit $fromUnit,
        Unit $toUnit): ?ProcessConversion;

    public function add(ProcessConversion $processConversion): void;

    public function save(ProcessConversion $processConversion): void;

    public function remove(ProcessConversion $processConversion): void;
}