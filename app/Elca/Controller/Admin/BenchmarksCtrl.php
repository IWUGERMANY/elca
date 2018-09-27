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

namespace Elca\Controller\Admin;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Url;
use Beibob\Blibs\Validator;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaBenchmarkGroup;
use Elca\Db\ElcaBenchmarkGroupIndicator;
use Elca\Db\ElcaBenchmarkGroupIndicatorSet;
use Elca\Db\ElcaBenchmarkGroupSet;
use Elca\Db\ElcaBenchmarkGroupThreshold;
use Elca\Db\ElcaBenchmarkGroupThresholdSet;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecificationSet;
use Elca\Db\ElcaBenchmarkRefConstructionValue;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaBenchmarkRefProcessConfigSet;
use Elca\Db\ElcaBenchmarkSystem;
use Elca\Db\ElcaBenchmarkThreshold;
use Elca\Db\ElcaBenchmarkThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaBenchmarkVersionConstrClass;
use Elca\Db\ElcaBenchmarkVersionConstrClassSet;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaSetting;
use Elca\ElcaNumberFormat;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Admin\LifeCycleUsageSpecificationService;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\Validator\ElcaValidator;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkGroupsView;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkSystemsView;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkSystemView;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkVersionCommonView;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkVersionComputationView;
use Elca\View\Admin\Benchmark\ElcaAdminBenchmarkVersionLcaView;
use Elca\View\ElcaAdminBenchmarkProjectionsView;
use Elca\View\ElcaAdminReferenceProjectsConstructionView;
use Elca\View\ElcaProcessConfigSelectorView;
use Exception;

/**
 * Admin benchmarks
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class BenchmarksCtrl extends TabsCtrl
{
    use BenchmarksCtrlTrait;

    /**
     * Section name
     */
    const SETTING_SECTION = 'elca.admin.benchmarks';

    /**
     * Context
     */
    const CONTEXT = 'admin-benchmarks';
    const SETTING_SECTION_PROJECTIONS = 'elca.admin.benchmark.projections';
    const SETTING_SECTION_DIN_CODES = 'elca.admin.reference-projects';

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->Access->hasAdminPrivileges()) {
            $this->noAccessRedirect('/');
        }
    }
    // End init


    /**
     * default action
     */
    protected function defaultAction()
    {
        $this->systemsAction();
    }
    // End defaultAction


    /**
     * default action
     */
    protected function systemsAction($systemId = null, $addNavigationViews = true, Validator $Validator = null)
    {
        if (!$this->isAjax()) {
            return;
        }
        $systemId        = $systemId ? $systemId : $this->Request->id;
        $BenchmarkSystem = ElcaBenchmarkSystem::findById($systemId);

        if ($BenchmarkSystem->isInitialized()) {
            $Data              = new \stdClass();
            $Data->systemId    = $BenchmarkSystem->getId();
            $Data->name        = $BenchmarkSystem->getName();
            $Data->modelClass  = $BenchmarkSystem->getModelClass();
            $Data->description = $BenchmarkSystem->getDescription();
            $Data->isActive    = $BenchmarkSystem->isActive();

            /**
             * Add Versions
             *
             * @var ElcaBenchmarkVersion $Version
             */
            foreach (
                ElcaBenchmarkVersionSet::findByBenchmarkSystemId(
                    $systemId,
                    ['process_db_id' => 'ASC', 'name' => 'ASC', 'id' => 'ASC']
                ) as $Version
            ) {
                $key                            = $Version->getId();
                $Data->versionName[$key]        = $Version->getName();
                $Data->versionProcessDbId[$key] = $Version->getProcessDbId();
                $Data->versionProcessDb[$key]   = $Version->getProcessDb()->getName();
                $Data->versionIsActive[$key]    = $Version->isActive();
            }

            $View = $this->setView(new ElcaAdminBenchmarkSystemView());
            $View->assign('Data', $Data);
            $View->assign('modelClasses', $this->container->get('elca.benchmark_systems'));


            if ($Validator) {
                $View->assign('Validator', $Validator);
            }

            /**
             * Render complete navigation on reload
             */
            if ($addNavigationViews) {
                $BenchmarkSystem = ElcaBenchmarkSystem::findById($systemId);

                $this->addNavigationView('systems');
                $this->Osit->add(new ElcaOsitItem(t('Systeme'), '/elca/admin/benchmarks/systems/', t('Benchmarks')));
                $this->Osit->add(new ElcaOsitItem($BenchmarkSystem->getName(), null, t('Benchmarksystem')));
            }
        } else {
            $this->setView(new ElcaAdminBenchmarkSystemsView());

            /**
             * Render complete navigation on reload
             */
            if ($addNavigationViews) {
                $this->addNavigationView('systems');
                $this->Osit->add(new ElcaOsitItem(t('Systeme'), null, t('Benchmarks')));
            }
        }
    }
    // End systemsAction


    /**
     * create benchmark system action
     */
    protected function createSystemAction(Validator $Validator = null)
    {
        if (!$this->isAjax()) {
            return;
        }

        $View = $this->setView(new ElcaAdminBenchmarkSystemView());
        $View->assign('Validator', $Validator);
    }
    // End createSystemAction


    /**
     * save system action
     */
    protected function saveBenchmarkSystemAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $BenchmarkSystem = ElcaBenchmarkSystem::findById($this->Request->benchmarkSystemId);

        if ($this->Request->has('save')) {
            $Validator = new ElcaValidator($this->Request);
            $Validator->assertNotEmpty('name', null, t('Dieses Feld ist ein Pflichtfeld'));

            /**
             * Validate versions
             */
            $versionNames = $this->Request->getArray('versionName');
            foreach ($versionNames as $key => $name) {
                $Validator->assertNotEmpty(
                    'versionName['.$key.']',
                    null,
                    t('Es muss ein Name für die Version eingetragen werden')
                );
                $Validator->assertNotEmpty(
                    'versionProcessDbId['.$key.']',
                    null,
                    t('Bitte wählen Sie eine Baustoff-Datenbank für die Version')
                );
            }

            if ($Validator->isValid()) {
                if ($BenchmarkSystem->isInitialized()) {
                    $BenchmarkSystem->setName($this->Request->name);
                    $BenchmarkSystem->setModelClass($this->Request->modelClass);
                    $BenchmarkSystem->setDescription($this->Request->description);
                    $BenchmarkSystem->setIsActive(($this->Request->has('isActive')));
                    $BenchmarkSystem->update();

                    /**
                     * Save versions
                     */
                    foreach ($versionNames as $key => $name) {
                        $version = ElcaBenchmarkVersion::findById($key);
                        $version->setName($name);

                        $reinitializeLifeCycleUsageSpecs = false;
                        if ($version->getProcessDbId() !== $this->Request->versionProcessDbId[$key]) {
                            $version->setProcessDbId($this->Request->versionProcessDbId[$key]);
                            $reinitializeLifeCycleUsageSpecs = true;
                        }
                        $version->update();

                        if ($reinitializeLifeCycleUsageSpecs) {
                            $this->container->get(LifeCycleUsageSpecificationService::class)
                                            ->initLifeCycleUsageSpecification($version);
                        }
                    }

                    $this->messages->add(t('Der Datensatz wurde aktualisiert'));
                } else {
                    $BenchmarkSystem = ElcaBenchmarkSystem::create(
                        $this->Request->name,
                        $this->Request->modelClass,
                        false,
                        $this->Request->description
                    );

                    $benchmarkVersion = ElcaBenchmarkVersion::create($BenchmarkSystem->getId(), 'Neu', null, false);
                    $this->container->get(BenchmarkService::class)->initWithDefaultValues($benchmarkVersion);

                    $this->messages->add('Der Datensatz wurde erstellt');
                }

                /**
                 * Update action and osit view
                 */
                $this->Response->setHeader(
                    'X-Update-Hash: /elca/admin/benchmarks/systems/?id='.$BenchmarkSystem->getId()
                );
                $Validator = null;

                $this->systemsAction($BenchmarkSystem->getId(), true);
            } else {
                foreach ($Validator->getErrors() as $error) {
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
                }

                if ($this->Request->benchmarkSystemId) {
                    $this->systemsAction($BenchmarkSystem->getId(), true, $Validator);
                } else {
                    $this->createSystemAction($Validator);
                }
            }
        } elseif ($this->Request->has('cancel')) {
            $this->systemsAction();
        }
    }
    // End saveSystemAction


    /**
     * Copies a benchmark system
     */
    protected function copySystemAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkSystem = ElcaBenchmarkSystem::findById($this->Request->id);

        $benchmarkSystemService = $this->get(BenchmarkSystemsService::class);
        $benchmarkSystemService->copySystem($benchmarkSystem);

        $this->systemsAction(null, false);
    }
    // End copySystemAction


    /**
     * Delete version
     */
    protected function deleteSystemAction()
    {
        if (!is_numeric($this->Request->id)) {
            return;
        }

        $System = ElcaBenchmarkSystem::findById($this->Request->id);

        /** check if system is used in any projects */
        if ($System->isUsedInProject()) {
            return $this->messages->add(
                t('Diese Version kann nicht gelöscht werden, da sie noch Projekten zugeordnet ist.'),
                ElcaMessages::TYPE_ERROR
            );
        }

        if ($this->Request->has('confirmed')) {
            if ($System->isInitialized()) {
                $Dbh = DbHandle::getInstance();
                try {
                    $Dbh->begin();
                    $System->delete();
                    $Dbh->commit();

                    $this->messages->add(t('Das Benchmarksystem wurde gelöscht'));
                } catch (Exception $Exception) {
                    $Dbh->rollback();
                    throw $Exception;
                }

                /**
                 * Forward to list
                 */
                $this->systemsAction(null, false);
            }
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t('Soll das Benchmarksystem wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End deleteSystemAction


    /**
     * Edit version
     *
     * @param bool      $addNavigationViews
     * @param Validator $Validator
     */
    protected function editVersionAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        /**
         * Default tab
         */
        $this->addTabItem(
            'common',
            t('Allgemein'),
            null,
            get_class($this),
            'editVersionCommon',
            ['id' => $this->Request->id]
        );

        if ($this->Request->id) {
            $this->addTabItem(
                'lca',
                t('LCA Punktwerte'),
                null,
                get_class($this),
                'editVersionThresholds',
                ['id' => $this->Request->id]
            );

            $this->addTabItem(
                'lca-groups',
                t('LCA Bewertung'),
                null,
                get_class($this),
                'editVersionGroups',
                ['id' => $this->Request->id]
            );

            $this->addTabItem(
                'computation',
                t('LCA Berechnungsgrundlage'),
                null,
                get_class($this),
                'editVersionComputation',
                ['id' => $this->Request->id]
            );

            foreach ($this->Elca->getAdditionalNavigations() as $navigation) {
                $tabs = (array)$navigation->getAdminBenchmarkVersionTabs($this->Request->id);

                foreach ($tabs as $tabItem) {
                    $this->addTabItemInstance($tabItem);
                }
            }

            $this->addTabItem(
                'referenceProjects',
                t('Referenzprojekte'),
                null,
                self::class,
                'referenceProjects',
                ['id' => $this->Request->id]
            );
            $this->addTabItem(
                'projections',
                t('Prognose'),
                null,
                self::class,
                'projections',
                ['id' => $this->Request->id]
            );
        }

        /**
         * invoke action controller
         */
        $this->invokeTabActionController();
    }


    /**
     * Edit version
     *
     * @param bool      $addNavigationViews
     * @param Validator $Validator
     */
    protected function editVersionCommonAction($addNavigationViews = true)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        $data                 = new \stdClass();
        $data->constrClassIds = ElcaBenchmarkVersionConstrClassSet::findByBenchmarkVersionId($benchmarkVersionId)
                                                                  ->getArrayBy('constrClassId');

        $view = $this->setView(new ElcaAdminBenchmarkVersionCommonView());
        $view->assign('benchmarkVersionId', $benchmarkVersionId);
        $view->assign('activeTabIdent', 'common');
        $view->assign('data', $data);

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }

    protected function editVersionThresholdsAction($addNavigationViews = true, Validator $Validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $BenchmarkVersion = ElcaBenchmarkVersion::findById($this->Request->id);

        $View = $this->setView(new ElcaAdminBenchmarkVersionLcaView());
        $View->assign('benchmarkVersionId', $this->Request->id);
        $View->assign('Validator', $Validator);
        $View->assign('activeTabIdent', 'lca');

        // build data object
        $Data                    = $View->assign('Data', new \stdClass());
        $Data->processDbId       = $BenchmarkVersion->getProcessDbId();
        $Data->useReferenceModel = $BenchmarkVersion->getUseReferenceModel();

        $refProcessConfigs = ElcaBenchmarkRefProcessConfigSet::find(
            ['benchmark_version_id' => $BenchmarkVersion->getId()]
        )->getArrayBy('processConfigId', 'ident');
        foreach (
            [
                ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY,
                ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY,
            ] as $ident
        ) {
            $Data->refProcessConfigId[$ident] = isset($refProcessConfigs[$ident]) ? $refProcessConfigs[$ident] : null;
        }

        $IndicatorSet          = ElcaIndicatorSet::findWithPetByProcessDbId(
            $BenchmarkVersion->getProcessDbId(),
            false,
            false,
            ['p_order' => 'ASC']
        );
        $refConstructionValues = ElcaBenchmarkRefConstructionValueSet::find(
            ['benchmark_version_id' => $BenchmarkVersion->getId()]
        )->getArrayBy('value', 'indicatorId');

        foreach ($refConstructionValues as $indicatorId => $value) {
            $Data->refConstrValue[$indicatorId] = $value;
        }

        /** @var ElcaIndicator $Indicator */
        foreach ($IndicatorSet as $Indicator) {
            $ident          = $Indicator->getIdent();
            $indicatorIdent = new IndicatorIdent($ident);
            $values         = [];

            /** @var ElcaBenchmarkThreshold $Threshold */
            foreach (
                ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorId(
                    $BenchmarkVersion->getId(),
                    $Indicator->getId()
                ) as $Threshold
            ) {
                // display MJ in kWh, except renewable pe is edited in %
                if ($indicatorIdent->isPrimaryEnergyIndicator() && false === $indicatorIdent->isRenewablePrimaryEnergy(
                    )) {
                    $values[$Threshold->getScore()] = $Threshold->getValue() / 3.6;
                } else {
                    $values[$Threshold->getScore()] = $Threshold->getValue();
                }
            }

            // threshold values
            $Data->$ident = $values;

            // ref construction values
            $Data->refConstrValue[$ident] = isset($refConstructionValues[$Indicator->getId()])
                ? $refConstructionValues[$Indicator->getId()] : null;
        }

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit($BenchmarkVersion);
        }
    }

    protected function editVersionComputationAction($addNavigationViews = true, Validator $Validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersion = ElcaBenchmarkVersion::findById($this->Request->id);

        $lcaSpecificationSet = ElcaBenchmarkLifeCycleUsageSpecificationSet::findByBenchmarkVersionId(
            $benchmarkVersion->getId()
        )->getArrayCopy('lifeCycleIdent');

        $data = new \stdClass();

        foreach ($lcaSpecificationSet as $lcIdent => $spec) {
            $data->construction[$lcIdent] = $spec->getUseInConstruction() || $spec->getUseInEnergyDemand();
            $data->maintenance[$lcIdent]  = $spec->getUseInMaintenance();
        }

        $view = $this->setView(new ElcaAdminBenchmarkVersionComputationView());
        $view->assign('benchmarkVersionId', $this->Request->id);
        $view->assign('Validator', $Validator);
        $view->assign('activeTabIdent', 'computation');
        $view->assign('data', $data);

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit($benchmarkVersion);
        }
    }

    protected function editVersionGroupsAction($addNavigationViews = true, Validator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id || !\is_numeric($this->Request->id)) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;
        $data               = new \stdClass();
        $groups             = ElcaBenchmarkGroupSet::find(
            ['benchmark_version_id' => $benchmarkVersionId],
            ['name' => 'ASC']
        );

        foreach ($groups as $benchmarkGroup) {
            $key                    = $benchmarkGroup->getId();
            $data->name[$key]       = $benchmarkGroup->getName();
            $data->indicators[$key] = [];

            /**
             * @var ElcaBenchmarkGroupIndicator $benchmarkGroupIndicator
             */
            foreach (ElcaBenchmarkGroupIndicatorSet::findByGroupId($key, null, null, null, true) as $benchmarkGroupIndicator) {
                $data->indicators[$key][] = $benchmarkGroupIndicator->getIndicatorId();
            }

            /**
             * @var ElcaBenchmarkGroupThreshold $benchmarkGroupIndicator
             */
            foreach (ElcaBenchmarkGroupThresholdSet::findByGroupId($key) as $benchmarkGroupThreshold) {
                $thresholdKey = $key.'-'.$benchmarkGroupThreshold->getId();

                $data->score[$thresholdKey]   = $benchmarkGroupThreshold->getScore();
                $data->caption[$thresholdKey] = $benchmarkGroupThreshold->getCaption();
            }
        }

        $view = $this->setView(new ElcaAdminBenchmarkGroupsView());
        $view->assign('benchmarkVersionId', $benchmarkVersionId);
        $view->assign('data', $data);
        $view->assign('activeTabIdent', 'lca-groups');

        if (null !== $validator) {
            $view->assign('validator', $validator);
        }

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }

    protected function saveBenchmarkVersionCommonAction()
    {
        if (!$this->Request->isPost() || !$this->Request->id) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        if ($this->Request->has('save')) {
            $constrClassIds = $this->Request->getArray('constrClassIds');

            $oldConstrClassIds = ElcaBenchmarkVersionConstrClassSet::findByBenchmarkVersionId($benchmarkVersionId)
                                                                   ->getArrayBy('id', 'constrClassId');

            $dbHandle = DbHandle::getInstance();

            try {
                $dbHandle->begin();

                foreach ($constrClassIds as $constrClassId) {
                    if (isset($oldConstrClassIds[(int)$constrClassId])) {
                        unset($oldConstrClassIds[(int)$constrClassId]);
                        continue;
                    }

                    ElcaBenchmarkVersionConstrClass::create($benchmarkVersionId, $constrClassId);
                }

                foreach ($oldConstrClassIds as $constrClassId => $benchmarkVersionConstrClassId) {
                    ElcaBenchmarkVersionConstrClass::findById($benchmarkVersionConstrClassId)->delete();
                }

                $this->messages->add('Die Änderungen wurden übernommen');

                $dbHandle->commit();
            } catch (\Exception $exception) {
                $dbHandle->rollback();
                throw $exception;
            }
        }
    }

    /**
     * Saves a benchmark version
     */
    protected function saveBenchmarkVersionThresholdsAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        if ($this->Request->has('save')) {
            $versionId        = $this->Request->id;
            $benchmarkVersion = ElcaBenchmarkVersion::findById($versionId);

            $indicatorSet = ElcaIndicatorSet::findWithPetByProcessDbId(
                $benchmarkVersion->getProcessDbId(),
                false,
                false,
                ['p_order' => 'ASC']
            );

            /**
             * Validate inputs
             */
            $validator = new ElcaValidator($this->Request);

            /** @var ElcaIndicator $Indicator */
            foreach ($indicatorSet as $Indicator) {
                $property      = $Indicator->getIdent();
                $thresholds    = $this->Request->getArray($property);
                $countNotEmpty = $maxValue = 0;
                foreach ($thresholds as $score => $value) {
                    if ($value) {
                        $countNotEmpty++;
                        if ($value > $maxValue) {
                            $maxValue = $value;
                        }
                    }
                }

                // skip if empty
                if (!$countNotEmpty) {
                    continue;
                }

                $validator->assertTrue(
                    $property,
                    $countNotEmpty > 1,
                    t(
                        'Für `%name%\' muss mindestens ein Minimum und ein Maximum spezifiziert werden!',
                        null,
                        ['%name%' => $Indicator->getName()]
                    )
                );

                if (in_array($property, ElcaIndicator::$primaryEnergyRenewableIndicators)) {
                    $validator->assertTrue(
                        $property,
                        $maxValue <= 50,
                        t(
                            'Der Prozentwert für `%indName%\' darf 50 nicht überschreiten!',
                            null,
                            ['%indName%' => $Indicator->getName()]
                        )
                    );
                }
            }

            if ($validator->isValid()) {
                $useRefModel = (bool)$this->Request->useReferenceModel;

                if ($benchmarkVersion->getUseReferenceModel() != $useRefModel) {
                    $benchmarkVersion->setUseReferenceModel($useRefModel);
                    $benchmarkVersion->update();
                }

                /**
                 * save ref process configs
                 */
                if ($useRefModel) {
                    $processConfigIds = $this->Request->getArray('refProcessConfigId');
                    foreach (
                        [
                            ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                            ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY,
                            ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY,
                        ] as $ident
                    ) {
                        $bnbRefProcessConfig = ElcaBenchmarkRefProcessConfig::findByPk(
                            $benchmarkVersion->getId(),
                            $ident
                        );
                        if ($bnbRefProcessConfig->isInitialized()) {

                            if ($processConfigIds[$ident]) {
                                $bnbRefProcessConfig->setProcessConfigId($processConfigIds[$ident]);
                                $bnbRefProcessConfig->update();
                            } else {
                                $bnbRefProcessConfig->delete();
                            }
                        } else {
                            ElcaBenchmarkRefProcessConfig::create(
                                $benchmarkVersion->getId(),
                                $ident,
                                $processConfigIds[$ident]
                            );
                        }
                    }

                    /**
                     * Save constr values
                     */
                    $refConstrValues = $this->Request->getArray('refConstrValue');
                    foreach ($refConstrValues as $indicatorId => $value) {
                        $value             = ElcaNumberFormat::fromString($value);
                        $BnbRefConstrValue = ElcaBenchmarkRefConstructionValue::findByPk(
                            $benchmarkVersion->getId(),
                            $indicatorId
                        );
                        if ($BnbRefConstrValue->isInitialized()) {
                            $BnbRefConstrValue->setValue($value);
                            $BnbRefConstrValue->update();
                        } else {
                            ElcaBenchmarkRefConstructionValue::create($benchmarkVersion->getId(), $indicatorId, $value);
                        }
                    }
                }

                /** @var ElcaIndicator $Indicator */
                foreach ($indicatorSet as $Indicator) {
                    $property       = $Indicator->getIdent();
                    $indicatorId    = $Indicator->getId();
                    $indicatorIdent = new IndicatorIdent($property);

                    /** @var array $thresholds */
                    $thresholds = $this->Request->getArray($property);
                    foreach ($thresholds as $score => $value) {
                        $value = ElcaNumberFormat::fromString($value);

                        $Threshold = ElcaBenchmarkThreshold::findByBenchmarkVersionIdAndIndicatorIdAndScore(
                            $versionId,
                            $indicatorId,
                            $score
                        );
                        if ($Threshold->isInitialized()) {

                            if ($value) {

                                /** MJ indicators (except renewable pe) are displayed and edited in kWh,
                                 * therefor convert it back into MJ
                                 */
                                if ($indicatorIdent->isPrimaryEnergyIndicator(
                                    ) && false === $indicatorIdent->isRenewablePrimaryEnergy()) {
                                    $value *= 3.6;
                                }

                                $Threshold->setValue($value);
                                $Threshold->update();
                            } else {
                                $Threshold->delete();
                            }
                        } else {
                            ElcaBenchmarkThreshold::create($versionId, $indicatorId, $score, $value);
                        }
                    }
                }
                $this->messages->add(t('Die Daten wurden gespeichert'));
            } else {
                foreach ($validator->getErrors() as $error) {
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
                }

                $this->editVersionThresholdsAction(false, $validator);

                return;
            }
        }

        $this->editVersionThresholdsAction(false);
    }

    protected function saveBenchmarkVersionGroupsAction()
    {
        if (!$this->Request->isPost() || !$this->Request->id) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        if ($this->Request->has('saveGroups')) {

            $validator = new ElcaValidator($this->Request);
            $validator->assertBenchmarkGroups();

            $names  = $this->Request->getArray('name');
            $scores = $this->Request->getArray('score');

            if ($validator->isValid()) {
                $dbHandle = DbHandle::getInstance();
                try {
                    $dbHandle->begin();

                    foreach ($names as $key => $foo) {
                        $benchmarkGroup = $this->saveBenchmarkGroup($benchmarkVersionId, $key);

                        $found = 0;
                        foreach ($scores as $relId => $bar) {
                            list($groupKey, $thresholdKey) = explode('-', $relId);

                            if ($groupKey != $key) {
                                continue;
                            }

                            $this->saveBenchmarkGroupThreshold($benchmarkGroup, $groupKey, $thresholdKey);
                            $found++;
                        }

                        if (!$found) {
                            throw new Exception('Tried to create transport without transport means');
                        }
                    }

                    $dbHandle->commit();
                } catch (Exception $exception) {
                    $dbHandle->rollback();
                    throw $exception;
                }

                $this->messages->add(t('Die Daten wurden gespeichert'));
                $this->editVersionGroupsAction(false);
            } else {
                $addNewThresholdFor = null;
                foreach ($validator->getErrors() as $property => $msg) {
                    $this->messages->add(t($msg), ElcaMessages::TYPE_ERROR);
                }
                $this->editVersionGroupsAction(false, $validator);
                $View = $this->getViewByName(ElcaAdminBenchmarkGroupsView::class);
                $View->assign('addNewGroup', isset($names['new']));
            }
        } elseif ($this->Request->has('addGroup')) {
            $this->editVersionGroupsAction(false);
            $View = $this->getViewByName(ElcaAdminBenchmarkGroupsView::class);
            $View->assign('addNewGroup', true);
        }
    }

    /**
     * Saves a benchmark version
     */
    protected function saveBenchmarkVersionComputationAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        if ($this->Request->has('save')) {
            $versionId        = $this->Request->id;
            $benchmarkVersion = ElcaBenchmarkVersion::findById($versionId);
            $constrSettings   = $this->Request->getArray('construction');
            $constrSettings   = $this->validateLifeCycleUsageSettings($constrSettings);
            $maintSettings    = $this->Request->getArray('maintenance');
            $maintSettings    = $this->validateLifeCycleUsageSettings($maintSettings);

            $validator = new ElcaValidator($this->Request);

            $lifeCycles = ElcaLifeCycleSet::findByProcessDbId($benchmarkVersion->getProcessDbId())
                                          ->getArrayCopy('ident');

            $allLcIdents = array_merge(
                ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
                ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
                ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
            );

            if ($validator->isValid()) {
                foreach ($allLcIdents as $lcIdent => $foo) {
                    if (!isset($lifeCycles[$lcIdent])) {
                        continue;
                    }

                    $spec = ElcaBenchmarkLifeCycleUsageSpecification::findByBenchmarkVersionIdAndLifeCycleIdent(
                        $versionId,
                        $lcIdent
                    );

                    $useInConstr = isset(ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent]) &&
                                   isset($constrSettings[$lcIdent]) && $constrSettings[$lcIdent];
                    $useInMaint  = isset(ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent]) &&
                                   isset($maintSettings[$lcIdent]) && $maintSettings[$lcIdent];
                    $useInEnergy = isset(ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]) &&
                                   isset($constrSettings[$lcIdent]) && $constrSettings[$lcIdent];

                    if ($spec->isInitialized()) {
                        $spec->setUseInConstruction($useInConstr);
                        $spec->setUseInMaintenance($useInMaint);
                        $spec->setUseInEnergyDemand($useInEnergy);
                        $spec->update();
                    } else {
                        ElcaBenchmarkLifeCycleUsageSpecification::create(
                            $versionId,
                            $lcIdent,
                            $useInConstr,
                            $useInMaint,
                            $useInEnergy
                        );
                    }
                }

                $this->messages->add(t('Die Daten wurden gespeichert'));
            } else {
                foreach ($validator->getErrors() as $error) {
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
                }

                $this->editVersionComputationAction(false, $validator);

                return;
            }
        }

        $this->editVersionComputationAction(false);
    }

    /**
     *
     * @param $benchmarkVersionId
     * @param $key
     *
     * @return ElcaBenchmarkGroup
     */
    protected function saveBenchmarkGroup($benchmarkVersionId, $key)
    {
        if (!isset($this->Request->name[$key])) {
            return null;
        }

        $name          = \trim($this->Request->name[$key]);
        $allIndicators = $this->Request->getArray('indicators');
        $indicators    = $allIndicators[$key];

        if (is_numeric($key)) {
            $benchmarkGroup = ElcaBenchmarkGroup::findById($key);

            $benchmarkGroup->setName($name);
            $benchmarkGroup->update();
        } else {
            $benchmarkGroup = ElcaBenchmarkGroup::create(
                $benchmarkVersionId,
                $name
            );
        }

        $oldIndicators = ElcaBenchmarkGroupIndicatorSet::findByGroupId($benchmarkGroup->getId())->getArrayBy(
            'indicatorId',
            'indicatorId'
        );

        foreach ($indicators as $indicatorId) {
            if (isset($oldIndicators[(int)$indicatorId])) {
                unset($oldIndicators[(int)$indicatorId]);
                continue;
            }

            ElcaBenchmarkGroupIndicator::create($benchmarkGroup->getId(), $indicatorId);
        }
        foreach ($oldIndicators as $oldIndicatorId) {
            ElcaBenchmarkGroupIndicator::findByPk($benchmarkGroup->getId(), $oldIndicatorId)->delete();
        }

        return $benchmarkGroup;
    }

    /**
     * Saves a transport with all its tranport means
     *
     * @param ElcaBenchmarkGroup   $benchmarkGroup
     * @param                      $groupKey
     * @param                      $thresholdKey
     * @return void
     */
    protected function saveBenchmarkGroupThreshold(ElcaBenchmarkGroup $benchmarkGroup, $groupKey, $thresholdKey)
    {
        $key = $groupKey.'-'.$thresholdKey;

        if (!isset($this->Request->score[$key]) || !$this->Request->score[$key]) {
            return;
        }

        $score   = (int)$this->Request->score[$key];
        $caption = \trim($this->Request->caption[$key]);

        if (is_numeric($thresholdKey)) {
            $groupThreshold = ElcaBenchmarkGroupThreshold::findById($thresholdKey);

            $groupThreshold->setScore($score);
            $groupThreshold->setCaption($caption);
            $groupThreshold->update();

        } else {
            ElcaBenchmarkGroupThreshold::create(
                $benchmarkGroup->getId(),
                $score,
                $caption
            );
        }
    }


    protected function addGroupThresholdAction()
    {
        if (!$this->Request->id || !$this->Request->groupId) {
            return;
        }

        $groupId = $this->Request->groupId;

        $this->editVersionGroupsAction(false);
        $View = $this->getViewByName(ElcaAdminBenchmarkGroupsView::class);
        $View->assign('groupId', $groupId);
        $View->assign('addNewGroupThreshold', true);
    }

    protected function deleteGroupThresholdAction()
    {
        if (!$this->Request->id || !$this->Request->thresholdId) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $benchmarkGroupThreshold = ElcaBenchmarkGroupThreshold::findById($this->Request->thresholdId);
        if (!$benchmarkGroupThreshold->isInitialized()) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            if (ElcaBenchmarkGroupThresholdSet::dbCount(
                    ['group_id' => $benchmarkGroupThreshold->getGroupId()]
                ) - 1 > 0
            ) {
                $benchmarkGroupThreshold->delete();
            } else {
                $benchmarkGroupThreshold->getGroup()->delete();
            }

            $this->editVersionGroupsAction(false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            if (ElcaBenchmarkGroupThresholdSet::dbCount(
                    ['group_id' => $benchmarkGroupThreshold->getGroupId()]
                ) - 1 > 0) {
                $msg = t(
                    'Bewertung "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $benchmarkGroupThreshold->getCaption()]
                );
            } else {
                $msg = t(
                    'Gruppe "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $benchmarkGroupThreshold->getGroup()->getName()]
                );
            }

            $this->messages->add(
                $msg,
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }


    /**
     * Activates a version
     */
    protected function activateVersionAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $Version = ElcaBenchmarkVersion::findById($this->Request->id);
        $Version->setIsActive(!$Version->isActive());
        $Version->update();

        $this->systemsAction($Version->getBenchmarkSystemId(), false);
    }
    // End activateVersionAction


    /**
     * Creates a new version
     */
    protected function createVersionAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $version = ElcaBenchmarkVersion::create($this->Request->id, t('Neu'));
        $this->container->get(BenchmarkService::class)->initWithDefaultValues($version);

        $this->systemsAction($this->Request->id, false);
    }
    // End createVersionAction


    /**
     * Copy version
     */
    protected function copyVersionAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $version = ElcaBenchmarkVersion::findById($this->Request->id);

        $benchmarkSystemService = $this->get(BenchmarkSystemsService::class);
        $benchmarkSystemService->copyVersion($version);

        $this->systemsAction($version->getBenchmarkSystemId(), false);
    }
    // End copyVersionAction


    /**
     * Delete version
     */
    protected function deleteVersionAction()
    {
        if (!is_numeric($this->Request->id)) {
            return;
        }

        /**
         * Check if user has admin privileges
         */
        $Version  = ElcaBenchmarkVersion::findById($this->Request->id);
        $systemId = $Version->getBenchmarkSystemId();

        if (ElcaBenchmarkVersionSet::dbCount(['benchmark_system_id' => $systemId]) < 2) {
            return $this->messages->add(t('Die letzte Version kann nicht gelöscht werden.'), ElcaMessages::TYPE_ERROR);
        }

        if ($this->Request->has('confirmed')) {
            if ($Version->isInitialized()) {
                $Dbh = DbHandle::getInstance();
                try {
                    $Dbh->begin();
                    $Version->delete();
                    $Dbh->commit();

                    $this->messages->add(t('Die Version wurde gelöscht'));
                } catch (Exception $Exception) {
                    $Dbh->rollback();
                    throw $Exception;
                }

                /**
                 * Forward to list
                 */
                $this->systemsAction($systemId, false);
            }
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t('Soll die Version wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End deleteVersionAction

    /**
     * Action selectProcessConfig
     */
    protected function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if (isset($this->Request->term)) {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $inUnit   = $this->Request->has('u') ? $this->Request->get('u') : null;
            $Results  = ElcaProcessConfigSearchSet::findByKeywords(
                $keywords,
                $this->Elca->getLocale(),
                $inUnit,
                !$this->Access->hasAdminPrivileges()
            );

            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name.' > '.$Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        } /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!isset($this->Request->select)) {
            $processConfigId = null;
            if ($this->Request->sp) {
                $processConfigId = $this->Request->sp;
            } elseif ($this->Request->id) {
                $processConfigId = $this->Request->id;
            } elseif ($this->Request->p) {
                $processConfigId = $this->Request->p;
            }

            if ($processConfigId == 'NULL') {
                $processConfigId = null;
            }

            $View = $this->setView(new ElcaProcessConfigSelectorView());
            $View->assign('processConfigId', $processConfigId);
            $View->assign('relId', $this->Request->relId);
            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $View->assign('data', $this->Request->data);
            $View->assign('buildMode', $this->Request->b);
            $View->assign('context', self::CONTEXT);
            $View->assign('allowDeselection', true);
        } /**
         * If user pressed select button, assign the new process
         */
        elseif (isset($this->Request->select)) {
            /**
             * Set view
             */
            $benchmarkVersionId = $this->Request->relId;
            $ident              = $this->Request->data;

            // in id is the newProcessConfigId, in p the old
            $processConfigId = $this->Request->id ? $this->Request->id : null;
            if ($processConfigId == 'NULL') {
                $processConfigId = null;
            }

            $View = $this->setView(new ElcaAdminBenchmarkVersionLcaView());
            $View->assign('buildMode', 'selector');
            $View->assign('Data', (object)['refProcessConfigId' => [$ident => $processConfigId]]);
            $View->assign('benchmarkVersionId', $benchmarkVersionId);
        }
    }

    protected function referenceProjectsAction(bool $addNavigationViews = true, ElcaValidator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;
        $data = $this->buildReferenceProjectsData($benchmarkVersionId, self::SETTING_SECTION_DIN_CODES, 1);

        $view = $this->setView(new ElcaAdminReferenceProjectsConstructionView());
        $view->assign('activeTabIdent', 'referenceProjects');
        $view->assign('data', $data);

        if ($validator) {
            $view->assign('validator', $validator);
        }

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }

    protected function saveReferenceProjectsAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        $validator = null;

        if ($this->Request->has('save')) {
            $benchmarkVersion = ElcaBenchmarkVersion::findById($benchmarkVersionId);
            $section          = BenchmarksCtrl::SETTING_SECTION_DIN_CODES.'.'.$benchmarkVersion->getId();

            $validator = new ElcaValidator($this->Request);

            $min = $this->Request->getArray('min');
            $avg = $this->Request->getArray('avg');
            $max = $this->Request->getArray('max');

            foreach ($avg as $key => $foo) {
                $minValue = ElcaNumberFormat::fromString($min[$key]);
                $avgValue = ElcaNumberFormat::fromString($avg[$key]);
                $maxValue = ElcaNumberFormat::fromString($max[$key]);

                if (!is_numeric($minValue) && !is_numeric($avgValue) && !is_numeric($maxValue)) {
                    continue;
                }

                foreach (['min' => $minValue, 'avg' => $avgValue, 'max' => $maxValue] as $property => $value) {
                    $ident = $key.'.'.$property;

                    $setting = ElcaSetting::findBySectionAndIdent($section, $ident);
                    $value   = (int)$value / 100;

                    if ($setting->isInitialized()) {
                        $setting->setNumericValue($value);
                        $setting->update();
                    } else {
                        ElcaSetting::create($section, $ident, null, $value);
                    }
                }
            }

            if ($validator->isValid()) {
                $validator = null;
            } else {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        }

        $this->referenceProjectsAction(false, $validator);
    }


    /**
     * projections action
     */
    protected function projectionsAction($addNavigationViews = true, Validator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        $data = $this->buildReferenceProjectsData($benchmarkVersionId, self::SETTING_SECTION_PROJECTIONS);

        $view = $this->setView(new ElcaAdminBenchmarkProjectionsView());
        $view->assign('activeTabIdent', 'projections');
        $view->assign('data', $data);

        if ($validator)
            $view->assign('validator', $validator);

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }
    // End projectionsAction


    /**
     * Save action
     */
    protected function saveProjectionsAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost())
            return;

        $benchmarkVersionId = $this->Request->id;

        $validator = null;
        if ($this->Request->has('save')) {
            $validator = new ElcaValidator($this->Request);

            $benchmarkVersion = ElcaBenchmarkVersion::findById($benchmarkVersionId);
            $section          = self::SETTING_SECTION_PROJECTIONS.'.'.$benchmarkVersion->getId();

            $min = $this->Request->getArray('min');
            $avg = $this->Request->getArray('avg');
            $max = $this->Request->getArray('max');

            foreach ($avg as $key => $foo) {
                $minValue = ElcaNumberFormat::fromString($min[$key]);
                $avgValue = ElcaNumberFormat::fromString($avg[$key]);
                $maxValue = ElcaNumberFormat::fromString($max[$key]);

                if (!is_numeric($minValue) && !is_numeric($avgValue) && !is_numeric($maxValue)) {
                    continue;
                }

                if (!$validator->assertTrue(
                    'min['.$key.']',
                    is_numeric($minValue) && is_numeric($avgValue) && is_numeric($maxValue),
                    t('Alle drei Werte müssen definiert werden')
                )) {
                    continue;
                }

                if (!$validator->assertTrue(
                    'min['.$key.']',
                    $minValue <= $avgValue,
                    t('Werte müssen aufsteigend definiert werden: schwach <= mittel <= gut')
                )) {
                    continue;
                }

                if (!$validator->assertTrue(
                    'min['.$key.']',
                    $avgValue <= $maxValue,
                    t('Werte müssen aufsteigend definiert werden: schwach <= mittel <= gut')
                )) {
                    continue;
                }

                foreach (['min' => $minValue, 'avg' => $avgValue, 'max' => $maxValue] as $property => $value) {
                    $ident = $key .'.'. $property;

                    $setting = ElcaSetting::findBySectionAndIdent($section, $ident);
                    if ($setting->isInitialized()) {
                        if (\is_numeric($value)) {
                            $setting->setNumericValue($value);
                            $setting->update();
                        } else {
                            $setting->delete();
                        }
                    } elseif (\is_numeric($value)) {
                        ElcaSetting::create($section, $ident, null, $value);
                    }
                }
            }

            if (!$validator->isValid()) {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        }

        $this->projectionsAction(false, $validator);
    }

    private function buildReferenceProjectsData(int $benchmarkVersionId, string $settingsSection, $defaultValue = null): \stdClass
    {
        $setArrayValue = function (array &$arr, $k, $v) {
            $arr[$k] = $v;
        };

        $data = new \stdClass();
        $data->id = $benchmarkVersionId;

        $benchmarkVersion = ElcaBenchmarkVersion::findById($data->id);
        $section = $settingsSection .'.'. $benchmarkVersion->getId();

        $data->min = $data->avg = $data->max = [];
        foreach (ElcaIndicatorSet::findByProcessDbId($benchmarkVersion->getProcessDbId()) as $indicator) {
            foreach (['min', 'avg', 'max'] as $property) {
                $ident   = $indicator->getIdent() .'.'. $property;
                $setting = ElcaSetting::findBySectionAndIdent($section, $ident);

                if (!$setting->isInitialized()) {
                    $setting = ElcaSetting::create($section, $ident, null, $defaultValue);
                }

                $setArrayValue($data->$property, $indicator->getIdent(), $setting->getNumericValue() ?? $defaultValue);
            }
        }

        return $data;
    }


    /**
     * @param $settings
     * @return array
     */
    private function validateLifeCycleUsageSettings($settings)
    {
        $a1Isset  = isset($settings[ElcaLifeCycle::IDENT_A1]);
        $a2Isset  = isset($settings[ElcaLifeCycle::IDENT_A2]);
        $a3Isset  = isset($settings[ElcaLifeCycle::IDENT_A3]);
        $a13Isset = isset($settings[ElcaLifeCycle::IDENT_A13]);

        if ($a13Isset) {
            $settings[ElcaLifeCycle::IDENT_A1] = true;
            $settings[ElcaLifeCycle::IDENT_A2] = true;
            $settings[ElcaLifeCycle::IDENT_A3] = true;

            return $settings;
        }

        if ($a1Isset && $a2Isset && $a3Isset) {
            $settings[ElcaLifeCycle::IDENT_A13] = true;

            return $settings;
        }

        return $settings;
    }

}
// End AdminBenchmarksCtrl
