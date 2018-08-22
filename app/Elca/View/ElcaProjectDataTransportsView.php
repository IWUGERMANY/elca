<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckbox;
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
use Beibob\HtmlTools\HtmlTextInput;
use DOMNode;
use Elca\Controller\ProjectDataCtrl;
use Elca\Db\ElcaCacheDataObjectSet;
use Elca\Db\ElcaProcessViewSet;
use Elca\Db\ElcaProjectTransport;
use Elca\Db\ElcaProjectTransportMean;
use Elca\Db\ElcaProjectTransportMeanSet;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;

/**
 * Builds the project data tansports view
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class ElcaProjectDataTransportsView extends HtmlView
{
    /**
     * Build modes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_TRANSPORT_MEANS = 'transport-means';

    /**
     * ProjectVariandtId
     */
    private $projectVariantId;

    /**
     * Data
     */
    private $Data;
    private $buildMode;
    private $changedElements;
    private $readOnly;

    //////////////////////////////////////////////////////////////////////////////////////

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
        $this->projectVariantId = $this->get('projectVariantId');
        $this->Data = $this->get('Data', (object)null);
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->changedElements = $this->get('changedElements', []);
        $this->readOnly = $this->get('readOnly');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'projectDataTransportsForm';
        $Form = new HtmlForm($formId, '/project-data/saveTransports/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setReadonly($this->readOnly);

        $Form->setDataObject($this->Data);

        if ($this->has('Validator')) {
            $Form->setValidator($Validator = $this->get('Validator'));
        }

        /**
         * Add hidden projectVariantId
         */
        $Form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));

        switch ($this->buildMode) {
            case self::BUILDMODE_TRANSPORT_MEANS:
                $transportId = $this->get('transportId', 'new');

                $this->appendTransportMeans($Form, $transportId);

                // append form to dummy container
                $DummyContainer = $this->appendChild($this->getDiv());
                $Form->appendTo($DummyContainer);

                // extract transport-means container and replace it with the dummy container
                $Content = $this->getElementById('transport-means-'. $transportId);
                $this->replaceChild($Content, $DummyContainer);
                break;

            case self::BUILDMODE_DEFAULT:
                /**
                 * Add Container
                 */
                $Container = $this->appendChild($this->getDiv(['id'    => 'content',
                                                                    'class' => 'project-transports']));


                $this->appendTransports($Form);
                $Form->appendTo($Container);

                $this->appendSummarySection($Container);
                break;
        }
    }
    // End afterRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the transports form
     *
     * @param  HtmlForm $Form
     */
    protected function appendTransports(HtmlForm $Form)
    {
        $ProcessDb = ElcaProjectVariant::findById($this->projectVariantId)->getProject()->getProcessDb();

        $Group = $Form->add(new HtmlFormGroup(''));

//        if ($ProcessDb->isEn15804Compliant()) {
//            $Group->add(new ElcaHtmlFormElementLabel(t('Ergebnisse in Ökobilanz mit einbeziehen?'), new HtmlCheckbox('includeInLca')));
//        }

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');
        $Row->add(new HtmlTag('h5', t('Baustoff im Projekt'), ['class' => 'hl-mat-process-config']));
        $Row->add(new HtmlTag('h5', t('Name'), ['class' => 'hl-name']));
        $Row->add(new HtmlTag('h5', t('Menge') . ' t', ['class' => 'hl-quantity']));
        $Row->add(new HtmlTag('h5', t('Verkehrsmittel'), ['class' => 'hl-process-config']));
        $Row->add(new HtmlTag('h5', t('Auslastung') . ' %', ['class' => 'hl-efficiency']));
        $Row->add(new HtmlTag('h5', t('Distanz km'), ['class' => 'hl-distance']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'transports']));
        $Ul = $Container->add(new HtmlTag('ul', null));

        foreach (ElcaProjectTransportSet::findByProjectVariantId($this->projectVariantId,
                                                                 ['id' => 'ASC']) as $Transport) {
            $Li = $Ul->add(new HtmlTag('li', null, ['id' => 'transport-'.$Transport->getId(), 'class' => 'transport']));
            $this->appendTransport($Li, $Transport);
        }

        if ($this->get('addNewTransport', false)) {
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'transport']));
            $this->appendTransport($Li);
            $Container->addClass('new-transport');
        }

        if (!$this->readOnly) {
            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('addTransport', t('Neuen Transport hinzufügen')));
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveTransports', t('Speichern'), true));
        }
    }
    // End appendBenchmarks


    /**
     * @param HtmlElement          $Container
     * @param ElcaProjectTransport $Transport
     */
    private function appendTransport(HtmlElement $Container, ElcaProjectTransport $Transport = null)
    {
        $key = !is_null($Transport) ? $Transport->getId() : 'new';

        if(is_numeric($key)) {
            $Container->add(new HtmlLink('>', '#'))
                  ->addClass('function-link toggle-link no-xhr no-history');
            $Container->add(new HtmlHiddenField('toggle['.$key.']'))->setAttribute('class', 'toggle');
        }

        // select process config
        $Container->add(new ElcaHtmlFormElementLabel('',
                                                     $Select = new HtmlSelectbox('matProcessConfigId[' . $key . ']')));
        $Select->add(new HtmlSelectOption('-- ' . t('Eigenangabe') . ' --', ''));
        foreach (ElcaCacheDataObjectSet::findProcessConfigMassByProjectVariantId($this->projectVariantId,
                                                                                 ['mass' => 'DESC']) as $DO) {
            $tMass = ElcaNumberFormat::toString($DO->mass / 1000, 1);

            $Select->add($Opt = new HtmlSelectOption($DO->name . ' [' . $tMass . ' t]',
                                                     $DO->process_config_id));
            $Opt->setAttribute('data-quantity', $tMass);
        }

        $Container->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('name[' . $key . ']')));
        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('quantity[' . $key . ']')));

        $this->appendTransportMeans($Container, $key);

        if ($Transport)
            $this->appendTransportResults($Container, $key);
    }
    // End appendTransport


    /**
     * @param HtmlElement $Container
     * @param mixed $transportKey
     */
    private function appendTransportMeans(HtmlElement $Container, $transportKey)
    {
        $Ul = $Container->add(new HtmlTag('ul', null, ['id' => 'transport-means-' . $transportKey, 'class' => 'transport-means']));

        if ($this->get('relId')) {
            $Ul->setAttribute('data-rel-id', $this->get('relId'));
        }
        if (is_numeric($transportKey)) {

            foreach (ElcaProjectTransportMeanSet::findByProjectTransportId($transportKey, ['id' => 'ASC']) as $TransportMean) {
                $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'transport-mean']));
                $this->appendTransportMean($Li, $transportKey, $TransportMean);
            }

            if ($this->get('addNewTransportMean', false) && $this->get('transportId') == $transportKey) {
                $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'transport-mean new']));
                $this->appendTransportMean($Li, $transportKey);
            }
            else {
                if (!$this->readOnly) {

                    /**
                     * Add link only on last row
                     */
                    $Li->add(
                        $Link = new HtmlLink(
                            '+ '.t('Verkehrsmittel'),
                            Url::factory('/project-data/addTransportMean/', ['id' => $transportKey])
                        )
                    )
                       ->addClass('function-link add-link');
                    $Link->setAttribute('title', t('Verkehrsmittel hinzufügen'));
                }
            }

            $Li->addClass('last');
        }
        else {
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'transport-mean new last']));
            $this->appendTransportMean($Li, $transportKey);
        }
    }
    // End appendTransportMeans


    /**
     * @param HtmlElement              $Container
     * @param                          $transportKey
     * @param ElcaProjectTransportMean $TransportMean
     */
    private function appendTransportMean(HtmlElement $Container, $transportKey, ElcaProjectTransportMean $TransportMean = null)
    {
        $key = !is_null($TransportMean) ? $transportKey .'-'. $TransportMean->getId() : $transportKey .'-new';

        $Container->setAttribute('id', 'transport-mean-'. $key);

        /**
         * ProcessConfig selector
         */
        $Selector = $Container->add(new ElcaHtmlProcessConfigSelectorLink('processConfigId['.$key.']'));
        $Selector->addClass('process-config-selector');
        $Selector->setRelId($key);
        $Selector->setProjectVariantId($this->projectVariantId);
        $Selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_TRANSPORTS);
        $Selector->setContext(ProjectDataCtrl::CONTEXT);

        $this->checkElementChange($Selector);

        if (isset($this->Data->processConfigId[$key]) && $this->Data->processConfigId[$key] ||
            (($Validator = $this->get('Validator')) && $Validator->getValue('processConfigId['. $key.']'))) {

            $NumberConverter = new ElcaNumberFormatConverter(0, true);
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('distance[' . $key . ']')));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('efficiency[' . $key . ']', null, false, $NumberConverter)));

            if(is_numeric($transportKey) && $TransportMean instanceof ElcaProjectTransportMean) {
                if (!$this->readOnly) {
                    $Container->add(
                        $Link = new HtmlLink(
                            t('Löschen'),
                            Url::factory('/project-data/deleteTransportMean/', ['id' => $TransportMean->getId()])
                        )
                    )
                              ->addClass('function-link delete-link');
                    $Link->setAttribute('title', t('Löschen'));
                }
            }
            else {
                $Container->add($Link = new HtmlLink(t('Abbrechen'), Url::factory('/project-data/transports/')))
                          ->addClass('function-link cancel-link');
                $Link->setAttribute('title', t('Abbrechen'));
            }
        } else {
            $Container->add($Link = new HtmlLink(t('Abbrechen'), Url::factory('/project-data/transports/')))
                      ->addClass('function-link cancel-link');
            $Link->setAttribute('title', t('Abbrechen'));

        }
    }
    // End appendTransportMean


    /**
     * Appends the result table
     *
     * @param HtmlElement $Container
     * @param int         $transportId
     * @return void -
     */
    private function appendTransportResults(HtmlElement $Container, $transportId)
    {
        if(!$transportId)
            return;

        /**
         * Build indicator result table
         */
        $IndicatorSet = ElcaProcessViewSet::findResultsByTransportId($transportId);

        if($IndicatorSet->count())
        {
            $doList = [];
            $indicators = [];
            foreach($IndicatorSet as $Indicator)
            {
                $key = $Indicator->transport_id . $Indicator->life_cycle_ident . $Indicator->process_id;
                if(!isset($doList[$key]))
                {
                    $DO = $doList[$key] = new \stdClass();
                    $DO->nameOrig = $Indicator->name_orig? $Indicator->name_orig : t('Gesamt');
                }
                else
                    $DO = $doList[$key];

                $indicatorId = $Indicator->indicator_ident;
                if (!isset($DO->$indicatorId))
                    $DO->$indicatorId = 0;

                $DO->$indicatorId += $Indicator->value;
                $indicators[$indicatorId] = $Indicator->indicator_name;
            }

            $Table = $Container->add(new HtmlTable('process-databases'));
            $Table->addColumn('nameOrig', t('Verkehrsmittel'));

            foreach($indicators as $indicatorId => $indicatorName)
                $Table->addColumn($indicatorId, t($indicatorName));

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();

            foreach($indicators as $indicatorId => $indicatorName)
                $Row->getColumn($indicatorId)->setOutputElement(new ElcaHtmlNumericText($indicatorId, 4, false, '?', null, null, true));

            $Body->setDataSet($doList);
        }
    }
    // End appendResults

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the summary section
     *
     * @param DOMElement|DOMNode $Container
     */
    protected function appendSummarySection(DOMNode $Container)
    {
        $Div = $Container->appendChild($this->getDiv(['id' => 'section-summary', 'class' => 'summary-section']));
        $FieldsetDiv = $Div->appendChild($this->getDiv(['class' => 'clear fieldset']));
        $Legend = $FieldsetDiv->appendChild($this->getDiv(['class' => 'legend']));
        $Legend->appendChild($this->getText(t('Gesamteinsatz') . ' '));

        $IndicatorSet = ElcaReportSet::findTransportEffects($this->projectVariantId);

        if($IndicatorSet->count())
        {
            $doList = [];
            $indicators = [];
            $SummaryDO = (object)null;
            $SummaryDO->processConfigName = t('Gesamt');

            foreach($IndicatorSet as $Indicator)
            {
                $key = $Indicator->process_config_name;

                if(!isset($doList[$key]))
                {
                    $DO = $doList[$key] = new \stdClass();
                    $DO->processConfigName = $key;
                }
                else
                    $DO = $doList[$key];

                $indicatorId = $Indicator->indicator_ident;

                if (!isset($DO->$indicatorId))
                    $DO->$indicatorId = 0;
                $DO->$indicatorId += $Indicator->indicator_value;
                $indicators[$indicatorId] = $Indicator->indicator_name;

                if (!isset($SummaryDO->$indicatorId))
                    $SummaryDO->$indicatorId = 0;

                $SummaryDO->$indicatorId += $Indicator->indicator_value;
            }

            $doList[] = $SummaryDO;

            $Table = new HtmlTable('transport-summary');
            $Table->addColumn('processConfigName', t('Verkehrsmittel'));

            foreach($indicators as $indicatorId => $indicatorName)
                $Table->addColumn($indicatorId, t($indicatorName));

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();

            foreach($indicators as $indicatorId => $indicatorName)
                $Row->getColumn($indicatorId)->setOutputElement(new ElcaHtmlNumericText($indicatorId, 4, false, '?', null, null, true));

            $Body->setDataSet($doList);
            $Table->appendTo($FieldsetDiv);
        }
    }
    // End appendSummarySection

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks if an element needs marked as changed
     */
    private function checkElementChange(HtmlFormElement $Element)
    {
        if(isset($this->changedElements[$Element->getName()]))
            $Element->addClass('changed');
    }
    // End checkElementChange
}
// End ElcaProjectDataTransportsView
