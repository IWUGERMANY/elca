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
use Beibob\Blibs\StringFactory;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlDOMImportElement;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use DOMNode;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaConstrCatalogSet;
use Elca\Db\ElcaConstrDesignSet;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProcessViewSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlMultiSelectbox;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaTranslatorConverter;
use Elca\View\helpers\SimpleAttrFormatter;

/**
 * Builds the element view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_ELEMENT_IMAGE = 'elementImage';
    const BUILDMODE_SUMMARY = 'summary';

    protected $activeTabIdent;

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
     * @var ElcaElement $element
     */
    private $element;

    /**
     * Element type node id
     */
    private $elementTypeNodeId;

    /**
     * Layers data object
     */
    private $Layers;

    /**
     * Single components data object
     */
    private $Components;

    private $assistant;

    /**
     * @var LifeCycleUsages
     */
    private $lifeCycleUsages;

    //////////////////////////////////////////////////////////////////////////////////////

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
        $this->element = ElcaElement::findById($this->get('elementId'));

        $this->buildMode         = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->context           = $this->get('context', ElementsCtrl::CONTEXT);
        $this->elementTypeNodeId = $this->get('elementTypeNodeId', $this->element->getElementTypeNodeId());
        $this->activeTabIdent    = $this->get('activeTabIdent', null);
        $this->lifeCycleUsages = $this->get('lifeCycleUsages');

        $this->Layers     = $this->get('Layers');
        $this->Components = $this->get('Components');
        $this->Elements   = $this->get('Elements');

        if ($this->get('readOnly', false)) {
            $this->readOnly = true;
        }

        if ($this->has('assistant')) {
            $this->assistant = $this->get('assistant');
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        switch ($this->buildMode) {
            case self::BUILDMODE_SUMMARY:
                $this->appendSummarySection($this);
                break;

            case self::BUILDMODE_ELEMENT_IMAGE:
                if ($ImgContainer = $this->getElementImage()) {
                    $ImgContainer->appendTo($this);
                }
                break;

            default:
            case self::BUILDMODE_DEFAULT:
                $form = new HtmlForm('elementForm', '/'.$this->context.'/save/');
                $form->setAttribute('id', 'elementForm');
                $form->addClass('clearfix');
                $form->setRequest(FrontController::getInstance()->getRequest());

                if ($this->readOnly) {
                    $form->setReadonly();
                }

                if ($this->has('Validator')) {
                    $form->setValidator($this->get('Validator'));
                }

                $form->add(new HtmlHiddenField('projectVariantId', Elca::getInstance()->getProjectVariantId()));

                if ($compositeElementId = $this->get('compositeElementId')) {
                    $form->add(new HtmlHiddenField('rel', $compositeElementId));
                }

                if ($this->element instanceOf ElcaElement && $this->element->isInitialized()) {
                    $form->addClass('highlight-changes');
                    $form->setDataObject($this->element);
                    $form->add(new HtmlHiddenField('elementId', $this->element->getId()));
                } else {
                    $form->add(new HtmlHiddenField('elementTypeNodeId', $this->elementTypeNodeId));
                }

                $content = $this->appendChild(
                    $this->getDiv(['id' => 'tabContent', 'class' => 'tab-general '.$this->context])
                );

            $this->appendIdInfo($content);
            $this->appendProcessDbCompatMessage($content);

                $this->appendDefault($form);
                $form->appendTo($content);
                $this->appendSections($content);
                break;
        }
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the default elements
     *
     * @param  HtmlForm $form
     */
    protected function appendDefault(HtmlForm $form)
    {
        /**
         * Name, description and isPublic
         */
        $leftGroup = $form->add(new HtmlFormGroup(''));
        $leftGroup->addClass('clearfix column properties');

        $leftGroup->add(new ElcaHtmlFormElementLabel(t('Name'), $inputElt = new HtmlTextInput('name'), true));

        if ($this->isLockedProperty(ElementAssistant::PROPERTY_NAME)) {
            $inputElt->setReadonly(true, false);
        }

        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            $Attr = ElcaElementAttribute::findByElementIdAndIdent($this->element->getId(), Elca::ELEMENT_ATTR_OZ);
            $leftGroup->add(
                new ElcaHtmlFormElementLabel(
                    t(Elca::$elementAttributes[Elca::ELEMENT_ATTR_OZ]),
                    new HtmlTextInput('attr['.Elca::ELEMENT_ATTR_OZ.']', $Attr->getTextValue())
                )
            );
        }

        $leftGroup->add(new ElcaHtmlFormElementLabel(t('Beschreibung'), new HtmlTextArea('description')));

        $isElementOfComposite = $this->element->hasCompositeElement();

        /**
         * Context sensitive elements
         */
        if ($this->context == ElementsCtrl::CONTEXT && ElcaAccess::getInstance()->hasAdminPrivileges()) {
            $checkboxGroup = $leftGroup->add(new ElcaHtmlFormElementLabel(''));

            $this->appendElementScope($checkboxGroup, 'isPublic', $isElementOfComposite, t('Öffentliche Vorlage'), (bool)$this->element->isReference());
            $this->appendElementScope($checkboxGroup, 'isReference', $isElementOfComposite, t('Referenzvorlage'));
        }

        if ($this->context == ProjectElementsCtrl::CONTEXT && $this->element instanceOf ElcaElement && $this->element->isInitialized(
            )) {
            $leftGroup->add(
                new ElcaHtmlFormElementLabel(
                    t('Verbaute Menge'),
                    $NumInput = new ElcaHtmlNumericInput('quantity'),
                    true,
                    null,
                    t('Verbaute Menge')
                )
            );
            $NumInput->setPrecision(3);

            if ($this->isLockedProperty(ElementAssistant::PROPERTY_QUANTITY, $this->element) || $isElementOfComposite) {
                $NumInput->setReadonly(true, false);
            }
        }
        $Select = $leftGroup->add(new ElcaHtmlFormElementLabel(t('Bezugsgröße'), new HtmlSelectbox('refUnit'), true));

        if ($this->isLockedProperty(ElementAssistant::PROPERTY_REF_UNIT)) {
            $Select->setReadonly(true, false);
        }

        $Select->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
        foreach ([Elca::UNIT_STK, Elca::UNIT_M2, Elca::UNIT_M] as $unit) {
            $Select->add(new HtmlSelectOption(t(Elca::$units[$unit]), $unit));
        }

        /**
         * Construction catalogs and designs
         */
        $idGroup = $form->add(new HtmlFormGroup(''));
        $idGroup->addClass('clearfix column attributes');

        /**
         * Context sensitive elements
         */
        if ($this->context == ElementsCtrl::CONTEXT) {
            $catalogs = $this->element->getConstrCatalogs()->getArrayBy('id', 'id');
            $designs  = $this->element->getConstrDesigns()->getArrayBy('id', 'id');

            $Select = $idGroup->add(
                $Label = new ElcaHtmlFormElementLabel(
                    t('Katalogzuordnung'),
                    new ElcaHtmlMultiSelectbox('constrCatalogId'),
                    true,
                    null,
                    t('Wählen Sie mehrere Datensätze, indem Sie die STRG-Taste beim Klicken gedrückt halten')
                )
            );
            foreach (ElcaConstrCatalogSet::find(null, ['name' => 'ASC']) as $Catalog) {
                $Opt = $Select->add(new HtmlSelectOption(t($Catalog->getName()), $id = $Catalog->getId()));
                if (isset($catalogs[$id])) {
                    $Opt->setAttribute('selected', 'selected');
                }
            }

            $Select = $idGroup->add(
                new ElcaHtmlFormElementLabel(
                    t('Bauweise'),
                    new ElcaHtmlMultiSelectbox('constrDesignId'),
                    false,
                    null,
                    t('Wählen Sie mehrere Datensätze, indem Sie die STRG-Taste beim Klicken gedrückt halten')
                )
            );
            foreach (ElcaConstrDesignSet::find(null, ['name' => 'ASC']) as $Design) {
                $Opt = $Select->add(new HtmlSelectOption(t($Design->getName()), $id = $Design->getId()));

                if (isset($designs[$id])) {
                    $Opt->setAttribute('selected', 'selected');
                }
            }

            $AttrContainer = $form;
        } else {
            $AttrContainer = $idGroup;
        }

        /**
         * Element image
         */
        if ($EltImgContainer = $this->getElementImage()) {
            $form->add($EltImgContainer);
        }

        /**
         * Attributes
         */
        $attrGroup = $AttrContainer->add(new HtmlFormGroup(t('Attribute')));
        $attrGroup->addClass('clearfix clear column');

        foreach (Elca::$elementAttributes as $ident => $caption) {
            $Attr = ElcaElementAttribute::findByElementIdAndIdent($this->element->getId(), $ident);

            switch ($ident) {
                /**
                 * Skip OZ
                 */
                case Elca::ELEMENT_ATTR_OZ:
                    break;
                default:
                    $attrGroup->add(
                        new ElcaHtmlFormElementLabel(
                            t($caption),
                            new ElcaHtmlNumericInput('attr['.$ident.']', $Attr->getNumericValue())
                        )
                    );
            }
        }

        // only for KG300
        if (intval($this->element->getElementTypeNode()->getDinCode() / 100) == 3) {

            $attrGroup = $AttrContainer->add(new HtmlFormGroup(t('BNB 4.1.4')));
            $attrGroup->addClass('clearfix column');

            $readOnly = !$this->element->isComposite() && $this->element->hasCompositeElement();
            foreach (Elca::$elementBnbAttributes as $ident => $caption) {
                $Attr = ElcaElementAttribute::findByElementIdAndIdent($this->element->getId(), $ident);
                $attrGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t($caption),
                        new ElcaHtmlNumericInput('attr['.$ident.']', $Attr->getNumericValue(), $readOnly)
                    )
                );
            }
        }
        /**
         * Buttons
         */
        $ButtonGroup = $form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix column buttons');

        if (!$form->isReadonly()) {
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveElement', t('Speichern'), true));

            if (!$this->element instanceOf ElcaElement || !$this->element->isInitialized()) {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbruch')));
            } else {
                if (!$this->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_DELETE, $this->element)) {
                    $deleteRecursive = $this->context == ProjectElementsCtrl::CONTEXT && $this->element->isComposite();
                    if (!$deleteRecursive || !$this->isLockedFunction(
                            ElementAssistant::FUNCTION_ELEMENT_DELETE_RECURSIVE
                        )) {
                        $ButtonDiv = $ButtonGroup->add(new HtmlTag('div', null, ['class' => 'delete button']));
                        $deleteUrl = '/'.$this->context.'/delete/?id='.$this->element->getId();
                        if ($deleteRecursive) {
                            $deleteUrl .= '&recursive';
                        }
                        $ButtonDiv->add(new HtmlLink(t('Löschen'), $deleteUrl));
                    }
                }


                if ($this->context == ProjectElementsCtrl::CONTEXT) {
                    $ButtonGroup->add(new ElcaHtmlSubmitButton('addAsTemplate', t('Als Vorlage'), false));
                }

                if ($this->get('enableProposeElement', false)) {
                    $ButtonGroup->add(new ElcaHtmlSubmitButton('proposeElement', t('Vorschlagen'), false));
                }
            }
        }

        /**
         * Composite element info
         */
        if ($this->element->hasCompositeElement()) {
            /**
             * Get distinct list of composite elements
             */
            $CompositeElementSet = $this->element->getCompositeElements();
            $compositeElements   = array_unique($CompositeElementSet->getArrayBy('compositeElementId'));

            /**
             * Check user access to composite elements
             */
            $Access = ElcaAccess::getInstance();
            if (!$Access->hasAdminPrivileges()) {
                foreach ($compositeElements as $index => $compositeElementId) {
                    if (!$Access->canAccessElement($CompositeElementSet[$index]->getCompositeElement())) {
                        unset($compositeElements[$index]);
                    }
                }
            }

            /**
             * Remove active composite element from list
             */
            if ($this->get('compositeElementId') &&
                ($key = array_search($this->get('compositeElementId'), $compositeElements)) !== false) {
                unset($compositeElements[$key]);
            }

            /**
             * Show all remaining
             */
            if (count($compositeElements)) {
                $Composite = $form->add(new ElcaHtmlFormElementLabel(t('Verknüpft mit Bauteil '), new HtmlTag('div')));
                $Composite->addClass('composite-element clear');

                foreach ($compositeElements as $index => $compositeElementId) {
                    $CompositeElement     = $CompositeElementSet[$index];
                    $compositeElementName = $CompositeElement->getCompositeElement()->getName();
                    $url                  = '/'.$this->context.'/'.$CompositeElement->getCompositeElementId().'/';

                    if ($this->activeTabIdent) {
                        $url .= '?tab='.$this->activeTabIdent;
                    }
                    $Link = $Composite->add(
                        new HtmlLink(StringFactory::stringMidCut($compositeElementName, 40).' ', $url)
                    );
                    $Link->setAttribute('title', $compositeElementName);
                    $Link->addClass('page');
                }
            }
        }
    }
    // End appendDefault

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the sections to the content
     *
     * @param  DOMElement $Content
     * @return -
     */
    protected function appendSections(DOMElement $Content)
    {
        if (!$this->element instanceOf ElcaElement || !$this->element->isInitialized()) {
            return;
        }

        $ElementTypeNode = ElcaElementType::findByNodeId($this->elementTypeNodeId);

        if ($ElementTypeNode->isCompositeLevel()) {
            $Content->appendChild(
                $this->getH2(t('Verknüpfte Bauteilkomponenten (von innen nach außen)'), ['class' => 'clearfix'])
            );

            /**
             * Append composite elements
             */
            $this->appendCompositeSection($Content);

            if ($this->context == ProjectElementsCtrl::CONTEXT) {
                $this->appendSummarySection($Content);
            }
        } else {
            $ElementType = $this->element->getElementTypeNode();

            $H2 = $Content->appendChild($this->getH2(t('Baustoffe').' ', ['class' => 'clearfix']));
            $H2->appendChild(
                $this->getSpan(t('bezogen auf 1').' '.ElcaNumberFormat::formatUnit($this->element->getRefUnit()))
            );

            if ($ElementType->getPrefHasElementImage()) {
                $this->appendGeometrySection($Content);
            }

            $this->appendComponentsSection($Content);

            if ($this->context == ProjectElementsCtrl::CONTEXT) {
                $this->appendSummarySection($Content);
            }
        }
    }
    // End appendSections

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the geometry section
     *
     * @param  DOMElement $Container
     */
    protected function appendGeometrySection(DOMElement $Container)
    {
        if (!$this->Layers) {
            return;
        }

        $view = new ElcaElementComponentsView();
        $view->assign('buildMode', ElcaElementComponentsView::BUILDMODE_LAYERS);
        $view->assign('readOnly', $this->readOnly);
        $view->assign('context', $this->context);
        $view->assign('elementId', $this->element->getId());
        $view->assign('Data', $this->Layers);

        if ($this->assistant) {
            $view->assign('assistant', $this->assistant);
        }

        if ($this->lifeCycleUsages) {
            $view->assign('lifeCycleUsages', $this->lifeCycleUsages);
        }

        $view->process();
        $view->appendTo($Container);
    }
    // End appendGeometrySection

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the components section
     *
     * @param  DOMElement $Container
     */
    protected function appendComponentsSection(DOMElement $Container)
    {
        if (!$this->Components) {
            return;
        }

        $view = new ElcaElementComponentsView();
        $view->assign('buildMode', ElcaElementComponentsView::BUILDMODE_COMPONENTS);
        $view->assign('readOnly', $this->readOnly);
        $view->assign('context', $this->context);
        $view->assign('elementId', $this->element->getId());
        $view->assign('Data', $this->Components);

        if ($this->assistant) {
            $view->assign('assistant', $this->assistant);
        }

        if ($this->lifeCycleUsages) {
            $view->assign('lifeCycleUsages', $this->lifeCycleUsages);
        }

        $view->process();
        $view->appendTo($Container);
    }
    // End appendComponentsSection


    /**
     * Appends the composite section
     *
     * @param  DOMElement $Container
     */
    protected function appendCompositeSection(DOMElement $Container)
    {
        if (!$this->Elements) {
            return;
        }

        $view = new ElcaElementCompositeView();
        $view->assign('readOnly', $this->readOnly);
        $view->assign('context', $this->context);
        $view->assign('compositeElementId', $this->element->getId());
        $view->assign('Data', $this->Elements);
        $view->assign('activeTabIdent', $this->activeTabIdent);

        if ($this->assistant) {
            $view->assign('assistant', $this->assistant);
        }

        if ($this->lifeCycleUsages) {
            $view->assign('lifeCycleUsages', $this->lifeCycleUsages);
        }

        $view->process();
        $view->appendTo($Container);
    }
    // End appendComponentsSection


    /**
     * Appends the summary section
     *
     * @param  DOMElement $Container
     */
    protected function appendSummarySection(DOMNode $Container)
    {
        $Div         = $Container->appendChild(
            $this->getDiv(['id' => 'section-summary', 'class' => 'element-section'])
        );
        $FieldsetDiv = $Div->appendChild($this->getDiv(['class' => 'clear fieldset']));
        $Legend      = $FieldsetDiv->appendChild($this->getDiv(['class' => 'legend']));
        $Legend->appendChild($this->getText(t('Gesamteinsatz').' '));
        //$Legend->appendChild($this->getSpan(t('bezogen auf 1) . ' ' . ElcaNumberFormat::formatUnit($this->Element->getRefUnit())));

        $quantity = $this->element->isComposite() ? 1 : $this->element->getQuantity();

        $indicatorSet = ElcaProcessViewSet::findResultsByElementId($this->element->getId());

        if ($indicatorSet->count()) {
            $doList     = [];
            $indicators = [];

            foreach ($indicatorSet as $indicator) {
                $key = $indicator->life_cycle_ident;

                if (Module::fromValue($key)->isA1A2OrA3()) {
                    continue;
                }

                if (!isset($doList[$key])) {
                    $DO                  = $doList[$key] = new \stdClass();
                    $DO->lifeCycleName   = $indicator->life_cycle_name;
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
                $DO->$indicatorId         = $indicator->value / max(1, $quantity);
                $indicators[$indicatorId] = $indicator->indicator_name;
            }

            usort(
                $doList,
                function ($a, $b) {
                    $a = (int)((int)(!$a->usedInCalculation).str_pad($a->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));
                    $b = (int)((int)(!$b->usedInCalculation).str_pad($b->lifeCyclePOrder, 3, '0', STR_PAD_LEFT));

                    return $a > $b ? 1 : -1;
                }
            );

            $Table = new HtmlTable('element-summary');
            $Table->addColumn('lifeCycleName', t('Lebenszyklus'));

            foreach ($indicators as $indicatorId => $indicatorName) {
                $Table->addColumn($indicatorId, t($indicatorName));
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

            $Row->getColumn('lifeCycleName')->setOutputElement(
                new HtmlText('lifeCycleName', new ElcaTranslatorConverter())
            );


            $Body->setDataSet($doList);
            $Table->appendTo($FieldsetDiv);
        }

        /**
         * Some more infos
         */
        $CElement = ElcaCacheElement::findByElementId($this->element->getId());
        $InfoDiv  = $FieldsetDiv->appendChild($this->getDiv(['class' => 'results clearfix']));

        $this->appendInfo(
            $InfoDiv,
            t('Masse'),
            ElcaNumberFormat::toString($CElement->getMass() / max(1, $quantity), 2),
            'kg'
        );
    }
    // End appendGeometrySection

    /**
     * Appends the components section
     *
     * @param  DOMElement $Container
     */
    protected function getElementImage()
    {
        if (!$this->element->isInitialized() ||
            !$this->element->getElementTypeNode()->getPrefHasElementImage()) {
            return;
        }

        $ImgContainer = null;

        if ($this->buildMode == self::BUILDMODE_DEFAULT) {
            $Canvas = new HtmlTag(
                'div', null,
                [
                    'class'           => 'element-image',
                    'data-url'        => '/'.$this->context.'/elementImage/',
                    'data-element-id' => $this->element->getId(),
                ]
            );

        } elseif ($this->buildMode == self::BUILDMODE_ELEMENT_IMAGE) {
            $elementImageView = null;

            if ($this->assistant) {
                $elementImageView = $this->assistant->getElementImageView($this->element->getId());
            }

            if ($elementImageView === null || !$elementImageView instanceof ElementImageView) {
                $elementImageView = new DefaultElementImageView();
            }

            $elementImageView->setElementId($this->element->getId());
            $elementImageView->setDimension($this->get('imageWidth'), $this->get('imageHeight'));

            if ($this->get('imageShowTotalSize', true)) {
                $elementImageView->enableTotalSize();
            }

            $elementImageView->process();

            $ImgContainer = new HtmlDOMImportElement($elementImageView);

            $Canvas = new HtmlTag(
                'div',
                null,
                [
                    'id'    => 'element-image-'.$this->element->getId(),
                    'style' => 'width:'.$this->get('imageWidth').'px;height:'.$this->get('imageHeight').'px;',
                ]
            );
            $Canvas->add($ImgContainer);
        }

        return $Canvas;
    }

    protected function appendElementScope(HtmlElement $container, string $property, bool $isElementOfComposite, string $caption, bool $readOnly = false): bool
    {
        /**
         * State of composite elements overwrites state of assigned elements
         */
        if (false === $readOnly && $isElementOfComposite) {
            foreach ($this->element->getCompositeElements() as $Assignment) {
                if ($readOnly |= $Assignment->getCompositeElement()->$property()) {
                    break;
                }
            }
        }

        $container->add($checkbox = new HtmlCheckbox($property, null, $caption));

        if ($readOnly) {
            $checkbox->setReadonly($readOnly, true);

            if ($this->element->$property) {
                $container->add(new HtmlHiddenField($property, true));
            }
        }

        return $readOnly;
    }

    /**
     * Appends one single info
     *
     * @param  HtmlElement $Container
     * @param  string      $caption
     * @param  string      $value
     */
    private function appendInfo($Container, $caption, $value, $refUnit)
    {
        $Info = $Container->appendChild($this->getDiv(['class' => 'info']));
        $Info->appendChild($this->getSpan($caption, ['class' => 'caption']));
        $Info->appendChild($this->getSpan($value.' '.ElcaNumberFormat::formatUnit($refUnit), ['class' => 'value']));
    }
    // End getElementImage

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
    private function isLockedFunction($what, ElcaElement $element = null)
    {
        if (!$this->assistant instanceof ElementAssistant) {
            return false;
        }

        return $this->assistant->isLockedFunction($what, $element);
    }

    /**
     * @param DOMElement $Content
     */
    private function appendProcessDbCompatMessage(DOMElement $Content)
    {
        if (false === $this->element->isTemplate()) {
            return;
        }

        $processDbNames = ElcaProcessDbSet::findElementCompatibles(
            $this->element, ['version' => 'ASC']
        )->getArrayBy('name');

        $numberProcessDbs = count($processDbNames);
        if ($numberProcessDbs) {
            $p = $Content->appendChild(
                $this->getP(
                    t(
                        'Diese Vorlage kann aufgrund der verwendeten Baustoffe in Projekten mit den folgenden Baustoffdatenbanken verwendet werden:'
                    ),
                    ['class' => 'process-dbs']
                )
            );

            $p->appendChild($this->getSpan(' '));

            foreach ($processDbNames as $index => $dbName) {
                $p->appendChild($this->getStrong($dbName));

                if ($index < ($numberProcessDbs - 2)) {
                    $p->appendChild($this->getSpan(', '));
                } elseif ($index < ($numberProcessDbs - 1)) {
                    $p->appendChild($this->getSpan(' und '));
                }
            }
        }
        else {
            $Content->appendChild(
                $this->getP(
                    t(
                        'Diese Vorlage kann aufgrund der verwendeten Baustoffe in keinen Projekten verwendet werden!'
                    ),
                    ['class' => 'process-dbs no-process-dbs']
                )
            );

        }
    }

    private function appendIdInfo(DOMNode $content)
    {
        if (!$this->element->getId()) {
            return;
        }

        $container = $content->appendChild($this->getUl(['class' => 'id-info']));
        $container->appendChild($this->getLi())->appendChild($this->selectionTextElement('ID', $this->element->getId()));
        $container->appendChild($this->getLi())->appendChild($this->selectionTextElement('UUID', $this->element->getUuid()));
    }

    private function selectionTextElement($label, $text) : \DOMElement {
        $container = $this->getSpan(null, ['class' => 'select-text']);
        $container->appendChild($this->getSpan($label.':', ['class' => 'selection-label']));
        $container->appendChild($this->getSpan($text, ['class' => 'selection-value']));

        return $container;
    }
}
