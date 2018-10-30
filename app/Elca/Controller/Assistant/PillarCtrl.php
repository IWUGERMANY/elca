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

namespace Elca\Controller\Assistant;

use Beibob\Blibs\Interfaces\Viewable;
use Beibob\Blibs\Url;
use Elca\Commands\Assistant\Pillar\SaveCommand;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Model\Assistant\Pillar\Assembler;
use Elca\Model\Assistant\Pillar\Validator;
use Elca\Service\Assistant\Pillar\PillarAssistant;
use Elca\Service\ElcaElementImageCache;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\Assistant\PillarAssistantView;
use Elca\View\ElcaElementsNavigationView;
use Elca\View\ElcaProcessConfigSelectorView;

class PillarCtrl extends TabsCtrl
{
    const CONTEXT = 'assistant/pillar';

    protected $context;
    protected $elementTypeNodeId;
    protected $elementId;

    /**
     * @var PillarAssistant
     */
    protected $assistant;

    /**
     * @var ElcaElementImageCache
     */
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
        $this->assistant = $this->container->get(PillarAssistant::class);
        $this->imageCache = $this->container->get(ElcaElementImageCache::class);
    }
    // End init

    /**
     *
     */
    public function defaultAction()
    {
        $view = $this->setView($this->getAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);
        $view->assign('elementId', $this->elementId);
        $view->assign('pillar', $this->assistant->getPillarFromElement($this->elementId));
        $view->assign('assistantIdent', $this->assistant->getIdent());
        $view->assign('assistantContext', static::CONTEXT);

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
        if (!$this->isAjax() || !$this->Request->isPost()) {
            return;
        }

        $command = SaveCommand::createFromRequest($this->Request);
        $pillar = $command->getPillar();

        $Validator = new Validator($this->Request);
        $Validator->assert($pillar);

        $view = $this->setView($this->getAssistantView());
        $view->assign('context', $this->context);
        $view->assign('elementTypeNodeId', $this->elementTypeNodeId);
        $view->assign('assistantIdent', $this->assistant->getIdent());
        $view->assign('assistantContext', static::CONTEXT);

        $elementId = $command->elementId;
        $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, $this->assistant->getIdent());
        if ($attr->isInitialized() && $attr->getNumericValue() !== null) {
            $elementId = $attr->getNumericValue();
        }

        $element = ElcaElement::findById($elementId);

        if ($Validator->isValid()) {

            $assembler = new Assembler($pillar, $this->context === ProjectElementsCtrl::CONTEXT? $this->Elca->getProjectVariantId() : null);

            if ($elementId) {
                $element = $assembler->update($element);

                $this->assistant->savePillarForElement($element->getId(), $pillar);
                $this->assistant->computeLcaForPillarElement($element->getId());
            }
            else {
                $element = $assembler->create($this->elementTypeNodeId);
                $this->assistant->savePillarForElement($element->getId(), $pillar);
                $this->assistant->computeLcaForPillarElement($element->getId());

                $url = Url::factory('/'.$this->context.'/'. $element->getId() .'/', ['tab' => static::CONTEXT]);
                $this->Response->setHeader('X-Replace-Hash: '. (string)$url);

                if ($this->context === ProjectElementsCtrl::CONTEXT)
                    $ctrl = '\Elca\Controller\ProjectElementsCtrl';
                else
                    $ctrl = '\Elca\Controller\ElementsCtrl';

                $this->forward($ctrl, $element->getId(), null, $url->getParameter());
            }

            $view->assign('elementId', $element->getId());
            $view->assign('pillar', $pillar);

            $this->imageCache->clear($element->getId());

            $this->addNavigationView($element->getElementTypeNodeId());
        } else {

            $view->assign('elementId', $elementId);
            $view->assign('Validator', $Validator);
            $view->assign('pillar', $pillar);

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

            if ($processConfigId === 'NULL') {
                $processConfigId = null;
            }

            $View = $this->setView(new ElcaProcessConfigSelectorView());
            $View->assign('processConfigId', $processConfigId);
            $View->assign('elementId', $this->Request->elementId);
            $View->assign('relId', $this->Request->relId);
            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId? $this->Request->processCategoryNodeId : $this->Request->c);
            $View->assign('buildMode', $this->Request->b);
            $View->assign('inUnit', $this->Request->u);
            $View->assign('context', static::CONTEXT);
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
            if ($processConfigId === 'NULL') {
                $processConfigId = null;
            }

            $view = $this->setView($this->getAssistantView());
            $view->assign('context', $this->context);
            $view->assign('buildMode', PillarAssistantView::BUILDMODE_SELECTOR);
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
    protected function addNavigationView($activeElementTypeId = null)
    {
        /**
         * Add left navigation
         */
        if(!$this->hasViewByName(ElcaElementsNavigationView::class))
        {
            $View = $this->addView(new ElcaElementsNavigationView());
            $View->assign('context', $this->context);
            $View->assign('activeElementTypeId', $activeElementTypeId);
            $View->assign('controller', $this->context === ElementsCtrl::CONTEXT? ElementsCtrl::class : ProjectElementsCtrl::class);
        }
    }

    /**
     * @return Viewable
     */
    protected function getAssistantView() : Viewable
    {
        return new PillarAssistantView();
    }
}
