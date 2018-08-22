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
 * 
 *
 * @package    elca
 * @class      ElcaBenchmarkRefConstructionValue
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkRefConstructionValue extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_ref_construction_values';

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * indicatorId
     */
    private $indicatorId;

    /**
     * reference construction value
     */
    private $value;

	/**
	 * Ext: indicator ident
	 */

    /**
     * Primary key
     */
    private static $primaryKey = array('benchmarkVersionId', 'indicatorId');

    /**
     * Column types
     */
    private static $columnTypes = array('benchmarkVersionId' => PDO::PARAM_INT,
                                        'indicatorId'        => PDO::PARAM_INT,
                                        'value'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('indicatorIdent' => PDO::PARAM_STR);

    /**
     * Creates the object
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  int      $indicatorId       - indicatorId
     * @param  float    $value             - reference construction value
     * @return ElcaBenchmarkRefConstructionValue
     */
    public static function create($benchmarkVersionId, $indicatorId, $value = null)
    {
        $ElcaBenchmarkRefConstructionValue = new ElcaBenchmarkRefConstructionValue();
        $ElcaBenchmarkRefConstructionValue->setBenchmarkVersionId($benchmarkVersionId);
        $ElcaBenchmarkRefConstructionValue->setIndicatorId($indicatorId);
        $ElcaBenchmarkRefConstructionValue->setValue($value);
        
        if($ElcaBenchmarkRefConstructionValue->getValidator()->isValid())
            $ElcaBenchmarkRefConstructionValue->insert();
        
        return $ElcaBenchmarkRefConstructionValue;
    }
    // End create
    

    /**
     * Inits a `ElcaBenchmarkRefConstructionValue' by its primary key
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  int      $indicatorId       - indicatorId
     * @param  bool     $force             - Bypass caching
     * @return ElcaBenchmarkRefConstructionValue
     */
    public static function findByPk($benchmarkVersionId, $indicatorId, $force = false)
    {
        if(!$benchmarkVersionId || !$indicatorId)
            return new ElcaBenchmarkRefConstructionValue();
        
        $sql = sprintf("SELECT benchmark_version_id
                             , indicator_id
                             , value
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'indicatorId' => $indicatorId), $force);
    }
    // End findByPk


	/**
	 * Copies the construction value and assigns it to the given versionId
	 *
	 * @param $versionId
	 * @return ElcaBenchmarkRefConstructionValue
	 */
	public function copy($versionId)
	{
		return self::create($versionId, $this->indicatorId, $this->value);
	}
	// End copy

    /**
     * Sets the property benchmarkVersionId
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @return void
     */
    public function setBenchmarkVersionId($benchmarkVersionId)
    {
        if(!$this->getValidator()->assertNotEmpty('benchmarkVersionId', $benchmarkVersionId))
            return;
        
        $this->benchmarkVersionId = (int)$benchmarkVersionId;
    }
    // End setBenchmarkVersionId
    

    /**
     * Sets the property indicatorId
     *
     * @param  int      $indicatorId - indicatorId
     * @return void
     */
    public function setIndicatorId($indicatorId)
    {
        if(!$this->getValidator()->assertNotEmpty('indicatorId', $indicatorId))
            return;
        
        $this->indicatorId = (int)$indicatorId;
    }
    // End setIndicatorId
    

    /**
     * Sets the property value
     *
     * @param  float    $value - reference construction value
     * @return void
     */
    public function setValue($value = null)
    {
        $this->value = $value;
    }
    // End setValue
    

    /**
     * Returns the property benchmarkVersionId
     *
     * @return int
     */
    public function getBenchmarkVersionId()
    {
        return $this->benchmarkVersionId;
    }
    // End getBenchmarkVersionId
    

    /**
     * Returns the associated ElcaBenchmarkVersion by property benchmarkVersionId
     *
     * @param  bool     $force
     * @return ElcaBenchmarkVersion
     */
    public function getBenchmarkVersion($force = false)
    {
        return ElcaBenchmarkVersion::findById($this->benchmarkVersionId, $force);
    }
    // End getBenchmarkVersion
    

    /**
     * Returns the property indicatorId
     *
     * @return int
     */
    public function getIndicatorId()
    {
        return $this->indicatorId;
    }
    // End getIndicatorId
    

    /**
     * Returns the associated ElcaIndicator by property indicatorId
     *
     * @param  bool     $force
     * @return ElcaIndicator
     */
    public function getIndicator($force = false)
    {
        return ElcaIndicator::findById($this->indicatorId, $force);
    }
    // End getIndicator
    

    /**
     * Returns the property value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
    // End getValue


	/**
	 * Returns the property indicatorId
	 *
	 * @return int
	 */
	public function getIndicatorIdent()
	{
		if (!$this->indicatorIdent) {
			$this->indicatorIdent = $this->getIndicator()->getIdent();
		}

		return $this->indicatorIdent;
	}
	// End getIndicatorIdent


	/**
     * Checks, if the object exists
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  int      $indicatorId       - indicatorId
     * @param  bool     $force             - Bypass caching
     * @return bool
     */
    public static function exists($benchmarkVersionId, $indicatorId, $force = false)
    {
        return self::findByPk($benchmarkVersionId, $indicatorId, $force)->isInitialized();
    }
    // End exists
    

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET value              = :value
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId,
                                        'indicatorId'       => $this->indicatorId,
                                        'value'             => $this->value)
                                  );
    }
    // End update
    

    /**
     * Deletes the object from the table
     *
     * @return bool
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE benchmark_version_id = :benchmarkVersionId
                                AND indicator_id = :indicatorId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId, 'indicatorId' => $this->indicatorId));
    }
    // End delete
    

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  bool     $propertiesOnly
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
    

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  bool     $extColumns
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

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        
        $sql = sprintf("INSERT INTO %s (benchmark_version_id, indicator_id, value)
                               VALUES  (:benchmarkVersionId, :indicatorId, :value)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId,
                                        'indicatorId'       => $this->indicatorId,
                                        'value'             => $this->value)
                                  );
    }
    // End insert
    

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->benchmarkVersionId = (int)$DO->benchmark_version_id;
        $this->indicatorId        = (int)$DO->indicator_id;
        $this->value              = $DO->value;
        
        /**
         * Set extensions
         */
	    if (isset($DO->indicator_ident)) $this->indicatorIdent = $DO->indicator_ident;
    }
    // End initByDataObject
}
// End class ElcaBenchmarkRefConstructionValue