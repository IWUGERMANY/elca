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

use Beibob\Blibs\DbObject;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use PDO;

/**
 *
 * @package   elca
 * @author    Fabian Möller <fab@beibob.de>
 * @author    Tobias Lode <tobias@beibob.de>
 *
 * @translate db \Elca\Db\ElcaIndicatorSet::find() name unit description
 *
 */
class ElcaIndicator extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.indicators';

    /**
     * Some idents
     */
    const IDENT_PE_N_EM = 'peNEm';
    const IDENT_PE_EM = 'peEm';
    const IDENT_PET = 'pet';
    const IDENT_PERE = 'pere';
    const IDENT_PERM = 'perm';
    const IDENT_PENRE = 'penre';
    const IDENT_PENRM = 'penrm';
    const IDENT_PERT = 'pert';
    const IDENT_PENRT = 'penrt';
    const IDENT_GWP = 'gwp';
    const IDENT_ODP = 'odp';

    /**
     * Indicator idents for primary energy renewable
     */
    public static $primaryEnergyRenewableIndicators = array(
        ElcaIndicator::IDENT_PE_EM,
        ElcaIndicator::IDENT_PERE,
        ElcaIndicator::IDENT_PERM,
        ElcaIndicator::IDENT_PERT,
    );

    /**
     * Indicator idents for primary energy not renewable
     */
    public static $primaryEnergyNotRenewableIndicators = array(
        ElcaIndicator::IDENT_PE_N_EM,
        ElcaIndicator::IDENT_PENRE,
        ElcaIndicator::IDENT_PENRM,
        ElcaIndicator::IDENT_PENRT,
    );

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'id'                 => PDO::PARAM_INT,
        'name'               => PDO::PARAM_STR,
        'ident'              => PDO::PARAM_STR,
        'unit'               => PDO::PARAM_STR,
        'isExcluded'         => PDO::PARAM_BOOL,
        'isHidden'           => PDO::PARAM_BOOL,
        'pOrder'             => PDO::PARAM_INT,
        'description'        => PDO::PARAM_STR,
        'uuid'               => PDO::PARAM_STR,
        'isEn15804Compliant' => PDO::PARAM_BOOL,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * indicatorId
     */
    private $id;

    /**
     * a pretty name
     */
    private $name;

    /**
     * indicator short name
     */
    private $ident;

    /**
     * unit of measure
     */
    private $unit;

    /**
     * exclude from lca
     */
    private $isExcluded;

    /**
     * hidden on screen
     */
    private $isHidden;

    /**
     * presentation order
     */
    private $pOrder;

    /**
     * Description
     */
    private $description;

    /**
     * uuid
     */
    private $uuid;

    /**
     * isEn15804Compliant
     */
    private $isEn15804Compliant;

    /**
     * Creates the object
     *
     * @param  integer $id                 - an identifier
     * @param  string  $name               - a pretty name
     * @param  string  $ident              - indicator short name
     * @param  string  $unit               - unit of measure
     * @param  boolean $isExcluded         - exclude from lca
     * @param  integer $pOrder             - presentation order
     * @param  string  $description        - description
     * @param  string  $uuid               - uuid
     * @param  boolean $isExcluded         - exclude from lca
     * @param  boolean $isEn15804Compliant - isEn15804Compliant
     */
    public static function create(
        $id,
        $name,
        $ident,
        $unit,
        $isExcluded = false,
        $isHidden = false,
        $pOrder = null,
        $description = null,
        $uuid = null,
        $isEn15804Compliant = false
    ) {
        $ElcaIndicator = new ElcaIndicator();
        $ElcaIndicator->setId($id);
        $ElcaIndicator->setName($name);
        $ElcaIndicator->setIdent($ident);
        $ElcaIndicator->setUnit($unit);
        $ElcaIndicator->setIsExcluded($isExcluded);
        $ElcaIndicator->setIsHidden($isHidden);
        $ElcaIndicator->setPOrder($pOrder);
        $ElcaIndicator->setDescription($description);
        $ElcaIndicator->setUuid($uuid);
        $ElcaIndicator->setIsEn15804Compliant($isEn15804Compliant);

        if ($ElcaIndicator->getValidator()->isValid()) {
            $ElcaIndicator->insert();
        }

        return $ElcaIndicator;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaIndicator' by its primary key
     *
     * @param  integer $id    - indicatorId
     * @param  boolean $force - Bypass caching
     * @return ElcaIndicator
     */
    public static function findById($id, $force = false)
    {
        if (!$id) {
            return new ElcaIndicator();
        }

        $sql = sprintf(
            "SELECT id
                             , name
                             , ident
                             , unit
                             , is_excluded
                             , is_hidden
                             , p_order
                             , description
                             , uuid
                             , is_en15804_compliant
                          FROM %s
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaIndicator' by its unique key (ident)
     *
     * @param  string  $ident - indicator short name
     * @param  boolean $force - Bypass caching
     * @return ElcaIndicator
     */
    public static function findByIdent($ident, $force = false)
    {
        if (!$ident) {
            return new ElcaIndicator();
        }

        $sql = sprintf(
            "SELECT id
                             , name
                             , ident
                             , unit
                             , is_excluded
                             , is_hidden
                             , p_order
                             , description
                             , uuid
                             , is_en15804_compliant
                          FROM %s
                         WHERE ident = :ident"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('ident' => $ident), $force);
    }
    // End findByIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaIndicator' by its unique key (uuid)
     *
     * @param  string  $uuid  - uuid
     * @param  boolean $force - Bypass caching
     * @return ElcaIndicator
     */
    public static function findByUuid($uuid, $force = false)
    {
        if (!$uuid) {
            return new ElcaIndicator();
        }

        $sql = sprintf(
            "SELECT id
                             , name
                             , ident
                             , unit
                             , is_excluded
                             , is_hidden
                             , p_order
                             , description
                             , uuid
                             , is_en15804_compliant
                          FROM %s
                         WHERE uuid = :uuid"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('uuid' => $uuid), $force);
    }
    // End findByUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer $id    - indicatorId
     * @param  boolean $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End setId

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
    // End setName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  boolean $extColumns
     * @param  mixed   $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column) {
            return $columnTypes[$column];
        }

        return $columnTypes;
    }
    // End setIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End setUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property id
     *
     * @param  integer $id - an identifier
     * @return
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }
    // End setIsExcluded

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    // End setPOrder

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property name
     *
     * @param  string $name - a pretty name
     * @return
     */
    public function setName($name)
    {
        if (!$this->getValidator()->assertNotEmpty('name', $name)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('name', 150, $name)) {
            return;
        }

        $this->name = (string)$name;
    }
    // End setDescription

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End setUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string $ident - indicator short name
     * @return
     */
    public function setIdent($ident)
    {
        if (!$this->getValidator()->assertNotEmpty('ident', $ident)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('ident', 20, $ident)) {
            return;
        }

        $this->ident = (string)$ident;
    }
    // End setIsEn15804Compliant

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property unit
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property unit
     *
     * @param  string $unit - unit of measure
     * @return
     */
    public function setUnit($unit)
    {
        if (!$this->getValidator()->assertNotEmpty('unit', $unit)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('unit', 50, $unit)) {
            return;
        }

        $this->unit = (string)$unit;
    }
    // End getName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property isExcluded
     *
     * @return boolean
     */
    public function isExcluded()
    {
        return $this->isExcluded;
    }
    // End getIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isExcluded
     *
     * @param  boolean $isExcluded - exclude from lca
     * @return
     */
    public function setIsExcluded($isExcluded = false)
    {
        $this->isExcluded = (bool)$isExcluded;
    }

    /**
     * @return mixed
     */
    public function isHidden()
    {
        return $this->isHidden;
    }

    /**
     * @param mixed $isHidden
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
    }


    /**
     * Retunrs the property pOrder
     *
     * @return  int
     */
    public function getPOrder()
    {
        return $this->pOrder;
    }
    // End isExcluded

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property pOrder
     *
     * @param  int $pOrder
     * @return
     */
    public function setPOrder($pOrder = null)
    {
        $this->pOrder = $pOrder;
    }
    // End getPOrder

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property description
     *
     * @return  string   $description
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getDescription

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property description
     *
     * @param  string $description
     * @return
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    // End getUuid

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
    // End isEn15804Compliant

    /**
     * Sets the property uuid
     *
     * @param  string $uuid - an identifier
     * @return
     */
    public function setUuid($uuid = null)
    {
        $this->uuid = $uuid;
    }

    /**
     * Returns the property isEn15804Compliant
     *
     * @return boolean
     */
    public function isEn15804Compliant()
    {
        return $this->isEn15804Compliant;
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isEn15804Compliant
     *
     * @param  boolean $isEn15804Compliant - isEn15804Compliant
     * @return
     */
    public function setIsEn15804Compliant($isEn15804Compliant = false)
    {
        $this->isEn15804Compliant = (bool)$isEn15804Compliant;
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return Indicator
     */
    public function getIndicator()
    {
        return new Indicator(
            new IndicatorId($this->id),
            $this->name,
            new IndicatorIdent($this->ident),
            $this->unit,
            $this->isEn15804Compliant
        );
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf(
            "UPDATE %s
                           SET name           = :name
                             , ident          = :ident
                             , unit           = :unit
                             , is_excluded    = :isExcluded
                             , is_hidden    = :isHidden
                             , p_order        = :pOrder
                             , description    = :description
                             , uuid           = :uuid
                             , is_en15804_compliant = :isEn15804Compliant
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'id'                 => $this->id,
                'name'               => $this->name,
                'ident'              => $this->ident,
                'unit'               => $this->unit,
                'isExcluded'         => $this->isExcluded,
                'isHidden'           => $this->isHidden,
                'pOrder'             => $this->pOrder,
                'description'        => $this->description,
                'uuid'               => $this->uuid,
                'isEn15804Compliant' => $this->isEn15804Compliant,
            )
        );
    }
    // End getPrimaryKey

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            array('id' => $this->id)
        );
    }
    // End getTablename

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly) {
            return self::$primaryKey;
        }

        $primaryKey = array();

        foreach (self::$primaryKey as $key) {
            $primaryKey[$key] = $this->$key;
        }

        return $primaryKey;
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
        $sql = sprintf(
            "INSERT INTO %s (id, name, ident, unit, is_excluded, is_hidden, p_order, description, uuid, is_en15804_compliant)
                               VALUES  (:id, :name, :ident, :unit, :isExcluded, :isHidden, :pOrder, :description, :uuid, :isEn15804Compliant)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'id'                 => $this->id,
                'name'               => $this->name,
                'ident'              => $this->ident,
                'unit'               => $this->unit,
                'isExcluded'         => $this->isExcluded,
                'isHidden'           => $this->isHidden,
                'pOrder'             => $this->pOrder,
                'description'        => $this->description,
                'uuid'               => $this->uuid,
                'isEn15804Compliant' => $this->isEn15804Compliant,
            )
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
        $this->id                 = (int)$DO->id;
        $this->name               = $DO->name;
        $this->ident              = $DO->ident;
        $this->unit               = $DO->unit;
        $this->isExcluded         = (bool)$DO->is_excluded;
        $this->isHidden           = (bool)$DO->is_hidden;
        $this->pOrder             = $DO->p_order;
        $this->description        = $DO->description;
        $this->uuid               = $DO->uuid;
        $this->isEn15804Compliant = (bool)$DO->is_en15804_compliant;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaIndicator