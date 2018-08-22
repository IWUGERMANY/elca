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

use PDO;
use Exception;
use Beibob\Blibs\DbObject;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      ElcaProjectIndicatorBenchmark
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class ElcaProjectIndicatorBenchmark extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_indicator_benchmarks';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * indicatorId
     */
    private $indicatorId;

    /**
     * benchmark
     */
    private $benchmark;

    /**
     * Primary key
     */
    private static $primaryKey = array('projectVariantId', 'indicatorId');

    /**
     * Column types
     */
    private static $columnTypes = array('projectVariantId' => PDO::PARAM_INT,
                                        'indicatorId'      => PDO::PARAM_INT,
                                        'benchmark'        => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Benchmark ratings
     */
    public static $ratings = array('gold' => array(80, 100),
                                   'silver' => array(70, 79),
                                   'bronze' => array(60, 69)
                                   );

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  integer  $indicatorId     - indicatorId
     * @param  integer  $benchmark       - benchmark
     */
    public static function create($projectVariantId, $indicatorId, $benchmark)
    {
        $ElcaProjectIndicatorBenchmark = new ElcaProjectIndicatorBenchmark();
        $ElcaProjectIndicatorBenchmark->setProjectVariantId($projectVariantId);
        $ElcaProjectIndicatorBenchmark->setIndicatorId($indicatorId);
        $ElcaProjectIndicatorBenchmark->setBenchmark($benchmark);

        if($ElcaProjectIndicatorBenchmark->getValidator()->isValid())
            $ElcaProjectIndicatorBenchmark->insert();

        return $ElcaProjectIndicatorBenchmark;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProjectIndicatorBenchmark' by its primary key
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  integer  $indicatorId     - indicatorId
     * @param  boolean  $force           - Bypass caching
     * @return ElcaProjectIndicatorBenchmark
     */
    public static function findByPk($projectVariantId, $indicatorId, $force = false)
    {
        if(!$projectVariantId || !$indicatorId)
            return new ElcaProjectIndicatorBenchmark();

        $sql = sprintf("SELECT project_variant_id
                             , indicator_id
                             , benchmark
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId, 'indicatorId' => $indicatorId), $force);
    }
    // End findByPk

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy from this benchmark
     *
     * @param  int $projectVariantId
     * @return ElcaProjectIndicatorBenchmark
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectIndicatorBenchmark();

        return self::create($projectVariantId,
                            $this->indicatorId,
                            $this->benchmark);
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectVariantId
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @return
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property indicatorId
     *
     * @param  integer  $indicatorId - indicatorId
     * @return
     */
    public function setIndicatorId($indicatorId)
    {
        if(!$this->getValidator()->assertNotEmpty('indicatorId', $indicatorId))
            return;

        $this->indicatorId = (int)$indicatorId;
    }
    // End setIndicatorId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property benchmark
     *
     * @param  integer  $benchmark - benchmark
     * @return
     */
    public function setBenchmark($benchmark)
    {
        if(!$this->getValidator()->assertNotEmpty('benchmark', $benchmark))
            return;

        $this->benchmark = (int)$benchmark;
    }
    // End setBenchmark

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property projectVariantId
     *
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  boolean  $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }
    // End getProjectVariant

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property indicatorId
     *
     * @return integer
     */
    public function getIndicatorId()
    {
        return $this->indicatorId;
    }
    // End getIndicatorId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaIndicator by property indicatorId
     *
     * @param  boolean  $force
     * @return ElcaIndicator
     */
    public function getIndicator($force = false)
    {
        return ElcaIndicator::findById($this->indicatorId, $force);
    }
    // End getIndicator

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property benchmark
     *
     * @return integer
     */
    public function getBenchmark()
    {
        return $this->benchmark;
    }
    // End getBenchmark

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  integer  $indicatorId     - indicatorId
     * @param  boolean  $force           - Bypass caching
     * @return boolean
     */
    public static function exists($projectVariantId, $indicatorId, $force = false)
    {
        return self::findByPk($projectVariantId, $indicatorId, $force)->isInitialized();
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
                           SET benchmark        = :benchmark
                         WHERE project_variant_id = :projectVariantId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'indicatorId'     => $this->indicatorId,
                                        'benchmark'       => $this->benchmark)
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
                              WHERE project_variant_id = :projectVariantId
                                AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId, 'indicatorId' => $this->indicatorId));
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

        $primaryKey = array();

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

        $sql = sprintf("INSERT INTO %s (project_variant_id, indicator_id, benchmark)
                               VALUES  (:projectVariantId, :indicatorId, :benchmark)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'indicatorId'     => $this->indicatorId,
                                        'benchmark'       => $this->benchmark)
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
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->indicatorId      = (int)$DO->indicator_id;
        $this->benchmark        = (int)$DO->benchmark;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectIndicatorBenchmark