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
namespace Elca\View\Report;


use Beibob\Blibs\FrontController;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlText;
use DOMElement;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectKwk;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlComponentAssets;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaReportAssetsConverter;
use Elca\View\helpers\ElcaTranslatorConverter;

/**
 * Builds the asset report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaReportAssetsView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_CONSTRUCTIONS = 'constructions';
    const BUILDMODE_SYSTEMS = 'systems';
    const BUILDMODE_OPERATION = 'operation';
    const BUILDMODE_TRANSPORTS = 'transports';
    const BUILDMODE_TOP_ASSETS = 'top-assets';
    const BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS = 'non-default-life-time';
    const BUILDMODE_NOT_CALCULATED_COMPONENTS = 'not-calculated-components';


    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * FilterDO
     */
    private $FilterDO;


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_CONSTRUCTIONS);
        $this->FilterDO = $this->get('FilterDO');
    }
    // End init


    /**
     * Renders the report
     *
     * @param DOMElement         $Container
     * @param DOMElement         $infoDl
     * @param ElcaProjectVariant $ProjectVariant
     * @param int                $lifeTime
     */
    protected function renderReports(DOMElement $Container, DOMElement $infoDl, ElcaProjectVariant $ProjectVariant, $lifeTime)
    {
        $this->addClass($Container, 'report-assets report-assets-'.$this->buildMode);

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)') . ': '));
        $infoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()) . ' m²'));

        $tdContainer = $this->appendPrintTable($Container);

        switch($this->buildMode)
        {
            case self::BUILDMODE_CONSTRUCTIONS:
                $this->buildAssets($Container, ElcaReportSet::findConstructionAssets($this->projectVariantId));
                break;

            case self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS:
                $tdContainer->appendChild($this->getP(t('Folgenden Baustoffen wurden eigene Nutzungsdauern zugewiesen.')));
                $this->buildAssets($tdContainer, ElcaReportSet::findNonDefaultLifeTimeAssets($this->projectVariantId));
                break;

            case self::BUILDMODE_NOT_CALCULATED_COMPONENTS:
                $tdContainer->appendChild($this->getP(t('Folgende Baustoffe werden in der Ökobilanzierung des Projekts nicht berücksichtigt.')));
                $this->buildAssets($tdContainer, ElcaReportSet::findNotCalculatedComponents($this->projectVariantId));
                break;

            case self::BUILDMODE_SYSTEMS:
                $this->buildAssets($tdContainer, ElcaReportSet::findSystemAssets($this->projectVariantId));
                break;

            case self::BUILDMODE_OPERATION:
                $projectEnEv = ElcaProjectEnEv::findByProjectVariantId($this->projectVariantId);
                $infoDl->appendChild($this->getDt([], t('EnEV-Bezugsfläche (NGF)') . ': '));
                $infoDl->appendChild($this->getDd([], $projectEnEv->getNgf() . ' m²'));

                $tdContainer->appendChild($this->getH1(t('Endenergiebereitstellung')));
                $this->buildOperationAssets($tdContainer, ElcaReportSet::findFinalEnergySupplyAssets($this->projectVariantId));
                $tdContainer->appendChild($this->getH1(t('Endenergiebedarf')));
                $this->buildOperationAssets($tdContainer, ElcaReportSet::findFinalEnergyDemandAssets($this->projectVariantId));

                $projectKwk = ElcaProjectKwk::findByProjectVariantId($this->projectVariantId);

                if ($projectKwk->isInitialized()) {
                    $h1 = $tdContainer->appendChild($this->getH1(t('Fernwärme')));

                    if ($projectKwk->getName()) {
                        $h1->appendChild($this->getText(' "'. $projectKwk->getName() .'"'));
                    }
                    $kwkFinalEnergyDemandAssets = ElcaReportSet::findKwkFinalEnergyDemandAssets($this->projectVariantId);
                    $this->buildOperationAssets($tdContainer,
                        $kwkFinalEnergyDemandAssets);
                    $this->buildKwkPieChart($tdContainer, $kwkFinalEnergyDemandAssets);
                }
                break;

            case self::BUILDMODE_TRANSPORTS:
                $TransportSet = ElcaProjectTransportSet::findByProjectVariantId($ProjectVariant->getId(), ['id' => 'ASC'], 1);
                if (!$TransportSet->count() || !$TransportSet[0]->getCalcLca())
                    return;

                $this->buildTransportAssets($tdContainer, ElcaReportSet::findTransportAssets($this->projectVariantId));
                break;
            case self::BUILDMODE_TOP_ASSETS:
                $this->appendTopAssets($tdContainer, $ProjectVariant);
                break;
        }
    }
    // End beforeRender


    /**
     * Builds the view for construction and system assets
     *
     * @param DOMElement     $Container
     * @param  ElcaReportSet $ReportSet
     * @return void -
     */
    private function buildAssets(DOMElement $Container, ElcaReportSet $ReportSet)
    {
        if(!$ReportSet->count())
            return;

        $reports = [];
        foreach($ReportSet as $DO)
            $reports[$DO->element_type_din_code . ' ' . t($DO->element_type_name)][t($DO->element_type_parent_name)][$DO->element_id][] = $DO;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category']));

        foreach($reports as $elementTypeName => $elementSubTypes)
        {
            foreach($elementSubTypes as $elementTypeParentName => $elements)
            {
                $TypeLi = $TypeUl->appendChild($this->getLi(['class' => 'section']));
                $H1 = $TypeLi->appendChild($this->getH1($elementTypeName));
                $H1->appendChild($this->getSpan($elementTypeParentName));

                $Ul = $TypeLi->appendChild($this->getUl(['class' => 'report-elements']));
                foreach($elements as $data)
                {
                    $Li = $Ul->appendChild($this->getLi(['class' => 'section clearfix']));
                    $this->appendAsset($Li, $data);
                }
            }
        }
        $this->addClass($Li, 'last');
        $this->addClass($TypeLi, 'last');
    }
    // End buildAssets


    /**
     * Appends a table for one asset
     *
     * @param  DOMElement $Container
     * @param  array      $data
     * @return void -
     */
    private function appendAsset(DOMElement $Container, array $data, $elementImage = true)
    {
        /**
         * Add project element description
         */
        $DO = $data[0];

        $elementId = $DO->element_id;

        $h2 = $Container->appendChild($this->getH2(''));
        $h2->appendChild($this->getA(['href' => '/project-elements/'.$elementId.'/', 'class' => 'page'], $DO->element_name));

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));
        $Dl->appendChild($this->getDt([], t('Menge im Gebäude') . ':'));
        $Dl->appendChild($this->getDd([], ElcaNumberFormat::toString($DO->element_quantity, 2) .' '. ElcaNumberFormat::formatUnit($DO->element_ref_unit)));

        if($DO->element_mass)
        {
            $Dl->appendChild($this->getDt([], t('Masse') . ':'));
            $Dl->appendChild($this->getDd([], ElcaNumberFormat::toString($DO->element_mass, 2) .' kg'));
        }

        if ($this->buildMode !== self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS) {
            $Dl->appendChild($this->getDt([], 'DIN 276:'));
            $Dl->appendChild($this->getDd([], $DO->element_type_din_code .' '. t($DO->element_type_name)));
        }

        $EltContainer = $Container->appendChild($this->getDiv(['class' => 'element-assets']));

        /**
         * Split data into geometric and non-geometric sections
         */
        $components = [];
        foreach($data as $DO)
        {
            if(!isset($components[$DO->component_is_layer][$DO->element_component_id]))
            {
                $components[$DO->component_is_layer][$DO->element_component_id] = $DO;

                // add an extra component details row
                if ($this->buildMode !== self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS)
                    $components[$DO->component_is_layer][$DO->element_component_id.'-details'] = $DO;

                $DO->processes = [];
            }

            $components[$DO->component_is_layer][$DO->element_component_id]->processes[] = $DO;
        }

        /**
         * Build and add tables
         */
        $Converter = new ElcaReportAssetsConverter();

        if(isset($components[true]))
        {
            $EltContainer->appendChild($this->getH3(t('Geometrische Komponenten')));

            $Table = $this->getAssetTable(true);
            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            $Row->setAttribute('class', 'component');

            $Row->getColumn('process_config_name')->setOutputElement(new ElcaHtmlComponentAssets('process_config_name',
                null, null, $this->Project->getProcessDbId()));
            $Row->getColumn('component_layer_position')->setOutputElement(new HtmlText('component_layer_position', $Converter));

            if ($this->buildMode !== self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS) {
                $Row = $Body->addTableRow();
                $Row->setAttribute('class', 'details');
                $Row->getColumn('component_layer_position')->setColSpan(2);
                $Row->getColumn('component_layer_position')->setOutputElement(new ElcaHtmlComponentAssets('component_layer_position', $Converter,
                     null, $this->Project->getProcessDbId()));
            }

            $Body->setDataSet($components[true]);
            $Table->appendTo($EltContainer);
        }

        if(isset($components[false]))
        {
            $EltContainer->appendChild($this->getH3(t('Einzelkomponenten')));

            $Table = $this->getAssetTable(false);
            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            $Row->setAttribute('class', 'component');
            $Row->getColumn('process_config_name')->setOutputElement(new ElcaHtmlComponentAssets('process_config_name',
                null, null, $this->Project->getProcessDbId()));

            if ($this->buildMode !== self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS) {
                $Row = $Body->addTableRow();
                $Row->setAttribute('class', 'details');
                $Row->getColumn('component_layer_position')->setColSpan(2);
                $Row->getColumn('component_layer_position')->setOutputElement(new ElcaHtmlComponentAssets('component_layer_position', $Converter,
                    null, $this->Project->getProcessDbId()));
            }

            $Body->setDataSet($components[false]);
            $Table->appendTo($EltContainer);
        }


        if (!\in_array($this->buildMode,
                [self::BUILDMODE_NON_DEFAULT_LIFE_TIME_ASSETS, self::BUILDMODE_NOT_CALCULATED_COMPONENTS], true)
           && $DO->has_element_image) {
            $this->appendElementImage($Container, $elementId);
        }
    }
    // End appendAsset


    /**
     * Returns a table configuration for geometric or non-gemetric element sections
     *
     * @param  -
     * @return HtmlTable
     */
    private function getAssetTable($isGeometric)
    {
        $Table = new HtmlTable('report report-assets '. ($isGeometric? 'is-geometric' : 'non-geometric'));

        if($isGeometric)
            $Table->addColumn('component_layer_position', '#');
        else
            $Table->addColumn('component_layer_position', '');

        $Table->addColumn('process_config_name', t('Komponente'));
        return $Table;
    }
    // End getTable


    /**
     * Builds the operation view
     *
     * @param  DOMElement   $Container
     * @param ElcaReportSet $ReportSet
     * @return void -
     */
    private function buildOperationAssets(DOMElement $Container, ElcaReportSet $ReportSet)
    {
        /**
         * Append additional operation assets
         */
        if(!$ReportSet->count())
            return;

        $reports = [];
        foreach($ReportSet as $DO)
            $reports[$DO->id][] = $DO;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category final-energy']));

        foreach($reports as $finalEnergyDemandId => $processes)
        {
            $Li = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $this->appendOperationAsset($Li, $processes);
        }
    }
    // End buildOperationAssets


    /**
     * Builds a operation asset
     *
     * @param DOMElement $Container
     * @param array      $data
     * @return void -
     */
    private function appendOperationAsset(DOMElement $Container, array $data)
    {
        /**
         * Add finalEnergyDemand description
         */
        $DO = $data[0];

        if (isset($DO->ident) && $DO->ident === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
            $name = t('Prozessenergie');
        } else {
            $name = $DO->process_config_name;
        }

        $Container->appendChild($this->getH2($name));

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));
        if (isset($DO->ratio) && $DO->ratio != 1) {
            $Dl->appendChild($this->getDt([], t('Anteil') . ':'));
            $Dl->appendChild($this->getDd([], ElcaNumberFormat::formatQuantity($DO->ratio, '%', 2, true)));
        }
        $Dl->appendChild($this->getDt([], t('Menge') . ':'));
        $Dl->appendChild($this->getDd([], ElcaNumberFormat::toString($DO->total, 2) . ' KWh / m²a'));

        $Table = new HtmlTable('report report-assets-details operation');

        $Table->addColumn('life_cycle_description', t('Lebenszyklus'));
        $Table->addColumn('process_name_orig', t('Energieträger'));
        $Table->addColumn('process_ref_value', t('Bezugsgröße'));
        $Table->addColumn('process_uuid', 'UUID');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-info');

        $Converter = new ElcaReportAssetsConverter();

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('process_ref_value')->setOutputElement(new HtmlText('process_ref_value', $Converter));
        $Row->getColumn('life_cycle_description')->setOutputElement(new HtmlText('life_cycle_description', new ElcaTranslatorConverter()));

        $Body->setDataSet($data);
        $Table->appendTo($Container);
    }
    // End appendOperationTable


    /**
     * Builds the transports view
     *
     * @param  DOMElement   $Container
     * @param ElcaReportSet $ReportSet
     * @return void -
     */
    private function buildTransportAssets(DOMElement $Container, ElcaReportSet $ReportSet)
    {
        /**
         * Append additional operation assets
         */
        if(!$ReportSet->count())
            return;

        $reports = [];
        foreach($ReportSet as $DO)
            $reports[$DO->transport_id][] = $DO;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'transports']));

        foreach($reports as $processes)
        {
            $Li = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $this->appendTransportAsset($Li, $processes);
        }
    }
    // End appendTransportAssets


    /**
     * Builds a transport asset
     *
     * @param DOMElement $Container
     * @param array      $data
     * @return void -
     */
    private function appendTransportAsset(DOMElement $Container, array $data)
    {
        /**
         * Add transport description
         */
        $DO = $data[0];

        $Container->appendChild($this->getH2($DO->transport_name));

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));
        $Dl->appendChild($this->getDt([], t('Menge') . ':'));
        $Dl->appendChild($this->getDd([], ElcaNumberFormat::toString($DO->transport_quantity, 2) . ' t'));

        $Table = new HtmlTable('report report-assets-details operation');

        $Table->addColumn('life_cycle_description', t('Lebenszyklus'));
        $Table->addColumn('process_name_orig', t('Verkehrsmittel'));
        $Table->addColumn('process_ref_value', t('Bezugsgröße'));
        $Table->addColumn('process_uuid', 'UUID');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-info');

        $Converter = new ElcaReportAssetsConverter();

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('process_ref_value')->setOutputElement(new HtmlText('process_ref_value', $Converter));
        $Row->getColumn('life_cycle_description')->setOutputElement(new HtmlText('life_cycle_description', new ElcaTranslatorConverter()));
        $Body->setDataSet($data);

        $Table->appendTo($Container);
    }
    // End appendTransportAsset


    /**
     * Appends the top elements view
     *
     * @param  DOMElement        $Container
     * @param ElcaProjectVariant $ProjectVariant
     * @return DOMElement
     */
    private function appendTopAssets(DOMElement $Container, ElcaProjectVariant $ProjectVariant)
    {
        $Form = new HtmlForm('reportTopForm', '/project-report-assets/topAssets/');

        if($this->FilterDO)
            $Form->setDataObject($this->FilterDO);

        $Form->setRequest(FrontController::getInstance()->getRequest());

        $Group = $Form->add(new HtmlFormGroup(t('Filter')));
        $Group->add(new ElcaHtmlFormElementLabel(t('Anzahl Baustoffe'), new ElcaHtmlNumericInput('limit')));

        $Radio = $Group->add(new ElcaHtmlFormElementLabel(t('Sortierung'), new HtmlRadioGroup('order')));
        $Radio->add(new HtmlRadiobox(t('Absteigend'), 'DESC'));
        $Radio->add(new HtmlRadiobox(t('Aufsteigend'), 'ASC'));

        $Radio = $Group->add(new ElcaHtmlFormElementLabel(t('Menge'), new HtmlRadioGroup('inTotal')));
        $Radio->add(new HtmlRadiobox(t('je Bezugsgröße'), '0'));
        $Radio->add(new HtmlRadiobox(t('gesamt'), '1'));

        $Form->appendTo($Container);

        $inTotal = isset($this->FilterDO->inTotal)? $this->FilterDO->inTotal : true;
        $order   = isset($this->FilterDO->order)? $this->FilterDO->order : 'DESC';
        $limit   = isset($this->FilterDO->limit)? $this->FilterDO->limit : 10;

        $ReportSet = ElcaReportSet::findTopNAssets($this->projectVariantId, $inTotal, $order, $limit);

        foreach($ReportSet as $index => $Report)
        {
            $Report->index = $index + 1;
            $Report->element_name = $Report->element_name.' ['.$Report->element_id.']';
            $Report->element_type = $Report->element_type_din_code . ' ' . t($Report->element_type_name);
            $Report->element_quantity = ElcaNumberFormat::toString($Report->element_quantity, 2).' '. ElcaNumberFormat::formatUnit($Report->element_ref_unit);
        }

        $Table = new HtmlTable('report report-top-elements');

        $Table->addColumn('index', '#');
        $Table->addColumn('process_name_orig', t('Prozess'));
        $Table->addColumn('process_life_cycle_description', t('Modul'));
        $Table->addColumn('element_name', t('Bauteil'));
        $Table->addColumn('element_quantity', t('Menge') . ' ' . t('Bauteil'))->addClass('element_quantity');
        $Table->addColumn('element_type', t('Kostengruppe'));

        $Table->addColumn('cache_component_mass', t('Masse') . ' ' . t('in') . ' ' . 'kg')->addClass('cache_component_mass');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');
        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();

        $Row->getColumn('cache_component_mass')->setOutputElement(new ElcaHtmlNumericText('cache_component_mass', 2));

        $Body->setDataSet($ReportSet);
        $Table->appendTo($Container);

        return $Container;
    }

    private function buildKwkPieChart(DOMElement $tdContainer, ElcaReportSet $kwkFinalEnergyDemandAssets)
    {
        $pieData = [];
        $overallRatio = 0;
        foreach ($kwkFinalEnergyDemandAssets as $kwkFinalEnergyDemandAsset) {
            $pieData[] = (object)['name' => $kwkFinalEnergyDemandAsset->process_config_name,
                                  'value' => $kwkFinalEnergyDemandAsset->ratio];

            $overallRatio += $kwkFinalEnergyDemandAsset->ratio;
        }

        if ($overallRatio < 1) {
            $pieData[] = (object)['name' => t('Undefiniert'),
                                  'value' => 1 - $overallRatio,
                                  'class' => 'undefined'
            ];
        }

        $tdContainer->appendChild($this->getDiv([
            'class' => 'chart pie-chart',
            'data-values' => json_encode($pieData)
        ]));
    }
}
// End ElcaReportAssetsView
