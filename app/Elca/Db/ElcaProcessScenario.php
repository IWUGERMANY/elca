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
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      ElcaProcessScenario
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 *
 * $Id$
 */
class ElcaProcessScenario extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_scenarios';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * scenarioId
     */
    private $id;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * ident
     */
    private $ident;

    /**
     * groupIdent
     */
    private $groupIdent;

    /**
     * default scenario for the specified group
     */
    private $isDefault;

    /**
     * scenario description
     */
    private $description;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'              => PDO::PARAM_INT,
                                        'processConfigId' => PDO::PARAM_INT,
                                        'ident'           => PDO::PARAM_STR,
                                        'groupIdent'      => PDO::PARAM_STR,
                                        'isDefault'       => PDO::PARAM_BOOL,
                                        'description'     => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $processConfigId - processConfigId
     * @param  string   $ident          - ident
     * @param  string   $groupIdent     - groupIdent
     * @param  boolean  $isDefault      - default scenario for the specified group
     * @param  string   $description    - scenario description
     */
    public static function create($processConfigId, $ident, $groupIdent = null, $isDefault = false, $description = null)
    {
        $ElcaProcessScenario = new ElcaProcessScenario();
        $ElcaProcessScenario->setProcessConfigId($processConfigId);
        $ElcaProcessScenario->setIdent($ident);
        $ElcaProcessScenario->setGroupIdent($groupIdent);
        $ElcaProcessScenario->setIsDefault($isDefault);
        $ElcaProcessScenario->setDescription($description);

        if($ElcaProcessScenario->getValidator()->isValid())
            $ElcaProcessScenario->insert();

        return $ElcaProcessScenario;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessScenario' by its primary key
     *
     * @param  integer  $id    - scenarioId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessScenario
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessScenario();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , ident
                             , group_ident
                             , is_default
                             , description
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProcessScenario' by its unique key (processConfigId, ident)
     *
     * @param  integer  $processConfigId - processConfigId
     * @param  string   $ident          - ident
     * @param  boolean  $force          - Bypass caching
     * @return ElcaProcessScenario
     */
    public static function findByProcessConfigIdAndIdent($processConfigId, $ident, $force = false)
    {
        if(!$processConfigId || !$ident)
            return new ElcaProcessScenario();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , ident
                             , group_ident
                             , is_default
                             , description
                          FROM %s
                         WHERE process_config_id = :processConfigId
                           AND ident = :ident"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('processConfigId' => $processConfigId, 'ident' => $ident), $force);
    }
    // End findByProcessConfigIdAndIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processConfigId
     *
     * @param  integer  $processConfigId - processConfigId
     * @return
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;

        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string   $ident - ident
     * @return
     */
    public function setIdent($ident)
    {
        if(!$this->getValidator()->assertNotEmpty('ident', $ident))
            return;

        if(!$this->getValidator()->assertMaxLength('ident', 250, $ident))
            return;

        $this->ident = (string)$ident;
    }
    // End setIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property groupIdent
     *
     * @param  string   $groupIdent - groupIdent
     * @return
     */
    public function setGroupIdent($groupIdent = null)
    {
        if(!$this->getValidator()->assertMaxLength('groupIdent', 250, $groupIdent))
            return;

        $this->groupIdent = $groupIdent;
    }
    // End setGroupIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property isDefault
     *
     * @param  boolean  $isDefault - default scenario for the specified group
     * @return
     */
    public function setIsDefault($isDefault = false)
    {
        $this->isDefault = (bool)$isDefault;
    }
    // End setIsDefault

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property description
     *
     * @param  string   $description - scenario description
     * @return
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End setDescription

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
     * Returns the property processConfigId
     *
     * @return integer
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId

    //////////////////////////////////////////////////////////////////////////////////////

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
     * Returns the property groupIdent
     *
     * @return string
     */
    public function getGroupIdent()
    {
        return $this->groupIdent;
    }
    // End getGroupIdent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property isDefault
     *
     * @return boolean
     */
    public function isDefault()
    {
        return $this->isDefault;
    }
    // End isDefault

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - scenarioId
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
                           SET process_config_id = :processConfigId
                             , ident           = :ident
                             , group_ident     = :groupIdent
                             , is_default      = :isDefault
                             , description     = :description
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'          => $this->ident,
                                        'groupIdent'     => $this->groupIdent,
                                        'isDefault'      => $this->isDefault,
                                        'description'    => $this->description)
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
        $this->id              = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, process_config_id, ident, group_ident, is_default, description)
                               VALUES  (:id, :processConfigId, :ident, :groupIdent, :isDefault, :description)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'ident'          => $this->ident,
                                        'groupIdent'     => $this->groupIdent,
                                        'isDefault'      => $this->isDefault,
                                        'description'    => $this->description)
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
        $this->id              = (int)$DO->id;
        $this->processConfigId = (int)$DO->process_config_id;
        $this->ident           = $DO->ident;
        $this->groupIdent      = $DO->group_ident;
        $this->isDefault       = (bool)$DO->is_default;
        $this->description     = $DO->description;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessScenario