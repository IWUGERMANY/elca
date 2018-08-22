<?php declare(strict_types=1);
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Model\Processing;

use Beibob\Blibs\Interfaces\Logger;
use Beibob\Blibs\Log;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Process\Module;
use Elca\Model\ProcessConfig\Conversion\ConversionException;
use Elca\Model\ProcessConfig\LifeCycle\Process;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\UsefulLife;

class FinalEnergyLcaCalculator
{
    /**
     * @var array
     */
    private $indicators;

    /**
     * @var Logger|null
     */
    private $logger;

    /**
     * @param Indicator[] $indicators
     * @param Logger|null $logger
     */
    public function __construct(array $indicators, Logger $logger = null)
    {
        $this->indicators      = $indicators;
        $this->logger = $logger;
    }

    /**
     * @throws ProcessNotFoundInLifeCycleException
     * @throws ConversionException
     * @throws \Exception
     */
    public function compute(
        ProcessLifeCycle $processLifeCycle, Quantity $quantity
    ): ProcessLifeCycleLcaResults {
        /**
         * Init result set
         */
        $lcaResults = new ProcessLifeCycleLcaResults(
            $quantity
        );

        $processLcaCalculator = new ProcessLcaCalculator(
            $processLifeCycle,
            $this->indicators,
            $this->logger
        );

        foreach ($processLifeCycle->usageProcesses() as $process) {
            $lcaResults->addProcessIndicatorResults(
                $processLcaCalculator->compute($process, $quantity)
            );
        }

        return $lcaResults;
    }
}
