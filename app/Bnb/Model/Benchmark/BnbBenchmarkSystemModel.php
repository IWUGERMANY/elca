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

namespace Bnb\Model\Benchmark;

use Bnb\Controller\WaterCtrl;
use Elca\Model\Benchmark\BenchmarkSystemModel;

class BnbBenchmarkSystemModel implements BenchmarkSystemModel
{

    public function name(): string
    {
        return 'BNB';
    }

    public function displayLivingSpace(): bool
    {
        return false;
    }

    public function displayScores(): bool
    {
        return true;
    }

    public function waterCalculator(): array
    {
        return [
            'caption'=> 'BNB Trinkwasser 1.2.3 Version 2011 / 2015',
            'module' => 'bnb',
            'controller' => WaterCtrl::class,
        ];
    }
}
