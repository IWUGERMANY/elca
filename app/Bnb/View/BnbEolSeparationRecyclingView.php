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
namespace Bnb\View;

use Beibob\Blibs\HtmlView;
use Beibob\Blibs\FrontController;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;
/**
 * BnbXmlExportView
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class BnbEolSeparationRecyclingView extends HtmlView
{
    /**
     * Data
     */
    private $Data;

    private $readOnly;

    /**
     * Initialize view
     *
     * @param array $args
     */
    public function init(array $args = [])
    {
        parent::init($args);

        $this->Data = $this->get('Data', new \stdClass());

        $this->readOnly = $this->get('readOnly');
    }
    // End __construct

    /**
     *
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'bnb-4-1-4']));

        $Form = new HtmlForm('bnb414', '/bnb/eol-separation-recycling/save/');
        $Form->addClass('clearfix highlight-changes');
        $Form->setReadonly($this->readOnly);

        if($this->has('Validator'))
        {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        $Form->setDataObject($this->Data);
        $Form->add(new HtmlHiddenField('projectVariantId', $this->Data->projectVariantId));


        $ListContainer = $Form->add(new HtmlTag('div', null, ['class' => 'element-types-container']));

        /**
         * Iterate over element types and elements in KG300
         */
        $RootElementType = ElcaElementType::findByIdent('300');
        foreach (ElcaElementTypeSet::findWithElementsByParentType($RootElementType, $this->Data->projectVariantId, true, null, true, null, false) as $ElementType) {
            $Group = $ListContainer->add(new HtmlFormGroup($ElementType->getDinCode() . ' ' . t($ElementType->getName())));
            $Group->addClass('bnb-element-type');
            $Group->add(new HtmlTag('h4', t('Rückbau'), ['class' => 'eol']));
            $Group->add(new HtmlTag('h4', t('Trennung'), ['class' => 'separation']));
            $Group->add(new HtmlTag('h4', t('Verwertung'), ['class' => 'recycling']));

            $Ul = $Group->add(new HtmlTag('ul', null, ['class' => 'bnb-elements']));
            $Elements = ElcaElementSet::findUnassignedByElementTypeNodeId($ElementType->getNodeId(), $this->Data->projectVariantId);

            foreach ($Elements as $Element) {
                $elementId = $Element->getId();
                $Li = $Ul->add(new HtmlTag('li'));
                $Link = $Li->add(new HtmlLink($Element->getName(), '/project-elements/'. $Element->getId().'/'));
                $Link->addClass('page');

                $Li->add(new ElcaHtmlFormElementLabel('', $Elt = new ElcaHtmlNumericInput('eol['. $elementId .']')));
                $Elt->addClass('eol');
                $Li->add(new ElcaHtmlFormElementLabel('', $Elt = new ElcaHtmlNumericInput('separation['. $elementId .']')));
                $Elt->addClass('separation');
                $Li->add(new ElcaHtmlFormElementLabel('', $Elt = new ElcaHtmlNumericInput('recycling['. $elementId .']')));
                $Elt->addClass('recycling');
                //$Span->add(new ElcaHtmlNumericInput('separation['. $elementId .']'))->addClass('separation');
                //$Span->add(new ElcaHtmlNumericInput('recycling['. $elementId .']'))->addClass('recycling');

                // mark incomplete entries
                if (isset($this->Data->eol[$elementId]) || isset($this->Data->separation[$elementId]) || isset($this->Data->recycling[$elementId])) {

                    if (!$this->Data->eol[$elementId] || !$this->Data->separation[$elementId] || !$this->Data->recycling[$elementId])
                        $Li->addClass('incomplete');
                    else
                        $Li->addClass('complete');
                }
            }
        }

        /**
         * Buttons bottom
         */
        if (!$this->readOnly) {
            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('clearfix buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        }

        $Form->appendTo($Container);
    }
    // End afterRender

}
// End BnbEolSeparationRecyclingView