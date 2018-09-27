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
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElement;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProcessViewSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\helpers\ElcaHtmlElementSelectorLink;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlToggleLink;
use Elca\View\helpers\SimpleAttrFormatter;

/**
 * Builds the view for elements within a composite element
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementCompositeView extends HtmlView
{
    /**
     * Read only
     */
    private $readOnly;

    /**
     * @var ElcaElement
     */
    private $compositeElement;

    /**
     * position
     */
    private $position;

    /**
     * Data
     */
    private $Data;

    /**
     * Add a new element
     */
    private $addNewElement;

    /**
     * @var array $highlightedElements
     */
    private $highlightedElements;

    /**
     * @var boolean $isExtantBuilding
     */
    private $isExtantBuilding = false;

    private $activeTabIdent;

    /**
     * @var ElementAssistant
     */
    private $assistant;

    /**
     * @var LifeCycleUsages
     */
    private $lifeCycleUsages;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->Data = $this->get('Data');

        /**
         * Translate elementTypes if given and not empty
         */
        if (is_object($this->Data) && isset($this->Data->elementType) && is_array($this->Data->elementType)) {
            foreach ($this->Data->elementType as $k => $elementType) {
                $this->Data->elementType[$k] = $elementType == '' ? $elementType : t($elementType);
            }
        }

        $this->compositeElement = ElcaElement::findById($this->get('compositeElementId'));
        $this->addNewElement    = $this->get('addNewElement', false);
        $this->context          = $this->get('context', ElementsCtrl::CONTEXT);
        $this->activeTabIdent   = $this->get('activeTabIdent', null);
        $this->lifeCycleUsages  = $this->get('lifeCycleUsages');

        /**
         * If a position is given, render only a single element row
         */
        $this->position = $this->get('position');

        /**
         * Readonly
         */
        if ($this->get('readOnly', false)) {
            $this->readOnly = true;
        }

        /**
         * highlighted elements
         */
        $this->highlightedElements = $this->get('highlightedElements', []);

        /**
         * extant building
         */
        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            $ProjectConstruction    = $this->compositeElement->getProjectVariant()->getProjectConstruction();
            $this->isExtantBuilding = $ProjectConstruction->isExtantBuilding();
        }

        if ($this->has('assistant')) {
            $this->assistant = $this->get('assistant');
        }
    }
    // End init


    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'compositeElementForm';
        $Form   = new HtmlForm($formId, '/' . $this->context . '/saveElements/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');

        $Form->setDataObject($this->Data);

        $Form->add(new HtmlHiddenField('relId', $this->compositeElement->getId()));
        $Form->add(new HtmlHiddenField('opaqueArea', ElcaNumberFormat::toString(round($this->compositeElement->getOpaqueArea(true), 3))));

        if ($this->readOnly) {
            $Form->setReadonly();
        }

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
        }

        if ($this->position) {
            $Assignment = ElcaCompositeElement::findByPk($this->compositeElement->getId(), $this->position);
            $key        = $Assignment->getPosition();

            $Element = $Assignment->getElement();
            $this->appendElement($Form, $key, $Element, $Element->getElementTypeNode()->isOpaque() === false);

            // append form to dummy container
            $DummyContainer = $this->appendChild($this->getDiv());
            $Form->appendTo($DummyContainer);

            // extract element container and replace it with the dummy container
            $Content = $this->getElementById('element-' . $key);
            $this->replaceChild($Content, $DummyContainer);
        } else {
            $Container = $this->appendChild(
                $this->getDiv(
                    [
                        'id'    => 'section-composite',
                        'class' => 'composite-elements element-section',
                    ]
                )
            );

            if ($this->isExtantBuilding) {
                $this->addClass($Container, 'is-extant-building');
            }

            $this->appendCompositeSection($Form);
            $Form->appendTo($Container);
        }
    }
    // End afterRender


    /**
     * Appends the composite section
     *
     * @param  HtmlForm $Form
     */
    protected function appendCompositeSection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('clear');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Bauteilkomponente (opak)'), ['class' => 'hl-element']));
        $Row->add(
            new HtmlTag(
                'h5',
                $this->compositeElement->isTemplate() ? t('Bezugsgröße') : t('Verbaute Menge'),
                ['class' => 'hl-quantity']
            )
        );
        $Row->add(new HtmlTag('h5', 'DIN 276', ['class' => 'hl-dinCode']));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $Row->add(
                new HtmlTag(
                    'h5',
                    t('Bestand'),
                    [
                        'class' => 'hl-is-extant',
                        'title' => t('Als Bestandskomponente markieren (Herstellung wird nicht einbezogen)'),
                    ]
                )
            );
        }

        if (!$this->readOnly) {
            $Row->add(new HtmlTag('h5', t('Verschieben'), ['class' => 'hl-move']));
        }

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-composite']));
        $Container->add(new HtmlHiddenField('a', $this->addNewElement));

        $Ol = $Container->add(new HtmlTag('ol', null, ['class' => 'sortable']));
        $Ol->setAttribute(
            'data-sort-handler-url',
            Url::factory(
                '/' . $this->context . '/sortElements/',
                ['compositeElementId' => $this->compositeElement->getId()]
            )
        );
        $Ol->setAttribute('data-element-id', $this->compositeElement->getId());
        $Ol->setAttribute('data-start-index', '0');
        $counter             = 0;
        $nonOpaqueStartIndex = null;
        $nonOpaqueElements   = [];
        if (isset($this->Data->elementId) &&
            is_array($this->Data->elementId) &&
            count($this->Data->elementId)
        ) {
            foreach ($this->Data->elementId as $key => $elementId) {
                $Element = ElcaElement::findById($elementId);

                if ($this->Data->isOpaque[$key] === false) {
                    if (is_null($nonOpaqueStartIndex)) {
                        $nonOpaqueStartIndex = (int)($this->Data->position[$key]) - 1;
                    }

                    $nonOpaqueElements[$key] = $Element;
                } else {
                    $Li = $Ol->add(
                        new HtmlTag('li', null, ['id' => 'composite-group-' . $elementId, 'class' => 'sortable-item'])
                    );
                    $Li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                    $this->appendElement($Li, $key, $Element);
                    $counter++;
                }
            }
        }

        if ($this->addNewElement) {
            $Li = $Ol->add(new HtmlTag('li'));
            $this->appendElement($Li, 'new');
            $counter++;
        }

        /**
         * Add non opaque elements
         */
        if (count($nonOpaqueElements)) {
            $Row = $Group->add(new HtmlTag('div'));
            $Row->addClass('hl-row clearfix');

            $Row->add(new HtmlTag('h5', t('Bauteilkomponente (nicht-opak)'), ['class' => 'hl-element']));

            $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-composite-non-opaque']));
            $Container->add(new HtmlHiddenField('a', $this->addNewElement));

            $Ol = $Container->add(new HtmlTag('ol', null, ['class' => 'sortable']));
            $Ol->setAttribute(
                'data-sort-handler-url',
                Url::factory(
                    '/' . $this->context . '/sortElements/',
                    ['compositeElementId' => $this->compositeElement->getId(), 'nonOpaque' => 1]
                )
            );
            $Ol->setAttribute('data-element-id', $this->compositeElement->getId());
            $Ol->setAttribute('data-start-index', $nonOpaqueStartIndex);

            foreach ($nonOpaqueElements as $key => $Element) {
                $Li = $Ol->add(
                    new HtmlTag(
                        'li', null, ['id' => 'composite-group-' . $Element->getId(), 'class' => 'sortable-item']
                    )
                );
                $Li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                $this->appendElement($Li, $key, $Element, true);
            }

            $infoDiv = $Container->add(new HtmlTag('div', null, ['class' => 'non-opaque-infos results clearfix']));

            $this->appendInfo($infoDiv, t('Abzugsfläche gesamt'), ElcaNumberFormat::toString($this->compositeElement->getNonOpaqueArea(), 2), Elca::UNIT_M2);
        }

        $this->appendButtons($Container, count($nonOpaqueElements) > 0);
    }
    // End appendComponentsSection


    /**
     * Appends a single element
     *
     * @param HtmlElement $Li
     * @param             $key
     * @param ElcaElement $Element
     * @param bool        $isNonOpaque
     */
    protected function appendElement(HtmlElement $Li, $key, ElcaElement $Element = null, $isNonOpaque = false)
    {
        $canEditElement = $Element ? ElcaAccess::getInstance()->canEditElement($Element) : false;

        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'element clearfix']));
        $Container->setAttribute('id', 'element-' . $key);

        /**
         * Toggler
         */
        $isOpen = isset($this->Data->toggleState[$key]) ? $this->Data->toggleState[$key] : false;

        if ($key != 'new') {
            $Container->add(
                new ElcaHtmlToggleLink(
                    Url::factory(
                        '/' . $this->context . '/toggleElement/',
                        ['pos' => $key, 'compositeElementId' => $this->compositeElement->getId()]
                    ), $isOpen
                )
            );
        }

        if ($isOpen) {
            $Container->addClass('open');
        }

        /**
         * Element select
         */
        $Selector = $Container->add(new ElcaHtmlElementSelectorLink('elementId[' . $key . ']'));
        $Selector->addClass('element-selector');
        $Selector->setRelId($this->compositeElement->getId());
        $Selector->setPosition($key);
        $Selector->setContext($this->context);
        $Selector->setBuildMode(ElcaElementSelectorView::BUILDMODE_ELEMENTS);

        if ($this->isLockedProperty(ElementAssistant::PROPERTY_ELEMENTS_ELEMENT_ID)) {
            $Selector->setReadonly(true, false);
        }

        $Request = FrontController::getInstance()->getRequest();
        if ((isset($this->Data->elementId[$key]) && $this->Data->elementId[$key]) || (isset($Request->elementId[$key]) && $Request->elementId[$key])) {
            $SelectedElement = ElcaElement::findById(
                isset($Request->elementId[$key]) ? $Request->elementId[$key] : $this->Data->elementId[$key]
            );
            if ($SelectedElement->isInitialized()) {
                $Selector->setElementTypeNodeId($SelectedElement->getElementTypeNodeId());
            }

            /**
             * Element properties
             */
            if (!$this->readOnly && $canEditElement && $this->context == ProjectElementsCtrl::CONTEXT) {
                $Qty = $Container->add(
                    new ElcaHtmlFormElementLabel('', $NumInput = new ElcaHtmlNumericInput('quantity[' . $key . ']'))
                );
                $NumInput->setPrecision(3);

                if ($this->isLockedProperty(ElementAssistant::PROPERTY_QUANTITY, $Element)) {
                    $NumInput->setReadonly(true, false);
                }
            } else {
                if ($isNonOpaque) {
                    $this->Data->quantity[$key] = 0;
                }

                $Qty = $Container->add(
                    new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('quantity[' . $key . ']'))
                );
            }

            $Qty->addClass('quantity');

            if ($isNonOpaque && $this->context == ProjectElementsCtrl::CONTEXT && $Element->getRefUnit(
                ) != Elca::UNIT_M2
            ) {
                $txt = sprintf(
                    "%s m² / %s m²",
                    ElcaNumberFormat::toString($Element->getMaxSurface(), 3),
                    ElcaNumberFormat::toString($Element->getMaxSurface(true), 3)
                );
                $Container->add(new ElcaHtmlFormElementLabel('', new HtmlStaticText($txt)))->addClass('surface');
            }

            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlText('refUnit[' . $key . ']')))->addClass(
                'refUnit'
            );
            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlText('dinCode[' . $key . ']')))->addClass(
                'dinCode'
            );
            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlText('elementType[' . $key . ']')))->addClass(
                'elementType'
            );

            if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {

                if (isset($this->Data->isExtant[$key])) {

                    if ($indeterminate = !$this->Data->isExtant[$key] && $Element->hasExtants()) {
                        $val = 'indeterminate';
                    } else {
                        $val = null;
                    }

                    $Container->add(
                        $Label = new ElcaHtmlFormElementLabel(
                            '',
                            $IsExtantCheckbox = new HtmlCheckbox(
                                'isExtant[' . $key . ']',
                                !is_numeric($key) ? true : $val
                            )
                        )
                    );

                    if ($indeterminate) {
                        $IsExtantCheckbox->addClass('indeterminate');
                    }

                    $this->checkElementHighlight($IsExtantCheckbox);
                }
            }
        }

        /**
         * Remove functions
         */
        if (is_numeric($key)) {
            $urlParams = [
                'rel' => $this->compositeElement->getId(),
            ];

            if ($this->activeTabIdent) {
                $urlParams['tab'] = $this->activeTabIdent;
            }

            $Container->add(
                new HtmlLink(
                    $this->readOnly || !$canEditElement ? t('Ansehen') : t('Bearbeiten'),
                    Url::factory(
                        '/' . $this->context . '/' . $this->Data->elementId[$key] . '/',
                        $urlParams
                    )
                )
            )
                      ->addClass('page function-link edit-link');

            if (!$this->readOnly) {

                $unassignElementFnIsLocked = $this->isLockedFunction(
                    ElementAssistant::FUNCTION_ELEMENT_UNASSIGN_ELEMENT
                );
                $deleteElementFnIsLocked   = $this->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_DELETE_ELEMENT);

                if (!$unassignElementFnIsLocked || !$deleteElementFnIsLocked) {
                    $Container->add(new HtmlTag('span', '|'))->addClass('function-separator-2');
                }

                if (!$unassignElementFnIsLocked) {
                    $Container->add(
                        new HtmlLink(
                            t('Entfernen'),
                            Url::factory(
                                '/' . $this->context . '/unassignElement/',
                                [
                                    'pos'                => $key,
                                    'compositeElementId' => $this->compositeElement->getId(),
                                    'e'                  => $this->Data->elementId[$key],
                                ]
                            )
                        )
                    )
                              ->addClass('function-link unassign-link');
                }

                if ($canEditElement && !$deleteElementFnIsLocked) {
                    $Container->add(new HtmlTag('span', '|'))->addClass('function-separator');

                    $Container->add(
                        new HtmlLink(
                            t('Löschen'),
                            Url::factory(
                                '/' . $this->context . '/deleteElement/',
                                [
                                    'id'                 => $this->Data->elementId[$key],
                                    'compositeElementId' => $this->compositeElement->getId(),
                                ]
                            )
                        )
                    )
                              ->addClass('function-link delete-link');
                }

                $Container->add(new HtmlTag('div', null, ['class' => 'drag-handle']));
            }
        } elseif (!$this->readOnly) {
            $Container->add(
                new HtmlLink(
                    t('Abbrechen'),
                    Url::factory('/' . $this->context . '/general/', ['e' => $this->compositeElement->getId()])
                )
            )
                      ->addClass('function-link cancel-link');
        }

        /**
         * Add results table
         */
        if (isset($this->Data->toggleState[$key]) && $this->Data->toggleState[$key]) {
            if ($this->context == ElementsCtrl::CONTEXT) {
                $this->appendElementInfo($Container, $this->Data->elementId[$key]);
            } else {
                $this->appendResults($Container, $Element, $Li);
            }
        }
    }
    // End appendElement


    /**
     * Appends element infos
     *
     * @param HtmlElement $Container
     * @param             $elementId
     * @return void -
     */
    private function appendElementInfo(HtmlElement $Container, $elementId)
    {
        $InfoDiv = $Container->add(new HtmlTag('div', null, ['class' => 'results clearfix']));

        $ProcessConfigs = ElcaProcessConfigSet::findByElementId($elementId, ['name' => 'ASC']);
        $this->appendInfo(
            $InfoDiv,
            t('Baustoffe'),
            implode(', ', $ProcessConfigs->map(function(ElcaProcessConfig $processConfig) {return \processConfigName($processConfig->getId());}))
        );

        $processDbNames = ElcaProcessDbSet::findElementCompatibles(ElcaElement::findById($elementId), ['version' => 'ASC'])
            ->getArrayBy('name');

        $this->appendInfo($InfoDiv, t('Baustoffdatenbanken'), $processDbNames ? implode(', ', $processDbNames) : t('keine'));
    }
    // End appendElementInfo


    /**
     * Appends the result table
     *
     * @param HtmlElement $container
     * @param             $Element
     * @return void -
     */
    private function appendResults(HtmlElement $container, $Element, HtmlElement $parentContainer)
    {
        if (!$Element) {
            return;
        }

        /**
         * Build indicator result table
         */
        $indicatorSet = ElcaProcessViewSet::findResultsByElementId($Element->getId());

        if ($indicatorSet->count()) {
            $doList     = [];
            $indicators = [];

            foreach ($indicatorSet as $indicator) {
                $key = $indicator->life_cycle_ident;

                if (Module::fromValue($key)->isA1A2OrA3()) {
                    continue;
                }

                if (!isset($doList[$key])) {
                    $DO                = $doList[$key] = new \stdClass();
                    $DO->lifeCycleName = t($indicator->life_cycle_name);

                    $DO->lifeCycleIdent  = $indicator->life_cycle_ident;
                    $DO->lifeCyclePOrder = $key !== 'total'
                        ? $indicator->life_cycle_p_order
                        : 999;

                    $DO->usedInCalculation = $key === 'total'
                        ?: $this->lifeCycleUsages->moduleIsAppliedInTotals(new Module($indicator->life_cycle_ident));
                } else {
                    $DO = $doList[$key];
                }

                $indicatorId              = $indicator->indicator_ident;
                $DO->$indicatorId         = $indicator->value;// / $quantity;
                $indicators[$indicatorId] = t($indicator->indicator_name);
            }

            usort(
                $doList,
                function ($a, $b) {
                    $a = (int)((int)(!$a->usedInCalculation) . str_pad($a->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));
                    $b = (int)((int)(!$b->usedInCalculation) . str_pad($b->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));

                    return $a > $b ? 1 : -1;
                }
            );

            $Table = $container->add(new HtmlTable('element-summary'));
            $Table->addColumn('lifeCycleName', t('Lebenszyklus'));

            foreach ($indicators as $indicatorId => $indicatorName) {
                $Table->addColumn($indicatorId, $indicatorName);
            }

            $Head    = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row  = $Body->addTableRow();
            $Row->addAttrFormatter(new SimpleAttrFormatter('class', 'lifeCycleIdent', true));

            foreach ($indicators as $indicatorId => $indicatorName) {
                $Row->getColumn($indicatorId)->setOutputElement(
                    new ElcaHtmlNumericText($indicatorId, 4, false, '?', null, null, true)
                );
            }

            $Body->setDataSet($doList);
        }
        else {
            $parentContainer->addClass('no-results');
        }

        /**
         * Some more infos
         */
        $CElement = ElcaCacheElement::findByElementId($Element->getId());
        $InfoDiv  = $container->add(new HtmlTag('div', null, ['class' => 'results clearfix']));

        $this->appendInfo($InfoDiv, t('Masse'), ElcaNumberFormat::toString($CElement->getMass(), 2), 'kg');
    }
    // End appendResults


    /**
     * Appends one single info
     *
     * @param  HtmlElement $Container
     * @param  string      $caption
     * @param  string      $value
     * @param string       $refUnit
     * @return HtmlElement
     */
    private function appendInfo($Container, $caption, $value = null, $refUnit = '')
    {
        if ($refUnit) {
            $refUnit = ElcaNumberFormat::formatUnit($refUnit);
        }

        $Info = $Container->add(new HtmlTag('div', null, ['class' => 'info']));
        $Info->add(new HtmlTag('span', $caption, ['class' => 'caption']));

        if (!is_null($value)) {
            $Info->add(new HtmlTag('span', $value . ' ' . $refUnit, ['class' => 'value']));
        }

        return $Info;
    }
    // End appendInfo


    /**
     * Appends submit button
     *
     * @param  HtmlElement $Container
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $Container, bool $hasNonOpaqueElements = false)
    {
        if ($this->readOnly) {
            return;
        }

        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        /**
         * Add save button
         */
        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            if ($hasNonOpaqueElements && $this->compositeElement->getRefUnit() === Elca::UNIT_M2) {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('refreshOpaqueElements', t('Abzugsfläche anwenden')))
                            ->addClass('refresh-opaque-elements');
            }

            $Button = $ButtonGroup->add(new ElcaHtmlSubmitButton('saveElements', t('Speichern'), true));
            $Button->addClass('save-elements');
        }

        /**
         * Add new component button
         */
        if (!$this->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_ADD_ELEMENT)) {
            $Button = $ButtonGroup->add(new ElcaHtmlSubmitButton('addElement', t('Neue Bauteilkomponente hinzufügen')));
            $Button->addClass('add-element');
        }
    }
    // End appendSubmitButton


    /**
     * Checks if an element needs highlightning
     */
    private function checkElementHighlight(HtmlFormElement $Element)
    {
        if (isset($this->highlightedElements[$Element->getName()])) {
            $Element->addClass('highlight');
        }
    }
    // End checkElementHighlight

    /**
     * @param string      $what
     * @param ElcaElement $element
     * @return bool
     */
    private function isLockedProperty($what, ElcaElement $element = null)
    {
        if (!$this->assistant instanceof ElementAssistant) {
            return false;
        }

        return $this->assistant->isLockedProperty($what, $element);
    }

    /**
     * @param string $what
     * @return bool
     */
    private function isLockedFunction($what)
    {
        if (!$this->assistant instanceof ElementAssistant) {
            return false;
        }

        return $this->assistant->isLockedFunction($what);
    }

}
// End ElcaElementComponentsView
