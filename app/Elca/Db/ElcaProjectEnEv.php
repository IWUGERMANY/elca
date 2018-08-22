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
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectEnEv extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_en_ev';

    const UNIT_KWH_NGF = 0;
    const UNIT_KWH = 1;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * NGF EnEv
     */
    private $ngf;

    /**
     * EnEv Version
     */
    private $version;

    /**
     * unit for final energy demand
     */
    private $unitDemand;

    /**
     * unit for final energy supply
     */
    private $unitSupply;


    /**
     * Primary key
     */
    private static $primaryKey = array('projectVariantId');

    /**
     * Column types
     */
    private static $columnTypes = array('projectVariantId' => PDO::PARAM_INT,
                                        'ngf'              => PDO::PARAM_STR,
                                        'version'          => PDO::PARAM_INT,
                                        'unitDemand'       => PDO::PARAM_INT,
                                        'unitSupply'       => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param  number $ngf              - NGF EnEv
     * @param  integer $version          - EnEv Version
     * @param  integer $unitDemand
     * @param  integer $unitSupply
     * @return ElcaProjectEnEv
     */
    public static function create($projectVariantId, $ngf, $version = null, $unitDemand = self::UNIT_KWH_NGF, $unitSupply = self::UNIT_KWH_NGF)
    {
        $ElcaProjectEnEv = new ElcaProjectEnEv();
        $ElcaProjectEnEv->setProjectVariantId($projectVariantId);
        $ElcaProjectEnEv->setNgf($ngf);
        $ElcaProjectEnEv->setVersion($version);
        $ElcaProjectEnEv->setUnitDemand($unitDemand);
        $ElcaProjectEnEv->setUnitSupply($unitSupply);

        if($ElcaProjectEnEv->getValidator()->isValid())
            $ElcaProjectEnEv->insert();

        return $ElcaProjectEnEv;
    }
    // End create



    /**
     * Inits a `ElcaProjectEnEv' by its primary key
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  boolean  $force           - Bypass caching
     * @return ElcaProjectEnEv
     */
    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaProjectEnEv();

        $sql = sprintf("SELECT project_variant_id
                             , ngf
                             , version
                             , unit_demand
                             , unit_supply
                          FROM %s
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }
    // End findByProjectVariantId



    /**
     * Creates a deep copy from this object
     *
     * @param  int $projectVariantId new project variant id
     * @return Elca - the new element copy
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectEnEv();

        $Copy = self::create($projectVariantId,
                             $this->ngf,
                             $this->version,
                             $this->unitDemand,
                             $this->unitSupply
        );
        return $Copy;
    }
    // End copy


    /**
     * Sets the property projectVariantId
     *
     * @param  integer $projectVariantId - projectVariantId
     * @return void
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId


    /**
     * Sets the property ngf
     *
     * @param  number $ngf - NGF EnEv
     * @return void
     */
    public function setNgf($ngf)
    {
        if(!$this->getValidator()->assertNotEmpty('ngf', $ngf))
            return;

        $this->ngf = $ngf;
    }
    // End setNgf


    /**
     * Sets the property version
     *
     * @param  integer $version - EnEv Version
     * @return void
     */
    public function setVersion($version = null)
    {
        $this->version = $version;
    }
    // End setVersion


    /**
     * Sets the property unitDemand
     *
     * @param  int      $unitDemand - unit for final energy demand
     * @return void
     */
    public function setUnitDemand($unitDemand = 0)
    {
        $this->unitDemand = (int)$unitDemand;
    }
    // End setUnitDemand


    /**
     * Sets the property unitSupply
     *
     * @param  int      $unitSupply - unit for final energy supply
     * @return void
     */
    public function setUnitSupply($unitSupply = 0)
    {
        $this->unitSupply = (int)$unitSupply;
    }
    // End setUnitSupply


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



    /**
     * Returns the property ngf
     *
     * @return number
     */
    public function getNgf()
    {
        return $this->ngf;
    }
    // End getNgf



    /**
     * Returns the property version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }
    // End getVersion


    /**
     * Returns the property unitDemand
     *
     * @return int
     */
    public function getUnitDemand()
    {
        return $this->unitDemand;
    }
    // End getUnitDemand


    /**
     * Returns the property unitSupply
     *
     * @return int
     */
    public function getUnitSupply()
    {
        return $this->unitSupply;
    }
    // End getUnitSupply

    /**
     * Checks, if the object exists
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  boolean  $force           - Bypass caching
     * @return boolean
     */
    public static function exists($projectVariantId, $force = false)
    {
        return self::findByProjectVariantId($projectVariantId, $force)->isInitialized();
    }
    // End exists



    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET ngf              = :ngf
                             , version          = :version
                             , unit_demand      = :unitDemand
                             , unit_supply      = :unitSupply
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'ngf'             => $this->ngf,
                                        'version'         => $this->version,
                                        'unitDemand'      => $this->unitDemand,
                                        'unitSupply'      => $this->unitSupply)
                                  );
    }
    // End update



    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId));
    }
    // End delete



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


    // protected


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $sql = sprintf("INSERT INTO %s (project_variant_id, ngf, version, unit_demand, unit_supply)
                               VALUES  (:projectVariantId, :ngf, :version, :unitDemand, :unitSupply)"
            , self::TABLE_NAME
        );

        return $this->insertBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'ngf'             => $this->ngf,
                                        'version'         => $this->version,
                                        'unitDemand'      => $this->unitDemand,
                                        'unitSupply'      => $this->unitSupply)
        );
    }
    // End insert



    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->ngf              = $DO->ngf;
        $this->version          = $DO->version;
        $this->unitDemand       = (int)$DO->unit_demand;
        $this->unitSupply       = (int)$DO->unit_supply;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectEnEv