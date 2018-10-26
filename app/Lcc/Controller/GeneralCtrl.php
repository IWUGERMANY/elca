<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Lcc\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\DbObjectCache;
use Beibob\Blibs\Url;
use Beibob\Blibs\Validator;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProjectConstruction;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaProjectNavigationLeftView;
use Exception;
use Lcc\Db\LccCost;
use Lcc\Db\LccCostSet;
use Lcc\Db\LccEnergySourceCost;
use Lcc\Db\LccIrregularCost;
use Lcc\Db\LccProjectCost;
use Lcc\Db\LccProjectCostSet;
use Lcc\Db\LccProjectTotalSet;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccRegularCost;
use Lcc\Db\LccRegularServiceCost;
use Lcc\Db\LccVersion;
use Lcc\LccModule;
use Lcc\View\LccGeneralView;

/**
 * Project data controller
 *
 * @package lcc
 * @author  Tobias Lode <tobias@beibob.de>
 */
class GeneralCtrl extends AppCtrl
{
    /**
     * Will be called on initialization
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init();
    }
    // End init


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
        $ProjectVersion      = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_GENERAL);

        // initial version to recent version or saved one
        if ($newVersionId) {
            $LccVersion = LccVersion::findById($newVersionId);
        } elseif ($ProjectVersion->isInitialized()) {
            $LccVersion = $ProjectVersion->getVersion();
        } else {
            $LccVersion = LccVersion::findRecent(LccModule::CALC_METHOD_GENERAL);
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
            $Data->category     = $ProjectVersion->getCategory();

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
        foreach (
            LccProjectCostSet::find(
                ['project_variant_id' => $projectVariantId, 'calc_method' => LccModule::CALC_METHOD_GENERAL]
            ) as $ProjectCost
        ) {
            $key                            = $ProjectCost->getCostId();
            $Data->quantity[$key]           = $ProjectCost->getQuantity();
            $Data->refValue[$key]           = $ProjectCost->getRefValue();
            if ($newVersionId) {
                $Data->energySourceCostId[$key] = LccEnergySourceCost::findByVersionIdAndName(
                    $newVersionId,
                    $ProjectCost->getEnergySourceCost()->getName()
                )->getId();
            } else {
                $Data->energySourceCostId[$key] = $ProjectCost->getEnergySourceCostId();
            }
        }

        // add totals
        foreach (
            LccProjectTotalSet::find(
                ['project_variant_id' => $projectVariantId, 'calc_method' => LccModule::CALC_METHOD_GENERAL]
            ) as $ProjectTotal
        ) {
            $Data->sum[$ProjectTotal->getGrouping()] = $ProjectTotal->getCosts();
        }

        $View = $this->addView(new LccGeneralView());
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
            $Project         = Elca::getInstance()->getProject();
            $projectId       = $Project->getId();
            $projectLifeTime = $Project->getLifeTime();

            $projectVariantId = $this->Request->projectVariantId;
            $isAdminMode      = (bool)$this->Request->isAdminMode;
            $ProjectVersion   = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_GENERAL);
            $Validator        = new Validator($this->Request);

            $quantities          = $this->Request->getArray('quantity');
            $refValues           = $this->Request->getArray('refValue');
            $kguAlt              = $this->Request->getArray('kguAlt');
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
            $projectVersionCreated = false;
            if (!$ProjectVersion->isInitialized()) {
                $ProjectVersion        = LccProjectVersion::create(
                    $projectVariantId,
                    LccModule::CALC_METHOD_GENERAL,
                    $versionId
                );
                $projectVersionCreated = true;
            }

            if ($Validator->assertNotEmpty('category', null, t('Es muss eine Sonderbedingung gewählt werden'))) {
                $ProjectVersion->setCategory($this->Request->category);
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

            foreach ([300, 400, 500] as $dinCode) {
                if ($Validator->assertNotEmpty(
                    'costs' . $dinCode,
                    null,
                    t('Bitte Kosten für KG %dinCode% angeben', null, ['%dinCode%' => $dinCode])
                )) {
                    $fn = 'setCosts' . $dinCode;
                    $ProjectVersion->$fn(ElcaNumberFormat::fromString($this->Request->get('costs' . $dinCode)));
                }

                $fn       = 'setKgu' . $dinCode . 'Alt';
                $grouping = LccCost::GROUPING_KGU . $dinCode;
                $ProjectVersion->$fn(
                    isset($kguAlt[$grouping]) && $kguAlt[$grouping] ? ElcaNumberFormat::fromString($kguAlt[$grouping])
                        : null
                );
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

            if (!$projectVersionCreated) {
                /**
                 * Save regular cleaning, energy and water costs
                 */
                foreach ([LccCost::GROUPING_WATER, LccCost::GROUPING_ENERGY, LccCost::GROUPING_CLEANING] as $grouping) {
                    foreach ($lccCostSet = LccCostSet::findRegular($versionId, $grouping) as $lccCost) {
                        $costId = $lccCost->getId();

                        if (!isset($quantities[$costId])) {
                            continue;
                        }

                        $quantity = ElcaNumberFormat::fromString($quantities[$costId]);

                        if ($isAdminMode || isset($refValues[$costId])) {
                            $refValue = ElcaNumberFormat::fromString($refValues[$costId]);
                        } else {
                            $refValue = null;
                        }

                        $hasEnergySourceCosts  = isset($energySourceCostId[$costId]);
                        $refValueEditIsEnabled = ($isAdminMode && !$hasEnergySourceCosts) || LccCost::IDENT_EEG === $lccCost->getIdent(
                            );

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

                        $ProjectCost = LccProjectCost::findByPk(
                            $projectVariantId,
                            LccModule::CALC_METHOD_GENERAL,
                            $costId
                        );
                        if ($ProjectCost->isInitialized()) {
                            $energySourceCostId = $energySourceCostIds[$costId] ?? null;
                            if ($hasEnergySourceCosts && $ProjectVersion->getVersionId() != $newVersionId) {
                                $energySourceCostId = LccEnergySourceCost::findByVersionIdAndName(
                                    $newVersionId,
                                    LccEnergySourceCost::findById($energySourceCostIds[$costId])->getName()
                                )->getId();
                            }

                            $ProjectCost->setQuantity($quantity);
                            $ProjectCost->setRefValue($refValue);
                            $ProjectCost->setEnergySourceCostId($energySourceCostId);
                            $ProjectCost->update();
                        } else {
                            LccProjectCost::create(
                                $projectVariantId,
                                LccModule::CALC_METHOD_GENERAL,
                                $costId,
                                $quantity,
                                $refValue,
                                $energySourceCostIds[$costId] ?? null
                            );
                        }

                        if ($isAdminMode) {
                            $RegularCost = LccRegularCost::findByCostId($costId);
                            $RegularCost->setRefValue($refValue);
                            $RegularCost->update();
                        }
                    }

                    // clear cache for extended set
                    DbObjectCache::freeObjectSet($lccCostSet);
                }

                /**
                 * Save regular service costs
                 */
                foreach ([300, 400, 500] as $code) {
                    foreach ($lccCostSet = LccCostSet::findRegularService($versionId, $code, $projectId) as $lccCost) {
                        $costId = $lccCost->getId();

                        if (!isset($quantities[$costId])) {
                            continue;
                        }

                        $quantity = ElcaNumberFormat::fromString($quantities[$costId]);

                        $ProjectCost = LccProjectCost::findByPk(
                            $projectVariantId,
                            LccModule::CALC_METHOD_GENERAL,
                            $costId
                        );
                        if ($ProjectCost->isInitialized()) {
                            if ($quantity != $ProjectCost->getQuantity()) {
                                $ProjectCost->setQuantity($quantity);
                                $ProjectCost->update();
                            }
                        } else {
                            $ProjectCost = LccProjectCost::create(
                                $projectVariantId,
                                LccModule::CALC_METHOD_GENERAL,
                                $costId,
                                $quantity
                            );
                        }

                        // admin mode
                        if ($isAdminMode) {
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
                    DbObjectCache::freeObjectSet($lccCostSet);

                    $newKey = LccCost::GROUPING_KGR . $code . '_new';
                    if (isset($quantities[$newKey]) && !empty($quantities[$newKey])) {
                        $din276Code      = ElcaNumberFormat::fromString($this->Request->din276Code[$newKey], 0);
                        $labelText       = \trim($this->Request->label[$newKey]);
                        $quantity        = ElcaNumberFormat::fromString($quantities[$newKey]);
                        $maintenancePerc = ElcaNumberFormat::fromString(
                            $this->Request->maintenancePerc[$newKey],
                            4,
                            true
                        );
                        $servicePerc     = ElcaNumberFormat::fromString($this->Request->servicePerc[$newKey], 4, true);

                        $valid = true;
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $din276Code,
                            t('Eine Kostengruppennummer muss für neue Einträge angegeben werden.')
                        );
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $labelText,
                            t('Eine Bezeichnung muss für neue Einträge angegeben werden.')
                        );
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $quantity,
                            t('Ein Wert muss für neue Einträge angegeben werden.')
                        );

                        if ($valid) {
                            $Dbh = DbHandle::getInstance();
                            try {
                                $Dbh->begin();
                                $lccCost = LccCost::create(
                                    LccCost::GROUPING_KGR . $code,
                                    $din276Code,
                                    $labelText,
                                    null,
                                    null,
                                    $projectId
                                );
                                LccRegularServiceCost::create(
                                    $lccCost->getId(),
                                    $maintenancePerc ? $maintenancePerc : 0,
                                    $servicePerc ? $servicePerc : 0
                                );
                                LccProjectCost::create(
                                    $projectVariantId,
                                    LccModule::CALC_METHOD_GENERAL,
                                    $lccCost->getId(),
                                    $quantity
                                );
                                $Dbh->commit();

                                // delete values from request
                                $this->Request->din276Code = $this->Request->label = $this->Request->quantity = $this->Request->maintenancePerc = $this->Request->servicePerc = [$newKey => ''];

                            }
                            catch (Exception $Exception) {
                                $Dbh->rollback();
                                throw $Exception;
                            }
                        }
                    }
                }

                /**
                 * Save irregular costs
                 */
                foreach ([300, 400, 500] as $code) {
                    foreach ($lccCostSet = LccCostSet::findIrregular($versionId, $code, $projectId) as $lccCost) {
                        $costId = $lccCost->getId();

                        if (!isset($quantities[$costId])) {
                            continue;
                        }

                        $quantity = ElcaNumberFormat::fromString($quantities[$costId]);

                        $ProjectCost = LccProjectCost::findByPk(
                            $projectVariantId,
                            LccModule::CALC_METHOD_GENERAL,
                            $costId
                        );
                        if ($ProjectCost->isInitialized()) {
                            if ($quantity != $ProjectCost->getQuantity()) {
                                $ProjectCost->setQuantity($quantity);
                                $ProjectCost->update();
                            }
                        } else {
                            $ProjectCost = LccProjectCost::create(
                                $projectVariantId,
                                LccModule::CALC_METHOD_GENERAL,
                                $costId,
                                $quantity
                            );
                        }

                        // admin mode
                        if ($isAdminMode) {
                            $lifeTime = ElcaNumberFormat::fromString($this->Request->lifeTime[$costId], 0);

                            $IrregCost = LccIrregularCost::findByCostId($costId);
                            $IrregCost->setLifeTime($lifeTime ? $lifeTime : $projectLifeTime);
                            $IrregCost->update();
                        }
                    }

                    // clear cache for extended set
                    DbObjectCache::freeObjectSet($lccCostSet);

                    $newKey = LccCost::GROUPING_KGU . $code . '_new';
                    if (isset($quantities[$newKey]) && !empty($quantities[$newKey])) {
                        $din276Code = ElcaNumberFormat::fromString($this->Request->din276Code[$newKey], 0);
                        $labelText  = \trim($this->Request->label[$newKey]);
                        $quantity   = ElcaNumberFormat::fromString($quantities[$newKey]);
                        $lifeTime   = ElcaNumberFormat::fromString($this->Request->lifeTime[$newKey], 0);

                        $valid = true;
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $din276Code,
                            t('Eine Kostengruppennummer muss für neue Einträge angegeben werden.')
                        );
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $labelText,
                            t('Eine Bezeichnung muss für neue Einträge angegeben werden.')
                        );
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $lifeTime,
                            t('Eine Nutzungsdauer muss für neue Einträge angegeben werden.')
                        );
                        $valid &= $Validator->assertNotEmpty(
                            'din276Code[' . $newKey . ']',
                            $quantity,
                            t('Ein Wert muss für neue Einträge angegeben werden.')
                        );

                        if ($valid) {
                            $Dbh = DbHandle::getInstance();
                            try {
                                $Dbh->begin();
                                $lccCost = LccCost::create(
                                    LccCost::GROUPING_KGU . $code,
                                    $din276Code,
                                    $labelText,
                                    null,
                                    null,
                                    $projectId
                                );
                                LccIrregularCost::create($lccCost->getId(), $lifeTime ? $lifeTime : $projectLifeTime);
                                LccProjectCost::create(
                                    $projectVariantId,
                                    LccModule::CALC_METHOD_GENERAL,
                                    $lccCost->getId(),
                                    $quantity
                                );
                                $Dbh->commit();

                                // delete values from request
                                $this->Request->din276Code = $this->Request->label = $this->Request->quantity = $this->Request->lifeTime = [$newKey => ''];
                            }
                            catch (Exception $Exception) {
                                $Dbh->rollback();
                                throw $Exception;
                            }
                        }
                    }

                }
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
                LccModule::CALC_METHOD_GENERAL
            )->getArrayBy('projectVariantId', 'projectVariantId');

            // delete the item
            $Cost->delete();

            foreach ($projectVariantIds as $projectVariantId) {
                $ProjectVersion = LccProjectVersion::findByPK($projectVariantId, LccModule::CALC_METHOD_GENERAL);
                $ProjectVersion->computeLcc();
            }

            $this->defaultAction(false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $count = LccProjectCostSet::countCostsNotNull(
                $Cost->getId(),
                LccModule::CALC_METHOD_GENERAL,
                $this->Elca->getProjectVariantId()
            );

            if ($count > 0) {
                $message = t(
                               'Diese Kostengruppe wird noch in %count% anderen %projectVariant% verwendet',
                               null,
                               [
                                   '%count%'          => $count > 1 ? $count : t('einer'),
                                   '%projectVariant%' => $count > 1 ? t('Projektvarianten') : t('Projektvariante'),
                               ]
                           )
                           . ' ' . t('Sind Sie sicher, dass Sie alle damit verbundenen Werte löschen wollen?');
            } else {
                $message = t('Soll diese Kostengruppe wirklich gelöscht werden?');
            }

            $this->messages->add($message, ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteAction
}
// End GeneralCtrl
