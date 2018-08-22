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
 * @translate db Elca\Db\ElcaLifeCycleSet::find() description
 * @translate value 'Nutzung'
 * @translate value 'Entsorgung'
 * @translate value 'Herstellung'
 * @translate value 'Instandhaltung'
 * @translate value 'Gesamt'
 *
 */
class ElcaLifeCycle extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.life_cycles';

    /**
     * LC phase idents
     */
    const PHASE_PROD = 'prod';
    const PHASE_OP   = 'op';
    const PHASE_EOL  = 'eol';
    const PHASE_REC  = 'rec';

    const PHASE_MAINT = 'maint';
    const PHASE_TOTAL = 'total';

    /**
     * LC idents
     */
    const IDENT_A13 = 'A1-3';
    const IDENT_A1 = 'A1';
    const IDENT_A2 = 'A2';
    const IDENT_A3 = 'A3';
    const IDENT_A4 = 'A4';
    const IDENT_A5 = 'A5';
    const IDENT_C1 = 'C1';
    const IDENT_C2 = 'C2';
    const IDENT_C3 = 'C3';
    const IDENT_C4 = 'C4';
    const IDENT_B6 = 'B6';
    const IDENT_D  = 'D';

    public static $identPhaseMap = [
        self::IDENT_A13 => self::PHASE_PROD,
        self::IDENT_A1  => self::PHASE_PROD,
        self::IDENT_A2  => self::PHASE_PROD,
        self::IDENT_A3  => self::PHASE_PROD,
        self::IDENT_A4  => self::PHASE_PROD,
        self::IDENT_A5  => self::PHASE_PROD,
        self::IDENT_C1  => self::PHASE_EOL,
        self::IDENT_C2  => self::PHASE_EOL,
        self::IDENT_C3  => self::PHASE_EOL,
        self::IDENT_C4  => self::PHASE_EOL,
        self::IDENT_B6  => self::PHASE_OP,
        self::IDENT_D   => self::PHASE_REC,
        self::PHASE_PROD => self::PHASE_PROD,
        self::PHASE_OP => self::PHASE_OP,
        self::PHASE_EOL => self::PHASE_EOL,
    ];

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * life cycle short name
     */
    private $ident;

    /**
     * a pretty name
     */
    private $name;

    /**
     * associated phase
     */
    private $phase;

    /**
     * presentation order
     */
    private $pOrder;

    /**
     * Description
     */
    private $description;

    /**
     * Primary key
     */
    private static $primaryKey = ['ident'];

    /**
     * Column types
     */
    private static $columnTypes = ['ident'          => PDO::PARAM_STR,
                                        'name'           => PDO::PARAM_STR,
                                        'phase'          => PDO::PARAM_STR,
                                        'pOrder'         => PDO::PARAM_INT,
                                        'description'    => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    /**
     * Valid phases
     */
    private static $validPhases = [self::PHASE_PROD,
                                        self::PHASE_OP,
                                        self::PHASE_EOL,
                                        self::PHASE_REC,
                                        self::PHASE_MAINT,
                                        self::PHASE_TOTAL];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  string   $ident - life cycle short name
     * @param  string   $name - a pretty name
     * @param  string   $phase - associated phase
     */
    public static function create($ident, $name, $phase, $pOrder = null, $description = null)
    {
        $ElcaLifeCycle = new ElcaLifeCycle();
        $ElcaLifeCycle->setIdent($ident);
        $ElcaLifeCycle->setName($name);
        $ElcaLifeCycle->setPhase($phase);
        $ElcaLifeCycle->setPOrder($pOrder);
        $ElcaLifeCycle->setDescription($description);

        if($ElcaLifeCycle->getValidator()->isValid())
            $ElcaLifeCycle->insert();

        return $ElcaLifeCycle;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaLifeCycle' by its primary key
     *
     * @param  string   $ident - life cycle short name
     * @param  boolean  $force - Bypass caching
     * @return ElcaLifeCycle
     */
    public static function findByIdent($ident, $force = false)
    {
        if(!$ident)
            return new ElcaLifeCycle();

        $sql = sprintf("SELECT ident
                             , name
                             , phase
                             , p_order
                             , description
                          FROM %s
                         WHERE ident = :ident"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['ident' => $ident], $force);
    }
    // End findByIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string   $ident - life cycle short name
     * @return
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;

        if(!$this->getValidator()->assertMaxLength('ident', 20, $ident))
            return;

        $this->ident = (string)$ident;
    }
    // End setIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property name
     *
     * @param  string   $name  - a pretty name
     * @return
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        if(!$this->getValidator()->assertMaxLength('name', 150, $name))
            return;

        $this->name = (string)$name;
    }
    // End setName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property phase
     *
     * @param  string   $phase - associated phase
     * @return
     */
    public function setPhase($phase)
    {
        if(!$this->getValidator()->assertNotEmpty('phase', $phase))
            return;

        if(!$this->getValidator()->assertMaxLength('phase', 50, $phase))
            return;

        $this->phase = (string)$phase;
    }
    // End setPhase

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
    // End setPOrder

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property description
     *
     * @param  string   $description
     * @return
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
    // End getIdent

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
    // End getName

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property phase
     *
     * @return string
     */
    public function getPhase()
    {
        return $this->phase;
    }
    // End getPhase

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Retunrs the property pOrder
     *
     * @return  int
     */
    public function getPOrder()
    {
        return $this->pOrder;
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
     * Checks, if the object exists
     *
     * @param  string   $ident - life cycle short name
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($ident, $force = false)
    {
        return self::findByIdent($ident, $force)->isInitialized();
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
                           SET name           = :name
                             , phase          = :phase
                             , p_order        = :pOrder
                             , description    = :description
                         WHERE ident = :ident"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['ident'         => $this->ident,
                                        'name'          => $this->name,
                                        'phase'         => $this->phase,
                                        'pOrder'        => $this->pOrder,
                                        'description'   => $this->description
                                        ]
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
                              WHERE ident = :ident"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  ['ident' => $this->ident]);
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

        $sql = sprintf("INSERT INTO %s (ident, name, phase, p_order, description)
                               VALUES  (:ident, :name, :phase, :pOrder, :description)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['ident'         => $this->ident,
                                        'name'          => $this->name,
                                        'phase'         => $this->phase,
                                        'pOrder'        => $this->pOrder,
                                        'description'   => $this->description]
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
        $this->ident          = $DO->ident;
        $this->name           = $DO->name;
        $this->phase          = $DO->phase;
        $this->pOrder         = $DO->p_order;
        $this->description    = $DO->description;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaLifeCycle