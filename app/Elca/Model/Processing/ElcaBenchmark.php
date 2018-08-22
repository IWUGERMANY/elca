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
namespace Elca\Model\Processing;

use Beibob\Blibs\Config;
use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\FloatCalc;
use Elca\Controller\Admin\BenchmarksCtrl;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkThreshold;
use Elca\Db\ElcaBenchmarkThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\Db\ElcaSetting;
use Elca\Db\ElcaSettingSet;
use Elca\Elca;

/**
 * Helper file for benchmark calculations
 */
class ElcaBenchmark
{
    /**
     * Instance
     */
    private static $instances = [];

    /**
     * @var ElcaBenchmarkVersion $BenchmarkVersion
     */
    private $BenchmarkVersion;

    /**
     * Benchmark cache
     */
    private $cache = [];

    /**
     * Returns an benchmark instance for the given process db
     *
     * @param ElcaBenchmarkVersion $BenchmarkVersion
     * @return ElcaBenchmark
     */
    public static function getInstance(ElcaBenchmarkVersion $BenchmarkVersion)
    {
        $versionId = $BenchmarkVersion->getId();

        if(!isset(self::$instances[$versionId]))
            self::$instances[$versionId] = new ElcaBenchmark($BenchmarkVersion);

        return self::$instances[$versionId];
    }
    // End getInstance


	/**
	 * @param $projectVariantId
	 *
	 * @return array
	 */
	public function compute($projectVariantId)
	{
		$ProjectVariant = ElcaProjectVariant::findById($projectVariantId);
		$m2a = $this->getM2aValue($ProjectVariant);

		/**
		 * Depending on which calculation model to use get a data set
		 */
		if ($this->BenchmarkVersion->getUseReferenceModel()) {
			$results = $this->computeRefModelValues($ProjectVariant, $m2a);
		}
		else {
			$results = $this->computeFixedValues($ProjectVariant, $m2a);
		}

		return $results;
	}
	// End compute


	/**
	 * @param $projectVariantId
	 *
	 * @return array
	 */
	public function computeProjection($projectVariantId)
	{
		$ProjectVariant = ElcaProjectVariant::findById($projectVariantId);
		$m2a = $this->getM2aValue($ProjectVariant);

		$dataSets = [];

		/**
		 * Depending on which calculation model to use get a data set
		 */
		if ($this->BenchmarkVersion->getUseReferenceModel()) {
			$ConstrValues = $this->getConstrProjectionValues($ProjectVariant);
			$opValues = ElcaReportSet::findTotalEffectsPerLifeCycle($ProjectVariant->getId(), ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP])->getArrayBy('value', 'ident');

			$refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId($this->BenchmarkVersion->getId())->getArrayBy('value', 'indicatorId');
			$refOpValues = ElcaReportSet::findFinalEnergyRefModelEffects($ProjectVariant->getId())->getArrayBy('value', 'indicator_id');

			foreach ($ConstrValues as $ConstrValue) {
				$id = $ConstrValue->indicatorId;
				$ident = $ConstrValue->indicatorIdent;
				foreach (['min', 'max', 'avg'] as $name) {
					if (!isset($opValues[$ident]) ||
						!isset($refConstrValues[$id]) ||
						!isset($refOpValues[$id]))
						continue;

					$dataSets[$name][$ConstrValue->indicatorIdent] = ($ConstrValue->$name * $m2a + $opValues[$ident]) / ($refConstrValues[$id] * $m2a + $refOpValues[$id]);
				}
			}
		}
		else {
			$ConstrValues = $this->getConstrProjectionValues($ProjectVariant);
			$opValues = ElcaReportSet::findTotalEffectsPerLifeCycle($ProjectVariant->getId(), ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP])->getArrayBy('value', 'ident');

			foreach ($ConstrValues as $ConstrValue) {
				foreach (['min', 'max', 'avg'] as $name) {
					if (!isset($opValues[$ConstrValue->indicatorIdent]))
						continue;
					$dataSets[$name][$ConstrValue->indicatorIdent] = $ConstrValue->$name + $opValues[$ConstrValue->indicatorIdent] / $m2a;
				}
			}
		}

		$results = [];
		foreach ($dataSets as $name => $dataSet)
			$results[$name] = $this->computeIndicators($dataSet);

		return $results;
	}
	// End computeProjection


	/**
	 * Returns the default values
	 *
	 * @param bool $useReferenceModel
	 *
	 * @return Config
	 */
	public function getDefaultValues($useReferenceModel = null)
	{
		$useReferenceModel = is_null($useReferenceModel)? $this->BenchmarkVersion->getUseReferenceModel() : $useReferenceModel;
		return Elca::getInstance()->getDefaults($useReferenceModel? 'bnb-ref' : 'bnb-static');
	}
	// End getDefaultValues


	/**
	 * Inits the benchmark version with default values from defaults.ini
	 */
	public function initWithDefaultValues()
	{
		if(!$this->BenchmarkVersion->isInitialized())
			return;

		$BnbDefaults = $this->getDefaultValues();

		/** @var ElcaIndicator $Indicator */
		foreach ($BnbDefaults as $ident => $values) {
			$indicatorId = ElcaIndicator::findByIdent($ident)->getId();
			foreach ($values as $score => $value) {

				$Threshold = ElcaBenchmarkThreshold::findByBenchmarkVersionIdAndIndicatorIdAndScore($this->BenchmarkVersion->getId(), $indicatorId, $score);
				if ($Threshold->isInitialized()) {
					$Threshold->setValue($value);
					$Threshold->update();
				}
				else {
					ElcaBenchmarkThreshold::create( $this->BenchmarkVersion->getId(), $indicatorId, $score, $value );
				}
			}
		}


	}
	// End initWithDefaultValues

    /**
     *
     */
    public function initLifeCycleUsageSpecification()
    {
        if (null === $this->BenchmarkVersion->getProcessDbId()) {
            return;
        }

        $lifeCycles = ElcaLifeCycleSet::findByProcessDbId(
            $this->BenchmarkVersion->getProcessDbId(),
            ['p_order' => 'ASC']
        )->getArrayCopy('ident');

        $allLcIdents = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
        );

        // iterate over lifecycles to keep order
        foreach ($allLcIdents as $lcIdent => $lifeCycle) {

            if (!isset($lifeCycles[$lcIdent]) ||
                ElcaBenchmarkLifeCycleUsageSpecification::existsByBenchmarkVersionIdAndLifeCycleIdent(
                    $this->BenchmarkVersion->getId(), $lcIdent)
            ) {
                continue;
            }

            ElcaBenchmarkLifeCycleUsageSpecification::create(
                $this->BenchmarkVersion->getId(),
                $lcIdent,
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults[$lcIdent],
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults[$lcIdent],
                isset(ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]) && ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults[$lcIdent]
            );
        }
    }


    /**
	 * @param array $indicatorValues
	 * @param string $indicatorIdent
	 *
	 * @return float|null
	 */
    private function computeRenewablePrimaryEnergy(array $indicatorValues, $indicatorIdent)
    {
        if(!isset($this->cache[$indicatorIdent])) {
            return null;
        }

        $total = 0;
        switch($indicatorIdent) {
            case ElcaIndicator::IDENT_PE_EM:
                if(!isset($indicatorValues[$indicatorIdent]) ||
                   !isset($indicatorValues[ElcaIndicator::IDENT_PE_N_EM])) {
                    return null;
                }

                $total = $indicatorValues[ElcaIndicator::IDENT_PE_EM] + $indicatorValues[ElcaIndicator::IDENT_PE_N_EM];
                break;

            case ElcaIndicator::IDENT_PERE:
            case ElcaIndicator::IDENT_PERM:
                if(!isset($indicatorValues[$indicatorIdent]) ||
                    !isset($indicatorValues[ElcaIndicator::IDENT_PERT]) ||
                    !isset($indicatorValues[ElcaIndicator::IDENT_PENRT])) {
                    return null;
                }

                $total = $indicatorValues[ElcaIndicator::IDENT_PERT] + $indicatorValues[ElcaIndicator::IDENT_PENRT];
        }

        if(!$total)
            return null;

        $value = $indicatorValues[$indicatorIdent] / $total * 100;

        return FloatCalc::computeBenchmark($this->cache[$indicatorIdent], $value);
    }
    // End computeRenewablePrimaryEnergy


	/**
	 * @param ElcaProjectVariant $ProjectVariant
	 * @param float              $m2a
	 *
	 * @return array
	 */
	private function computeFixedValues(ElcaProjectVariant $ProjectVariant, $m2a)
	{
		$dataSet = ElcaReportSet::findTotalEffects($ProjectVariant->getId())->getArrayBy('value', 'ident');

		/**
		 * Normalize values
		 */
		foreach ($dataSet as $ident => $value) {
			$dataSet[$ident] = $value / $m2a;
		}

		return $this->computeIndicators($dataSet);
	}
	// End computeFixedValues


	/**
	 * @param ElcaProjectVariant $ProjectVariant
	 * @param float              $m2a
	 *
	 * @return array
	 */
	private function computeRefModelValues(ElcaProjectVariant $ProjectVariant, $m2a)
	{
		$indicators = ElcaIndicatorSet::findWithPetByProcessDbId($ProjectVariant->getProject()->getProcessDbId())->getArrayBy('ident', 'id');

		$totalValues = ElcaReportSet::findTotalEffects($ProjectVariant->getId())->getArrayBy('value', 'ident');

		$refConstrValues = ElcaBenchmarkRefConstructionValueSet::findByBenchmarkVersionId($this->BenchmarkVersion->getId())->getArrayBy('value', 'indicatorId');
		$refOpValues = ElcaReportSet::findFinalEnergyRefModelEffects($ProjectVariant->getId())->getArrayBy('value', 'indicator_id');

		$dataSet = [];
		foreach ($indicators as $id => $ident) {
			if (!isset($refConstrValues[$id]) || !$refConstrValues[$id] ||
			    !isset($refOpValues[$id]) || !$refOpValues[$id] ||
			    !isset($totalValues[$ident]) || !$totalValues[$ident])
				continue;

			$dataSet[$ident] = $totalValues[$ident] / ($refConstrValues[$id] * $m2a + $refOpValues[$id]);
		}

		return $this->computeIndicators($dataSet);
	}
	// End computeRefModelValues


	/**
	 * Calculates bnb benchmark for the given indicator values (indicatorIdent => value)
	 * All values should be normalized by NGFa and life time
	 *
	 * @param  array $indicatorValues
	 * @return array
	 */
	public function computeIndicators(array $indicatorValues)
	{
		$results = [];

		$benchmarkVersionId = $this->BenchmarkVersion->getId();

		/**
		 * Build benchmark cache
		 */
		foreach($indicatorValues as $ident => $value)
		{
			/**
			 * Add benchmarks to cache
			 */
			if(!isset($this->cache[$ident]))
			{
				$ThresholdSet = ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorIdent($benchmarkVersionId, $ident);

				$this->cache[$ident] = $ThresholdSet->getArrayBy('value', 'score');

				if(in_array($ident, ElcaIndicator::$primaryEnergyRenewableIndicators))
					ksort($this->cache[$ident], SORT_NUMERIC);
				else
					krsort($this->cache[$ident], SORT_NUMERIC);
			}

			if(isset($this->cache[$ident]) && !in_array($ident, ElcaIndicator::$primaryEnergyRenewableIndicators))
				$results[$ident] = FloatCalc::computeBenchmark($this->cache[$ident], $value);
		}

		/**
		 * Compute benchmark for renewable primary energy based total primary energy
		 */
		if ($this->BenchmarkVersion->getProcessDb()->isEn15804Compliant()) {
			$results[ ElcaIndicator::IDENT_PERE ] = $this->computeRenewablePrimaryEnergy($indicatorValues,
			                                                                             ElcaIndicator::IDENT_PERE);
			$results[ ElcaIndicator::IDENT_PERM ] = $this->computeRenewablePrimaryEnergy($indicatorValues,
			                                                                             ElcaIndicator::IDENT_PERM);
		} else {
			$results[ ElcaIndicator::IDENT_PE_EM ] = $this->computeRenewablePrimaryEnergy($indicatorValues,
			                                                                              ElcaIndicator::IDENT_PE_EM);
		}
		return $results;
	}
	// End computeIndicators


	/**
	 * @param ElcaProjectVariant $ProjectVariant
	 *
	 * @return DataObjectSet
	 */
	private function getConstrProjectionValues(ElcaProjectVariant $ProjectVariant)
	{
		$Settings = ElcaSettingSet::findBySection(BenchmarksCtrl::SETTING_SECTION);

		$IndicatorSet = ElcaIndicatorSet::findByProcessDbId($ProjectVariant->getProject()->getProcessDbId());

		$RefConstrEffects = new DataObjectSet();
		foreach ($IndicatorSet as $Indicator)
		{
			$RefConstrEffect = new \stdClass();
			$RefConstrEffect->indicatorId = $Indicator->getId();
			$RefConstrEffect->indicatorIdent = $Indicator->getIdent();
			$RefConstrEffect->min = $RefConstrEffect->avg = $RefConstrEffect->max = null;
			$RefConstrEffects->add($RefConstrEffect);

			foreach (['min', 'avg', 'max'] as $property)
			{
				$ident = $property .'.'. $RefConstrEffect->indicatorIdent;

				$Setting = $Settings->search('ident', $ident);
				if($Setting instanceof ElcaSetting)
					$RefConstrEffect->$property = $Setting->getNumericValue();
			}
		}

		return $RefConstrEffects;
	}
	// End getConstrProjectionValues


	/**
	 * @param ElcaProjectVariant $ProjectVariant
	 *
	 * @return mixed
	 */
	private function getM2aValue(ElcaProjectVariant $ProjectVariant)
	{
		$ProjectConstruction = $ProjectVariant->getProjectConstruction();
		$Project             = $ProjectVariant->getProject();

		/**
		 * Normalize values by ngf and life time
		 */
		return max(1, $Project->getLifeTime() * $ProjectConstruction->getNetFloorSpace());
	}
	// End getM2aValue


	/**
     * Constructor
     *
     * @param ElcaBenchmarkVersion $BenchmarkVersion
     */
    private function __construct(ElcaBenchmarkVersion $BenchmarkVersion)
    {
        $this->BenchmarkVersion = $BenchmarkVersion;
    }
	// End __construct
}
// End ElcaBenchmark
