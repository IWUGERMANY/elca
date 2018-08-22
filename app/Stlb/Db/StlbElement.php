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
namespace Stlb\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaProject;
use PDO;

class StlbElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'stlb.elements';


    /**
     * indicatorId
     */
    private $id;

    /**
     * elca.projects.id
     */
    private $projectId;

    /**
     * DIN276-1_08
     */
    private $dinCode;

    /**
     * kurztext
     */
    private $name;

    /**
     * langtext
     */
    private $description;

    /**
     * unit of measure
     */
    private $quantity;

    /**
     * me in import file
     */
    private $refUnit;

    /**
     * the oz
     */
    private $oz;

    /**
     * LB-NR
     */
    private $lbNr;

    /**
     * Einheitspreis
     */
    private $pricePerUnit;

    /**
     * Gesamtbetrag
     */
    private $price;

    /**
     * soll in der View angezeigt werden
     */
    private $isVisible;

    /**
     * Creation Time
     */
    private $created;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = ['id'             => PDO::PARAM_INT,
                                        'projectId'      => PDO::PARAM_INT,
                                        'dinCode'        => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'description'    => PDO::PARAM_STR,
                                        'quantity'       => PDO::PARAM_STR,
                                        'refUnit'        => PDO::PARAM_STR,
                                        'oz'             => PDO::PARAM_STR,
                                        'lbNr'           => PDO::PARAM_STR,
                                        'pricePerUnit'   => PDO::PARAM_STR,
                                        'price'          => PDO::PARAM_STR,
                                        'isVisible'      => PDO::PARAM_BOOL,
                                        'created'        => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];


    /**
     * Creates the object
     *
     * @param  integer  $projectId   - elca.projects.id
     * @param  integer  $dinCode     - DIN276-1_08
     * @param  string   $name        - kurztext
     * @param  string   $description - langtext
     * @param  number  $quantity    - unit of measure
     * @param  string   $refUnit     - me in import file
     * @param  string   $oz          - the oz
     * @param  string   $lbNr        - LB-NR
     * @param  number  $pricePerUnit - Einheitspreis
     * @param  number  $price       - Gesamtbetrag
     * @param  boolean  $isVisible   - Soll angezeigt werden
     */
    public static function create($projectId, $dinCode, $name,
        $description, $quantity, $refUnit, $oz, $lbNr,
        $pricePerUnit = null, $price = null, $isVisible = true)
    {
        $StlbElement = new StlbElement();
        $StlbElement->setProjectId($projectId);
        $StlbElement->setDinCode($dinCode);
        $StlbElement->setName($name);
        $StlbElement->setDescription($description);
        $StlbElement->setQuantity($quantity);
        $StlbElement->setRefUnit($refUnit);
        $StlbElement->setOz($oz);
        $StlbElement->setLbNr($lbNr);
        $StlbElement->setPricePerUnit($pricePerUnit);
        $StlbElement->setPrice($price);
        $StlbElement->setIsVisible($isVisible);

        if($StlbElement->getValidator()->isValid())
            $StlbElement->insert();

        return $StlbElement;
    }
    // End create


    /**
     * Inits a `StlbElement' by its primary key
     *
     * @param  integer  $id    - indicatorId
     * @param  boolean  $force - Bypass caching
     * @return StlbElement
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new StlbElement();

        $sql = sprintf("SELECT id
                             , project_id
                             , din_code
                             , name
                             , description
                             , quantity
                             , ref_unit
                             , oz
                             , lb_nr
                             , price_per_unit
                             , price
                             , is_visible
                             , created
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById


    /**
     * Sets the property projectId
     *
     * @param  integer  $projectId - elca.projects.id
     * @return
     */
    public function setProjectId($projectId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;

        $this->projectId = (int)$projectId;
    }
    // End setProjectId


    /**
     * Sets the property dinCode
     *
     * @param  integer  $dinCode - DIN276-1_08
     * @return
     */
    public function setDinCode($dinCode)
    {
        if(!$this->getValidator()->assertNotEmpty('dinCode', $dinCode))
            return;

        $this->dinCode = (int)$dinCode;
    }
    // End setDinCode


    /**
     * Sets the property name
     *
     * @param  string   $name  - kurztext
     * @return
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        $this->name = (string)$name;
    }
    // End setName


    /**
     * Sets the property description
     *
     * @param  string   $description - langtext
     * @return
     */
    public function setDescription($description)
    {
        if(!$this->getValidator()->assertNotEmpty('description', $description))
            return;

        $this->description = (string)$description;
    }
    // End setDescription


    /**
     * Sets the property quantity
     *
     * @param  number  $quantity - unit of measure
     * @return
     */
    public function setQuantity($quantity)
    {
        if(!$this->getValidator()->assertNotEmpty('quantity', $quantity))
            return;

        $this->quantity = $quantity;
    }
    // End setQuantity


    /**
     * Sets the property refUnit
     *
     * @param  string   $refUnit - me in import file
     * @return
     */
    public function setRefUnit($refUnit)
    {
        if(!$this->getValidator()->assertNotEmpty('refUnit', $refUnit))
            return;

        if(!$this->getValidator()->assertMaxLength('refUnit', 20, $refUnit))
            return;

        $this->refUnit = (string)$refUnit;
    }
    // End setRefUnit


    /**
     * Sets the property oz
     *
     * @param  string   $oz    - the oz
     * @return
     */
    public function setOz($oz)
    {
        if(!$this->getValidator()->assertNotEmpty('oz', $oz))
            return;

        if(!$this->getValidator()->assertMaxLength('oz', 150, $oz))
            return;

        $this->oz = (string)$oz;
    }
    // End setOz


    /**
     * Sets the property lbNr
     *
     * @param  string   $lbNr  - LB-NR
     * @return
     */
    public function setLbNr($lbNr)
    {
        if(!$this->getValidator()->assertNotEmpty('lbNr', $lbNr))
            return;

        $this->lbNr = (string)$lbNr;
    }
    // End setLbNr


    /**
     * Sets the property pricePerUnit
     *
     * @param  number  $pricePerUnit - Einheitspreis
     * @return
     */
    public function setPricePerUnit($pricePerUnit = null)
    {
        $this->pricePerUnit = $pricePerUnit;
    }
    // End setPricePerUnit


    /**
     * Sets the property price
     *
     * @param  number  $price - Gesamtbetrag
     * @return
     */
    public function setPrice($price = null)
    {
        $this->price = $price;
    }
    // End setPrice


    /**
     * Sets the visibility flag
     *
     * @param  boolean  $visible - sichtbarkeits flag
     * @return
     */
    public function setIsVisible($visible = true)
    {
        $this->isVisible = $visible;
    }
    // End setIsVisible


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
     * Returns the property projectId
     *
     * @return integer
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
    // End getProjectId


    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  boolean  $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject


    /**
     * Returns the property dinCode
     *
     * @return integer
     */
    public function getDinCode()
    {
        return $this->dinCode;
    }
    // End getDinCode


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
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getDescription


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
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit


    /**
     * Returns the property oz
     *
     * @return string
     */
    public function getOz()
    {
        return $this->oz;
    }
    // End getOz


    /**
     * Returns the property lbNr
     *
     * @return string
     */
    public function getLbNr()
    {
        return $this->lbNr;
    }
    // End getLbNr


    /**
     * Returns the property pricePerUnit
     *
     * @return number
     */
    public function getPricePerUnit()
    {
        return $this->pricePerUnit;
    }
    // End getPricePerUnit


    /**
     * Returns the property price
     *
     * @return number
     */
    public function getPrice()
    {
        return $this->price;
    }
    // End getPrice


    /**
     * Returns the isVisible flag
     *
     * @return boolean
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }
    // End getIsVisible


    /**
     * Returns the property created
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }
    // End getCreated


    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - indicatorId
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
                           SET project_id     = :projectId
                             , din_code       = :dinCode
                             , name           = :name
                             , description    = :description
                             , quantity       = :quantity
                             , ref_unit       = :refUnit
                             , oz             = :oz
                             , lb_nr          = :lbNr
                             , price_per_unit = :pricePerUnit
                             , price          = :price
                             , is_visible     = :isVisible
                             , created        = :created
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['id'            => $this->id,
                                        'projectId'     => $this->projectId,
                                        'dinCode'       => $this->dinCode,
                                        'name'          => $this->name,
                                        'description'   => $this->description,
                                        'quantity'      => $this->quantity,
                                        'refUnit'       => $this->refUnit,
                                        'oz'            => $this->oz,
                                        'lbNr'          => $this->lbNr,
                                        'pricePerUnit'  => $this->pricePerUnit,
                                        'price'         => $this->price,
                                        'isVisible'     => $this->isVisible,
                                        'created'       => $this->created]
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
                                  ['id' => $this->id]);
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

        $primaryKey = [];

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



    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id             = $this->getNextSequenceValue();
        $this->created        = self::getCurrentTime();

        $sql = sprintf("INSERT INTO %s (id, project_id, din_code, name, description, quantity, ref_unit, oz, lb_nr, price_per_unit, price, is_visible, created)
                               VALUES  (:id, :projectId, :dinCode, :name, :description, :quantity, :refUnit, :oz, :lbNr, :pricePerUnit, :price, :isVisible, :created)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['id'            => $this->id,
                                        'projectId'     => $this->projectId,
                                        'dinCode'       => $this->dinCode,
                                        'name'          => $this->name,
                                        'description'   => $this->description,
                                        'quantity'      => $this->quantity,
                                        'refUnit'       => $this->refUnit,
                                        'oz'            => $this->oz,
                                        'lbNr'          => $this->lbNr,
                                        'pricePerUnit'  => $this->pricePerUnit,
                                        'price'         => $this->price,
                                        'isVisible'     => $this->isVisible,
                                        'created'       => $this->created]
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
        $this->id             = (int)$DO->id;
        $this->projectId      = (int)$DO->project_id;
        $this->dinCode        = (int)$DO->din_code;
        $this->name           = $DO->name;
        $this->description    = $DO->description;
        $this->quantity       = $DO->quantity;
        $this->refUnit        = $DO->ref_unit;
        $this->oz             = $DO->oz;
        $this->lbNr           = $DO->lb_nr;
        $this->pricePerUnit   = $DO->price_per_unit;
        $this->price          = $DO->price;
        $this->created        = $DO->created;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End StlbElement