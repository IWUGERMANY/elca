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
 * @translate db Elca\Db\ElcaProjectPhaseSet::find() name
 *
 */
class ElcaProjectPhase extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_phases';

    const IDENT_VORPL = 'VORPL';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectPhaseId
     */
    private $id;

    /**
     * name
     */
    private $name;

    /**
     * internal short name
     */
    private $ident;

    /**
     * construction measure
     */
    private $constrMeasure;

    /**
     * Step
     */
    private $step;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'             => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'ident'          => PDO::PARAM_STR,
                                        'constrMeasure'  => PDO::PARAM_INT);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    //////////////////////////////////////////////////////////////////////////////////////
    // public/
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  string   $name         - name
     * @param  integer  $constrMeasure - construction measure
     * @param  string   $ident        - internal short name
     */
    public static function create($name, $constrMeasure, $ident = null, $step = 1)
    {
        $ElcaProjectPhase = new ElcaProjectPhase();
        $ElcaProjectPhase->setName($name);
        $ElcaProjectPhase->setConstrMeasure($constrMeasure);
        $ElcaProjectPhase->setIdent($ident);
        $ElcaProjectPhase->setStep($step);

        if($ElcaProjectPhase->getValidator()->isValid())
            $ElcaProjectPhase->insert();

        return $ElcaProjectPhase;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProjectPhase' by its primary key
     *
     * @param  integer  $id    - projectPhaseId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProjectPhase
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProjectPhase();

        $sql = sprintf("SELECT id
                             , name
                             , ident
                             , constr_measure
                             , step
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProjectPhase' by constrMeasure and ident
     *
     * @param  int $constrMeasure
     * @param  string $ident
     * boolean  $force - Bypass caching
     * @return ElcaProjectPhase
     */
    public static function findByConstrMeasureAndIdent($constrMeasure, $ident, $force = false)
    {
        if(!$ident)
            return new ElcaProjectPhase();

        $sql = sprintf("SELECT id
                             , name
                             , ident
                             , constr_measure
                             , step
                          FROM %s
                         WHERE ident = :ident
                           AND constr_measure = :constrMeasure"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('ident' => $ident,
                                                        'constrMeasure' => $constrMeasure), $force);
    }
    // End findByConstrMeasureAndIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Gets the min id that represents the first phase. Needed for creation of projects
     *
     * @return self
     */
    public static function findMinIdByConstrMeasure($constrMeasure, $minStep = 0, $force = false)
    {
        if (!$constrMeasure)
            return;

        $sql = sprintf("SELECT id
                             , name
                             , ident
                             , constr_measure
                             , step
                          FROM %s
                         WHERE constr_measure = :cm
                           AND step >= :minStep
                         ORDER BY step LIMIT 1"
                       , self::TABLE_NAME);

        return self::findBySql(get_class(), $sql, [
            'cm' => $constrMeasure,
            'minStep' => $minStep
        ], $force);
    }
    // End findMinIdByConstrMeasure

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string   $ident - internal short name
     * @return
     */
    public function setIdent($ident = null)
    {
        if(!$this->getValidator()->assertMaxLength('ident', 100, $ident))
            return;

        $this->ident = $ident;
    }
    // End setIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property constrMeasure
     *
     * @param  integer  $constrMeasure - construction measure
     * @return
     */
    public function setConstrMeasure($constrMeasure)
    {
        if(!$this->getValidator()->assertNotEmpty('constrMeasure', $constrMeasure))
            return;

        $this->constrMeasure = (int)$constrMeasure;
    }
    // End setConstrMeasure

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property step
     *
     * @param  string   $step
     * @return
     */
    public function setStep($step = 1)
    {
        $this->step = $step;
    }
    // End setStep

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
    // End getId

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
     * Returns the property constrMeasure
     *
     * @return integer
     */
    public function getConstrMeasure()
    {
        return $this->constrMeasure;
    }
    // End getConstrMeasure

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property step
     *
     * @return  int
     */
    public function getStep()
    {
        return $this->step;
    }
    // End getStep

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - projectPhaseId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
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
                             , ident          = :ident
                             , constr_measure = :constrMeasure
                             , step = :step
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'ident'         => $this->ident,
                                        'constrMeasure' => $this->constrMeasure,
                                        'step'          => $this->step
                                        )
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
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('id' => $this->id));
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
        $this->id = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, name, ident, constr_measure, step)
                               VALUES  (:id, :name, :ident, :constrMeasure, :step)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'            => $this->id,
                                        'name'          => $this->name,
                                        'ident'         => $this->ident,
                                        'constrMeasure' => $this->constrMeasure,
                                        'step'          => $this->step
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
        $this->id             = (int)$DO->id;
        $this->name           = $DO->name;
        $this->ident          = $DO->ident;
        $this->constrMeasure  = (int)$DO->constr_measure;
        $this->step           = (int)$DO->step;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectPhase