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
namespace Elca\View\Admin\Benchmark;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Model\Benchmark\BenchmarkSystemModel;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * ElcaAdminBenchmarkSystemView
 *
 * @package eLCA
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ElcaAdminBenchmarkSystemView extends HtmlView
{
    /**
     * @var object $Data
     */
    private $Data;

    /**
     * @var array|BenchmarkSystemModel[]
     */
    private $modelClasses;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->Data = $this->get('Data', new \stdClass());
        $this->modelClasses = $this->get('modelClasses', []);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        /**
         * Add Container
         */
        $Container = $this->appendChild($this->getDiv(['id' => 'content',
                                                            'class' => 'elca-admin-benchmark-system']));

        $formId = 'adminBenchmarkSystemForm';
        $form = new HtmlForm($formId, '/elca/admin/benchmarks/saveBenchmarkSystem/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');

        if($this->Data)
        {
            $form->setDataObject($this->Data);

            if(isset($this->Data->systemId))
                $form->add(new HtmlHiddenField('benchmarkSystemId', $this->Data->systemId));
        }

        if($this->has('Validator'))
        {
            $form->setValidator($this->get('Validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('benchmark-system-group column');
        $group->add(new ElcaHtmlFormElementLabel(t('Name des Benchmarksystems'), new HtmlTextInput('name'), true));
        $modelSelect = $group->add(new ElcaHtmlFormElementLabel(t('Basiert auf'), new HtmlSelectbox('modelClass'), true));

        foreach ($this->modelClasses as $modelClass) {
            $modelSelect->add(new HtmlSelectOption($modelClass->name(), get_class($modelClass)));
        }

        $group->add(new ElcaHtmlFormElementLabel(t('Beschreibung'), new HtmlTextArea('description')));

        if(isset($this->Data->versionName))
            $group->add(new ElcaHtmlFormElementLabel(t('System zur Verwendung freigegeben'), new HtmlCheckbox('isActive')));

        $this->appendBenchmarkSystemModelInformation($form);

        $this->appendVersions($form);

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');

        if(!isset($this->Data->systemId))
            $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));

        $buttonGroup->add(new ElcaHtmlSubmitButton('save', isset($this->Data->systemId)? t('Speichern') : t('Erstellen')));

        $form->appendTo($Container);
    }

    protected function appendBenchmarkSystemModelInformation(HtmlElement $htmlElement): void
    {
        $group = $htmlElement->add(new HtmlFormGroup('Informationen'));
        $group->addClass('benchmark-system-information ');

        $group->add(new HtmlTag('p', t('Parameter für das Benchmarksystem BNB:')));
        $ul = $group->add(new HtmlTag('ul', null, ['class' => 'list']));
        $ul->add(new HtmlTag('li', t('Punktwert anzeigen: ja')));
        $ul->add(new HtmlTag('li', t('Wohnfläche abfragen: nein')));
        $ul->add(new HtmlTag('li', t('Trinkwasser-Rechenhilfe: BNB 1.2.3')));

        $group->add(new HtmlTag('p', t('Parameter für das Benchmarksystem NaWoh:')));
        $ul = $group->add(new HtmlTag('ul', null, ['class' => 'list']));
        $ul->add(new HtmlTag('li', t('Punktwert anzeigen: nein')));
        $ul->add(new HtmlTag('li', t('Wohnfläche abfragen: ja')));
        $ul->add(new HtmlTag('li', t('Trinkwasser-Rechenhilfe: NaWoh 3.1')));
    }
    // End beforeRender

    /**
     * Appends the benchmark versions form
     *
     * @param HtmlForm $Form
     * @return void
     */
    private function appendVersions(HtmlForm $Form)
    {
        if(!$this->Data || !isset($this->Data->systemId))
            return;

        $Group = $Form->add(new HtmlFormGroup(t('Versionen')));
        $Group->addClass('benchmark-version-group clear');

        $Link = $Group->add(new HtmlLink('+ '. t('Neue Version erstellen'), '/elca/admin/benchmarks/createVersion/?id='. $this->Data->systemId));
        $Link->addClass('function-link add-version');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');
        $Row->add(new HtmlTag('h5', t('Versionsname'), ['class' => 'hl-version-name']));
        $Row->add(new HtmlTag('h5', t('Baustoff-Datenbank'), ['class' => 'hl-process-db']));
        $Row->add(new HtmlTag('h5', t('Aktionen'), ['class' => 'hl-actions']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'benchmarkVersions']));
        $Ul = $Container->add(new HtmlTag('ul', null));

        $ProcessDbs = ElcaProcessDbSet::find(['is_active' => true], ['name' => 'ASC']);

        $versionCount = count($this->Data->versionName);

        /** @var object $VersionDO */
        foreach($this->Data->versionName as $key => $name)
        {
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'benchmark-version']));
            $TextInput = $Li->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('versionName['.$key.']')));

            $Li->add(new ElcaHtmlFormElementLabel('', $Select = new HtmlSelectbox('versionProcessDbId['.$key.']')));
            $Select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', null));

            /** @var ElcaProcessDb $ProcessDb */
            foreach($ProcessDbs as $ProcessDb)
                $Select->add(new HtmlSelectOption($ProcessDb->getName(), $ProcessDb->getId()));

            /**
             * Edit, copy and delete links
             */
            if($this->Data->versionProcessDbId[$key]) {
                $Li->add(new HtmlLink(t('Bearbeiten'), Url::factory('/elca/admin/benchmarks/editVersion/', ['id' => $key])))
                    ->addClass('page function-link edit-link');

                $Li->add(new HtmlLink(t('Kopieren'), Url::factory('/elca/admin/benchmarks/copyVersion/', ['id' => $key])))
                    ->addClass('function-link copy-link');

                $Li->add(new HtmlLink($this->Data->versionIsActive[$key]? t('sperren') : t('freigeben'), Url::factory('/elca/admin/benchmarks/activateVersion/', ['id' => $key])))
                    ->addClass('function-link activate-link');
            }

            if($versionCount > 1) {
                $Li->add(new HtmlLink(t('Löschen'), Url::factory('/elca/admin/benchmarks/deleteVersion/', ['id' => $key])))
                    ->addClass('function-link delete-link');
            }
        }
    }
    // End appendVersions
}
// End ElcaAdminBenchmarkSystemView