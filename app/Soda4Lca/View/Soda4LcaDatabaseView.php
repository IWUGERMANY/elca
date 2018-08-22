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
namespace Soda4Lca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlDataElement;
use Beibob\HtmlTools\HtmlDataElementDiv;
use Beibob\HtmlTools\HtmlDomElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectSet;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaTranslatorConverter;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaProcess;
use Soda4Lca\Db\Soda4LcaProcessSet;
use Soda4Lca\Db\Soda4LcaReportSet;
use Soda4Lca\Model\Import\Soda4LcaParser;
use Soda4Lca\View\helpers\Soda4LcaHtmlReportProcessLink;
use Soda4Lca\View\helpers\Soda4LcaHtmlReportStatus;
use Soda4Lca\View\helpers\Soda4LcaReportConverter;

/**
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soda4LcaDatabaseView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_NEW = 'new';
    const BUILDMODE_REPORT = 'report';

    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * Properties
     */
    private $Data;

    /**
     * Filter
     */
    private $FilterDO;

    /**
     * Filter status map
     *
     * @translate array Soda4Lca\View\Soda4LcaDatabaseView::$processStatusMap
     */
    public static $processStatusMap = [
        Soda4LcaProcess::STATUS_OK => 'Importiert',
        Soda4LcaProcess::STATUS_SKIPPED => 'Nicht importiert',
        Soda4LcaProcess::STATUS_UNASSIGNED => 'Nicht zugeordnet'
    ];

    /**
     * Import status map
     * @translate array Soda4Lca\View\Soda4LcaDatabaseView::$importStatusMap
     */
    public static $importStatusMap = [
        Soda4LcaImport::STATUS_INIT => 'In Vorbereitung',
        Soda4LcaImport::STATUS_IMPORT => 'Import läuft',
        Soda4LcaImport::STATUS_DONE => 'Import abgeschlossen'
    ];


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->Data = $this->get('Data', new \stdClass);
        $this->FilterDO = $this->get('FilterDO', new \stdClass);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        switch($this->buildMode)
        {
            case self::BUILDMODE_DEFAULT:
            case self::BUILDMODE_NEW:
                $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'soda4lca-database']));

                if($this->appendForm($Container))
                {
                    if(isset($this->Data->id) && $this->Data->id)
                    {
                        if($this->Data->status == Soda4LcaImport::STATUS_DONE)
                        {
                            if(Soda4LcaProcessSet::dbCount(['import_id' => $this->Data->id, 'status' => Soda4LcaProcess::STATUS_SKIPPED]))
                                $this->appendImport($Container, true);

                            $this->appendProcesses($Container);
                        }
                        else
                            $this->appendImport($Container);
                    }
                }
                break;

            case self::BUILDMODE_REPORT:
                if($this->Data->id)
                    $this->appendProcesses($this);
                break;
        }
    }
    // End beforeRender


    /**
     * Appends the form
     *
     * @param  DOMElement $Container
     * @return bool
     */
    protected function appendForm(DOMElement $Container)
    {
        $Form = new HtmlForm('soda4lcaDatabaseForm', '/soda4Lca/databases/save/');
        $Form->addClass('clearfix highlight-changes');
        $Form->setDataObject($this->Data);

        if($this->has('Validator'))
        {
            $Form->setRequest(FrontController::getInstance()->getRequest());
            $Form->setValidator($this->get('Validator'));
        }

        if(isset($this->Data->id))
            $Form->add(new HtmlHiddenField('importId', $this->Data->id));

        $readOnly = isset($this->Data->status) && $this->Data->status == Soda4LcaImport::STATUS_IMPORT;

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('data');

        if($this->buildMode == self::BUILDMODE_NEW)
        {
            $processDbs = ElcaProcessDbSet::find()->getArrayBy('id', 'uuid');

            $Soda4LcaParser = Soda4LcaParser::getInstance();
            $dataStocks = $Soda4LcaParser->getDataStocks();

            foreach($processDbs as $uuid => $processDbId)
                unset($dataStocks[$uuid]);

            if(count($dataStocks))
            {
                $Select = $Group->add(new ElcaHtmlFormElementLabel(t('Datenbasis'), new HtmlSelectbox('uuid')));
                foreach($dataStocks as $DataStockDO)
                {
                    $dbName = $DataStockDO->name? $DataStockDO->name : $DataStockDO->shortName;
                    $dbName .= ' ('. $DataStockDO->totalSize .' ' . t('Einträge') . ')';

                    $Select->add(new HtmlSelectOption($dbName, $DataStockDO->uuid));
                }
            }
            else
            {
                $Container->appendChild($this->getP(t('Alle angebotenen Datenbanken sind bereits importiert.'), ['class' => 'notice']));
                return false;
            }
        }
        else
        {
            $Group->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true, '', $readOnly));
            $Group->add(new ElcaHtmlFormElementLabel(t('Version'), new HtmlTextInput('version'), true, '', $readOnly));

            if($this->Data->status == Soda4LcaImport::STATUS_DONE)
                $Group->add(new ElcaHtmlFormElementLabel(t('Zur Verwendung freigegeben'), new HtmlCheckbox('isActive', null, '', $readOnly)));
        }

        /**
         * Submit button
         */
        $ButtonGroup = $Group->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', $this->buildMode == self::BUILDMODE_DEFAULT? t('Speichern') : t('Erstellen'), true));


        if($this->buildMode == self::BUILDMODE_DEFAULT)
        {
            $Group = $Form->add(new HtmlFormGroup(''));
            $Group->addClass('info');
            $TextInput = $Group->add(new ElcaHtmlFormElementLabel(t('Datenbasis'), new HtmlTextInput('sourceUriShort', null, true)));
            $TextInput->setAttribute('title', $this->Data->sourceUri);
            $Group->add(new ElcaHtmlFormElementLabel(t('DataStock'), new HtmlTextInput('dataStock', null, true)));

            $Group->add(new ElcaHtmlFormElementLabel(t('Status'), new HtmlTextInput('status', t(Soda4LcaDatabaseView::$importStatusMap[$this->Data->status]), true)));
            $Group->add(new ElcaHtmlFormElementLabel(t('Importiert am'), new HtmlTextInput('dateOfImport', null, true)));
            $Group->add(new ElcaHtmlFormElementLabel(t('Datensätze insgesamt'), new HtmlTextInput('numberOfProcesses', null, true)));
            $Group->add(new ElcaHtmlFormElementLabel(t('Importierte Datensätze'), new HtmlTextInput('numberOfImportedProcesses', null, true)));

            $Div = new HtmlTag('div');
            $Div->addChild(new HtmlTextInput('numberOfProjects', null, true));

            if ((int)$this->Data->numberOfProjects > 0) {
                $Import = Soda4LcaImport::findById($this->Data->id);

                $Wrapper = $Div->addChild(new HtmlTag('div', null, ['class' => 'projects-using-this-db']));
                $Wrapper->add(new HtmlTag('h4', t('Mit dieser Datenbank verknüpfte Projekte')));
                $Ol = $Wrapper->addChild(new HtmlTag('ol'));

                /** @var ElcaProject $Project */
                foreach (ElcaProjectSet::find(['process_db_id' => $Import->getProcessDbId()], ['name' => 'ASC']) as $Project) {
                    $Li = $Ol->add(new HtmlTag('li'));
                    $Li->add(new HtmlTag('a', $Project->getName(), ['href' => '/elca/projects/'. $Project->getId(). '/', 'class' => 'no-xhr']));
                }
            }
            $Group->add(new ElcaHtmlFormElementLabel(t('Anzahl Projekte'), $Div, false, null, t('Projekte, die diese Baustoffdatenbank verwenden')));

        }
        $Form->appendTo($Container);

        return true;
    }
    // End appendForm



    /**
     * Appends the form
     *
     * @param  DOMElement $Container
     */
    protected function appendImport(DOMNode $Container, $retryMode = false)
    {
        $ImportContainer = $Container->appendChild($this->getDiv(['id' => 'import']));

        $Form = new HtmlForm('soda4lcaDatabaseForm', '/soda4Lca/databases/import/');
        $Form->addClass('clearfix');
        $Form->add(new HtmlHiddenField('importId', $this->Data->id));

        $ButtonGroup = $Form->add(new HtmlFormGroup(t('Datenimport')));
        //$ButtonGroup->addClass('buttons');

        if($retryMode)
            $ButtonGroup->add(new ElcaHtmlSubmitButton('retrySkipped', t('Nicht importierte erneut importieren')));
        else
            $ButtonGroup->add(new ElcaHtmlSubmitButton('import', t('Prozesse importieren')));

        $Form->appendTo($ImportContainer);
    }
    // End appendImport


    /**
     * Appends the form
     *
     * @param  DOMElement $Container
     */
    protected function appendProcesses(DOMNode $Container)
    {
        if(!$this->Data->id)
            return;

        $ProcessesContainer = $Container->appendChild($this->getDiv(['id' => 'processes']));
        $ProcessesContainer->appendChild($this->getA(['id' => 'exportLink',
                                                      'href' => '/soda4Lca/exports/processes/?importId='. $this->Data->id,
                                                      'class' => 'no-xhr'], t('Herunterladen')));

        $url = Url::factory('/soda4Lca/databases/import/', ['importId' => $this->Data->id, 'checkVersions' => true]);
        $ProcessesContainer->appendChild($this->getA(['id' => 'checkVersions',
                                                      'href' => (string) $url,
                                                      ], t('Nach neuen Versionen suchen')));

        if (ElcaAccess::getInstance()->hasAdminPrivileges()) {
            $url = Url::factory('/soda4Lca/databases/updateEpdType/', ['importId' => $this->Data->id]);
            $ProcessesContainer->appendChild(
                $this->getA(
                    [
                        'id'   => 'updateEPDType',
                        'href' => (string)$url,
                    ],
                    'EPD SubTyp aktualisieren'
                )
            );
        }

        if (ElcaAccess::getInstance()->hasAdminPrivileges()) {
            $url = Url::factory('/soda4Lca/databases/updateGeographicalRepresentativeness/', ['importId' => $this->Data->id]);
            $ProcessesContainer->appendChild(
                $this->getA(
                    [
                        'id'   => 'updateGeographicalRepresentativeness',
                        'href' => (string)$url,
                    ],
                    'Geographische Repräsentativität aktualisieren'
                )
            );
        }

        $Form = new HtmlForm('soda4lcaReportFilter', '/soda4Lca/databases/filter/');
        $Form->addClass('clearfix');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->FilterDO);
        $Form->add(new HtmlHiddenField('importId', $this->Data->id));

        $Filter = $Form->add(new HtmlFormGroup(t('Importprotokoll')));
        $Filter->addClass('filter');

        $Select = $Filter->add(new ElcaHtmlFormElementLabel(t('Statusfilter'), new HtmlSelectbox('status')));
        $Select->add(new HtmlSelectOption(t('Alle Datensätze'), ''));
        foreach(self::$processStatusMap as $status => $statusText)
            $Select->add(new HtmlSelectOption(t($statusText), $status));

        $Form->appendTo($ProcessesContainer);

        $conditions = [];
        if(isset($this->FilterDO->status) && $this->FilterDO->status)
            $conditions['status'] = $this->FilterDO->status;

        $ProcessSet = Soda4LcaReportSet::findImportedProcesses($this->Data->id, $conditions);

        $Table = new HtmlTable('soda4lca-process-data');
        $Table->addColumn('status', t('Status'))->addClass('status');
        $Table->addColumn('name', t('Name'))->addClass('name');
        $Table->addColumn('version', t('eLCA Datensatz'))->addClass('version');

        if (Soda4LcaProcessSet::dbCountUpdateables($this->Data->id) > 0)
        {
            $Table->addColumn('latest_version', t('Neuester Datensatz'))->addClass('latest-version');
            $Table->addClass('has-latest-version-column');
        }

        $Table->addColumn('modules', t('EPD Module'))->addClass('modules');
        $Table->addColumn('epd_types', t('EPD Subtype'))->addClass('epdSubType');
        $Table->addColumn('details', t('Information'))->addClass('details');
        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Converter = new Soda4LcaReportConverter();

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->addAttrFormatter($Converter);
        $Row->getColumn('status')->setOutputElement(new Soda4LcaHtmlReportStatus('status'));
        $Row->getColumn('name')->setOutputElement(new Soda4LcaHtmlReportProcessLink('name'));

        $Body->setDataSet($ProcessSet);
        $Table->appendTo($ProcessesContainer);
    }
    // End appendForm
}
// End Soda4LcaDatabaseView
