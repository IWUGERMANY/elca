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
class ElcaProjectConstruction extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_constructions';


    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * default constr catalog id
     */
    private $constrCatalogId;

    /**
     * default constr design id
     */
    private $constrDesignId;

    /**
     * indicates an extant building
     */
    private $isExtantBuilding;

    /**
     * Bruttogeschossfläche in m2
     */
    private $grossFloorSpace;

    /**
     * Nettogrundfläche in m2
     */
    private $netFloorSpace;

    /**
     * Nutzfläche in m2
     */
    private $floorSpace;

    /**
     * property size in m2
     */
    private $propertySize;

    /**
     * in m2
     */
    private $livingSpace;

    /**
     * Primary key
     */
    private static $primaryKey = array('projectVariantId');

    /**
     * Column types
     */
    private static $columnTypes = array('projectVariantId' => PDO::PARAM_INT,
                                        'constrCatalogId'  => PDO::PARAM_INT,
                                        'constrDesignId'   => PDO::PARAM_INT,
                                        'isExtantBuilding' => PDO::PARAM_BOOL,
                                        'grossFloorSpace'  => PDO::PARAM_INT,
                                        'netFloorSpace'    => PDO::PARAM_INT,
                                        'floorSpace'       => PDO::PARAM_INT,
                                        'propertySize'     => PDO::PARAM_INT,
                                        'livingSpace'      => PDO::PARAM_INT,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param  integer $constrCatalogId  - default constr catalog id
     * @param  integer $constrDesignId   - default constr design id
     * @param  integer $grossFloorSpace  - Bruttogeschossfläche in m2
     * @param  integer $netFloorSpace    - Nettogrundfläche in m2
     * @param  integer $floorSpace       - Nutzfläche in m2
     * @param  integer $propertySize     - property size in m2
     * @param bool     $isExtantBuilding
     * @return ElcaProjectConstruction
     */
    public static function create($projectVariantId, $constrCatalogId = null, $constrDesignId = null, $grossFloorSpace = null, $netFloorSpace = null, $floorSpace = null, $propertySize = null, $livingSpace = null, $isExtantBuilding = false)
    {
        $projectConstruction = new ElcaProjectConstruction();
        $projectConstruction->setProjectVariantId($projectVariantId);
        $projectConstruction->setConstrCatalogId($constrCatalogId);
        $projectConstruction->setConstrDesignId($constrDesignId);
        $projectConstruction->setGrossFloorSpace($grossFloorSpace);
        $projectConstruction->setNetFloorSpace($netFloorSpace);
        $projectConstruction->setFloorSpace($floorSpace);
        $projectConstruction->setPropertySize($propertySize);
        $projectConstruction->setLivingSpace($livingSpace);
        $projectConstruction->setIsExtantBuilding($isExtantBuilding);

        if($projectConstruction->getValidator()->isValid())
            $projectConstruction->insert();

        return $projectConstruction;
    }
    // End create



    /**
     * Inits a `ElcaProjectConstruction' by its primary key
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  boolean  $force           - Bypass caching
     * @return ElcaProjectConstruction
     */
    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaProjectConstruction();

        $sql = sprintf("SELECT project_variant_id
                             , constr_catalog_id
                             , constr_design_id
                             , is_extant_building
                             , gross_floor_space
                             , net_floor_space
                             , floor_space
                             , property_size
                             , living_space
                          FROM %s
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }
    // End findByProjectVariantId



    /**
     * Creates a deep copy from this ProjectConstruction
     *
     * @param  int $projectVariantId new project variant id
     * @return ElcaProjectConstruction - the new copy
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectConstruction();

        $Copy = self::create($projectVariantId,
                             $this->constrCatalogId,
                             $this->constrDesignId,
                             $this->grossFloorSpace,
                             $this->netFloorSpace,
                             $this->floorSpace,
                             $this->propertySize,
                             $this->livingSpace,
                             $this->isExtantBuilding
                             );

        return $Copy;
    }
    // End copy



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



    /**
     * Sets the property constrCatalogId
     *
     * @param  integer  $constrCatalogId - default constr catalog id
     * @return
     */
    public function setConstrCatalogId($constrCatalogId = null)
    {
        $this->constrCatalogId = $constrCatalogId;
    }
    // End setConstrCatalogId



    /**
     * Sets the property constrDesignId
     *
     * @param  integer  $constrDesignId - default constr design id
     * @return
     */
    public function setConstrDesignId($constrDesignId = null)
    {
        $this->constrDesignId = $constrDesignId;
    }
    // End setConstrDesignId



    /**
     * Sets the property isExtantBuilding
     *
     * @param  boolean $isExtantBuilding
     * @return void
     */
    public function setIsExtantBuilding($isExtantBuilding = false)
    {
        $this->isExtantBuilding = (bool)$isExtantBuilding;
    }
    // End setIsExtantBuilding



    /**
     * Sets the property grossFloorSpace
     *
     * @param  integer  $grossFloorSpace - Bruttogeschossfläche in m2
     * @return
     */
    public function setGrossFloorSpace($grossFloorSpace = null)
    {
        $this->grossFloorSpace = $grossFloorSpace;
    }
    // End setGrossFloorSpace



    /**
     * Sets the property netFloorSpace
     *
     * @param  integer  $netFloorSpace - Nettogrundfläche in m2
     * @return
     */
    public function setNetFloorSpace($netFloorSpace = null)
    {
        $this->netFloorSpace = $netFloorSpace;
    }
    // End setNetFloorSpace



    /**
     * Sets the property floorSpace
     *
     * @param  integer  $floorSpace - Nutzfläche in m2
     * @return
     */
    public function setFloorSpace($floorSpace = null)
    {
        $this->floorSpace = $floorSpace;
    }
    // End setFloorSpace



    /**
     * Sets the property propertySize
     *
     * @param  integer  $propertySize - property size in m2
     * @return
     */
    public function setPropertySize($propertySize = null)
    {
        $this->propertySize = $propertySize;
    }
    // End setPropertySize



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
     * Returns the property constrCatalogId
     *
     * @return integer
     */
    public function getConstrCatalogId()
    {
        return $this->constrCatalogId;
    }
    // End getConstrCatalogId



    /**
     * Returns the associated ElcaConstrCatalog by property constrCatalogId
     *
     * @param  boolean  $force
     * @return ElcaConstrCatalog
     */
    public function getConstrCatalog($force = false)
    {
        return ElcaConstrCatalog::findById($this->constrCatalogId, $force);
    }
    // End getConstrCatalog



    /**
     * Returns the property constrDesignId
     *
     * @return integer
     */
    public function getConstrDesignId()
    {
        return $this->constrDesignId;
    }
    // End getConstrDesignId



    /**
     * Returns the associated ElcaConstrDesign by property constrDesignId
     *
     * @param  boolean  $force
     * @return ElcaConstrDesign
     */
    public function getConstrDesign($force = false)
    {
        return ElcaConstrDesign::findById($this->constrDesignId, $force);
    }
    // End getConstrDesign



    /**
     * Returns the property isExtantBuilding
     *
     * @return boolean
     */
    public function isExtantBuilding()
    {
        return $this->isExtantBuilding;
    }
    // End isExtantBuilding


    /**
     * Returns the property grossFloorSpace
     *
     * @return float|int
     */
    public function getGrossFloorSpace()
    {
        return $this->grossFloorSpace;
    }
    // End getGrossFloorSpace



    /**
     * Returns the property netFloorSpace
     *
     * @return integer
     */
    public function getNetFloorSpace()
    {
        return $this->netFloorSpace;
    }
    // End getNetFloorSpace



    /**
     * Returns the property floorSpace
     *
     * @return integer
     */
    public function getFloorSpace()
    {
        return $this->floorSpace;
    }
    // End getFloorSpace



    /**
     * Returns the property propertySize
     *
     * @return integer
     */
    public function getPropertySize()
    {
        return $this->propertySize;
    }

    public function getLivingSpace()
    {
        return $this->livingSpace;
    }

    public function setLivingSpace($livingSpace)
    {
        $this->livingSpace = $livingSpace;
    }

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
                           SET constr_catalog_id = :constrCatalogId
                             , constr_design_id = :constrDesignId
                             , is_extant_building = :isExtantBuilding
                             , gross_floor_space = :grossFloorSpace
                             , net_floor_space  = :netFloorSpace
                             , floor_space      = :floorSpace
                             , property_size    = :propertySize
                             , living_space    = :livingSpace
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'constrCatalogId' => $this->constrCatalogId,
                                        'constrDesignId'  => $this->constrDesignId,
                                        'isExtantBuilding' => $this->isExtantBuilding,
                                        'grossFloorSpace' => $this->grossFloorSpace,
                                        'netFloorSpace'   => $this->netFloorSpace,
                                        'floorSpace'      => $this->floorSpace,
                                        'propertySize'    => $this->propertySize,
                                        'livingSpace'    => $this->livingSpace,
                                  )
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

        $sql = sprintf("INSERT INTO %s (project_variant_id, constr_catalog_id, constr_design_id, is_extant_building, gross_floor_space, net_floor_space, floor_space, property_size, living_space)
                               VALUES  (:projectVariantId, :constrCatalogId, :constrDesignId, :isExtantBuilding, :grossFloorSpace, :netFloorSpace, :floorSpace, :propertySize, :livingSpace)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'constrCatalogId' => $this->constrCatalogId,
                                        'constrDesignId'  => $this->constrDesignId,
                                        'isExtantBuilding' => $this->isExtantBuilding,
                                        'grossFloorSpace' => $this->grossFloorSpace,
                                        'netFloorSpace'   => $this->netFloorSpace,
                                        'floorSpace'      => $this->floorSpace,
                                        'propertySize'    => $this->propertySize,
                                        'livingSpace'    => $this->livingSpace,
                                  )
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
        $this->constrCatalogId  = $DO->constr_catalog_id;
        $this->constrDesignId   = $DO->constr_design_id;
        $this->isExtantBuilding = (bool)$DO->is_extant_building;
        $this->grossFloorSpace  = $DO->gross_floor_space;
        $this->netFloorSpace    = $DO->net_floor_space;
        $this->floorSpace       = $DO->floor_space;
        $this->propertySize     = $DO->property_size;
        $this->livingSpace = $DO->living_space;
    }
}
