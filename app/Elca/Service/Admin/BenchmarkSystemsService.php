<?php declare(strict_types=1);
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

namespace Elca\Service\Admin;

use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaBenchmarkGroupSet;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecificationSet;
use Elca\Db\ElcaBenchmarkRefConstructionValueSet;
use Elca\Db\ElcaBenchmarkRefProcessConfigSet;
use Elca\Db\ElcaBenchmarkSystem;
use Elca\Db\ElcaBenchmarkThreshold;
use Elca\Db\ElcaBenchmarkThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaBenchmarkVersionConstrClassSet;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Model\Benchmark\BenchmarkSystemModel;

class BenchmarkSystemsService
{
    /**
     * @var array|BenchmarkSystemObserver[]
     */
    private $benchmarkSystemObservers;

    /**
     * @var DbHandle
     */
    private $dbHandle;

    /**
     * @var array|BenchmarkSystemModel[]
     */
    private $benchmarkSystemModels;

    public function __construct(array $benchmarkSystemModels = [], array $benchmarkSystemObservers = [], DbHandle $dbHandle)
    {
        $this->benchmarkSystemModels = [];
        foreach ($benchmarkSystemModels as $benchmarkSystemModel) {
            $this->benchmarkSystemModels[get_class($benchmarkSystemModel)] = $benchmarkSystemModel;
        }

        $this->benchmarkSystemObservers = $benchmarkSystemObservers;
        $this->dbHandle = $dbHandle;
    }

    /**
     * @return BenchmarkSystemModel[]|array
     */
    public function benchmarkSystemModels(): array
    {
        return $this->benchmarkSystemModels;
    }

    /**
     * @return BenchmarkSystemModel
     */
    public function benchmarkSystemModelByClassName(string $modelClass): ?BenchmarkSystemModel
    {
        return $this->benchmarkSystemModels[$modelClass] ?? null;
    }

    /**
     * @return BenchmarkSystemModel
     */
    public function benchmarkSystemModelByVersionId(?int $benchmarkVersionId): ?BenchmarkSystemModel
    {
        if (null === $benchmarkVersionId) {
            return null;
        }

        $elcaBenchmarkSystem = ElcaBenchmarkSystem::findByVersionId($benchmarkVersionId);

        return $this->benchmarkSystemModels[$elcaBenchmarkSystem->getModelClass()] ?? null;
    }

    public function copySystem(ElcaBenchmarkSystem $system): ElcaBenchmarkSystem
    {
        $copy = ElcaBenchmarkSystem::create(t('Kopie von') . $system->getName(), $system->getModelClass(), false, $system->getDescription());

        /**
         * @var ElcaBenchmarkVersion $version
         */
        foreach (ElcaBenchmarkVersionSet::findByBenchmarkSystemId($this->id, array('id' => 'ASC')) as $version) {
            $this->copyVersion($version);
        }

        foreach ($this->benchmarkSystemObservers as $observer) {
            $observer->onSystemCopy($system, $copy);
        }

        return $copy;
    }

    public function copyVersion(ElcaBenchmarkVersion $benchmarkVersion, string $name = null): ElcaBenchmarkVersion
    {
        try {
            $this->dbHandle->begin();

            $copy = ElcaBenchmarkVersion::create(
                $benchmarkVersion->getBenchmarkSystemId(),
                $name ? $name : (t('Kopie von') . ' ' . $benchmarkVersion->getName()),
                $benchmarkVersion->getProcessDbId(),
                false,
                $benchmarkVersion->getUseReferenceModel());

            /**
             * Copy benchmark thresholds
             *
             * @var ElcaBenchmarkThreshold $Threshold
             */
            foreach (ElcaBenchmarkThresholdSet::findByVersionIdAndIndicatorId($benchmarkVersion->getId(), null, array('id' => 'ASC')) as $Threshold) {
                $Threshold->copy($copy->getId());
            }

            /**
             * Copy reference process configs
             */
            foreach (ElcaBenchmarkRefProcessConfigSet::find(array('benchmark_version_id' => $benchmarkVersion->getId()), array('process_config_id' => 'ASC')) AS $BenchmarkRefProcessConfig) {
                $BenchmarkRefProcessConfig->copy($copy->getId());
            }

            /**
             * Copy reference construction values
             */
            foreach (ElcaBenchmarkRefConstructionValueSet::find(array('benchmark_version_id' => $benchmarkVersion->getId()), array('indicator_id' => 'ASC')) AS $BenchmarkRefConstructionValue) {
                $BenchmarkRefConstructionValue->copy($copy->getId());
            }

            /**
             * Copy life cycle usage specifications
             */
            foreach (ElcaBenchmarkLifeCycleUsageSpecificationSet::findByBenchmarkVersionId($benchmarkVersion->getId()) as $lifeCycleUsageSpecification) {
                $lifeCycleUsageSpecification->copy($copy->getId());
            }

            foreach (ElcaBenchmarkGroupSet::findByBenchmarkVersionId($benchmarkVersion->getId()) as $group) {
                $group->copy($copy->getId());
            }

            foreach (ElcaBenchmarkVersionConstrClassSet::findByBenchmarkVersionId($benchmarkVersion->getId(), ['id' => 'ASC']) as $constrClass) {
                $constrClass->copy($copy->getId());
            }

            foreach ($this->benchmarkSystemObservers as $observer) {
                $observer->onVersionCopy($benchmarkVersion, $copy);
            }

            $this->dbHandle->commit();
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }

        return $copy;
    }


}
