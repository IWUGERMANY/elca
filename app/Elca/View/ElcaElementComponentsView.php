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
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaCacheElementComponent;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentAttribute;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessViewSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Service\Assistant\ElementAssistant;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlLifeTimeInput;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlToggleLink;
use Elca\View\helpers\ElcaNumberFormatConverter;
use Elca\View\helpers\ElcaProcessesConverter;
use Elca\View\helpers\ElcaTranslatorConverter;
use Elca\View\helpers\SimpleAttrFormatter;

/**
 * Builds the element component view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementComponentsView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_LAYERS = 'layers';
    const BUILDMODE_COMPONENTS = 'components';

    /**
     * Read only
     */
    private $readOnly;

    /**
     * Current buildmode
     */
    private $buildMode;

    /**
     * Current context
     */
    private $context;

    /**
     * Element
     */
    private $Element;

    /**
     * elementComponentId
     */
    private $elementComponentId;

    /**
     * Data
     */
    private $Data;

    /**
     * Add a new component
     */
    private $addNewComponent;

    /**
     * Changed elements
     */
    private $changedElements = [];

    /**
     * Highlighted elements
     */
    private $highlightedElements = [];

    /**
     * @var boolean $isExtantBuilding
     */
    private $isExtantBuilding = false;

    /**
     * @var ElementAssistant
     */
    private $assistant;

    /**
     * @var LifeCycleUsages $lifeCycleUsages
     */
    private $lifeCycleUsages;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->Data            = $this->get('Data');
        $this->Element         = ElcaElement::findById($this->get('elementId'));
        $this->buildMode       = $this->get('buildMode', self::BUILDMODE_COMPONENTS);
        $this->addNewComponent = $this->get('addNewComponent', false);
        $this->context         = $this->get('context', ElementsCtrl::CONTEXT);
        $this->lifeCycleUsages = $this->get('lifeCycleUsages');

        /**
         * If an elementComponentId is given, render only this component row
         */
        $this->elementComponentId = $this->get('elementComponentId');

        /**
         * Readonly
         */
        if ($this->get('readOnly', false))
            $this->readOnly = true;

        if ($this->has('assistant')) {
            $this->assistant = $this->get('assistant');
        }

        /**
         * Changed elements
         */
        if ($this->has('changedElements'))
            $this->changedElements = $this->get('changedElements');

        /**
         * Highlighted elements
         */
        if ($this->has('highlightedElements'))
            $this->highlightedElements = $this->get('highlightedElements');

        /**
         * extant building
         */
        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            $ProjectConstruction = $this->Element->getProjectVariant()->getProjectConstruction();
            $this->isExtantBuilding = $ProjectConstruction->isExtantBuilding();
        }
    }
    // End init


    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'elementComponentForm_' . $this->buildMode;
        $Form = new HtmlForm($formId, '/' . $this->context . '/saveComponents/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        $Form->setDataObject($this->Data);

        $Form->add(new HtmlHiddenField('elementId', $this->Element->getId()));
        $Form->add(new HtmlHiddenField('b', $this->buildMode));

        if ($this->readOnly)
            $Form->setReadonly();

        if ($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        if ($this->elementComponentId) {
            $key = $this->elementComponentId;
            $Component = ElcaElementComponent::findById($key);
            if ($this->buildMode == self::BUILDMODE_LAYERS)
                $this->appendLayer($Form, $key, $Component);
            else
                $this->appendSingleComponent($Form, $key, $Component);

            // append form to dummy container
            $DummyContainer = $this->appendChild($this->getDiv());
            $Form->appendTo($DummyContainer);

            // extract elementComponent container and replace it with the dummy container
            $Content = $this->getElementById('component-' . $key);
            $this->replaceChild($Content, $DummyContainer);
        } else {
            $Container = $this->appendChild($this->getDiv(['id'    => 'section-' . $this->buildMode,
                                                           'class' => 'element-components element-section']));

            if ($this->isExtantBuilding) {
                $this->addClass($Container, 'is-extant-building');
            }
            if ($this->buildMode == self::BUILDMODE_COMPONENTS)
                $this->appendComponentsSection($Form);
            else
                $this->appendGeometrySection($Form);

            $Form->appendTo($Container);
        }
    }
    // End afterRender


    /**
     * Appends the geometry section
     *
     * @param  HtmlForm $Form
     */
    protected function appendGeometrySection(HtmlForm $Form)
    {
        $title = t('Bauteilgeometrie (von innen nach außen)');

        $Group = $Form->add(new HtmlFormGroup($title));
        $Group->addClass('clear');

        if ($this->Element->getElementTypeNode()->isOpaque() === false && ($maxSurface = $this->Element->getMaxSurface()))
            $Group->add(new HtmlTag('span', t('Abzugsfläche') . ' ' . ElcaNumberFormat::toString($maxSurface, 3) . ' m²', ['class' => 'area']));

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Schicht'), ['class' => 'hl-layer']));
        $Row->add(new HtmlTag('h5', t('Dicke') . ' mm', ['class' => 'hl-size']));
        $Row->add(new HtmlTag('h5', t('Länge') . 'x' . 'Breite' . ' m', ['class' => 'hl-width-length']));
        $Row->add(new HtmlTag('h5', t('Anteil') . '%', ['class' => 'hl-sibling']));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $labelText = t('Austausch/Rest');
            $toolTip = t('Austausch / Erster Austausch nach ... Jahren');
        } else {
            $labelText = t('Austausch');
            $toolTip = t('Austausch in Jahren');
        }
        $Row->add(new HtmlTag('h5', $labelText, ['class' => 'hl-life-time', 'title' => $toolTip]));

        $Row->add(new HtmlTag('h5', t('Bilanz'), ['class' => 'hl-is-active', 'title' => t('In die Bilanz einbeziehen')]));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT)
            $Row->add(new HtmlTag('h5', t('Bestand'), ['class' => 'hl-is-extant', 'title' => t('Als Bestandskomponente markieren (Herstellung wird nicht einbezogen)')]));

        $Row->add(new HtmlTag('h5', t('Verschieben'), ['class' => 'hl-move']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-' . $this->buildMode]));
        $Container->add(new HtmlHiddenField('a', $this->addNewComponent));

        // sortable container
        $Ol = $Container->add(new HtmlTag('ol', null, ['class' => 'sortable']));
        $Ol->setAttribute('data-sort-handler-url', Url::factory('/' . $this->context . '/sortComponents/', ['elementId' => $this->Element->getId()]));
        $Ol->setAttribute('data-element-id', $this->Element->getId());

        $counter = 0;
        if (isset($this->Data->processConfigId) &&
            is_array($this->Data->processConfigId) &&
            count($this->Data->processConfigId)
        ) {
            $siblings = [];
            foreach ($this->Data->processConfigId as $key => $foo) {
                $Component = ElcaElementComponent::findById($key);

                $isSibling = false;
                if ($siblingId = $Component->getLayerSiblingId()) {
                    if (isset($siblings[$siblingId]) && $siblings[$siblingId] instanceof HtmlElement) {
                        $Li = $siblings[$siblingId];
                        $isSibling = true;
                    } else
                        $Li = $siblings[$key] = $Ol->add(new HtmlTag('li', null, ['id' => 'component-group-' . $key, 'class' => 'sortable-item']));

                    $Li->addClass('siblings');
                } else
                    $Li = $Ol->add(new HtmlTag('li', null, ['id' => 'component-group-' . $key, 'class' => 'sortable-item']));

                $Li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                $this->appendLayer($Li, $key, $Component, $isSibling);
                $counter++;
            }
        }

        if ($this->addNewComponent) {
            $Li = $Ol->add(new HtmlTag('li'));
            $this->appendLayer($Li, 'new_' . $this->buildMode);
            $counter++;
        }

        $this->appendButtons($Container, $counter > 0);
    }
    // End appendGeometrySection


    /**
     * Appends a layer component
     *
     * @param HtmlElement          $Li
     * @param                      $key
     * @param ElcaElementComponent $Component
     * @param bool                 $isSibling
     */
    protected function appendLayer(HtmlElement $Li, $key, ElcaElementComponent $Component = null, $isSibling = false)
    {
        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'element-component']));
        $Container->setAttribute('id', 'component-' . $key);
        $Container->addClass('clearfix element-component ' . $this->buildMode);

        if (isset($this->Data->isExtant[$key]) && $this->Data->isExtant[$key]) {
            $Container->addClass('is-extant');
        }

        if ($isSibling) {
            $Container->addClass('sibling');
        }

        /**
         * Position
         */
        if ($Component && $Component->isInitialized())
            $Container->add(new HtmlHiddenField('position[' . $key . ']', $Component->getLayerPosition()));
        else
            $Container->add(new HtmlHiddenField('position[' . $key . ']', ''));

        /**
         * <<<<<<< HEAD
         * =======
         * Toggler
         */
        if ($key != 'new_' . $this->buildMode)
            $Container->add(new ElcaHtmlToggleLink(Url::factory('/' . $this->context . '/toggleComponent/', ['componentId' => $key,
                                                                                                             'b'           => $this->buildMode]), $this->Data->toggleState[$key]));

        /**
         * >>>>>>> feature/i18n
         * ProcessConfig selector
         */
        $Container->add($Selector = new ElcaHtmlProcessConfigSelectorLink('processConfigId[' . $key . ']'));

        if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID, $Component))
            $Selector->setReadonly(true, false);

        $Selector->addClass('process-config-selector');
        $Selector->setElementId($this->Element->getId());
        $Selector->setRelId($key);
        $Selector->setBuildMode($this->buildMode);
        $Selector->setContext($this->context);

        if ($this->context == ProjectElementsCtrl::CONTEXT)
            $Selector->setProcessDbId(Elca::getInstance()->getProject()->getProcessDbId());

        $this->checkElementChange($Selector);

        $Request = FrontController::getInstance()->getRequest();
        if ((isset($this->Data->processConfigId[$key]) && $this->Data->processConfigId[$key]) || (isset($Request->processConfigId[$key]) && $Request->processConfigId[$key])) {
            $ProcessConfig = ElcaProcessConfig::findById(isset($Request->processConfigId[$key]) ? $Request->processConfigId[$key] : $this->Data->processConfigId[$key]);
            if ($ProcessConfig->isInitialized())
                $Selector->setProcessCategoryNodeId($ProcessConfig->getProcessCategoryNodeId());

            if ($this->context == ProjectElementsCtrl::CONTEXT && $Component !== null) {
                $CacheComponent = ElcaCacheElementComponent::findByElementComponentId($Component->getId());
                if ($CacheComponent->isInitialized()) {
                    if (!ElcaProcessViewSet::findResultsByElementComponentId($Component->getId())->count()) {
                        $Li->addClass('no-results');
                        $this->Data->toggleState[$key] = true;
                    }
                } else {
                    $Li->addClass('no-results');
                    $this->Data->toggleState[$key] = true;
                }
            }
            if ($ProcessConfig->isStale() || $ProcessConfig->isUnknown()) {
                $Li->addClass('no-results');
                $this->Data->toggleState[$key] = true;
            }

            /**
             * Add Sibling link
             */
            if (!$this->readOnly && $Component && $Component->isInitialized() && !$Component->hasLayerSibling() &&
                !$this->isLockedFunction(ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING, $Component)
            ) {
                $Container->add(new HtmlLink(t('Gefach'), Url::factory('/' . $this->context . '/addComponentSibling/', ['componentId' => $key])))
                    ->addClass('function-link add-sibling-link confirm');

                $Container->add(new HtmlTag('span', '|'))->addClass('function-separator');
            }

            $NumberFormatConverter = new ElcaNumberFormatConverter(1, true);

            /**
             * Component properties
             */
            $Container->add(new ElcaHtmlFormElementLabel('', $numericInput = new ElcaHtmlNumericInput('size[' . $key . ']')));
            $numericInput->setPrecision(2);
            $this->checkElementChange($numericInput);

            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_LAYER_SIZE, $Component))
                $numericInput->setReadonly(true, false);

            $Link = $Container->add(new HtmlLink(t('Länge und Breite einblenden')));
            $Link->addClass('no-xhr show-dimension-icon');
            $Link->setAttribute('title', t('Länge und Breite einblenden'));
            $Container->add(new ElcaHtmlFormElementLabel('', $numericInput = new ElcaHtmlNumericInput('length[' . $key . ']')));
            $numericInput->setPrecision(2);
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_LAYER_LENGTH, $Component))
                $numericInput->setReadonly(true, false);

            $Container->add(new ElcaHtmlFormElementLabel('', $numericInput = new ElcaHtmlNumericInput('width[' . $key . ']')));
            $numericInput->setPrecision(2);
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_LAYER_WIDTH, $Component))
                $numericInput->setReadonly(true, false);

            $Container->add(new ElcaHtmlFormElementLabel('', $numericInput = new ElcaHtmlNumericInput('areaRatio[' . $key . ']', null, $this->readOnly, $NumberFormatConverter)));
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_LAYER_AREA_RATIO, $Component))
                $numericInput->setReadonly(true, false);

            $Container->add(new ElcaHtmlFormElementLabel('', ($LifeTimeElt = new ElcaHtmlLifeTimeInput('lifeTime[' . $key . ']'))));
            $LifeTimeElt->setPrecision(0);
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_LIFE_TIME, $Component))
                $LifeTimeElt->setReadonly(true, false);

            $this->checkElementChange($LifeTimeElt);
            if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {

                $Link = $Container->add(new HtmlLink(t('Restnutzungsdauer einblenden')));
                $Link->addClass('no-xhr show-lifeTimeDelay-icon');
                $Link->setAttribute('title', t('Restnutzungsdauer einblenden'));
                $Container->add(new ElcaHtmlFormElementLabel('',
                    ($LifeTimeDelayElt = new ElcaHtmlNumericInput('lifeTimeDelay[' . $key . ']'))));
                if (!isset($this->Data->isExtant[$key]) || !$this->Data->isExtant[$key]) {
                    $LifeTimeDelayElt->setReadonly();
                }
                $this->checkElementChange($LifeTimeDelayElt);
            }

            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlCheckbox('calcLca[' . $key . ']', !is_numeric($key) ? true : null)));

            if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
                $Container->add(new ElcaHtmlFormElementLabel('', $IsExtantCheckbox = new HtmlCheckbox('isExtant[' . $key . ']', !is_numeric($key) ? true : null)));
                $this->checkElementHighlight($IsExtantCheckbox);
            }
        }

        /**
         * Toggler
         */
        if ($key != 'new_' . $this->buildMode)
            $Container->add(new ElcaHtmlToggleLink(Url::factory('/' . $this->context . '/toggleComponent/', array('componentId' => $key,
                                                                                                                  'b'           => $this->buildMode)), $this->Data->toggleState[$key]));

        /**
         * Remove link
         */
        if (!$this->readOnly && !$this->isLockedFunction(ElementAssistant::FUNCTION_COMPONENT_DELETE, $Component)) {
            if (is_numeric($key))
                $Container->add(new HtmlLink(t('Löschen'), Url::factory('/' . $this->context . '/deleteComponent/', ['id' => $key])))
                    ->addClass('function-link delete-link');
            else
                $Container->add(new HtmlLink(t('Abbrechen'), Url::factory('/' . $this->context . '/general/', ['e' => $this->Element->getId()])))
                    ->addClass('function-link cancel-link');
        }

        if (!$this->readOnly && !$isSibling)
            $Container->add(new HtmlTag('div', null, ['class' => 'drag-handle']));

        /**
         * Add results table
         */
        if (isset($this->Data->toggleState[$key]) && $this->Data->toggleState[$key]) {
            if ($this->context == ElementsCtrl::CONTEXT)
                $this->appendProcessInfo($Container, $key);
            else
                $this->appendResults($Container, $Component);
        }
    }
    // End appendComponentsSection


    /**
     * Appends the geometry section
     *
     * @param  HtmlForm $Form
     */
    protected function appendComponentsSection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(t('Sonstige Baustoffe')));
        $Group->addClass('clear');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Baustoff'), ['class' => 'hl-processConfig']));
        $Row->add(new HtmlTag('h5', t('Menge'), ['class' => 'hl-quantity']));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $labelText = t('Austausch/Rest');
            $toolTip = t('Austausch / Erster Austausch nach ... Jahren');
        } else {
            $labelText = t('Austausch');
            $toolTip = t('Austausch in Jahren');
        }
        $Row->add(new HtmlTag('h5', $labelText, ['class' => 'hl-life-time', 'title' => $toolTip]));
        $Row->add(new HtmlTag('h5', t('Bilanz'), ['class' => 'hl-is-active', 'title' => t('In die Bilanz einbeziehen')]));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $Row->add(new HtmlTag('h5', t('Bestand'), ['class' => 'hl-is-extant',
                                                       'title' => t('Als Bestandskomponente markieren (Herstellung wird nicht einbezogen)')]));
        }

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-' . $this->buildMode]));
        $Container->add(new HtmlHiddenField('a', $this->addNewComponent));
        $Ul = $Container->add(new HtmlTag('ul'));

        $counter = 0;
        if (isset($this->Data->processConfigId) &&
            is_array($this->Data->processConfigId) &&
            count($this->Data->processConfigId)
        ) {
            foreach ($this->Data->processConfigId as $key => $processConfigId) {
                $Component = ElcaElementComponent::findById($key);
                $Li = $Ul->add(new HtmlTag('li', null, ['id' => 'component-group-' . $key]));
                $this->appendSingleComponent($Li, $key, $Component);
                $counter++;
            }
        }

        if ($this->addNewComponent) {
            $Li = $Ul->add(new HtmlTag('li'));
            $this->appendSingleComponent($Li, 'new_' . $this->buildMode);
            $counter++;
        }

        $this->appendButtons($Container, $counter > 0);
    }
    // End appendComponentsSection


    /**
     * Appends a single component
     *
     * @param  HtmlForm $Form
     */
    protected function appendSingleComponent(HtmlElement $Li, $key, ElcaElementComponent $Component = null)
    {
        $Li->add(new HtmlTag('span'));
        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'element-component']));
        $Container->setAttribute('id', 'component-' . $key);
        $Container->addClass('clearfix element-component ' . $this->buildMode);

        /**
         * ProcessConfig selector
         *
         * @var ElcaHtmlProcessConfigSelectorLink $Selector
         */
        $Selector = $Container->add(
            new ElcaHtmlProcessConfigSelectorLink(
                'processConfigId[' . $key . ']'
            )
        );

        if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID, $Component))
            $Selector->setReadonly(true, false);

        $Selector->addClass('process-config-selector');
        $Selector->setElementId($this->Element->getId());
        $Selector->setRelId($key);
        $Selector->setBuildMode($this->buildMode);
        $Selector->setContext($this->context);

        if ($this->context == ProjectElementsCtrl::CONTEXT)
            $Selector->setProcessDbId(Elca::getInstance()->getProject()->getProcessDbId());

        $this->checkElementChange($Selector);

        $Request = FrontController::getInstance()->getRequest();
        if ((isset($this->Data->processConfigId[$key]) && $this->Data->processConfigId[$key]) || (isset($Request->processConfigId[$key]) && $Request->processConfigId[$key])) {
            $ProcessConfig = ElcaProcessConfig::findById(isset($Request->processConfigId[$key]) ? $Request->processConfigId[$key] : $this->Data->processConfigId[$key]);
            if ($ProcessConfig->isInitialized())
                $Selector->setProcessCategoryNodeId($ProcessConfig->getProcessCategoryNodeId());

            if ($this->context == ProjectElementsCtrl::CONTEXT && $Component !== null) {
                $CacheComponent = ElcaCacheElementComponent::findByElementComponentId($Component->getId());
                if ($CacheComponent->isInitialized()) {
                    if (!ElcaProcessViewSet::findResultsByElementComponentId($Component->getId())->count()) {
                        $Li->addClass('no-results');
                        $this->Data->toggleState[$key] = true;
                    }
                } else {
                    $Li->addClass('no-results');
                    $this->Data->toggleState[$key] = true;
                }
            }
            if ($ProcessConfig->isStale()) {
                $Li->addClass('no-results');
                $this->Data->toggleState[$key] = true;
            }

            /**
             * Component properties
             */
            $Container->add(new ElcaHtmlFormElementLabel('', $QuantityInput = new ElcaHtmlNumericInput('quantity[' . $key . ']')));
            $QuantityInput->setPrecision(4);
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_QUANTITY, $Component))
                $QuantityInput->setReadonly(true, false);

            $this->checkElementChange($QuantityInput);
            $Container->add(new ElcaHtmlFormElementLabel('', $Select = new HtmlSelectbox('conversionId[' . $key . ']')));
            if ($this->isLockedProperty(ElementAssistant::PROPERTY_COMPONENT_CONVERSION_ID, $Component))
                $Select->setReadonly(true, false);

            $this->checkElementChange($Select);
            list($RequiredConversions, $AvailableConversions) = $ProcessConfig->getRequiredConversions();
            $units = array_unique($RequiredConversions->getArrayBy('inUnit', 'id') + $AvailableConversions->getArrayBy('inUnit', 'id'));
            if (count($units) > 1)
                $Select->add(new HtmlSelectOption('-', ''));
            foreach ($units as $conversionId => $unit)
                $Select->add(new HtmlSelectOption(isset(Elca::$units[$unit]) ? t(Elca::$units[$unit]) : $unit, $conversionId));
        }
        $Container->add(new ElcaHtmlFormElementLabel('', ($LifeTimeElt = new ElcaHtmlLifeTimeInput('lifeTime[' . $key . ']'))));
        $LifeTimeElt->setPrecision(0);
        $this->checkElementChange($LifeTimeElt);

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $Link = $Container->add(new HtmlLink(t('Restnutzungsdauer einblenden')));
            $Link->addClass('no-xhr show-lifeTimeDelay-icon');
            $Link->setAttribute('title', t('Restnutzungsdauer einblenden'));
            $Container->add(new ElcaHtmlFormElementLabel('',
                ($LifeTimeDelayElt = new ElcaHtmlNumericInput('lifeTimeDelay[' . $key . ']'))));
            if (!isset($this->Data->isExtant[$key]) || !$this->Data->isExtant[$key]) {
                $LifeTimeDelayElt->setReadonly();
            }
            $this->checkElementChange($LifeTimeDelayElt);
        }
        $Container->add(new ElcaHtmlFormElementLabel('', new HtmlCheckbox('calcLca[' . $key . ']', !is_numeric($key) ? true : null)));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlCheckbox('isExtant[' . $key . ']',
                !is_numeric($key) ? true : null)));
        }

        /**
         * Toggler
         */
        $isOpen = isset($this->Data->toggleState[$key]) ? $this->Data->toggleState[$key] : false;
        if ($key != 'new_' . $this->buildMode)
            $Container->add(new ElcaHtmlToggleLink(Url::factory('/' . $this->context . '/toggleComponent/', array('componentId' => $key,
                                                                                                                  'b'           => $this->buildMode)), $isOpen));

        if ($isOpen)
            $Container->addClass('open');

        /**
         * Remove link
         */
        if (!$this->readOnly) {
            if (is_numeric($key)) {
                if (!$this->isLockedFunction(ElementAssistant::FUNCTION_COMPONENT_DELETE_COMPONENT, $Component))
                    $Container->add(new HtmlLink(t('Löschen'), Url::factory('/' . $this->context . '/deleteComponent/', ['id' => $key])))
                        ->addClass('function-link delete-link');
            } else
                $Container->add(new HtmlLink(t('Abbrechen'), Url::factory('/' . $this->context . '/general/', ['e' => $this->Element->getId()])))
                    ->addClass('function-link cancel-link');
        }

        /**
         * Add results table
         */
        if (isset($this->Data->toggleState[$key]) && $this->Data->toggleState[$key]) {
            if ($this->context == ElementsCtrl::CONTEXT)
                $this->appendProcessInfo($Container, $key);
            else
                $this->appendResults($Container, $Component);
        }
    }
    // End appendSingleComponent


    /**
     * Appends the result table
     *
     * @param HtmlElement $Container
     * @param             $key
     */
    private function appendProcessInfo(HtmlElement $Container, $key)
    {
        $ProcessSet = ElcaProcessViewSet::findWithProcessDbByProcessConfigIdAndPhase($this->Data->processConfigId[$key], 'prod');

        if ($ProcessSet->count()) {
            $Table = $Container->add(new HtmlTable('process-databases'));
            $Table->addColumn('processDb', t('Datenbank'));
            $Table->addColumn('lifeCycleDescription', t('Lebenszyklus'));
            $Table->addColumn('ratio', t('Anteil'));
            $Table->addColumn('nameOrig', t('Prozess'));
            $Table->addColumn('refValue', t('Bezugsgröße'));
            $Table->addColumn('uuid', t('UUID'));

            $doList = [];
            foreach ($ProcessSet as $Process) {
                $Obj = (object)null;
                foreach ($Process as $k => $v) {
                    switch ($k) {
                        case 'lifeCycleDescription':
                            $Obj->$k = t($Process->$k);
                            break;
                        default:
                            $Obj->$k = $Process->$k;
                    }
                }
                $doList[] = $Obj;
            }

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Converter = new ElcaProcessesConverter();

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            $Row->getColumn('refValue')->setOutputElement(new HtmlText('refValue', $Converter));

            $Body->setDataSet($doList);
        } else {
            $InfoDiv = $Container->add(new HtmlTag('div', null, array('class' => 'results clearfix')));
            $this->appendInfo($InfoDiv, t('Keine Bilanzierung'));
        }
    }
    // End appendProcessInfo


    /**
     * Appends the result table
     *
     * @param HtmlElement          $Container
     * @param ElcaElementComponent $component
     *
     * @return void -
     */
    private function appendResults(HtmlElement $Container, $component)
    {
        if (!$component)
            return;

        $Element = $component->getElement();
        $quantity = $Element->getQuantity();

        /**
         * Build indicator result table
         */
        $indicatorSet = ElcaProcessViewSet::findResultsByElementComponentId($component->getId());

        $moduleMap = $indicatorSet->getArrayBy('item_id', 'life_cycle_ident');
        $hasA1A2OrA3 = isset($moduleMap[Module::A1]) || isset($moduleMap[Module::A2]) || isset($moduleMap[Module::A3]);

        if ($indicatorSet->count()) {
            $doList = [];
            $indicators = [];

            foreach ($indicatorSet as $indicator) {
                if ($hasA1A2OrA3 && Module::fromValue($indicator->life_cycle_ident)->isA13()) {
                    continue;
                }

                $key = $indicator->life_cycle_ident.$indicator->process_id;
                if (!isset($doList[$key])) {
                    $DO                = $doList[$key] = new \stdClass();
                    $DO->nameOrig      = $indicator->name_orig;
                    $DO->lifeCycleName = $indicator->life_cycle_name;

                    if ($indicator->ratio != 1) {
                        $DO->lifeCycleName .= ' ('.$indicator->ratio * 100 .'%)';
                    }

                    $DO->lifeCycleIdent = $indicator->life_cycle_ident;
                    $DO->lifeCyclePOrder   = $key !== 'total'
                        ? $indicator->life_cycle_p_order
                        : 999;

                    $DO->usedInCalculation = $key === 'total'
                        ?: $this->lifeCycleUsages->moduleIsAppliedInTotals(new Module($indicator->life_cycle_ident));

                } else {
                    $DO = $doList[$key];
                }

                $indicatorId              = $indicator->indicator_ident;
                $DO->$indicatorId         = $indicator->value / max(1, $quantity);
                $indicators[$indicatorId] = $indicator->indicator_name;
            }

            usort($doList, function ($a, $b) {
                $a = (int)((int)(!$a->usedInCalculation) . str_pad($a->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));
                $b = (int)((int)(!$b->usedInCalculation) . str_pad($b->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));

                return $a > $b ? 1 : -1;
            });

            $Table = $Container->add(new HtmlTable('process-databases'));
            $Table->addColumn('lifeCycleName', t('Lebenszyklus'));
            $Table->addColumn('nameOrig', t('Prozess'));

            foreach ($indicators as $indicatorId => $indicatorName)
                $Table->addColumn($indicatorId, t($indicatorName));

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();

            $Row = $Body->addTableRow();
            $Row->addAttrFormatter(new SimpleAttrFormatter('class', 'lifeCycleIdent', true));

            foreach ($indicators as $indicatorId => $indicatorName) {
                $Row->getColumn($indicatorId)->setOutputElement(
                    new ElcaHtmlNumericText($indicatorId, 4, false, '?', null, null, true)
                );

            }


            $Row->getColumn('lifeCycleName')->setOutputElement(new HtmlText('lifeCycleName', new ElcaTranslatorConverter()));

            $Body->setDataSet($doList);
        }

        /**
         * Some more infos
         */
        $ProcessConfig = $component->getProcessConfig();
        $CElementComponent = ElcaCacheElementComponent::findByElementComponentId($component->getId());

        $InfoDiv = $Container->add(new HtmlTag('div', null, ['class' => 'results clearfix']));

        if (!$indicatorSet->count() || $ProcessConfig->isStale() || $ProcessConfig->isUnknown()) {
            $this->appendInfo($InfoDiv, t('Keine Bilanzierung'));

            if ($unknownInfoAttr = $component->getAttribute(Elca::ELEMENT_COMPONENT_ATTR_UNKNOWN)) {
                $this->appendInfo(
                    $InfoDiv,
                    t('Originalname'),
                    $unknownInfoAttr->getTextValue()
                );
            }
        }

        if ($CElementComponent->isInitialized()) {
            $this->appendInfo($InfoDiv, t('Menge'), ElcaNumberFormat::toString($CElementComponent->getQuantity() / max(1, $quantity), 3), $CElementComponent->getRefUnit());

            if ($CElementComponent->getRefUnit() != Elca::UNIT_KG)
                $this->appendInfo($InfoDiv, t('Masse'), ElcaNumberFormat::toString($CElementComponent->getMass() / max(1, $quantity), 3), Elca::UNIT_KG);

            if ($density = $ProcessConfig->getDensity())
                $this->appendInfo($InfoDiv, t('Rohdichte'), ElcaNumberFormat::toString($density, 2), 'kg / m3');

            $numReplacements = $CElementComponent->getNumReplacements();
            $this->appendInfo($InfoDiv, t('Instandhaltungszyklen'), $numReplacements ? ElcaNumberFormat::toString($numReplacements, 2) : 0);
        } else
            $this->appendInfo($InfoDiv, t('Keine Bilanzierung'));
    }
    // End appendResults


    /**
     * Appends one single info
     *
     * @param  HtmlElement $Container
     * @param  string      $caption
     * @param  string      $value
     * @param string       $refUnit
     */
    private function appendInfo($Container, $caption, $value = null, $refUnit = '')
    {
        if ($refUnit)
            $refUnit = ElcaNumberFormat::formatUnit($refUnit);

        $Info = $Container->add(new HtmlTag('div', null, ['class' => 'info']));
        $Info->add(new HtmlTag('span', $caption, ['class' => 'caption']));

        if (!is_null($value))
            $Info->add(new HtmlTag('span', $value . ' ' . $refUnit, ['class' => 'value']));
    }
    // End appendInfo


    /**
     * Appends submit button
     *
     * @param  HtmlElement $Container
     * @param bool         $showSaveButton
     *
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $Container, $showSaveButton = false)
    {
        if ($this->readOnly)
            return;

        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        /**
         * Add new component button
         */
        if ($this->buildMode == self::BUILDMODE_COMPONENTS) {
            if (!$this->isLockedFunction(ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT)) {
                $Button = $ButtonGroup->add(new ElcaHtmlSubmitButton('addComponent', t('Neuen Baustoff hinzufügen')));
                $Button->addClass('add-component');
            }
        } else {
            if (!$this->isLockedFunction(ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER)) {
                $Button = $ButtonGroup->add(new ElcaHtmlSubmitButton('addLayer', t('Neue Schicht hinzufügen')));
                $Button->addClass('add-component');
            }
        }

        /**
         * Submit button
         */
        if ($showSaveButton)
            $ButtonGroup->add(new ElcaHtmlSubmitButton($this->buildMode == self::BUILDMODE_COMPONENTS ? 'saveComponents' : 'saveLayers', t('Speichern'), true));
    }
    // End appendSubmitButton


    /**
     * Checks if an element needs marked as changed
     */
    private function checkElementChange(HtmlFormElement $Element)
    {
        if (isset($this->changedElements[$Element->getName()]))
            $Element->addClass('changed');
    }
    // End checkElementChange


    /**
     * Checks if an element needs highlightning
     */
    private function checkElementHighlight(HtmlFormElement $Element)
    {
        if (isset($this->highlightedElements[$Element->getName()]))
            $Element->addClass('highlight');
    }
    // End checkElementHighlight

    /**
     * @param string $what
     *
     * @return bool
     */
    private function isLockedProperty($what, ElcaElementComponent $component = null)
    {
        if (!$this->assistant instanceof ElementAssistant || $component === null)
            return false;

        if (!$this->assistant->isLockedProperty($what))
            return false;

        return ElcaElementComponentAttribute::existsByElementComponentIdAndIdent($component->getId(), $this->assistant->getIdent());
    }

    /**
     * @param string                    $what
     *
     * @param ElcaElementComponent|null $component
     * @return bool
     */
    private function isLockedFunction($what, ElcaElementComponent $component = null)
    {
        if (!$this->assistant instanceof ElementAssistant)
            return false;

        if (!$this->assistant->isLockedFunction($what, $this->Element && $this->Element->isInitialized() ? $this->Element : null)) {
            return false;
        }

        if (null === $component) {
            return false;
        }

        return ElcaElementComponentAttribute::existsByElementComponentIdAndIdent($component->getId(), $this->assistant->getIdent());
    }
}
// End ElcaElementComponentsView
