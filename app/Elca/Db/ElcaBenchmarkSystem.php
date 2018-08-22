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
use PDO;

/**
 * @package    elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 */
class ElcaBenchmarkSystem extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.benchmark_systems';

    /**
     * benchmarkSystemId
     */
    private $id;

    /**
     * system name
     */
    private $name;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * active flag
     */
    private $isActive;

    /**
     * description
     */
    private $description;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'          => PDO::PARAM_INT,
                                        'name'        => PDO::PARAM_STR,
                                        'modelClass'  => PDO::PARAM_STR,
                                        'isActive'    => PDO::PARAM_BOOL,
                                        'description' => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  string $name - system name
     * @param  boolean $isActive - active flag
     * @param  string $description - description
     * @return ElcaBenchmarkSystem
     */
    public static function create($name, $modelClass, $isActive = false, $description = null)
    {
        $ElcaBenchmarkSystem = new ElcaBenchmarkSystem();
        $ElcaBenchmarkSystem->setName($name);
        $ElcaBenchmarkSystem->setModelClass($modelClass);
        $ElcaBenchmarkSystem->setIsActive($isActive);
        $ElcaBenchmarkSystem->setDescription($description);

        if ($ElcaBenchmarkSystem->getValidator()->isValid())
            $ElcaBenchmarkSystem->insert();

        return $ElcaBenchmarkSystem;
    }
    // End create


    /**
     * Inits a `ElcaBenchmarkSystem' by its primary key
     *
     * @param  integer $id - benchmarkSystemId
     * @param  boolean $force - Bypass caching
     * @return ElcaBenchmarkSystem
     */
    public static function findById($id, $force = false)
    {
        if (!$id)
            return new ElcaBenchmarkSystem();

        $sql = sprintf("SELECT id
                             , name
                             , model_class
                             , is_active
                             , description
                          FROM %s
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }

    public static function findByVersionId($benchmarkVersionId, $force = false)
    {
        if (!$benchmarkVersionId) {
            return new ElcaBenchmarkSystem();
        }

        $sql = sprintf("SELECT s.id
                             , s.name
                             , s.model_class
                             , s.is_active
                             , s.description
                          FROM %s s
                          JOIN %s v ON s.id = v.benchmark_system_id
                         WHERE v.id = :benchmarkVesionId"
            , self::TABLE_NAME
            , ElcaBenchmarkVersion::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('benchmarkVesionId' => $benchmarkVersionId), $force);
    }

    /**
     * Sets the property name
     *
     * @param  string $name - system name
     * @return void
     */
    public function setName($name)
    {
        if (!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        if (!$this->getValidator()->assertMaxLength('name', 150, $name))
            return;

        $this->name = (string)$name;
    }

    public function setModelClass(string $modelClass): void
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Sets the property isActive
     *
     * @param  boolean $isActive - active flag
     * @return
     */
    public function setIsActive($isActive = false)
    {
        $this->isActive = (bool)$isActive;
    }
    // End setIsActive


    /**
     * Sets the property description
     *
     * @param  string $description - description
     * @return
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End setDescription


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
     * Returns the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Returns the property isActive
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }
    // End isActive


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
     * @return bool
     */
    public function isUsedInProject()
    {
        return ElcaBenchmarkVersionSet::countProjectsByBenchmarkSystemId($this->id) > 0;
    }
    // End isUsedInProject


    /**
     * Checks, if the object exists
     *
     * @param  integer $id - benchmarkSystemId
     * @param  boolean $force - Bypass caching
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
                           SET name           = :name
                             , model_class    = :modelClass
                             , is_active      = :isActive
                             , description    = :description
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return $this->updateBySql($sql,
            array('id'          => $this->id,
                  'name'        => $this->name,
                  'modelClass'  => $this->modelClass,
                  'isActive'    => $this->isActive,
                  'description' => $this->description)
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
     * @param  boolean $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly)
            return self::$primaryKey;

        $primaryKey = array();

        foreach (self::$primaryKey as $key)
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
     * @param  boolean $extColumns
     * @param  mixed $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column)
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
        $this->id = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, name, model_class, is_active, description)
                               VALUES  (:id, :name, :modelClass, :isActive, :description)"
            , self::TABLE_NAME
        );

        return $this->insertBySql($sql,
            array('id'          => $this->id,
                  'name'        => $this->name,
                  'modelClass'  => $this->modelClass,
                  'isActive'    => $this->isActive,
                  'description' => $this->description)
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
        $this->id = (int)$DO->id;
        $this->name = $DO->name;
        $this->modelClass = $DO->model_class;
        $this->isActive = (bool)$DO->is_active;
        $this->description = $DO->description;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaBenchmarkSystem