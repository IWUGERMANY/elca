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
use Elca\Commands\Assistant\Stairs\SaveCommand;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementSearchSet;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Model\Assistant\Stairs\Assembler;
use Elca\Model\Assistant\Stairs\Validator;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\Service\Element\ElementService;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\Assistant\StaircaseAssistantView;
use Elca\View\ElcaElementSelectorView;
use Elca\View\ElcaElementsNavigationView;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\ElcaProjectElementsNavigationView;

/**
 * StaircaseCtrl
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class StaircaseCtrl extends TabsCtrl
{
    const CONTEXT = 'assistant/staircase';

    protected $context;
    protected $elementTypeNodeId;
    protected $elementId;

    /**
     * @var StaircaseAssistant
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
        $this->assistant = $this->container->get('Elca\Service\Assistant\Stairs\StaircaseAssistant');
        $this->imageCache = $this->container->get('Elca\Service\ElcaElementImageCache');
    }
    // End init

    /**
     *
     */
    public function defaultAction()
    {
        $view = $this->setView(new StaircaseAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);
        $view->assign('elementId', $this->elementId);
        $view->assign('staircase', $this->assistant->getStaircaseFromElement($this->elementId));
        $view->assign('platformConstructionElementId', $this->assistant->getPlatformConstructionElementIdFromElement($this->elementId));
        $view->assign('platformCoverElementId', $this->assistant->getPlatformCoverElementIdFromElement($this->elementId));

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
        $staircase = $command->getStaircase();

        $Validator = new Validator($this->Request);
        $Validator->assert($staircase);

        $view = $this->setView(new StaircaseAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);

        $element = $this->assistant->getStaircaseElement($command->elementId);
        $elementId = $element->getId();

        if ($Validator->isValid()) {

            $assembler = new Assembler(
                $this->container->get(ElementService::class),
                $staircase,
                $this->context === ProjectElementsCtrl::CONTEXT ? $this->Elca->getProjectVariantId() : null
            );

            if ($elementId) {
                $element = $assembler->update($element, $command->platformConstructionElementId(), $command->platformCoverElementId());

                $this->assistant->saveStaircaseForElement($element->getId(), $staircase);
                $this->assistant->computeLcaForStaircaseElement($element->getId());
            }
            else {
                $element = $assembler->create($command->platformConstructionElementId(), $command->platformCoverElementId());
                $this->assistant->saveStaircaseForElement($element->getId(), $staircase);
                $this->assistant->computeLcaForStaircaseElement($element->getId());

                $url = Url::factory('/'.$this->context.'/'. $element->getId() .'/', ['tab' => StaircaseAssistant::IDENT]);
                $this->Response->setHeader('X-Replace-Hash: '. (string)$url);

                if ($this->context === ProjectElementsCtrl::CONTEXT)
                    $ctrl = '\Elca\Controller\ProjectElementsCtrl';
                else
                    $ctrl = '\Elca\Controller\ElementsCtrl';

                $this->forward($ctrl, $element->getId(), null, $url->getParameter());
            }

            $view->assign('elementId', $element->getId());
            $view->assign('staircase', $staircase);
            $view->assign('platformConstructionElementId', $this->assistant->getPlatformConstructionElementIdFromElement($this->elementId, true));
            $view->assign('platformCoverElementId', $this->assistant->getPlatformCoverElementIdFromElement($this->elementId, true));

            //$this->imageCache->clear($element->getId());
            $this->addNavigationView($element->getElementTypeNodeId(), $element->getProjectVariantId());
        } else {

            $view->assign('elementId', $elementId);
            $view->assign('Validator', $Validator);
            $view->assign('staircase', $staircase);
            $view->assign('platformConstructionElementId', $this->assistant->getPlatformConstructionElementIdFromElement($this->elementId));
            $view->assign('platformCoverElementId', $this->assistant->getPlatformCoverElementIdFromElement($this->elementId));

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
            $Results = ElcaProcessConfigSearchSet::findByKeywords($keywords, $inUnit, !$this->Access->hasAdminPrivileges(),
                $this->context == ProjectElementsCtrl::CONTEXT? null : [$this->Elca->getProject()->getProcessDbId()], null, $this->Request->epdSubType);

            $returnValues = [];
            foreach($Results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->process_category_node_id;
                $DO->label = $Result->name;
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

            $View = $this->setView(new ElcaProcessConfigSelectorView());
            $View->assign('processConfigId', $processConfigId);
            $View->assign('elementId', $this->Request->elementId);
            $View->assign('relId', $this->Request->relId);
            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId? $this->Request->processCategoryNodeId : $this->Request->c);
            $View->assign('buildMode', $this->Request->b);
            $View->assign('inUnit', $this->Request->u);
            $View->assign('context', self::CONTEXT);
            $View->assign('allowDeselection', true);
            $View->assign('db', $this->Request->db);
            $View->assign('epdSubType', $this->Request->epdSubType);
        }
        /**
         * If user pressed select button, assign the new process
         */
        elseif(isset($this->Request->select))
        {
            // in id is the newProcessConfigId, in p the old
            $processConfigId = $this->Request->id? $this->Request->id : null;
            if ($processConfigId == 'NULL') $processConfigId = null;

            $view = $this->setView(new StaircaseAssistantView());
            $view->assign('context', $this->context);
            $view->assign('buildMode', StaircaseAssistantView::BUILDMODE_SELECTOR);
            $view->assign('key', $this->Request->relId);
            $view->assign('value', $this->Request->id);
        }
    }
    // End selectProcessConfigAction


    /**
     * Action selectProcessConfig
     */
    protected function selectElementAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if(isset($this->Request->term))
        {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $searchMode = $this->Request->has('m')? $this->Request->get('m') : null;
            $searchScope = $this->Request->has('scope')? $this->Request->get('scope') : null;
            $CompositeElement = ElcaElement::findById($this->Request->ce);
            $refUnit = $this->Request->has('u')? $this->Request->u : null;

            $Results = ElcaElementSearchSet::findByKeywordsAndCompositeElementTypeNodeId(
                $keywords,
                $CompositeElement->getElementTypeNodeId(),
                $searchMode == self::CONTEXT? null : $CompositeElement->getProjectVariantId(),
                $this->Access->hasAdminPrivileges(),
                $this->Access->getUserGroupId(),
                $this->context == self::CONTEXT || $searchMode == ElementsCtrl::CONTEXT,
                $this->context == self::CONTEXT || $searchMode == ElementsCtrl::CONTEXT? null : $CompositeElement->getId(),
                $searchScope === 'private' ? true : null,
                $searchScope === 'reference' ? true : null,
                $refUnit
            );

            $returnValues = [];
            $suffix = $searchMode == ElementsCtrl::CONTEXT? ' (' . t('Vorlage') . ')' : '';
            foreach($Results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->element_type_node_id;
                $DO->label = $Result->name . $suffix;
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
            $CompositeElement = ElcaElement::findById($this->Request->relId);
            if(!$this->Access->canEditElement($CompositeElement))
                return false;

            $view = $this->setView(new StaircaseAssistantView());
            $view->assign('context', $this->context);
            $view->assign('buildMode', StaircaseAssistantView::BUILDMODE_ELEMENT_SELECTOR);
            $view->assign('key', $this->Request->pos);
            $view->assign('value', $this->Request->id);

            //$this->addNavigationView($CompositeElement->getElementTypeNodeId());

            return true;
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        else
        {
            $View = $this->setView(new ElcaElementSelectorView());
            $View->assign('elementId', $this->Request->e);
            $View->assign('currentElementId', $this->Request->id);
            $View->assign('relId', $this->Request->relId);
            $View->assign('pos', $this->Request->pos);
            $View->assign('elementTypeNodeId', $this->Request->elementTypeNodeId? $this->Request->elementTypeNodeId : $this->elementTypeNodeId);
            $View->assign('context', self::CONTEXT);
            $View->assign('buildMode', $this->Request->b);
            $View->assign('searchMode', $this->Request->has('mode')? $this->Request->get('mode') : null);
            $View->assign('searchScope', $this->Request->has('scope')? $this->Request->get('scope') : null);
            $View->assign('forceProjectContext', $this->context === ProjectElementsCtrl::CONTEXT);
            $View->assign('refUnit', $this->Request->has('u')? $this->Request->get('u') : null);
        }

        return false;
    }
    // End selectElementAction

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
        if(!$this->hasViewByName('Elca\View\ElcaElementsNavigationView'))
        {
            $view = $this->addView(
                null !== $projectVariantId
                    ? new ElcaProjectElementsNavigationView()
                    : new ElcaElementsNavigationView()
            );
            $view->assign('context', $this->context);
            $view->assign('activeElementTypeId', $activeElementTypeId);
            $view->assign('controller', $this->context === ElementsCtrl::CONTEXT? 'Elca\Controller\ElementsCtrl' : 'Elca\Controller\ProjectElementsCtrl');

            if (null !== $projectVariantId) {
                $view->assign('projectVariantId', $projectVariantId);
            }
        }
    }
}
