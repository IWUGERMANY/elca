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
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckboxSimple;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessConfig;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 * Renders Search & Replace Screen
 *
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectSearchAndReplaceProcessesView extends HtmlView
{
    /**
     * Properties
     */
    private $Project;
    private $ProjectVariant;
    private $context;


    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->Project = $this->get('Project');
        $this->ProjectVariant = $this->get('ProjectVariant');
        $this->context = 'project-data';
    }
    // End init

    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'projectSearchAndReplaceProcessesForm';
        $Form = new HtmlForm($formId, '/project-data/replaceProcesses/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        $FormData = $this->get('FormData', new \stdClass());
        $Form->setDataObject($FormData);

        $Form->add(new HtmlHiddenField('projectId', $this->Project->getId()));
        $Form->add(new HtmlHiddenField('projectVariantId', $this->ProjectVariant->getId()));

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'project-search-and-replace-processes']));

        $Group = $Form->add(new HtmlFormGroup(''));

        $Group->add($label = new ElcaHtmlFormElementLabel(t('Suchen nach:')));
        $linkAttr = [];
        $linkAttr['href'] = Url::factory('/project-data/replaceProcesses/', ['relId' => $this->ProjectVariant->getId(), 'openpcs' => null, 'f' => 'search']);
        $linkAttr['class'] = 'open-modal';
        $caption = isset($FormData->searchForName) && $FormData->searchForName ? $FormData->searchForName : t('Baustoff wählen');
        $label->add(new HtmlTag('a', $caption, $linkAttr));

        $Group->add($label = new ElcaHtmlFormElementLabel(t('Ersetzen durch:')));
        $linkAttr['href'] = Url::factory('/project-data/replaceProcesses/', ['relId' => $this->ProjectVariant->getId(), 'openpcs' => null, 'f' => 'replace']);
        $caption = isset($FormData->replaceWithName) && $FormData->replaceWithName ? $FormData->replaceWithName : t('Baustoff wählen');
        $label->add(new HtmlTag('a', $caption, $linkAttr));

        $Div = $Form->add(new HtmlTag('div'));

        if ($this->get('ResultSet'))
            $this->appendResultTable($Div);

        $Div = $Form->add(new HtmlTag('div'));
        $this->appendButtons($Div);

        $Form->appendTo($Container);
    }
    // End afterRender
    

    /**
     *
     */
    protected function appendResultTable(HtmlElement $Container)
    {
        if (!$this->get('ResultSet')->count())
        {
            $Div = $Container->add(new HtmlTag('div'));
            $Div->add(new HtmlTag('p', t('Der gewählte Baustoff wird in dieser Projektvariante nicht verwendet.')));
            return;
        }

        $FormData = $this->get('FormData');

        $availableUnits = [];
        if (isset($FormData->replaceWithId))
        {
            $ReplaceWithProcessConfig = ElcaProcessConfig::findById($FormData->replaceWithId);
            list($RequiredConversions, $AvailableConversions) = $ReplaceWithProcessConfig->getRequiredConversions();

            $availableUnits = array_flip(array_unique($RequiredConversions->getArrayBy('inUnit', 'id') + $AvailableConversions->getArrayBy('inUnit', 'id')));
        }

        $elementId = false;
        $groups = [];
        $uls = [];
        $lastSiblingId = false;
        foreach ($this->get('ResultSet') as $ResultItem) {

            /**
             * Build group with table header for each element type
             */
            if (!isset($groups[$ResultItem->din_code])) {
                $groups[$ResultItem->din_code] = new HtmlFormGroup($ResultItem->din_code . ' ' . t($ResultItem->element_type_name));
                $header = $groups[$ResultItem->din_code]->add(new HtmlTag('div', null, ['class' => 'header']));
                $header->add(new HtmlTag('p', t('Menge'), ['class' => 'quantity']));

                if (isset($FormData->replaceWithId))
                {
                    $header->add(new HtmlTag('p', t('Umrechnung'), ['class' => 'conversion']));
                    $header->add(new HtmlTag('p', t('Dicke [mm]'), ['class' => 'layer-size']));
                    $header->add(new HtmlTag('p', t('neu'), ['class' => 'layer-size-new']));
                    $header->add(new HtmlTag('p', t('Austausch'), ['class' => 'lifetime']));
                    $header->add(new HtmlTag('p', t('neu'), ['class' => 'lifetime-new']));
                    $header->add(new HtmlTag('a', t('Alle'), ['href' => '#', 'class' => 'feature all no-xhr']));
                    $header->add(new HtmlTag('a', t('Umkehren'), ['href' => '#', 'class' => 'feature invert no-xhr']));
                }
                $header->add(new HtmlTag('div', 'x', ['class' => 'clear']));
                
                $uls[$ResultItem->din_code] = $groups[$ResultItem->din_code]->add(new HtmlTag('ul', null, ['class' => 's-r-results']));
            }

            /**
             * Build list item for each element
             */
            if (!$elementId || $elementId != $ResultItem->element_id) {
                $Li = $uls[$ResultItem->din_code]->add(new HtmlTag('li'));

                $Div = $Li->add(new HtmlTag('div', null, ['class' => 'element']));
                $element = ElcaElement::findById($ResultItem->element_id);

                if ($element->hasCompositeElement()) {
                    $compositeElement = $element->getCompositeElement();
                    $Div->add(new HtmlStaticText($compositeElement->getName()));
                }
                $Div = $Li->add(new HtmlTag('div', null, ['class' => 'element']));
                $href = Url::factory('/project-elements/' . $ResultItem->element_id . '/');
                $Link = $Div->add(new HtmlLink($ResultItem->name . ' [' . $ResultItem->element_id . ']', $href));
                $Link->addClass('page');
                $elementId = $ResultItem->element_id;

                $Div->add(new HtmlTag('span', ElcaNumberFormat::formatQuantity($ResultItem->element_quantity, $ResultItem->element_ref_unit), ['class' => 'quantity']));
                $lastSiblingId = false;
            }

            /**
             * One container for each component
             */
            $ProcessConfig = ElcaProcessConfig::findById($ResultItem->process_config_id);
            $PCD = $Li->add(new HtmlTag('div', null, ['class' => 'process-config']));
            $PCD->add(new HtmlTag('span', $ProcessConfig->getName(), ['class' => 'name']));

            /**
             * Layer position
             */
            if (!$ResultItem->is_layer)
                $layerPosition = '';
            elseif ($ResultItem->layer_sibling_id) {
                $layerPosition = ($lastSiblingId == $ResultItem->id) ? '' : $ResultItem->layer_position . '.';
                $lastSiblingId = $ResultItem->layer_sibling_id;
            } else {
                $layerPosition = $ResultItem->layer_position . '.';
            }
            $PCD->add(new HtmlTag('span', $layerPosition, ['class' => 'layer-position']));

            /**
             * Proportion if layer is a 'Gefach'
             */
            $ratio = $ResultItem->layer_area_ratio < 1 ? ElcaNumberFormat::toString($ResultItem->layer_area_ratio, null, true) . ' %' : '';
            $PCD->add(new HtmlTag('span', $ratio, ['class' => 'ratio']));

            /**
             * If component has to be replaced ...
             */
            if ($ProcessConfig->getId() == $FormData->searchForId)
            {
                $PCD->addClass('match');

                /**
                 * Display quantity
                 */
                $quantity = $ResultItem->is_layer ? $ResultItem->layer_size * $ResultItem->layer_width * $ResultItem->layer_length * $ResultItem->layer_area_ratio * $ResultItem->element_quantity : $ResultItem->quantity * $ResultItem->element_quantity ;
                $unit = $ResultItem->is_layer ? Elca::UNIT_M3 : $ResultItem->component_unit;
                $PCD->add(new HtmlTag('span', ElcaNumberFormat::formatQuantity($quantity, $unit, 3), ['class' => 'quantity']));

                if (isset($FormData->replaceWithId)) {

                    $forbidden = false;

                    /**
                     * Check if component can be replaced and if so check if quantity has to be converted
                     */
                    $usedUnit = $ResultItem->is_layer ? Elca::UNIT_M3 : $ResultItem->component_unit;
                    if (!isset($availableUnits[$usedUnit]))
                    {
                        if ($ResultItem->is_layer)
                            $forbidden = true;
                        else
                        {
                            $matrix = $ProcessConfig->getConversionMatrix();
                            foreach ($matrix[$usedUnit] as $unit => $factor)
                            {
                                if (isset($availableUnits[$unit]))
                                    $outUnit = $unit;
                            }

                            $quantity = $matrix[$usedUnit][$outUnit] * $ResultItem->quantity * $ResultItem->element_quantity;

                            $PCD->add(new HtmlTag('span', '⇒ ' . ElcaNumberFormat::formatQuantity($quantity, $outUnit, 3), ['class' => 'conversion']));
                        }
                    }

                    /**
                     * Build input elements to control replacement
                     */
                    if (!$forbidden) {
                        if ($ResultItem->is_layer) {
                            $PCD->add(new HtmlTag('span', ElcaNumberFormat::formatQuantity($ResultItem->layer_size * 1000, 'mm'), ['class' => 'layer-size-old']));
                            $Span = $PCD->add(new HtmlTag('span', null, ['class' => 'layer-size-new']));
                            $Span->add(new ElcaHtmlNumericInput('newLayerSize['.$ResultItem->id.']'));
                        }

                        $PCD->add(new HtmlTag('span', $ResultItem->life_time, ['class' => 'lifetime-old']));
                        $Span = $PCD->add(new HtmlTag('span', null, ['class' => 'lifetime-new']));
                        $Span->add(new ElcaHtmlNumericInput('newLifetime[' . $ResultItem->id . ']'));

                        $Span = $PCD->add(new HtmlTag('span', null, ['class' => 'checkbox']));
                        $Span->add(new HtmlCheckboxSimple('replaceConfirmed['. $ResultItem->id . ']', 1));
                    }
                    else
                        $PCD->add(new HtmlTag('span', t('Austausch wegen inkompatibler Einheiten nicht möglich!'), ['class' => 'forbidden']));

                }
            }

            $PCD->add(new HtmlTag('div', 'x', ['class' => 'clear']));
        }

        /**
         * Append all groups
         */
        $keys = array_keys($groups);
        natsort($keys);
        foreach ($keys as $key)
            $Container->add($groups[$key]);
    }
    // End appendResultTable


    /**
     * Appends submit button
     *
     * @param  HtmlElement $Container
     * @param  string $name
     * @param  string $caption
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $Container)
    {
        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        $FormData = $this->get('FormData');
        if($this->get('ResultSet') && $this->get('ResultSet')->count() || isset($FormData->replaceWithId))
        {
            $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abrechen')));

            if (isset($FormData->replaceWithId)) {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('doReplace', t('Ersetzen'), true));
            }
        }
    }
    // End appendSubmitButton

}
// End ElcaProjectSearchAndReplaceProcessesView
