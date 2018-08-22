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

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElement;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use Elca\Controller\ProjectDataCtrl;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaCacheFinalEnergyDemand;
use Elca\Db\ElcaCacheFinalEnergyRefModel;
use Elca\Db\ElcaCacheFinalEnergySupply;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessViewSet;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergyRefModel;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectIndicatorBenchmark;
use Elca\Db\ElcaProjectIndicatorBenchmarkSet;
use Elca\Db\ElcaProjectVariant;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\View\helpers\ElcaHtmlBenchmarkProjection;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlToggleLink;
use Elca\View\helpers\ElcaNumberFormatConverter;

/**
 * Builds the project data en ev view
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 *
 * $Id $
 */
class ElcaProjectDataEnEvView extends HtmlView
{
    /**
     * ProjectVariandtId
     */
    private $projectVariantId;

    /**
     * Data
     */
    private $Data;

    /**
     * Add a new project data
     */
    private $addNewProjectFinalEnergyDemand;
    private $addNewProjectFinalEnergySupply;

    /**
     * nfg
     */
    private $ngf;
    private $enEvVersion;

    /**
     * Changed elements
     */
    private $changedElements = [];

    private $readOnly;

    /**
     * @var array $refProcessConfigIdents
     * @translate array Elca\View\ElcaProjectDataEnEvView::$refProcessConfigIdents
     */
    public static $refProcessConfigIdents = [
        ElcaBenchmarkRefProcessConfig::IDENT_HEATING        => 'Wärme',
        ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY    => 'Strom',
        ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY => 'Prozessenergie'
    ];

    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->projectVariantId = $this->get('projectVariantId');
        $this->ngf = $this->get('ngf');
        $this->enEvVersion = $this->get('enEvVersion');

        $this->Data = $this->get('Data');
        $this->addNewProjectFinalEnergyDemand = $this->get('addNewProjectFinalEnergyDemand', false);
        $this->addNewProjectFinalEnergySupply = $this->get('addNewProjectFinalEnergySupply', false);

        /**
         * Changed elements
         */
        if ($this->has('changedElements'))
            $this->changedElements = $this->get('changedElements');

        $this->readOnly = $this->get('readOnly');
    }
    // End init


    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id'    => 'content',
                                                       'class' => 'project-final-energy']));

        $ProjectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $Project = $ProjectVariant->getProject();

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));
        $Dl->appendChild($this->getDt([], t('Bilanzierungszeitraum') . ': '));
        $Dl->appendChild($this->getDd([], $ProjectVariant->getProject()->getLifeTime() . ' ' . t('Jahre')));

        $Dl->appendChild($this->getDt([], t('Bezugsfläche (NGF)') . ': '));
        $Dl->appendChild($this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace(), 2) . ' m²'));

        /**
         * EnEV ngf and version (values send by javascript on submit of
         */
        $Row = new HtmlTag('div', null, ['id' => 'enEvNgfAndVersion', 'class' => 'clearfix']);
        $Row->add(new ElcaHtmlFormElementLabel(t('NGF-EnEV'), new ElcaHtmlNumericInput('ngf', $this->ngf, $this->readOnly), true, 'm²'));
        $Row->add(new ElcaHtmlFormElementLabel(t('EnEV-Version'), $EnEvVersion = new ElcaHtmlNumericInput('enEvVersion', $this->enEvVersion, $this->readOnly)));
        $EnEvVersion->setPrecision(0);
        $Row->appendTo($Container);

        /**
         * Append reference model form
         */
        $refModelProcessEnergyProcessConfigId = null;
        if ($Project->getBenchmarkVersion()->getUseReferenceModel()) {
            $Form = $this->getSectionForm('projectFinalEnergyRefModelForm', $this->Data->RefModel);
            $this->appendReferenceModelSection($Form);
            $Form->appendTo($Container);
            $refModelProcessEnergyProcessConfigId = $this->Data->RefModel->processConfigId[ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY];
        }

        /**
         * Append final energy demand form
         */
        $Form = $this->getSectionForm('projectFinalEnergyDemandForm', $this->Data->Demand);

        $Form->add(new HtmlHiddenField('addDemand', $this->addNewProjectFinalEnergyDemand));
        $this->appendEnergyDemandSection($Form, $refModelProcessEnergyProcessConfigId);
        $Form->appendTo($Container);

        /**
         * Append final energy supply form
         */
        if ($ProjectVariant->getPhase()->getStep() > 0 && ElcaAccess::getInstance()->canEditFinalEnergySupplies()) {

            $Form = $this->getSectionForm('projectFinalEnergySupplyForm', $this->Data->Supply);
            $Form->add(new HtmlHiddenField('addSupply', $this->addNewProjectFinalEnergySupply));
            $this->appendEnergySupplySection($Form);
            $Form->appendTo($Container);
        }

        /**
         * Append projections if in first phase and project has a specified a benchmark version
         */
        if ($ProjectVariant->getPhase()->getStep() == 0 && $ProjectVariant->getProject()->getBenchmarkVersionId())
            $this->appendProjections($Container);
    }
    // End afterRender


    /**
     * Appends the energy demand section
     *
     * @param  HtmlForm $Form
     */
    protected function appendReferenceModelSection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('clear');
        $Group->addClass('final-energy-section reference-model');

        /**
         * Headline
         */
        $Group->add(new HtmlTag('h5', t('Nutzung Referenzgebäude in') . ' kWh/m²a', ['class' => 'hl-final-energy-ref-model']));

        $this->appendHlRow($Group, ['hl-usage'       => t('Nutzung Energiebedarf Referenzgebäude'),
                                    'hl-heating'     => t('Heizung') . ' kWh / m²a',
                                    'hl-water'       => t('Warmwasser') . ' kWh / m²a',
                                    'hl-lighting'    => t('Beleuchtung') . ' kWh / m²a',
                                    'hl-ventilation' => t('Lüftung') . ' kWh / m²a',
                                    'hl-cooling'     => t('Kühlung') . ' kWh / m²a',
                                    'hl-overall'     => t('Gesamt') . ' kWh / m²a'
        ]);

        $Container = $Group->add(new HtmlTag('div', null, ['class' => 'clear']));

        $Ul = $Container->add(new HtmlTag('ul', null));
        foreach ([ElcaBenchmarkRefProcessConfig::IDENT_HEATING, ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY] as $ident) {
            $FinalEnergyRefModel = ElcaProjectFinalEnergyRefModel::findByProjectVariantIdAndIdent($this->projectVariantId, $ident);
            $Li = $Ul->add(new HtmlTag('li'));
            $this->appendReferenceModelRow($Li, $ident, $FinalEnergyRefModel);
        }

        $this->appendHlRow($Container, [
            'hl-heating'     => t('Heizung') . ' kWh / a',
            'hl-water'       => t('Warmwasser') . ' kWh / a',
            'hl-lighting'    => t('Beleuchtung') . ' kWh / a',
            'hl-ventilation' => t('Lüftung') . ' kWh / a',
            'hl-cooling'     => t('Kühlung') . ' kWh / a',
            'hl-overall'     => t('Gesamt') . ' kWh / m²a'
        ])->addClass('hl-process-energy');

        $Ul = $Container->add(new HtmlTag('ul', null));
        $finalEnergyRefModel = ElcaProjectFinalEnergyRefModel::findByProjectVariantIdAndIdent($this->projectVariantId, ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY);
        $li = $Ul->add(new HtmlTag('li'));
        $this->appendReferenceModelRow($li, ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY, $finalEnergyRefModel);

        if (!$this->readOnly) {
            $ButtonGroup = $Container->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveEnergyRefModel', t('Speichern'), true));
        }
    }
    // End appendReferenceModelSection


    /**
     * Appends a row
     *
     * @param HtmlElement                    $Li
     * @param                                $ident
     * @param ElcaProjectFinalEnergyRefModel $FinalEnergyRefModel
     */
    protected function appendReferenceModelRow(HtmlElement $Li, $ident, ElcaProjectFinalEnergyRefModel $FinalEnergyRefModel)
    {
        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'clearfix final-energy-row reference-model']));
        $Container->setAttribute('id', 'final-energy-ref-model-' . $ident);

        /**
         * reference model ident
         */
        $hasProcessConfigId = isset($this->Data->RefModel->processConfigId[$ident]);
        $Container->add(new HtmlTag('span', t(self::$refProcessConfigIdents[$ident]) . ($hasProcessConfigId ? '' : ' (' . t('nicht definiert') . ')'), ['class' => 'ref-model-ident']));
        $Container->add(new HtmlHiddenField('processConfigId[' . $ident . ']', $hasProcessConfigId ? $this->Data->RefModel->processConfigId[$ident] : null));

        /**
         * FinalEnergyDemand Data
         */
        $NumberConverter = new ElcaNumberFormatConverter();

        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('heating[' . $ident . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('water[' . $ident . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('lighting[' . $ident . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('ventilation[' . $ident . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('cooling[' . $ident . ']', null, !$hasProcessConfigId, $NumberConverter)));

        $OverallContainer = new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('overall[' . $ident . ']', null, false, ',', $NumberConverter));
        $OverallContainer->setAttribute('class', 'overall');
        $Container->add($OverallContainer);

        /**
         * Toggler
         */
        $Container->add(new HtmlHiddenField('toggle[' . $ident . ']'))->setAttribute('class', 'toggle');
        if ($hasProcessConfigId)
            $Container->add(new ElcaHtmlToggleLink())->addClass('no-xhr');

        /**
         * Add results table
         */
        $this->appendReferenceModelResults($Container, $FinalEnergyRefModel);
    }
    // End appendReferenceModelRow


    /**
     * Appends the energy demand section
     *
     * @param  HtmlForm $Form
     */
    protected function appendEnergyDemandSection(HtmlForm $Form, $refModelProcessEnergyProcessConfigId = null)
    {
        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('clear');
        $Group->addClass('final-energy-section energy-demand');

        /**
         * Headline
         */
        $Group->add(new HtmlTag('h5', t('Endenergiebedarf in') . ' kWh/m²a', ['class' => 'hl-final-energy-demand']));

        $this->appendHlRow($Group, ['hl-usage'       => t('Nutzung Energiebedarf'),
                                    'hl-heating'     => t('Heizung') . ' kWh / m²a',
                                    'hl-water'       => t('Warmwasser') . ' kWh / m²a',
                                    'hl-lighting'    => t('Beleuchtung') . ' kWh / m²a',
                                    'hl-ventilation' => t('Lüftung') . ' kWh / m²a',
                                    'hl-cooling'     => t('Kühlung') . ' kWh / m²a',
                                    'hl-overall'     => t('Gesamt') . ' kWh / m²a'
        ]);

        $Container = $Group->add(new HtmlTag('div', null, ['class' => 'clear']));

        $Ul = $Container->add(new HtmlTag('ul', null));

        $processEnergyDemand = null;

        if (isset($this->Data->Demand->processConfigId) &&
            is_array($this->Data->Demand->processConfigId) &&
            count($this->Data->Demand->processConfigId)
        ) {
            foreach ($this->Data->Demand->processConfigId as $key => $foo) {
                if ($key === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
                    $processEnergyDemand = ElcaProjectFinalEnergyDemand::findByProjectVariantIdAndIdent($this->projectVariantId, $key);
                    continue;
                }

                $FinalEnergyDemand = ElcaProjectFinalEnergyDemand::findById($key);
                $Li = $Ul->add(new HtmlTag('li'));

                $this->appendDemandRow($Li, $key, $FinalEnergyDemand);
            }
        }

        if ($this->addNewProjectFinalEnergyDemand) {
            $Li = $Ul->add(new HtmlTag('li'));
            $this->appendDemandRow($Li, 'newDemand');
        }

        if (!$this->readOnly) {
            $ButtonGroup = $Container->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('addEnergyDemand', t('Bedarf hinzufügen')))->addClass(
                'add-energy-carrier'
            );
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveEnergyDemand', t('Speichern'), true));
        }

        if ($refModelProcessEnergyProcessConfigId) {

            $this->appendHlRow($Group, ['hl-usage'       => t('Bedarf an Prozessenergie (bezogen auf NGF)'),
                                        'hl-heating'     => t('Heizung') . ' kWh / a',
                                        'hl-water'       => t('Warmwasser') . ' kWh / a',
                                        'hl-lighting'    => t('Beleuchtung') . ' kWh / a',
                                        'hl-ventilation' => t('Lüftung') . ' kWh / a',
                                        'hl-cooling'     => t('Kühlung') . ' kWh / a',
                                        'hl-overall'     => t('Gesamt') . ' kWh / m²a'
            ])->addClass('hl-process-energy');

            $Container = $Group->add(new HtmlTag('div', null, ['class' => 'clear']));

            $this->appendProcessEnergyDemand($Container, $refModelProcessEnergyProcessConfigId, $processEnergyDemand);

            if (!$this->readOnly) {
                $ButtonGroup = $Container->add(new HtmlFormGroup(''));
                $ButtonGroup->addClass('buttons');
                $ButtonGroup->add(new ElcaHtmlSubmitButton('saveEnergyDemand', t('Speichern'), true));
            }
        }
    }
    // End appendEnergyDemandSection


    /**
     * Appends a row
     *
     * @param HtmlElement                  $Li
     * @param                              $key
     * @param ElcaProjectFinalEnergyDemand $FinalEnergyDemand
     *
     * @internal param HtmlForm $Form
     */
    protected function appendDemandRow(HtmlElement $Li, $key, ElcaProjectFinalEnergyDemand $FinalEnergyDemand = null)
    {
        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'clearfix final-energy-row final-energy-demand']));
        $Container->setAttribute('id', 'final-energy-demand-' . $key);

        if (!is_numeric($key))
            $Container->addClass('new');

        /**
         * ProcessConfig selector
         *
         * @var ElcaHtmlProcessConfigSelectorLink $Selector
         */
        $Selector = $Container->add(new ElcaHtmlProcessConfigSelectorLink('processConfigId[' . $key . ']'));
        $Selector->addClass('process-config-selector');
        $Selector->setRelId($key);
        $Selector->setProjectVariantId($this->projectVariantId);
        $Selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_OPERATION);
        $Selector->setContext(ProjectDataCtrl::CONTEXT);

        $this->checkElementChange($Selector);

        $Request = FrontController::getInstance()->getRequest();

        if ((isset($this->Data->Demand->processConfigId[$key]) && $this->Data->Demand->processConfigId[$key]) || (isset($Request->processConfigId[$key]) && $Request->processConfigId[$key])) {
            $ProcessConfig = ElcaProcessConfig::findById(isset($Request->processConfigId[$key]) ? $Request->processConfigId[$key] : $this->Data->Demand->processConfigId[$key]);
            if ($ProcessConfig->isInitialized())
                $Selector->setProcessCategoryNodeId($ProcessConfig->getProcessCategoryNodeId());

            if ($FinalEnergyDemand !== null) {
                $CacheDemand = ElcaCacheFinalEnergyDemand::findByFinalEnergyDemandId($FinalEnergyDemand->getId());
                if ($CacheDemand->isInitialized()) {
                    if (!ElcaProcessViewSet::findResultsByCacheItemId($CacheDemand->getItemId(), 1)->count()) {
                        $Li->addClass('no-results');
                        $this->Data->toggle[$key] = true;
                    }
                } else {
                    $Li->addClass('no-results');
                    $this->Data->toggle[$key] = true;
                }
            }
            if ($ProcessConfig->isStale()) {
                $this->Data->toggle[$key] = true;
            }

            /**
             * FinalEnergyDemand Data
             */
            $NumberConverter = new ElcaNumberFormatConverter();

            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('heating[' . $key . ']', null, false, $NumberConverter)));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('water[' . $key . ']', null, false, $NumberConverter)));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('lighting[' . $key . ']', null, false, $NumberConverter)));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('ventilation[' . $key . ']', null, false, $NumberConverter)));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('cooling[' . $key . ']', null, false, $NumberConverter)));

            $OverallContainer = new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('overall[' . $key . ']'));
            $OverallContainer->setAttribute('class', 'overall');
            $Container->add($OverallContainer);
        }

        /**
         * Toggler
         */
        if ($key != 'newDemand') {
            $Container->add(new HtmlHiddenField(
                    'toggle[' . $key . ']',
                    isset($this->Data->toggle[$key]) ? $this->Data->toggle[$key] : null)
            )->setAttribute('class', 'toggle');
            $Container->add(new ElcaHtmlToggleLink())->addClass('no-xhr');
        }

        /**
         * Remove link
         */
        if (!$this->readOnly) {
            if (is_numeric($key)) {
                $Container->add(
                    new HtmlLink(t('Löschen'), Url::factory('/project-data/deleteFinalEnergyDemand/', ['id' => $key]))
                )
                          ->addClass('function-link delete-link');
            } else {
                $Container->add(new HtmlLink(t('Abbrechen'), Url::factory('/project-data/enEv/')))
                          ->addClass('function-link cancel-link');
            }
        }

        /**
         * Add results table
         */
        if (is_numeric($key)) {
            $this->appendDemandResults($Container, $FinalEnergyDemand);
        }
    }
    // End appendDemandRow

    /**
     * Appends a row
     *
     * @param HtmlElement                  $container
     * @param                              $refModelProcessEnergyProcessConfigId
     * @param ElcaProjectFinalEnergyDemand $finalEnergyDemand
     */
    protected function appendProcessEnergyDemand(HtmlElement $container, $refModelProcessEnergyProcessConfigId, $finalEnergyDemand = null)
    {
        $group = $container->add(new HtmlFormGroup(''));
        $group->addClass('clear');

        $wrapper = $group->add(new HtmlTag('div', null, ['class' => 'clear']));

        $ul = $wrapper->add(new HtmlTag('ul', null));
        $li = $ul->add(new HtmlTag('li'));

        $key = ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY;

        $demandContainer = $li->add(new HtmlTag('div', null, ['class' => 'clearfix final-energy-row final-energy-demand']));
        $demandContainer->setAttribute('id', 'final-energy-demand-' . $key);

        /**
         * reference model ident
         */
        $hasProcessConfigId = $refModelProcessEnergyProcessConfigId;
        $demandContainer->add(new HtmlTag('span', t('Prozessenergie'), ['class' => 'process-energy-demand']));
        $demandContainer->add(new HtmlHiddenField('processConfigId[' . $key . ']', $refModelProcessEnergyProcessConfigId));

        /**
         * FinalEnergyDemand Data
         */
        $NumberConverter = new ElcaNumberFormatConverter();

        $demandContainer->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('heating[' . $key . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $demandContainer->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('water[' . $key . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $demandContainer->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('lighting[' . $key . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $demandContainer->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('ventilation[' . $key . ']', null, !$hasProcessConfigId, $NumberConverter)));
        $demandContainer->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('cooling[' . $key . ']', null, !$hasProcessConfigId, $NumberConverter)));

        $OverallContainer = new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('overall[' . $key . ']', null, false, ',', $NumberConverter));
        $OverallContainer->setAttribute('class', 'overall');
        $demandContainer->add($OverallContainer);

        /**
         * Toggler
         */
        $demandContainer->add(new HtmlHiddenField('toggle[' . $key . ']'))->setAttribute('class', 'toggle');
        if ($hasProcessConfigId)
            $demandContainer->add(new ElcaHtmlToggleLink())->addClass('no-xhr');

        /**
         * Add results table
         */
        if ($finalEnergyDemand instanceof ElcaProjectFinalEnergyDemand && $finalEnergyDemand->isInitialized())
            $this->appendDemandResults($demandContainer, $finalEnergyDemand);
    }

    /**
     * Appends the energy demand section
     *
     * @param  HtmlForm $Form
     */
    protected function appendEnergySupplySection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('clear');
        $Group->addClass('final-energy-section energy-supply');

        /**
         * Headline
         */
        $Group->add(new HtmlTag('h5', t('Endenergiebereitstellung in') . ' kWh/a', ['class' => 'hl-final-energy-supply']));

        $this->appendHlRow($Group, [
            'hl-usage'       => t('Nutzung Energiebereitstellung'),
            'hl-description' => t('Beschreibung'),
            'hl-quantity'    => t('Gesamt') . ' kWh / a',
            'hl-enEvRatio'   => t('In EnEV verrechnet') . ' %',
            'hl-overall'     => t('D energetisch') . ' kWh / a'
        ]);

        $Container = $Group->add(new HtmlTag('div', null, ['class' => 'clear']));

        $Ul = $Container->add(new HtmlTag('ul', null));

        if (isset($this->Data->Supply->processConfigId) &&
            is_array($this->Data->Supply->processConfigId) &&
            count($this->Data->Supply->processConfigId)
        ) {
            foreach ($this->Data->Supply->processConfigId as $key => $foo) {
                $FinalEnergySupply = ElcaProjectFinalEnergySupply::findById($key);
                $Li = $Ul->add(new HtmlTag('li'));

                $this->appendSupplyRow($Li, $key, $FinalEnergySupply);
            }
        }

        if ($this->addNewProjectFinalEnergySupply) {
            $Li = $Ul->add(new HtmlTag('li'));
            $this->appendSupplyRow($Li, 'newSupply');
        }

        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('addEnergySupply', t('Bereitstellung hinzufügen')))->addClass('add-energy-carrier');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('saveEnergySupply', t('Speichern'), true));
    }
    // End appendEnergySupplySection


    /**
     * Appends a row
     *
     * @param HtmlElement                  $Li
     * @param mixed                        $key
     * @param ElcaProjectFinalEnergySupply $FinalEnergySupply
     *
     * @internal param HtmlForm $Form
     */
    protected function appendSupplyRow(HtmlElement $Li, $key, ElcaProjectFinalEnergySupply $FinalEnergySupply = null)
    {
        $Container = $Li->add(new HtmlTag('div', null, ['class' => 'clearfix final-energy-row final-energy-supply']));
        $Container->setAttribute('id', 'final-energy-supply-' . $key);

        if (!is_numeric($key))
            $Container->addClass('new');

        /**
         * ProcessConfig selector
         */
        $Selector = $Container->add(new ElcaHtmlProcessConfigSelectorLink('processConfigId[' . $key . ']'));
        $Selector->addClass('process-config-selector');
        $Selector->setRelId($key);
        $Selector->setProjectVariantId($this->projectVariantId);
        $Selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY);
        $Selector->setContext('project-data');

        $this->checkElementChange($Selector);

        $Request = FrontController::getInstance()->getRequest();

        if ((isset($this->Data->Supply->processConfigId[$key]) && $this->Data->Supply->processConfigId[$key]) || (isset($Request->processConfigId[$key]) && $Request->processConfigId[$key])) {
            $ProcessConfig = ElcaProcessConfig::findById(isset($Request->processConfigId[$key]) ? $Request->processConfigId[$key] : $this->Data->Supply->processConfigId[$key]);
            if ($ProcessConfig->isInitialized())
                $Selector->setProcessCategoryNodeId($ProcessConfig->getProcessCategoryNodeId());

            if ($FinalEnergySupply !== null) {
                $CacheSupply = ElcaCacheFinalEnergySupply::findByFinalEnergySupplyId($FinalEnergySupply->getId());
                if ($CacheSupply->isInitialized()) {
                    if (!ElcaProcessViewSet::findResultsByCacheItemId($CacheSupply->getItemId(), 1)->count()) {
                        $Li->addClass('no-results');
                        $this->Data->toggle[$key] = true;
                    }
                } else {
                    $Li->addClass('no-results');
                    $this->Data->toggle[$key] = true;
                }
            }
            if ($ProcessConfig->isStale()) {
                $this->Data->toggle[$key] = true;
            }
            /**
             * FinalEnergySupply Data
             */
            $NumberConverter = new ElcaNumberFormatConverter();
            $PercentageConverter = new ElcaNumberFormatConverter(null, true);

            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('description[' . $key . ']')));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('quantity[' . $key . ']', null, false, $NumberConverter)));
            $Container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('enEvRatio[' . $key . ']', null, false, $PercentageConverter)));

            $OverallContainer = new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('overall[' . $key . ']'));
            $OverallContainer->setAttribute('class', 'overall');
            $Container->add($OverallContainer);

        }

        /**
         * Toggler
         */
        if ($key != 'newSupply') {
            $Container->add(new HtmlHiddenField('toggle[' . $key . ']'))->setAttribute('class', 'toggle');
            $Container->add(new ElcaHtmlToggleLink())->addClass('no-xhr');
        }

        /**
         * Remove link
         */
        if (!$this->readOnly) {

            if (is_numeric($key)) {
                $Container->add(
                    new HtmlLink(t('Löschen'), Url::factory('/project-data/deleteFinalEnergySupply/', ['id' => $key]))
                )
                          ->addClass('function-link delete-link');
            } else {
                $Container->add(new HtmlLink(t('Abbrechen'), Url::factory('/project-data/enEv/')))
                          ->addClass('function-link cancel-link');
            }
        }

        /**
         * Add results table
         */
        if (is_numeric($key))
            $this->appendSupplyResults($Container, $FinalEnergySupply);
    }
    // End appendSupplyRow


    /**
     * Appends the result table
     *
     * @param HtmlElement                  $Container
     * @param ElcaProjectFinalEnergyDemand $Demand
     */
    private function appendDemandResults($Container, ElcaProjectFinalEnergyDemand $Demand)
    {
        $ngf = ElcaProjectConstruction::findByProjectVariantId($Demand->getProjectVariantId())->getNetFloorSpace();
        $lifeTime = $Demand->getProjectVariant()->getProject()->getLifeTime();
        $m2a = $ngf * $lifeTime;

        $CacheDemand = ElcaCacheFinalEnergyDemand::findByFinalEnergyDemandId($Demand->getId());
        $this->appendResults($Container, $CacheDemand->getItemId(), $m2a);
    }
    // End appendDemandResults


    /**
     * Appends the result table
     *
     * @param HtmlElement                  $Container
     * @param ElcaProjectFinalEnergySupply $Supply
     *
     * @return void -
     */
    private function appendSupplyResults($Container, ElcaProjectFinalEnergySupply $Supply)
    {
        $ngf = ElcaProjectConstruction::findByProjectVariantId($Supply->getProjectVariantId())->getNetFloorSpace();
        $lifeTime = $Supply->getProjectVariant()->getProject()->getLifeTime();
        $m2a = $ngf * $lifeTime;

        $CacheSupply = ElcaCacheFinalEnergySupply::findByFinalEnergySupplyId($Supply->getId());
        $this->appendResults($Container, $CacheSupply->getItemId(), $m2a);
    }
    // End appendDemandResults


    /**
     * Appends the result table
     *
     * @param HtmlElement                  $Container
     * @param ElcaProjectFinalEnergySupply $Supply
     *
     * @return void -
     */
    private function appendReferenceModelResults($Container, ElcaProjectFinalEnergyRefModel $RefModel)
    {
        $ngf = ElcaProjectConstruction::findByProjectVariantId($RefModel->getProjectVariantId())->getNetFloorSpace();
        $lifeTime = $RefModel->getProjectVariant()->getProject()->getLifeTime();
        $m2a = $ngf * $lifeTime;

        $CacheRefModel = ElcaCacheFinalEnergyRefModel::findByFinalEnergyRefModelId($RefModel->getId());
        $this->appendResults($Container, $CacheRefModel->getItemId(), $m2a);
    }
    // End appendDemandResults


    /**
     * Appends the result table
     *
     * @param HtmlElement $Container
     * @param             $cacheItemId
     * @param             $m2a
     *
     * @return void -
     */
    private function appendResults(HtmlElement $Container, $cacheItemId, $m2a)
    {
        $Div = $Container->add(new HtmlTag('div', null, ['class' => 'results clearfix']));

        /**
         * Build indicator result table
         */
        $IndicatorSet = ElcaProcessViewSet::findResultsByCacheItemId($cacheItemId);

        if ($IndicatorSet->count()) {
            $doList = array();
            $indicators = array();
            foreach ($IndicatorSet as $Indicator) {
                $key = $Indicator->life_cycle_ident . $Indicator->process_id;
                if (!isset($doList[$key])) {
                    $DO = $doList[$key] = new \stdClass();
                    $DO->nameOrig = $Indicator->name_orig;
                    $DO->lifeCycleName = t($Indicator->life_cycle_name);
                } else
                    $DO = $doList[$key];

                $indicatorId = $Indicator->indicator_ident;
                $DO->$indicatorId = $Indicator->value / $m2a;
                $indicators[$indicatorId] = $Indicator->indicator_name;
            }

            $Table = $Div->add(new HtmlTable('process-databases'));
            $Table->addColumn('lifeCycleName', t('Lebenszyklus'));
            $Table->addColumn('nameOrig', t('Prozess'));

            foreach ($indicators as $indicatorId => $indicatorName)
                $Table->addColumn($indicatorId, t($indicatorName));

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();

            foreach ($indicators as $indicatorId => $indicatorName)
                $Row->getColumn($indicatorId)->setOutputElement(new ElcaHtmlNumericText($indicatorId, 4, false, ',', null, null, true));

            $Body->setDataSet($doList);
        } else {
            $this->appendInfo($Div, t('Keine Bilanzierung'));
        }
    }
    // End appendResults

    /**
     * Appends one single info
     *
     * @param  HtmlElement $Container
     * @param  string      $caption
     * @param  string      $value
     * @param string       $refUnit
     */
    private function appendInfo($Container, $caption, $value = null, $refUnit = '')
    {
        if ($refUnit)
            $refUnit = ElcaNumberFormat::formatUnit($refUnit);

        $Info = $Container->add(new HtmlTag('div', null, array('class' => 'info')));
        $Info->add(new HtmlTag('span', $caption, array('class' => 'caption')));

        if (!is_null($value))
            $Info->add(new HtmlTag('span', $value . ' ' . $refUnit, array('class' => 'value')));
    }
    // End appendInfo

    /**
     * Checks if an element needs marked as changed
     *
     * @param HtmlFormElement $Element
     */
    private function checkElementChange(HtmlFormElement $Element)
    {
        if (isset($this->changedElements[$Element->getName()]))
            $Element->addClass('changed');
    }
    // End checkElementChange


    /**
     * Appends projections
     *
     * @param DOMElement $Container
     */
    private function appendProjections(DOMElement $Container)
    {
        $Container = $Container->appendChild($this->getDiv(['class' => 'projection']));
        $Container->appendChild($this->getH5(t('Zielwert Prognose')));

        $Container->appendChild($P = $this->getP(''));
        $P->appendChild($this->getText(t('Die Prognose versucht zu einer sehr frühen Projektphase, auf Basis weniger Daten, eine erste, sehr grobe Abschatzung zu projizieren.').' '));
        $P->appendChild($this->getText(t('Die Prognose basiert auf den Eingabewerten der Nutzung plus einem Aufschlag für die Konstruktion auf Basis ausgewerteter Projekte.').' '));
        $P->appendChild($this->getText(t('Alle Aussagen können zu diesem Zeitpunkt nur grobe Abschätzungen sein, und sind kein Ersatz der Ökobilanz.')));

        $indicatorBenchmarks = ElcaProjectIndicatorBenchmarkSet::find(['project_variant_id' => $this->projectVariantId])->getArrayBy('benchmark', 'indicatorId');
        if (!count($indicatorBenchmarks)) {
            $P = $Container->appendChild($this->getP(t('Es sind noch keine Zielwerte für eine Prognose spezifiziert. Definieren Sie diese') . ' ', ['class' => 'notice']));
            $P->appendChild($this->getA(['href' => '/project-data/benchmarks/'], t('hier')));
            $P->appendChild($this->getText('.'));
        }

        $projectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $project = $projectVariant->getProject();

        $benchmarks = Environment::getInstance()->getContainer()->get(BenchmarkService::class)
            ->computeProjection($project->getBenchmarkVersion(), $projectVariant);

        $Indicators = ElcaIndicatorSet::findByProcessDbId($project->getProcessDbId());

        $DataSet = new DataObjectSet();
        foreach ($Indicators as $Indicator) {

            /**
             * Build new DO
             */
            $DO = $DataSet->add((object)['name'        => $Indicator->getName(),
                                         'ident'       => $Indicator->getIdent(),
                                         'targetValue' => isset($indicatorBenchmarks[$Indicator->getId()]) ? $indicatorBenchmarks[$Indicator->getId()] : null,
                                         'unit'        => $Indicator->getUnit()
            ]);

            foreach (['min', 'max', 'avg'] as $name) {
                $projProp = $name . 'Projection';
                $ratingProp = $name . 'Rating';

                if (!isset($benchmarks[$name][$DO->ident]))
                    continue;

                $DO->$projProp = round(abs($benchmarks[$name][$DO->ident]));

                if (!$DO->targetValue)
                    continue;

                if ($DO->$projProp >= $DO->targetValue)
                    $DO->$ratingProp = 'green';

                else {
                    $ratings = [];
                    foreach ([$DO->$projProp, $DO->targetValue] as $index => $val) {
                        foreach (ElcaProjectIndicatorBenchmark::$ratings as $rating => $range) {
                            if ($val < $range[0] || $val > $range[1])
                                continue;

                            $ratings[$index] = $rating;
                            break;
                        }
                    }

                    $DO->$ratingProp = (count($ratings) == 2 && $ratings[0] == $ratings[1]) ? 'yellow' : 'red';
                }
            }
        }

        $Table = new HtmlTable('benchmark-projections');
        $Table->addColumn('name', t('Wirkungskategorie'))->addClass('name');
        $Table->addColumn('targetValue', t('Zielwert / Mindest-Erfüllungsgrad'))->addClass('targetValue');
        $Table->addColumn('minProjection', t('Beste Prognose'))->addClass('min');
        $Table->addColumn('avgProjection', t('Mittlere Prognose'))->addClass('avg');
        $Table->addColumn('maxProjection', t('Schlechteste Prognose'))->addClass('max');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();

        $Span = new HtmlTag('span');
        $Span->add(new HtmlText('name'));
        $Row->getColumn('name')->setOutputElement($Span);
        $Row->getColumn('minProjection')->setOutputElement(new ElcaHtmlBenchmarkProjection('min'));
        $Row->getColumn('maxProjection')->setOutputElement(new ElcaHtmlBenchmarkProjection('max'));
        $Row->getColumn('avgProjection')->setOutputElement(new ElcaHtmlBenchmarkProjection('avg'));

        $Body->setDataSet($DataSet);
        $Table->appendTo($Container);
    }
    // End appendInfo


    /**
     * @param HtmlElement $Container
     * @param array       $headlines
     *
     * @return HtmlElement
     */
    private function appendHlRow(HtmlElement $Container, array $headlines)
    {
        $Row = $Container->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        foreach ($headlines as $cssIdent => $caption)
            $Row->add(new HtmlTag('h6', $caption, ['class' => $cssIdent]));

        return $Row;
    }
    // End appendHlRow

    /**
     * @param string $formId
     * @param object $FormData
     *
     * @return HtmlForm
     */
    private function getSectionForm($formId, $FormData)
    {
        $form = new HtmlForm($formId, '/project-data/saveEnEv/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');
        $form->setRequest(FrontController::getInstance()->getRequest());
        $form->setDataObject($FormData);
        $form->setReadonly($this->readOnly);

        if ($this->has('Validator'))
            $form->setValidator($this->get('Validator'));

        $form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));

        return $form;
    }
    // End getSectionForm
}
// End ElcaProjectDataEnEvView
