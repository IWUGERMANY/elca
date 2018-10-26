<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Lcc\Controller;

use Beibob\Blibs\DbObjectCache;
use Beibob\Blibs\Url;
use Beibob\Blibs\Validator;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProjectConstruction;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\ElcaProjectNavigationLeftView;
use Lcc\Db\LccCost;
use Lcc\Db\LccCostSet;
use Lcc\Db\LccEnergySourceCost;
use Lcc\Db\LccProjectCost;
use Lcc\Db\LccProjectCostSet;
use Lcc\Db\LccProjectTotalSet;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccRegularCost;
use Lcc\Db\LccRegularServiceCost;
use Lcc\Db\LccVersion;
use Lcc\LccModule;
use Lcc\Model\Processing\DetailedMethod;
use Lcc\View\LccDetailedView;

/**
 * Project data controller
 *
 * @package lcc
 * @author  Tobias Lode <tobias@beibob.de>
 */
class DetailedCtrl extends AppCtrl
{

    /**
     * Default action
     *
     * @param  -
     * @return -
     */
    protected function defaultAction(
        $addNavigationViews = true,
        $Validator = null,
        $adminMode = false,
        $newVersionId = null
    ) {
        if (!$this->isAjax()) {
            return;
        }

        $Data = new \stdClass();

        $projectVariantId    = $this->Elca->getProjectVariantId();
        $Project             = $this->Elca->getProject();
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($projectVariantId);
        $ProjectVersion      = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);

        // initial version to recent version or saved one
        if ($newVersionId) {
            $LccVersion = LccVersion::findById($newVersionId);
        } elseif ($ProjectVersion->isInitialized()) {
            $LccVersion = $ProjectVersion->getVersion();
        } else {
            $LccVersion = LccVersion::findRecent(LccModule::CALC_METHOD_DETAILED);
        }

        // parameters
        $Data->projectLifeTime = $Project->getLifeTime();
        $Data->bgf             = $ProjectConstruction->getGrossFloorSpace();

        foreach (['rate', 'commonPriceInc', 'energyPriceInc', 'waterPriceInc', 'cleaningPriceInc'] as $property) {
            $Data->$property = $LccVersion->$property;
        }

        if ($ProjectVersion->isInitialized()) {
            $Data->isInitialized = true;

            $Data->versionId    = $newVersionId ? (int)$newVersionId : $ProjectVersion->getVersionId();
            $Data->oldVersionId = $ProjectVersion->getVersionId();

            foreach ([300, 400, 500] as $code) {
                $costProperty        = 'costs' . $code;
                $Data->$costProperty = $ProjectVersion->$costProperty;

                $grouping                    = LccCost::GROUPING_KGU . $code;
                $property                    = \utf8_strtolower($grouping) . 'Alt';
                $Data->kguAlt[$grouping]     = $ProjectVersion->$property;
                $Data->kguAltPerc[$grouping] = $ProjectVersion->$costProperty * LccProjectVersion::KGU_ALTERNATIVE_PERCENTAGE / 100;
            }
        } else {
            $Data->isInitialized = false;
            $Data->versionId     = $LccVersion->getId();
        }

        // all stored project data
        $Data->quantity = [];
        $Data->refValue = [];

        $projectCosts = LccProjectCostSet::find(
            [
                'project_variant_id' => $projectVariantId,
                'calc_method'        => LccModule::CALC_METHOD_DETAILED,
            ]
        );

        foreach ($projectCosts as $projectCost) {
            $key                            = $projectCost->getCostId();
            $Data->quantity[$key]           = $projectCost->getQuantity();
            $Data->quantityCalculated[$key] = $projectCost->getQuantity();
            $Data->refValue[$key]           = $projectCost->getRefValue();
            if ($newVersionId) {
                $Data->energySourceCostId[$key] = LccEnergySourceCost::findByVersionIdAndName(
                    $newVersionId,
                    $projectCost->getEnergySourceCost()->getName()
                )->getId();
            } else {
                $Data->energySourceCostId[$key] = $projectCost->getEnergySourceCostId();
            }
        }

        // add totals
        foreach (
            LccProjectTotalSet::find(
                ['project_variant_id' => $projectVariantId, 'calc_method' => LccModule::CALC_METHOD_DETAILED]
            ) as $ProjectTotal
        ) {
            $Data->sum[$ProjectTotal->getGrouping()] = $ProjectTotal->getCosts();
        }

        $View = $this->addView(new LccDetailedView());
        $View->assign('Data', $Data);
        $View->assign('toggleStates', $this->Request->getArray('toggleStates'));
        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        if ($Validator) {
            $View->assign('Validator', $Validator);
        }

        if ($adminMode) {
            $View->assign('adminMode', true);
        }

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(
                new ElcaOsitItem(t('Gebäudebezogene Kosten im Lebenszyklus'), null, t('Ökonomische Qualtität'))
            );
        }
    }
    // End defaultAction


    /**
     * Default action
     *
     * @param  -
     * @return -
     */
    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost()) {
            return;
        }

        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('save')) {
            $Project   = Elca::getInstance()->getProject();
            $projectId = $Project->getId();

            $projectVariantId = $this->Request->projectVariantId;
            $isAdminMode      = (bool)$this->Request->isAdminMode;
            $ProjectVersion   = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);
            $Validator        = new Validator($this->Request);

            $quantities = $this->Request->getArray('quantity');
            $refValues  = $this->Request->getArray('refValue');
            $energySourceCostIds = $this->Request->getArray('energySourceCostId');

            /**
             * If version has changed, keep it separate from old version.
             * This will be handled at end of the update
             */
            $newVersionId = $this->Request->versionId;
            $versionId    = $ProjectVersion->isInitialized() ? $ProjectVersion->getVersionId() : $newVersionId;

            /**
             * Save project life cycle cost data
             */
            if (!$ProjectVersion->isInitialized()) {
                $ProjectVersion = LccProjectVersion::create(
                    $projectVariantId,
                    LccModule::CALC_METHOD_DETAILED,
                    $versionId
                );
                $detailedMethod = new DetailedMethod($projectVariantId, $ProjectVersion);
                $detailedMethod->updateAll();
            }

            if ($isAdminMode) {
                $version = $ProjectVersion->getVersion();

                $version->setRate(ElcaNumberFormat::fromString($this->Request->rate, 4, true));
                $version->setCommonPriceInc(ElcaNumberFormat::fromString($this->Request->commonPriceInc, 4, true));
                $version->setEnergyPriceInc(ElcaNumberFormat::fromString($this->Request->energyPriceInc, 4, true));
                $version->setWaterPriceInc(ElcaNumberFormat::fromString($this->Request->waterPriceInc, 4, true));
                $version->setCleaningPriceInc(ElcaNumberFormat::fromString($this->Request->cleaningPriceInc, 4, true));
                $version->update();
            }

            // update follows at end of method

            // if bgf was not set, save it now
            $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($projectVariantId);

            if (!$ProjectConstruction->getGrossFloorSpace()) {
                if ($Validator->assertNotEmpty('bgf', null, t('Bitte geben Sie einen Wert für die BGF an'))) {
                    $bgf = ElcaNumberFormat::fromString($this->Request->bgf);
                    $ProjectConstruction->setGrossFloorSpace($bgf);
                    $ProjectConstruction->update();
                }
            }

            /**
             * Save regular cleaning, energy and water costs
             */
            foreach ([LccCost::GROUPING_WATER, LccCost::GROUPING_ENERGY, LccCost::GROUPING_CLEANING] as $grouping) {
                foreach ($Set = LccCostSet::findRegular($versionId, $grouping) as $lccCost) {
                    $costId = $lccCost->getId();

                    $refValue = null;
                    if ($isAdminMode || isset($refValues[$costId])) {
                        $refValue = ElcaNumberFormat::fromString($refValues[$costId]);
                    }

                    if ($isAdminMode) {
                        $RegularCost = LccRegularCost::findByCostId($costId);
                        $RegularCost->setRefValue($refValue);
                        $RegularCost->update();
                    }

                    $hasQuantity = isset($quantities[$costId]);
                    $hasEnergySourceCosts  = isset($energySourceCostId[$costId]);
                    $refValueEditIsEnabled = ($isAdminMode && !$hasEnergySourceCosts) ||
                                             LccCost::IDENT_EEG === $lccCost->getIdent();

                    $projectCost = LccProjectCost::findByPk(
                        $projectVariantId,
                        LccModule::CALC_METHOD_DETAILED,
                        $costId
                    );

                    $quantity = $hasQuantity
                        ? ElcaNumberFormat::fromString($quantities[$costId])
                        : $projectCost->getQuantity();

                    // assert that refValue is set when quantity value given
                    $refValueMayNotBeEmpty = $quantity && LccCost::IDENT_EEG === $lccCost->getIdent()
                        ? $Validator->assertNotEmpty(
                            'quantity[' . $costId . ']',
                            $refValue,
                            t('Ein Wert für die Kosten pro ME muss gesetzt sein')
                        )
                        : false;

                    if (!$refValueEditIsEnabled && isset($energySourceCostIds[$costId])) {
                        $energySourceCost = LccEnergySourceCost::findById($energySourceCostIds[$costId]);
                        $refValue         = $energySourceCost->getCosts();
                    }

                    if ($projectCost->isInitialized()) {
                        $energySourceCostId = $energySourceCostIds[$costId] ?? null;
                        if ($hasEnergySourceCosts && $ProjectVersion->getVersionId() != $newVersionId) {
                            $energySourceCostId = LccEnergySourceCost::findByVersionIdAndName(
                                $newVersionId,
                                LccEnergySourceCost::findById($energySourceCostIds[$costId])->getName()
                            )->getId();
                        }

                        $projectCost->setQuantity($quantity);
                        $projectCost->setRefValue($refValue);
                        $projectCost->setEnergySourceCostId($energySourceCostId);
                        $projectCost->update();
                    } else {
                        LccProjectCost::create(
                            $projectVariantId,
                            LccModule::CALC_METHOD_DETAILED,
                            $costId,
                            $quantity,
                            $refValue,
                            $energySourceCostIds[$costId] ?? null
                        );
                    }
                }

                // clear cache for extended set
                DbObjectCache::freeObjectSet($Set);
            }

            /**
             * Save regular service costs
             */
            // admin mode
            if ($isAdminMode) {
                foreach ([300, 400, 500] as $code) {
                    foreach ($Set = LccCostSet::findRegularService($versionId, $code, $projectId) as $lccCost) {
                        $costId = $lccCost->getId();

                        $maintenancePerc = ElcaNumberFormat::fromString(
                            $this->Request->maintenancePerc[$costId],
                            4,
                            true
                        );
                        $servicePerc     = ElcaNumberFormat::fromString(
                            $this->Request->servicePerc[$costId],
                            4,
                            true
                        );

                        $RegCost = LccRegularServiceCost::findByCostId($costId);
                        $RegCost->setMaintenancePerc($maintenancePerc ? $maintenancePerc : 0);
                        $RegCost->setServicePerc($servicePerc ? $servicePerc : 0);
                        $RegCost->update();
                    }
                }

                // clear cache for extended set
                DbObjectCache::freeObjectSet($Set);
            }

            /**
             * Check version change
             *
             * if version has changed...
             */
            if ($ProjectVersion->getVersionId() != $newVersionId) {
                /**
                 * ... this should fire the `trigger_lcc_on_version_update_also_update_project_costs'
                 * on next update which will do the rest
                 */
                $ProjectVersion->setVersionId($newVersionId);
            }

            /**
             * Finally update
             */
            $ProjectVersion->update();

            /**
             * Check validator and add error messages
             */
            if ($Validator->isValid()) {
                /**
                 * Compute results
                 */
                $ProjectVersion->computeLcc();
            } else {
                foreach (array_unique($Validator->getErrors()) as $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }

            $this->defaultAction(false, !$Validator->isValid() ? $Validator : null, $isAdminMode);
        } elseif ($this->Request->has('setAdminMode')) {
            $this->defaultAction(false, null, true);
        } elseif ($this->Request->has('cancel')) {
            $this->defaultAction(false);
        } else {
            $this->defaultAction(false, null, false, $this->Request->versionId);
        }
    }
    // End saveAction


    /**
     * Deletes project costs
     *
     * @param  -
     * @return -
     */
    protected function deleteAction()
    {
        if (!$this->isAjax() || !is_numeric($this->Request->id)) {
            return;
        }

        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $Cost = LccCost::findById($this->Request->id);
        if (!$Cost->isInitialized() || !$Cost->getProjectId()) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            // find and re-compute all affected project variants
            $projectVariantIds = LccProjectCostSet::findCostsNotNull(
                $Cost->getId(),
                LccModule::CALC_METHOD_DETAILED
            )->getArrayBy('projectVariantId', 'projectVariantId');

            // delete the item
            $Cost->delete();

            foreach ($projectVariantIds as $projectVariantId) {
                $ProjectVersion = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_DETAILED);
                $ProjectVersion->computeLcc();
            }

            $this->defaultAction(false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $count = LccProjectCostSet::countCostsNotNull(
                $Cost->getId(),
                LccModule::CALC_METHOD_DETAILED,
                $this->Elca->getProjectVariantId()
            );

            if ($count > 0) {
                $message = t(
                    'Diese Kostengruppe wird noch in mindestens einer anderen Projektvariante verwendet! Sind Sie sicher, dass Sie alle damit verbundenen Werte löschen wollen?'
                );
            } else {
                $message = t('Soll diese Kostengruppe wirklich gelöscht werden?');
            }

            $this->messages->add($message, ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteAction
}
// End DetailedCtrl