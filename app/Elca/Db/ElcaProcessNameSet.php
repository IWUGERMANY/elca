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

namespace Elca\Db;

use Beibob\Blibs\DbObjectSet;

class ElcaProcessNameSet extends DbObjectSet
{
    public static function findByProcessId(int $processId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$processId)
            return new ElcaProcessNameSet();

        $initValues = ['processId' => $processId];

        return self::_find(get_class(), ElcaProcessName::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);
    }


    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProcessName::TABLE_NAME, $initValues, $orderBy, $limit, $offset, $force);
    }

    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProcessName::getTablename(), $initValues, $force);
    }
}
