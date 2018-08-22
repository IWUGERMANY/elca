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
 * Handles a set of ElcaProjectTransportMean
 *
 * @package    -
 * @class      ElcaProjectTransportMeanSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaProjectTransportMeanSet extends DbObjectSet
{
    /**
     * Views
     */
    const VIEW_PROJECT_TRANSPORT_MEANS = 'elca.project_transport_means_v';

    /**
     * @param int        $transportId
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     * @param bool       $force
     * @return DbObjectSet
     */
    public static function findByProjectTransportId($transportId, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        $initValues = array('project_transport_id' => $transportId);

        return parent::_find(get_class(), self::VIEW_PROJECT_TRANSPORT_MEANS, $initValues, $orderBy, $limit, $offset, $force);
    }
    // End findByProjectVariantId


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
            return new ElcaProjectTransportMeanSet();

        if(is_null($processDbId))
        {
            $initValues = array('processConfigId' => $processConfigId);

            $sql = sprintf('SELECT DISTINCT t.*
                              FROM %s t
                             WHERE t.process_config_id = :processConfigId'
                , ElcaProjectTransportMean::TABLE_NAME
            );
        }
        else
        {
            $initValues = array('processConfigId' => $processConfigId,
                                'processDbId' => $processDbId);

            $sql = sprintf('SELECT DISTINCT tm.*
                              FROM %s tm
                              JOIN %s t ON t.id = tm.project_transport_id
                              JOIN %s v ON v.id = t.project_variant_id
                              JOIN %s p ON p.id = v.project_id
                             WHERE tm.process_config_id = :processConfigId
                               AND p.process_db_id = :processDbId'
                , ElcaProjectTransportMean::TABLE_NAME
                , ElcaProjectTransport::TABLE_NAME
                , ElcaProjectVariant::TABLE_NAME
                , ElcaProject::TABLE_NAME
            );
        }

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByProcessConfigId


    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return ElcaProjectTransportMeanSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaProjectTransportMean::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find
    

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  bool     $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaProjectTransportMean::getTablename(), $initValues, $force);
    }
}
// End class ElcaProjectTransportMeanSet