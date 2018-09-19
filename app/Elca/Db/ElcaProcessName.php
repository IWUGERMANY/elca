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

class ElcaProcessName extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_names';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'processId' => PDO::PARAM_INT,
        'lang'      => PDO::PARAM_STR,
        'name'      => PDO::PARAM_STR,
        'created'   => PDO::PARAM_STR,
        'modified'  => PDO::PARAM_STR,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * @var int
     */
    private $processId;

    /**
     * @var string
     */
    private $lang;

    /**
     * @var string
     */
    private $name;

    private $created;

    private $modified;

    public static function create(int $processId, string $lang, string $name): ElcaProcessName
    {
        $elcaProcessName = new ElcaProcessName();
        $elcaProcessName->setProcessId($processId);
        $elcaProcessName->setLang($lang);
        $elcaProcessName->setName($name);

        if ($elcaProcessName->getValidator()->isValid()) {
            $elcaProcessName->insert();
        }

        return $elcaProcessName;
    }

    public static function findByProcessIdAndLang(int $processId, string $lang, bool $force = false): ElcaProcessName
    {
        if (!$processId) {
            return new ElcaProcessName();
        }

        $sql = sprintf(
            "SELECT process_id
                             , lang
                             , name
                             , created
                             , modified
                          FROM %s
                         WHERE (process_id, lang) = (:processId, :lang)"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['processId' => $processId, 'lang' => $lang], $force);
    }

    public static function exists(int $processId, string $lang, bool $force = false): bool
    {
        return self::findByProcessIdAndLang($processId, $lang, $force)->isInitialized();
    }

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

    public function getProcessId(): ?int
    {
        return $this->processId;
    }

    public function setProcessId(int $processId): void
    {
        if (!$this->getValidator()->assertNotEmpty('processId', $processId)) {
            return;
        }

        $this->processId = $processId;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        if (!$this->getValidator()->assertNotEmpty('lang', $lang)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('lang', 3, $lang)) {
            return;
        }

        $this->lang = $lang;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (!$this->getValidator()->assertMaxLength('name', 250, $name)) {
            return;
        }

        $this->name = $name;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    public function getModified(): ?string
    {
        return $this->modified;
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

    public function update()
    {
        $this->modified = self::getCurrentTime();

        $sql = sprintf(
            "UPDATE %s
                           SET name        = :name
                             , modified    = :modified
                         WHERE  (process_id, lang) = (:processId, :lang)"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            [
                'processId' => $this->processId,
                'lang'      => $this->lang,
                'name'      => $this->name,
                'modified'  => $this->modified,
            ]
        );
    }

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE  (process_id, lang) = (:processId, :lang)"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            [
                'processId' => $this->processId,
                'lang'      => $this->lang,
            ]
        );
    }

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

    protected function insert()
    {
        $this->created  = self::getCurrentTime();
        $this->modified = null;

        $sql = sprintf(
            "INSERT INTO %s (process_id, lang, name, created, modified)
                               VALUES  (:processId, :lang, :name, :created, :modified)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            [
                'processId' => $this->processId,
                'lang'      => $this->lang,
                'name'      => $this->name,
                'created'   => $this->created,
                'modified'  => $this->modified,
            ]
        );
    }

    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->processId = (int)$DO->process_id;
        $this->lang      = (string)$DO->lang;
        $this->name      = (string)$DO->name;
        $this->created   = (string)$DO->created;
        $this->modified  = $DO->modified;

        /**
         * Set extensions
         */
    }
}
