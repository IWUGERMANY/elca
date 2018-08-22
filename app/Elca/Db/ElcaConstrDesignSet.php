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
/**
 * Handles a set of ElcaConstrDesign
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaConstrDesignSet extends DbObjectSet
{

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Finds all construction designs of an element
     *
     * @param  int      $elementId
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaConstrDesignSet
     */
    public static function findByElementId($elementId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$elementId)
            return new ElcaConstrCatalogSet();

        $sql = sprintf("SELECT d.*
                          FROM %s d
                          JOIN %s m ON d.id = m.constr_design_id
                         WHERE m.element_id = :elementId"
                       , ElcaConstrDesign::TABLE_NAME
                       , ElcaElementConstrDesign::TABLE_NAME
                       );

        if($orderBy)
        {
            if($orderSql = self::buildOrderView($orderBy, $limit, $offset))
                $sql .= ' '.$orderSql;
        }

        return self::_findBySql(get_class(), $sql, array('elementId' => $elementId), $force);
    }
    // End findByElementId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaConstrDesignSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaConstrDesign::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaConstrDesign::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaConstrDesignSet