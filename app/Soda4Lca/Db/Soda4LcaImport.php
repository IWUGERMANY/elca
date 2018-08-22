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

namespace Soda4Lca\Db;

use PDO;
use Beibob\Blibs\DbObject;
use Elca\Db\ElcaProcessDb;

/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      Soda4LcaImport
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2014 BEIBOB Medienfreunde
 *
 * $Id$
 */
class Soda4LcaImport extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'soda4lca.imports';

    /**
     * Status
     */
    const STATUS_INIT = 'INITIAL';
    const STATUS_IMPORT = 'IMPORT';
    const STATUS_DONE = 'DONE';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * importId
     */
    private $id;

    /**
     * processDbId
     */
    private $processDbId;

    /**
     * import status
     */
    private $status;

    /**
     * dateOfImport
     */
    private $dateOfImport;

    /**
     * dataStock
     */
    private $dataStock;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = [
        'id'             => PDO::PARAM_INT,
        'processDbId'    => PDO::PARAM_INT,
        'status'         => PDO::PARAM_STR,
        'dateOfImport'   => PDO::PARAM_STR,
        'dataStock'      => PDO::PARAM_STR
    ];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer $processDbId  - processDbId
     * @param  string  $status       - import status
     * @param  string  $dateOfImport - dateOfImport
     * @param null     $dataStock
     * @return Soda4LcaImport
     */
    public static function create($processDbId, $status = self::STATUS_INIT, $dateOfImport = null, $dataStock = null)
    {
        $Soda4LcaImport = new Soda4LcaImport();
        $Soda4LcaImport->setProcessDbId($processDbId);
        $Soda4LcaImport->setStatus($status);
        $Soda4LcaImport->setDateOfImport($dateOfImport);
        $Soda4LcaImport->setDataStock($dataStock);

        if($Soda4LcaImport->getValidator()->isValid())
            $Soda4LcaImport->insert();

        return $Soda4LcaImport;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `Soda4LcaImport' by its primary key
     *
     * @param  integer  $id    - importId
     * @param  boolean  $force - Bypass caching
     * @return Soda4LcaImport
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new Soda4LcaImport();

        $sql = sprintf("SELECT id
                             , process_db_id
                             , status
                             , date_of_import
                             , data_stock
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property processDbId
     *
     * @param  integer  $processDbId - processDbId
     * @return
     */
    public function setProcessDbId($processDbId)
    {
        if(!$this->getValidator()->assertNotEmpty('processDbId', $processDbId))
            return;

        $this->processDbId = (int)$processDbId;
    }
    // End setProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property status
     *
     * @param  string   $status - import status
     * @return
     */
    public function setStatus($status)
    {
        if(!$this->getValidator()->assertNotEmpty('status', $status))
            return;

        if(!$this->getValidator()->assertMaxLength('status', 20, $status))
            return;

        $this->status = (string)$status;
    }
    // End setStatus

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property dateOfImport
     *
     * @param  string   $dateOfImport - dateOfImport
     * @return
     */
    public function setDateOfImport($dateOfImport = null)
    {
        $this->dateOfImport = $dateOfImport;
    }
    // End setDateOfImport

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property dataStock
     *
     * @param  string   $dataStock
     * @return
     */
    public function setDataStock($dataStock = null)
    {
        $this->dataStock = $dataStock;
    }
    // End setDataStock

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
     * Returns the property processDbId
     *
     * @return integer
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End getProcessDbId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProcessDb by property processDbId
     *
     * @param  boolean  $force
     * @return ElcaProcessDb
     */
    public function getProcessDb($force = false)
    {
        return ElcaProcessDb::findById($this->processDbId, $force);
    }
    // End getProcessDb

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    // End getStatus

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property dateOfImport
     *
     * @return string
     */
    public function getDateOfImport()
    {
        return $this->dateOfImport;
    }
    // End getDateOfImport

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property dataStock
     *
     * @return  string   $dataStock
     */
    public function getDataStock()
    {
        return $this->dataStock;
    }
    // End getDataStock

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - importId
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
                           SET process_db_id  = :processDbId
                             , status         = :status
                             , date_of_import = :dateOfImport
                             , data_stock = :dataStock
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['id'            => $this->id,
                                        'processDbId'   => $this->processDbId,
                                        'status'        => $this->status,
                                        'dateOfImport'  => $this->dateOfImport,
                                        'dataStock'     => $this->dataStock]
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
                                  ['id' => $this->id]);
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
        $this->id             = $this->getNextSequenceValue();

        $sql = sprintf("INSERT INTO %s (id, process_db_id, status, date_of_import, data_stock)
                               VALUES  (:id, :processDbId, :status, :dateOfImport, :dataStock)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['id'            => $this->id,
                                        'processDbId'   => $this->processDbId,
                                        'status'        => $this->status,
                                        'dateOfImport'  => $this->dateOfImport,
                                        'dataStock'     => $this->dataStock]

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
        $this->processDbId    = (int)$DO->process_db_id;
        $this->status         = $DO->status;
        $this->dateOfImport   = $DO->date_of_import;
        $this->dataStock      = $DO->data_stock;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class Soda4LcaImport