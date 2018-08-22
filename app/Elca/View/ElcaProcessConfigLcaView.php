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
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProcessViewSet;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessSelectorLink;
use Elca\View\helpers\ElcaHtmlProcessWithDatasheetLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;
use Elca\View\helpers\ElcaProcessesConverter;

/**
 * Builds the lca tab content for process configs
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigLcaView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_PHASE = 'phase';
    const BUILDMODE_LIFE_CYCLE = 'lc';

    /**
     * Process db versions which are not din 18504
     */
    private static $notDin15804 = ['2009' => 2009, '2011' => 2011];

    /**
     * Read only
     */
    private $readOnly = false;

    /**
     * Current buildmode
     */
    private $buildMode;

    /**
     * Data object
     */
    private $Data;

    /**
     * Current lifeCycle ident
     */
    private $lifeCycleIdent;

    /**
     * ProcessCategoryNodeId
     */
    private $processCategoryNodeId;

    /**
     * Changed elements
     */
    private $changedElements = [];

    private $phase;

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->Data      = $this->get('Data');

        // only for build mode LIFE_CYCLE
        $this->lifeCycleIdent = $this->get('lifeCycleIdent');

        // only for build mode PHASE and when to add a new assignment
        $this->phase         = $this->get('phase');
        $this->addAssignment = $this->get('addAssignment');

        // get category
        $this->processCategoryNodeId = $this->get(
            'processCategoryNodeId',
            ElcaProcessConfig::findById($this->Data->processConfigId)->getProcessCategoryNodeId()
        );

        /**
         * Changed elements
         */
        if ($this->has('changedElements')) {
            $this->changedElements = $this->get('changedElements');
        }

        /**
         * Read only mode
         */
        if ($this->get('readOnly', false)) {
            $this->readOnly = true;
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        if (!$this->Data->processConfigId) {
            return;
        }

        $Form = new HtmlForm('processConfigLcaForm', '/processes/saveLcaConfig/');
        $Form->setAttribute('id', 'processLcaConfig');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->Data);

        if ($this->readOnly) {
            $Form->setReadonly();
        }

        $Form->addClass('highlight-changes');
        $Form->add(new HtmlHiddenField('processConfigId', $this->Data->processConfigId));
        $Form->add(new HtmlHiddenField('processDbId', $this->Data->processDbId));

        switch ($this->buildMode) {
            case self::BUILDMODE_LIFE_CYCLE:
                $LifeCycle = ElcaLifeCycle::findByIdent($this->lifeCycleIdent);
                $phase     = $LifeCycle->isInitialized() ? $LifeCycle->getPhase() : $this->phase;
                $this->appendProcess($Form, $phase, $this->Data->key);

                // append form to dummy container
                $DummyContainer = $this->appendChild($this->getDiv());
                $Form->appendTo($DummyContainer);

                // extract conversion element and replace it with the dummy container
                $Content = $this->getElementById('group_'.$this->Data->key);
                $this->replaceChild($Content, $DummyContainer);
                break;

            case self::BUILDMODE_PHASE:
                $this->appendPhase($Form, $this->phase, ElcaProcessDb::findById($this->Data->processDbId)->isEn15804Compliant());

                // append form to dummy container
                $DummyContainer = $this->appendChild($this->getDiv());
                $Form->appendTo($DummyContainer);

                // extract conversion element and replace it with the dummy container
                $Content = $this->getElementById('group_'.$this->phase);
                $this->replaceChild($Content, $DummyContainer);
                break;

            default:
            case self::BUILDMODE_DEFAULT:
                $this->appendDefault($Form);

                $isEn15804Compliant = ElcaProcessDb::findById($this->Data->processDbId)->isEn15804Compliant();
                $phases           = [ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_OP, ElcaLifeCycle::PHASE_EOL, ElcaLifeCycle::PHASE_REC];

                foreach ($phases as $phase) {
                    $this->appendPhase($Form, $phase, $isEn15804Compliant);
                }

                $this->appendButtons($Form);

                $Content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-lca']));
                $Form->appendTo($Content);
                break;
        }
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the default form content
     *
     * @param  HtmlForm $Form
     * @return -
     */
    protected function appendDefault(HtmlForm $Form)
    {
        if ($this->readOnly) {
            return;
        }

        /**
         * ProcessDb chooser
         */
        $Group = $Form->add(new HtmlFormGroup(''));

        $Select = $Group->add(
            new ElcaHtmlFormElementLabel(t('Zuordnung auf Grundlage von '), new HtmlSelectbox('processDbId'))
        );
        $Select->addClass('db-select mark-no-change');
        // add url for change event to reload the view
        $Select->setAttribute(
            'data-url',
            Url::factory('/processes/lca/', ['processConfigId' => $this->Data->processConfigId])
        );

        foreach (ElcaProcessDbSet::find(null, ['version' => 'ASC']) as $ProcessDb) {
            $Select->add(new HtmlSelectOption($ProcessDb->getName(), $ProcessDb->getId()));
        }
    }
    // End appendDefault

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the buttons
     *
     * @param  HtmlForm $Form
     * @return -
     */
    protected function appendButtons(HtmlForm $Form)
    {
        /**
         * Buttons
         */
        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix buttons');

        if (!$Form->isReadonly()) {
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveLca', t('Speichern'), true));
        }
    }
    // End appendButtons

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the content for one life cycle phase
     *
     * @param  HtmlForm $Form
     * @param  string   $phase
     * @return -
     */
    private function appendPhase(HtmlForm $Form, $phase, $isEn15804Compliant)
    {
        /**
         * One group per phase
         */
        $Group = $Form->add(new HtmlFormGroup(t(Elca::$lcPhases[$phase])));
        $Group->setAttribute('id', 'group_'.$phase);
        $Group->addClass('process-group');

        /**
         * Add link
         */
        if (!$this->readOnly && !($phase === ElcaLifeCycle::PHASE_REC && !$isEn15804Compliant)) {
            $Link = $Group->add(
                new HtmlLink(
                    '+ '.t('Zuordnung hinzufügen'), Url::factory(
                    '/processes/addLca/',
                    [
                        'processDbId' => $this->Data->processDbId,
                        'processConfigId' => $this->Data->processConfigId,
                        'phase' => $phase,
                    ]
                )
                )
            );
            $Link->addClass('function-link add-assignment');
            $Link->setAttribute('title', t('Eine neue Zuordnung hinzufügen'));
        }
        $count = 0;
        foreach ($this->Data->processId as $key => $processId) {
            $Process = ElcaProcess::findById($processId, true);

            if ($Process->getLifeCyclePhase() !== $phase) {
                continue;
            }

            $this->appendProcess($Group, $phase, $key);
            $count++;
        }

        if (!$count || $this->addAssignment) {
            $this->appendProcess($Group, $phase, $phase.'_new');
        }
    }
    // End appendPhase

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends a process
     *
     * @param  HtmlElement $Element
     * @param  ElcaProcess $Process
     * @return -
     */
    private function appendProcess(HtmlElement $Element, $phase, $key)
    {
        $lifeCycle = $this->Data->lifeCycle[$key] ?? null;
        $isNew     = $key === $phase.'_new';

        $isEn15804Compliant = ElcaProcessDb::findById($this->Data->processDbId)->isEn15804Compliant();
        $ProcessSet         = ElcaProcessViewSet::findWithProcessDbByProcessConfigIdAndLifeCycle(
            $this->Data->processConfigId,
            $phase,
            false === $isEn15804Compliant ? null : $lifeCycle
        );

        /**
         * One group per process
         */
        $Group = $Element->add(new HtmlFormGroup(''));
        $Group->setAttribute('id', 'group_'.$key);
        $Group->addClass('process-assignment '.$phase);

        /**
         * Toggler
         */
        $Link = $Group->add(new HtmlTag('div', null, ['class' => 'toggle-link']));
        if ($ProcessSet->count()) {
            $Link->setAttribute('data-lc', $isNew ? $phase : $lifeCycle);
            $Link->addClass('open');
        }

        /**
         * End of life ratio
         */
        if ($phase == ElcaLifeCycle::PHASE_EOL || $phase == ElcaLifeCycle::PHASE_REC) {
            $Converter = new ElcaNumberFormatConverter(0, true);
            $Group->add(
                new ElcaHtmlFormElementLabel(
                    '',
                    new ElcaHtmlNumericInput('ratio['.$key.']', $isNew ? 1 : null, false, $Converter)
                )
            );
            $Group->add(new HtmlTag('span', '%', ['class' => 'unit']));
        }

        /**
         * Life cycle select
         */
        $Select = $Group->add(new ElcaHtmlFormElementLabel('', new HtmlSelectbox('lifeCycle['.$key.']')));
        $Select->addClass('lc-select');
        // add url for change event to rebuild this process group
        $Select->setAttribute(
            'data-url',
            Url::factory(
                '/processes/saveLcaConfig/',
                [
                    'plcaId' => $key,
                    'processConfigId' => $this->Data->processConfigId,
                    'processDbId' => $this->Data->processDbId,
                    'lcPhase' => $phase
                ]
            )
        );

        /**
         * Add options. Set lifeCycle immediatly if only one lifeCycle is available
         */
        $LifeCycleSet = ElcaLifeCycleSet::findByProcessDbIdAndPhase(
            $this->Data->processDbId,
            $phase,
            ['name' => 'ASC']
        );
        if (!$LifeCycleSet->count() || $LifeCycleSet->count() > 1) {
            $Select->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
        } else {
            $lifeCycle = $LifeCycleSet[0]->getIdent();
        }

        foreach ($LifeCycleSet as $LifeCycle) {
            $Select->add(new HtmlSelectOption(t($LifeCycle->getDescription()), $LifeCycle->getIdent()));
        }

        $this->checkElementChange($Select);

        /**
         * Process selector
         */
        if ($lifeCycle) {
            $Selector = $Group->add(
                new ElcaHtmlFormElementLabel('', new ElcaHtmlProcessSelectorLink('processId['.$key.']'))
            );
            $Selector->setProcessDbId($this->Data->processDbId);
            $Selector->setProcessConfigId($this->Data->processConfigId);
            $Selector->setProcessCategoryNodeId($this->processCategoryNodeId);
            $Selector->setLifeCycleIdent($lifeCycle);
            $Selector->setProcessLifeCycleAssignmentId($key);

            if ($isNew && in_array(
                $lifeCycle,
                [ElcaLifeCycle::PHASE_EOL, ElcaLifeCycle::IDENT_C3, ElcaLifeCycle::IDENT_C4, ElcaLifeCycle::IDENT_D],
                true
            )) {
                $Selector->setProcessCategoryNodeId(ElcaProcessCategory::findByRefNum('100.01')->getNodeId());
            }


            $this->checkElementChange($Selector);

            /**
             * Remove link
             */
            if (!$this->readOnly && is_numeric($key) && $this->Data->processId[$key]) {
                $Group->add(new HtmlLink(t('Löschen'), Url::factory('/processes/deleteLca/', ['plcaId' => $key])))
                      ->addClass('function-link delete-link');
            }
        }

        /**
         * ProcessDbs and indicators
         */
        if ($ProcessSet->count()) {
            $Table = $Group->add(new HtmlTable('process-databases'));
            $Table->addColumn('processDb', t('Datenbank'));
            $Table->addColumn('nameOrig', t('Prozess'));
            $Table->addColumn('epdType', t('EPD Subtype'));
            $Table->addColumn('geographicalRepresentativeness', t('Geographie'));
            $Table->addColumn('lifeCycleName', t('Modul'));
            $Table->addColumn('ratio', t('Anteil'));
            $Table->addColumn('refValue', t('Bezugsgröße'));
            $Table->addColumn('uuid', 'UUID');

            $Head    = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Converter = new ElcaProcessesConverter();

            $Body = $Table->createTableBody();
            $Row  = $Body->addTableRow();
            $Row->getColumn('refValue')->setOutputElement(new HtmlText('refValue', $Converter));
            $Row->getColumn('nameOrig')->setOutputElement(new ElcaHtmlProcessWithDatasheetLink('nameOrig'));
            $Row->getColumn('epdType')->setOutputElement(new HtmlText('epdType', $Converter));
            $Row->getColumn('geographicalRepresentativeness')->setOutputElement(new HtmlText('geographicalRepresentativeness', $Converter));
            $Body->setDataSet($ProcessSet);
        }
    }
    // End appendProcess

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks if an element needs marked as changed
     */
    private function checkElementChange(HtmlFormElement $Element)
    {
        if (isset($this->changedElements[$Element->getName()])) {
            $Element->addClass('changed');
        }
    }
    // End checkElementChange

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaView
