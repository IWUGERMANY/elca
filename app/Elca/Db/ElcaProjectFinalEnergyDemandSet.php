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
 * Handles a set of ElcaProjectFinalEnergyDemand
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectFinalEnergyDemandSet extends DbObjectSet
{
    /**
     * Find by processConfigId
     *
     * @param  int      $processConfigId
     * @param  int      $processDbId
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProjectFinalEnergyDemandSet
     */
    public static function findByProcessConfigId($processConfigId, $processDbId = null, $force = false)
    {
        if(!$processConfigId)
            return new ElcaProjectFinalEnergyDemandSet();

        if(is_null($processDbId))
        {
            $initValues = array('processConfigId' => $processConfigId);

            $sql = sprintf('SELECT DISTINCT f.*
                              FROM %s f
                             WHERE f.process_config_id = :processConfigId'
                           , ElcaProjectFinalEnergyDemand::TABLE_NAME
                           );
        }
        else
        {
            $initValues = array('processConfigId' => $processConfigId,
                                'processDbId' => $processDbId);

            $sql = sprintf('SELECT DISTINCT f.*
                              FROM %s f
                              JOIN %s v ON v.id = f.project_variant_id
                              JOIN %s p ON p.id = v.project_id
                             WHERE f.process_config_id = :processConfigId
                               AND p.process_db_id = :processDbId'
                           , ElcaProjectFinalEnergyDemand::TABLE_NAME
                           , ElcaProjectVariant::TABLE_NAME
                           , ElcaProject::TABLE_NAME
                           );
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId


    /**
     * Find by projectVariantId
     *
     * @param  int      $projectVariantId
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProjectFinalEnergyDemandSet
     */
    public static function findByProjectVariantId($projectVariantId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaProjectFinalEnergyDemandSet();

        $initValues = array('project_variant_id' => $projectVariantId);
        return self::_find(get_class(), ElcaProjectFinalEnergyDemand::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByProjectVariantId

    public static function findByKwkId(int $kwkId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if(!$kwkId)
            return new ElcaProjectFinalEnergyDemandSet();

        $initValues = array('kwk_id' => $kwkId);
        return self::_find(get_class(), ElcaProjectFinalEnergyDemand::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaProjectFinalEnergyDemandSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProjectFinalEnergyDemand::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find



    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProjectFinalEnergyDemand::getTablename(), $initValues, $force);
    }
    // End dbCount

}
// End class ElcaProjectFinalEnergyDemandSet