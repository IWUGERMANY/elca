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

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOptGroup;
use Beibob\HtmlTools\HtmlSelectOption;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the element type selector
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementTypeSelectorView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';

    /**
     * action
     */
    private $action;

    /**
     * BuildMode
     */
    private $buildMode;

    /**
     * Element id
     */
    private $elementId;

    /**
     * Element
     */
    private $Element;

    /**
     * Current element type nodeId
     */
    private $elementTypeNodeId;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view
     *
     * @param  array $args
     */
    public function init(array $args = [])
    {
        $this->setTplName('elca_element_type_selector');
        $this->action = $this->get('action');
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);

        $this->elementId = $this->get('elementId');
        $this->Element = ElcaElement::findById($this->elementId);
        $this->elementTypeNodeId = $this->get('elementTypeNodeId', $this->Element->getElementTypeNodeId());

        $this->assign('headline', 'Bauteilgruppe wählen');

        if($this->Element->hasCompositeElement())
            $this->assign('info', 'Es werden nur Bauteilgruppen innerhalb der gleichen Kostengruppe zur Auswahl gestellt.');
        else
            $this->assign('info', 'Es werden nur Bauteilgruppen der dritten Ebene zur Auswahl gestellt.');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        // init variables
        $Container = $this->getElementById('elca-element-type-selector-form-holder');

        /**
         * Form
         */
        $Form = new HtmlForm('elementTypeSelectorForm', $this->action);
        $Form->setAttribute('id', 'elementTypeSelectorForm');
        $Form->setAttribute('class', 'clearfix modal-selector-form');

        $Form->add(new HtmlHiddenField('t', $this->elementTypeNodeId));
        $Form->add(new HtmlHiddenField('id', $this->elementId));
        $Form->add(new HtmlHiddenField('b', $this->buildMode));

        $DataObject = new \stdClass();
        $DataObject->nodeId = $this->elementTypeNodeId;
        $Form->setDataObject($DataObject);

        /**
         * ElementTypes
         */
        $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Bauteilgruppe'), new HtmlSelectbox('nodeId'), true));
        $Select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));

        if($this->Element->hasCompositeElement())
        {
            $ParentElementType = ElcaElementType::findParentByNodeId($this->elementTypeNodeId);
            $ElementTypeSet = ElcaElementTypeSet::findByParentType($ParentElementType);
            foreach($ElementTypeSet as $Type)
                $Select->add(new HtmlSelectOption($Type->getDinCode(). ' - '. $Type->getName(), $Type->getNodeId()));
        }
        else
        {
            $ParentElementType = ElcaElementType::findParentByNodeId($this->elementTypeNodeId);
            $RootElementType = ElcaElementType::findParentByNodeId($ParentElementType->getNodeId());

            foreach(ElcaElementTypeSet::findByParentType($RootElementType) as $SecLevelType)
            {
                $OptGroup = $Select->add(new HtmlSelectOptGroup($SecLevelType->getDinCode(). ' - '. $SecLevelType->getName()));

                $ElementTypeSet = ElcaElementTypeSet::findByParentType($SecLevelType);
                foreach($ElementTypeSet as $Type)
                    $Opt = $OptGroup->add(new HtmlSelectOption($Type->getDinCode(). ' - '. $Type->getName(), $Type->getNodeId()));
            }
        }

        /**
         * Buttons
         */
        $Button = $Form->add(new ElcaHtmlSubmitButton('select', 'Übernehmen'));
        $Form->appendTo($Container);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaElementTypeSelectorView
