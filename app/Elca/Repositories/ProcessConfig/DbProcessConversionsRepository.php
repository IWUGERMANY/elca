<?php


namespace Elca\Repositories\ProcessConfig;


use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessConversionVersion;
use Elca\Db\ElcaProcessConversionVersionSet;
use Elca\Model\Common\Unit;
use Elca\Model\Exception\InvalidArgumentException;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\ProcessConversionsRepository;
use Elca\Model\ProcessConfig\ConversionId;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConversion;
use Utils\Model\FactoryHelper;

class DbProcessConversionsRepository implements ProcessConversionsRepository
{
    /**
     * @var DbHandle
     */
    private $dbHandle;

    public function __construct(DbHandle $dbHandle)
    {
        $this->dbHandle = $dbHandle;
    }

    public function findById(ConversionId $conversionId, ProcessDbId $processDbId): ?ProcessConversion
    {
        $processConversionVersion = ElcaProcessConversionVersion::findByPK(
            $conversionId->value(),
            $processDbId->value()
        );

        if (!$processConversionVersion->isInitialized()) {
            return null;
        }

        $processConversion = $processConversionVersion->getProcessConversion();

        return $this->build($processConversion, $processConversionVersion);
    }

    public function findByConversion(ProcessConfigId $processConfigId, ProcessDbId $processDbId, Unit $fromUnit,
        Unit $toUnit): ?ProcessConversion
    {

        $processConversion = ElcaProcessConversion::findByProcessConfigIdAndInOut(
            $processConfigId->value(),
            $fromUnit->value(),
            $toUnit->value(),
            true
        );

        if (!$processConversion->isInitialized()) {
            return null;
        }

        $processConversionVersion = ElcaProcessConversionVersion::findByPK(
            $processConversion->getId(),
            $processDbId->value()
        );

        if (!$processConversionVersion->isInitialized()) {
            return null;
        }

        return $this->build($processConversion, $processConversionVersion);
    }

    public function add(ProcessConversion $processConversion): void
    {
        $linearConversion = $processConversion->conversion();

        $elcaProcessConversion = ElcaProcessConversion::findByProcessConfigIdAndInOut(
            $processConversion->processConfigId()->value(),
            $linearConversion->fromUnit()->value(),
            $linearConversion->toUnit()->value()
        );

        try {
            $this->dbHandle->begin();

            if (!$elcaProcessConversion->isInitialized()) {
                $elcaProcessConversion = ElcaProcessConversion::create(
                    $processConversion->processConfigId()->value(),
                    $linearConversion->fromUnit()->value(),
                    $linearConversion->toUnit()->value()
                );
            }

            ElcaProcessConversionVersion::create(
                $elcaProcessConversion->getId(),
                $processConversion->processDbId()->value(),
                $linearConversion->factor(),
                $linearConversion->type()->value()
            );

            $this->dbHandle->commit();
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }

        $processConversion->setConversionId(new ConversionId($elcaProcessConversion->getId()));
    }

    public function save(ProcessConversion $processConversion): void
    {
        $conversionId = $processConversion->conversionId();

        $elcaProcessConversion = ElcaProcessConversion::findById($conversionId->value());

        if (!$elcaProcessConversion->isInitialized()) {
            throw new InvalidArgumentException(
                'Process conversion could not be found by id `:id:\'', [
                    ':id:' => $conversionId->value(),
                ]
            );
        }

        $elcaProcessConversionVersion = ElcaProcessConversionVersion::findByPK(
            $conversionId->value(),
            $processConversion->processDbId()->value()
        );

        if (!$elcaProcessConversionVersion->isInitialized()) {
            throw new InvalidArgumentException(
                'Process conversion version could not be found by id `:id:\' and processDbId `:dbId:\'', [
                    ':id:'   => $conversionId->value(),
                    ':dbId:' => $processConversion->processDbId()->value(),
                ]
            );
        }

        $linearConversion = $processConversion->conversion();
        $elcaProcessConversionVersion->setFactor($linearConversion->factor());
        $elcaProcessConversionVersion->setIdent($linearConversion->type()->value());
        $elcaProcessConversionVersion->update();
    }

    public function remove(ProcessConversion $processConversion): void
    {
        $elcaProcessConversionVersion = ElcaProcessConversionVersion::findByPK(
            $processConversion->conversionId()->value(),
            $processConversion->processDbId()->value()
        );

        try {
            $elcaProcessConversionVersion->delete();

            if (0 === ElcaProcessConversionVersionSet::countByConversionId($processConversion->conversionId()->value())) {
                $elcaProcessConversion = ElcaProcessConversion::findById($elcaProcessConversionVersion->getConversionId());
                $elcaProcessConversion->delete();
            }
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }
    }

    private function build(
        ElcaProcessConversion $processConversion,
        ElcaProcessConversionVersion $processConversionVersion
    )
    {
        $conversionType = $processConversionVersion->getIdent() ? ImportedLinearConversion::class
            : LinearConversion::class;

        $conversion = new $conversionType(
            Unit::fromString($processConversion->getInUnit()),
            Unit::fromString($processConversion->getOutUnit()),
            $processConversionVersion->getFactor()
        );

        return FactoryHelper::createInstanceWithoutConstructor(
            ProcessConversion::class,
            [
                'conversionId'    => new ConversionId($processConversion->getId()),
                'processDbId'     => new ProcessDbId($processConversionVersion->getProcessDbId()),
                'processConfigId' => new ProcessConfigId($processConversion->getProcessConfigId()),
                'conversion'      => $conversion,
            ]
        );
    }
}