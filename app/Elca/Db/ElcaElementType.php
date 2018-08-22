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
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 * @translate db Elca\Db\ElcaElementTypeSet::find() name description
 */
class ElcaElementType extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.element_types';

    /**
     * Root Node idents
     */
    const ROOT_NODE = 'ELCA_ELEMENT_TYPES';



    /**
     * elementTypeId
     */
    private $nodeId;

    /**
     * name
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * din code
     */
    private $dinCode;

    /**
     * indicates constructional types
     */
    private $isConstructional;

    /**
     * indicates opaque types
     */
    private $isOpaque;

    /**
     * preferred refUnit
     */
    private $prefRefUnit;

    /**
     * inclanation
     */
    private $prefInclination;

    /**
     * has element an image
     */
    private $prefHasElementImage;

    /**
     * ext: ident
     */
    private $ident;

    /**
     * ext: projectVariantId
     */
    private $projectVariantId;

    /**
     * ext: number elements for this element type
     */
    private $elementCount;

    /**
     * Primary key
     */
    private static $primaryKey = array('nodeId');

    /**
     * Column types
     */
    private static $columnTypes = array('nodeId'                => PDO::PARAM_INT,
                                        'name'                  => PDO::PARAM_STR,
                                        'description'           => PDO::PARAM_STR,
                                        'dinCode'               => PDO::PARAM_INT,
                                        'isConstructional'      => PDO::PARAM_BOOL,
                                        'isOpaque'              => PDO::PARAM_BOOL,
                                        'prefRefUnit'           => PDO::PARAM_STR,
                                        'prefInclination'       => PDO::PARAM_INT,
                                        'prefHasElementImage'   => PDO::PARAM_BOOL);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('ident'            => PDO::PARAM_STR,
                                           'projectVariantId' => PDO::PARAM_INT,
                                           'elementCount'     => PDO::PARAM_INT
                                           );


    // public


    /**
     * Creates the object
     *
     * @param  integer    $nodeId              - elementTypeId
     * @param  string     $name                - name
     * @param  string     $description         - description
     * @param  integer    $dinCode             - din code
     * @param  boolean    $isConstructional    - indicates constructional types
     * @param bool|null   $isOpaque
     * @param null|string $prefRefUnit
     * @param  integer    $prefInclination     - inclination
     * @param  boolean    $prefHasElementImage - has the element an image?
     * @return ElcaElementType
     */
    public static function create($nodeId, $name, $description = null, $dinCode = null, $isConstructional = null, $isOpaque = null, $prefRefUnit = null, $prefInclination = null, $prefHasElementImage = false)
    {
        $ElcaElementType = new ElcaElementType();
        $ElcaElementType->setNodeId($nodeId);
        $ElcaElementType->setName($name);
        $ElcaElementType->setDescription($description);
        $ElcaElementType->setDinCode($dinCode);
        $ElcaElementType->setIsConstructional($isConstructional);
        $ElcaElementType->setIsOpaque($isOpaque);
        $ElcaElementType->setPrefRefUnit($prefRefUnit);
        $ElcaElementType->setPrefInclination($prefInclination);
        $ElcaElementType->setPrefHasElementImage($prefHasElementImage);

        if($ElcaElementType->getValidator()->isValid())
            $ElcaElementType->insert();

        return $ElcaElementType;
    }
    // End create


    /**
     * Inits a `ElcaElementType' by its primary key
     *
     * @param  boolean $force - Bypass caching
     * @return ElcaElementType
     */
    public static function findRoot($force = false)
    {
        $sql = sprintf("SELECT node_id
                             , name
                             , description
                             , din_code
                             , is_constructional
                             , is_opaque
                             , pref_ref_unit
                             , pref_inclination
                             , pref_has_element_image
                             , ident
                          FROM %s
                         WHERE ident = :rootIdent"
                       , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                       );

        return self::findBySql(get_class(), $sql, array('rootIdent' => self::ROOT_NODE), $force);
    }
    // End findByRootId



    /**
     * Inits a `ElcaElementType' by its primary key
     *
     * @param  integer  $nodeId - nodeId
     * @param  boolean  $force - Bypass caching
     * @return ElcaElementType
     */
    public static function findByNodeId($nodeId, $force = false)
    {
        if(!$nodeId)
            return new ElcaElementType();

        $sql = sprintf("SELECT node_id
                             , name
                             , description
                             , din_code
                             , is_constructional
                             , is_opaque
                             , pref_ref_unit
                             , pref_inclination
                             , pref_has_element_image
                             , ident
                          FROM %s
                         WHERE node_id = :nodeId"
                       , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                       );

        return self::findBySql(get_class(), $sql, array('nodeId' => $nodeId), $force);
    }
    // End findByNodeId



    /**
     * Inits a parent `ElcaElementType' by a childNodeId
     *
     * @param  integer  $nodeId - nodeId
     * @param  boolean  $force - Bypass caching
     * @return ElcaElementType
     */
    public static function findParentByNodeId($nodeId, $force = false)
    {
        if(!$nodeId)
            return new ElcaElementType();

        $sql = sprintf("SELECT p.node_id
                             , p.name
                             , p.description
                             , p.din_code
                             , p.is_constructional
                             , p.is_opaque
                             , p.pref_ref_unit
                             , p.pref_inclination
                             , p.pref_has_element_image
                             , p.ident
                          FROM %s p
                          JOIN %s c ON c.lft BETWEEN p.lft AND p.rgt AND p.level = c.level - 1
                         WHERE c.node_id = :nodeId"
                       , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                       , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                       );

        return self::findBySql(get_class(), $sql, array('nodeId' => $nodeId), $force);
    }
    // End findParentByNodeId



    /**
     * Inits a `ElcaElementType' by its ident
     *
     * @param  integer  $ident
     * @param  boolean  $force    - Bypass caching
     * @return ElcaElementType
     */
    public static function findByIdent($ident, $force = false)
    {
        if(!$ident)
            return new ElcaElementType();

        $sql = sprintf("SELECT node_id
                             , name
                             , description
                             , din_code
                             , is_opaque
                             , pref_ref_unit
                             , is_constructional
                             , pref_inclination
                             , pref_has_element_image
                             , ident
                          FROM %s
                         WHERE ident = :ident"
                       , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                       );

        return self::findBySql(get_class(), $sql, array('ident' => $ident), $force);
    }
    // End findByIdent



    /**
     * Sets the property nodeId
     *
     * @param  integer  $nodeId - elementTypeId
     * @return
     */
    public function setNodeId($nodeId)
    {
        if(!$this->getValidator()->assertNotEmpty('nodeId', $nodeId))
            return;

        $this->nodeId = (int)$nodeId;
    }
    // End setNodeId



    /**
     * Sets the property name
     *
     * @param  string   $name  - name
     * @return
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
     * Sets the property description
     *
     * @param  string   $description - description
     * @return
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End setDescription



    /**
     * Sets the property dinCode
     *
     * @param  integer  $dinCode - din code
     * @return
     */
    public function setDinCode($dinCode = null)
    {
        $this->dinCode = $dinCode;
    }
    // End setDinCode



    /**
     * Sets the property isConstructional
     *
     * @param  boolean  $isConstructional - indicates constructional types
     * @return
     */
    public function setIsConstructional($isConstructional = null)
    {
        $this->isConstructional = $isConstructional;
    }
    // End setIsConstructional



    /**
     * Sets the property isOpaque
     *
     * @param  boolean  $isOpaque - indicates opaque types
     * @return
     */
    public function setIsOpaque($isOpaque = null)
    {
        $this->isOpaque = $isOpaque;
    }
    // End setIsOpaque



    /**
     * Sets the property prefRefUnit
     *
     * @param  string   $prefRefUnit - preferred refUnit
     * @return
     */
    public function setPrefRefUnit($prefRefUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('prefRefUnit', 10, $prefRefUnit))
            return;

        $this->prefRefUnit = $prefRefUnit;
    }
    // End setPrefRefUnit



    /**
     * Sets the property prefInclination
     *
     * @param  integer  $prefInclination - inclination
     * @return
     */
    public function setPrefInclination($prefInclination)
    {
        $this->prefInclination = $prefInclination;
    }
    // End setPrefInclination



    /**
     * Sets the property prefHasElementImage
     *
     * @param  boolean  $prefHasElementImage - has element an image
     * @return
     */
    public function setPrefHasElementImage($prefHasElementImage)
    {
        $this->prefHasElementImage = $prefHasElementImage;
    }
    // End setPrefHasElementImage



    /**
     * Returns the property nodeId
     *
     * @return integer
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }
    // End getNodeId



    /**
     * Returns the associated NestedNode by property nodeId
     *
     * @param  boolean  $force
     * @return NestedNode
     */
    public function getNode($force = false)
    {
        return NestedNode::findById($this->nodeId, $force);
    }
    // End getNode



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
     * Returns the property isConstructional
     *
     * @return boolean
     */
    public function isConstructional()
    {
        return $this->isConstructional;
    }
    // End isConstructional



    /**
     * Returns the property isOpaque
     *
     * @return boolean
     */
    public function isOpaque()
    {
        return $this->isOpaque;
    }
    // End isOpaque



    /**
     * Returns the property prefRefUnit
     *
     * @return string
     */
    public function getPrefRefUnit()
    {
        return $this->prefRefUnit;
    }
    // End getPrefRefUnit



     /**
     * Returns the property prefInclination
     *
     * @return integer
     */
    public function getPrefInclination()
    {
        return $this->prefInclination;
    }
    // End getPrefInclination



    /**
     * Returns the property prefHasElementImage
     *
     * @return boolean
     */
    public function getPrefHasElementImage()
    {
        return $this->prefHasElementImage;
    }
    // End getPrefHasElementImage



    /**
     * Returns the property ident
     *
     * @return number
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent



    /**
     * Returns the ext property projectVariantId
     *
     * @return int
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId



    /**
     * Extension: Returns the number of elements for this type
     *
     * @return int
     */
    public function getElementCount()
    {
        return $this->elementCount;
    }
    // End getElementCount



    /**
     * Returns true if this is the level of composite elements
     *
     * @return boolean
     */
    public function isCompositeLevel()
    {
        if(!$this->isInitialized())
            return false;

        return $this->dinCode % 10 == 0;
    }
    // End isCompositeLevel



    /**
     * Returns the parent node
     *
     * @param  -
     * @return ElcaElementType
     */
    public function getParent()
    {
        return self::findParentByNodeId($this->nodeId);
    }
    // End getParent



    /**
     * Returns a list of element types associated which are childs of this type
     *
     * @param  -
     * @return ElcaElementTypeSet
     */
    public function getChildren()
    {
        return ElcaElementTypeSet::findByParentType($this);
    }
    // End getChildren



    /**
     * Checks, if the object exists
     *
     * @param  integer  $nodeId - elementTypeId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($nodeId, $force = false)
    {
        return self::findByNodeId($nodeId, $force)->isInitialized();
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
                           SET name                 = :name
                             , description          = :description
                             , din_code             = :dinCode
                             , is_constructional    = :isConstructional
                             , is_opaque           = :isOpaque
                             , pref_ref_unit       = :prefRefUnit
                             , pref_inclination     = :prefInclination
                             , pref_has_element_image = :prefHasElementImage
                         WHERE node_id = :nodeId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('nodeId'            => $this->nodeId,
                                        'name'              => $this->name,
                                        'description'       => $this->description,
                                        'dinCode'           => $this->dinCode,
                                        'isConstructional'  => $this->isConstructional,
                                        'isOpaque'           => $this->isOpaque,
                                        'prefRefUnit'        => $this->prefRefUnit,
                                        'prefInclination'  => $this->prefInclination,
                                        'prefHasElementImage'    => $this->prefHasElementImage,)
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
                              WHERE node_id = :nodeId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('nodeId' => $this->nodeId));
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

        $sql = sprintf("INSERT INTO %s (node_id, name, description, din_code, is_constructional, is_opaque, pref_ref_unit, pref_inclination, pref_has_element_image)
                               VALUES  (:nodeId, :name, :description, :dinCode, :isConstructional, :isOpaque, :prefRefUnit, :prefInclination, :prefHasElementImage)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('nodeId'          => $this->nodeId,
                                        'name'            => $this->name,
                                        'description'     => $this->description,
                                        'dinCode'         => $this->dinCode,
                                        'isConstructional' => $this->isConstructional,
                                        'isOpaque'           => $this->isOpaque,
                                        'prefRefUnit'        => $this->prefRefUnit,
                                        'prefInclination'  => $this->prefInclination,
                                        'prefHasElementImage' => $this->prefHasElementImage)
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
        $this->nodeId              = (int)$DO->node_id;
        $this->name                = $DO->name;
        $this->description         = $DO->description;
        $this->dinCode             = $DO->din_code;
        $this->isConstructional    = $DO->is_constructional;
        $this->isOpaque            = $DO->is_opaque;
        $this->prefRefUnit         = $DO->pref_ref_unit;
        $this->prefInclination     = $DO->pref_inclination;
        $this->prefHasElementImage = $DO->pref_has_element_image;

        /**
         * Set extensions
         */
        if(isset($DO->ident))              $this->ident = $DO->ident;
        if(isset($DO->project_variant_id)) $this->projectVariantId = $DO->project_variant_id;
        if(isset($DO->element_count))      $this->elementCount = $DO->element_count;
    }
    // End initByDataObject
}
// End class ElcaElementType