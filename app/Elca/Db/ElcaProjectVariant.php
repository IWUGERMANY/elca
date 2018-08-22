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
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectVariant extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_variants';


    /**
     * projectVariantId
     */
    private $id;

    /**
     * project id
     */
    private $projectId;

    /**
     * project phase id
     */
    private $phaseId;

    /**
     * name
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = ['id'          => PDO::PARAM_INT,
                                   'projectId'   => PDO::PARAM_INT,
                                   'phaseId'     => PDO::PARAM_INT,
                                   'name'        => PDO::PARAM_STR,
                                   'description' => PDO::PARAM_STR,
                                   'created'     => PDO::PARAM_STR,
                                   'modified'    => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    /**
     * Creates the object
     *
     * @param  integer $projectId   - project id
     * @param  integer $phaseId     - project phase id
     * @param  string  $name        - name
     * @param  string  $description - description
     */
    public static function create($projectId, $phaseId, $name, $description = null)
    {
        $ElcaProjectVariant = new ElcaProjectVariant();
        $ElcaProjectVariant->setProjectId($projectId);
        $ElcaProjectVariant->setPhaseId($phaseId);
        $ElcaProjectVariant->setName($name);
        $ElcaProjectVariant->setDescription($description);

        if ($ElcaProjectVariant->getValidator()->isValid())
            $ElcaProjectVariant->insert();

        return $ElcaProjectVariant;
    }
    // End create


    /**
     * Inits a `ElcaProjectVariant' by its primary key
     *
     * @param  integer $id    - projectVariantId
     * @param  boolean $force - Bypass caching
     *
     * @return ElcaProjectVariant
     */
    public static function findById($id, $force = false)
    {
        if (!$id)
            return new ElcaProjectVariant();

        $sql = sprintf("SELECT id
                             , project_id
                             , phase_id
                             , name
                             , description
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById


    /**
     * Inits a `ElcaProjectVariant' by it projectId and phaseId
     *
     * @param  integer $projectId - project id
     * @param  integer $phaseId   - project phase id
     * @param  boolean $force     - Bypass caching
     *
     * @return ElcaProjectVariant
     */
    public static function findByProjectIdAndPhaseId($projectId, $phaseId, $force = false)
    {
        if (!$projectId or !$phaseId)
            return new ElcaProjectVariant();

        $sql = sprintf("SELECT id
                             , project_id
                             , phase_id
                             , name
                             , description
                             , created
                             , modified
                          FROM %s
                         WHERE project_id = :projectId
                           AND phase_id = :phaseId"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['projectId' => $projectId, 'phaseId' => $phaseId], $force);
    }
    // End findById


    /**
     * finds last modified or created Variant for a certain phase and project
     *
     * @param  int $projectId : id of the project
     * @param  int $phaseId   : id of the project phase
     */
    public static function findLastModifiedCreated($projectId, $phaseId, $force = false)
    {
        if (!$phaseId or !$projectId)
            return new ElcaProjectVariant();

        $sql = sprintf("SELECT v.id
                             , v.project_id
                             , v.phase_id
                             , v.name
                             , v.description
                             , v.created
                             , v.modified
                         FROM %s v
                         JOIN %s p
                           ON v.phase_id = p.id
                        WHERE v.project_id = :project_id
                          AND v.phase_id < :phase_id
                          ORDER BY step, coalesce(v.modified, v.created) DESC
                          LIMIT 1"
            , self::TABLE_NAME, ElcaProjectPhase::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['project_id' => $projectId, 'phase_id' => $phaseId], $force);
    }
    // End findLastModifiedCreated

    /**
     * Sets the property projectId
     *
     * @param  integer $projectId - project id
     *
     * @return
     */
    public function setProjectId($projectId)
    {
        if (!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;

        $this->projectId = (int)$projectId;
    }
    // End setProjectId


    /**
     * Sets the property phaseId
     *
     * @param  integer $phaseId - project phase id
     *
     * @return
     */
    public function setPhaseId($phaseId)
    {
        if (!$this->getValidator()->assertNotEmpty('phaseId', $phaseId))
            return;

        $this->phaseId = (int)$phaseId;
    }
    // End setPhaseId


    /**
     * Sets the property name
     *
     * @param  string $name - name
     *
     * @return
     */
    public function setName($name)
    {
        if (!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        if (!$this->getValidator()->assertMaxLength('name', 250, $name))
            return;

        $this->name = (string)$name;
    }
    // End setName


    /**
     * Sets the property description
     *
     * @param  string $description - description
     *
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
     * @param  boolean $force
     *
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject


    /**
     * Returns the property phaseId
     *
     * @return integer
     */
    public function getPhaseId()
    {
        return $this->phaseId;
    }
    // End getPhaseId


    /**
     * Returns the associated ElcaProjectPhase by property phaseId
     *
     * @param  boolean $force
     *
     * @return ElcaProjectPhase
     */
    public function getPhase($force = false)
    {
        return ElcaProjectPhase::findById($this->phaseId, $force);
    }
    // End getPhase


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
     * Returns the property modified
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }
    // End getModified


    /**
     * Returns the Project location
     *
     * @return ElcaProjectLocation
     */
    public function getProjectLocation()
    {
        return ElcaProjectLocation::findByProjectVariantId($this->id);
    }
    // End getProjectLocation


    /**
     * Returns the ProjectConstruction
     *
     * @return ElcaProjectConstruction
     */
    public function getProjectConstruction()
    {
        return ElcaProjectConstruction::findByProjectVariantId($this->id);
    }
    // End getProjectConstruction


    /**
     * Returns the ProjectEnEv
     *
     * @return ElcaProjectEnEv
     */
    public function getProjectEnEv()
    {
        return ElcaProjectEnEv::findByProjectVariantId($this->id);
    }
    // End getProjectEnEv

    /**
     * @param bool $force
     * @return ElcaProjectVariantAttributeSet
     */
    public function getAttributes($force = false)
    {
        return ElcaProjectVariantAttributeSet::find(['project_variant_id' => $this->getId()], null, null, null, $force);
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer $id    - projectVariantId
     * @param  boolean $force - Bypass caching
     *
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
        $this->modified = self::getCurrentTime();

        $sql = sprintf("UPDATE %s
                           SET project_id     = :projectId
                             , phase_id       = :phaseId
                             , name           = :name
                             , description    = :description
                             , created        = :created
                             , modified       = :modified
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return $this->updateBySql($sql,
            ['id'          => $this->id,
             'projectId'   => $this->projectId,
             'phaseId'     => $this->phaseId,
             'name'        => $this->name,
             'description' => $this->description,
             'created'     => $this->created,
             'modified'    => $this->modified]
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
     * @param  boolean $propertiesOnly
     *
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly)
            return self::$primaryKey;

        $primaryKey = [];

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
     * @param  mixed   $column
     *
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


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id = $this->getNextSequenceValue();
        $this->created = self::getCurrentTime();
        $this->modified = null;

        $sql = sprintf("INSERT INTO %s (id, project_id, phase_id, name, description, created, modified)
                               VALUES  (:id, :projectId, :phaseId, :name, :description, :created, :modified)"
            , self::TABLE_NAME
        );

        return $this->insertBySql($sql,
            ['id'          => $this->id,
             'projectId'   => $this->projectId,
             'phaseId'     => $this->phaseId,
             'name'        => $this->name,
             'description' => $this->description,
             'created'     => $this->created,
             'modified'    => $this->modified]
        );
    }
    // End insert


    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     *
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->id = (int)$DO->id;
        $this->projectId = (int)$DO->project_id;
        $this->phaseId = (int)$DO->phase_id;
        $this->name = $DO->name;
        $this->description = $DO->description;
        $this->created = $DO->created;
        $this->modified = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectVariant