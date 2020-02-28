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

namespace Elca\Controller;

use Beibob\Blibs\CssLoader;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\JsLoader;
use Beibob\Blibs\Log;
use Beibob\Blibs\Url;
use Beibob\Blibs\Validator;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigAttribute;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessConversionVersion;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProcessLifeCycleAssignment;
use Elca\Db\ElcaProcessSearchSet;
use Elca\Db\ElcaProcessSet;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectTransport;
use Elca\Db\ElcaProjectTransportMeanSet;
use Elca\Db\ElcaProjectVariant;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Unit;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\ConversionId;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessLifeCycleId;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\ProcessConfig\Conversions;
use Elca\Validator\ElcaProcessConfigValidator;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProcessConfigGeneralView;
use Elca\View\ElcaProcessConfigLcaView;
use Elca\View\ElcaProcessDatabasesView;
use Elca\View\ElcaProcessesNavigationView;
use Elca\View\ElcaProcessSelectorView;
use Elca\View\ProcessConfig\CategoryListView;
use Elca\View\ProcessConfig\IndexView;
use Elca\View\ProcessConfig\SearchView;
use Exception;

/**
 * This controller handles actualy process data and process config management
 * Perhaps it would be cleaner to separate this controller into two...
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ProcessesCtrl extends TabsCtrl
{
    const DEFAULT_EOL_CATEGORY_REF_NUM = '9.05';

    /**
     * ProcessConfig
     */
    private $ProcessConfig;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * Session namespace
     */
    private $namespace;

    /**
     * @var Conversions
     */
    private $conversions;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if ($this->hasBaseView()) {
            $this->getBaseView()->setContext(ElcaBaseView::CONTEXT_PROCESSES);
        }

        /**
         * Session namespace
         */
        $this->namespace = $this->Session->getNamespace('elca.processes', true);
        $this->conversions = $this->container->get(Conversions::class);

        /**
         * In case the default action forwards to the tab controller (which this one also is)
         * the initial action is retrieved through the $args map
         */
        if (isset($args['initialAction']) && is_numeric($args['initialAction'])) {
            $this->processConfigId = (int)$args['initialAction'];
        }

        if (!$this->isAjax()) {
            $jsLoader = JsLoader::getInstance();
            $jsLoader->register('DataTables', 'datatables.min.js');
            $jsLoader->register('DataTables', 'Select-1.2.3/js/dataTables.select.min.js');
            $jsLoader->register('selectize', 'sifter.min.js');
            $jsLoader->register('selectize', 'microplugin.min.js');
            $jsLoader->register('selectize', 'selectize.min.js');
            $cssLoader = CssLoader::getInstance();
            $cssLoader->prepend('DataTables', 'datatables.min.css', 'all', '/js');
            $cssLoader->prepend('selectize', 'selectize.css', 'all', '/js');
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Default action
     */
    protected function defaultAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        /**
         * A numeric action assumes a valid processConfigId
         */
        if (is_numeric($this->getAction())) {
            $this->editAction();
        } else {
            $this->listAction();
        }

        $this->addNavigationView($this->ProcessConfig ? $this->ProcessConfig->getProcessCategoryNodeId() : null);
    }
    // End defaultAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * list action
     */
    protected function listAction($categoryId = null, $page = 0)
    {
        if (!$this->isAjax()) {
            return;
        }

        $categoryId = $categoryId ?: $this->Request->getNumeric('c');

        if ($this->Request->has('page')) {
            $page = $this->Request->getNumeric('page');
        }

        $mainViewName = null !== $categoryId ? CategoryListView::class : IndexView::class;

        $view = $this->setView($page || $this->Request->isPost() ? new SearchView() : new $mainViewName());
        $view->assign('categoryId', $categoryId);
        $view->assign('allowEmptySearch', null !== $categoryId);

        $view->assign('readOnly', !$this->Access->hasAdminPrivileges());
        $view->assign('action', '/processes/list/');
        $view->assign('page', $page);

        $filterDO = $view->assign('filterDO', $this->getFilterDO('process-config.list', ['search' => null]));

        if ($this->Request->isPost()) {
            $view->assign('returnResultList', true);
            $view->assign('page', 0);
        } elseif (0 === $page) {
            /**
             * Empty some filters on GET and first page requests
             */
            if (!$this->Request->has('back')) {
                $filterDO->search = null;
            }

            $this->addView(new ElcaProcessesNavigationView());
            $this->Osit->setProcessConfigListScenario($categoryId, $this->Request->get('back') === 'search');
        }
    }
    // End listAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Action for the general tab
     */
    protected function generalAction()
    {
        $processConfigId = $this->processConfigId ?: $this->Request->processConfigId;

        $this->initProcessDbId($processConfigId);

        $view            = $this->setView(new ElcaProcessConfigGeneralView());
        $view->assign('processConfigId', $processConfigId);
        $view->assign('readOnly', !$this->Access->hasAdminPrivileges());
        $view->assign('processDbId', $this->namespace->processDbId);
    }
    // End generalAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a new process config
     */
    protected function createAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        if (!$this->Request->c) {
            return;
        }

        /**
         * Add only one tab in create view
         */
        $this->addTabItem('general', t('Allgemein'), null, 'Elca\Controller\ProcessesCtrl', 'general');

        $view = $this->addView(new ElcaProcessConfigGeneralView());
        $view->assign('processCategoryNodeId', $this->Request->c);
        $view->assign('processDbId', $this->namespace->processDbId);

        $this->Osit->setProcessConfigScenario($this->Request->c);
    }
    // End createAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Save config action
     */
    protected function saveConfigAction()
    {
        if (!$this->Request->isPost()) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $processConfig = ElcaProcessConfig::findById($this->Request->processConfigId);
        $this->initProcessDbId($processConfig->getId());

        if (isset($this->Request->saveGeneral)) {
            $validator = new ElcaProcessConfigValidator($this->Request);
            $validator->assertNotEmpty('name', null, t('Bitte geben Sie einen Namen ein'));
            $validator->assertNumber('minLifeTime', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('avgLifeTime', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('maxLifeTime', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('density', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('fHsHi', null, t('Es sind nur numerische Werte erlaubt'));
			$validator->assertNumber('wasteCode', null, t('Es sind nur numerische Werte erlaubt'));
			$validator->assertNumber('wasteCodeSuffix', null, t('Es sind nur numerische Werte erlaubt'));
			$validator->assertNumber('lambdaValue', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('defaultSize', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('thermalConductivity', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('thermalResistance', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('transportPayLoad', null, t('Es sind nur numerische Werte erlaubt'));
            $validator->assertNumber('transportEfficiency', null, t('Es sind nur numerische Werte erlaubt'));

            /**
             * Assert presence of lifeTime only for configs with prod processes
             */
            if ($processConfig->isInitialized() && $this->Request->has('isReference') &&
                ElcaProcessSet::dbCountByProcessConfigId(
                    $processConfig->getId(),
                    ['life_cycle_phase' => ElcaLifeCycle::PHASE_PROD]
                ) > 0
            ) {
                $validator->assertLifeTime('minLifeTime', 'avgLifeTime', 'maxLifeTime');
            }

            /**
             * Validate conversions
             */
			if ($processConfig->isInitialized()) {

				$processConfigId       = new ProcessConfigId($processConfig->getId());
				$processDbId           = new ProcessDbId($this->Request->processDbId);
            $processLifeCycleId    = new ProcessLifeCycleId($processDbId, $processConfigId);

            $conversionService     = $this->container->get(Conversions::class);
				$requiredConversions   = $conversionService->findRequiredConversions($processLifeCycleId);
				$additionalConversions = $conversionService->findAdditionalConversions($processLifeCycleId);

				foreach ($requiredConversions as $conversion) {
					if ($conversion->isIdentity()) {
						continue;
					}

					$validator->assertConversion($conversion, 'factor_', 'inUnit_', 'outUnit_');
				}

				foreach ($additionalConversions as $conversion) {
					if ($conversion->isIdentity()) {
						continue;
					}

					$validator->assertConversion($conversion, 'factor_', 'inUnit_', 'outUnit_');
				}
			}

            if ($validator->isValid()) {
                $minLifeTime     = ElcaNumberFormat::fromString($this->Request->minLifeTime, 0);
                $avgLifeTime     = ElcaNumberFormat::fromString($this->Request->avgLifeTime, 0);
                $maxLifeTime     = ElcaNumberFormat::fromString($this->Request->maxLifeTime, 0);
                $lifeTimeInfo    = \trim($this->Request->lifeTimeInfo);
                $minLifeTimeInfo = \trim($this->Request->minLifeTimeInfo);
                $avgLifeTimeInfo = \trim($this->Request->avgLifeTimeInfo);
                $maxLifeTimeInfo = \trim($this->Request->maxLifeTimeInfo);

                $density             = ElcaNumberFormat::fromString($this->Request->density, 2);
                $thermalConductivity = ElcaNumberFormat::fromString($this->Request->thermalConductivity, 2);
                $thermalResistance   = ElcaNumberFormat::fromString($this->Request->thermalResistance, 2);
                $fHsHi               = ElcaNumberFormat::fromString($this->Request->fHsHi, 2);
                $wasteCode           = !empty($this->Request->wasteCode) ? (int)$this->Request->wasteCode : null;
				$wasteCodeSuffix     = !empty($this->Request->wasteCodeSuffix) ? (int)$this->Request->wasteCodeSuffix : null;
				$lambdaValue		 = !empty($this->Request->lambdaValue) ? ElcaNumberFormat::fromString($this->Request->lambdaValue, 3) : null;
				$elementGroupA     	 = !empty($this->Request->elementGroupA) ? (bool)$this->Request->elementGroupA : null;
				$elementGroupB     	 = !empty($this->Request->elementGroupB) ? (bool)$this->Request->elementGroupB : null;
				$defaultSize         = $this->Request->defaultSize ? ElcaNumberFormat::fromString($this->Request->defaultSize, 2) / 1000 : null;
                $svgPatternId        = $this->Request->svgPatternId ? $this->Request->svgPatternId : null;

                if ($processConfig->isInitialized()) {
                    $needLcaProcessing = false;

                    /**
                     * Update
                     */
                    $Dbh = DbHandle::getInstance();
                    try {
                        $Dbh->begin();

                        $oldName = $processConfig->getName();
                        $processConfig->setName($this->Request->name);
                        $processConfig->setDescription($this->Request->description ?: null);


                        /**
                         * Remember old default life time value
                         */
                        $oldDefaultLifeTime = $processConfig->getDefaultLifeTime();

                        $processConfig->setMinLifeTime($minLifeTime);
                        $processConfig->setAvgLifeTime($avgLifeTime);
                        $processConfig->setMaxLifeTime($maxLifeTime);
                        $processConfig->setLifeTimeInfo($lifeTimeInfo);
                        $processConfig->setMinLifeTimeInfo($minLifeTimeInfo);
                        $processConfig->setAvgLifeTimeInfo($avgLifeTimeInfo);
                        $processConfig->setMaxLifeTimeInfo($maxLifeTimeInfo);

						// AVV
						$processConfig->setWasteCode($wasteCode);
						$processConfig->setWasteCodeSuffix($wasteCodeSuffix);
						$processConfig->setLambdaValue($lambdaValue);

						$processConfig->setElementGroupA($elementGroupA);
						$processConfig->setElementGroupB($elementGroupB);

                        $processConfig->setThermalConductivity($thermalConductivity);
                        $processConfig->setThermalResistance($thermalResistance);
                        $processConfig->setIsReference($this->Request->has('isReference'));
                        $processConfig->setSvgPatternId(
                            $processConfig->getProcessCategory()->getSvgPatternId() == $svgPatternId ? null
                                : $svgPatternId
                        );

                        if ($fHsHi != $processConfig->getFHsHi()) {
                            $needLcaProcessing = true;
                        }
                        $processConfig->setFHsHi($fHsHi);

                        if ($conversionService->changeProcessConfigDensity($processDbId, $processConfigId, $density, null, __METHOD__)) {
                            $newDefaultSize = $conversionService->computeDefaultSizeFromDensity(
                                $processDbId,
                                $processConfigId,
                                $density
                            );

                            if ($newDefaultSize && !FloatCalc::cmp($defaultSize, $newDefaultSize)) {
                                $this->messages->add(
                                    t(
                                        'Soll die Dicke auf den Wert :value: mm angepasst werden?',
                                        null,
                                        [':value:' => ElcaNumberFormat::toString($newDefaultSize * 1000, 2)]
                                    ),
                                    ElcaMessages::TYPE_CONFIRM,
                                    $this->getActionLink(
                                        'updateDefaultSize',
                                        [
                                            'processConfigId' => $processConfig->getId(),
                                            'processDbId'     => $processDbId->value(),
                                            'size'            => $newDefaultSize,
                                        ]
                                    )
                                );
                            }
                        }

                        if ($conversionService->changeProcessConfigDefaultSize($processConfig, $defaultSize)) {
                            $newDensity = $conversionService->computeDensityFromMpua($processDbId, $processConfigId, $defaultSize);

                            if ($newDensity && !FloatCalc::cmp($density, $newDensity)) {
                                $this->messages->add(
                                    t(
                                        'Soll die Rohdichte auf den Wert :value: angepasst werden?',
                                        null,
                                        [':value:' => ElcaNumberFormat::toString($newDensity, 2)]
                                    ),
                                    ElcaMessages::TYPE_CONFIRM,
                                    $this->getActionLink(
                                        'updateDensity',
                                        [
                                            'processConfigId' => $processConfig->getId(),
                                            'processDbId'     => $processDbId->value(),
                                            'density'         => $newDensity,
                                        ]
                                    )
                                );
                            }
                        }

                        $processConfig->update();

                        /**
                         * Update the life time of all element components of reference template elements
                         * if the minimum lifetime has changed
                         */
                        if ($oldDefaultLifeTime != $processConfig->getDefaultLifeTime()) {
                            $newDefaultLifeTime = $processConfig->getDefaultLifeTime();
                            $tplComponents      = ElcaElementComponentSet::findTemplatesByProcessConfigId(
                                $processConfig->getId()
                            );
                            foreach ($tplComponents as $TplComponent) {
                                $TplComponent->setLifeTime($newDefaultLifeTime);
                                $TplComponent->update();
                            }

                            if ($tplComponents->count()) {
                                $this->messages->add(
                                    t(
                                        'Die Nutzungsdauern der Bauteilvorlagen, die diese Baustoffkonfiguration verwenden, wurden ebenfalls aktualisiert!'
                                    )
                                );
                            }
                        }

                        /**
                         * Update osit view if the name of the process config changed
                         */
                        if ($oldName != $this->Request->name) {
                            $this->Osit->setProcessConfigScenario(
                                $processConfig->getProcessCategoryNodeId(),
                                $processConfig->getId()
                            );
                        }

                        /**
                         * Update existing conversions
                         */
                        foreach ($processConfig->getProcessConversions() as $conversion) {
                            $conversionVersion = $conversion->getVersionFor($processDbId);

                            if ($conversionVersion->getIdent()) {
                                continue;
                            }

                            if ($conversion->getInUnit() === Unit::CUBIC_METER && $conversion->getOutUnit() === Unit::KILOGRAMM) {
                                continue;
                            }

                            $factor = ElcaNumberFormat::fromString(
                                $this->Request->get('factor_'.$conversion->getId()),
                                8
                            );

                            if (!FloatCalc::cmp($factor, $conversionVersion->getFactor())) {
                                $conversionService->registerConversion($processDbId, $processConfigId,
                                    new LinearConversion(
                                        Unit::fromString($this->Request->get('inUnit_'.$conversion->getId())),
                                        Unit::fromString($this->Request->get('outUnit_'.$conversion->getId())),
                                        $factor
                                    ), null, __METHOD__);

                                $needLcaProcessing = true;
                            }
                        }

                        /**
                         * Add required, but missing conversions
                         */
                        foreach ($requiredConversions as $conversion) {
                            if ($conversion->hasSurrogateId()) {
                                continue;
                            }

                            $inUnit  = (string)$conversion->fromUnit();
                            $outUnit = (string)$conversion->toUnit();
                            $reqId   = '_new_'.$inUnit.'_'.$outUnit;

                            $factor = ElcaNumberFormat::fromString($this->Request->get('factor'.$reqId), 8);

                            $conversionService->registerConversion($processDbId, $processConfigId,
                                new LinearConversion(
                                    $conversion->fromUnit(),
                                    $conversion->toUnit(),
                                    $factor
                                ), null,__METHOD__);
                        }

                        /**
                         * Add new conversions
                         */
                        if ($this->Request->factor_new &&
                            $this->Request->inUnit_new &&
                            $this->Request->outUnit_new
                        ) {
                            $factor = ElcaNumberFormat::fromString($this->Request->factor_new, 8);

                            $conversionService->registerConversion($processDbId, $processConfigId,
                                new LinearConversion(
                                    Unit::fromString($this->Request->inUnit_new),
                                    Unit::fromString($this->Request->outUnit_new),
                                    $factor
                                ), null,__METHOD__);
                        }

                        $Dbh->commit();

                        /**
                         * Save Transport attributes
                         */
                        if (isset($this->Request->transportPayLoad) && $this->Request->transportPayLoad) {
                            $needLcaProcessing |= $this->Request->transportPayLoad != ElcaProcessConfigAttribute::findValue(
                                    $processConfig->getId(),
                                    ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD
                                );
                            ElcaProcessConfigAttribute::updateValue(
                                $processConfig->getId(),
                                ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD,
                                ElcaNumberFormat::fromString($this->Request->transportPayLoad, 2)
                            );
                        } else {
                            ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent(
                                $processConfig->getId(),
                                ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD
                            )->delete();
                            $needLcaProcessing |= ElcaProcessConfigAttribute::findValue(
                                    $processConfig->getId(),
                                    ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD
                                ) !== null;
                        }
                        if (isset($this->Request->transportEfficiency) && $this->Request->transportEfficiency) {
                            ElcaProcessConfigAttribute::updateValue(
                                $processConfig->getId(),
                                ElcaProcessConfigAttribute::IDENT_TRANSPORT_EFFICIENCY,
                                ElcaNumberFormat::fromString($this->Request->transportEfficiency, 3, true)
                            );
                        } else {
                            ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent(
                                $processConfig->getId(),
                                ElcaProcessConfigAttribute::IDENT_TRANSPORT_EFFICIENCY
                            )->delete();
                        }

                        /**
                         * Save final energy attributes
                         */
                        $this->updateProcessConfigAttribute(
                            $processConfig->getId(),
                            ElcaProcessConfigAttribute::IDENT_OP_AS_SUPPLY,
                            (int)$this->Request->has('opAsSupply')
                        );
                        $this->updateProcessConfigAttribute(
                            $processConfig->getId(),
                            ElcaProcessConfigAttribute::IDENT_OP_INVERT_VALUES,
                            (int)$this->Request->has('opInvertValues')
                        );

                        // save 4108 compat flag
                        $this->updateProcessConfigAttribute(
                            $processConfig->getId(),
                            ElcaProcessConfigAttribute::IDENT_4108_COMPAT,
                            (int)$this->Request->has('is4108Compat')
                        );

                        $this->messages->add(t('Der Baustoff wurde gespeichert'));

                        if ($needLcaProcessing) {
                            $this->initiateRecomputeLcaView($processConfig);
                        }

                        $this->reloadHashUrl();
                    } catch (Exception $Exception) {
                        $Dbh->rollback();
                        throw $Exception;
                    }
                } else {
                    /**
                     * Insert a new
                     */
                    $processConfig = ElcaProcessConfig::create(
                        $this->Request->name,
                        $this->Request->processCategoryNodeId,
                        $this->Request->description ?: null,
                        $thermalConductivity,
                        $thermalResistance,
                        $this->Request->has('isReference'),
                        $fHsHi,
                        $minLifeTime,
                        $avgLifeTime,
                        $maxLifeTime,
                        $lifeTimeInfo,
                        $minLifeTimeInfo,
                        $avgLifeTimeInfo,
                        $maxLifeTimeInfo,
                        null,
                        $svgPatternId,
                        false,
                        $defaultSize,
						$wasteCode,
						$wasteCodeSuffix,
						$lambdaValue,
						$elementGroupA,
						$elementGroupB
                    );
                    /**
                     * Init a redirect to render the default view
                     */
                    $this->Response->setHeader('X-Redirect: '.$this->getActionLink($processConfig->getId()));

                    return;
                }

            } else {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }

                $view = $this->setView(new ElcaProcessConfigGeneralView());
                $view->assign('processConfigId', $processConfig->getId());
                $view->assign('processDbId', $this->namespace->processDbId);

                /**
                 * Assign Validator to mark error fields
                 */
                $view->assign('Validator', $validator);
            }
        } elseif (isset($this->Request->cancel)) {
            /**
             * In case the insert action was canceled
             */
            $this->listAction($this->Request->processCategoryNodeId);
        } else {
            /**
             * Just render the view
             */
            $view = $this->setView(new ElcaProcessConfigGeneralView());
            $view->assign('processConfigId', $processConfig->getId());
            $view->assign('processDbId', $this->namespace->processDbId);
        }
    }
    // End generalAction

    //////////////////////////////////////////////////////////////////////////////////////

    protected function updateDensityAction()
    {
        if (!$this->isAjax() || !$this->Request->processConfigId || !$this->Request->processDbId ||
            !is_numeric($this->Request->density)) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $processConfig = ElcaProcessConfig::findById($this->Request->processConfigId);
        if (!$processConfig->isInitialized()) {
            return;
        }

        $processDbId = new ProcessDbId($this->Request->processDbId);
        $processConfigId = new ProcessConfigId($processConfig->getId());

        /**
         * @var Conversions $conversionService
         */
        $conversionService = $this->get(Conversions::class);

        $density = round($this->Request->density, 2);
        if ($conversionService->changeProcessConfigDensity($processDbId, $processConfigId, $density, null, __METHOD__)) {
            $this->initiateRecomputeLcaView($processConfig);
        }

        $this->messages->add(t('Die Rohdichte wurde angepasst'));

        $this->reloadHashUrl();
    }

    protected function updateDefaultSizeAction()
    {
        if (!$this->isAjax() || !$this->Request->processConfigId || !$this->Request->processDbId ||
            !is_numeric($this->Request->size)) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $processConfig = ElcaProcessConfig::findById($this->Request->processConfigId);
        if (!$processConfig->isInitialized()) {
            return;
        }

        /**
         * @var Conversions $conversionService
         */
        $conversionService = $this->get(Conversions::class);

        $defaultSize = round($this->Request->size, 4);
        if ($conversionService->changeProcessConfigDefaultSize($processConfig, $defaultSize)) {
            $processConfig->update();
            $this->initiateRecomputeLcaView($processConfig);
        }

        $this->messages->add(t('Die Dicke wurde angepasst'));

        $this->reloadHashUrl();
    }

    /**
     * Calculates the lca for the some elements
     */
    protected function lcaConfigProcessingAction()
    {
        if (!$this->Request->processConfigId) {
            return;
        }

        $this->updateLca($this->Request->processConfigId);
    }
    // End lcaConfigProcessingAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds a conversion action
     */
    protected function addConversionAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $view = $this->setView(new ElcaProcessConfigGeneralView());
        $view->assign('processConfigId', $this->Request->p);
        $view->assign('buildMode', ElcaProcessConfigGeneralView::BUILDMODE_CONVERSIONS);
        $view->assign('addConversion', true);
        $view->assign('processDbId', $this->namespace->processDbId);
    }
    // End addConversionAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a conversion action
     */
    protected function deleteConversionAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $conversionId = $this->Request->id;
        $processDbId = $this->Request->db;
        $processConfigId = $this->Request->processConfigId;

        if (!\is_numeric($conversionId) || !\is_numeric($processDbId)) {
            return;
        }

        $conversionsService = $this->container->get(Conversions::class);

        /**
         * If deletion has confirmed, do the action
         */
        if ($this->Request->has('confirmed')) {

            $conversionsService->unregisterConversion(new ProcessDbId($processDbId), new ConversionId($conversionId), __METHOD__);

            $view = $this->setView(new ElcaProcessConfigGeneralView());
            $view->assign('buildMode', ElcaProcessConfigGeneralView::BUILDMODE_CONVERSIONS);
            $view->assign('processConfigId', $processConfigId);
            $view->assign('processDbId', $processDbId);

        } else {
            $conversion = ElcaProcessConversion::findById($conversionId);

            if ($conversion->isInitialized()) {
                /**
                 * Check if conversion is used by any element components
                 */
                $components = ElcaElementComponentSet::findByProcessConversionId($conversion->getId(), [], null, 5);

                if ($cnt = $components->count()) {
                    $cntTxt         = $cnt == 5 ? t('%count% oder mehr', null, ['%count%' => $cnt]) : $cnt;
                    $componentsText = $components->join('\', `', 'elementName');

                    if ($cnt == 5) {
                        $msg = t(
                            'Diese Umrechnung kann nicht gelöscht werden, da sie von mindestens 5 Bauteilkomponenten verwendet wird: `%components%\'',
                            null,
                            ['%components%' => $componentsText]
                        );
                    } else {
                        $msg = t(
                            'Diese Umrechnung kann nicht gelöscht werden, da sie von %count% Bauteilkomponenten verwendet wird: `%components%\'',
                            null,
                            ['%count%' => $cnt, '%components%' => $componentsText]
                        );
                    }

                    $this->messages->add($msg, ElcaMessages::TYPE_ERROR);
                } else {

                    /**
                     * Build confirm url by adding the confirmed argument to the current request
                     */
                    $url = Url::parse($this->Request->getURI());
                    $url->addParameter(['confirmed' => null]);

                    /**
                     * Show confirm message
                     */
                    $this->messages->add(
                        t('Soll die Umrechnung wirklich gelöscht werden?'),
                        ElcaMessages::TYPE_CONFIRM,
                        (string)$url
                    );
                }
            }
        }
    }
    // End deleteConversionAction

    /**
     * Deletes a conversion action
     */
    protected function editImportedConversionAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        if (!\is_numeric($this->Request->id) || !\is_numeric($this->Request->db)) {
            return;
        }

        $conversionVersion = ElcaProcessConversionVersion::findByPK($this->Request->id, $this->Request->db);

        if (!$conversionVersion->getIdent()) {
            return;
        }

        /**
         * If confirmed act
         */
        if ($this->Request->has('confirmed')) {

            if ($conversionVersion->isInitialized()) {
                $conversionVersion->setIdent(null);
                $conversionVersion->update();

                $view = $this->setView(new ElcaProcessConfigGeneralView());
                $view->assign('processConfigId', $conversionVersion->getProcessConfigId());
                $view->assign('buildMode', ElcaProcessConfigGeneralView::BUILDMODE_CONVERSIONS);
                $view->assign('processDbId', $this->namespace->processDbId);
            }
        } else {
            if ($conversionVersion->isInitialized()) {
                /**
                 * Build confirm url by adding the confirmed argument to the current request
                 */
                $url = Url::parse($this->Request->getURI());
                $url->addParameter(['confirmed' => null]);

                /**
                 * Show confirm message
                 */
                $this->messages->add(
                    t(
                        'Soll die importierte Umrechnung wirklich bearbeitet werden? Dies ist nachträglich nicht mehr rückgängig zu machen!'
                    ),
                    ElcaMessages::TYPE_CONFIRM,
                    (string)$url
                );
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a process config
     */
    protected function deleteAction()
    {
        if (!is_numeric($this->Request->id)) {
            return;
        }

        /**
         * If deletion has confirmed, do the action
         */
        if ($this->Request->has('confirmed')) {
            $ProcessConfig = ElcaProcessConfig::findById($this->Request->id);
            if ($ProcessConfig->isInitialized()) {
                $categoryId = $ProcessConfig->getProcessCategoryNodeId();
                $ProcessConfig->delete();

                /**
                 * Forward to list
                 */
                $this->listAction($categoryId);
                $this->messages->add(t('Der Datensatz wurde gelöscht'));
            }
        } else {
            /**
             * Build confirm url by adding the confirmed argument to the current request
             */
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            /**
             * Show confirm message
             */
            $this->messages->add(
                t('Soll der Baustoff wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End deleteAction

    /**
     *
     */
    protected function copyAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $processConfig = ElcaProcessConfig::findById($this->Request->id);
        $processConfig->copy();

        $this->listAction($processConfig->getProcessCategoryNodeId());
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * database action
     */
    protected function databasesAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (!$this->Request->id) {
            return;
        }

        $View = $this->setView(new ElcaProcessDatabasesView());
        $View->assign('processDbId', $this->Request->id);

        $this->addNavigationView();

        $ProcessDb = ElcaProcessDb::findById($this->Request->id);
        $this->Osit->add(new ElcaOsitItem($ProcessDb->getName(), null, t('Datenbanken')));
    }
    // End databaseAction

    //////////////////////////////////////////////////////////////////////////////////////
    // LCA View actions
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Action lca
     */
    protected function lcaAction($processConfigId = null, $processDbId = null, $onlyPhase = null, $addAssignment = false
    ) {
        $processConfigId = $processConfigId ? $processConfigId : $this->Request->processConfigId;
        $processDbId     = $processDbId ? $processDbId : $this->Request->processDbId;

        if (!$processDbId) {
            $processDbId = $this->namespace->processDbId ? $this->namespace->processDbId
                : ElcaProcessDb::findMostRecentVersion()->getId();
        }

        /**
         * Store processDb in session
         */
        $this->namespace->processDbId = $processDbId;

        // build data object for either the complete form or just for one life cycle
        $DO                  = new \stdClass();
        $DO->processDbId     = $processDbId;
        $DO->processConfigId = $processConfigId;
        $DO->lifeCycle       = $DO->processId = [];

        $phases = $onlyPhase
            ? [$onlyPhase]
            : [
                ElcaLifeCycle::PHASE_PROD,
                ElcaLifeCycle::PHASE_OP,
                ElcaLifeCycle::PHASE_EOL,
                ElcaLifeCycle::PHASE_REC,
            ];

        foreach ($phases as $phase) {
            /**
             * Find all assigned processes for the current processDb and phase
             */
            $ProcessSet = ElcaProcessSet::findByProcessConfigId(
                $processConfigId,
                ['life_cycle_phase' => $phase, 'process_db_id' => $processDbId],
                ['life_cycle_ident' => 'ASC']
            );
            foreach ($ProcessSet as $Process) {
                $aId                 = $Process->getProcessLifeCycleAssignmentId();
                $lcIdent             = $Process->getLifeCycleIdent();
                $DO->ratio[$aId]     = $Process->getRatio();
                $DO->lifeCycle[$aId] = $lcIdent;
                $DO->processId[$aId] = $Process->getId();
            }
        }

        $View = $this->setView(new ElcaProcessConfigLcaView());
        $View->assign('Data', $DO);

        $View->assign('readOnly', !$this->Access->hasAdminPrivileges());

        if ($onlyPhase) {
            $View->assign('buildMode', ElcaProcessConfigLcaView::BUILDMODE_PHASE);
            $View->assign('phase', $onlyPhase);

            if ($addAssignment) {
                $View->assign('addAssignment', true);
            }
        }
    }
    // End lcaAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Action selectProcess
     *
     * @param  -
     * @return -
     */
    protected function selectProcessAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        /**
         * If a term was send, autocomplete term
         */
        if (isset($this->Request->term) && is_numeric($this->Request->processDbId)) {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $Results  = ElcaProcessSearchSet::findByKeywords(
                $this->Request->processDbId,
                $keywords,
                $this->Request->lc
            );

            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = $Result->name;
                $DO->category = $Result->process_category_parent_node_name.' > '.$Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        } /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!isset($this->Request->select)) {
            $View = $this->setView(new ElcaProcessSelectorView());
            $View->assign('processId', $this->Request->id ? $this->Request->id : $this->Request->p);
            $View->assign('processDbId', $this->Request->processDbId);
            $View->assign('lifeCycleIdent', $this->Request->lc);
            $View->assign('processLifeCycleAssignmentId', $this->Request->plcaId);
            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);

        } /**
         * If user pressed select button, assign the new process
         */
        elseif (isset($this->Request->select)) {
            $changedElts = [];

            $plcaId  = $this->Request->plcaId;
            $lcIdent = $this->Request->lc;

            if ($this->Request->id != $this->Request->p) {
                $changedElts['processId['.$plcaId.']'] = true;

                $OldProcess = ElcaProcess::findById($this->Request->p);

                if ($OldProcess->getLifeCycleIdent() != $lcIdent) {
                    $changedElts['lifeCycle['.$plcaId.']'] = true;
                }
            }

            /**
             * Build DataObject
             */
            $DO                     = new \stdClass();
            $DO->processDbId        = $this->Request->processDbId;
            $DO->processConfigId    = $this->Request->processConfigId;
            $DO->key                = $plcaId;
            $DO->lifeCycle[$plcaId] = $lcIdent;
            $DO->processId[$plcaId] = $this->Request->id; // in id is the newProcessId, in p the old
            $DO->ratio[$plcaId]     = ElcaProcessLifeCycleAssignment::findById($plcaId)->getRatio();

            /**
             * Build single life cycle row
             */
            $View = $this->setView(new ElcaProcessConfigLcaView());
            $View->assign('buildMode', ElcaProcessConfigLcaView::BUILDMODE_LIFE_CYCLE);
            $View->assign('lifeCycleIdent', $lcIdent);
            $View->assign('Data', $DO);
            $View->assign('changedElements', $changedElts);
        }
    }
    // End selectProcessAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Saves the lca config
     *
     * @param  -
     * @return -
     */
    protected function saveLcaConfigAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $lcPhase = null;

        /**
         * Save button was pressed
         */
        if ($this->Request->saveLca) {
            $Validator = new Validator($this->Request);
            $Validator->assertNotEmpty('processConfigId', null, t('Kein Baustoff gewählt'));
            $Validator->assertNotEmpty('processDbId', null, t('Es muss eine Datenbank gewählt werden'));

            if ($Validator->isValid()) {
                $needLcaProcessing = false;

                $lifeCycles = $this->Request->lifeCycle;
                $processIds = $this->Request->processId;

                foreach ($lifeCycles as $key => $value) {
                    $ratio = isset($this->Request->ratio[$key]) ? ElcaNumberFormat::fromString(
                        $this->Request->ratio[$key],
                        2,
                        true
                    ) : 1;
                    if (is_numeric($key)) {
                        $Assignment = ElcaProcessLifeCycleAssignment::findById($key);

                        // has the process changed?
                        if ($Assignment->getProcessId() != $processIds[$key] || $Assignment->getRatio() != $ratio) {
                            $NewProcess = ElcaProcess::findById($processIds[$key]);
                            if ($NewProcess->isInitialized()) {
                                if ($ratio != $Assignment->getRatio()) {
                                    $needLcaProcessing = true;
                                }

                                if ($NewProcess->getId() != $Assignment->getProcessId()) {
                                    $needLcaProcessing = true;
                                }

                                $Assignment->setRatio($ratio);
                                $Assignment->setProcessId($NewProcess->getId());
                                $Assignment->update();

                                $this->messages->add(
                                    t(
                                        'Neuer Prozess `%name%\' für `%lifeCycle%\' wurde gespeichert',
                                        null,
                                        [
                                            '%name%'      => $NewProcess->getName(),
                                            '%lifeCycle%' => $NewProcess->getLifeCycle()->getName(),
                                        ]
                                    )
                                );
                            } else {
                                $this->messages->add(
                                    t(
                                        'Kein Prozess für `%lifeCycle%\' zugeordnet',
                                        null,
                                        ['%lifeCycle%' => ElcaLifeCycle::findByIdent($lifeCycles[$key])->getName()]
                                    ),
                                    ElcaMessages::TYPE_ERROR
                                );
                            }
                        }
                    } else {
                        // check if a process was selected
                        if (isset($processIds[$key]) && is_numeric($processIds[$key])) {
                            // create new assignment
                            ElcaProcessLifeCycleAssignment::create(
                                $this->Request->processConfigId,
                                $processIds[$key],
                                $ratio
                            );
                            $process = ElcaProcess::findById($processIds[$key]);
                            $this->messages->add(
                                t(
                                    'Prozess `%name%\' für `%lifeCycle%\' wurde gespeichert',
                                    null,
                                    [
                                        '%name%'      => $process->getName(),
                                        '%lifeCycle%' => $process->getLifeCycle()->getName(),
                                    ]
                                )
                            );

                            /**
                             * Add identity conversion for prod processes
                             */
                            if ($process->getLifeCyclePhase() === ElcaLifeCycle::PHASE_PROD) {

                                $procRefUnit = Unit::fromString($process->getRefUnit());
                                $processConfigId  = new ProcessConfigId($this->Request->processConfigId);
                                $processDbId      = new ProcessDbId($this->Request->processDbId);

                                $processConversion = $this->conversions->findByConversion($processConfigId,
                                    $processDbId, $procRefUnit, $procRefUnit);

                                if (null === $processConversion) {
                                    $this->conversions->registerConversion(
                                        $processDbId, $processConfigId,
                                        ImportedLinearConversion::forReferenceUnit($procRefUnit), null,__METHOD__
                                    );
                                }
                            }

                            $needLcaProcessing = true;
                        }
                    }
                }

                if ($needLcaProcessing) {
                    $View = $this->addView(new ElcaModalProcessingView());
                    $View->assign(
                        'action',
                        $this->getActionLink(
                            'lcaProcessing',
                            [
                                'processConfigId' => $this->Request->processConfigId,
                                'processDbId'     => $this->Request->processDbId,
                            ]
                        )
                    );
                    $View->assign('headline', t('Neuberechnung erforderlich'));
                    $View->assign(
                        'description',
                        t(
                            'Sie haben Änderungen vorgenommen, die eine Neuberechnung einiger Bauteile erforderlich machen.'
                        )
                    );
                }
            } else {
                foreach ($Validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }

                /**
                 * Assign Validator to mark error fields
                 */
                //$View->assign('Validator', $Validator);
            }
        } /**
         * LifeCycle selectbox was changed
         */
        elseif ($this->Request->plcaId && $this->Request->lifeCycle) {
            /**
             * Find changed elements, to mark it as changed
             */
            $changedElts = [];

            $plcaId                = $this->Request->plcaId;
            $newLifeCycleIdent     = $this->Request->lifeCycle[$plcaId];
            $lcPhase               = $this->Request->lcPhase;
            $processCategoryNodeId = null;

            if (is_numeric($plcaId)) {
                $ProcessLifeCycleAssignment = ElcaProcessLifeCycleAssignment::findById($plcaId);
                $process                    = $ProcessLifeCycleAssignment->getProcess();
                $processDbId                = $process->getProcessDbId();
                $processConfigId            = $ProcessLifeCycleAssignment->getProcessConfigId();
                $processLcIdent             = $process->getLifeCycleIdent();
                $newProcessId               = $newLifeCycleIdent == $processLcIdent ? $process->getId() : null;

                if (!$newProcessId) {
                    $changedElts['processId['.$plcaId.']'] = true;
                    $changedElts['lifeCycle['.$plcaId.']'] = true;
                }
            } else {
                $processDbId                           = $this->Request->processDbId;
                $processConfigId                       = $this->Request->processConfigId;
                $newProcessId                          = null;
                $changedElts['lifeCycle['.$plcaId.']'] = true;

                if (in_array(
                    $newLifeCycleIdent,
                    [
                        ElcaLifeCycle::PHASE_EOL,
                        ElcaLifeCycle::IDENT_C3,
                        ElcaLifeCycle::IDENT_C4,
                        ElcaLifeCycle::IDENT_D,
                    ],
                    true
                )) {
                    $processCategoryNodeId = ElcaProcessCategory::findByRefNum(
                        self::DEFAULT_EOL_CATEGORY_REF_NUM
                    )->getNodeId();

                    $processSet = ElcaProcessSet::findExtended(
                        [
                            'process_category_node_id' => $processCategoryNodeId,
                            'life_cycle_ident'         => $newLifeCycleIdent,
                            'process_db_id'            => $processDbId,
                        ],
                        ['name' => 'ASC'],
                        2
                    );

                    if ($processSet->count() === 1 || ElcaLifeCycle::IDENT_C4 === $newLifeCycleIdent) {
                        $newProcessId                          = false !== $processSet->current()
                            ? $processSet->current()->getId() : null;
                        $changedElts['processId['.$plcaId.']'] = true;
                    }
                }
            }

            /**
             * Build DataObject
             */
            $DO                     = new \stdClass();
            $DO->processDbId        = $processDbId;
            $DO->processConfigId    = $processConfigId;
            $DO->key                = $plcaId;
            $DO->lifeCycle[$plcaId] = $newLifeCycleIdent;
            $DO->processId[$plcaId] = $newProcessId;

            /**
             * Build single life cycle row
             */
            $View = $this->setView(new ElcaProcessConfigLcaView());
            $View->assign('buildMode', ElcaProcessConfigLcaView::BUILDMODE_LIFE_CYCLE);
            $View->assign('lifeCycleIdent', $newLifeCycleIdent);
            $View->assign('phase', $lcPhase);
            $View->assign('Data', $DO);
            $View->assign('changedElements', $changedElts);


            if ($processCategoryNodeId) {
                $View->assign('processCategoryNodeId', $processCategoryNodeId);
            }
        }
    }
    // End saveLcaConfigAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Calculates the lca for the some elements
     */
    protected function lcaProcessingAction()
    {
        if (!$this->Request->processConfigId || !$this->Request->processDbId) {
            return;
        }

        $this->updateLca($this->Request->processConfigId, $this->Request->processDbId);
        $this->lcaAction($this->Request->processConfigId, $this->Request->processDbId);
    }
    // End lcaProcessingAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Add lca action
     */
    protected function addLcaAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        if (!$this->Request->processConfigId || !$this->Request->processDbId || !$this->Request->phase) {
            return;
        }

        /**
         * Build lca view with added lifeCycle for the given phase
         */
        $this->lcaAction($this->Request->processConfigId, $this->Request->processDbId, $this->Request->phase, true);
    }
    // End deleteLcaAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Delete lca action
     */
    protected function deleteLcaAction()
    {
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        if (!$this->Request->plcaId) {
            return;
        }

        /**
         * If deletion has confirmed, do the action
         */
        if ($this->Request->has('confirmed')) {
            $assignment = ElcaProcessLifeCycleAssignment::findById($this->Request->plcaId);

            if ($assignment->isInitialized()) {
                $processConfigId = $assignment->getProcessConfigId();
                $processDbId     = $assignment->getProcess()->getProcessDbId();
                $processRefUnit  = $assignment->getProcess()->getRefUnit();
                $phase           = $assignment->getProcess()->getLifeCycle()->getPhase();

                $assignment->delete();

                /**
                 * Remove identity production conversion for this process
                 */
                $this->conversions->removeIdentityConversionForUnit(new ProcessConfigId($processConfigId),
                    new ProcessDbId($processDbId), Unit::fromString($processRefUnit));

                $this->updateLca($processConfigId);

                $this->lcaAction($processConfigId, $processDbId, $phase);
                $this->messages->add(t('Die Zuordnung wurde gelöscht'));
            }
        } else {
            /**
             * Build confirm url by adding the confirmed argument to the current request
             */
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            /**
             * Show confirm message
             */
            $this->messages->add(
                t('Soll die Zuordnung wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End deleteLcaAction

    //////////////////////////////////////////////////////////////////////////////////////
    // private
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a filter data object form request or session
     *
     * @param  string $key
     * @return object
     */
    protected function getFilterDO($key, array $defaults = [])
    {
        if (!$filterDOs = $this->namespace->filterDOs) {
            $filterDOs = [];
        }

        $FilterDO = isset($filterDOs[$key]) ? $filterDOs[$key] : new \stdClass();

        foreach ($defaults as $name => $defaultValue) {
            $FilterDO->$name = $this->Request->has($name) ? $this->Request->get($name)
                : (isset($FilterDO->$name) ? $FilterDO->$name : $defaultValue);
        }

        $filterDOs[$key] = $FilterDO;

        $this->namespace->filterDOs = $filterDOs;

        return $FilterDO;
    }
    // End updateLca

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $processConfig
     */
    protected function updateProcessConfigAttribute(int $processConfigId, string $ident, $value)
    {
        if ($value) {
            ElcaProcessConfigAttribute::updateValue(
                $processConfigId,
                $ident,
                $value
            );
        } else {
            ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent(
                $processConfigId,
                $ident
            )->delete();
        }
    }

    protected function initiateRecomputeLcaView(ElcaProcessConfig $processConfig): void
    {
        return;

        $view = $this->addView(new ElcaModalProcessingView());
        $view->assign(
            'action',
            $this->getActionLink(
                'lcaConfigProcessing',
                ['processConfigId' => $processConfig->getId()]
            )
        );
        $view->assign('headline', t('Neuberechnung erforderlich'));
        $view->assign(
            'description',
            t(
                'Sie haben Änderungen vorgenommen, die eine Neuberechnung einiger Bauteile erforderlich machen.'
            )
        );
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates all affected lca results on processConfig change
     *
     * @param  ElcaProcessConfig $ProcessConfig
     * @return int
     */
    private function updateLca($processConfigId, $processDbId = null)
    {
        $Elements       = ElcaElementSet::findProjectElementsByProcessConfigId($processConfigId, $processDbId);
        $Demands        = ElcaProjectFinalEnergyDemandSet::findByProcessConfigId($processConfigId, $processDbId);
        $TransportMeans = ElcaProjectTransportMeanSet::findByProcessConfigId($processConfigId, $processDbId);

        $eCount = $Elements->count();
        $fCount = $Demands->count();
        $tCount = $TransportMeans->count();
        if ($eCount + $fCount + $tCount == 0) {
            return 0;
        }

        $affectedProjectIds = [];
        $LcaProcessor       = $this->container->get(ElcaLcaProcessor::class);

        if ($eCount) {
            Log::getInstance()->debug(
                'ProcessConfig has changed! Updating the following elements: '.$Elements->join(', ', 'id'),
                __METHOD__
            );

            foreach ($Elements as $Element) {
                $LcaProcessor->computeElement($Element);
            }
        }

        if ($fCount) {
            /**
             * Get a unique list of projectVariantIds
             */
            $projectVariantIds = $Demands->getArrayBy('projectVariantId', 'projectVariantId');

            Log::getInstance()->debug(
                'ProcessConfig has changed! Updating final energy demands for the following project variants: '.join(
                    ', ',
                    $projectVariantIds
                ),
                __METHOD__
            );

            foreach ($projectVariantIds as $projectVariantId) {
                $projectVariant = ElcaProjectVariant::findById($projectVariantId);
                $LcaProcessor->computeFinalEnergy($projectVariant);
                $affectedProjectIds[] = $projectVariant->getProjectId();
            }
        }

        if ($tCount) {
            /**
             * Get a unique list of projectVariantIds
             */
            $transportIds      = $TransportMeans->getArrayBy('projectTransportId', 'projectTransportId');
            $projectVariantIds = null;

            foreach ($transportIds as $transportId) {
                $projectVariantIds[] = ElcaProjectTransport::findById($transportId)->getProjectVariantId();
            }

            Log::getInstance()->debug(
                'ProcessConfig has changed! Updating transport means for the following project variants: '.join(
                    ', ',
                    $projectVariantIds
                ),
                __METHOD__
            );

            foreach ($projectVariantIds as $projectVariantId) {
                $projectVariant = ElcaProjectVariant::findById($projectVariantId);
                $LcaProcessor->computeTransports($projectVariant);
                $affectedProjectIds[] = $projectVariant->getProjectId();
            }

        }

        foreach ($affectedProjectIds as $projectId) {
            $LcaProcessor->updateCache($projectId);
        }

        return $eCount + $fCount + $tCount;
    }
    // End falsePositivesAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Helper to add the navigation to the view stack
     */
    private function addNavigationView($activeCategoryId = null)
    {
        /**
         * Add left navigation
         */
        if (!$this->hasViewByName('Elca\View\ElcaProcessesNavigationView')) {
            $View = $this->addView(new ElcaProcessesNavigationView());
            $View->assign('activeCategoryId', $activeCategoryId);
        }
    }

    private function editAction()
    {
        $this->processConfigId = (int)$this->getAction();
        $this->ProcessConfig   = ElcaProcessConfig::findById($this->processConfigId);

        if ($this->ProcessConfig->isInitialized()) {
            $this->addTabItem(
                'general',
                t('Allgemein'),
                null,
                'Elca\Controller\ProcessesCtrl',
                'general',
                ['processConfigId' => $this->ProcessConfig->getId()]
            );
            $this->addTabItem(
                'lca',
                t('Lebenszyklus'),
                null,
                'Elca\Controller\ProcessesCtrl',
                'lca',
                ['processConfigId' => $this->ProcessConfig->getId()]
            );

            $this->invokeTabActionController();

            $backUrl = null;
            if ($backReference = $this->Request->get('back')) {
                if ($backReference === 'search') {
                    $this->Osit->setProcessConfigFromSearchScenario(
                        $this->ProcessConfig->getProcessCategoryNodeId(),
                        $this->ProcessConfig->getId()
                    );

                } else {
                    $this->Osit->setProcessConfigFromSanitiesScenario(
                        $this->ProcessConfig->getId(),
                        '/sanity/processes/?r='.$backReference
                    );
                }
            }
            else {
                $this->Osit->setProcessConfigScenario(
                    $this->ProcessConfig->getProcessCategoryNodeId(),
                    $this->ProcessConfig->getId()
                );
            }
        }
    }

    private function initProcessDbId($processConfigId): void
    {
        $processDbId = $this->Request->get('processDbId');

        if (!$processDbId) {

            if ($this->namespace->processDbId) {
                $processDbId = $this->namespace->processDbId;
            }
            else {
                $processDb   = ElcaProcessDbSet::findRecentForProcessConfigId($processConfigId)->current();
                $processDbId = $processDb->getId();
            }
        }

        $this->namespace->processDbId = $processDbId;
    }
}
// End ElcaProcessesCtrl
