<?php
namespace Lcc\Model\Processing;

use Bnb\Db\BnbWater;
use Bnb\Model\Processing\BnbProcessor;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectVariant;
use Lcc\Db\LccCost;
use Lcc\Db\LccIrregularCost;
use Lcc\Db\LccProjectCost;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccRegularServiceCost;
use Lcc\LccModule;

/**
 * DetailedMethod
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class DetailedMethod
{
    /**
     * @var LccProjectVersion
     */
    private $projectVersion;
    private $projectVariantId;

    /**
     * DetailedMethod constructor.
     *
     * @param                   $projectVariantId
     * @param LccProjectVersion $projectVersion
     */
    public function __construct($projectVariantId, LccProjectVersion $projectVersion)
    {
        $this->projectVersion = $projectVersion;
        $this->projectVariantId = (int)$projectVariantId;
    }

    /**
     *
     */
    public function updateAll()
    {
        $this->updateFinalEnergyDemands(ElcaProjectFinalEnergyDemandSet::findByProjectVariantId($this->projectVariantId));
        $this->updateFinalEnergySupplies(ElcaProjectFinalEnergySupplySet::findByProjectVariantId($this->projectVariantId));
        $this->updateDomesticWater(BnbWater::findByProjectId(ElcaProjectVariant::findById($this->projectVariantId)->getProjectId()));

        $this->computeLcc();
    }

    /**
     *
     */
    public function computeLcc()
    {
        $this->projectVersion->computeLcc();
    }


    /**
     * @param ElcaProjectFinalEnergyDemandSet $finalEnergyDemands
     * @throws \Exception
     */
    public function updateFinalEnergyDemands(ElcaProjectFinalEnergyDemandSet $finalEnergyDemands)
    {
        foreach ([
            LccCost::IDENT_HEATING => 'heating',
            LccCost::IDENT_WATER => 'water',
            LccCost::IDENT_LIGHTING => 'lighting',
            LccCost::IDENT_COOLING => 'cooling',
            LccCost::IDENT_VENTILATION => 'ventilation'] as $costIdent => $demandProperty) {

            $cost = $this->getProjectCostByIdent($this->projectVariantId, $this->projectVersion->getVersionId(), $costIdent);
            $sum = $finalEnergyDemands->getSumByKey($demandProperty);
            if ($cost->getQuantity() !== $sum) {
                $cost->setQuantity($sum);
                $cost->update();
            }
        }
    }

    /**
     * @param ElcaProjectFinalEnergySupplySet $finalEnergySupplies
     * @throws \Exception
     */
    public function updateFinalEnergySupplies(ElcaProjectFinalEnergySupplySet $finalEnergySupplies)
    {
        $cost = $this->getProjectCostByIdent($this->projectVariantId, $this->projectVersion->getVersionId(), LccCost::IDENT_CREDIT_EEG);

        /**
         * @var ElcaProjectFinalEnergySupply $finalEnergySupply
         */
        $sum = 0;
        foreach ($finalEnergySupplies as $finalEnergySupply) {
            $sum += $finalEnergySupply->getQuantity() * (1 - $finalEnergySupply->getEnEvRatio());
        }

        if ($cost->getQuantity() !== $sum) {
            $cost->setQuantity($sum);
            $cost->update();
        }
    }

    /**
     * @param BnbWater $water
     * @throws \Exception
     */
    public function updateDomesticWater(BnbWater $water)
    {
        $bnbProcessor = new BnbProcessor();
        $benchmark = $bnbProcessor->computeWaterBenchmark(
            $water,
            ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId)->getNetFloorSpace()
        );

        $tapWater = $benchmark['gesamtfrischwasserbedarf'];
        $wasteWater = $benchmark['gesamtabwasseraufkommen'];
        $rainWater = $benchmark['niederschlagDaecher'];

        foreach ([
            LccCost::IDENT_TAP_WATER => $tapWater,
            LccCost::IDENT_WASTE_WATER => $wasteWater,
            LccCost::IDENT_RAIN_WATER=> $rainWater
        ] as $ident => $waterDemand) {
            
            $cost = $this->getProjectCostByIdent(
                $this->projectVariantId,
                $this->projectVersion->getVersionId(),
                $ident
            );

            if ($cost->getQuantity() !== $waterDemand) {
                $cost->setQuantity($waterDemand);
                $cost->update();
            }
        }
    }

    /**
     * @param $projectVariantId
     * @param $ident
     * @return LccProjectCost
     * @throws \Exception
     */
    private function getProjectCostByIdent($projectVariantId, $versionId, $ident)
    {
        $cost = LccCost::findByVersionIdAndIdent($versionId, $ident);

        if (!$cost->isInitialized()) {
            throw new \Exception('Could not find costs for versionId='. $versionId.' and ident='. $ident);
        }

        $projectCost = LccProjectCost::findByPk($projectVariantId, LccModule::CALC_METHOD_DETAILED, $cost->getId());

        if (!$projectCost->isInitialized()) {
            $projectCost = LccProjectCost::create($projectVariantId, LccModule::CALC_METHOD_DETAILED, $cost->getId());
        }

        return $projectCost;
    }
}
