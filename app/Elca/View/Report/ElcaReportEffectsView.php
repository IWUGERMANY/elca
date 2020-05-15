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

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use DOMElement;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectKwk;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Transform\ArrayOfObjects;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Model\Report\IndicatorEffect;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportProjectElementLink;
use Elca\View\helpers\ElcaTranslatorConverter;
use Elca\View\helpers\Report\HtmlIndicatorEffectsTable;

/**
 * Builds the report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaReportEffectsView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_CONSTRUCTIONS  = 'constructions';
    const BUILDMODE_SYSTEMS  = 'systems';
    const BUILDMODE_OPERATION  = 'operation';
    const BUILDMODE_ELEMENTS  = 'elements';
    const BUILDMODE_TOP_ELEMENTS  = 'top-elements';
    const BUILDMODE_TOP_PROCESSES  = 'top-processes';
    const BUILDMODE_TRANSPORTS = 'transports';


    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * FilterDO
     */
    private $filterDO;

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
        $this->filterDO  = $this->get('FilterDO');
    }
    // End init

    /**
     * Renders the report
     *
     * @param DOMElement $Container
     * @param DOMElement $InfoDl
     * @param ElcaProjectVariant $ProjectVariant
     * @param $lifeTime
     */
    protected function renderReports(DOMElement $Container, DOMElement $InfoDl, ElcaProjectVariant $ProjectVariant, $lifeTime)
    {
        $this->addClass($Container, 'report-effects report-effects-'. $this->buildMode);

        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $InfoDl->appendChild($this->getDt([], t('Bezugsfläche (NGF)') . ': '));
        $InfoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($ProjectConstruction->getNetFloorSpace()) .' m²'));

        $m2a = $lifeTime * $ProjectConstruction->getNetFloorSpace();
        $isEn15804Compliant = $this->Project->getProcessDb()->isEn15804Compliant();

        $lifeCycleUsages = Environment::getInstance()
                                      ->getContainer()
                                      ->get(LifeCycleUsageService::class)
                                      ->findLifeCycleUsagesForProject(new ProjectId($this->Project->getId()));

        $tdContainer = $this->appendPrintTable($Container);

        switch($this->buildMode)
        {
            case self::BUILDMODE_CONSTRUCTIONS:
                $Form = new HtmlForm('reportForm', '/project-report-effects/construction/');
                $Form->setRequest(FrontController::getInstance()->getRequest());

                if($this->filterDO)
                    $Form->setDataObject($this->filterDO);

                $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('indicatorId')));
                foreach(ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator)
                    $Select->add(new HtmlSelectOption(t($Indicator->getName()), $Indicator->getId()));

                $Radio = $Form->add(new ElcaHtmlFormElementLabel(t('Baustoffe') . ' / m²a', new HtmlRadioGroup('aggregated')));
                $Radio->add(new HtmlRadiobox(t('seperat'), 0));
                $Radio->add(new HtmlRadiobox(t('aggregiert'), 1));

                $Form->appendTo($Container);

                $this->buildElementEffects($tdContainer, ElcaReportSet::findConstructionEffects($this->projectVariantId), $m2a, $isEn15804Compliant, $lifeCycleUsages);
                break;

            case self::BUILDMODE_ELEMENTS:
                $this->appendNonDefaultLifeTimeInfo($InfoDl);
                $this->buildElementEffects($tdContainer, ElcaReportSet::findElementCatalogEffects($this->projectVariantId), $m2a, $isEn15804Compliant, $lifeCycleUsages);
                break;

            case self::BUILDMODE_SYSTEMS:
                $Form = new HtmlForm('reportForm', '/project-report-effects/systems/');
                $Form->setRequest(FrontController::getInstance()->getRequest());

                if($this->filterDO)
                    $Form->setDataObject($this->filterDO);

                $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('indicatorId')));
                foreach(ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator)
                    $Select->add(new HtmlSelectOption(t($Indicator->getName()), $Indicator->getId()));

                $Radio = $Form->add(new ElcaHtmlFormElementLabel(t('Baustoffe') . ' / m²a', new HtmlRadioGroup('aggregated')));
                $Radio->add(new HtmlRadiobox(t('seperat'), 0));
                $Radio->add(new HtmlRadiobox(t('aggregiert'), 1));

                $Form->appendTo($Container);
                $this->buildElementEffects($tdContainer, ElcaReportSet::findSystemEffects($this->projectVariantId), $m2a, $isEn15804Compliant, $lifeCycleUsages);
                break;

            case self::BUILDMODE_OPERATION:
                $ProjectEnEv = ElcaProjectEnEv::findByProjectVariantId($this->projectVariantId);
                $InfoDl->appendChild($this->getDt([], t('EnEV-Bezugsfläche (NGF)') . ': '));
                $InfoDl->appendChild($this->getDd([], $ProjectEnEv->getNgf().' m²'));

                $tdContainer->appendChild($this->getH1(t('Endenergiebereitstellung')));
                $this->buildOperationEffects($tdContainer, ElcaReportSet::findFinalEnergySupplyEffects($this->projectVariantId), $m2a, $isEn15804Compliant, true);
                $tdContainer->appendChild($this->getH1(t('Endenergiebedarf')));
                $this->buildOperationEffects($tdContainer, ElcaReportSet::findFinalEnergyDemandEffects($this->projectVariantId), $m2a, $isEn15804Compliant);

                $projectKwk = ElcaProjectKwk::findByProjectVariantId($this->projectVariantId);

                if ($projectKwk->isInitialized()) {
                    $h1 = $tdContainer->appendChild($this->getH1(t('KWK / Fernwärme')));

                    if ($projectKwk->getName()) {
                        $h1->appendChild($this->getText(' "'. $projectKwk->getName() .'"'));
                    }

                    $kwkFinalEnergyDemandAssets = ElcaReportSet::findKwkFinalEnergyDemandAssets($this->projectVariantId);
                    $this->buildKwkPieChart($tdContainer, $kwkFinalEnergyDemandAssets);

                    $this->buildOperationEffects($tdContainer,
                        ElcaReportSet::findKwkFinalEnergyDemandEffects($this->projectVariantId), $m2a, $isEn15804Compliant);
                }
                break;

            case self::BUILDMODE_TRANSPORTS:
                $TransportSet = ElcaProjectTransportSet::findByProjectVariantId($ProjectVariant->getId(), ['id' => 'ASC'], 1);
                if (!$TransportSet->count() || !$TransportSet[0]->getCalcLca())
                    return;

                $this->buildTransportEffects($tdContainer, ElcaReportSet::findTransportEffects($this->projectVariantId), $m2a, $isEn15804Compliant);
                break;

            case self::BUILDMODE_TOP_ELEMENTS:
                $this->appendTopElements($tdContainer, $ProjectVariant, $m2a);
                break;
            case self::BUILDMODE_TOP_PROCESSES:
                $this->appendTopProcesses($tdContainer, $ProjectVariant, $m2a);
                break;

        }
    }
    // End beforeRender

    /**
     * Builds the view for construction and system effects
     *
     * @param DOMElement    $container
     * @param ElcaReportSet $reportSet
     * @param float         $m2a
     * @param bool          $isEn15804Compliant
     */
    private function buildElementEffects(DOMElement $container, ElcaReportSet $reportSet, $m2a, $isEn15804Compliant, LifeCycleUsages $lifeCycleUsages)
    {
        if(!$reportSet->count())
            return;

        $reports = $this->prepareElementEffects($reportSet, $m2a, $isEn15804Compliant);

        $typeUl = $container->appendChild($this->getUl(['class' => 'category']));
        foreach ($reports as $dinCode => $elements)
        {
            $elementItem = current(current($elements));
            $typeLi = $typeUl->appendChild($this->getLi(['class' => 'section clearfix']));

            $H1 = $typeLi->appendChild($this->getH1($dinCode .' '. t($elementItem->element_type_name)));
            $H1->appendChild($this->getSpan(t($elementItem->element_parent_name)));

            $ul = $typeLi->appendChild($this->getUl(['class' => 'report-elements']));
            foreach ($elements as $elementId => $indicators)
            {
                $indicatorEffects = [];
                foreach ($indicators as $indicatorItem) {
                    $indicatorEffects[] = new IndicatorEffect(
                        $indicatorItem->indicator,
                        $indicatorItem->phases
                    );
                }

                $indicatorItem = current($indicators);

                $li = $ul->appendChild($this->getLi(['class' => 'section clearfix']));
                $h2 = $li->appendChild($this->getH2(''));
                $h2->appendChild($this->getA([
                    'href' => '/project-elements/'. $indicatorItem->element_id.'/',
                    'class' => 'page'
                ], $indicatorItem->element_name));

                $Dl = $li->appendChild($this->getDl(['class' => 'clearfix']));
                $Dl->appendChild($this->getDt([], t('Menge') . ': '));

                $qtyStr = ElcaNumberFormat::toString($indicatorItem->element_quantity, 2) .' '. ElcaNumberFormat::formatUnit($indicatorItem->element_ref_unit);
                $Dl->appendChild($this->getDd([], $qtyStr));

                $tables = $li->appendChild($this->getDiv(['class' => 'tables']));
                $effectsTable = new HtmlIndicatorEffectsTable('element-effects', $indicatorEffects, $lifeCycleUsages);
                $effectsTable->appendTo($tables);

                /**
                 * Append wrapper for details informations on element
                 */
                $tables->appendChild($this->createElement('include', null,
                    ['name' => ElcaReportEffectDetailsView::class,
                     'buildMode' => ElcaReportEffectDetailsView::BUILDMODE_WRAPPER,
                     'elementId' => $elementId,
                     'm2a' => $m2a,
                     'aggregated' => $this->filterDO? $this->filterDO->aggregated : false,
                    ]));


                if ($this->filterDO) {
                    $this->appendElementEffectsChart($li, $elementId, $this->filterDO->indicatorId, $this->filterDO->aggregated);
                }

                if ($elementItem->has_element_image) {
                    $this->appendElementImage($li, $elementId);
                }
            }
        }

        $this->addClass($typeLi, 'last');
        $this->addClass($li, 'last');
    }
    // End buildEffects


    /**
     * Builds the view for construction and system effects
     *
     * @param  DOMElement    $Container
     * @param  ElcaReportSet $ReportSet
     * @param  float         $m2a
     * @param bool           $addPhaseRec
     * @param bool           $isSupply
     * @return void -
     */
    private function buildOperationEffects(DOMElement $Container, ElcaReportSet $ReportSet, $m2a, $addPhaseRec = false, $isSupply = false)
    {
        if(!$ReportSet->count())
            return;

        $reports = [];
        foreach($ReportSet as $DO)
            $reports[$DO->id]['total'][] = $DO;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category final-energy']));
        foreach($reports as $finalEnergyDemandId => $indicators)
        {
            $Li = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $this->appendEffect($Li, $indicators, $m2a, $addPhaseRec, $isSupply);
        }
    }
    // End buildOperationEffects

    /**
     * Builds the view for construction and system effects
     *
     * @param  DOMElement    $Container
     * @param  ElcaReportSet $ReportSet
     * @param  float         $m2a
     * @param bool           $addPhaseRec
     * @return void -
     */
    private function buildTransportEffects(DOMElement $Container, ElcaReportSet $ReportSet, $m2a, $addPhaseRec = false)
    {
        if(!$ReportSet->count())
            return;

        $reports = [];
        foreach($ReportSet as $DO)
            $reports[$DO->transport_mean_id]['total'][] = $DO;

        $TypeUl = $Container->appendChild($this->getUl(['class' => 'category transports']));
        foreach($reports as $indicators)
        {
            $Li = $TypeUl->appendChild($this->getLi(['class' => 'section clearfix']));
            $this->appendEffect($Li, $indicators, $m2a, $addPhaseRec);
        }
    }
    // End buildOperationEffects


    /**
     * Appends a table for one effect
     *
     * @param  DOMElement $Container
     * @param  array      $data
     * @param  float      $m2a
     * @param bool        $addPhaseRec
     * @param bool        $isSupply
     * @return void -
     */
    private function appendEffect(DOMElement $Container, array $data, $m2a, $addPhaseRec = false, $isSupply = false)
    {
        $elementEffectsMode = in_array($this->buildMode, [self::BUILDMODE_CONSTRUCTIONS, self::BUILDMODE_SYSTEMS, self::BUILDMODE_ELEMENTS]);

        /**
         * Add project element description
         */
        $DataObject = $data['total'][0];

        $h2 = $Container->appendChild($this->getH2(''));

        if ($elementEffectsMode) {
            $url = '/project-elements/'. $DataObject->element_id.'/';
            $h2->appendChild($this->getA(['href' => $url, 'class' => 'page'], $DataObject->element_name));
        } else {
            $prefix = '';
            if (isset($DataObject->ident) && $DataObject->ident === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
                $prefix = t('Prozessenergie') . ': ';
            }
            $h2->appendChild($this->getText($prefix . $DataObject->element_name));
        }

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));
        if (isset($DataObject->ratio) && $DataObject->ratio != 1) {
            $Dl->appendChild($this->getDt([], t('Anteil') . ':'));
            $Dl->appendChild($this->getDd([], ElcaNumberFormat::formatQuantity($DataObject->ratio, '%', 2, true)));
        }

        $Dl->appendChild($this->getDt([], t('Menge') . ': '));

        $qtyStr = ElcaNumberFormat::toString($DataObject->element_quantity, 2) .' '. ElcaNumberFormat::formatUnit($DataObject->element_ref_unit);
        if($this->buildMode == self::BUILDMODE_OPERATION)
            $qtyStr .= $isSupply? ' / a' : ' / m²a';
        $Dl->appendChild($this->getDd([], $qtyStr));

        /**
         * Normalize indicators
         */
        $dataSet = [];
        foreach($data as $phase => $indicators)
        {
            $name = 'indicator_' . $phase . '_value';
            foreach($indicators as $DO)
            {
                if(!isset($dataSet[$DO->indicator_id]))
                    $dataSet[$DO->indicator_id] = $DataObject = $DO;
                else
                    $DataObject = $dataSet[$DO->indicator_id];

                $DataObject->$name = $DO->indicator_value / max(1, $m2a);
            }
        }

        $Table = new HtmlTable('report report-effects');
        $Table->addColumn('indicator_name', t('Indikator'));
        $Table->addColumn('indicator_unit', t('Einheit'));

        if($this->buildMode == self::BUILDMODE_OPERATION)
            $Table->addColumn('indicator_total_value', t('Indikator Nutzung') . ' / m²a');
        elseif ($this->buildMode == self::BUILDMODE_TRANSPORTS) {
            $Table->addColumn('indicator_total_value', t('Gesamt') . ' / m²a');
        }
        elseif ($elementEffectsMode) {
            $Table->addColumn('indicator_total_value', t('Gesamt') . ' / m²a');
            $Table->addColumn('indicator_prod_value', t('Herstellung') . ' / m²a');
            $Table->addColumn('indicator_maint_value', t('Instandhaltung') . ' / m²a');
            $Table->addColumn('indicator_eol_value', t('Entsorgung') . ' / m²a');

            if ($addPhaseRec)
                $Table->addColumn('indicator_rec_value', t('Rec.potential') . ' / m²a');
        }

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $columns = ['indicator_total_value' => $this->buildMode != self::BUILDMODE_OPERATION? t('Gesamt') : ($isSupply? t('Rec.potential') : t('Nutzung'))];
        if ($elementEffectsMode) {
            $columns = array_merge($columns, ['indicator_prod_value' => t('Herstellung'), 'indicator_maint_value' => t('Instandhaltung'), 'indicator_eol_value' => t('Entsorgung')]);

            if ($addPhaseRec)
                $columns['indicator_rec_value'] = t('Rec.potential');
        }

        foreach ($columns as $colName => $caption) {
            $Span = $HeadRow->getColumn($colName)->setOutputElement(new HtmlTag('span', $caption. ' / m²'));
            $Span->add(new HtmlTag('sub', t('NGF')));
            $Span->add(new HtmlStaticText('a'));
        }

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('indicator_total_value')->setOutputElement(new ElcaHtmlNumericText('indicator_total_value', 10, false, '?', null, null, true));
        $Row->getColumn('indicator_name')->setOutputElement(new HtmlText('indicator_name', new ElcaTranslatorConverter()));
        $Row->getColumn('indicator_unit')->setOutputElement(new HtmlText('indicator_unit', new ElcaTranslatorConverter()));

        if ($elementEffectsMode) {
            $Row->getColumn('indicator_prod_value')->setOutputElement(new ElcaHtmlNumericText('indicator_prod_value', 10, false, '?', null, null, true));
            $Row->getColumn('indicator_maint_value')->setOutputElement(new ElcaHtmlNumericText('indicator_maint_value', 10, false, '?', null, null, true));
            $Row->getColumn('indicator_eol_value')->setOutputElement(new ElcaHtmlNumericText('indicator_eol_value', 10, false, '?', null, null, true));

            if ($addPhaseRec)
                $Row->getColumn('indicator_rec_value')->setOutputElement(new ElcaHtmlNumericText('indicator_rec_value', 10, false, '?', null, null, true));

        }
        $Body->setDataSet($dataSet);

        $Tables = $Container->appendChild($this->getDiv(['class' => 'tables']));
        $Table->appendTo($Tables);

        /**
         * Append wrapper for details informations on element
         */
        if ($elementEffectsMode) {
            $Tables->appendChild($this->createElement('include', null,
                ['name' => ElcaReportEffectDetailsView::class,
                 'buildMode' => ElcaReportEffectDetailsView::BUILDMODE_WRAPPER,
                 'elementId' => $DataObject->element_id,
                 'm2a' => $m2a,
                 'aggregated' => $this->filterDO? $this->filterDO->aggregated : false,
                 'addPhaseRec' => $addPhaseRec
                ]));


            if ($this->filterDO) {
                $this->appendElementEffectsChart($Container, $DataObject->element_id, $this->filterDO->indicatorId, $this->filterDO->aggregated);
            }

            if ($DataObject->has_element_image) {
                $this->appendElementImage($Container, $DataObject->element_id);
            }
        }
    }
    // End appendEffect



    /**
     * Appends the top elements view
     *
     * @param DOMElement        $Container
     * @param ElcaProjectVariant $ProjectVariant
     * @param float              $m2a
     * @return DOMElement
     */
    private function appendTopElements(DOMElement $Container, ElcaProjectVariant $ProjectVariant, $m2a)
    {
        $Form = new HtmlForm('reportTopForm', '/project-report-effects/topElements/');

        if($this->filterDO)
            $Form->setDataObject($this->filterDO);

        $Form->setRequest(FrontController::getInstance()->getRequest());

        $Group = $Form->add(new HtmlFormGroup(t('Filter')));
        $Select = $Group->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('indicatorId')));
        /** @var ElcaIndicator $Indicator */
        foreach(ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator)
            $Select->add(new HtmlSelectOption(t($Indicator->getName()), $Indicator->getId()));

        $Group->add(new ElcaHtmlFormElementLabel(t('Anzahl Bauteile'), new ElcaHtmlNumericInput('limit')));

        $Radio = $Group->add(new ElcaHtmlFormElementLabel(t('Sortierung nach Wirkung'), new HtmlRadioGroup('order')));
        $Radio->add(new HtmlRadiobox(t('Absteigend'), 'DESC'));
        $Radio->add(new HtmlRadiobox(t('Aufsteigend'), 'ASC'));

        $Radio = $Group->add(new ElcaHtmlFormElementLabel(t('Menge'), new HtmlRadioGroup('inTotal')));
        $Radio->add(new HtmlRadiobox(t('je Bezugsgröße'), '0'));
        $Radio->add(new HtmlRadiobox(t('gesamt'), '1'));

        $Form->appendTo($Container);

        $inTotal = isset($this->filterDO->inTotal)? $this->filterDO->inTotal : true;
        $order   = isset($this->filterDO->order)? $this->filterDO->order : 'DESC';
        $limit   = isset($this->filterDO->limit)? $this->filterDO->limit : 10;

        if(!isset($this->filterDO->indicatorId) || !is_numeric($this->filterDO->indicatorId))
            return $Container;

        $ReportSet = ElcaReportSet::findTopNEffects($this->projectVariantId, $this->filterDO->indicatorId, $inTotal, $order, $limit);

        foreach($ReportSet as $index => $Report)
        {
            $Report->index = $index + 1;
            $Report->element_type = $Report->element_type_din_code . ' ' . t($Report->element_type_name);
            $Report->indicator_value = $Report->indicator_value / max(1, $m2a);

            $Report->element_quantity = ElcaNumberFormat::toString($Report->element_quantity, 2) . ' ' . ElcaNumberFormat::formatUnit($Report->element_ref_unit);
        }

        $Table = new HtmlTable('report report-top-elements');
        $Table->addColumn('index', '#');
        $Table->addColumn('element_name', t('Bauteilkomponente'));
        $Table->addColumn('element_quantity', t('Menge'))->addClass('element_quantity');
        $Table->addColumn('element_type', t('Kostengruppe'));
        $Table->addColumn('composite_element_name', t('Verbaut in Bauteil'));

        $Table->addColumn('indicator_name', t('Indikator'))->addClass('indicator_name');
        $Table->addColumn('indicator_value', t('Gesamt') . ' / m²a')->addClass('indicator_value');
        $Table->addColumn('indicator_unit', t('Einheit'));

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        /**
         * Add m2 NGF Sub in Headline
         */
        $Span = $HeadRow->getColumn('indicator_value')->setOutputElement(new HtmlTag('span', t('Gesamt') . ' / m²'));
        $Span->add(new HtmlTag('sub', t('NGF')));
        $Span->add(new HtmlStaticText('a'));

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();

        $Row->getColumn('element_name')->setOutputElement(new ElcaHtmlReportProjectElementLink('element_name'));
        $Row->getColumn('composite_element_name')->setOutputElement(new ElcaHtmlReportProjectElementLink('composite_element_name'));
        $Row->getColumn('indicator_value')->setOutputElement(new ElcaHtmlNumericText('indicator_value', 10, false, '?', null, null, true));
        $Row->getColumn('indicator_name')->setOutputElement(new HtmlText('indicator_name', new ElcaTranslatorConverter()));
        $Row->getColumn('indicator_unit')->setOutputElement(new HtmlText('indicator_unit', new ElcaTranslatorConverter()));

        $Body->setDataSet($ReportSet);
        $Table->appendTo($Container);

        return $Container;
    }
    // End appendTopElements


    /**
     * Appends the top processes view
     *
     * @param  DOMElement $Container
     * @return DOMElement
     */
    private function appendTopProcesses(DOMElement $Container, ElcaProjectVariant $ProjectVariant, $m2a)
    {
        $Form = new HtmlForm('reportTopForm', '/project-report-effects/topProcesses/');

        if($this->filterDO)
            $Form->setDataObject($this->filterDO);

        $Form->setRequest(FrontController::getInstance()->getRequest());

        $Group = $Form->add(new HtmlFormGroup(t('Filter')));
        $Select = $Group->add(new ElcaHtmlFormElementLabel(t('Indikator'), new HtmlSelectbox('indicatorId')));
        foreach(ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId()) as $Indicator)
            $Select->add(new HtmlSelectOption(t($Indicator->getName()), $Indicator->getId()));

        $Group->add(new ElcaHtmlFormElementLabel(t('Anzahl Baustoffe'), new ElcaHtmlNumericInput('limit')));

        $Radio = $Group->add(new ElcaHtmlFormElementLabel(t('Sortierung nach Wirkung'), new HtmlRadioGroup('order')));
        $Radio->add(new HtmlRadiobox(t('Absteigend'), 'DESC'));
        $Radio->add(new HtmlRadiobox(t('Aufsteigend'), 'ASC'));

        $Form->appendTo($Container);

        $order   = isset($this->filterDO->order)? $this->filterDO->order : 'DESC';
        $limit   = isset($this->filterDO->limit)? $this->filterDO->limit : 10;

        if(!isset($this->filterDO->indicatorId) || !is_numeric($this->filterDO->indicatorId))
            return;

        $ReportSet = ElcaReportSet::findTopNProcessConfigEffects($this->projectVariantId, $this->filterDO->indicatorId, $order, $limit);

        foreach($ReportSet as $index => $Report)
        {
            $Report->index = $index + 1;
            $Report->indicator_value = $Report->indicator_value / max(1, $m2a);
        }

        $Table = new HtmlTable('report report-top-processes');
        $Table->addColumn('index', '#');
        $Table->addColumn('process_config_name', t('Baustoff'));
        $Table->addColumn('indicator_name', t('Indikator'))->addClass('indicator_name');
        $Table->addColumn('indicator_value', t('Gesamt') . ' / m²a')->addClass('indicator_value');
        $Table->addColumn('indicator_unit', t('Einheit'));

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        /**
         * Add m2 NGF Sub in Headline
         */
        $Span = $HeadRow->getColumn('indicator_value')->setOutputElement(new HtmlTag('span', t('Gesamt') . ' / m²'));
        $Span->add(new HtmlTag('sub', t('NGF')));
        $Span->add(new HtmlStaticText('a'));

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();

        $Row->getColumn('indicator_value')->setOutputElement(new ElcaHtmlNumericText('indicator_value', 10, false, '?', null, null, true));
        $Row->getColumn('indicator_unit')->setOutputElement(new HtmlText('indicator_unit', new ElcaTranslatorConverter()));
        $Row->getColumn('indicator_name')->setOutputElement(new HtmlText('indicator_name', new ElcaTranslatorConverter()));

        $Body->setDataSet($ReportSet);
        $Table->appendTo($Container);

        return $Container;
    }
    // End appendTopProcesses


    /**
     * Appends the container for the element effects cahrt
     *
     * @param  DOMElement $Container
     * @param  array $dataSet
     * @return -
     */
    protected function appendElementEffectsChart(DOMElement $Container, $elementId, $indicatorId, $aggregated = false)
    {
        $args = ['e' => $elementId,
                      'i' => $indicatorId,
                      'a' => (int)$aggregated];

        $attributes = ['class' => 'chart stacked-bar-chart',
                            'data-url' => Url::factory('/elca/project-report-effects/elementChart/', $args)];;

        $Container->appendChild($this->getDiv($attributes));
    }

    /**
     * @param ElcaReportSet $reportSet
     * @param               $m2a
     * @param               $isEn15804Compliant
     * @return array
     */
    private function prepareElementEffects(ElcaReportSet $reportSet, $m2a, $isEn15804Compliant)
    {
        $list    = new ArrayOfObjects($reportSet->getArrayCopy());
        $reports = $list->groupBy(
            [
                'element_type_din_code',
                'element_id',
                'indicator_id'
            ],
            function ($current, $item) use ($m2a, $isEn15804Compliant) {
                $current->element_type_name   = $item->element_type_name;
                $current->element_parent_name = $item->element_type_parent_name;
                $current->element_id          = $item->element_id;
                $current->element_name        = $item->element_name;
                $current->element_quantity    = $item->element_quantity;
                $current->element_ref_unit    = $item->element_ref_unit;
                $current->has_element_image   = $item->has_element_image;

                if (!isset($current->phases)) {
                    $current->phases = [
                        ElcaLifeCycle::PHASE_PROD  => 0,
                        ElcaLifeCycle::PHASE_MAINT => 0,
                        ElcaLifeCycle::PHASE_EOL   => 0,
                        ElcaLifeCycle::PHASE_REC   => 0,
                        ElcaLifeCycle::PHASE_TOTAL => 0,
                    ];
                }

                $current->phases[$item->life_cycle_phase] += $item->indicator_value / $m2a;

                if (!isset($current->indicator)) {
                    $current->indicator = new Indicator(
                        new IndicatorId($item->indicator_id),
                        $item->indicator_name,
                        new IndicatorIdent($item->indicator_name),
                        $item->indicator_unit,
                        $isEn15804Compliant
                    );
                }
            }
        );

        return $reports;
    }
    // End appendElementEffectsChart


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
// End ElcaReportEffectsView
