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

namespace Lcc\Db;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\DbObject;
use Exception;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccRegularServiceCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccRegularServiceCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.regular_service_costs';

    const PROC_UPDATE_DETAILED_REGULAR_PROJECT_COSTS = 'lcc.update_detailed_regular_project_costs';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * costId
     */
    private $costId;

    /**
     * maintenance percentage
     */
    private $maintenancePerc;

    /**
     * service percentage
     */
    private $servicePerc;

    /**
     * Primary key
     */
    private static $primaryKey = ['costId'];

    /**
     * Column types
     */
    private static $columnTypes = ['costId'          => PDO::PARAM_INT,
                                        'maintenancePerc' => PDO::PARAM_STR,
                                        'servicePerc'     => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $projectVariantId
     */
    public static function updateDetailedRegularProjectCosts($projectVariantId)
    {
        if (!$projectVariantId)
            return;

        DbHandle::getInstance()
            ->exec(
                sprintf(
                    'SELECT %s(%d)',
                    self::PROC_UPDATE_DETAILED_REGULAR_PROJECT_COSTS,
                    $projectVariantId
                )
            );
    }


    /**
     * Creates the object
     *
     * @param  integer  $costId         - costId
     * @param  number  $maintenancePerc - maintenance percentage
     * @param  number  $servicePerc    - service percentage
     */
    public static function create($costId, $maintenancePerc, $servicePerc)
    {
        $LccRegularServiceCost = new LccRegularServiceCost();
        $LccRegularServiceCost->setCostId($costId);
        $LccRegularServiceCost->setMaintenancePerc($maintenancePerc);
        $LccRegularServiceCost->setServicePerc($servicePerc);

        if($LccRegularServiceCost->getValidator()->isValid())
            $LccRegularServiceCost->insert();

        return $LccRegularServiceCost;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccRegularServiceCost' by its primary key
     *
     * @param  integer  $costId - costId
     * @param  boolean  $force - Bypass caching
     * @return LccRegularServiceCost
     */
    public static function findByCostId($costId, $force = false)
    {
        if(!$costId)
            return new LccRegularServiceCost();

        $sql = sprintf("SELECT cost_id
                             , maintenance_perc
                             , service_perc
                          FROM %s
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['costId' => $costId], $force);
    }
    // End findByCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this with a new versionId
     *
     * @param  int $versionId
     * @return LccRegularServiceCost
     */
    public function copy($versionId)
    {
        if(!$versionId || !$this->isInitialized())
            return new LccRegularServiceCost();

        try
        {
            $this->Dbh->begin();

            $Cost = $this->getCost();
            $CostCopy = $Cost->copy($versionId);

            $Copy = self::create($CostCopy->getId(),
                                 $this->maintenancePerc,
                                 $this->servicePerc
                                 );

            $this->Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costId
     *
     * @param  integer  $costId - costId
     * @return
     */
    public function setCostId($costId)
    {
        if(!$this->getValidator()->assertNotEmpty('costId', $costId))
            return;

        $this->costId = (int)$costId;
    }
    // End setCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property maintenancePerc
     *
     * @param  number  $maintenancePerc - maintenance percentage
     * @return
     */
    public function setMaintenancePerc($maintenancePerc)
    {
        if(!$this->getValidator()->assertNotEmpty('maintenancePerc', $maintenancePerc))
            return;

        $this->maintenancePerc = $maintenancePerc;
    }
    // End setMaintenancePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property servicePerc
     *
     * @param  number  $servicePerc - service percentage
     * @return
     */
    public function setServicePerc($servicePerc)
    {
        if(!$this->getValidator()->assertNotEmpty('servicePerc', $servicePerc))
            return;

        $this->servicePerc = $servicePerc;
    }
    // End setServicePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property costId
     *
     * @return integer
     */
    public function getCostId()
    {
        return $this->costId;
    }
    // End getCostId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated LccCost by property costId
     *
     * @param  boolean  $force
     * @return LccCost
     */
    public function getCost($force = false)
    {
        return LccCost::findById($this->costId, $force);
    }
    // End getCost

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property maintenancePerc
     *
     * @return number
     */
    public function getMaintenancePerc()
    {
        return $this->maintenancePerc;
    }
    // End getMaintenancePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property servicePerc
     *
     * @return number
     */
    public function getServicePerc()
    {
        return $this->servicePerc;
    }
    // End getServicePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $costId - costId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($costId, $force = false)
    {
        return self::findByCostId($costId, $force)->isInitialized();
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET maintenance_perc = :maintenancePerc
                             , service_perc    = :servicePerc
                         WHERE cost_id = :costId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['costId'         => $this->costId,
                                        'maintenancePerc' => $this->maintenancePerc,
                                        'servicePerc'    => $this->servicePerc]
                                  );
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE cost_id = :costId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  ['costId' => $this->costId]);
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean  $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if($propertiesOnly)
            return self::$primaryKey;

        $primaryKey = [];

        foreach(self::$primaryKey as $key)
            $primaryKey[$key] = $this->$key;

        return $primaryKey;
    }
    // End getPrimaryKey

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the tablename constant. This is used
     * as interface for other objects.
     *
     * @return string
     */
    public static function getTablename()
    {
        return self::TABLE_NAME;
    }
    // End getTablename

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  boolean  $extColumns
     * @param  mixed    $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if($column)
            return $columnTypes[$column];

        return $columnTypes;
    }
    // End getColumnTypes

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {

        $sql = sprintf("INSERT INTO %s (cost_id, maintenance_perc, service_perc)
                               VALUES  (:costId, :maintenancePerc, :servicePerc)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['costId'         => $this->costId,
                                        'maintenancePerc' => $this->maintenancePerc,
                                        'servicePerc'    => $this->servicePerc]
                                  );
    }
    // End insert

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->costId          = (int)$DO->cost_id;
        $this->maintenancePerc = $DO->maintenance_perc;
        $this->servicePerc     = $DO->service_perc;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccRegularServiceCost