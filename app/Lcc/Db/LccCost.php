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
use Elca\Db\ElcaProject;
use PDO;

/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccCost
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * @translate db Lcc\Db\LccCostSet::find() label headline
 */
class LccCost extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.costs';

    /**
     * Groupings
     */
    const GROUPING_PROD = 'PROD';
    const GROUPING_WATER = 'WATER';
    const GROUPING_ENERGY = 'ENERGY';
    const GROUPING_CLEANING = 'CLEANING';
    const GROUPING_KGR = 'KGR';
    const GROUPING_KGU = 'KGU';

    /**
     * Idents
     */
    const IDENT_TAP_WATER = 'TAP_WATER';
    const IDENT_WASTE_WATER = 'WASTE_WATER';
    const IDENT_RAIN_WATER = 'RAIN_WATER';

    const IDENT_CREDIT_EEG = 'CREDIT_EEG';
    const IDENT_EEG = 'EEG';
    const IDENT_HEATING = 'HEATING';
    const IDENT_WATER = 'WATER';
    const IDENT_VENTILATION = 'VENTILATION';
    const IDENT_COOLING = 'COOLING';
    const IDENT_LIGHTING = 'LIGHTING';
    const IDENT_AUX = 'AUX';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * periodicCostId
     */
    private $id;

    /**
     * versionId
     */
    private $versionId;

    /**
     * groups within periodic costs
     */
    private $grouping;

    /**
     * din276 code
     */
    private $din276Code;

    /**
     * label
     */
    private $label;

    /**
     * optional headline
     */
    private $headline;

    /**
     * project specific config (not part of the default config)
     */
    private $projectId;

    /**
     * ident
     */
    private $ident;

    /**
     * ext: refValue
     */
    private $refValue;

    /**
     * ext: refUnit
     */
    private $refUnit;

    /**
     * ext: maintenance percentage
     */
    private $maintenancePerc;

    /**
     * ext: service percentage
     */
    private $servicePerc;

    /**
     * ext: life_time
     */
    private $lifeTime;

    /**
     * ext: quantity
     */
    private $quantity;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = ['id'             => PDO::PARAM_INT,
                                   'versionId'      => PDO::PARAM_INT,
                                   'grouping'       => PDO::PARAM_STR,
                                   'din276Code'     => PDO::PARAM_INT,
                                   'label'          => PDO::PARAM_STR,
                                   'headline'       => PDO::PARAM_STR,
                                   'ident'          => PDO::PARAM_STR,
                                   'projectId'      => PDO::PARAM_INT];

    /**
     * Extended column types
     */
    private static $extColumnTypes = ['refValue' => PDO::PARAM_STR,
                                      'refUnit' => PDO::PARAM_STR,
                                      'maintenancePerc' => PDO::PARAM_STR,
                                      'servicePerc' => PDO::PARAM_STR,
                                      'lifeTime' => PDO::PARAM_STR,
                                      'quantity'         => PDO::PARAM_STR
    ];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  string   $grouping  - groups within periodic costs
     * @param  integer  $din276Code - din276 code
     * @param  string   $label     - label
     * @param  integer  $versionId - versionId
     * @param  string   $headline  - optional headline
     * @param  integer  $projectId - project specific config (not part of the default config)
     */
    public static function create($grouping, $din276Code, $label, $versionId = null, $headline = null, $projectId = null, $ident = null)
    {
        $LccCost = new LccCost();
        $LccCost->setGrouping($grouping);
        $LccCost->setDin276Code($din276Code);
        $LccCost->setLabel($label);
        $LccCost->setVersionId($versionId);
        $LccCost->setHeadline($headline);
        $LccCost->setProjectId($projectId);
        $LccCost->setIdent($ident);

        if($LccCost->getValidator()->isValid())
            $LccCost->insert();

        return $LccCost;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccCost' by its primary key
     *
     * @param  integer  $id    - periodicCostId
     * @param  boolean  $force - Bypass caching
     * @return LccCost
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new LccCost();

        $sql = sprintf("SELECT id
                             , version_id
                             , grouping
                             , din276_code
                             , label
                             , headline
                             , project_id
                             , ident
                          FROM %s
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccCost' by its unique key (versionId, grouping, din276Code, label)
     *
     * @param  integer  $versionId - versionId
     * @param  string   $grouping  - groups within periodic costs
     * @param  integer  $din276Code - din276 code
     * @param  string   $label     - label
     * @param  boolean  $force     - Bypass caching
     * @return LccCost
     */
    public static function findByVersionIdAndGroupingAndDin276CodeAndLabel($versionId, $grouping, $din276Code, $label, $force = false)
    {
        if(!$versionId || !$grouping || !$din276Code || !$label)
            return new LccCost();

        $sql = sprintf("SELECT id
                             , version_id
                             , grouping
                             , din276_code
                             , label
                             , headline
                             , project_id
                             , ident
                          FROM %s
                         WHERE version_id = :versionId
                           AND grouping = :grouping
                           AND din276_code = :din276Code
                           AND label = :label"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['versionId' => $versionId, 'grouping' => $grouping, 'din276Code' => $din276Code, 'label' => $label], $force);
    }
    // End findByVersionIdAndGroupingAndDin276CodeAndLabel


    /**
     * Inits a `LccCost' by its unique key (versionId, grouping, din276Code, label)
     *
     * @param  integer $versionId - versionId
     * @param          $ident
     * @param  boolean $force     - Bypass caching
     * @return LccCost
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByVersionIdAndIdent($versionId, $ident, $force = false)
    {
        if(!$versionId || !$ident)
            return new LccCost();

        $sql = sprintf("SELECT id
                             , version_id
                             , grouping
                             , din276_code
                             , label
                             , headline
                             , project_id
                             , ident
                          FROM %s
                         WHERE version_id = :versionId
                           AND ident = :ident"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['versionId' => $versionId, 'ident' => $ident], $force);
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this with a new versionId
     *
     * @param  int $versionId
     * @return LccVersion
     */
    public function copy($versionId)
    {
        if(!$versionId || !$this->isInitialized())
            return new LccCost();

        $Copy = self::create($this->grouping,
                             $this->din276Code,
                             $this->label,
                             $versionId,
                             $this->headline,
                             null, // projectId
                             $this->ident
                             );

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property versionId
     *
     * @param  integer  $versionId - versionId
     * @return
     */
    public function setVersionId($versionId = null)
    {
        $this->versionId = $versionId;
    }
    // End setVersionId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property grouping
     *
     * @param  string   $grouping - groups within periodic costs
     * @return
     */
    public function setGrouping($grouping)
    {
        if(!$this->getValidator()->assertNotEmpty('grouping', $grouping))
            return;

        if(!$this->getValidator()->assertMaxLength('grouping', 100, $grouping))
            return;

        $this->grouping = (string)$grouping;
    }
    // End setGrouping

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property din276Code
     *
     * @param  integer  $din276Code - din276 code
     * @return
     */
    public function setDin276Code($din276Code)
    {
        if(!$this->getValidator()->assertNotEmpty('din276Code', $din276Code))
            return;

        $this->din276Code = (int)$din276Code;
    }
    // End setDin276Code

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property label
     *
     * @param  string   $label - label
     * @return
     */
    public function setLabel($label)
    {
        if(!$this->getValidator()->assertNotEmpty('label', $label))
            return;

        $this->label = (string)$label;
    }
    // End setLabel

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property headline
     *
     * @param  string   $headline - optional headline
     * @return
     */
    public function setHeadline($headline = null)
    {
        $this->headline = $headline;
    }
    // End setHeadline

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectId
     *
     * @param  integer  $projectId - project specific config (not part of the default config)
     * @return
     */
    public function setProjectId($projectId = null)
    {
        $this->projectId = $projectId;
    }
    // End setProjectId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property ident
     *
     * @param  string $ident
     * @return
     */
    public function setIdent($ident = null)
    {
        $this->ident = $ident;
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
    // End getId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property versionId
     *
     * @return integer
     */
    public function getVersionId()
    {
        return $this->versionId;
    }
    // End getVersionId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated LccVersion by property versionId
     *
     * @param  boolean  $force
     * @return LccVersion
     */
    public function getVersion($force = false)
    {
        return LccVersion::findById($this->versionId, $force);
    }
    // End getVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property grouping
     *
     * @return string
     */
    public function getGrouping()
    {
        return $this->grouping;
    }
    // End getGrouping

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property din276Code
     *
     * @return integer
     */
    public function getDin276Code()
    {
        return $this->din276Code;
    }
    // End getDin276Code

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
    // End getLabel

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property headline
     *
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }
    // End getHeadline

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  boolean  $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject

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
     * Returns the property refValue
     *
     * @return numeric
     */
    public function getRefValue()
    {
        return $this->refValue;
    }
    // End getRefValue

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property maintenancePerc
     *
     * @return numeric
     */
    public function getMaintenancePerc()
    {
        return $this->maintenancePerc;
    }
    // End getMaintenancePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property servicePerc
     *
     * @return numeric
     */
    public function getServicePerc()
    {
        return $this->servicePerc;
    }
    // End getServicePerc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property lifeTime
     *
     * @return numeric
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }
    // End getLifeTime

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property quantity
     *
     * @return numeric
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - periodicCostId
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
                           SET version_id     = :versionId
                             , grouping       = :grouping
                             , din276_code    = :din276Code
                             , label          = :label
                             , headline       = :headline
                             , ident          = :ident
                             , project_id     = :projectId
                         WHERE id = :id"
            , self::TABLE_NAME
        );

        return $this->updateBySql($sql,
            ['id'            => $this->id,
             'versionId'     => $this->versionId,
             'grouping'      => $this->grouping,
             'din276Code'    => $this->din276Code,
             'label'         => $this->label,
             'headline'      => $this->headline,
             'ident'         => $this->ident,
             'projectId'     => $this->projectId]
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

        $sql = sprintf("INSERT INTO %s (id, version_id, grouping, din276_code, label, headline, ident, project_id)
                               VALUES  (:id, :versionId, :grouping, :din276Code, :label, :headline, :ident, :projectId)"
            , self::TABLE_NAME
        );

        return $this->insertBySql($sql,
            ['id'            => $this->id,
             'versionId'     => $this->versionId,
             'grouping'      => $this->grouping,
             'din276Code'    => $this->din276Code,
             'label'         => $this->label,
             'headline'      => $this->headline,
             'ident'         => $this->ident,
             'projectId'     => $this->projectId]
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
        $this->versionId      = $DO->version_id;
        $this->grouping       = $DO->grouping;
        $this->din276Code     = (int)$DO->din276_code;
        $this->label          = $DO->label;
        $this->headline       = $DO->headline;
        $this->ident          = $DO->ident;
        $this->projectId      = $DO->project_id;

        /**
         * Set extensions
         */
        if(isset($DO->ref_value)) $this->refValue = $DO->ref_value;
        if(isset($DO->ref_unit)) $this->refUnit = $DO->ref_unit;
        if(isset($DO->maintenance_perc)) $this->maintenancePerc = $DO->maintenance_perc;
        if(isset($DO->service_perc)) $this->servicePerc = $DO->service_perc;
        if(isset($DO->life_time)) $this->lifeTime = $DO->life_time;
        if(isset($DO->quantity)) $this->quantity = $DO->quantity;
    }
    // End initByDataObject
}
// End class LccCost