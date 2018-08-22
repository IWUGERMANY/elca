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

namespace NaWoh\Model\Water;

use NaWoh\Db\NawohWater;
use NaWoh\Db\NawohWaterVersion;

class NaWohWaterConsumptionCalculator
{
    /**
     * @var NawohWaterVersion
     */
    private $version;

    public function __construct(NawohWaterVersion $version)
    {
        $this->version = $version;
    }

    public function computeWaterConsumption(NawohWater $naWohWater): array
    {
        $results = [];
        $total   = 0;

        foreach (
            [
                'toiletteVoll',
                'toiletteSpartaste',
                'dusche',
                'badewanneGesamt',
                'wasserhaehneBad',
                'wasserhaehneKueche',
                'waschmaschine',
                'geschirrspueler',
            ] as $property
        ) {
            if ('badewanneGesamt' === $property && false === $naWohWater->getMitBadewanne()) {
                continue;
            }

            $results[$property] = $this->version->$property * $naWohWater->$property;
            $total              += $results[$property];
        }

        $results['total'] = $total;

        return $results;

    }
}
