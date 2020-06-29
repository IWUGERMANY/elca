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

namespace Elca\Controller\Assistant;

use Beibob\Blibs\Url;
use Elca\Commands\Assistant\Window\SaveCommand;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Model\Assistant\Window\Assembler;
use Elca\Model\Assistant\Window\Validator;
use Elca\Service\Assistant\Window\WindowAssistant;
use Elca\Service\ElcaElementImageCache;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\Assistant\WindowAssistantView;
use Elca\View\ElcaElementsNavigationView;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\ElcaProjectElementsNavigationView;

/**
 * WindowCtrl
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class WindowCtrl extends TabsCtrl
{
    const CONTEXT = 'assistant/window';

    protected $context;
    protected $elementTypeNodeId;
    protected $elementId;

    /**
     * @var WindowAssistant
     */
    protected $assistant;
    protected $imageCache;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->context = isset($args['context'])? $args['context'] : $this->Request->get('context');
        $this->elementTypeNodeId = isset($args['t'])? $args['t'] : $this->Request->get('t');
        $this->elementId = isset($args['e'])? $args['e'] : $this->Request->get('e');
        $this->assistant = $this->container->get(WindowAssistant::class);
        $this->imageCache = $this->container->get(ElcaElementImageCache::class);
    }
    // End init

    /**
     *
     */
    public function defaultAction()
    {
        $view = $this->setView(new WindowAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);
        $view->assign('elementId', $this->elementId);
        $view->assign('window', $this->assistant->getWindowFromElement($this->elementId));

        if ($this->elementId) {
            $element = ElcaElement::findById($this->elementId);
            $view->assign('readOnly', $element->isInitialized() && !$this->Access->canEditElement($element));
        }
    }

    /**
     *
     */
    public function saveAction()
    {
        $command = SaveCommand::createFromRequest($this->Request);
        $window = $command->getWindow();

        $Validator = new Validator($this->Request);
        $Validator->assert($window);

        $view = $this->setView(new WindowAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);

        $elementId = $command->elementId;

        $assistantElement = ElcaAssistantElement::findByElementId($elementId, WindowAssistant::IDENT);
        $elementId = $assistantElement->getMainElementId();

        $element = ElcaElement::findById($elementId);

        if ($Validator->isValid()) {

            $windowAssembler = new Assembler($window, $this->context === ProjectElementsCtrl::CONTEXT? $this->Elca->getProjectVariantId() : null);

            if ($elementId) {
                $element = $windowAssembler->update($element);

                $this->assistant->saveWindowForElement($element->getId(), $window);
                $this->assistant->computeLcaForWindowElement($element->getId());
            }
            else {
                $element = $windowAssembler->create($element);
                $this->assistant->saveWindowForElement($element->getId(), $window);
                $this->assistant->computeLcaForWindowElement($element->getId());

                $url = Url::factory('/'.$this->context.'/'. $element->getId() .'/', ['tab' => self::CONTEXT]);
                $this->Response->setHeader('X-Replace-Hash: '. (string)$url);

                if ($this->context === ProjectElementsCtrl::CONTEXT)
                    $ctrl = '\Elca\Controller\ProjectElementsCtrl';
                else
                    $ctrl = '\Elca\Controller\ElementsCtrl';

                $this->forward($ctrl, $element->getId(), null, $url->getParameter());
            }

            $view->assign('elementId', $element->getId());
            $view->assign('window', $window);

            $elementIds = $this->assistant->findElementsForWindowElement($element->getId(), true);
            foreach ($elementIds as $elt) {
                $this->imageCache->clear($elt->getId());
            }
            $this->addNavigationView($element->getElementTypeNodeId(), $element->getProjectVariantId());
        } else {

            $view->assign('elementId', $elementId);
            $view->assign('Validator', $Validator);
            $view->assign('window', $window);

            foreach ($Validator->getErrors() as $error) {
                $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }
    }


    /**
     * Action selectProcessConfig
     */
    protected function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if(isset($this->Request->term))
        {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $inUnit = $this->Request->has('u')? $this->Request->get('u') : null;
            $Results = ElcaProcessConfigSearchSet::findByKeywords($keywords, $this->Elca->getLocale(), $inUnit, !$this->Access->hasAdminPrivileges(),
                $this->context == ProjectElementsCtrl::CONTEXT? null : [$this->Elca->getProject()->getProcessDbId()], null, $this->Request->epdSubType);

            $returnValues = [];
            foreach($Results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->process_category_node_id;
                $DO->label = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name .' > '. $Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        elseif(!isset($this->Request->select))
        {
            $processConfigId = $this->Request->sp? $this->Request->sp : ($this->Request->id? $this->Request->id : $this->Request->p);

            if ($processConfigId == 'NULL') $processConfigId = null;

            $view = $this->setView(new ElcaProcessConfigSelectorView());
            $view->assign('processConfigId', $processConfigId);
            $view->assign('elementId', $this->Request->elementId);
            $view->assign('relId', $this->Request->relId);
            $view->assign('processCategoryNodeId', $this->Request->processCategoryNodeId? $this->Request->processCategoryNodeId : $this->Request->c);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('inUnit', $this->Request->u);
            $view->assign('context', 'assistant/window');
            $view->assign('allowDeselection', true);
            $view->assign('db', $this->Request->db);
            $view->assign('epdSubType', $this->Request->epdSubType);
            $view->assign('isTemplateContext', $this->Request->tpl ?? ElementsCtrl::CONTEXT === $this->context);

        }
        /**
         * If user pressed select button, assign the new process
         */
        elseif(isset($this->Request->select))
        {
            // in id is the newProcessConfigId, in p the old
            $processConfigId = $this->Request->id? $this->Request->id : null;
            if ($processConfigId == 'NULL') $processConfigId = null;

            $view = $this->setView(new WindowAssistantView());
            $view->assign('context', $this->context);
            $view->assign('buildMode', WindowAssistantView::BUILDMODE_SELECTOR);
            $view->assign('key', $this->Request->relId);
            $view->assign('value', $this->Request->id);
        }
    }
    // End selectProcessConfigAction

    /**
     * Render navigation
     *
     * @param null $activeElementTypeId
     */
    protected function addNavigationView($activeElementTypeId = null, $projectVariantId = null)
    {
        /**
         * Add left navigation
         */
        if(!$this->hasViewByName(ElcaElementsNavigationView::class))
        {
            $view = $this->addView(
                null !== $projectVariantId
                    ? new ElcaProjectElementsNavigationView()
                    : new ElcaElementsNavigationView()
            );
            $view->assign('context', $this->context);
            $view->assign('activeElementTypeId', $activeElementTypeId);
            $view->assign('controller', $this->context === ElementsCtrl::CONTEXT? ElementsCtrl::class : ProjectElementsCtrl::class);

            if (null !== $projectVariantId) {
                $view->assign('projectVariantId', $projectVariantId);
            }
        }
    }
    // End addNavigationView
}
