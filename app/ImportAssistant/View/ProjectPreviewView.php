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

namespace ImportAssistant\View;

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaProcessDb;
use ImportAssistant\Model\Import\Component;
use ImportAssistant\Model\Import\Element;
use ImportAssistant\Model\Import\FinalEnergyDemand;
use ImportAssistant\Model\Import\FinalEnergySupply;
use ImportAssistant\Model\Import\LayerComponent;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\Import\ProjectVariant;
use ImportAssistant\Model\Import\RefModel;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the import screen
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ProjectPreviewView extends HtmlView
{
    /**
     * @var Project|null
     */
    private $project;

    private $context;

    private $data;

    private $activeTab;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('project_import', 'importAssistant');

        $this->context   = $this->get('context');
        $this->project   = $this->get('project');
        $this->data      = $this->get('data');
        $this->activeTab = $this->get('activeTab');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('content');

        $form = new HtmlForm('projectImportPreviewForm', '/importAssistant/projects/preview/');
        $form->addClass('clearfix');

        $form->setDataObject($this->data);

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
        }

        $this->appendProject($form);
        $form->appendTo($container);
    }

    /**
     * @param \DOMElement $container
     */
    private function appendProject(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup('Projektdaten'));
        $group->addClass('project-info');
        $group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlStaticText($this->project->name())));
        $group->add(new ElcaHtmlFormElementLabel(t('Projekt-Nr'), new HtmlStaticText($this->project->projectNr())));
        $group->add(new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), new HtmlStaticText(ElcaProcessDb::findById($this->project->processDbId())->getName())));

        $this->appendButtons($form);

        if ($this->project->description()) {
            $group->add(
                new ElcaHtmlFormElementLabel(t('Beschreibung'), new HtmlStaticText($this->project->description()))
            )->addClass('clear');
        }

        $variant = current($this->project->variants());

        $this->appendNavigation($form);

        $this->appendElements($form, $variant, '3');
        $this->appendElements($form, $variant, '4');
        $this->appendFinalEnergy($form, $variant);

        $this->appendButtons($form);
    }

    private function appendElements(HtmlForm $form, ProjectVariant $variant, $dinCodeLevel1)
    {
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('tab-item');

        $tabId = 'din-code-' . $dinCodeLevel1;

        if ($this->activeTab === $tabId) {
            $group->addClass('active');
        }

        $group->setAttribute('id', $tabId);

        $ul = $group->add(new HtmlTag('ul', null, ['class' => 'elements']));

        $counter = 0;
        foreach ($variant->elements() as $element) {
            if ((string)$element->dinCode()[0] === $dinCodeLevel1) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'element']));

                $this->appendElement($li, $element, $tabId);
                $counter++;
            }
        }

        if (0 === $counter) {
            $ul->add(new HtmlTag('li', t('Es wurden keine Bauteile für diese Kostengruppe übergeben.'), []));
        }
    }

    private function appendElement(HtmlElement $li, Element $element, $tabId)
    {
        $dinCodeCaption = function (ElcaElementType $elementType) {
            return $elementType->getDinCode() . ' - ' . $elementType->getName();
        };

        $elementType      = ElcaElementType::findByIdent($this->data->elementDinCodes[$element->uuid()]);
        $dinCodes         = array_map(
            $dinCodeCaption,
            ElcaElementTypeSet::findByParentType($elementType)->getArrayCopy('dinCode')
        );
        $levelTwoDinCodes = array_map(
            $dinCodeCaption,
            ElcaElementTypeSet::findByParentType($elementType->getParent())->getArrayCopy('dinCode')
        );

        $infoDiv = $li->add(new HtmlTag('div', null, ['class' => 'element-info clearfix']));

        $infoDiv->add(new HtmlTag('h4', $element->name(), ['class' => 'element-name']));

        $infoDiv->add(
            new ElcaHtmlFormElementLabel(
                t('DIN 276'),
                $select = new HtmlSelectbox('elementDinCodes[' . $element->uuid() . ']')
            )
        );
        $select->setAttribute('onchange', '$(this.form).submit();');
        foreach ($levelTwoDinCodes as $dinCode => $caption) {
            $select->add(new HtmlSelectOption($caption, $dinCode));
        }

        $infoDiv->add(
            new ElcaHtmlFormElementLabel(
                t('Menge'),
                new HtmlStaticText(ElcaNumberFormat::toString($element->quantity())),
                null, $element->refUnit()
            )
        );

        $this->addComponents(
            $li,
            $element,
            $element->layerComponents(),
            $dinCodes,
            true,
            $tabId
        );

        $this->addComponents(
            $li,
            $element,
            $element->singleComponents(),
            $dinCodes,
            false,
            $tabId
        );
    }

    /**
     * @param HtmlElement $container
     * @param Element     $element
     * @param Component[] $components
     * @param             $dinCodes
     * @param bool        $isLayer
     */
    private function addComponents(
        HtmlElement $container,
        Element $element,
        array $components,
        $dinCodes,
        bool $isLayer,
        $tabId
    ) {
        if (!$components) {
            return;
        }

        $row = $container->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');

        $row->add(new HtmlTag('h5', t('Baustoff'), ['class' => 'hl-material']));

        if ($isLayer) {
            $row->add(new HtmlTag('h5', t('L × B × H'), ['class' => 'hl-quantity']));
            $row->add(new HtmlTag('h5', t('Anteil'), ['class' => 'hl-ratio']));
        } else {
            $row->add(new HtmlTag('h5', t('Menge'), ['class' => 'hl-quantity']));
        }

        $row->add(new HtmlTag('h5', t('DIN 276'), ['class' => 'hl-din-code']));
        $row->add(new HtmlTag('h5', t('eLCA Baustoff'), ['class' => 'hl-process-config']));

        $ol = $container->add(
            new HtmlTag(
                'ol',
                null,
                ['class' => $isLayer ? 'components layer-components' : 'components single-components']
            )
        );

        $siblings = [];
        /**
         * @var Component[] $components
         */
        foreach ($components as $index => $component) {
            $key = $component->uuid();

            $isSibling = false;
            if ($sibling = $component instanceof LayerComponent && $component->isSibling() ? $component->isSiblingOf()
                : null
            ) {
                if (isset($siblings[$sibling->uuid()])) {
                    $li        = $siblings[$sibling->uuid()];
                    $isSibling = true;
                } else {
                    $li = $siblings[$key] = $ol->add(
                        new HtmlTag('li', null, ['class' => 'component-group siblings'])
                    );
                }
            } else {
                $li = $ol->add(new HtmlTag('li', null, ['class' => 'component-group']));
            }

            if (!$component->materialMapping()->hasMapping()) {
                $li->addClass('warning');
            }

            $componentContainer = $li->add(new HtmlTag('div', null, ['class' => 'component clearfix']));
            $componentContainer->add(new HtmlTag('span', null, ['class' => 'clearfix']));

            $componentContainer->add(
                new HtmlTag(
                    'span',
                    $component->materialMapping()->materialName(),
                    [
                        'title' => $component->materialMapping()->materialName()
                    ]
                )
            )->addClass('column material');


            if ($isLayer) {
                $componentContainer->add(
                    new HtmlTag(
                        'span',
                        ElcaNumberFormat::toString($component->layerLength()) . ' × ' .
                        ElcaNumberFormat::toString($component->layerWidth()) . ' × ' .
                        ElcaNumberFormat::toString($component->layerSize()) . ' ' . ElcaNumberFormat::formatUnit(
                            Elca::UNIT_M3
                        )
                    )
                )->addClass('column quantity');

                $componentContainer->add(
                    new HtmlTag(
                        'span',
                        ElcaNumberFormat::toString($component->layerAreaRatio(), 0, true) . ' %'
                    )
                )->addClass('column ratio');
            } else {
                $componentContainer->add(
                    new HtmlTag(
                        'span',
                        ElcaNumberFormat::formatQuantity($component->quantity(), $component->refUnit())
                    )
                )->addClass('column quantity');
            }

            if (false === $isSibling) {
                $componentContainer->add(
                    new ElcaHtmlFormElementLabel(
                        '',
                        $select = new HtmlSelectbox('dinCodes[' . $key . ']')
                    )
                )->addClass('column din-code');

                $select->setAttribute('onchange', '$(this.form).submit();');
                foreach ($dinCodes as $dinCode => $caption) {
                    $select->add(new HtmlSelectOption($caption, $dinCode));
                }
            }

            $componentContainer->add(
                $selector = new ElcaHtmlProcessConfigSelectorLink(
                    'processConfigIds[' . $key . ']'
                )
            )->addClass('column elca-process-config-selector');

            $selector->setContext($this->context);
            $selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_DEFAULT);
            $selector->setProcessDbId($this->data->processDbId);
            $selector->setRelId($key);
            $selector->setData($tabId);
            $selector->setEnableReplaceAll();
            $selector->setHeadline(t('Baustoff ":name:" ersetzen', null, [':name:' => $component->materialMapping()->materialName()]));

            if ($isLayer) {
                $selector->setInUnit(Elca::UNIT_M3);
            }
            else {
                $selector->setInUnit(
                    $component->refUnit()
                );
            }
        }
    }

    private function appendFinalEnergy(HtmlForm $form, ProjectVariant $variant)
    {
        $tabId = 'final-energy';

        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('final-energy tab-item');
        $group->setAttribute('id', $tabId);

        if ($this->activeTab === $tabId) {
            $group->addClass('active');
        }

        $infoDiv = $group->add(new HtmlTag('div', null, ['class' => 'finalenergy-info clearfix']));

        $infoDiv->add(
            new ElcaHtmlFormElementLabel(
                'NGF EnEV',
                new HtmlStaticText(ElcaNumberFormat::toString($variant->ngfEnEv())),
                null, 'm2'

            )
        );

        $infoDiv->add(
            new ElcaHtmlFormElementLabel(
                'EnEV Version',
                new HtmlStaticText($variant->enEvVersion())
            )
        );

        if (count($variant->finalEnergyDemands())) {
            $group->add(new HtmlTag('h4', t('Endenergiebedarf')));

            $row = $group->add(new HtmlTag('div'));
            $row->addClass('hl-row clearfix');

            $row->add(new HtmlTag('h5', t('Energieträger') . ' [kWh / m²a]', ['class' => 'hl-material']));

            $row->add(new HtmlTag('h5', t('Heizung'), ['class' => 'hl-heating']));
            $row->add(new HtmlTag('h5', t('Wasser'), ['class' => 'hl-water']));
            $row->add(new HtmlTag('h5', t('Beleuchtung'), ['class' => 'hl-lighting']));
            $row->add(new HtmlTag('h5', t('Lüftung'), ['class' => 'hl-ventilation']));
            $row->add(new HtmlTag('h5', t('Kühlung'), ['class' => 'hl-cooling']));
            $row->add(new HtmlTag('h5', t('eLCA Baustoff'), ['class' => 'hl-process-config']));


            $ul = $group->add(new HtmlTag('ul', null, ['class' => 'final-energy-demands']));

            foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'final-energy-demand']));

                $this->appendFinalEnergyDemand($li, $finalEnergyDemand, $tabId);
            }
        }

        if (count($variant->refModels())) {
            $group->add(new HtmlTag('h4', t('Energiebedarf des Referenzgebäudes')));

            $row = $group->add(new HtmlTag('div'));
            $row->addClass('hl-row clearfix');

            $row->add(new HtmlTag('h5', t('Energieträger') . ' [kWh / m²a]', ['class' => 'hl-material']));

            $row->add(new HtmlTag('h5', t('Heizung'), ['class' => 'hl-heating']));
            $row->add(new HtmlTag('h5', t('Wasser'), ['class' => 'hl-water']));
            $row->add(new HtmlTag('h5', t('Beleuchtung'), ['class' => 'hl-lighting']));
            $row->add(new HtmlTag('h5', t('Lüftung'), ['class' => 'hl-ventilation']));
            $row->add(new HtmlTag('h5', t('Kühlung'), ['class' => 'hl-cooling']));

            $ul = $group->add(new HtmlTag('ul', null, ['class' => 'final-energy-demands']));

            foreach ($variant->refModels() as $refModel) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'final-energy-demand']));

                $this->appendRefModel($li, $refModel, $tabId);
            }
        }

        if (count($variant->finalEnergySupplies())) {
            $group->add(new HtmlTag('h4', t('Endenergiebereitstellung')));

            $row = $group->add(new HtmlTag('div'));
            $row->addClass('hl-row clearfix');

            $row->add(new HtmlTag('h5', t('Energieträger') . ' [kWh / a]', ['class' => 'hl-material']));
            $row->add(new HtmlTag('h5', t('Gesamt'), ['class' => 'hl-quantity']));
            $row->add(new HtmlTag('h5', t('In EnEV verrechnet'), ['class' => 'hl-enev-ratio']));
            $row->add(new HtmlTag('h5', t('eLCA Baustoff'), ['class' => 'hl-process-config']));

            $ul = $group->add(new HtmlTag('ul', null, ['class' => 'final-energy-supplies']));

            foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'final-energy-supply']));

                $this->appendFinalEnergySupply($li, $finalEnergySupply, $tabId);
            }
        }


    }

    private function appendFinalEnergyDemand(HtmlElement $container, FinalEnergyDemand $finalEnergyDemand, $tabId)
    {
        $key = $finalEnergyDemand->uuid();

        $div = $container->add(new HtmlTag('div', null, ['class' => 'component-group']));

        $componentContainer = $div->add(new HtmlTag('div', null, ['class' => 'component clearfix']));
        $componentContainer->add(new HtmlTag('span', null, ['class' => 'clearfix']));

        $componentContainer->add(
            new HtmlTag(
                'span',
                $finalEnergyDemand->materialMapping()->materialName()
            )
        )->addClass('column material');

        foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
            $componentContainer->add(
                new HtmlTag(
                    'span',
                    ElcaNumberFormat::toString($finalEnergyDemand->$property(), 3)
                )
            )->addClass('column ' . $property);
        }

        $componentContainer->add(
            $selector = new ElcaHtmlProcessConfigSelectorLink(
                'processConfigIds[' . $key . ']'
            )
        )->addClass('column elca-process-config-selector');

        $selector->setContext($this->context);
        $selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_OPERATION);
        $selector->setProcessDbId($this->data->processDbId);
        $selector->setRelId($key);
        $selector->setData($tabId);

        if (!$finalEnergyDemand->materialMapping()->hasMapping()) {
            $container->addClass('warning');
        }

    }

    private function appendFinalEnergySupply(HtmlElement $container, FinalEnergySupply $finalEnergySupply, $tabId)
    {
        $key = $finalEnergySupply->uuid();

        $div = $container->add(new HtmlTag('div', null, ['class' => 'component-group']));

        $componentContainer = $div->add(new HtmlTag('div', null, ['class' => 'component clearfix']));
        $componentContainer->add(new HtmlTag('span', null, ['class' => 'clearfix']));

        $componentContainer->add(
            new HtmlTag(
                'span',
                $finalEnergySupply->materialMapping()->materialName()
            )
        )->addClass('column material');


        $componentContainer->add(
            new HtmlTag(
                'span',
                ElcaNumberFormat::toString($finalEnergySupply->quantity(), 3)
            )
        )->addClass('column quantity');


        $componentContainer->add(
            new HtmlTag(
                'span',
                ElcaNumberFormat::formatQuantity($finalEnergySupply->enEvRatio(), '%', 0, true)
            )
        )->addClass('column enev-ratio');

        $componentContainer->add(
            $selector = new ElcaHtmlProcessConfigSelectorLink(
                'processConfigIds[' . $key . ']'
            )
        )->addClass('column elca-process-config-selector');

        $selector->setContext($this->context);
        $selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY);
        $selector->setProcessDbId($this->data->processDbId);
        $selector->setRelId($key);
        $selector->setData($tabId);

        if (!$finalEnergySupply->materialMapping()->hasMapping()) {
            $container->addClass('warning');
        }
    }

    private function appendRefModel(HtmlElement $container, RefModel $refModel, $tabId)
    {
        $key = $refModel->ident();

        $div = $container->add(new HtmlTag('div', null, ['class' => 'component-group']));

        $componentContainer = $div->add(new HtmlTag('div', null, ['class' => 'component clearfix']));
        $componentContainer->add(new HtmlTag('span', null, ['class' => 'clearfix']));

        $componentContainer->add(
            new HtmlTag(
                'span',
                $refModel->ident()
            )
        )->addClass('column material');

        foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
            $componentContainer->add(
                new HtmlTag(
                    'span',
                    ElcaNumberFormat::toString($refModel->$property(), 3)
                )
            )->addClass('column ' . $property);
        }
    }

    /**
     * @param HtmlForm $form
     */
    private function appendNavigation(HtmlForm $form)
    {
        $form->add(new HtmlHiddenField('activeTab', $this->activeTab));
        $navigation = $form->add(new HtmlTag('ul', null, ['class' => 'elca-tabs navigation clearfix']));

        if (!$this->activeTab) {
            $this->activeTab = 'din-code-3';
        }
        foreach (
            [
                t('Bauteile') . ' ' . t('KG 300') => 'din-code-3',
                t('Bauteile') . ' ' . t('KG 400') => 'din-code-4',
                t('Endenergie')                   => 'final-energy',
            ] as $caption => $tabId
        ) {
            $tab = $navigation->add(new HtmlTag('li', null, ['class' => 'elca-tab']));
            $a   = $tab->add(
                new HtmlTag('a', $caption, ['href' => '#' . $tabId, 'class' => 'no-xhr'])
            );

            if ($tabId === $this->activeTab) {
                $tab->addClass('active');
            }
        }
    }

    /**
     * @param HtmlForm $form
     */
    private function appendButtons(HtmlElement $form)
    {
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('createProject', t('Projekt erstellen')));
        $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen'), false));
    }

}
// End ElcaProjectImportView
