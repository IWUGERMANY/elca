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

namespace Elca\View\Import\Ifc;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTag;
use Elca\Controller\ElementImageCtrl;
use Elca\Controller\ProjectIfcCtrl;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Elca;
use Elca\Model\Import\Ifc\ImportElement;
use Elca\Model\Import\Ifc\Project;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlTemplateElementSelectorLink;
use Ramsey\Uuid\Uuid;


/**
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ProjectImportPreviewView extends HtmlView
{
    /**
     * @var Project|null
     */
    private $project;

    /**
     * @var \stdClass
     */
    private $data;

    /**
     * Inits the view
     *
     * @param array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->readOnly = $this->get('readOnly');

        $this->project = $this->get('project');
        $this->data    = $this->get('data');
    }

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $container = $this->appendChild(
            $this->getDiv(['id' => 'content', 'class' => 'project project-csv-import preview'])
        );

        $form = new HtmlForm('projectImportPreviewForm', '/project-ifc/preview/');
        $form->addClass('clearfix highlight-changes');

        $form->setDataObject($this->data);

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
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

        $benchmarkVersion = ElcaBenchmarkVersion::findById($this->project->benchmarkVersionId());
        $group->add(
            new ElcaHtmlFormElementLabel(
                t('Baustoffdatenbank'),
                new HtmlStaticText($benchmarkVersion->getProcessDb()->getName())
            )
        );


        if (null !== $this->project && $this->project->wasModifiedDuringImport()) {
            $group = $form->add(new HtmlFormGroup('Hinweis'));
            $group->addClass('project-info-right');

            $p = $group->add(new HtmlTag('p', t('Die Daten wurden beim Einlesen modifiziert.'), ['class' => 'notice']));
            $p->add(new HtmlTag('br'));
            $p->add(new HtmlStaticText(t('Bitte überprüfen Sie die angepassten Felder!')));
        }

        $this->appendButtons($form);

        $this->appendImportElements($form);

        $this->appendButtons($form);
    }

    private function appendImportElements(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(''));

        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');

        $row->add(new HtmlTag('h5', t('DIN 276'), ['class' => 'hl-din-code']));
        $row->add(new HtmlTag('h5', t('Name'), ['class' => 'hl-name']));
        $row->add(new HtmlTag('h5', t('Menge'), ['class' => 'hl-quantity']));
        $row->add(new HtmlTag('h5', t('Bauteilvorlage'), ['class' => 'hl-tpl-element']));

        $ol = $group->add(new HtmlTag('ol', null, ['class' => 'elements']));

        $counter = 0;
        foreach ($this->project->importElements() as $element) {
            $li = $ol->add(new HtmlTag('li', null, ['class' => 'element']));

            $this->appendImportElement($li, $element);
            $counter++;
        }

        if (0 === $counter) {
            $ol->add(new HtmlTag('li', t('Es wurden keine Bauteile für diese Kostengruppe übergeben.'), []));
        }
    }

    private function appendImportElement(HtmlElement $li, ImportElement $element)
    {
        $key              = $element->uuid();
        $levelTwoDinCodes = $this->findLevelTwoDinCodes();

        $li->add(
            new ElcaHtmlFormElementLabel(
                '',
                $dinCodeSelect = new HtmlSelectbox('dinCode2[' . $key . ']')
            )
        )->addClass('column din-code');
        $dinCodeSelect->setAttribute('onchange', '$(this.form).submit();');
        $dinCodeSelect->add(new HtmlSelectOption(t('-- Bitte wählen --'), null));

        foreach ($levelTwoDinCodes as $dinCode => $caption) {
            $dinCodeSelect->add(new HtmlSelectOption($caption, $dinCode));
        }

        if (isset($this->data->dinCode2[$key]) && !empty($this->data->dinCode2[$key])) {
            $elementTypeLevel2  = ElcaElementType::findByIdent($this->data->dinCode2[$key]);
            $levelThreeDinCodes = $this->findLevelThreeDinCodes($elementTypeLevel2);
            $li->add(
                new ElcaHtmlFormElementLabel(
                    '',
                    $dinCode3Select = new HtmlSelectbox('dinCode3[' . $key . ']')
                )
            )->addClass('column din-code3');
            $dinCode3Select->setAttribute('onchange', '$(this.form).submit();');
            $dinCode3Select->add(new HtmlSelectOption(t('-- Komponente wählen --'), null));
            foreach ($levelThreeDinCodes as $dinCode => $caption) {
                $dinCode3Select->add(new HtmlSelectOption($caption, $dinCode));
            }
        }

        if ($element->isModified() && $element->hasModificationReason(ImportElement::DINCODE_MISMATCH)) {
            $dinCodeSelect->addClass('changed');

            if (isset($this->data->dinCode3[$key]) && !empty($this->data->dinCode3[$key])) {
                $dinCode3Select->addClass('changed');
            }

            $li->add(new HtmlTag('span', t('Die Kostengruppe wurde angepasst.'),
                ['class' => 'modified-din-code']));
        }

        $li->add(
            new HtmlTag(
                'span',
                $element->name(),
                [
                    'title' => $element->name(),
                ]
            )
        )->addClass('column name');

        $li->add(
            new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('quantity[' . $key . ']'))
        )->addClass('column quantity');

        $li->add(
            new ElcaHtmlFormElementLabel('', $unitSelect = new HtmlSelectbox('unit[' . $key . ']'))
        )->addClass('column unit');
        foreach ([Elca::UNIT_M2, Elca::UNIT_STK, Elca::UNIT_M] as $unit) {
            $unitSelect->add(new HtmlSelectOption(t(Elca::$units[$unit]), $unit));
        }

        if ($element->isModified() && $element->hasModificationReason(ImportElement::UNIT_MISMATCH)) {
            $unitSelect->addClass('changed');
            $li->add(new HtmlTag('span', t('Die Einheit wurde angepasst. Stimmt die Menge?'),
                ['class' => 'modified-unit']));
        }

        $elementType = ElcaElementType::findByIdent($element->dinCode());

        if ($elementType->isInitialized()) {
            $tplElementDiv = $li->add(new HtmlTag('div'));

            $tplElementImgDiv = $li->add(new HtmlTag('div'));
            $tplElementImgDiv->addClass('column tpl-element-image');

            if (null !== $element->tplElementUuid() && $elementType->getPrefHasElementImage()) {
                $this->addElementImage($tplElementImgDiv, $this->data->tplElementId[$key]);
            }

            $tplElementDiv->add(
                $selector = new ElcaHtmlTemplateElementSelectorLink(
                    'tplElementId[' . $key . ']'
                )
            )->addClass('column tpl-element');

            $selector->addClass('element-selector');
            $selector->setUrl(FrontController::getInstance()->getUrlTo(ProjectIfcCtrl::class, 'selectElement'));
            $selector->setElementTypeNodeId($elementType->getNodeId());
            $selector->setRelId($key);
        }

        if (!$element->isValid()) {
            $li->addClass('warning');
        }
    }

    /**
     * @param HtmlForm $form
     */
    private function appendButtons(HtmlElement $form)
    {
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen'), false));
        $buttonGroup->add(new ElcaHtmlSubmitButton('createProject', t('Projekt erstellen')));
    }

    private function addElementImage(HtmlElement $container, $tplElementId)
    {
        $elementImageUrl = FrontController::getInstance()->getUrlTo(
            ElementImageCtrl::class,
            null,
            ['elementId' => $tplElementId, 'legend' => '0']
        );

        $container->add(
            new HtmlTag(
                'div', null, [
                    'class'             => 'element-image',
                    'data-element-id'   => $tplElementId,
                    'data-url'          => $elementImageUrl,
                    'data-container-id' => Uuid::uuid4(),
                ]
            )
        );

        $container->addClass('has-element-image');
    }

    private function findLevelTwoDinCodes(): array
    {
        $levelOneTypes = [
            ElcaElementType::findByIdent(300),
            ElcaElementType::findByIdent(400),
        ];

        $levelTwoDinCodes = [];
        foreach ($levelOneTypes as $levelOneType) {
            $levelTwoDinCodes += array_map(
                [$this, 'dinCodeCaption'],
                ElcaElementTypeSet::findByParentType($levelOneType)->getArrayCopy('dinCode')
            );
        }

        return $levelTwoDinCodes;
    }

    private function findLevelThreeDinCodes(ElcaElementType $elementType)
    {
        return array_map(
            [$this, 'dinCodeCaption'],
            ElcaElementTypeSet::findByParentType($elementType)->getArrayCopy('dinCode')
        );
    }

    private function dinCodeCaption(ElcaElementType $elementType)
    {
        return $elementType->getDinCode() . ' - ' . $elementType->getName();
    }
}
