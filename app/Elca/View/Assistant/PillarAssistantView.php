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

namespace Elca\View\Assistant;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlImage;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Controller\ElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessCategory;
use Elca\Elca;
use Elca\Model\Assistant\Pillar\Pillar;
use Elca\View\ElcaElementComponentsView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;

class PillarAssistantView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECTOR = 'selector';

    protected static $selectorConfig = [
        'material1' => [
            'label'      => 'Material',
            'required'   => true,
            'catRefNum'  => '1.04',
            'horizontal' => false,
            'buildMode'  => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit'     => 'm3',
        ],
        'material2' => [
            'label'      => '',
            'required'   => false,
            'catRefNum'  => '4.01',
            'horizontal' => false,
            'buildMode'  => ElcaElementComponentsView::BUILDMODE_COMPONENTS,
            'inUnit'     => 'm3',
        ],
    ];

    protected $allowedShapes;

    /**
     * @var Pillar
     */
    private $pillar;

    private $element;

    /**
     * Current buildmode
     */
    private $buildMode;

    private $readOnly;

    private $context;

    private $elementTypeNodeId;

    private $assistantIdent;

    private $assistantContext;

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
        $this->element   = ElcaElement::findById($this->get('elementId'));
        $this->pillar    = $this->get('pillar');
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);

        $this->context = $this->get('context', ElementsCtrl::CONTEXT);

        $this->assistantContext  = $this->get('assistantContext');
        $this->elementTypeNodeId = $this->get('elementTypeNodeId', $this->element->getElementTypeNodeId());

        $this->assistantIdent = $this->get('assistantIdent');

        if ($this->get('readOnly', false)) {
            $this->readOnly = true;
        }

        $this->allowedShapes = ['rectangular', 'cylindric'];
    }

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        switch ($this->buildMode) {
            case self::BUILDMODE_SELECTOR:
                $form = new HtmlForm('assistantForm', '/' . $this->assistantContext . '/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if ($this->readOnly) {
                    $form->setReadonly();
                }

                $key   = $this->get('key');
                $value = $this->get('value');

                if (!$key) {
                    return;
                }

                $this->appendSelector($form, $key, $value);

                // append form to dummy container
                $dummyContainer = $this->appendChild($this->getDiv());
                $form->appendTo($dummyContainer);

                // extract elementComponent container and replace it with the dummy container
                $content = $this->getElementById('processConfigSelector-' . $key);
                $this->replaceChild($content, $dummyContainer);
                break;

            default:
            case self::BUILDMODE_DEFAULT:
                $form = new HtmlForm('assistantForm', '/' . $this->assistantContext . '/save/');
                $form->setAttribute('id', 'assistantForm');
                $form->addClass('clearfix');

                if ($this->readOnly) {
                    $form->setReadonly();
                }

                if ($this->has('Validator')) {
                    $form->setRequest(FrontController::getInstance()->getRequest());
                    $form->setValidator($this->get('Validator'));
                } else {
                    $form->setDataObject($this->pillar->getDataObject());
                }

                $form->add(new HtmlHiddenField('context', $this->context));
                $form->add(new HtmlHiddenField('projectVariantId', Elca::getInstance()->getProjectVariantId()));

                if ($this->element instanceOf ElcaElement && $this->element->isInitialized()) {
                    $form->addClass('highlight-changes');
                    $form->add(new HtmlHiddenField('e', $this->element->getId()));
                } else {
                    $form->add(new HtmlHiddenField('t', $this->elementTypeNodeId));
                }

                $content = $this->appendChild(
                    $this->getDiv(
                        [
                            'id'    => 'tabContent',
                            'class' => 'assistant pillar-assistants tab-' . $this->assistantIdent . ' ' . $this->context,
                        ]
                    )
                );

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
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('clearfix column properties');
        $group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true));

        $select = $group->add(new ElcaHtmlFormElementLabel(t('Bezugsgröße'), new HtmlSelectbox('unit'), true));
        if ($this->readOnly) {
            $select->setReadonly(true, false);
        }

        $select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
        foreach ([Elca::UNIT_STK, Elca::UNIT_M] as $unit) {
            $select->add(new HtmlSelectOption(t(Elca::$units[$unit]), $unit));
        }

        /**
         * Dimensions
         */
        $group = $form->add(new HtmlFormGroup(t('Abmessungen')));
        $group->addClass('clearfix column clear properties');

        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Grundfläche'),
                $typeGroup = new HtmlRadioGroup('shape', $this->pillar->shape(), $this->readOnly)
            )
        );

        if (in_array('rectangular', $this->allowedShapes, true)) {
            $typeGroup->add(new HtmlRadiobox(t('rechteckig'), 'rectangular'));
        }

        if (in_array('cylindric', $this->allowedShapes, true)) {
            $typeGroup->add(new HtmlRadiobox(t('rund'), 'cylindric'));
        }

        $group->add(new ElcaHtmlFormElementLabel(t('Länge'), new ElcaHtmlNumericInput('height'), true, 'm'));

        if (in_array('rectangular', $this->allowedShapes, true)) {
            $rectContainer = $group->add(new HtmlTag('div', null, ['class' => 'shape-properties rectangular']));
            $rectContainer->add(
                new ElcaHtmlFormElementLabel(t('Breite'), new ElcaHtmlNumericInput('width'), true, 'm')
            );
            $rectContainer->add(
                new ElcaHtmlFormElementLabel(t('Tiefe'), new ElcaHtmlNumericInput('length'), true, 'm')
            );
        }

        if (in_array('cylindric', $this->allowedShapes, true)) {
            $cylContainer = $group->add(new HtmlTag('div', null, ['class' => 'shape-properties cylindric']));
            $cylContainer->add(
                new ElcaHtmlFormElementLabel(t('Radius'), new ElcaHtmlNumericInput('radius'), true, 'm')
            );
        }

        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Mantelfläche'), new ElcaHtmlNumericInput('surface', null, true), false, 'm2'
            )
        );

        $group = $form->add(new HtmlFormGroup(t('Material(ien)')));
        $group->addClass('clearfix column clear properties');

        $percentageConverter = new ElcaNumberFormatConverter(0, true);

        $this->appendSelector($group, 'material1');
        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Anteil'),
                $input = new ElcaHtmlNumericInput('material1Share', null, false, $percentageConverter),
                false,
                '%'
            )
        );
        $input->setPrecision(0);
        $input->disableNegative();

        $this->appendSelector($group, 'material2');
        $group->add(
            new ElcaHtmlFormElementLabel(
                '', //t('Anteil'),
                $input = new ElcaHtmlNumericInput('material2Share', null, false, $percentageConverter),
                false,
                null //'%'
            )
        );
        $input->setPrecision(0);
        $input->disableNegative();


        /**
         * Element image
         */
        $this->appendElementImage($form);

        $this->appendButtons($form);
    }

    /**
     * @param HtmlElement $container
     * @param             $key
     * @param null        $value
     */
    protected function appendSelector(HtmlElement $container, $key, $value = null)
    {
        $label = $container->add(
            new ElcaHtmlFormElementLabel(
                self::$selectorConfig[$key]['label'] ? t(self::$selectorConfig[$key]['label']) : '',
                null,
                self::$selectorConfig[$key]['required']
            )
        );
        $label->setAttribute('id', 'processConfigSelector-' . $key);
        $label->addClass('assistant');

        if (self::$selectorConfig[$key]['horizontal']) {
            $label->addClass('horizontal-label');
        }

        /**
         * @var ElcaHtmlProcessConfigSelectorLink $selector
         */
        $selector = $label->add(new ElcaHtmlProcessConfigSelectorLink($key, $value));
        $selector->addClass('process-config-selector');
        $selector->setElementId($this->element->getId());
        $selector->setRelId($key);
        $selector->setProcessCategoryNodeId(
            ElcaProcessCategory::findByRefNum(self::$selectorConfig[$key]['catRefNum'])->getNodeId()
        );
        $selector->setBuildMode(self::$selectorConfig[$key]['buildMode']);
        $selector->setContext($this->assistantContext);
        $selector->setProcessDbId(Elca::getInstance()->getProject()->getProcessDbId());

        if (isset(self::$selectorConfig[$key]['inUnit'])) {
            $selector->setInUnit(self::$selectorConfig[$key]['inUnit']);
        }

        if ($this->buildMode === self::BUILDMODE_SELECTOR) {
            $selector->addClass('changed');
        }
    }

    /**
     * @param HtmlForm $form
     */
    protected function appendButtons(HtmlForm $form)
    {
        if ($form->isReadonly()) {
            return;
        }

        /**
         * Buttons
         */
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clearfix column buttons');

        if (!$form->isReadonly()) {
            $buttonGroup->add(new ElcaHtmlSubmitButton('saveElement', t('Speichern'), true));

            if (!$this->element instanceOf ElcaElement || !$this->element->isInitialized()) {
                $buttonGroup->add(new HtmlLink(t('Abbruch'), (string)Url::factory('/'.$this->context.'/list/?t='.$this->elementTypeNodeId)))
                    ->addClass('button');
            } else {
                $compositeElement = $this->element->isComposite() ? $this->element
                    : $this->element->getCompositeElement();

                if ($compositeElement && $compositeElement->isInitialized()) {
                    $ButtonDiv = $buttonGroup->add(new HtmlTag('div', null, ['class' => 'delete button']));
                    $deleteUrl = '/' . $this->context . '/delete/?id=' . $compositeElement->getId() . '&recursive';
                    $ButtonDiv->add(new HtmlLink(t('Löschen'), $deleteUrl));
                }
            }
        }
    }

    /**
     * @param HtmlForm $form
     */
    protected function appendElementImage(HtmlForm $form)
    {
        $baseUrl = PillarElementImageView::IMG_DIRECTORY;

        $container = $form->add(new HtmlTag('div', null, ['class' => 'type-images']));
        $container->add(new HtmlImage($baseUrl . 'rectangular.png'))->addClass('rectangular');
        $container->add(new HtmlImage($baseUrl . 'cylindric.png'))->addClass('cylindric');
    }
}
