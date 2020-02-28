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
class ElcaProcessConversionVersion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_conversion_versions';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Primary key
     */
    private static $primaryKey = ['conversionId', 'processDbId'];

    /**
     * Column types
     */
    private static $columnTypes = [
        'conversionId' => PDO::PARAM_INT,
        'processDbId'  => PDO::PARAM_INT,
        'factor'       => PDO::PARAM_STR,
        'ident'        => PDO::PARAM_STR,
        'flowUuid'     => PDO::PARAM_STR,
        'flowVersion'  => PDO::PARAM_STR,
        'created'      => PDO::PARAM_STR,
        'modified'     => PDO::PARAM_STR,
    ];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [
        'processConfigId' => PDO::PARAM_INT,
        'inUnit'          => PDO::PARAM_STR,
        'outUnit'         => PDO::PARAM_STR,
    ];

    /**
     * conversionId
     */
    private $conversionId;

    /**
     * processConfigId
     */
    private $processDbId;

    /**
     * conversion factor
     */
    private $factor;

    /**
     * internal ident
     */
    private $ident;

    private $flowUuid;
    private $flowVersion;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * Ext
     */
    private $processConfigId;

    /**
     * Ext
     */
    private $inUnit;

    /**
     * Ext
     */
    private $outUnit;

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    public static function create($conversionId, $processDbId, $factor, $ident = null, $flowUuid = null, $flowVersion = null): ElcaProcessConversionVersion
    {
        $processConversionVersion = new self();
        $processConversionVersion->setConversionId($conversionId);
        $processConversionVersion->setProcessDbId($processDbId);
        $processConversionVersion->setFactor($factor);
        $processConversionVersion->setIdent($ident);
        $processConversionVersion->setFlowUuid($flowUuid);
        $processConversionVersion->setFlowVersion($flowVersion);

        if ($processConversionVersion->getValidator()->isValid()) {
            $processConversionVersion->insert();
        }

        return $processConversionVersion;
    }

    public static function findByPK($conversionId, $processDbId, $force = false): ElcaProcessConversionVersion
    {
        if (!$conversionId) {
            return new ElcaProcessConversionVersion();
        }

        $sql = sprintf(
            "SELECT conversion_id
                             , process_db_id
                             , factor
                             , ident
                             , flow_uuid
                             , flow_version
                             , created
                             , modified
                          FROM %s
                         WHERE (conversion_id, process_db_id) = (:conversionId, :processDbId)"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(
            get_class(),
            $sql,
            ['conversionId' => $conversionId, 'processDbId' => $processDbId],
            $force
        );
    }

    public static function findExtendedIdentityByProcessConfigIdProcessDbIdAndUnit($processConfigId, $processDbId, $unit,
        $force = false)
    {
        if (!$processConfigId || !$processDbId || !$unit) {
            return new ElcaProcessConversionVersion();
        }

        $sql = sprintf(
            'SELECT cv.*
                                    , c.process_config_id
                                    , c.in_unit
                                    , c.out_unit
                                 FROM %s c 
                                 JOIN %s cv ON c.id = cv.conversion_id
                                WHERE (c.process_config_id, cv.process_db_id) = (:processConfigId, :processDbId)
                                  AND (c.in_unit, c.out_unit) = (:unit, :unit)',
            ElcaProcessConversion::TABLE_NAME,
            ElcaProcessConversionVersion::TABLE_NAME
        );

        return self::findBySql(
            get_class(),
            $sql,
            [
                'processConfigId' => $processConfigId,
                'processDbId'     => $processDbId,
                'unit' => $unit,
            ],
            $force
        );
    }

    public static function exists($conversionId, $processDbId, $force = false)
    {
        return self::findByPK($conversionId, $processDbId, $force)->isInitialized();
    }

    public static function getTablename()
    {
        return self::TABLE_NAME;
    }

    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column) {
            return $columnTypes[$column];
        }

        return $columnTypes;
    }

    public function copy($newProcessDbId)
    {
        return self::create(
            $this->conversionId,
            $newProcessDbId,
            $this->factor,
            $this->ident,
            $this->flowUuid,
            $this->flowVersion
        );
    }

    public function getConversionId()
    {
        return $this->conversionId;
    }

    public function setConversionId($conversionId): void
    {
        $this->conversionId = $conversionId;
    }

    public function getProcessDbId()
    {
        return $this->processDbId;
    }

    public function setProcessDbId($processDbId)
    {
        if (!$this->getValidator()->assertNotEmpty('processDbId', $processDbId)) {
            return;
        }

        $this->processDbId = (int)$processDbId;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function setFactor($factor)
    {
        if (!$this->getValidator()->assertNotEmpty('factor', $factor)) {
            return;
        }

        if (!$this->getValidator()->assertNumber('factor', $factor)) {
            return;
        }

        $this->factor = $factor;
    }

    public function getIdent()
    {
        return $this->ident;
    }

    public function setIdent($ident = null)
    {
        if (!$this->getValidator()->assertMaxLength('ident', 20, $ident)) {
            return;
        }

        $this->ident = $ident;
    }

    public function flowUuid()
    {
        return $this->flowUuid;
    }

    public function setFlowUuid($flowUuid): void
    {
        $this->flowUuid = $flowUuid;
    }

    public function flowVersion()
    {
        return $this->flowVersion;
    }

    public function setFlowVersion($flowVersion): void
    {
        $this->flowVersion = $flowVersion;
    }


    public function getCreated()
    {
        return $this->created;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function getProcessConfigId()
    {
        if (null !== $this->processConfigId) {
            return $this->processConfigId;
        }

        return ElcaProcessConversion::findById($this->conversionId)->getProcessConfigId();
    }

    public function getInUnit()
    {
        if (null !== $this->inUnit) {
            return $this->inUnit;
        }

        return ElcaProcessConversion::findById($this->conversionId)->getInUnit();
    }

    public function getOutUnit()
    {
        if (null !== $this->outUnit) {
            return $this->outUnit;
        }

        return ElcaProcessConversion::findById($this->conversionId)->getOutUnit();
    }

    public function update()
    {
        $this->modified = self::getCurrentTime();

        $sql = sprintf(
            "UPDATE %s
                           SET factor          = :factor
                             , ident           = :ident
                             , flow_uuid       = :flowUuid
                             , flow_version    = :flowVersion
                             , created         = :created
                             , modified        = :modified
                         WHERE (conversion_id, process_db_id) = (:conversionId, :processDbId)"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'conversionId' => $this->conversionId,
                'processDbId'  => $this->processDbId,
                'factor'       => $this->factor,
                'ident'        => $this->ident,
                'flowUuid'     => $this->flowUuid,
                'flowVersion'  => $this->flowVersion,
                'created'      => $this->created,
                'modified'     => $this->modified,
            )
        );
    }

    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE (conversion_id, process_db_id) = (:conversionId, :processDbId)"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            [
                'conversionId' => $this->conversionId,
                'processDbId'  => $this->processDbId,
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

    public function getProcessConversion($force = false): ElcaProcessConversion
    {
        return ElcaProcessConversion::findById($this->conversionId, $force);
    }

    protected function insert()
    {
        $this->created  = self::getCurrentTime();
        $this->modified = null;

        $sql = sprintf(
            "INSERT INTO %s (conversion_id, process_db_id, factor, ident, flow_uuid, flow_version, created, modified)
                               VALUES  (:conversionId, :processDbId, :factor, :ident, :flowUuid, :flowVersion, :created, :modified)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'conversionId' => $this->conversionId,
                'processDbId'  => $this->processDbId,
                'factor'       => $this->factor,
                'ident'        => $this->ident,
                'flowUuid'     => $this->flowUuid,
                'flowVersion'  => $this->flowVersion,
                'created'      => $this->created,
                'modified'     => $this->modified,
            )
        );
    }

    protected function initByDataObject(\stdClass $dataObject = null)
    {
        if (null === $dataObject) {
            return;
        }

        $this->conversionId = (int)$dataObject->conversion_id;
        $this->processDbId         = (int)$dataObject->process_db_id;
        $this->factor              = null !== $dataObject->factor ? (float)$dataObject->factor : null;
        $this->ident               = $dataObject->ident;
        $this->flowUuid            = $dataObject->flow_uuid;
        $this->flowVersion         = $dataObject->flow_version;
        $this->created             = $dataObject->created;
        $this->modified            = $dataObject->modified;

        /**
         * Set extensions
         */
        if (isset($dataObject->process_config_id)) {
            $this->processConfigId = $dataObject->process_config_id;
        }
        if (isset($dataObject->in_unit)) {
            $this->inUnit = $dataObject->in_unit;
        }
        if (isset($dataObject->out_unit)) {
            $this->outUnit = $dataObject->out_unit;
        }
    }
}
// End class ElcaProcessConversion