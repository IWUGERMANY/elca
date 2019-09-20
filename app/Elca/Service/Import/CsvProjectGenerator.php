<?php


namespace Elca\Service\Import;


use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Import\Csv\Project;
use Elca\Security\ElcaAccess;
use Elca\Service\Project\ProjectElementService;

class CsvProjectGenerator
{
    /**
     * @var ElcaAccess
     */
    private $elcaAccess;

    /**
     * @var DbHandle
     */
    private $dbHandle;

    /**
     * @var ProjectElementService
     */
    private $projectElementService;

    public function __construct(
        ElcaAccess $elcaAccess,
        DbHandle $dbHandle,
        ProjectElementService $projectElementService
    ) {
        $this->elcaAccess            = $elcaAccess;
        $this->dbHandle              = $dbHandle;
        $this->projectElementService = $projectElementService;
    }

    public function generate(Project $importProject): ElcaProject
    {

        try {
            $this->dbHandle->begin();

            $elcaProject = $this->generateProject($importProject);

            $this->generateElements($importProject, $elcaProject->getCurrentVariantId());

            $this->dbHandle->commit();
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();

            throw $exception;
        }

        return $elcaProject;
    }

    private function generateProject(Project $importProject): ElcaProject
    {
        $benchmarkVersion = ElcaBenchmarkVersion::findById($importProject->benchmarkVersionId());

        $elcaProject = ElcaProject::create(
            $benchmarkVersion->getProcessDbId(),
            $this->elcaAccess->getUserId(),
            $this->elcaAccess->getUserGroupId(),
            $importProject->name(),
            Elca::DEFAULT_LIFE_TIME,
            null,
            null,
            null,
            $importProject->constrMeasure(),
            $importProject->constrClassId(),
            null,
            false,
            $benchmarkVersion->getId()
        );

        $phase          = ElcaProjectPhase::findMinIdByConstrMeasure($elcaProject->getConstrMeasure(), 1);
        $projectVariant = ElcaProjectVariant::create(
            $elcaProject->getId(),
            $phase->getId(),
            $phase->getName()
        );
        $elcaProject->setCurrentVariantId($projectVariant->getId());
        $elcaProject->update();

        ElcaProjectConstruction::create(
            $projectVariant->getId(),
            null,
            null,
            $importProject->grossFloorSpace(),
            $importProject->netFloorSpace()
        );

        ElcaProjectLocation::create(
            $projectVariant->getId(),
            null,
            $importProject->postcode()
        );

        return $elcaProject;
    }

    private function generateElements(Project $importProject, int $projectVariantId)
    {
        foreach ($importProject->importElements() as $importElement) {

            $tplElement = ElcaElement::findByUuid($importElement->tplElementUuid());

            if (!$tplElement->isInitialized()) {
                continue;
            }

            $newElement = $this->projectElementService->copyElementFrom(
                $tplElement,
                $this->elcaAccess->getUserId(),
                $projectVariantId,
                $this->elcaAccess->getUserGroupId(),
                true,
                false
            );

            if (null === $newElement) {
                throw new \RuntimeException('Copy from template element failed');
            }

            $oldQuantity = $newElement->getQuantity();

            $newElement->setQuantity($importElement->quantity()->value());
            $newElement->setRefUnit($importElement->quantity()->unit()->value());
            $newElement->update();

            $this->projectElementService->updateQuantityOfAffectedElements($newElement, $oldQuantity);
        }
    }
}