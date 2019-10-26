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
use Elca\Elca;
use PDO;

/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfig extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_configs';

    const UNKNOWN_UUID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
    const UNKNOWN_NAME = 'Unbekannt';

    /**
     * processConfigId
     */
    private $id;

    /**
     * name
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * processCategoryNodeId
     */
    private $processCategoryNodeId;

    /**
     * avg life time in years
     */
    private $avgLifeTime;

    /**
     * min life time in years
     */
    private $minLifeTime;

    /**
     * max life time in years
     */
    private $maxLifeTime;

    /**
     * life time info
     */
    private $lifeTimeInfo;

    /**
     * avg life time info
     */
    private $avgLifeTimeInfo;

    /**
     * min life time info
     */
    private $minLifeTimeInfo;

    /**
     * max life time info
     */
    private $maxLifeTimeInfo;

    /**
     * density
     */
    private $density;

    /**
     * thermal conductivity
     */
    private $thermalConductivity;

    /**
     * thermal resistance
     */
    private $thermalResistance;

    /**
     * is reference process config
     */
    private $isReference;

    /**
     * factor hs/hi
     */
    private $fHsHi;

    /**
     * @var float
     */
    private $defaultSize;

    /**
     * uuid
     */
    private $uuid;

    /**
     * svgPatternId
     */
    private $svgPatternId;

    /**
     * @var boolean
     */
    private $isStale;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;


    /**
     * Conversion matrix
     */
    private $conversionMatrix;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                    => PDO::PARAM_INT,
                                        'name'                  => PDO::PARAM_STR,
                                        'description'           => PDO::PARAM_STR,
                                        'processCategoryNodeId' => PDO::PARAM_INT,
                                        'avgLifeTime'           => PDO::PARAM_INT,
                                        'minLifeTime'           => PDO::PARAM_INT,
                                        'maxLifeTime'           => PDO::PARAM_INT,
                                        'lifeTimeInfo'          => PDO::PARAM_STR,
                                        'avgLifeTimeInfo'       => PDO::PARAM_STR,
                                        'minLifeTimeInfo'       => PDO::PARAM_STR,
                                        'maxLifeTimeInfo'       => PDO::PARAM_STR,
                                        'density'               => PDO::PARAM_STR,
                                        'thermalConductivity'   => PDO::PARAM_STR,
                                        'thermalResistance'     => PDO::PARAM_STR,
                                        'isReference'           => PDO::PARAM_BOOL,
                                        'fHsHi'                 => PDO::PARAM_STR,
                                        'defaultSize'           => PDO::PARAM_STR,
                                        'uuid'                  => PDO::PARAM_STR,
                                        'svgPatternId'          => PDO::PARAM_INT,
                                        'isStale'               => PDO::PARAM_BOOL,
                                        'created'               => PDO::PARAM_STR,
                                        'modified'              => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();


    // public


    /**
     * Creates the object
     *
     * @param  string  $name                  - name
     * @param  integer $processCategoryNodeId - processCategoryNodeId
     * @param null     $description
     * @param  number  $density               - density
     * @param  number  $thermalConductivity   - thermal conductivity
     * @param  number  $thermalResistance     - thermal resistance
     * @param  boolean $isReference           - is reference process
     * @param  number  $fHsHi                 - factor hs/hi
     * @param null     $minLifeTime
     * @param  integer $avgLifeTime           - avg life time in years
     * @param null     $maxLifeTime
     * @param null     $lifeTimeInfo
     * @param null     $avgLifeTimeInfo
     * @param null     $minLifeTimeInfo
     * @param null     $maxLifeTimeInfo
     * @param  string  $uuid
     * @param null     $svgPatternId
     * @param bool     $isStale
     * @return ElcaProcessConfig
     */
    public static function create($name, $processCategoryNodeId, $description = null, $density = null, $thermalConductivity = null, $thermalResistance = null, $isReference = true, $fHsHi = null, $minLifeTime = null, $avgLifeTime = null, $maxLifeTime = null, $lifeTimeInfo = null, $avgLifeTimeInfo = null, $minLifeTimeInfo = null, $maxLifeTimeInfo = null, $uuid = null, $svgPatternId = null, $isStale = false, $defaultSize = null)
    {
        $processConfig = new ElcaProcessConfig();
        $processConfig->setName($name);
        $processConfig->setProcessCategoryNodeId($processCategoryNodeId);
        $processConfig->setAvgLifeTime($avgLifeTime);
        $processConfig->setDescription($description);
        $processConfig->setDensity($density);
        $processConfig->setThermalConductivity($thermalConductivity);
        $processConfig->setThermalResistance($thermalResistance);
        $processConfig->setIsReference($isReference);
        $processConfig->setFHsHi($fHsHi);
        $processConfig->setMinLifeTime($minLifeTime);
        $processConfig->setMaxLifeTime($maxLifeTime);
        $processConfig->setLifeTimeInfo($lifeTimeInfo);
        $processConfig->setAvgLifeTimeInfo($avgLifeTimeInfo);
        $processConfig->setMinLifeTimeInfo($minLifeTimeInfo);
        $processConfig->setMaxLifeTimeInfo($maxLifeTimeInfo);
        $processConfig->setSvgPatternId($svgPatternId);
        $processConfig->setIsStale($isStale);
        $processConfig->setDefaultSize($defaultSize);

        if($uuid)
            $processConfig->setUuid($uuid);

        if($processConfig->getValidator()->isValid())
            $processConfig->insert();

        return $processConfig;
    }
    // End create



    /**
     * Inits a `ElcaProcessConfig' by its primary key
     *
     * @param  integer  $id    - processConfigId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT id
                             , name
                             , process_category_node_id
                             , description
                             , avg_life_time
                             , min_life_time
                             , max_life_time
                             , life_time_info
                             , avg_life_time_info
                             , min_life_time_info
                             , max_life_time_info
                             , density
                             , thermal_conductivity
                             , thermal_resistance
                             , is_reference
                             , f_hs_hi
                             , default_size
                             , uuid
                             , svg_pattern_id
                             , is_stale
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
     * Inits a `ElcaProcessConfig' by its uuid
     *
     * @param  integer  $uuid
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findByUuid($uuid, $force = false)
    {
        if(!$uuid)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT id
                             , name
                             , process_category_node_id
                             , description
                             , avg_life_time
                             , min_life_time
                             , max_life_time
                             , life_time_info
                             , avg_life_time_info
                             , min_life_time_info
                             , max_life_time_info
                             , density
                             , thermal_conductivity
                             , thermal_resistance
                             , is_reference
                             , f_hs_hi
                             , default_size
                             , uuid
                             , svg_pattern_id
                             , is_stale
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
     * Inits a `ElcaProcessConfig' by its uuid
     *
     * @return ElcaProcessConfig
     */
    public static function findUnknown($force = false)
    {
        $unknownProcessConfig = self::findByUuid(self::UNKNOWN_UUID, $force);

        if ($unknownProcessConfig->isInitialized()) {
            return $unknownProcessConfig;
        }

        return self::createUnknown();
    }

    /**
     * Inits a `ElcaProcessConfig' by ProcessId. joins over process_life_cycle_assignments
     *
     * @param  integer  $id    - processConfigId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findByProcessId($processId, $force = false)
    {
        if(!$processId )
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size                             
                             , pc.uuid
                             , pc.svg_pattern_id
                             , is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.process_id = :id"
                       , self::TABLE_NAME
                       , ElcaProcessLifeCycleAssignment::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $processId), $force);
    }
    // End findByProcessId



    /**
     * Inits a `ElcaProcessConfig' by process uuid
     *
     * @deprecated  This query may return multiple matches - use method on ElcaProcessConfigSet instead
     *
     * @param  string  $processUuid
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findByProcessUuid($processUuid, $force = false)
    {
        if(!$processUuid )
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.uuid = :processUuid"
                       , self::TABLE_NAME
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       );

        return self::findBySql(get_class(), $sql, array('processUuid' => $processUuid), $force);
    }
    // End findByProcessUuid



    /**
     * Inits a `ElcaProcessConfig' by the process orig name
     *
     * @param  string  $name
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findByProcessNameOrig($processNameOrig, $force = false)
    {
        if(!$processNameOrig)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.name_orig = :processNameOrig"
                       , self::TABLE_NAME
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       );

        return self::findBySql(get_class(), $sql, array('processNameOrig' => $processNameOrig), $force);
    }
    // End findByProcessName



    /**
     * Inits a `ElcaProcessConfig' by the process name
     *
     * @deprecated  This query may return multiple matches - use method on ElcaProcessConfigSet instead
     *
     * @param  string  $name
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessConfig
     */
    public static function findByProcessName($processName, $force = false)
    {
        if(!$processName)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.name = :processName
                         LIMIT 1"
                       , self::TABLE_NAME
                       , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
                       );

        return self::findBySql(get_class(), $sql, array('processName' => $processName), $force);
    }


    public static function findByProcessNameAndProcessDbId($processName, $processDbId, $force = false)
    {
        if(!$processName || !$processDbId)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.process_db_id = :processDbId 
                           AND plca.name = :processName
                         LIMIT 1"
            , self::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
        );

        return self::findBySql(get_class(), $sql, array('processName' => $processName, 'processDbId' => $processDbId), $force);
    }

    public static function findCaseInsensitiveByProcessNameAndProcessDbId($processName, $processDbId, $force = false)
    {
        if(!$processName || !$processDbId)
            return new ElcaProcessConfig();

        $sql = sprintf("SELECT pc.id
                             , pc.name
                             , pc.process_category_node_id
                             , pc.description
                             , pc.avg_life_time
                             , pc.min_life_time
                             , pc.max_life_time
                             , pc.life_time_info
                             , pc.avg_life_time_info
                             , pc.min_life_time_info
                             , pc.max_life_time_info
                             , pc.density
                             , pc.thermal_conductivity
                             , pc.thermal_resistance
                             , pc.is_reference
                             , pc.f_hs_hi
                             , pc.default_size
                             , pc.uuid
                             , pc.svg_pattern_id
                             , pc.is_stale
                             , pc.created
                             , pc.modified
                          FROM %s pc
                          JOIN %s plca ON plca.process_config_id = pc.id
                         WHERE plca.process_db_id = :processDbId 
                           AND lower(plca.name) = lower(:processName)
                         LIMIT 1"
            , self::TABLE_NAME
            , ElcaProcessSet::VIEW_ELCA_PROCESS_ASSIGNMENTS
        );

        return self::findBySql(get_class(), $sql, array('processName' => $processName, 'processDbId' => $processDbId), $force);
    }


    /**
     *
     */
    public function copy($newName = null)
    {
        try {
            $this->Dbh->begin();
            $copy = self::create(
                $newName ?? $this->name . ' (Kopie)',
                $this->processCategoryNodeId,
                $this->description,
                $this->density,
                $this->thermalConductivity,
                $this->thermalResistance,
                false,
                $this->fHsHi,
                $this->minLifeTime,
                $this->avgLifeTime,
                $this->maxLifeTime,
                $this->lifeTimeInfo,
                $this->avgLifeTimeInfo,
                $this->minLifeTimeInfo,
                $this->maxLifeTimeInfo,
                null,
                $this->svgPatternId,
                $this->isStale,
                $this->defaultSize
            );

            foreach (ElcaProcessLifeCycleAssignmentSet::find(['process_config_id' => $this->getId()]) as $assignment) {
                ElcaProcessLifeCycleAssignment::create(
                    $copy->getId(),
                    $assignment->getProcessId(),
                    $assignment->getRatio()
                );
            }

            foreach (ElcaProcessConversionSet::findByProcessConfigId($this->getId()) as $conversion) {
                $conversion->copy($copy->getId());
            }

            $this->Dbh->commit();
        }
        catch (\Exception $exception) {
            $this->Dbh->rollback();
            throw $exception;
        }
    }


    /**
     * @return bool
     */
    public function isUnknown()
    {
        return self::UNKNOWN_UUID === $this->uuid;
    }


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

        if(!$this->getValidator()->assertMaxLength('name', 250, $name))
            return;

        $this->name = (string)$name;
    }
    // End setName



    /**
     * Sets the property processCategoryNodeId
     *
     * @param  integer $processCategoryNodeId - processCategoryNodeId
     * @return void
     */
    public function setProcessCategoryNodeId($processCategoryNodeId)
    {
        if(!$this->getValidator()->assertNotEmpty('processCategoryNodeId', $processCategoryNodeId))
            return;

        $this->processCategoryNodeId = (int)$processCategoryNodeId;
    }
    // End setProcessCategoryNodeId


    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }



    /**
     * Sets the property avgLifeTime
     *
     * @param  integer  $avgLifeTime - avg life time in years
     * @return
     */
    public function setAvgLifeTime($avgLifeTime = null)
    {
        $this->avgLifeTime = $avgLifeTime;
    }
    // End setAvgLifeTime



    /**
     * Sets the property minLifeTime
     *
     * @param  integer  $minLifeTime - min life time in years
     * @return
     */
    public function setMinLifeTime($minLifeTime = null)
    {
        $this->minLifeTime = $minLifeTime;
    }
    // End setMinLifeTime



    /**
     * Sets the property maxLifeTime
     *
     * @param  integer  $maxLifeTime - max life time in years
     * @return
     */
    public function setMaxLifeTime($maxLifeTime = null)
    {
        $this->maxLifeTime = $maxLifeTime;
    }
    // End setMaxLifeTime



    /**
     * Sets the property lifeTimeInfo
     *
     * @param  string   $lifeTimeInfo - life time info
     * @return
     */
    public function setLifeTimeInfo($lifeTimeInfo = null)
    {
        $this->lifeTimeInfo = $lifeTimeInfo;
    }
    // End setLifeTimeInfo



    /**
     * Sets the property avgLifeTimeInfo
     *
     * @param  string   $avgLifeTimeInfo - avg life time info
     * @return
     */
    public function setAvgLifeTimeInfo($avgLifeTimeInfo = null)
    {
        $this->avgLifeTimeInfo = $avgLifeTimeInfo;
    }
    // End setAvgLifeTimeInfo



    /**
     * Sets the property minLifeTimeInfo
     *
     * @param  string   $minLifeTimeInfo - min life time info
     * @return
     */
    public function setMinLifeTimeInfo($minLifeTimeInfo = null)
    {
        $this->minLifeTimeInfo = $minLifeTimeInfo;
    }
    // End setMinLifeTimeInfo



    /**
     * Sets the property maxLifeTimeInfo
     *
     * @param  string   $maxLifeTimeInfo - max life time info
     * @return
     */
    public function setMaxLifeTimeInfo($maxLifeTimeInfo = null)
    {
        $this->maxLifeTimeInfo = $maxLifeTimeInfo;
    }
    // End setMaxLifeTimeInfo



    /**
     * Sets the property density
     *
     * @param  number  $density - density
     * @return
     * @deprecated
     */
    public function setDensity(float $density = null)
    {
        throw new \InvalidArgumentException("Setting the density in this context is not supported anymore");
    }
    // End setDensity

    /**
     * Sets the property thermalConductivity
     *
     * @param  number  $thermalConductivity - thermal conductivity
     * @return
     */
    public function setThermalConductivity($thermalConductivity = null)
    {
        $this->thermalConductivity = $thermalConductivity;
    }
    // End setThermalConductivity



    /**
     * Sets the property thermalResistance
     *
     * @param  number  $thermalResistance - thermal resistance
     * @return
     */
    public function setThermalResistance($thermalResistance = null)
    {
        $this->thermalResistance = $thermalResistance;
    }
    // End setThermalResistance



    /**
     * Sets the property isReference
     *
     * @param  boolean  $isReference - is reference process
     * @return
     */
    public function setIsReference($isReference = true)
    {
        $this->isReference = (bool)$isReference;
    }
    // End setIsReference



    /**
     * Sets the property fHsHi
     *
     * @param  boolean  $fHsHi
     * @return
     */
    public function setFHsHi(float $fHsHi = null)
    {
        $this->fHsHi = $fHsHi;
    }

    /**
     * @param float $defaultSize
     */
    public function setDefaultSize(float $defaultSize = null)
    {
        $this->defaultSize = $defaultSize;
    }

    /**
     * Sets the property uuid
     *
     * @param  string   $uuid  - uuid
     * @return
     */
    public function setUuid($uuid)
    {
        if(!$this->getValidator()->assertNotEmpty('uuid', $uuid))
            return;

        $this->uuid = (string)$uuid;
    }
    // End setUuid



    /**
     * Sets the property svgPatternId
     *
     * @param  integer $svgPatternId
     * @return void
     */
    public function setSvgPatternId($svgPatternId = null)
    {
        $this->svgPatternId = $svgPatternId;
    }

    /**
     * @param boolean $isStale
     */
    public function setIsStale($isStale)
    {
        $this->isStale = $isStale;
    }
    // End setSvgPatternId


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
     * Returns the property processCategoryNodeId
     *
     * @return integer
     */
    public function getProcessCategoryNodeId()
    {
        return $this->processCategoryNodeId;
    }
    // End getProcessCategoryNodeId



    /**
     * Returns the associated ElcaProcessCategory by property processCategoryNodeId
     *
     * @param  boolean  $force
     * @return ElcaProcessCategory
     */
    public function getProcessCategory($force = false)
    {
        return ElcaProcessCategory::findByNodeId($this->processCategoryNodeId, $force);
    }
    // End getProcessCategory

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the property avgLifeTime
     *
     * @return integer
     */
    public function getAvgLifeTime()
    {
        return $this->avgLifeTime;
    }
    // End getAvgLifeTime



    /**
     * Returns the property minLifeTime
     *
     * @return integer
     */
    public function getMinLifeTime()
    {
        return $this->minLifeTime;
    }
    // End getMinLifeTime



    /**
     * Returns the property maxLifeTime
     *
     * @return integer
     */
    public function getMaxLifeTime()
    {
        return $this->maxLifeTime;
    }
    // End getMaxLifeTime



    /**
     * Returns the default (minimum) lifeTime
     *
     * @return integer
     */
    public function getDefaultLifeTime()
    {
        $values = array();
        if(!is_null($this->minLifeTime))
            $values[] = $this->minLifeTime;
        if(!is_null($this->avgLifeTime))
            $values[] = $this->avgLifeTime;
        if(!is_null($this->maxLifeTime))
            $values[] = $this->maxLifeTime;

        return $values? min($values) : null;
    }
    // End getDefaultLifeTime



    /**
     * Returns the property lifeTimeInfo
     *
     * @return string
     */
    public function getLifeTimeInfo()
    {
        return $this->lifeTimeInfo;
    }
    // End getLifeTimeInfo



    /**
     * Returns the property avgLifeTimeInfo
     *
     * @return string
     */
    public function getAvgLifeTimeInfo()
    {
        return $this->avgLifeTimeInfo;
    }
    // End getAvgLifeTimeInfo



    /**
     * Returns the property minLifeTimeInfo
     *
     * @return string
     */
    public function getMinLifeTimeInfo()
    {
        return $this->minLifeTimeInfo;
    }
    // End getMinLifeTimeInfo



    /**
     * Returns the property maxLifeTimeInfo
     *
     * @return string
     */
    public function getMaxLifeTimeInfo()
    {
        return $this->maxLifeTimeInfo;
    }
    // End getMaxLifeTimeInfo


    /**
     * @return array
     */
    public function getLifeTimes()
    {
        $lifeTimes = [];
        foreach(['min', 'avg', 'max'] as $type)
        {
            $property = $type.'LifeTime';
            $infoProperty = $property.'Info';

            if(!$value = $this->$property)
                continue;

            $info = $this->$infoProperty;

            $lifeTimes[$value] = $info;
        }

        return $lifeTimes;
    }


    /**
     * Returns the property density
     *
     * @return float|null
     * @deprecated
     */
    public function getDensity() : ?float
    {
        return $this->density;
    }

    /**
     * Returns the property thermalConductivity
     *
     * @return number
     */
    public function getThermalConductivity()
    {
        return $this->thermalConductivity;
    }
    // End getThermalConductivity



    /**
     * Returns the property thermalResistance
     *
     * @return number
     */
    public function getThermalResistance()
    {
        return $this->thermalResistance;
    }
    // End getThermalResistance



    /**
     * Returns the property isReference
     *
     * @return boolean
     */
    public function isReference()
    {
        return $this->isReference;
    }
    // End isReference



    /**
     * Returns the property fHsHi
     *
     * @return float
     */
    public function getFHsHi() : ?float
    {
        return $this->fHsHi;
    }

    /**
     * @return float|null
     */
    public function getDefaultSize() : ?float
    {
        return $this->defaultSize;
    }

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
     * Returns the property svgPatternId
     *
     * @return int
     */
    public function getSvgPatternId()
    {
        return $this->svgPatternId;
    }
    // End getSvgPatternId



    /**
     * Returns the svg pattern
     *
     * @return ElcaSvgPattern
     */
    public function getSvgPattern()
    {
        return ElcaSvgPattern::findById($this->svgPatternId);
    }

    /**
     * @return boolean
     */
    public function isStale()
    {
        return $this->isStale;
    }

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
     * Returns the associated ElcaProcessConversions for this process config
     *
     * @param array    $orderBy
     * @param  boolean $force
     * @return ElcaProcessConversionSet
     * @deprecated
     */
    public function getProcessConversions(array $orderBy = null, $force = false)
    {
        return ElcaProcessConversionSet::findByProcessConfigId($this->id, $orderBy, $force);
    }
    // End getProcessConversionSet

    /**
     * Returns a conversion matrix
     *
     * @param bool $force
     * @return array
     */
    public function getConversionMatrix($force = false)
    {
        if(!$force && $this->conversionMatrix)
            return $this->conversionMatrix;

        $Conversions = $this->getProcessConversions();

        foreach($Conversions as $Conversion)
        {
            if(!$factor = $Conversion->getFactor())
                continue;

            $this->conversionMatrix[$Conversion->getInUnit()][$Conversion->getOutUnit()] = $factor;
            $this->conversionMatrix[$Conversion->getOutUnit()][$Conversion->getInUnit()] = 1 / $factor;
        }

        return $this->conversionMatrix;
    }

    /**
     * Returns the associated ElcaProcesses for this process config
     *
     * @param  array $initValues - filter
     * @param  array $orderBy
     * @param  boolean  $force
     * @return ElcaProcessSet
     */
    public function getProcesses(array $initValues = array(), array $orderBy = null, $force = false)
    {
        return ElcaProcessSet::findByProcessConfigId($this->id, $initValues, $orderBy, $force);
    }
    // End getProcesses



    /**
     * Returns the associated ElcaProcesses for this process config filtered by processDbId
     *
     * @param  array $initValues - filter
     * @param  array $orderBy
     * @param  boolean  $force
     * @return ElcaProcessSet
     */
    public function getProcessesByProcessDbId($processDbId, array $initValues = array(), array $orderBy = null, $force = false)
    {
        $initValues['process_db_id'] = $processDbId;
        return ElcaProcessSet::findByProcessConfigId($this->id, $initValues, $orderBy, $force);
    }
    // End getProcessesByProcessDbId



    /**
     * Returns a list of required units
     *
     * @return array
     */
    public function getRequiredUnits($includeOpLifeCycle = false)
    {
        if(!$this->isInitialized())
            return array();

        $requiredUnits = $filter = array();

        /**
         * Find all refUnits of all configured processes
         */
        $Processes = $this->getProcesses();
        foreach($Processes as $Process)
            if($includeOpLifeCycle || $Process->getLifeCyclePhase() != ElcaLifeCycle::PHASE_OP)
                $requiredUnits[$Process->getRefUnit()] = true;

        return $requiredUnits;
    }
    // End getRequiredUnits



    /**
     * Returns two sets of required and available conversions for a given process config
     *
     * @return array
     */
    public function getRequiredConversions($includeOpLifeCycle = false)
    {
        if(!$this->isInitialized())
            return array(array(), array());

        $requiredUnits = $this->getRequiredUnits($includeOpLifeCycle);

        /**
         * Build triangle matrix
         */
        $matrix = array();
        foreach($requiredUnits as $inUnit => $foo)
        {
            foreach($requiredUnits as $outUnit => $foo)
            {
                 if(!isset($matrix[$outUnit][$inUnit]))
                    $matrix[$inUnit][$outUnit] = true;
            }
        }

        $RequiredConversions = new ElcaProcessConversionSet();
        $AdditionalConversions = new ElcaProcessConversionSet();

        /**
         * Find direct matches in -> out and vice versa
         */
        $Conversions = $this->getProcessConversions(array('ident' => 'ASC', 'id' => 'ASC'), true);
        foreach($Conversions as $Conversion)
        {
            $inUnit  = $Conversion->getInUnit();
            $outUnit = $Conversion->getOutUnit();

            if(isset($matrix[$inUnit][$outUnit]))
            {
                unset($matrix[$inUnit][$outUnit]);
                $RequiredConversions->add($Conversion);
            }
            elseif(isset($matrix[$outUnit][$inUnit]))
            {
                unset($matrix[$outUnit][$inUnit]);
                $RequiredConversions->add($Conversion);
            }
            else
                $AdditionalConversions->add($Conversion);
        }

        /**
         * Add remaining
         */
        foreach($matrix as $inUnit => $outUnits)
        {
            foreach($outUnits as $outUnit => $foo)
            {
                if($inUnit == $outUnit)
                    continue;

                $Conversion = ElcaProcessConversion::findById(null);
                $Conversion->setInUnit($inUnit);
                $Conversion->setOutUnit($outUnit);
                $RequiredConversions->add($Conversion);
            }
        }

        return array($RequiredConversions, $AdditionalConversions);
    }
    // End getRequiredConversions

    /**
     * Returns associated attributes
     *
     * @param bool $force
     * @return ElcaProcessConfigAttributeSet
     */
    public function getAttributes($force = false)
    {
        return ElcaProcessConfigAttributeSet::find(['process_config_id' => $this->getId()], null, null, null, $force);
    }


    /**
     * Returns a associated attribute
     *
     * @param string $ident
     * @param bool $force
     * @return ElcaProcessConfigAttribute
     */
    public function getAttribute($ident, $force = false)
    {
        return ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent($this->getId(), $ident, $force);
    }

    /**
     * Returns a associated attribute
     *
     * @param string $ident
     * @param bool $force
     * @return mixed
     */
    public function getAttributeValue($ident, $force = false)
    {
        return ElcaProcessConfigAttribute::findValue($this->getId(), $ident, $force);
    }

    /**
     * Returns the epd subtype of the assigned production process
     *
     * @param int|null $processDbId
     * @return string|null
     */
    public function getEpdSubType($processDbId = null)
    {
        $initValues = [
            'life_cycle_phase' => ElcaLifeCycle::PHASE_PROD,
        ];

        if (null !== $processDbId) {
            $initValues['process_db_id'] = $processDbId;
        }

        $processes = $this->getProcesses(
            $initValues,
            ['process_db_id' => 'DESC']
        );

        return $processes->count() > 0 ? $processes[0]->getEpdType() : null;
    }


    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - processConfigId
     * @param  boolean  $force - Bypass caching
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
                           SET name                  = :name
                             , process_category_node_id = :processCategoryNodeId
                             , description = :description
                             , min_life_time         = :minLifeTime
                             , avg_life_time         = :avgLifeTime
                             , max_life_time         = :maxLifeTime
                             , life_time_info        = :lifeTimeInfo
                             , min_life_time_info    = :minLifeTimeInfo
                             , avg_life_time_info    = :avgLifeTimeInfo
                             , max_life_time_info    = :maxLifeTimeInfo
                             , density               = :density
                             , thermal_conductivity  = :thermalConductivity
                             , thermal_resistance    = :thermalResistance
                             , is_reference          = :isReference
                             , f_hs_hi               = :fHsHi
                             , default_size          = :defaultSize
                             , uuid                  = :uuid
                             , svg_pattern_id        = :svgPatternId
                             , is_stale              = :isStale
                             , created               = :created
                             , modified              = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'                   => $this->id,
                                        'name'                 => $this->name,
                                        'processCategoryNodeId' => $this->processCategoryNodeId,
                                        'description'          => $this->description,
                                        'minLifeTime'          => $this->minLifeTime,
                                        'avgLifeTime'          => $this->avgLifeTime,
                                        'maxLifeTime'          => $this->maxLifeTime,
                                        'lifeTimeInfo'         => $this->lifeTimeInfo,
                                        'minLifeTimeInfo'      => $this->minLifeTimeInfo,
                                        'avgLifeTimeInfo'      => $this->avgLifeTimeInfo,
                                        'maxLifeTimeInfo'      => $this->maxLifeTimeInfo,
                                        'density'              => $this->density,
                                        'thermalConductivity'  => $this->thermalConductivity,
                                        'thermalResistance'    => $this->thermalResistance,
                                        'isReference'          => $this->isReference,
                                        'fHsHi'                => $this->fHsHi,
                                        'defaultSize'          => $this->defaultSize,
                                        'uuid'                 => $this->uuid,
                                        'svgPatternId'         => $this->svgPatternId,
                                        'isStale'              => $this->isStale,
                                        'created'              => $this->created,
                                        'modified'             => $this->modified)
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


    // protected


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id                    = $this->getNextSequenceValue();
        $this->created               = self::getCurrentTime();
        $this->modified              = null;

        if(!$this->uuid)
            $this->uuid = $this->queryExpression('uuid_generate_v4()');

        $sql = sprintf("INSERT INTO %s (id, name, process_category_node_id, description, min_life_time, avg_life_time, max_life_time, life_time_info, min_life_time_info, avg_life_time_info, max_life_time_info, density, thermal_conductivity, thermal_resistance, is_reference, f_hs_hi, default_size, uuid, svg_pattern_id, is_stale, created, modified)
                               VALUES  (:id, :name, :processCategoryNodeId, :description, :minLifeTime, :avgLifeTime, :maxLifeTime, :lifeTimeInfo, :minLifeTimeInfo, :avgLifeTimeInfo, :maxLifeTimeInfo, :density, :thermalConductivity, :thermalResistance, :isReference, :fHsHi, :defaultSize, :uuid, :svgPatternId, :isStale, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'                   => $this->id,
                                        'name'                 => $this->name,
                                        'processCategoryNodeId' => $this->processCategoryNodeId,
                                        'description'          => $this->description,
                                        'minLifeTime'          => $this->minLifeTime,
                                        'avgLifeTime'          => $this->avgLifeTime,
                                        'maxLifeTime'          => $this->maxLifeTime,
                                        'lifeTimeInfo'         => $this->lifeTimeInfo,
                                        'minLifeTimeInfo'      => $this->minLifeTimeInfo,
                                        'avgLifeTimeInfo'      => $this->avgLifeTimeInfo,
                                        'maxLifeTimeInfo'      => $this->maxLifeTimeInfo,
                                        'density'              => $this->density,
                                        'thermalConductivity'  => $this->thermalConductivity,
                                        'thermalResistance'    => $this->thermalResistance,
                                        'isReference'          => $this->isReference,
                                        'fHsHi'                => $this->fHshi,
                                        'defaultSize'          => $this->defaultSize,
                                        'uuid'                 => $this->uuid,
                                        'svgPatternId'         => $this->svgPatternId,
                                        'isStale'              => $this->isStale,
                                        'created'              => $this->created,
                                        'modified'             => $this->modified));
    }
    // End insert



    /**
     * Inits the object with row values
     *
     * @param  \stdClass $dataObject - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $dataObject = null)
    {
        if (null === $dataObject) {
            return;
        }

        $this->id                    = (int)$dataObject->id;
        $this->name                  = $dataObject->name;
        $this->processCategoryNodeId = (int)$dataObject->process_category_node_id;
        $this->description = $dataObject->description;
        $this->minLifeTime           = $dataObject->min_life_time;
        $this->avgLifeTime           = $dataObject->avg_life_time;
        $this->maxLifeTime           = $dataObject->max_life_time;
        $this->lifeTimeInfo          = $dataObject->life_time_info;
        $this->minLifeTimeInfo       = $dataObject->min_life_time_info;
        $this->avgLifeTimeInfo       = $dataObject->avg_life_time_info;
        $this->maxLifeTimeInfo       = $dataObject->max_life_time_info;
        $this->density               = null !== $dataObject->density ? (float)$dataObject->density : null;
        $this->thermalConductivity   = $dataObject->thermal_conductivity;
        $this->thermalResistance     = $dataObject->thermal_resistance;
        $this->isReference           = (bool)$dataObject->is_reference;
        $this->fHsHi                 = null !== $dataObject->f_hs_hi ? (float)$dataObject->f_hs_hi : null;
        $this->defaultSize           = null !== $dataObject->default_size ? (float)$dataObject->default_size : null;
        $this->uuid                  = $dataObject->uuid;
        $this->svgPatternId          = $dataObject->svg_pattern_id;
        $this->isStale               = (bool)$dataObject->is_stale;
        $this->created               = $dataObject->created;
        $this->modified              = $dataObject->modified;

        /**
         * Set extensions
         */
    }

    /**
     * @return ElcaProcessConfig
     */
    protected static function createUnknown(): ElcaProcessConfig
    {
        $othersInternCategory = ElcaProcessCategory::findByRefNum(ElcaProcessCategory::REF_NUM_OTHERS_INTERN);
        if (!$othersInternCategory->isInitialized()) {
            $othersInternCategory = ElcaProcessCategory::createOthersInternNode();
        }

        $unknownProcessConfig = self::create(
            self::UNKNOWN_NAME,
            $othersInternCategory->getNodeId(),
            null,
            \null,
            null,
            null,
            null,
            null,
            Elca::DEFAULT_LIFE_TIME,
            null,
            null,
            null,
            null,
            null,
            null,
            self::UNKNOWN_UUID,
            null,
            false
        );

        foreach (Elca::$units as $unit => $caption) {
            ElcaProcessConversion::create($unknownProcessConfig->getId(), $unit, $unit, 1);
        }

        return $unknownProcessConfig;
    }
    // End initByDataObject
}
// End class ElcaProcessConfig