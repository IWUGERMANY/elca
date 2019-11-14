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

declare(strict_types = 1);
namespace Elca\Controller\ProjectData;

use Beibob\Blibs\SessionNamespace;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaSearchAndReplaceResultSet;
use Elca\Elca;
use Elca\Model\Element\SearchAndReplaceObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\ElcaElementComponentsView;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\ElcaProjectProcessConfigSanityView;

class ProjectElementSanityCtrl extends AppCtrl
{
    const CONTEXT = 'project-data/project-element-sanity';

    /**
     * @var SessionNamespace
     */
    private $namespace;

    /**
     *
     */
    public function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if (isset($this->Request->term)) {
            $keywords                 = explode(' ', \trim((string)$this->Request->term));
            $inUnit                   = $this->Request->has('u') ? $this->Request->get('u') : null;
            $filterByProjectVariantId = $this->Request->has('filterByProjectVariantId') ? $this->Request->get(
                'filterByProjectVariantId'
            ) : null;

            switch ($this->Request->b) {
                case ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY:
                    $Results = ElcaProcessConfigSearchSet::findFinalEnergySuppliesByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        $this->Elca->getProject()->getProcessDbId()
                    );
                    break;

                default:
                    $Results = ElcaProcessConfigSearchSet::findByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        [$this->Elca->getProject()->getProcessDbId()],
                        $filterByProjectVariantId,
                        $this->Request->epdSubTyp
                    );
            }
            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name.' > '.$Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!isset($this->Request->select)) {
            $view = $this->setView(new ElcaProcessConfigSelectorView());

            $processConfigId = $this->Request->sp ? $this->Request->sp
                : ($this->Request->id ? $this->Request->id : $this->Request->p);

            $processCategoryNodeId = $this->Request->processCategoryNodeId ? $this->Request->processCategoryNodeId
                : $this->Request->c;

            /**
             * Deselection results in 'NULL' string
             */
            if ($processConfigId === 'NULL') {
                $processConfigId = null;
            }

            $view->assign('processConfigId', $processConfigId);
            $view->assign('processCategoryNodeId', $processCategoryNodeId);
            $view->assign('projectVariantId', $this->Request->projectVariantId);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('context', self::CONTEXT);
            $view->assign('relId', $this->Request->relId ? $this->Request->relId : null);
            $view->assign('allowDeselection', true);
            $view->assign('db', $this->Elca->getProject()->getProcessDbId());
            $view->assign('epdSubType', $this->Request->epdSubType);
            $view->assign('data', $this->Request->data);

            if ($this->Request->relId) {
                $elementComponents = ElcaElementComponentSet::findByElementId(
                    $this->Request->data,
                    ['process_config_id' => $this->Request->relId]
                );

                $componentTypes = [];

                /**
                 * @var ElcaElementComponent $elementComponent
                 */
                foreach ($elementComponents as $elementComponent) {
                    $componentTypes[$elementComponent->isLayer()] = true;
                }

                if (count($componentTypes) === 1 && isset($componentTypes[true])) {
                    $view->assign('buildMode', ElcaElementComponentsView::BUILDMODE_LAYERS);
                }
                else {
                    $view->assign('buildMode', ElcaElementComponentsView::BUILDMODE_COMPONENTS);

                    $searchFor = ElcaProcessConfig::findById($this->Request->relId);
                    list($requiredConversions, $availableConversions) = $searchFor->getRequiredConversions();

                    $availableUnits = array_unique(
                        $requiredConversions->getArrayBy('inUnit', 'id') + $availableConversions->getArrayBy(
                            'inUnit',
                            'id'
                        )
                    );

                    $view->assign('inUnit', implode(',', $availableUnits));
                }

                /**
                 * This is necessary because the requested buildmode differs from the evaluated.
                 * Unfortunately HtmlForm prefers the request value
                 */
                $this->Request->__set('b', $view->get('buildMode'));
            }
        }

        /**
         * If user pressed select button, assign the new process
         */
        elseif (isset($this->Request->select)) {

            $processConfigId    = $this->Request->relId;
            $newProcessConfigId = $this->Request->id ? $this->Request->id : null;

            $newProcessConfigIds                   = $this->getNewProcessConfigIds();
            $newProcessConfigIds[$processConfigId] = $newProcessConfigId;
            $this->namespace->newProcessConfigIds  = $newProcessConfigIds;

            $view = $this->setView(new ElcaProjectProcessConfigSanityView());
            $view->assign('newProcessConfigIds', $newProcessConfigIds);
            $view->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));
        }
    }

    /**
     *
     */
    public function replaceInvalidProcessesAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (!$this->checkProjectAccess() || !$this->Access->canEditProject($this->Elca->getProject())) {
            return;
        }

        // get replacements from session
        $newProcessConfigIds = $this->getNewProcessConfigIds();

        if ($this->Request->has('replace')) {
            $requestedProcessConfigChanges = $this->Request->getArray('newProcessConfigId');
            foreach ($requestedProcessConfigChanges as $oldProcessConfigId => $newProcessConfigId) {
                if (!$newProcessConfigId) {
                    continue;
                }

                $this->replaceInvalidProcess(
                    ElcaProcessConfig::findById($oldProcessConfigId),
                    ElcaProcessConfig::findById($newProcessConfigId)
                );

                // remove from session
                unset($newProcessConfigIds[$oldProcessConfigId]);
            }
        }
        elseif ($this->Request->has('cancel')) {
            $newProcessConfigIds = [];
        }

        // store in session
        $this->namespace->newProcessConfigIds = $newProcessConfigIds;

        $view = $this->setView(new ElcaProjectProcessConfigSanityView());
        $view->assign('newProcessConfigIds', $newProcessConfigIds);
    }

    public function getNewProcessConfigIds()
    {
        if (!is_array($this->namespace->newProcessConfigIds)) {
            $this->namespace->newProcessConfigIds = [];
        }

        return $this->namespace->newProcessConfigIds;
    }

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->namespace = $this->Session->getNamespace(self::CONTEXT, true);
    }

    private function replaceInvalidProcess(ElcaProcessConfig $oldProcessConfig, ElcaProcessConfig $newProcessConfig)
    {
        list($requiredConversions, $availableConversions) = $newProcessConfig->getRequiredConversions();
        $availableUnits = array_flip(
            array_unique(
                $requiredConversions->getArrayBy('inUnit', 'id') + $availableConversions->getArrayBy('inUnit', 'id')
            )
        );

        $resultSet = ElcaSearchAndReplaceResultSet::findByProjectVariantIdAndProcessConfigId(
            $this->Elca->getProjectVariantId(),
            $oldProcessConfig->getId()
        );

        $quantity = $conversionId = null;
        $updated  = 0;
        foreach ($resultSet as $resultItem) {
            if ($resultItem->process_config_id != $oldProcessConfig->getId()) {
                continue;
            }

            $processConfig = ElcaProcessConfig::findById($resultItem->process_config_id);
            $usedUnit      = $resultItem->is_layer ? Elca::UNIT_M3 : $resultItem->component_unit;
            if (!isset($availableUnits[$usedUnit])) {
                if ($resultItem->is_layer) {
                    continue;
                } // Should not happen
                else {
                    $matrix  = $processConfig->getConversionMatrix($this->Elca->getProject()->getProcessDbId());
                    $outUnit = null;
                    foreach ($matrix[$usedUnit] as $unit => $factor) {
                        if (isset($availableUnits[$unit])) {
                            $outUnit = $unit;
                            break;
                        }
                    }
                    if (null === $outUnit) {
                        continue;
                    }

                    $quantity     = $matrix[$usedUnit][$outUnit] * $resultItem->quantity;
                    $conversionId = $availableUnits[$outUnit];
                }
            }
            else {
                $conversionId = $availableUnits[$usedUnit];
            }

            $elementComponent = ElcaElementComponent::findById($resultItem->id);
            $elementComponent->setProcessConfigId($newProcessConfig->getId());

            if ($quantity) {
                $elementComponent->setQuantity($quantity);
            }

            if ($conversionId) {
                $elementComponent->setProcessConversionId($conversionId);
            }

            $elementComponent->update();

            /**
             * @var SearchAndReplaceObserver $observer
             */
            foreach ($this->container->get('elca.search_and_replace_observers') as $observer) {
                $observer->onElementComponenentSearchAndReplace(
                    $elementComponent,
                    $oldProcessConfig->getId(),
                    $newProcessConfig->getId()
                );
            }

            $this->container->get(ElcaLcaProcessor::class)->computeElementComponent($elementComponent);
            $updated++;

            $quantity = $conversionId = false;
        }

        if ($updated > 0) {
            $this->container->get(ElcaLcaProcessor::class)->updateCache($this->Elca->getProjectId());

            $updatedOne  = t('Es wurde ein Baustoff ersetzt');
            $updatedMore = t('Es wurden %count% Baustoffe ersetzt.', null, ['%count%' => $updated]);

            $this->messages->add(
                $updated > 1 ? $updatedMore : $updatedOne,
                ElcaMessages::TYPE_INFO
            );
        }
    }
}
