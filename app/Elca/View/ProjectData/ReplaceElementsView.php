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

namespace Elca\View\ProjectData;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Elca\Controller\ElementImageCtrl;
use Elca\Controller\ProjectData\ReplaceElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlCheckbox;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlTemplateElementSelectorLink;

class ReplaceElementsView extends HtmlView
{
    const BUILDMODE_ELEMENT_SELECTOR = 'selector';
    const BUILDMODE_DEFAULT = 'default';

    private $projectVariantId;

    private $data;

    private $context;

    private $buildMode;

    private $projectVariantIsActive;

    /**
     * Inits the view
     *
     * @param array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('project_data/replace_elements');

        $this->buildMode              = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->projectVariantId       = $this->get('projectVariantId');
        $this->projectVariantIsActive = $this->get('projectVariantIsActive', false);
        $this->data                   = $this->get('data', new \stdClass());
        $this->context                = $this->get('context');
    }

    protected function beforeRender()
    {
        $container = $this->getElementById('content');

        $formId = 'replaceElementsForm';
        $form   = new HtmlForm($formId, '/elca/project-data/replace-elements/save/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix');

        if ($this->data) {
            $form->setDataObject($this->data);
        }
        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
        }

        switch ($this->buildMode) {
            case self::BUILDMODE_ELEMENT_SELECTOR:
                $this->removeChild($container);

                $this->appendTemplateElementSelector($form, $this->get('elementTypeNodeId'), $this->get('elementId'));

                // append form to dummy container
                $dummyContainer = $this->appendChild($this->getDiv());
                $form->appendTo($dummyContainer);

                // extract elementComponent container and replace it with the dummy container
                $content = $this->getElementById('templateElement');
                $this->replaceChild($content, $dummyContainer);
                break;

            default:
                $form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));

                $this->appendDin276Select(
                    $form,
                    t('Ersetze Bauteil in folgender Kostengruppe'),
                    ElcaElementType::findByIdent('300')->getNodeId(),
                    0
                );

                if (!empty($this->data->din276[0])) {
                    $this->appendTemplateElementSelector($form, $this->data->din276[0]);
                    $this->appendMatchingElements($form, $this->data->din276[0]);
                }

                $this->appendButtons($form);

                $form->appendTo($container);
        }
    }

    protected function appendElementImage(HtmlElement $container, ElcaElement $element)
    {
        if (!$element->getElementTypeNode()->getPrefHasElementImage()) {
            return;
        }

        $elementImageUrl = FrontController::getInstance()->getUrlTo(
            ElementImageCtrl::class,
            null,
            array('elementId' => $element->getId(), 'legend' => '0')
        );

        $svgDiv = new HtmlTag(
            'div', null, [
            'class'           => 'element-image',
            'data-element-id' => $element->getId(),
            'data-url'        => $elementImageUrl,
        ]
        );


        $container->add($svgDiv);
        $container->addClass('has-element-image clearfix');
    }

    private function appendDin276Select(HtmlElement $container, $caption, $parentNodeId, $level): void
    {
        $rootNode = ElcaElementType::findByNodeId($parentNodeId);

        $select = $container->add(
            new ElcaHtmlFormElementLabel(
                $caption,
                new HtmlSelectbox('din276[' . ($level) . ']')
            )
        );
        $select->setAttribute('onchange', '$(this.form).submit()');

        $select->add(new HtmlSelectOption(t('-- Bitte wählen --'), null));
        foreach (ElcaElementTypeSet::findByParentType($rootNode) as $type) {
            $select->add(new HtmlSelectOption($type->getDinCode() . ' - ' . t($type->getName()), $type->getNodeId()));
        }
    }

    private function appendMatchingElements(HtmlElement $container, $compositeElementTypeNodeId)
    {
        $composites = ElcaElementSet::findCompositesByElementTypeNodeId(
            $compositeElementTypeNodeId,
            $this->projectVariantId
        );

        $group = $container->add(
            new HtmlFormGroup(
                t(
                    ':count: Bauteile gefunden',
                    null,
                    [
                        ':count:' => $composites->count(),
                    ]
                )
            )
        );
        $group->addClass('matching-elements');
        $group->add(new HtmLTag('h5', t('Menge'), ['class' => 'hl-quantity']));

        $ul = $group->add(new HtmlTag('ul', null, ['class' => 'composite-elements']));

        /**
         * @var ElcaElement $compositeElement
         */
        foreach ($composites as $compositeElement) {
            $li = $ul->add(new HtmlTag('li', null, ['class' => 'composite-element']));

            $this->appendElementImage($li, $compositeElement);

            $li->add(
                new ElcaHtmlCheckbox('replaceElements[' . $compositeElement->getId() . ']', $compositeElement->getId())
            )->addClass('column check');

            if ($this->projectVariantIsActive) {
                $li->add(
                    new HtmlTag(
                        'a', $compositeElement->getName() . ' [' . $compositeElement->getId() . ']', [
                        'href'  => '/project-elements/' . $compositeElement->getId() . '/',
                        'class' => 'page',
                    ]
                    )
                );
            } else {
                $li->add(
                    new HtmlTag(
                        'span', $compositeElement->getName() . ' [' . $compositeElement->getId() . ']'
                    )
                );
            }

            $li->add(
                new HtmlTag(
                    'span', ElcaNumberFormat::toString($compositeElement->getQuantity()), [
                    'class' => 'column quantity',
                ]
                )
            );
            $li->add(
                new HtmlTag(
                    'span', ElcaNumberFormat::formatUnit($compositeElement->getRefUnit()), [
                    'class' => 'column ref-unit',
                ]
                )
            );

            $elementsUl = $li->add(new HtmlTag('ul', null, ['class' => 'elements']));

            foreach ($compositeElement->getCompositeElements() as $elementAssignment) {
                $element   = $elementAssignment->getElement();
                $elementLi = $elementsUl->add(new HtmlTag('li', null, ['class' => 'element']));

                if ($this->projectVariantIsActive) {
                    $elementLi->add(
                        new HtmlTag(
                            'a', $element->getName() . ' [' . $element->getId() . ']', [
                            'href'  => '/project-elements/' . $element->getId() . '/',
                            'class' => 'page element-name',
                        ]
                        )
                    );
                } else {
                    $elementLi->add(
                        new HtmlTag(
                            'span', $element->getName() . ' [' . $element->getId() . ']', [
                                'class' => 'element-name',
                            ]
                        )
                    );
                }

                $elementLi->add(
                    new HtmlTag(
                        'span', ElcaNumberFormat::toString($element->getQuantity()), [
                        'class' => 'column quantity',
                    ]
                    )
                );
                $elementLi->add(
                    new HtmlTag(
                        'span', ElcaNumberFormat::formatUnit($element->getRefUnit()), [
                        'class' => 'column ref-unit',
                    ]
                    )
                );
            }

        }
    }

    private function appendTemplateElementSelector(HtmlElement $container, $elementTypeNodeId, $elementId = null)
    {
        $group = $container->add(new HtmlFormGroup(t('Neues Bauteil konfigurieren')));
        $group->setId('templateElement');

        /**
         * @var ElcaHtmlTemplateElementSelectorLink $selector
         */
        $label = $group->add(new ElcaHtmlFormElementLabel(t('Bauteil'), null, true));

        $selector = $label->add(new ElcaHtmlTemplateElementSelectorLink('elementId', $elementId));
        $selector->addClass('element-selector');
        $selector->setUrl(FrontController::getInstance()->getUrlTo(ReplaceElementsCtrl::class, 'selectElement'));
        $selector->setElementTypeNodeId($elementTypeNodeId);
        $selector->setProjectVariantId($this->projectVariantId);

        $this->appendElementImage($label, ElcaElement::findById($this->data->elementId));

        $this->appendTemplateElement($label);
    }

    private function appendTemplateElement(HtmlElement $container)
    {
        if (empty($this->data->elementId)) {
            return;
        }

        $compositeElement = ElcaElement::findById($this->data->elementId);

        $ul = $container->add(new HtmlTag('ul', null, ['class' => 'composite-elements']));

        foreach ($compositeElement->getCompositeElements() as $elementAssignment) {
            $element   = $elementAssignment->getElement();
            $elementLi = $ul->add(new HtmlTag('li', null, ['class' => 'composite-element']));
            $elementLi->add(
                new HtmlTag(
                    'span', $element->getName() . ' [' . $element->getId() . ']', [
                        'class' => 'element-name',
                    ]
                )
            );
        }
    }

    private function appendButtons(HtmlElement $element)
    {
        $group = $element->add(new HtmlFormGroup(''));
        $group->addClass('buttons');

        $button = $group->add(new ElcaHtmlSubmitButton('replaceSelected', t('Markierte Bauteile ersetzen'), true));
        $button->setId('replaceButton');
    }
    // End addElementImage
}

