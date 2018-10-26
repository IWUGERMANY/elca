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

namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use Exception;
use Lcc\LccModule;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccVersion
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccVersion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.versions';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * configId
     */
    private $id;

    /**
     * name
     */
    private $name;

    /**
     * version
     */
    private $version;

    /**
     * calc method
     */
    private $calcMethod;

    /**
     * Zinssatz
     */
    private $rate;

    /**
     * Allg. Preissteierung
     */
    private $commonPriceInc;

    /**
     * Energie Preissteierung
     */
    private $energyPriceInc;

    /**
     * Wasser/ Abwasser Preissteigerung
     */
    private $waterPriceInc;

    /**
     * Reinigung Preissteigerung
     */
    private $cleaningPriceInc;

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
    private static $columnTypes = ['id'               => PDO::PARAM_INT,
                                        'name'             => PDO::PARAM_STR,
                                        'version'          => PDO::PARAM_STR,
                                        'rate'             => PDO::PARAM_STR,
                                        'commonPriceInc'   => PDO::PARAM_STR,
                                        'energyPriceInc'   => PDO::PARAM_STR,
                                        'waterPriceInc'    => PDO::PARAM_STR,
                                        'cleaningPriceInc' => PDO::PARAM_STR,
                                        'created'          => PDO::PARAM_STR,
                                        'modified'         => PDO::PARAM_STR];

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
     * @param  string   $name            - name
     * @param  number  $rate            - Zinssatz
     * @param  number  $commonPriceInc  - Allg. Preissteierung
     * @param  number  $energyPriceInc  - Energie Preissteierung
     * @param  number  $waterPriceInc   - Wasser/ Abwasser Preissteigerung
     * @param  number  $cleaningPriceInc - Reinigung Preissteigerung
     * @param  string   $version         - version
     */
    public static function create($name, $rate, $commonPriceInc, $energyPriceInc, $waterPriceInc, $cleaningPriceInc, $calcMethod = LccModule::CALC_METHOD_GENERAL, $version = null)
    {
        $LccVersion = new LccVersion();
        $LccVersion->setName($name);
        $LccVersion->setRate($rate);
        $LccVersion->setCommonPriceInc($commonPriceInc);
        $LccVersion->setEnergyPriceInc($energyPriceInc);
        $LccVersion->setWaterPriceInc($waterPriceInc);
        $LccVersion->setCleaningPriceInc($cleaningPriceInc);
        $LccVersion->setCalcMethod($calcMethod);
        $LccVersion->setVersion($version);

        if($LccVersion->getValidator()->isValid())
            $LccVersion->insert();

        return $LccVersion;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccVersion' by its primary key
     *
     * @param  integer  $id    - configId
     * @param  boolean  $force - Bypass caching
     * @return LccVersion
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new LccVersion();

        $sql = sprintf("SELECT id
                             , name
                             , version
                             , calc_method
                             , rate
                             , common_price_inc
                             , energy_price_inc
                             , water_price_inc
                             , cleaning_price_inc
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the recent `LccVersion'
     *
     * @param  boolean  $force - Bypass caching
     * @return LccVersion
     */
    public static function findRecent($calcMethod, $force = false)
    {
        $sql = sprintf("SELECT id
                             , name
                             , version
                             , calc_method
                             , rate
                             , common_price_inc
                             , energy_price_inc
                             , water_price_inc
                             , cleaning_price_inc
                             , created
                             , modified
                          FROM %s
                         WHERE calc_method = :calcMethod
                         ORDER BY id DESC
                         LIMIT 1"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['calcMethod' => $calcMethod], $force);
    }
    // End findRecent

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this version as a new version
     *
     * @param  boolean $copyName
     * @return LccVersion
     */
    public function copy($copyName = false)
    {
        if(!$this->isInitialized())
            return new LccVersion();

        try
        {
            $this->Dbh->begin();

            $Copy = self::create($copyName? $this->name : t('Kopie von') . ' ' . $this->name,
                                 $this->rate,
                                 $this->commonPriceInc,
                                 $this->energyPriceInc,
                                 $this->waterPriceInc,
                                 $this->cleaningPriceInc,
                                 $this->calcMethod,
                                 $this->version
                                 );

            $versionId = $Copy->getId();

            foreach(LccRegularCostSet::findByVersionId($this->id) as $Cost)
                $Cost->copy($versionId);

            foreach(LccRegularServiceCostSet::findByVersionId($this->id) as $Cost)
                $Cost->copy($versionId);

            foreach(LccIrregularCostSet::findByVersionId($this->id) as $Cost)
                $Cost->copy($versionId);

            foreach (LccEnergySourceCostSet::findByVersionId($this->id) as $energySourceCost) {
                $energySourceCost->copy($versionId);
            }

            $this->Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Copy;
    }
    // End copy


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
     * Sets the property version
     *
     * @param  string   $version - version
     * @return
     */
    public function setVersion($version = null)
    {
        if(!$this->getValidator()->assertMaxLength('version', 100, $version))
            return;

        $this->version = $version;
    }

    /**
     * @param mixed $calcMethod
     */
    public function setCalcMethod($calcMethod)
    {
        $this->calcMethod = $calcMethod;
    }

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Sets the property rate
     *
     * @param  number  $rate  - Zinssatz
     * @return
     */
    public function setRate($rate)
    {
        if(!$this->getValidator()->assertNotEmpty('rate', $rate))
            return;

        $this->rate = $rate;
    }
    // End setRate

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property commonPriceInc
     *
     * @param  number  $commonPriceInc - Allg. Preissteierung
     * @return
     */
    public function setCommonPriceInc($commonPriceInc)
    {
        if(!$this->getValidator()->assertNotEmpty('commonPriceInc', $commonPriceInc))
            return;

        $this->commonPriceInc = $commonPriceInc;
    }
    // End setCommonPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property energyPriceInc
     *
     * @param  number  $energyPriceInc - Energie Preissteierung
     * @return
     */
    public function setEnergyPriceInc($energyPriceInc)
    {
        if(!$this->getValidator()->assertNotEmpty('energyPriceInc', $energyPriceInc))
            return;

        $this->energyPriceInc = $energyPriceInc;
    }
    // End setEnergyPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property waterPriceInc
     *
     * @param  number  $waterPriceInc - Wasser/ Abwasser Preissteigerung
     * @return
     */
    public function setWaterPriceInc($waterPriceInc)
    {
        if(!$this->getValidator()->assertNotEmpty('waterPriceInc', $waterPriceInc))
            return;

        $this->waterPriceInc = $waterPriceInc;
    }
    // End setWaterPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property cleaningPriceInc
     *
     * @param  number  $cleaningPriceInc - Reinigung Preissteigerung
     * @return
     */
    public function setCleaningPriceInc($cleaningPriceInc)
    {
        if(!$this->getValidator()->assertNotEmpty('cleaningPriceInc', $cleaningPriceInc))
            return;

        $this->cleaningPriceInc = $cleaningPriceInc;
    }
    // End setCleaningPriceInc

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
     * Returns the property version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return mixed
     */
    public function getCalcMethod()
    {
        return $this->calcMethod;
    }

    /**
     * Returns the property rate
     *
     * @return number
     */
    public function getRate()
    {
        return $this->rate;
    }
    // End getRate

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property commonPriceInc
     *
     * @return number
     */
    public function getCommonPriceInc()
    {
        return $this->commonPriceInc;
    }
    // End getCommonPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property energyPriceInc
     *
     * @return number
     */
    public function getEnergyPriceInc()
    {
        return $this->energyPriceInc;
    }
    // End getEnergyPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property waterPriceInc
     *
     * @return number
     */
    public function getWaterPriceInc()
    {
        return $this->waterPriceInc;
    }
    // End getWaterPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property cleaningPriceInc
     *
     * @return number
     */
    public function getCleaningPriceInc()
    {
        return $this->cleaningPriceInc;
    }
    // End getCleaningPriceInc

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - configId
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
        $this->modified = self::getCurrentTime();

        $sql = sprintf("UPDATE %s
                           SET name             = :name
                             , version          = :version
                             , calc_method      = :calcMethod
                             , rate             = :rate
                             , common_price_inc = :commonPriceInc
                             , energy_price_inc = :energyPriceInc
                             , water_price_inc  = :waterPriceInc
                             , cleaning_price_inc = :cleaningPriceInc
                             , created          = :created
                             , modified         = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['id'              => $this->id,
                                        'name'            => $this->name,
                                        'version'         => $this->version,
                                        'calcMethod'      => $this->calcMethod,
                                        'rate'            => $this->rate,
                                        'commonPriceInc'  => $this->commonPriceInc,
                                        'energyPriceInc'  => $this->energyPriceInc,
                                        'waterPriceInc'   => $this->waterPriceInc,
                                        'cleaningPriceInc' => $this->cleaningPriceInc,
                                        'created'         => $this->created,
                                        'modified'        => $this->modified]
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
        $this->id               = $this->getNextSequenceValue();
        $this->created          = self::getCurrentTime();
        $this->modified         = null;

        $sql = sprintf("INSERT INTO %s (id, name, version, calc_method, rate, common_price_inc, energy_price_inc, water_price_inc, cleaning_price_inc, created, modified)
                               VALUES  (:id, :name, :version, :calcMethod, :rate, :commonPriceInc, :energyPriceInc, :waterPriceInc, :cleaningPriceInc, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['id'              => $this->id,
                                        'name'            => $this->name,
                                        'version'         => $this->version,
                                        'calcMethod'      => $this->calcMethod,
                                        'rate'            => $this->rate,
                                        'commonPriceInc'  => $this->commonPriceInc,
                                        'energyPriceInc'  => $this->energyPriceInc,
                                        'waterPriceInc'   => $this->waterPriceInc,
                                        'cleaningPriceInc' => $this->cleaningPriceInc,
                                        'created'         => $this->created,
                                        'modified'        => $this->modified]
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
        $this->id               = (int)$DO->id;
        $this->name             = $DO->name;
        $this->version          = $DO->version;
        $this->calcMethod       = $DO->calc_method;
        $this->rate             = $DO->rate;
        $this->commonPriceInc   = $DO->common_price_inc;
        $this->energyPriceInc   = $DO->energy_price_inc;
        $this->waterPriceInc    = $DO->water_price_inc;
        $this->cleaningPriceInc = $DO->cleaning_price_inc;
        $this->created          = $DO->created;
        $this->modified         = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccVersion