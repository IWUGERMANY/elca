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

use Beibob\Blibs\Config;
use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlTag;
use Elca\Controller\Admin\BenchmarksCtrl;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * ElcaAdminBenchmarkVersionView
 *
 * @package eLCA
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 */
class ElcaAdminBenchmarkVersionLcaView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECTOR = 'selector';

    /** @var array $refProcessConfigIdents */
    private static $refProcessConfigIdents = [
        ElcaBenchmarkRefProcessConfig::IDENT_HEATING        => 'Wärme',
        ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY    => 'Strom',
        ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY => 'Prozessenergie',
    ];

    private static $hideIndicators = [
        ElcaIndicator::IDENT_PERM  => true,
        ElcaIndicator::IDENT_PERE  => true,
        ElcaIndicator::IDENT_PENRM => true,
        ElcaIndicator::IDENT_PENRE => true,
    ];

    /** @var  string $buildMode */
    private $buildMode;

    /**
     * @var int $benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * Data
     *
     * @var object $Data
     */
    private $Data;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        $this->setTplName('elca_admin_benchmark_version');

        parent::init($args);

        $this->buildMode          = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->benchmarkVersionId = $this->get('benchmarkVersionId');

        $this->Data = $this->get('Data', new \stdClass());
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
        $Container = $this->getElementById('tabContent');

        $formId = 'adminBenchmarkVersionForm';
        $Form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveBenchmarkVersionThresholds/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');

        if ($this->Data) {
            $Form->setDataObject($this->Data);
            $Form->add(new HtmlHiddenField('id', $this->benchmarkVersionId));
        }

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
        }
        $Form->setRequest(FrontController::getInstance()->getRequest());

        switch ($this->buildMode) {
            case self::BUILDMODE_SELECTOR:
                $idents = array_keys($this->Data->refProcessConfigId);
                $ident  = $idents[0];

                $Group   = $Form->add(new HtmlFormGroup(t('Referenzgebäude')));
                $Element = $this->appendRefProcessConfigSelector(
                    $Group,
                    $ident,
                    t(self::$refProcessConfigIdents[$ident])
                );
                $Element->addClass('changed');
                $Form->appendTo($Container);

                $Content = $this->getElementById('refProcessConfigIdSelector_'.$ident);
                $this->replaceChild($Content, $Container);
                break;

            case self::BUILDMODE_DEFAULT:
            default:
                $RadioGroup = $Form->add(
                    new ElcaHtmlFormElementLabel(
                        t('Bewertung nach'),
                        new HtmlRadioGroup('useReferenceModel')
                    )
                );
                $RadioGroup->add(new HtmlRadiobox(t('Festwertverfahren'), false));
                $RadioGroup->add(new HtmlRadiobox(t('Referenzgebäudeverfahren'), true));

                $this->appendRefProcessConfigs($Form);
                /**
                 * Add buttons
                 */
                $ButtonGroup = $Form->add(new HtmlFormGroup(''));
                $ButtonGroup->addClass('buttons');
                $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

                $this->appendThresholds($Form, (bool)$Form->getElementValue('useReferenceModel'));

                /**
                 * Add buttons
                 */
                $ButtonGroup = $Form->add(new HtmlFormGroup(''));
                $ButtonGroup->addClass('buttons');
                $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
                $Form->appendTo($Container);
        }
    }
    // End beforeRender

    /**
     * @param HtmlForm $Form
     */
    private function appendRefProcessConfigs(HtmlForm $Form)
    {
        if (!$Form->getElementValue('useReferenceModel')) {
            return;
        }

        $Group = $Form->add(new HtmlFormGroup(t('Referenzgebäude')));
        $Group->addClass('reference-model');

        foreach (self::$refProcessConfigIdents as $ident => $caption) {
            $this->appendRefProcessConfigSelector($Group, $ident, t($caption));
        }

        /**
         * Append reference construction values
         */
        $this->appendConstrValuesRows(
            $Group,
            ElcaIndicatorSet::findWithPetByProcessDbId(
                $this->Data->processDbId,
                false,
                false,
                ['p_order' => 'ASC']
            )
        );
    }
    // End appendRefProcessConfigs

    /**
     * @param HtmlFormGroup $Group
     * @param string        $ident
     * @param string        $caption
     *
     * @return HtmlElement
     */
    private function appendRefProcessConfigSelector(HtmlFormGroup $Group, $ident, $caption)
    {
        $SelectorLabel = $Group->add(new ElcaHtmlFormElementLabel($caption));
        $SelectorLabel->setAttribute('id', 'refProcessConfigIdSelector_'.$ident);
        $Selector = $SelectorLabel->add(new ElcaHtmlProcessConfigSelectorLink('refProcessConfigId['.$ident.']'));
        $Selector->addClass('process-config-selector');
        $Selector->setRelId($this->benchmarkVersionId);
        $Selector->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_OPERATION);
        $Selector->setData($ident);
        $Selector->setContext(BenchmarksCtrl::CONTEXT);

        return $SelectorLabel;
    }
    // End appendRefProcessConfigSelector

    /**
     * @param HtmlForm $Form
     * @param bool     $useReferenceModel
     */
    private function appendThresholds(HtmlForm $Form, $useReferenceModel = false)
    {
        $H3   = $Form->add(
            new HtmlTag(
                'h3',
                t('Schwellenwerte nach ').($useReferenceModel ? t('Referenzgebäudeverfahren') : t('Festwertverfahren'))
            )
        );
        $Link = $H3->add(new HtmlLink(t('Alle Eingabefelder leeren')));
        $Link->addClass('clear-fields no-xhr');
        $Link->setAttribute('rel', 'clearFields');

        $Link = $H3->add(new HtmlLink(t('Vorgabewerte verwenden')));
        $Link->addClass('clear-fields no-xhr');
        $Link->setAttribute('rel', 'useDefaults');

        $Form->add(
            new HtmlTag(
                'p',
                t(
                    'Es müssen mindestens zwei Schwellenwerte - das Maximum und das Minimum - pro Wirkindikator spezifiziert werden. Zwischen den Werten wird interpoliert.'
                )
            )
        );

        $IndicatorSet = ElcaIndicatorSet::findWithPetByProcessDbId(
            $this->Data->processDbId,
            false,
            false,
            ['p_order' => 'ASC']
        );

        $indicators = $peEmIndicators = [];

        /** @var ElcaIndicator $Indicator */
        foreach ($IndicatorSet as $Indicator) {
            if (in_array($Indicator->getIdent(), ElcaIndicator::$primaryEnergyRenewableIndicators)) {
                $peEmIndicators[] = $Indicator;
            } else {
                $indicators[] = $Indicator;
            }
        }

        /**
         * Append indicator thresholds
         */
        $this->appendThresholdRows($Form, $indicators, 100, 10, $useReferenceModel);

        /**
         * Append peEm indicator thresholds
         */
        $this->appendThresholdRows($Form, $peEmIndicators, 50, 5, $useReferenceModel);

    }
    // End appendThresholds


    /**
     * Appends the benchmark thresholds
     *
     * @param HtmlForm $Form
     * @param array    $indicators
     * @param int      $scoreMax
     * @param int      $scoreDecrement
     * @param bool     $useReferenceModel
     *
     * @return void
     */
    private function appendThresholdRows(
        HtmlForm $Form, array $indicators, $scoreMax = 100, $scoreDecrement = 10, $useReferenceModel = false
    ) {
        if (!$this->Data || !isset($this->benchmarkVersionId)) {
            return;
        }

        $bnbDefaults = Environment::getInstance()->getContainer()->get(BenchmarkService::class)
                                  ->getDefaultValues(
                                      ElcaBenchmarkVersion::findById($this->benchmarkVersionId),
                                      $useReferenceModel
                                  );

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('benchmark-thresholds-group');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        for ($score = $scoreMax; $score > 0; $score -= $scoreDecrement) {
            $Row->add(new HtmlTag('h5', $score, ['class' => 'hl-benchmark']));
        }
        $fifthScoreDecrement = $scoreDecrement / 5;
        for ($score = $scoreDecrement - $fifthScoreDecrement; $score > 0; $score -= $fifthScoreDecrement) {
            $Row->add(new HtmlTag('h5', $score, ['class' => 'hl-benchmark']));
        }

        $Ul = $Group->add(new HtmlTag('ul', null));

        $Validator = $this->get('Validator');
        foreach ($indicators as $Indicator) {
            $property = $Indicator->getIdent();

            if (isset(self::$hideIndicators[$property])) {
                continue;
            }
            $indicatorIdent = new IndicatorIdent($property);
            $unit           = $useReferenceModel ? null : ($scoreMax === 50 ? '%' : $Indicator->getUnit());

            $Li    = $Ul->add(new HtmlTag('li', null, ['class' => 'indicator-values']));
            $Label = $Li->add(
                new ElcaHtmlFormElementLabel(
                    t($Indicator->getName()),
                    null,
                    false,
                    $indicatorIdent->isPrimaryEnergyIndicator() && false === $indicatorIdent->isRenewablePrimaryEnergy()
                        ? Elca::UNIT_KWH
                        : $unit,
                    t($Indicator->getDescription())
                )
            );

            if ($Validator && $Validator->hasError($property)) {
                $Label->addClass('error');
            }

            for ($score = $scoreMax; $score > 0; $score -= $scoreDecrement) {
                $this->appendInputField($Label, $property, $score, $bnbDefaults);
            }
            for ($score = $scoreDecrement - $fifthScoreDecrement; $score > 0; $score -= $fifthScoreDecrement) {
                $this->appendInputField($Label, $property, $score, $bnbDefaults);
            }
        }
    }
    // End appendThresholdRows


    /**
     * Appends the benchmark construction values row
     *
     * @param HtmlFormGroup    $Group
     * @param ElcaIndicatorSet $Indicators
     *
     * @return void
     */
    private function appendConstrValuesRows(HtmlFormGroup $Group, ElcaIndicatorSet $IndicatorSet)
    {
        if (!$this->Data || !$this->Data->refConstrValue || !isset($this->benchmarkVersionId)) {
            return;
        }

        $Validator  = $this->get('Validator');
        $indicators = $IndicatorSet->getArrayCopy();
        $rounds     = 0;
        while ($slice = array_splice($indicators, 0, 9)) {

            /**
             * Headline
             */
            $HlRow = $Group->add(new HtmlTag('div'));
            $HlRow->addClass('hl-row clearfix');

            $ValRow = $Group->add(new HtmlTag('div'));
            $ValRow->addClass('constr-values clearfix');

            foreach ($slice as $Indicator) {
                $H5 = $HlRow->add(new HtmlTag('h5', null, ['class' => 'hl-benchmark']));
                $H5->add(new HtmlTag('span', $Indicator->getName(), ['class' => 'indicator-name']));
                $H5->add(new HtmlTag('span', $Indicator->getUnit(), ['class' => 'indicator-unit']));
            }

            $Label = $ValRow->add(
                new ElcaHtmlFormElementLabel(
                    $rounds == 0 ? t('Konstruktion') : ' ',
                    null,
                    false,
                    $rounds == 0 ? '/ NGFa' : null
                )
            );

            if ($Validator && $Validator->hasError('refConstrValue')) {
                $Label->addClass('error');
            }

            foreach ($slice as $Indicator) {
                $Label->add(new ElcaHtmlNumericInput('refConstrValue['.$Indicator->getId().']'));
            }
            $rounds++;
        }
    }

    /**
     * @param $label
     * @param $property
     * @param $score
     * @param $bnbDefaults
     * @return void
     */
    private function appendInputField(HtmlElement $label, $property, $score, Config $bnbDefaults)
    {
        $input = new ElcaHtmlNumericInput($property.'['.$score.']');
        $label->add($input);

        if (isset($bnbDefaults->$property) && isset($bnbDefaults->$property->$score)) {
            $input->setAttribute('data-default', ElcaNumberFormat::toString($bnbDefaults->$property->$score));
        }

//        if (isset(self::$indicatorMaxScores[$property]) && $score > self::$indicatorMaxScores[$property]) {
//            $input->setReadonly(true, true);
//        }
    }
    // End appendConstrValuesRows

}
// End ElcaAdminBenchmarkVersionView