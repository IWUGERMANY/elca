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
 * @package    elca
 * @class      ElcaProjectTransport
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectTransport extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_transports';



    /**
     * projectTransportId
     */
    private $id;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * transport short description
     */
    private $name;

    /**
     * quantity in kg
     */
    private $quantity;

    /**
     * process config id
     */
    private $processConfigId;

    /**
     * calculate lca
     */
    private $calcLca;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'               => PDO::PARAM_INT,
                                        'projectVariantId' => PDO::PARAM_INT,
                                        'name'             => PDO::PARAM_STR,
                                        'quantity'         => PDO::PARAM_STR,
                                        'processConfigId'  => PDO::PARAM_INT,
                                        'calcLca'          => PDO::PARAM_BOOL);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param  string  $name             - transport short description
     * @param  number $quantity         - quantity in kg
     * @param  integer $processConfigId  - process config id
     * @param  boolean $calcLca          - calculate lca
     * @return \ElcaProjectTransport
     */
    public static function create($projectVariantId, $name, $quantity, $processConfigId = null, $calcLca = false)
    {
        $ElcaProjectTransport = new ElcaProjectTransport();
        $ElcaProjectTransport->setProjectVariantId($projectVariantId);
        $ElcaProjectTransport->setName($name);
        $ElcaProjectTransport->setQuantity($quantity);
        $ElcaProjectTransport->setProcessConfigId($processConfigId);
        $ElcaProjectTransport->setCalcLca($calcLca);
        
        if($ElcaProjectTransport->getValidator()->isValid())
            $ElcaProjectTransport->insert();
        
        return $ElcaProjectTransport;
    }
    // End create
    


    /**
     * Inits a `ElcaProjectTransport' by its primary key
     *
     * @param  integer  $id    - projectTransportId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProjectTransport
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectTransport();
        
        $sql = sprintf("SELECT id
                             , project_variant_id
                             , name
                             , quantity
                             , process_config_id
                             , calc_lca
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Creates a deep copy from this transport
     *
     * @param  int $projectVariantId new project variant id
     * @throws Exception
     * @return ElcaProjectTransport - the new element copy
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectTransport();

        try {
            $this->Dbh->begin();

            $Copy = self::create($projectVariantId,
                                 $this->name,
                                 $this->quantity,
                                 $this->processConfigId,
                                 $this->calcLca
            );

            foreach(ElcaProjectTransportMeanSet::findByProjectTransportId($this->id) as $TransportMean) {
                $TransportMean->copy($Copy->getId());
            }
            $this->Dbh->commit();
        }
        catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

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
     * Sets the property name
     *
     * @param  string $name - transport short description
     * @return void
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;
        
        if(!$this->getValidator()->assertMaxLength('name', 200, $name))
            return;
        
        $this->name = (string)$name;
    }
    // End setName
    


    /**
     * Sets the property quantity
     *
     * @param  float $quantity - quantity in kg
     * @return void
     */
    public function setQuantity($quantity)
    {
        if(!$this->getValidator()->assertNotEmpty('quantity', $quantity))
            return;
        
        $this->quantity = $quantity;
    }
    // End setQuantity
    


    /**
     * Sets the property processConfigId
     *
     * @param  integer $processConfigId - process config id
     * @return void
     */
    public function setProcessConfigId($processConfigId = null)
    {
        $this->processConfigId = $processConfigId;
    }
    // End setProcessConfigId
    


    /**
     * Sets the property calcLca
     *
     * @param  boolean $calcLca - calculate lca
     * @return void
     */
    public function setCalcLca($calcLca = false)
    {
        $this->calcLca = (bool)$calcLca;
    }
    // End setCalcLca
    


    /**
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId
    


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
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    // End getName
    


    /**
     * Returns the property quantity
     *
     * @return number
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity
    


    /**
     * Returns the property processConfigId
     *
     * @return integer
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId
    


    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  boolean  $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig
    


    /**
     * Returns the property calcLca
     *
     * @return boolean
     */
    public function getCalcLca()
    {
        return $this->calcLca;
    }
    // End getCalcLca
    


    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - projectTransportId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                           SET project_variant_id = :projectVariantId
                             , name             = :name
                             , quantity         = :quantity
                             , process_config_id = :processConfigId
                             , calc_lca         = :calcLca
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'name'            => $this->name,
                                        'quantity'        => $this->quantity,
                                        'processConfigId' => $this->processConfigId,
                                        'calcLca'         => $this->calcLca)
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id               = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, project_variant_id, name, quantity, process_config_id, calc_lca)
                               VALUES  (:id, :projectVariantId, :name, :quantity, :processConfigId, :calcLca)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'              => $this->id,
                                        'projectVariantId' => $this->projectVariantId,
                                        'name'            => $this->name,
                                        'quantity'        => $this->quantity,
                                        'processConfigId' => $this->processConfigId,
                                        'calcLca'         => $this->calcLca)
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
        $this->id               = (int)$DO->id;
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->name             = $DO->name;
        $this->quantity         = $DO->quantity;
        $this->processConfigId  = $DO->process_config_id;
        $this->calcLca          = (bool)$DO->calc_lca;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectTransport