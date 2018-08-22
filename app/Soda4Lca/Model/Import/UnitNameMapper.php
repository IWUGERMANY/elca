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

namespace Soda4Lca\Model\Import;

use Elca\Model\Common\Unit;

class UnitNameMapper
{
    /**
     * Mappings
     */
    private static $unitToRefUnitMap = [
        'm²'      => Unit::SQUARE_METER,
        'qm'      => Unit::SQUARE_METER,
        'm³'      => Unit::CUBIC_METER,
        'Item(s)' => Unit::PIECE,
        'stück'   => Unit::PIECE,
        'Bauteil' => Unit::PIECE,
    ];

    public static function unitByName($unit): Unit
    {
        return Unit::fromString(
            self::$unitToRefUnitMap[$unit] ?? $unit
        );
    }
}
