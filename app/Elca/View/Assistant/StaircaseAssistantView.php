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
namespace Elca\View\Assistant;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlImage;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use Elca\Controller\Assistant\StaircaseCtrl;
use Elca\Controller\ElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessCategory;
use Elca\Elca;
use Elca\Model\Assistant\Stairs\Construction\MiddleHolm;
use Elca\Model\Assistant\Stairs\Staircase;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\View\ElcaElementComponentsView;
use Elca\View\ElcaElementSelectorView;
use Elca\View\helpers\ElcaHtmlElementSelectorLink;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;

/**
 * Builds the element view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class StaircaseAssistantView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECTOR = 'selector';
    const BUILDMODE_ELEMENT_SELECTOR = 'element-selector';

    /**
     * @var Staircase
     */
    protected $staircase;

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
     * @var bool $readonly
     */
    private $readOnly;

    private $platformConstructionElementId;

    private $platformCoverElementId;

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
        $this->staircase = $this->get('staircase');
        $this->platformConstructionElementId = $this->get('platformConstructionElementId');
        $this->platformCoverElementId = $this->get('platformCoverElementId');

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->context = $this->get('context', ElementsCtrl::CONTEXT);
        $this->elementTypeNodeId = $this->get('elementTypeNodeId', $this->element->getElementTypeNodeId());

        if($this->get('readOnly', false))
            $this->readOnly = true;
    }


    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        switch($this->buildMode)
        {
            case self::BUILDMODE_SELECTOR:
                $form = new HtmlForm('assistantForm', '/assistant/staircase/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if($this->readOnly)
                    $form->setReadonly();

                $key = $this->get('key');
                $value = $this->get('value');

                if (!$key)
                    return;

                $this->appendSelector($form, $key, $value);

                // append form to dummy container
                $dummyContainer = $this->appendChild($this->getDiv());
                $form->appendTo($dummyContainer);

                // extract elementComponent container and replace it with the dummy container
                $content = $this->getElementById('processConfigSelector-'. $key);
                $this->replaceChild($content, $dummyContainer);
                break;

            case self::BUILDMODE_ELEMENT_SELECTOR:
                $form = new HtmlForm('assistantForm', '/assistant/staircase/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if($this->readOnly)
                    $form->setReadonly();

                $key = $this->get('key');
                $value = $this->get('value');

                if (!$key)
                    return;

                $this->appendElementSelector($form, $key, $value);

                // append form to dummy container
                $dummyContainer = $this->appendChild($this->getDiv());
                $form->appendTo($dummyContainer);

                // extract elementComponent container and replace it with the dummy container
                $content = $this->getElementById('elementSelector-'. $key);
                $this->replaceChild($content, $dummyContainer);
                break;

            default:
            case self::BUILDMODE_DEFAULT:
                $form = new HtmlForm('assistantForm', '/assistant/staircase/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if($this->readOnly)
                    $form->setReadonly();

                if($this->has('Validator')) {
                    $form->setRequest(FrontController::getInstance()->getRequest());
                    $form->setValidator($this->get('Validator'));
                } else {
                    $form->setDataObject($this->staircase->getDataObject());
                }

                $form->add(new HtmlHiddenField('context', $this->context));
                $form->add(new HtmlHiddenField('projectVariantId', Elca::getInstance()->getProjectVariantId()));

                if($this->element instanceOf ElcaElement && $this->element->isInitialized())
                {
                    $form->addClass('highlight-changes');
                    $form->add(new HtmlHiddenField('e', $this->element->getId()));
                }
                else
                {
                    $form->add(new HtmlHiddenField('elementTypeNodeId', $this->elementTypeNodeId));
                }

                $content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'assistant tab-'. StaircaseAssistant::IDENT.' '.$this->context]));
                $this->appendDefault($form);
                $form->appendTo($content);
                break;
        }
    }


    /**
     * Appends the default elements
     *
     * @param  HtmlForm $form
     */
    protected function appendDefault(HtmlForm $form)
    {
        $percentageConverter = new ElcaNumberFormatConverter(0, true);

        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('clearfix column properties');
        $group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true));

        $group->add(new ElcaHtmlFormElementLabel(t('Treppentyp'), $typeGroup = new HtmlRadioGroup('type', $this->staircase->getType(), $this->readOnly)));
        $typeGroup->add(new HtmlRadiobox(t('Massivtreppe'), Staircase::TYPE_SOLID));
        $typeGroup->add(new HtmlRadiobox(t('Wangentreppe'), Staircase::TYPE_STRINGER));
        $typeGroup->add(new HtmlRadiobox(t('Mittelholmtreppe'), Staircase::TYPE_MIDDLE_HOLM));

        /**
         * Dimensions
         */
        $group = $form->add(new HtmlFormGroup('Abmessungen'));
        $group->addClass('clearfix column clear properties');

        $group->add(new ElcaHtmlFormElementLabel(t('Laufbreite'), new ElcaHtmlNumericInput('width'), true, 'm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Anzahl Stufen'), new ElcaHtmlNumericInput('numberOfSteps'), true));

        $group->add(new ElcaHtmlFormElementLabel(t('Schrittmaß'), new ElcaHtmlNumericInput('stepDegree', null, true), false, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Steigung'), new ElcaHtmlNumericInput('stepHeight'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Auftritt'), new ElcaHtmlNumericInput('stepDepth'), true, 'cm'));

        $group->add(new ElcaHtmlFormElementLabel(t('Trittstufe')))->addClass('horizontal-label');
        $group->add(new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('coverSize'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('coverLength1'), true, 'cm'));

        $group->add(new ElcaHtmlFormElementLabel(t('Trapezform?'), new HtmlCheckbox('isTrapezoid')));
        $group->add(new ElcaHtmlFormElementLabel(t('Tiefe 2'), new ElcaHtmlNumericInput('coverLength2'), true, 'cm'));
        $this->appendSelector($group, 'cover');

        $group->add(new ElcaHtmlFormElementLabel(t('Setzstufe')))->addClass('horizontal-label');
        $group->add(new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('riserSize'), false, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Höhe'), new ElcaHtmlNumericInput('riserHeight'), false, 'cm'));
        $this->appendSelector($group, 'riser');

        /**
         * Element image
         */
        $baseUrl = StaircaseElementImageView::IMG_DIRECTORY;

        $container = $form->add(new HtmlTag('div', null, ['class' => 'type-images']));
        $container->add(new HtmlImage($baseUrl .'solid.png'))->addClass(Staircase::TYPE_SOLID);
        $container->add(new HtmlImage($baseUrl .'stringer.png'))->addClass(Staircase::TYPE_STRINGER);
        $container->add(new HtmlImage($baseUrl .'middle-holm.png'))->addClass(Staircase::TYPE_MIDDLE_HOLM);

        $this->appendSolidForm($form);
        $this->appendStringerForm($form);
        $this->appendMiddleHolmForm($form);

        $this->appendPlatformForm($form);

        $this->appendButtons($form);
    }

    /**
     * @param HtmlElement $group
     */
    public function appendSolidForm(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Konstruktion Laufplatte')));
        $group->addClass('clearfix column construction solid');

        $percentageConverter = new ElcaNumberFormatConverter(0, true);
        $numericConverter = new ElcaNumberFormatConverter(2);

        $group->add(new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('solidSlabHeight'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Länge'), new ElcaHtmlNumericInput('solidLength', null, false, $numericConverter), false, 'm'))->addClass('alternative-length');

        $this->appendStepsLengthChooser($group);

        $this->appendSelector($group, 'solid1');
        $defaultValue = $this->staircase->getType() !== Staircase::TYPE_SOLID? 1 : null;
        $group->add(new ElcaHtmlFormElementLabel(t('Anteil'), $input = new ElcaHtmlNumericInput('solidMaterial1Share', $defaultValue, false, $percentageConverter), false, '%'));
        $input->setPrecision(0);
        $input->disableNegative();

        $this->appendSelector($group, 'solid2');
        $defaultValue = $this->staircase->getType() !== Staircase::TYPE_SOLID? 0 : null;
        $group->add(new ElcaHtmlFormElementLabel(t('Anteil'), $input = new ElcaHtmlNumericInput('solidMaterial2Share', $defaultValue, false, $percentageConverter), false, '%'));
        $input->setPrecision(0);
        $input->disableNegative();
    }

    /**
     * @param HtmlElement $group
     */
    public function appendStringerForm(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Konstruktion Wangen')));
        $group->addClass('clearfix column construction stringer');

        $numericConverter = new ElcaNumberFormatConverter(2);

        //$group->add(new ElcaHtmlFormElementLabel(t('Wangen')))->addClass('horizontal-label');
        $group->add(new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('stringerWidth'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Höhe'), new ElcaHtmlNumericInput('stringerHeight'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Länge'), new ElcaHtmlNumericInput('stringerLength', null, false, $numericConverter), false, 'm'))->addClass('alternative-length');

        $this->appendStepsLengthChooser($group);

        $defaultValue = $this->staircase->getType() !== Staircase::TYPE_STRINGER? 2 : null;
        $group->add(new ElcaHtmlFormElementLabel(t('Anzahl'), new ElcaHtmlNumericInput('numberOfStringers', $defaultValue)));
        $this->appendSelector($group, 'stringer');
    }


    /**
     * @param HtmlElement $group
     */
    public function appendMiddleHolmForm(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Konstruktion Mittelholm')));
        $group->addClass('clearfix column construction middle-holm');
        $group->add(new HtmlTag('p', 'Für runde bzw. elliptische Mittelholmformen entsprechen die Angaben für Breite und Höhe dem horizontalen und vertikalen Durchmesser der Kreisform.'));

        $numericConverter = new ElcaNumberFormatConverter(2);

        //$group->add(new ElcaHtmlFormElementLabel('Holm'))->addClass('horizontal-label');
        $defaultValue = $this->staircase->getType() !== Staircase::TYPE_MIDDLE_HOLM ? MiddleHolm::SHAPE_RECTANGLE : null;
        $group->add(new ElcaHtmlFormElementLabel(t('Form'), $typeGroup = new HtmlRadioGroup('holmShape', $defaultValue)));
        $typeGroup->add(new HtmlRadiobox(t('Eckig'), MiddleHolm::SHAPE_RECTANGLE));
        $typeGroup->add(new HtmlRadiobox(t('Rund'), MiddleHolm::SHAPE_ELLIPSOID));

        $defaultValue = $this->staircase->getType() !== Staircase::TYPE_MIDDLE_HOLM ? MiddleHolm::ORIENTATION_ASCENDING : null;
        $group->add(new ElcaHtmlFormElementLabel(t('Verlauf'), $typeGroup = new HtmlRadioGroup('holmOrientation', $defaultValue)));
        $typeGroup->add(new HtmlRadiobox(t('Aufsteigend'), MiddleHolm::ORIENTATION_ASCENDING));
        $typeGroup->add(new HtmlRadiobox(t('Senkrecht'), MiddleHolm::ORIENTATION_VERTICAL));
        $group->add(new ElcaHtmlFormElementLabel(t('Länge'), new ElcaHtmlNumericInput('holmLength', null, false, $numericConverter), false, 'm'))->addClass('alternative-length');

        $this->appendStepsLengthChooser($group);

        $group->add(new ElcaHtmlFormElementLabel(t('Breite'), new ElcaHtmlNumericInput('holmWidth'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Höhe'), new ElcaHtmlNumericInput('holmHeight'), true, 'cm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Wandstärke'), new ElcaHtmlNumericInput('holmSize'),false, 'mm'));



        $this->appendSelector($group, 'holm');
    }

    /**
     * @param HtmlForm $form
     */
    public function appendPlatformForm(HtmlForm $form)
    {
        if (!$this->element->isInitialized()) {
            return;
        }
        $group = $form->add(new HtmlFormGroup(t('Podest')));
        $group->addClass('clearfix column clear platforms');

        $group->add(new ElcaHtmlFormElementLabel(t('Anzahl'), new ElcaHtmlNumericInput('numberOfPlatforms')));
        $group->add(new ElcaHtmlFormElementLabel(t('Breite'), new ElcaHtmlNumericInput('platformWidth'), true, 'm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Länge'), new ElcaHtmlNumericInput('platformHeight'), true, 'm'));

        $platform = $this->staircase->getPlatform();

        $compositeElementId = $this->element->isComposite()? $this->element->getId() : $this->element->getCompositeElement()->getId();

        $this->appendElementSelector($group, 'platformCoverElementId', $this->platformCoverElementId);
        if ($platform && $this->platformCoverElementId) {
            $div = $group->add(
                new HtmlTag('div', null, ['class' => 'edit-platform-element'])
            );
            $url = Url::factory(
                '/'. $this->context . '/' . $this->platformCoverElementId .'/',
                [
                    'tab' => 'general',
                    'rel' => $compositeElementId
                ]
            );
            $div->add(new HtmlLink('[ ' . t('Bearbeiten') . ' ]', $url))->addClass('page');

            $elementImageUrl = FrontController::getInstance()->getUrlTo('Elca\Controller\ElementImageCtrl', null, array('elementId' => $this->platformCoverElementId, 'legend' => '0'));

            $group->add(new HtmlTag('div', null, [
                'class' => 'element-image',
                'data-element-id' => $this->platformCoverElementId,
                'data-url' => $elementImageUrl
            ]));
        }

        $this->appendElementSelector($group, 'platformConstructionElementId', $this->platformConstructionElementId);
        if ($platform && $this->platformConstructionElementId) {
            $div = $group->add(
                new HtmlTag('div', null, ['class' => 'edit-platform-element'])
            );
            $url = Url::factory(
                '/'. $this->context . '/' . $this->platformConstructionElementId .'/',
                [
                    'tab' => 'general',
                    'rel' => $compositeElementId
                ]
            );
            $div->add(new HtmlLink('[ ' . t('Bearbeiten') . ' ]', $url))->addClass('page');

            $elementImageUrl = FrontController::getInstance()->getUrlTo('Elca\Controller\ElementImageCtrl', null, array('elementId' => $this->platformConstructionElementId, 'legend' => '0'));

            $group->add(new HtmlTag('div', null, [
                'class' => 'element-image',
                'data-element-id' => $this->platformConstructionElementId,
                'data-url' => $elementImageUrl
            ]));
        }

    }

    /**
     * @translate 'Material'
     * @translate 'Material'
     * @translate 'Material'
     * @translate 'Material'
     * @translate 'Material'
     * @translate 'Material'
     * @translate 'Material'
     */
    protected static $selectorConfig = [
        'cover' => [
            'label' => 'Material',
            'required' => true,
            'catRefNum' => '3.01',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_LAYERS,
            'inUnit' => 'm3'
        ],
        'riser' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '3.01',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_LAYERS,
            'inUnit' => 'm3'
        ],
        'solid1' => [
            'label' => 'Material',
            'required' => true,
            'catRefNum' => '1.04',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'solid2' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '4.01',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'stringer' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '3.01',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_LAYERS,
            'inUnit' => 'm3'
        ],
        'holm' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '7.01',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_LAYERS,
            'inUnit' => 'm3'
        ],
    ];

    /**
     * @param HtmlElement $container
     * @param             $key
     * @param null        $value
     */
    protected function appendSelector(HtmlElement $container, $key, $value = null)
    {
        $label = $container->add(new ElcaHtmlFormElementLabel(t(self::$selectorConfig[$key]['label']), null, self::$selectorConfig[$key]['required']));
        $label->setAttribute('id', 'processConfigSelector-'. $key);
        $label->addClass('assistant');

        if (self::$selectorConfig[$key]['horizontal'])
            $label->addClass('horizontal-label');

        /**
         * @var ElcaHtmlProcessConfigSelectorLink $selector
         */
        $selector = $label->add(new ElcaHtmlProcessConfigSelectorLink('materialId['.$key.']', $value));
        $selector->addClass('process-config-selector');
        $selector->setElementId($this->element->getId());
        $selector->setRelId($key);
        $selector->setProcessCategoryNodeId(ElcaProcessCategory::findByRefNum(self::$selectorConfig[$key]['catRefNum'])->getNodeId());
        $selector->setBuildMode(self::$selectorConfig[$key]['buildMode']);
        $selector->setContext(StaircaseCtrl::CONTEXT);
        $selector->setTplContext($this->context === ElementsCtrl::CONTEXT);
        $selector->setProcessDbId(Elca::getInstance()->getProject()->getProcessDbId());

        if (isset(self::$selectorConfig[$key]['inUnit']))
            $selector->setInUnit(self::$selectorConfig[$key]['inUnit']);

        if ($this->buildMode === self::BUILDMODE_SELECTOR)
            $selector->addClass('changed');
    }


    /**
     * @param HtmlForm $form
     */
    protected function appendButtons(HtmlForm $form)
    {
        if($form->isReadonly())
            return;

        /**
         * Buttons
         */
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clearfix column buttons');

        if(!$form->isReadonly())
        {
            $buttonGroup->add(new ElcaHtmlSubmitButton('saveElement', t('Speichern'), true));

            if(!$this->element instanceOf ElcaElement || !$this->element->isInitialized())
                $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbruch')));

            else
            {
                $compositeElement = $this->element->isComposite() ? $this->element : $this->element->getCompositeElement();

                if ($compositeElement && $compositeElement->isInitialized()) {
                    $ButtonDiv = $buttonGroup->add(new HtmlTag('div', null, ['class' => 'delete button']));
                    $deleteUrl = '/' . $this->context . '/delete/?id=' . $compositeElement->getId() .'&recursive';
                    $ButtonDiv->add(new HtmlLink(t('Löschen'), $deleteUrl));
                }
            }
        }
    }

    /**
     * Appends the components section
     *
     * @param  DOMElement $Container
     */
    protected function getElementImage()
    {
        $ImgContainer = new HtmlTag('div', null, [
                'class' => 'element-image',
                'data-url' => '/'.$this->context.'/elementImage/',
                'data-element-id' => $this->get('elementId')
            ]
        );

        return $ImgContainer;
    }

    private static $elementSelectorConfig = [
        'platformCoverElementId' => [
            'label' => 'Belag',
            'required' => false,
        ],
        'platformConstructionElementId' => [
            'label' => 'Konstruktion',
            'required' => false,
        ]
    ];

    /**
     * @param HtmlElement $container
     * @param             $key
     * @param null        $value
     */
    private function appendElementSelector(HtmlElement $container, $key, $value = null)
    {
        $label = $container->add(new ElcaHtmlFormElementLabel(t(self::$elementSelectorConfig[$key]['label']), null, self::$elementSelectorConfig[$key]['required']));
        $label->setAttribute('id', 'elementSelector-'. $key);
        $label->addClass('assistant');

        $compositeElementId = null;
        if ($this->element && $this->element->isInitialized()) {
            $compositeElementId = $this->element->isComposite()
                ? $this->element->getId()
                : $this->element->getCompositeElement()->getId();
        }

        /**
         * @var ElcaHtmlElementSelectorLink $selector
         */
        $selector = $label->add(new ElcaHtmlElementSelectorLink($key, $value));
        $selector->addClass('element-selector');
        $selector->setPosition($key);

        if ($compositeElementId)
            $selector->setRelId($compositeElementId);

        $selector->setContext($this->context);
        $selector->setUrl(FrontController::getInstance()->getUrlTo('Elca\Controller\Assistant\StaircaseCtrl', 'selectElement'));
        $selector->setBuildMode(ElcaElementSelectorView::BUILDMODE_ELEMENTS);
        $selector->setElementTypeNodeId($this->elementTypeNodeId);
        $selector->setRefUnit(Elca::UNIT_M2);

        if ($this->buildMode === self::BUILDMODE_ELEMENT_SELECTOR)
            $selector->addClass('changed');
    }

    /**
     * @param HtmlFormGroup $group
     */
    private function appendStepsLengthChooser(HtmlFormGroup $group)
    {
        $numericConverter = new ElcaNumberFormatConverter(2);

        $div = new HtmlTag('div', null, ['class' => 'calcLength']);
        $span = $div->add(new HtmlTag('span', null, ['class' => 'stepsLength']));
        $span->add(new ElcaHtmlNumericText('stepsLength', null, false, '?', $numericConverter));

        if (!$this->readOnly)
            $div->add(new HtmlLink(t('übernehmen')))->setAttribute('rel', 'set');

        $group->add(new ElcaHtmlFormElementLabel(t('Errechnete Länge'), $div, false, 'm'))->addClass('alternativeLength');
    }

}
