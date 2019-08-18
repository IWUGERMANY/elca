<?php declare(strict_types=1);
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

namespace Elca\Controller\ProjectData;

use Beibob\Blibs\SessionNamespace;
use Beibob\Blibs\Url;
use Elca\Controller\AppCtrl;
use Elca\Controller\ProjectDataCtrl;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementSearchSet;
use Elca\Db\ElcaProjectVariant;
use Elca\ElcaNumberFormat;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\ReplaceComponentsService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaContentHeadView;
use Elca\View\ElcaProjectNavigationLeftView;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\ElcaTemplateElementSelectorView;
use Elca\View\ProjectData\ReplaceComponentsView;

class ReplaceComponentsCtrl extends AppCtrl
{
    public const CONTEXT = 'project-data/replace-components';

    /**
     * @var SessionNamespace
     */
    private $namespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->namespace = $this->Session->getNamespace('project-data.replace-components', true);
    }


    protected function replaceComponentsAction($addNavigationViews = true, ElcaValidator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->projectVariantId) {
            return;
        }

        $projectVariantId = (int)$this->Request->projectVariantId;

        if ($this->Request->has('init')) {
            $this->namespace->freeData();

            $url                 = Url::parse($this->Request->getURI());
            $url->removeParameter('init');

            $this->updateHashUrl((string)$url);

            if ($projectVariantId !== (int)$this->Elca->getProjectVariantId()) {
                $this->Elca->setProjectVariantId($projectVariantId);

                $contentHeadView = $this->addView(new ElcaContentHeadView());
                $contentHeadView->assign('Project', $this->Elca->getProject());
            }
        }

        $projectVariant = ElcaProjectVariant::findById($projectVariantId);

        $view = $this->setView(new ReplaceComponentsView());
        $view->assign('projectVariantId', $projectVariantId);
        $view->assign('data', $this->buildFormData($projectVariantId));
        $view->assign('context', self::CONTEXT);
        $view->assign('projectVariantIsActive', $projectVariantId === (int)$this->Elca->getProjectVariantId());
        $view->assign('validator', $validator);

        if ($addNavigationViews) {
            $this->addOsitView($projectVariant);
            $this->addNavigationView();
        }
    }

    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost()) {
            return;
        }

        $dinCodes = $this->Request->getArray('din276');
        if (!empty($dinCodes[0]) && !empty($this->namespace->din276[0]) &&
            $dinCodes[0] !== $this->namespace->din276[0]) {
            $this->namespace->din276 = [0 => $dinCodes[0]];
        }
        else {
            $this->namespace->din276 = $dinCodes;
        }

        $this->namespace->replaceComponents = $this->Request->getArray('replaceComponents');
        $this->namespace->layerSizes = $this->Request->getArray('layerSize');
        $this->namespace->lifeTimes = $this->Request->getArray('lifeTime');

        $validator = null;

        if ($this->Request->has('replaceSelected')) {
            $validator = new ElcaValidator($this->Request);
            $validator->assertNotEmpty('elementId', null, t('Bitte wählen Sie eine Bauteilkomponente'));

            foreach ($this->namespace->layerSizes as $componentId => $layerSize) {
                $validator->assertNotEmpty('layerSize['.$componentId.']', $layerSize, t('Dieses Feld darf nicht leer sein'));
            }
            foreach ($this->namespace->lifeTimes as $componentId => $lifeTime) {
                $validator->assertNotEmpty('lifeTime['.$componentId.']', $lifeTime, t('Dieses Feld darf nicht leer sein'));
            }

            if ($validator->isValid()) {
                $tplElementId = (int)$this->Request->elementId;

                $layerSizes = [];
                foreach ($this->namespace->layerSizes as $componentId => $layerSize) {
                    $layerSizes[$componentId] = ElcaNumberFormat::fromString($layerSize) / 1000;
                }

                $this->container->get(ReplaceComponentsService::class)->replaceCompositeComponents(
                    $this->namespace->replaceComponents,
                    $tplElementId,
                    $layerSizes,
                    $this->namespace->lifeTimes
                );

                $this->messages->add(t('Die markierten Bauteilkomponenten wurden ersetzt'));
            }
            else {
                foreach ($validator->getErrors() as $error) {
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
                }
            }
        }
        elseif ($this->Request->has('deleteSelected')) {
            $this->container->get(ReplaceComponentsService::class)->deleteCompositeElements(
                $this->namespace->replaceComponents
            );

            $this->messages->add(t('Die markierten Bauteilkomponenten wurden gelöscht'));
        }

        $this->replaceComponentsAction(false, $validator);
    }

    protected function selectElementAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if(isset($this->Request->term))
        {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $elementTypeNodeId = $this->Request->elementTypeNodeId;
            $searchScope = $this->Request->has('scope')? $this->Request->get('scope') : null;
            $compatDbs = $this->Request->compatdbs ?: [];

            $results = ElcaElementSearchSet::findByKeywordsAndElementTypeNodeId(
                $keywords,
                $elementTypeNodeId,
                null,
                $this->Access->hasAdminPrivileges(),
                $this->Access->getUserGroupId(),
                true,
                null,
                $searchScope === 'public' ? true : null,
                $searchScope === 'reference' ? true : null,
                null,
                $compatDbs
            );

            $returnValues = [];
            foreach($results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->element_type_node_id;
                $DO->label = $Result->name;
                $DO->category = $Result->element_type_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * This selects and assigns an element in composite element context,
         * if a user pressed the select button
         */
        elseif(isset($this->Request->selectElement))
        {
            $this->namespace->elementId = $this->Request->id;
            $this->namespace->elementTypeNodeId = $this->Request->elementTypeNodeId;

            $view = $this->setView(new ReplaceComponentsView());
            $view->assign('projectVariantId', $this->Request->projectVariantId);
            $view->assign('elementTypeNodeId', $this->Request->elementTypeNodeId);
            $view->assign('buildMode', ReplaceComponentsView::BUILDMODE_ELEMENT_SELECTOR);
            $view->assign('data', $this->buildFormData($this->Request->projectVariantId));

            return true;
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        else
        {
            $view = $this->setView(new ElcaTemplateElementSelectorView());
            $view->assign('elementId', $this->Request->e);
            $view->assign('currentElementId', $this->Request->id);
            $view->assign('projectVariantId', $this->Request->projectVariantId);
            $view->assign('elementTypeNodeId', $this->Request->elementTypeNodeId ?? $this->Request->t);
            $view->assign('searchScope', $this->Request->has('scope')? $this->Request->get('scope') : 'public');
            $view->assign('url', $this->getActionLink('selectElement'));
        }

        return false;
    }

    private function addNavigationView(): void
    {
        $view = $this->addView(new ElcaProjectNavigationView());
        $view->assign('activeCtrlName', ProjectDataCtrl::class);

        $this->addView(new ElcaProjectNavigationLeftView());
    }

    private function addOsitView(ElcaProjectVariant $projectVariant)
    {
        $this->Osit->add(new ElcaOsitItem(t('Projektvarianten'), '/project-data/variants/', t('Stammdaten')));
        $this->Osit->add(new ElcaOsitItem($projectVariant->getName(),  null, t('Komponenten ersetzen')));
    }

    private function buildFormData($projectVariantId)
    {
        $data = new \stdClass();
        $data->din276 = $this->namespace->din276 ?? [];
        $data->replaceComponents = $this->namespace->replaceComponents ?? [];
        $elementTypeNodeId = !empty($data->din276[0]) && !empty($data->din276[1]) ? $data->din276[1] : null;

        $data->layerSize = $this->namespace->layerSizes;
        $data->lifeTime = $this->namespace->lifeTimes;

        if ($elementTypeNodeId && $elementTypeNodeId === $this->namespace->elementTypeNodeId) {
            $data->elementId = $this->namespace->elementId;
        }
        else {
            $data->elementId = null;
            unset($this->namespace->elementTypeNodeId);
            unset($this->namespace->replaceComponents);
        }

        if ($data->elementId) {
            $components = ElcaElementComponentSet::findLayers($data->elementId);

            foreach ($components as $layer) {
                $layerId                   = $layer->getId();
                $data->layerSize[$layerId] = $data->layerSize[$layerId] ?? $layer->getLayerSize() * 1000;
                $data->lifeTime[$layerId]  = $data->lifeTime[$layerId]  ?? $layer->getLifeTime();
            }
        }

        return $data;
    }
}
