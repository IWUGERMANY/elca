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
 * @package    -
 * @class      ElcaBenchmarkRefProcessConfig
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkRefProcessConfig extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_ref_process_configs';

	/**
	 * Idents
	 */
	const IDENT_HEATING = 'heating';
	const IDENT_ELECTRICITY = 'electricity';
	const IDENT_PROCESS_ENERGY = 'process-energy';

    /**
     * benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * ident
     */
    private $ident;

    /**
     * reference process config
     */
    private $processConfigId;

    /**
     * Primary key
     */
    private static $primaryKey = array('benchmarkVersionId', 'ident');

    /**
     * Column types
     */
    private static $columnTypes = array('benchmarkVersionId' => PDO::PARAM_INT,
                                        'ident'              => PDO::PARAM_STR,
                                        'processConfigId'    => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  string   $ident             - ident
     * @param  int      $processConfigId   - reference process config
     * @return ElcaBenchmarkRefProcessConfig
     */
    public static function create($benchmarkVersionId, $ident, $processConfigId)
    {
        $ElcaBenchmarkRefProcessConfig = new ElcaBenchmarkRefProcessConfig();
        $ElcaBenchmarkRefProcessConfig->setBenchmarkVersionId($benchmarkVersionId);
        $ElcaBenchmarkRefProcessConfig->setIdent($ident);
        $ElcaBenchmarkRefProcessConfig->setProcessConfigId($processConfigId);
        
        if($ElcaBenchmarkRefProcessConfig->getValidator()->isValid())
            $ElcaBenchmarkRefProcessConfig->insert();
        
        return $ElcaBenchmarkRefProcessConfig;
    }
    // End create
    

    /**
     * Inits a `ElcaBenchmarkRefProcessConfig' by its primary key
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  string   $ident             - ident
     * @param  bool     $force             - Bypass caching
     * @return ElcaBenchmarkRefProcessConfig
     */
    public static function findByPk($benchmarkVersionId, $ident, $force = false)
    {
        if(!$benchmarkVersionId || !$ident)
            return new ElcaBenchmarkRefProcessConfig();
        
        $sql = sprintf("SELECT benchmark_version_id
                             , ident
                             , process_config_id
                          FROM %s
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('benchmarkVersionId' => $benchmarkVersionId, 'ident' => $ident), $force);
    }
    // End findByPk


	/**
	 * Copies the construction value and assigns it to the given versionId
	 *
	 * @param $versionId
	 * @return ElcaBenchmarkRefProcessConfig
	 */
	public function copy($versionId)
	{
		return self::create($versionId, $this->ident, $this->processConfigId);
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
     * Sets the property ident
     *
     * @param  string   $ident - ident
     * @return void
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;
        
        if(!$this->getValidator()->assertMaxLength('ident', 30, $ident))
            return;
        
        $this->ident = (string)$ident;
    }
    // End setIdent
    

    /**
     * Sets the property processConfigId
     *
     * @param  int      $processConfigId - reference process config
     * @return void
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;
        
        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId
    

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
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent
    

    /**
     * Returns the property processConfigId
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId
    

    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  bool     $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $benchmarkVersionId - benchmarkVersionId
     * @param  string   $ident             - ident
     * @param  bool     $force             - Bypass caching
     * @return bool
     */
    public static function exists($benchmarkVersionId, $ident, $force = false)
    {
        return self::findByPk($benchmarkVersionId, $ident, $force)->isInitialized();
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
                           SET process_config_id  = :processConfigId
                         WHERE benchmark_version_id = :benchmarkVersionId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId,
                                        'ident'             => $this->ident,
                                        'processConfigId'   => $this->processConfigId)
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
                                AND ident = :ident"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId, 'ident' => $this->ident));
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
        
        $sql = sprintf("INSERT INTO %s (benchmark_version_id, ident, process_config_id)
                               VALUES  (:benchmarkVersionId, :ident, :processConfigId)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('benchmarkVersionId' => $this->benchmarkVersionId,
                                        'ident'             => $this->ident,
                                        'processConfigId'   => $this->processConfigId)
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
        $this->ident              = $DO->ident;
        $this->processConfigId    = (int)$DO->process_config_id;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaBenchmarkRefProcessConfig