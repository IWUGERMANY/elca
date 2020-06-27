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
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use DOMNode;
use Elca\Controller\Assistant\DormerCtrl;
use Elca\Controller\ElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessCategory;
use Elca\Elca;
use Elca\Model\Assistant\Window\Window;
use Elca\View\ElcaElementComponentsView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
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
class DormerAssistantView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECTOR = 'selector';

    /**
     * @var Window
     */
    protected $window;

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
        $this->window = $this->get('window');

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
                $form = new HtmlForm('assistantForm', '/assistant/dormer/save/');
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

            default:
            case self::BUILDMODE_DEFAULT:
                $form = new HtmlForm('assistantForm', '/assistant/dormer/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if($this->readOnly)
                    $form->setReadonly();

                if($this->has('Validator')) {
                    $form->setRequest(FrontController::getInstance()->getRequest());
                    $form->setValidator($this->get('Validator'));
                } else {
                    $form->setDataObject($this->window->getDataObject());
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

                $content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'assistant tab-dormer-assistant tab-window-assistant '.$this->context]));
                $this->appendIdInfo($content);
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
        $percentageConverter = new ElcaNumberFormatConverter(1, true);

        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('clearfix column properties');
        $group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true));


        /**
         * Dimensions
         */
        $group = $form->add(new HtmlFormGroup(t('Abmessungen')));
        $group->addClass('clearfix column clear properties');

        $group->add(new ElcaHtmlFormElementLabel(t('Fenstermaß')))->addClass('horizontal-label opening-dim');
        $group->add(new ElcaHtmlFormElementLabel(t('Breite'), new ElcaHtmlNumericInput('width'), true, 'm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Höhe'), new ElcaHtmlNumericInput('height'), true, 'm'));
        $group->add(new ElcaHtmlFormElementLabel(t('Fläche'), new ElcaHtmlNumericInput('area', null, true), false, 'm2'));

        $group->add(new ElcaHtmlFormElementLabel(t('Anschlussfuge'), new ElcaHtmlNumericInput('sealingWidth'), false, 'mm'));
        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Abzugsfläche'),
                new ElcaHtmlNumericInput('openingArea', null, true),
                false,
                'm2'
            )
        );

        $group->add(new ElcaHtmlFormElementLabel(t('Rahmenbreite')))->addClass('horizontal-label frame-widths');
        $group->add(
            new ElcaHtmlFormElementLabel(t('Blendrahmen'), new ElcaHtmlNumericInput('fixedFrameWidth'), true, 'cm')
        );
        $group->add(
            new ElcaHtmlFormElementLabel(t('Flügelrahmen'), new ElcaHtmlNumericInput('sashFrameWidth'), false, 'cm')
        );

        if ($this->window instanceof Window ) {
            $fixedFrame = $this->window->getFixedFrame();

            $group->add(new ElcaHtmlFormElementLabel(t('Teilung')))->addClass('horizontal-label mullions-transoms');
            $group->add(new ElcaHtmlFormElementLabel(t('Pfosten'), new ElcaHtmlNumericInput('numberOfMullions')));
            $group->add(new ElcaHtmlFormElementLabel(t('Riegel'), new ElcaHtmlNumericInput('numberOfTransoms')));

            if ($fixedFrame->getNumberOfMullions() > 0 || $fixedFrame->getNumberOfTransoms() > 0) {
                $group->add(new ElcaHtmlFormElementLabel(' ', $link = new HtmlLink('Details', '#')))->addClass(
                    'mullion-transom-details'
                );
                $link->setAttribute('rel', 'toggle-mullion-transom-details');



                $hasIndividualFrameDimensions = $this->window->getFixedFrame()->hasIndividualFrameDimensions();

                $attr = [
                    'class' => 'mullions-transoms-dims clear clearfix'
                ];

                if (!$hasIndividualFrameDimensions) {
                    $link->addClass('closed');
                    $attr['style'] = 'display:none';
                }

                $div = $group->add(new HtmlTag('div', null, $attr));

                if ($fixedFrame->getNumberOfMullions() > 0) {
                    $div->add(new ElcaHtmlFormElementLabel(t('Breiten von links nach rechts')))->addClass(
                        'horizontal-label clear'
                    );
                    $fixedFrame = $this->window->getFixedFrame();

                    for ($w = 0; $w <= $fixedFrame->getNumberOfMullions(); $w++) {
                        $label = $div->add(
                            new ElcaHtmlFormElementLabel(
                                ($w + 1) . '. ' . t('Breite'),
                                $input = new ElcaHtmlNumericInput(
                                    'tileWidth[' . $w . ']',
                                    null,
                                    false,
                                    $percentageConverter
                                ),
                                false,
                                '%'
                            )
                        );
                        $input->setPrecision(0);

                        if ($w > 0 && $w % 3 === 0) {
                            $label->addClass('first');
                        }
                    }
                }

                if ($fixedFrame->getNumberOfTransoms() > 0) {
                    $div->add(new ElcaHtmlFormElementLabel(t('Höhen von oben nach unten')))->addClass(
                        'horizontal-label clear'
                    );

                    for ($l = 0; $l <= $fixedFrame->getNumberOfTransoms(); $l ++) {
                        $label = $div->add(
                            new ElcaHtmlFormElementLabel(
                                ($l + 1) . '. ' . t('Höhe'),
                                $input = new ElcaHtmlNumericInput(
                                    'tileHeight[' . $l . ']',
                                    null,
                                    false,
                                    $percentageConverter
                                ),
                                false,
                                '%'
                            )
                        );
                        $input->setPrecision(0);

                        if ($l > 0 && $l % 3 === 0) {
                            $label->addClass('first');
                        }

                    }
                }
            }


            $group->add(new ElcaHtmlFormElementLabel(t('Festehende Pfosten und Riegel')))->addClass('horizontal-label mullions-transoms');
            $group->add(
                new ElcaHtmlFormElementLabel(
                    '',
                    new HtmlCheckbox('fixedMullionsTransoms')
                )
            );

            $group->add(new ElcaHtmlFormElementLabel(t('Oberlicht')))->addClass('horizontal-label top-light');
            $group->add(new ElcaHtmlFormElementLabel(t('vorhanden?'), new HtmlCheckbox('hasTopLight')));
            $group->add(new ElcaHtmlFormElementLabel(t('Höhe'), new ElcaHtmlNumericInput('topLightHeight', null, !$fixedFrame->hasTopLight()), false, 'cm'));

            //if ($fixedFrame->getNumberOfMullions() > 0 || $fixedFrame->getNumberOfTransoms() > 0 || $fixedFrame->hasTopLight()) {
            //}
        }

        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Rahmenanteil'),
                new ElcaHtmlNumericInput('frameRatio', null, true, $percentageConverter),
                false,
                '%'
            )
        );
        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Glasanteil'),
                new ElcaHtmlNumericInput('glassRatio', null, true, $percentageConverter),
                false,
                '%'
            )
        );

        /**
         * Element image
         */
        if ($EltImgContainer = $this->getElementImage()) {
            $form->add($EltImgContainer);
        }

        $leftGroup = $form->add(new HtmlFormGroup(''));
        $leftGroup->addClass('left-group');
        $rightGroup = $form->add(new HtmlFormGroup(''));
        $rightGroup->addClass('right-group');

        /**
         * Material profiles
         *
         */
        $group = $leftGroup->add(new HtmlFormGroup(t('Material des Rahmens')));
        $group->addClass('clearfix column properties');

        $this->appendSelector($group, 'fixedFrame');
        $this->appendSelector($group, 'sashFrame');

        /**
         * Material glass
         */
        $group = $leftGroup->add(new HtmlFormGroup(t('Verglasung')));
        $group->addClass('clearfix column properties');

        $this->appendSelector($group, 'glass');

        /**
         * Material sealing
         */
        $group = $leftGroup->add(new HtmlFormGroup(t('Anschlussfuge')));
        $group->addClass('clearfix column properties');

        $this->appendSelector($group, 'sealing');

        /**
         * Material fittings
         */
        $group = $rightGroup->add(new HtmlFormGroup(t('Beschläge und Griffe')));
        $group->addClass('clearfix column properties');

        $group->add(new ElcaHtmlFormElementLabel(t('Beschläge')))->addClass('horizontal-label');
        $this->appendSelector($group, 'fittings');
        $group->add(new ElcaHtmlFormElementLabel(t('Anzahl'), new ElcaHtmlNumericInput('fittings')));

        $group->add(new ElcaHtmlFormElementLabel(t('Griffe')))->addClass('horizontal-label');
        $this->appendSelector($group, 'handles');
        $group->add(new ElcaHtmlFormElementLabel(t('Anzahl'), new ElcaHtmlNumericInput('handles')));

        /**
         * Material sunscreen outdoor
         */
        $group = $rightGroup->add(new HtmlFormGroup(t('Sonnenschutz (außen)')));
        $group->addClass('clearfix column properties');

        $this->appendSelector($group, 'sunscreenOutdoor');

        /**
         * Material sunscreen indoor
         */
        $group = $rightGroup->add(new HtmlFormGroup(t('Blendschutz (innen)')));
        $group->addClass('clearfix column properties');

        $this->appendSelector($group, 'sunscreenIndoor');

        /**
         * Material indoor
         */
        $group = $form->add(new HtmlFormGroup(t('Innen')));
        $group->addClass('clearfix column properties clear');

        $group->add(new ElcaHtmlFormElementLabel(t('Fensterbank')))->addClass('horizontal-label');
        $this->appendSelector($group, 'sillIndoor');

        $group->add(
            new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('sillIndoorSize'), false, 'mm')
        );

        $group->add(
            new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('sillIndoorDepth'), false, 'cm')
        );

        $group->add(new ElcaHtmlFormElementLabel(t('Fensterlaibung')))->addClass('horizontal-label');
        $this->appendSelector($group, 'soffitIndoor');

        $group->add(
            new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('soffitIndoorSize'), false, 'mm')
        );
        $group->add(
            new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('soffitIndoorDepth'), false, 'cm')
        );

        /**
         * Material window soffit
         */
        $group = $form->add(new HtmlFormGroup(t('Außen')));
        $group->addClass('clearfix column properties');

        $group->add(new ElcaHtmlFormElementLabel(t('Fensterbank')))->addClass('horizontal-label');
        $this->appendSelector($group, 'sillOutdoor');

        $group->add(
            new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('sillOutdoorSize'), false, 'mm')
        );
        $group->add(
            new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('sillOutdoorDepth'), false, 'cm')
        );

        $group->add(new ElcaHtmlFormElementLabel(t('Fensterlaibung')))->addClass('horizontal-label');
        $this->appendSelector($group, 'soffitOutdoor');

        $group->add(
            new ElcaHtmlFormElementLabel(t('Dicke'), new ElcaHtmlNumericInput('soffitOutdoorSize'), false, 'mm')
        );
        $group->add(
            new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('soffitOutdoorDepth'), false, 'cm')
        );

        $this->appendButtons($form);
    }


    protected static $selectorConfig = [
        'fixedFrame' => [
            'label' => 'Blendrahmen',
            'required' => true,
            'catRefNum' => '7.01',
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm'
        ],
        'sashFrame' => [
            'label' => 'Flügelrahmen',
            'required' => false,
            'catRefNum' => '7.01',
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm'
        ],
        'fittings' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '7.04',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'Stück'
        ],
        'handles' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '7.04',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'Stück'
        ],
        'glass' => [
            'label' => 'Material',
            'required' => true,
            'catRefNum' => '7.02',
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm2'
        ],
        'sealing' => [
            'label' => 'Material',
            'required' => true,
            'catRefNum' => '7.03',
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_LAYERS,
            'inUnit' => 'm3'
        ],
        'sunscreen' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '7.01',
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm2'
        ],
        'sillIndoor' => [
            'label' => 'Material',
            'required' => false,
            'catRefNum' => '1.03',
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'sillOutdoor' => [
            'label' => 'Material',
            'catRefNum' => '4.03',
            'required' => false,
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'soffitIndoor' => [
            'label' => 'Material',
            'catRefNum' => '1.04',
            'required' => false,
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'soffitOutdoor' => [
            'label' => 'Material',
            'catRefNum' => '1.04',
            'required' => false,
            'horizontal' => false,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm3'
        ],
        'sunscreenIndoor' => [
            'label' => 'Material',
            'catRefNum' => '6.06',
            'required' => false,
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm2'
        ],
        'sunscreenOutdoor' => [
            'label' => 'Material',
            'catRefNum' => '7.02',
            'required' => false,
            'horizontal' => true,
            'buildMode' => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit' => 'm2'
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
        $selector = $label->add(new ElcaHtmlProcessConfigSelectorLink('processConfigId['.$key.']', $value));
        $selector->addClass('process-config-selector');
        $selector->setElementId($this->element->getId());
        $selector->setRelId($key);
        $selector->setProcessCategoryNodeId(ElcaProcessCategory::findByRefNum(self::$selectorConfig[$key]['catRefNum'])->getNodeId());
        $selector->setBuildMode(self::$selectorConfig[$key]['buildMode']);
        $selector->setContext(DormerCtrl::CONTEXT);
        $selector->setProcessDbId(Elca::getInstance()->getProject()->getProcessDbId());
        $selector->setTplContext($this->context === ElementsCtrl::CONTEXT);

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
                $ButtonDiv = $buttonGroup->add(new HtmlTag('div', null, ['class' => 'delete button']));
                $deleteUrl = '/'.$this->context. '/delete/?id='.$this->element->getId();
                $ButtonDiv->add(new HtmlLink(t('Löschen'), $deleteUrl));
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
