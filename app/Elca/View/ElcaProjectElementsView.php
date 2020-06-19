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
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\Log;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds a list of project element sheets
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectElementsView extends ElcaElementsView
{
    /**
     * @var ElcaAccess
     */
    private $access;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->access = ElcaAccess::getInstance();
    }

    /**
     * Builds the element list
     */
    protected function buildList($sheetView)
    {
        if(!Elca::getInstance()->getProjectVariantId())
            return Log::getInstance()->fatal('No project variant for this project (id='.Elca::getInstance()->getProjectId().') found', __METHOD__);
		
        return parent::buildList('Elca\View\ElcaProjectElementSheetView');
    }
    // End buildList


    /**
     * Appends buttons
     *
     * @param  -
     * @return -
     */
    protected function appendButtons(DOMElement $Container)
    {
        if ($this->readOnly) {
            return;
        }

        /**
         * Buttons
         */
        $elementType = ElcaElementType::findByNodeId($this->elementTypeNodeId);
        $isComposite = $elementType->isCompositeLevel();
        $buttonTxt   = $isComposite ? t('Neues Bauteil') : t('Neue Bauteilkomponente');

        $buttonContainer = $Container->appendChild($this->getDiv(['class' => 'button add']));
        if ($isComposite) {
            $this->addClass($buttonContainer, 'composite');
        }
        $buttonContainer->appendChild($this->getA(['href' => '/project-elements/create/?t='.$this->elementTypeNodeId], '+ '.$buttonTxt));

        /**
         * Add create from button action if at least one template element exists for this element type
         */
        $TplElements = ElcaElementSet::findExtended(['element_type_node_id' => $this->elementTypeNodeId, 'project_variant_id' => null],
                                                    $this->access->hasAdminPrivileges(), $this->access->getUserGroupIds(), null, 1);
        if($TplElements->count())
            $buttonContainer->appendChild($this->getA(['href' => '/project-elements/createFromTemplate/?t='.$this->elementTypeNodeId], '+ ' . $buttonTxt . ' ' . t('von Vorlage')));

        if ($this->assistantRegistry->hasAssistantsForElementType($this->elementType, $this->context)) {
            $assistants = $this->assistantRegistry
                ->getAssistantsForElementType($this->elementType, $this->context);

            foreach ($assistants as $assistant) {
                $buttonTxt = $assistant->getConfiguration()->getCaption();
                $buttonContainer = $Container->appendChild($this->getDiv(['class' => 'button add assistant']));
                if ($isComposite) {
                    $this->addClass($buttonContainer, 'composite');
                }

                $url = Url::factory(
                    '/project-elements/create/', [
                        't' => $this->elementTypeNodeId,
                        'assistant' => $assistant->getConfiguration()->getIdent()
                    ]
                );
                $buttonContainer->appendChild($this->getA(['href' => $url], '+ ' . $buttonTxt));
            }
        }
    }
    // End appendButtons


    /**
     * Append Filter form
     *
     * @param  -
     * @return -
     */
    protected function appendFilterForm(DOMElement $Container)
    {
        $FilterContainer = $Container->appendChild($this->getDiv(['class' => 'sheets-filter']));

        $Form = new HtmlForm('elementFilterForm', $this->action);
        $Form->setAttribute('id', 'elementFilterForm');
        $Form->addClass('filter-form');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->FilterDO);
        $Form->add(new HtmlHiddenField('t', $this->elementTypeNodeId));

        $Filter = $Form->add(new HtmlFormGroup(t('Liste einschränken')));

        $Search = $Filter->add(new ElcaHtmlFormElementLabel(t('Suche'), new HtmlTextInput('search')));
        $Search->addClass('list-search');

        $Filter->add(new ElcaHtmlSubmitButton('refresh', t('Filter aktualisieren'), true));
        $Form->appendTo($FilterContainer);
    }
    // End appendFilter


    /**
     * Returns the element set
     *
     * @param  array $filterKeywords
     * @return ElcaElementSet
     */
    protected function getElementSet(array $filterKeywords)
    {
        $Access = ElcaAccess::getInstance();
        $filter = ['element_type_node_id' => $this->elementTypeNodeId,
                        'project_variant_id' => Elca::getInstance()->getProjectVariantId()];

        if(count($filterKeywords))
            $Elements = ElcaElementSet::searchExtended($filterKeywords, $filter, $this->elementType->isCompositeLevel(), $Access->hasAdminPrivileges(), $Access->getUserGroupIds(), Elca::getInstance()->getProject()->getProcessDbId(), ['name' => 'ASC'], self::PAGE_LIMIT + 1, $this->page * self::PAGE_LIMIT);

        else
            $Elements = ElcaElementSet::findExtended($filter, $Access->hasAdminPrivileges(), $Access->getUserGroupIds(), ['name' => 'ASC'], self::PAGE_LIMIT + 1, $this->page * self::PAGE_LIMIT);

        return $Elements;
    }
    // End getElementSet

}
// End ElcaProjectElementView
