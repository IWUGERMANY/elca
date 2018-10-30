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
class ElcaProcessDb extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_dbs';


    /**
     * processDbId
     */
    private $id;

    /**
     * name
     */
    private $name;

    /**
     * version string
     */
    private $version;

    /**
     * uuid of the process db
     */
    private $uuid;

    /**
     * source uri
     */
    private $sourceUri;

    /**
     * flags the database as active
     */
    private $isActive;

    /**
     * is EN 15804 comliant
     */
    private $isEn15804Compliant;

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
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'name'               => PDO::PARAM_STR,
                                        'version'            => PDO::PARAM_STR,
                                        'uuid'               => PDO::PARAM_STR,
                                        'sourceUri'          => PDO::PARAM_STR,
                                        'isActive'           => PDO::PARAM_BOOL,
                                        'isEn15804Compliant' => PDO::PARAM_BOOL,
                                        'created'            => PDO::PARAM_STR,
                                        'modified'           => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    /**
     * Creates the object
     *
     * @param  string $name - name
     * @param  string $version - version string
     * @param  string $uuid
     * @param null $sourceUri
     * @param bool $isActive
     * @param bool $isEn15804Compliant
     * @return \ElcaProcessDb
     */
    public static function create($name, $version = null, $uuid = null, $sourceUri = null, $isActive = false, $isEn15804Compliant = true)
    {
        $ElcaProcessDb = new ElcaProcessDb();
        $ElcaProcessDb->setName($name);
        $ElcaProcessDb->setVersion($version);
        $ElcaProcessDb->setSourceUri($sourceUri);
        $ElcaProcessDb->setIsActive($isActive);
        $ElcaProcessDb->setIsEn15804Compliant($isEn15804Compliant);

        if ($uuid)
            $ElcaProcessDb->setUuid($uuid);

        if ($ElcaProcessDb->getValidator()->isValid())
            $ElcaProcessDb->insert();

        return $ElcaProcessDb;
    }
    // End create


    /**
     * Inits a `ElcaProcessDb' by its primary key
     *
     * @param  integer $id - processDbId
     * @param  boolean $force - Bypass caching
     * @return ElcaProcessDb
     */
    public static function findById($id, $force = false)
    {
        if (!$id)
            return new ElcaProcessDb();

        $sql = sprintf("SELECT id
                             , name
                             , version
                             , uuid
                             , source_uri
                             , is_active
                             , is_en15804_compliant
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Finds entry by its name
     *
     * @param  string $name - name
     * @param  boolean $force - Bypass caching
     * @return ElcaProcessDb
     */
    public static function findByName($name, $force = false)
    {
        if (!$name)
            return new ElcaProcessDb();

        $sql = sprintf("SELECT id
                             , name
                             , version
                             , uuid
                             , source_uri
                             , is_active
                             , is_en15804_compliant
                             , created
                             , modified
                          FROM %s
                         WHERE name = :name"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('name' => $name), $force);
    }
    // End findById


    /**
     * Inits the newest `ElcaProcessDb'
     *
     * @param  boolean $force - Bypass caching
     * @return ElcaProcessDb
     */
    public static function findMostRecentVersion(bool $filterByActiveFlag = false, $force = false)
    {
        $sql = sprintf("SELECT id
                             , name
                             , version
                             , uuid
                             , source_uri
                             , is_active
                             , is_en15804_compliant
                             , created
                             , modified
                          FROM %s
                          %s
                         ORDER BY id DESC
                         LIMIT 1"
            , self::TABLE_NAME
            , $filterByActiveFlag ? 'WHERE is_active' : ''
        );

        return self::findBySql(get_class(), $sql, array(), $force);
    }
    // End findMostRecentVersion

    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessDb' by its unique key (uuid)
     *
     * @param  string $uuid - uuid of the process
     * @param  boolean $force - Bypass caching
     * @return ElcaProcessDb
     */
    public static function findByUuid($uuid, $force = false)
    {
        if (!$uuid)
            return new ElcaProcessDb();

        $sql = sprintf("SELECT id
                             , name
                             , version
                             , uuid
                             , source_uri
                             , is_active
                             , is_en15804_compliant
                             , created
                             , modified
                          FROM %s
                         WHERE uuid = :uuid"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('uuid' => $uuid), $force);
    }
    // End findByUuid


    /**
     * Returns a new unique name
     *
     * @param  ElcaProcessDb
     * @return string
     */
    public static function findNewUniqueName(ElcaProcessDb $ProcessDb)
    {
        $initValues = array('name' => '% ' . t('Kopie von') . ' ' . $ProcessDb->getName());

        $sql = sprintf('SELECT name
                          FROM %s
                         WHERE name ILIKE :name
                      ORDER BY name DESC
                         LIMIT 1'
            , self::TABLE_NAME
        );

        $results = array();
        parent::executeSql(get_class(), $sql, $initValues, $results);

        $name = '';
        if (count($results)) {
            $DO = array_shift($results);
            if (preg_match('/^(\d+)\. ' . t('Kopie von') . ' /u', $DO->name, $matches)) {
                $counter = (int)$matches[1];
                $name = ++$counter . '. ' . t('Kopie von') . ' ' . $ProcessDb->getName();
            }
        }

        if (!$name)
            $name = '1. ' . t('Kopie von') . ' ' . $ProcessDb->getName();

        return $name;
    }
    // End findNewUniqueName


    /**
     * Creates a deep copy if the current database
     *
     * @param  string $name
     * @param  string $version
     * @param  string $uuid
     * @throws Exception
     * @return ElcaProcessDb
     */
    public function copy($name = null, $version = null, $uuid = null)
    {
        if (!$this->isInitialized())
            return new ElcaProcessDb();

        try {
            $this->Dbh->begin();

            /**
             * Create copy
             */
            $Copy = self::create($name ? $name : self::findNewUniqueName($this),
                                 $version ? $version : $this->version,
                                 $uuid,
                                 $this->sourceUri,
                                 false, // is active
                                 $this->isEn15804Compliant
                                );

            /**
             * Copy processes
             */
            $ProcessSet = ElcaProcessSet::find(array('process_db_id' => $this->getId()));
            $newProcessDbId = $Copy->getId();
            foreach ($ProcessSet as $Process)
                $Process->copy($newProcessDbId);

            $this->Dbh->commit();
        } catch (Exception $Exception) {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End copy


    /**
     * Sets the property name
     *
     * @param  string $name - name
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
    // End setName


    /**
     * Sets the property version
     *
     * @param  string $version - version string
     * @return void
     */
    public function setVersion($version = null)
    {
        if (!$this->getValidator()->assertMaxLength('version', 50, $version))
            return;

        $this->version = $version;
    }
    // End setVersion


    /**
     * Sets the property uuid
     *
     * @param  string $uuid - uuid of the process
     * @return void
     */
    public function setUuid($uuid)
    {
        if (!$this->getValidator()->assertNotEmpty('uuid', $uuid))
            return;

        $this->uuid = (string)$uuid;
    }
    // End setUuid


    /**
     * Sets the property sourceUri
     *
     * @param  string $sourceUri - source uri
     * @return void
     */
    public function setSourceUri($sourceUri = null)
    {
        if (!$this->getValidator()->assertMaxLength('sourceUri', 250, $sourceUri))
            return;

        $this->sourceUri = $sourceUri;
    }
    // End setSourceUri


    /**
     * Sets the property isActive
     *
     * @param  boolean $isActive - flags the database as active
     * @return void
     */
    public function setIsActive($isActive = false)
    {
        $this->isActive = (bool)$isActive;
    }
    // End setIsActive


    /**
     * Sets the property isEn15804Compliant
     *
     * @param  boolean $isEn15804Compliant
     * @return void
     */
    public function setIsEn15804Compliant($isEn15804Compliant = false)
    {
        $this->isEn15804Compliant = (bool)$isEn15804Compliant;
    }
    // End setIsEn15804Compliant


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
    // End getName


    /**
     * Returns the property version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
    // End getVersion


    /**
     * Returns the property uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
    // End getUuid


    /**
     * @return bool
     */
    public function hasSourceUri()
    {
        return (bool)$this->sourceUri;
    }

    /**
     * Returns the property sourceUri
     *
     * @return string
     */
    public function getSourceUri()
    {
        return $this->sourceUri;
    }
    // End getSourceUri


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
     * Returns the property isEn15804Compliant
     *
     * @return  boolean
     */
    public function isEn15804Compliant()
    {
        return $this->isEn15804Compliant;
    }
    // End isEn15804Compliant


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
     * Checks, if the object exists
     *
     * @param  integer $id - processDbId
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
                             , version        = :version
                             , uuid           = :uuid
                             , source_uri     = :sourceUri
                             , is_active      = :isActive
                             , is_en15804_compliant = :isEn15804Compliant
                             , created        = :created
                             , modified       = :modified
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return $this->updateBySql($sql,
            array('id'        => $this->id,
                  'name'      => $this->name,
                  'version'   => $this->version,
                  'uuid'      => $this->uuid,
                  'sourceUri' => $this->sourceUri,
                  'isActive'  => $this->isActive,
                  'isEn15804Compliant' => $this->isEn15804Compliant,
                  'created'   => $this->created,
                  'modified'  => $this->modified)
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
    // protected


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

        $sql = sprintf("INSERT INTO %s (id, name, version, uuid, source_uri, is_active, is_en15804_compliant, created, modified)"
            , self::TABLE_NAME);

        $initValues = array('id'        => $this->id,
                            'name'      => $this->name,
                            'version'   => $this->version,
                            'sourceUri' => $this->sourceUri,
                            'isActive'  => $this->isActive,
                            'isEn15804Compliant' => $this->isEn15804Compliant,
                            'created'   => $this->created,
                            'modified'  => $this->modified);

        $returnValues = array();

        if ($this->uuid) {
            $sql .= ' VALUES  (:id, :name, :version, :uuid, :sourceUri, :isActive, :isEn15804Compliant, :created, :modified)';
            $initValues['uuid'] = $this->uuid;
        } else {
            $sql .= ' VALUES  (:id, :name, :version, DEFAULT, :sourceUri, :isActive, :isEn15804Compliant, :created, :modified) RETURNING uuid';
        }

        $result = $this->insertBySql($sql,
            $initValues,
            $returnValues
        );

        if (count($returnValues) && is_object($returnValues[0]) && isset($returnValues[0]->uuid))
            $this->uuid = $returnValues[0]->uuid;

        return $result;
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
        $this->version = $DO->version;
        $this->uuid = $DO->uuid;
        $this->sourceUri = $DO->source_uri;
        $this->isActive = (bool)$DO->is_active;
        $this->isEn15804Compliant = (bool)$DO->is_en15804_compliant;
        $this->created = $DO->created;
        $this->modified = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessDb