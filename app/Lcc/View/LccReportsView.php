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
namespace Lcc\View;

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTextArea;
use DOMElement;
use Elca\Db\ElcaBenchmarkSystemSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantAttribute;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlReportBar;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\Report\ElcaReportsView;
use Lcc\Db\LccCost;
use Lcc\Db\LccProjectCostProgressionSet;
use Lcc\Db\LccProjectVersion;
use Lcc\LccModule;
use Lcc\Service\BenchmarkService;

/**
 * Builds the summary report for life cycle costs
 *
 * @package lcc
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccReportsView extends ElcaReportsView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_SUMMARY  = 'summary';
    const BUILDMODE_PROGRESSION  = 'progression';

    private $calcMethod;
    private $buildMode;

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * Grouping captions
     *
     * @translate array Lcc\View\LccBenchmark::$groupingCaptions
     */
    private static $groupingCaptions = [
        LccCost::GROUPING_PROD     => 'Herstellkosten KG ',
        LccCost::GROUPING_WATER    => 'Barwert Nutzungskosten Wasser/ Abwasser',
        LccCost::GROUPING_ENERGY   => 'Barwert Nutzungskosten Energie',
        LccCost::GROUPING_CLEANING => 'Barwert Nutzungskosten Reinigung',
        LccCost::GROUPING_KGR      => 'Barwert regelmäßige Instandhaltungskosten KG ',
        LccCost::GROUPING_KGU      => 'Barwert unregelmäßige Zahlungen KG ',
        'total'                    => 'Barwert Gesamt',
        'totalPerBgf'              => 'Lebenszykluskosten / m²BGF',
        'totalPoints'              => 'Punkte Kriterium 2.1.1',
    ];

    /**
     * @translate array Lcc\View\LccBenchmark::$groupingShortNames
     */
    private static $groupingShortNames = [
        LccCost::GROUPING_PROD     => 'KG ',
        LccCost::GROUPING_WATER    => 'Wasser/ Abwasser',
        LccCost::GROUPING_ENERGY   => 'Energie',
        LccCost::GROUPING_CLEANING => 'Reinigung',
        LccCost::GROUPING_KGR      => 'Regelm. KG ',
        LccCost::GROUPING_KGU      => 'Unregelm. KG',
    ];

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_SUMMARY);
        $this->calcMethod = $this->get('calcMethod', LccModule::CALC_METHOD_GENERAL);
        $this->benchmarkVersionId = $this->get('benchmarkVersionId');
    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement $container
     */
    protected function renderReports(DOMElement $container, DOMElement $infoDl, ElcaProjectVariant $projectVariant, $lifeTime)
    {
        $this->addClass($container, 'lcc-reports lcc-report-'.$this->buildMode);

        $projectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);
        $projectVersion = LccProjectVersion::findByPK($this->projectVariantId, $this->calcMethod);

        $infoDl->appendChild($this->getDt([], t('Bezugsfläche (BGF):').' '));
        $infoDl->appendChild($this->getDd([], ElcaNumberFormat::toString($projectConstruction->getGrossFloorSpace()).' m²'));
        $infoDl->appendChild($this->getDt([], t('Preisstand:').' '));
        $infoDl->appendChild($this->getDd([], $projectVersion->getVersion()->getName()));
        $infoDl->appendChild($this->getDt([], t('Kategorie Sonderbedingungen:').' '));
        $infoDl->appendChild($this->getDd([], $projectVersion->getCategory()));

        $tdContainer = $this->appendPrintTable($container);

        switch($this->buildMode)
        {
            case self::BUILDMODE_PROGRESSION:
                $this->appendForm($tdContainer, '/lcc/reports/progress/');
                $this->buildProgression($tdContainer);
                break;

            default:
            case self::BUILDMODE_SUMMARY:
                $this->appendBenchmarkVersionSelect($infoDl, $projectVariant, $tdContainer);
                $this->appendForm($tdContainer, '/lcc/reports/');
                $this->buildSummary($tdContainer, $projectVersion, $projectConstruction->getGrossFloorSpace());
                break;
        }
    }

    protected function appendBenchmarkVersionSelect(DOMElement $infoDl, ElcaProjectVariant $projectVariant, $tdContainer
    ): void {
        $project = $projectVariant->getProject();
        if (!$project->getBenchmarkVersionId()) {
            $form = new HtmlForm('reportForm', '/lcc/reports/');
            $form->setRequest(FrontController::getInstance()->getRequest());
            $select = $form->add(
                new ElcaHtmlFormElementLabel(t('Benchmarksystem'), new HtmlSelectbox('benchmarkVersionId'))
            );
            $select->add(new HtmlSelectOption('-- '.t('Kein Benchmark').' --', ''));

            $isEn15804Compliant = $project->getProcessDb()->isEn15804Compliant();

            $benchmarkSystems = ElcaBenchmarkSystemSet::find(['is_active' => true], ['name' => 'ASC']);
            foreach ($benchmarkSystems as $benchmarkSystem) {
                /** @var ElcaBenchmarkVersion $benchmarkVersion */
                foreach (
                    ElcaBenchmarkVersionSet::find(
                        ['benchmark_system_id' => $benchmarkSystem->getId(), 'is_active' => true],
                        ['name' => 'ASC', 'id' => 'ASC']
                    ) as $benchmarkVersion
                ) {
                    $processDb = $benchmarkVersion->getProcessDb();

                    /** offer only compliant benchmarks */
                    if ($processDb->isEn15804Compliant(
                        ) !== $isEn15804Compliant || $benchmarkVersion->getUseReferenceModel()
                    ) {
                        continue;
                    }

                    $opt = $select->add(
                        new HtmlSelectOption(
                            $benchmarkSystem->getName().' - '.$benchmarkVersion->getName(),
                            $benchmarkVersion->getId()
                        )
                    );

                    if ($this->benchmarkVersionId == $benchmarkVersion->getId()) {
                        $opt->setAttribute('selected', 'selected');
                        $infoDl->appendChild($this->getDt(['class' => 'print'], t('Benchmarksystem').': '));
                        $infoDl->appendChild(
                            $this->getDd(
                                ['class' => 'print'],
                                $benchmarkSystem->getName().' - '.$benchmarkVersion->getName()
                            )
                        );
                    }
                }
            }

            $form->appendTo($tdContainer);
        } else {
            $benchmarkVersion = $projectVariant->getProject()->getBenchmarkVersion();
            $benchmarkSystem  = $benchmarkVersion->getBenchmarkSystem();
            $infoDl->appendChild($this->getDt([], t('Benchmarksystem').': '));
            $infoDl->appendChild(
                $this->getDd([], $benchmarkSystem->getName().' - '.$benchmarkVersion->getName())
            );
        }
    }
    // End beforeRender


    /**
     * Builds the summary
     *
     * @param  DOMElement $container
     * @return -
     */
    private function buildSummary(DOMElement $container, LccProjectVersion $projectVersion, $bgf)
    {
        $summary = Environment::getInstance()->getContainer()->get(BenchmarkService::class)->summary($projectVersion, $this->benchmarkVersionId);
        $totalCosts = !empty($summary) ? max(1, $summary['total'][0]->costs) : 0;

        $table = new HtmlTable('report report-effects');
        $table->addColumn('name', t('Aufteilung der LCC-Kosten'));
        $table->addColumn('costs', t('Kosten'));
        $table->addColumn('unit', t('Einheit'));
        $table->addColumn('percentage', '%');
        $table->addColumn('bar', '');

        $head = $table->createTableHead();
        $tableRow = $head->addTableRow(new HtmlTableHeadRow());
        $tableRow->addClass('table-headlines');

        $pieData = [];

        // add table bodies for each group
        foreach($summary as $cssGrouping => $subData)
        {
            $body = $table->createTableBody();
            $body->addClass($cssGrouping);
            $row = $body->addTableRow();
            $costs = $row->getColumn('costs');
            $costs->addClass('costs');
            $costs->setOutputElement(new ElcaHtmlNumericText('costs', 2));
            $row->getColumn('unit')->addClass('unit');

            // calculate percentages
            if($cssGrouping !== 'total' && $cssGrouping !== 'rating')
            {
                foreach($subData as $DO)
                {
                    $DO->percentage = $DO->costs / $totalCosts;
                    $DO->bar = round($DO->percentage * 100);

                    if($DO->bar > 0)
                        $pieData[] = (object)['name' => $DO->shortName, 'tooltip' => $DO->name.' - '. ElcaNumberFormat::toString($DO->costs,2) .' '. $DO->unit, 'value' => $DO->costs];
                }

                $percentage = $row->getColumn('percentage');
                $percentage->addClass('percentage');
                $percentage->setOutputElement(new ElcaHtmlNumericText('percentage', 2, true));

                $row->getColumn('bar')->setOutputElement(new ElcaHtmlReportBar('bar'));
            }

            $body->setDataSet($subData);
        }
        $table->appendTo($container);

        $container->appendChild($this->getDiv(['class' => 'chart pie-chart', 'data-values' => json_encode($pieData)]));

        if ($this->benchmarkVersionId) {
            $this->appendBenchmarkComment($container);
        }
    }
    // End buildSummary


    /**
     * Builds the progression chart
     *
     * @param  DOMElement $Container
     * @return -
     */
    private function buildProgression(DOMElement $Container)
    {
        // expand captions
        $captions = [];
        foreach(self::$groupingShortNames as $pattern => $caption)
        {
            if($pattern == 'total')
                continue;

            if($pattern == LccCost::GROUPING_WATER || $pattern == LccCost::GROUPING_ENERGY || $pattern == LccCost::GROUPING_CLEANING)
                $captions[$pattern] = $caption;

            else
            {
                foreach([300,400,500] as $code)
                    $captions[$pattern.$code] = $caption . $code;
             }
         }

        // Progression data
        $ProgressionSet = LccProjectCostProgressionSet::find(['project_variant_id' => $this->projectVariantId, 'calc_method' => $this->calcMethod], ['grouping' => 'ASC', 'life_time' => 'ASC']);

        $sums = [];
        $series = [];
        foreach($ProgressionSet as $Prog)
        {
            $grouping = $Prog->getGrouping();

            if(!isset($series[$grouping]))
            {
                $series[$grouping] = $DO = new \stdClass();
                $DO->id = $Prog->getGrouping();
                $DO->name = $captions[$Prog->getGrouping()];
                $DO->values = [];
                $sums[$grouping] = 0;
            }
            else
                $DO = $series[$grouping];

            $Val = $DO->values[] = new \stdClass();
            $Val->lifeTime = $Prog->getLifeTime();
            $sums[$grouping] += $Val->val = round($Prog->getQuantity(), 2);
            $Val->tooltip = ElcaNumberFormat::toString($Val->val).' €';
        }

        foreach($sums as $grouping => $sum)
        {
            if($sum == 0)
                unset($series[$grouping]);
        }

        $Container->appendChild($this->getDiv(['class' => 'chart line-chart', 'data-values' => json_encode(array_values($series))]));
    }
    // End buildProgression


    /**
     * @param DOMElement         $container
     * @param string             $url
     */
    protected function appendForm(DOMElement $container, $url)
    {
        $access = ElcaAccess::getInstance();

        if (false === ($access->hasAdminPrivileges() || $access->hasRoles([LccModule::ROLE, Elca::ELCA_ROLE_ORGA]))) {
            return;
        }

        $Form = new HtmlForm('reportForm', $url);
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject((object)['calcMethod' => $this->calcMethod]);

        $Radio = $Form->add(new ElcaHtmlFormElementLabel(t('Methode'), new HtmlRadioGroup('calcMethod')));
        $Radio->add(new HtmlRadiobox(t('vereinfacht'), LccModule::CALC_METHOD_GENERAL));
        $Radio->add(new HtmlRadiobox(t('ausführlich'), LccModule::CALC_METHOD_DETAILED));

        $Form->appendTo($container);
    }
    // End appendForm

    private function appendBenchmarkComment(DOMElement $container)
    {
        $comment = ElcaProjectVariantAttribute::findValue(
            $this->projectVariantId,
            LccModule::ATTRIBUTE_IDENT_LCC_BENCHMARK_COMMENT .'_'. $this->calcMethod
        );

        if (!empty($comment)) {
            $printContainer = $container->appendChild($this->getDiv(['class' => 'benchmark-print-comment clear']));
            $printContainer->appendChild($this->getH3(t('Anmerkungen')));

            $this->appendMultilineAsPTags($printContainer, $comment, true);
        }

        $form = new HtmlForm('reportForm', '/lcc/reports/');
        $form->addClass('clear');
        $form->setRequest(FrontController::getInstance()->getRequest());

        $form->add(
            new ElcaHtmlFormElementLabel(
                t('Anmerkungen'),
                new HtmlTextArea(
                    'comment',
                    $comment
                )
            )
        );

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clear buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        $form->appendTo($container);
    }
}
// End LccReportsView
