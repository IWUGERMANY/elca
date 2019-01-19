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

namespace ImportAssistant\View\Admin;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElement;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSubmitButton;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaProcessDbSet;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;
use ImportAssistant\Controller\Admin\MappingsCtrl;

class MappingEditView extends HtmlView
{
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECT = 'select';

    /**
     * @var string
     */
    private $buildMode;

    /**
     * @var object
     */
    private $data;

    private $context;

    private $changedMapping;

    /**
     * Inits the view
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        $this->buildMode      = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->context        = $this->get('context');
        $this->data           = $this->get('data');
        $this->changedMapping = $this->get('changedMapping');

        if ($this->buildMode === self::BUILDMODE_DEFAULT) {
            $this->setTplName('admin/mapping_edit', 'importAssistant');
        }
    }

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $formId = 'materialMappingForm';
        $form   = new HtmlForm($formId, '/importAssistant/admin/mappings/save/');
        $form->addClass('clearfix highlight-changes');
        $form->setId($formId);
        $form->setDataObject($this->data);

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $form->add(new HtmlHiddenField('id'));

        switch ($this->buildMode) {
            case self::BUILDMODE_DEFAULT:
                $container = $this->getElementById('content');
                $this->appendDefaultForm($form);
                $this->appendMappings($form);
                $this->appendButtons($form);
                $form->appendTo($container);
                break;

            case self::BUILDMODE_SELECT:
                $selectorContainer = $this->appendSelector($form, $this->changedMapping);

                // append form to dummy container
                $replacementContainer = $this->appendChild($this->getDiv());
                $form->appendTo($replacementContainer);

                // extract mappings container and replace it with the dummy container
                $replacementContent = $this->getElementById($selectorContainer->getId());
                $this->replaceChild($replacementContent, $replacementContainer);
                break;
        }
    }

    /**
     * @param $form
     * @return HtmlElement
     */
    protected function appendDefaultForm(HtmlElement $form)
    {
        $group = $form->add(new HtmlFormGroup(''));
        $group->add(new ElcaHtmlFormElementLabel(t('Materialname'), new HtmlTextInput('materialName'), true));

        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Zuordnungsmodus'),
                $radioGroup = new HtmlRadioGroup('mode')
            )
        );

        foreach (MappingsCtrl::$mappingModeMap as $value => $caption) {
            $radioGroup->add(new HtmlRadiobox(t($caption), $value));
        }

        $radioGroup->setAttribute('id', 'mappingMode');

        return $group;
    }

    /**
     * @param HtmlElement $form
     * @return HtmlElement
     */
    protected function appendMappings(HtmlElement $form)
    {
        $group = $form->add(new HtmlFormGroup(''));

        $this->appendSelector($group, 0);


        $div = $group->add(new HtmlTag('div', null, ['id' => 'siblingMappings']));
        $percentageConverter = new ElcaNumberFormatConverter(0, true);

        $div->add(
            new ElcaHtmlFormElementLabel(
                t('Anteil'),
                new ElcaHtmlNumericInput('siblingRatio[0]', null, false, $percentageConverter),
                true,
                '%'
            )
        );

        $this->appendSelector($div, 1);
        $div->add(
            new ElcaHtmlFormElementLabel(
                t('Anteil'),
                new ElcaHtmlNumericInput('siblingRatio[1]', null, false, $percentageConverter),
                $this->data->mode === MappingsCtrl::MAPPING_TYPE_SIBLINGS,
                '%'
            )
        );

        $div = $group->add(new HtmlTag('div', null, ['id' => 'multipleMappings']));
        $this->appendSelector($div, 2);

        return $group;
    }

    /**
     * @param $form
     */
    protected function appendButtons($form)
    {
        $group = $form->add(new HtmlFormGroup(''));
        $group->setAttribute('class', 'buttons');

        $group->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));
        $group->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
    }

    /**
     * @param HtmlElement $container
     * @param int         $index
     * @return HtmlElement
     */
    protected function appendSelector(HtmlElement $container, int $index)
    {
        $caption = t(($index + 1) .'.').' '.t('Baustoffkonfiguration');
        $isRequired = $index === 0 || $index === 1;

        $selectorId  = 'importAssistantMappingSelector_' . $index;
        $selectorDiv = $container->add(new HtmlTag('div', null, ['id' => $selectorId, 'class' => 'import-assistant-mapping-selector']));
        $selectorDiv->add(
            new ElcaHtmlFormElementLabel(
                $caption,
                $selector = new ElcaHtmlProcessConfigSelectorLink('processConfigId['.$index.']'),
                $isRequired
            )
        );

        $selector->setContext($this->context);
        $selector->setRelId($this->data->mappingId[$index] ?? null);
        $selector->setData($index);
        $selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_ALL);
        $selector->setProcessDbId($this->data->processDbId);

        $this->checkMappingChange($selector, $index);

        $container->add(new HtmlHiddenField('mappingId['. $index .']'));

        return $selectorDiv;
    }

    /**
     * Checks if an element needs marked as changed
     *
     * @param HtmlFormElement $formElement
     * @param int             $index
     */
    private function checkMappingChange(HtmlFormElement $formElement, int $index)
    {
        if ($this->changedMapping === $index) {
            $formElement->addClass('changed');
        }
    }
    // End checkElementChange

}
