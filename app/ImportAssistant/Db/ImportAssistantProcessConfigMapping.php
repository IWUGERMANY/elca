<?php

namespace ImportAssistant\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaProcessConfig;
use PDO;

class ImportAssistantProcessConfigMapping extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'import_assistant.process_config_mapping';
    const VIEW_PROCESS_CONFIG_MAPPING_CONVERSIONS = 'import_assistant.process_config_mapping_conversions_view';

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'id'                      => PDO::PARAM_INT,
        'materialName'            => PDO::PARAM_STR,
        'process_db_id' => PDO::PARAM_INT,
        'processConfigId'         => PDO::PARAM_INT,
        'isSibling'               => PDO::PARAM_BOOL,
        'siblingRatio'            => PDO::PARAM_STR,
        'requiredAdditionalLayer' => PDO::PARAM_BOOL,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array(
        'processConfigName' => PDO::PARAM_STR,
        'units'             => PDO::PARAM_STR,
        'epdSubTypes'       => PDO::PARAM_STR,
        'processDbIds'      => PDO::PARAM_STR,
    );

    /**
     *
     */
    private $id;

    /**
     *
     */
    private $materialName;

    /**
     *
     */
    private $processConfigId;

    private $processDbId;

    private $processConfigName;

    private $isSibling;

    /**
     *
     */
    private $siblingRatio;

    /**
     *
     */
    private $requiredAdditionalLayer;

    private $units = [];

    private $epdSubTypes = [];

    private $processDbIds = [];

    /**
     * Creates the object
     *
     * @param  string $materialName            -
     * @param  int    $processConfigId         -
     * @param  bool   $isSibling               -
     * @param  float  $siblingRatio            -
     * @param  bool   $requiredAdditionalLayer -
     * @return ImportAssistantProcessConfigMapping
     */
    public static function create(
        $materialName, $processDbId, $processConfigId, $isSibling = false, $siblingRatio = null, $requiredAdditionalLayer = false
    ) {
        $mapping = new ImportAssistantProcessConfigMapping();
        $mapping->setMaterialName($materialName);
        $mapping->setProcessDbId($processDbId);
        $mapping->setProcessConfigId($processConfigId);
        $mapping->setIsSibling($isSibling);
        $mapping->setSiblingRatio($siblingRatio);
        $mapping->setRequiredAdditionalLayer($requiredAdditionalLayer);

        if ($mapping->getValidator()->isValid()) {
            $mapping->insert();
        }

        return $mapping;
    }
    // End create


    /**
     * Inits a `ImportAssistantProcessConfigMapping' by its primary key
     *
     * @param  int  $id    -
     * @param  bool $force - Bypass caching
     * @return ImportAssistantProcessConfigMapping
     */
    public static function findById($id, $force = false)
    {
        if (!$id) {
            return new ImportAssistantProcessConfigMapping();
        }

        $sql = sprintf(
            "SELECT id
                             , material_name
                             , process_config_id
                             , process_config_name
                             , process_db_id
                             , is_sibling
                             , sibling_ratio
                             , required_additional_layer
                             , units
                             , epd_types
                             , process_db_ids
                          FROM %s
                         WHERE id = :id"
            ,
            self::VIEW_PROCESS_CONFIG_MAPPING_CONVERSIONS
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    /**
     * Checks, if the object exists
     *
     * @param  int  $id    -
     * @param  bool $force - Bypass caching
     * @return bool
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End setmaterialName

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
    // End setProcessConfigId

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  bool  $extColumns
     * @param  mixed $column
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
    // End setIsSibling

    /**
     * Returns the property id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    // End setSiblingRatio

    /**
     * Returns the property materialName
     *
     * @return string
     */
    public function getMaterialName()
    {
        return $this->materialName;
    }
    // End setRequiredAdditionalLayer

    /**
     * Sets the property materialName
     *
     * @param  string $materialName -
     * @return void
     */
    public function setMaterialName($materialName)
    {
        if (!$this->getValidator()->assertNotEmpty('materialName', $materialName)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('materialName', 200, $materialName)) {
            return;
        }

        $this->materialName = (string)$materialName;
    }

    /**
     * @return mixed
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }

    /**
     * @param mixed $processDbId
     */
    public function setProcessDbId($processDbId): void
    {
        $this->processDbId = $processDbId;
    }


    /**
     * Returns the property processConfigId
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getmaterialName

    /**
     * Sets the property processConfigId
     *
     * @param  int $processConfigId -
     * @return void
     */
    public function setProcessConfigId($processConfigId)
    {
        if (!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId)) {
            return;
        }

        $this->processConfigId = (int)$processConfigId;
    }

    /**
     * @return string
     */
    public function processConfigName()
    {
        return $this->processConfigName;
    }


    /**
     * Returns the associated ElcaProcessConfig by property processConfigId
     *
     * @param  bool $force
     * @return ElcaProcessConfig
     */
    public function getProcessConfig($force = false)
    {
        return ElcaProcessConfig::findById($this->processConfigId, $force);
    }
    // End getProcessConfig


    /**
     * Returns the property isSibling
     *
     * @return bool
     */
    public function isSibling()
    {
        return $this->isSibling;
    }
    // End isSibling

    /**
     * Sets the property isSibling
     *
     * @param  bool $isSibling -
     * @return void
     */
    public function setIsSibling($isSibling = false)
    {
        $this->isSibling = (bool)$isSibling;
    }
    // End getSiblingRatio

    /**
     * Returns the property siblingRatio
     *
     * @return float
     */
    public function getSiblingRatio()
    {
        return $this->siblingRatio;
    }
    // End getRequiredAdditionalLayer

    /**
     * Sets the property siblingRatio
     *
     * @param  float $siblingRatio -
     * @return void
     */
    public function setSiblingRatio($siblingRatio = null)
    {
        $this->siblingRatio = $siblingRatio;
    }

    /**
     * Returns the property requiredAdditionalLayer
     *
     * @return bool
     */
    public function getRequiredAdditionalLayer()
    {
        return $this->requiredAdditionalLayer;
    }

    /**
     * Sets the property requiredAdditionalLayer
     *
     * @param  bool $requiredAdditionalLayer -
     * @return void
     */
    public function setRequiredAdditionalLayer($requiredAdditionalLayer = false)
    {
        $this->requiredAdditionalLayer = (bool)$requiredAdditionalLayer;
    }

    /**
     * @return array
     */
    public function getUnits()
    {
        return $this->units;
    }

    public function getEpdSubTypes()
    {
        return $this->epdSubTypes;
    }

    public function getProcessDbIds()
    {
        return $this->processDbIds;
    }

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf(
            "UPDATE %s
                           SET material_name       = :materialName
                             , process_db_id       = :processDbId
                             , process_config_id       = :processConfigId
                             , is_sibling              = :isSibling
                             , sibling_ratio           = :siblingRatio
                             , required_additional_layer = :requiredAdditionalLayer
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'id'                      => $this->id,
                'materialName'            => $this->materialName,
                'processDbId'         => $this->processDbId,
                'processConfigId'         => $this->processConfigId,
                'isSibling'               => $this->isSibling,
                'siblingRatio'            => $this->siblingRatio,
                'requiredAdditionalLayer' => $this->requiredAdditionalLayer,
            )
        );
    }
    // End getPrimaryKey

    /**
     * Deletes the object from the table
     *
     * @return bool
     */
    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            array('id' => $this->id)
        );
    }
    // End getTablename

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  bool $propertiesOnly
     * @return array
     */
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
    // End getColumnTypes

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        $this->id = $this->getNextSequenceValue();

        $sql = sprintf(
            "INSERT INTO %s (id, material_name, process_db_id, process_config_id, is_sibling, sibling_ratio, required_additional_layer)
                               VALUES  (:id, :materialName, :processDbId, :processConfigId, :isSibling, :siblingRatio, :requiredAdditionalLayer)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'id'                      => $this->id,
                'materialName'            => $this->materialName,
                'processDbId'         => $this->processDbId,
                'processConfigId'         => $this->processConfigId,
                'isSibling'               => $this->isSibling,
                'siblingRatio'            => $this->siblingRatio,
                'requiredAdditionalLayer' => $this->requiredAdditionalLayer,
            )
        );
    }
    // End insert


    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        if (null === $DO) {
            return;
        }

        $this->id                      = (int)$DO->id;
        $this->materialName            = $DO->material_name;
        $this->processDbId             = (int)$DO->process_db_id;
        $this->processConfigId         = (int)$DO->process_config_id;
        $this->isSibling               = (bool)$DO->is_sibling;
        $this->siblingRatio            = $DO->sibling_ratio;
        $this->requiredAdditionalLayer = (bool)$DO->required_additional_layer;

        if (isset($DO->units)) {
            $this->units = str_getcsv(trim($DO->units, '{}'));
        }

        if (isset($DO->epd_types)) {
            $this->epdSubTypes = str_getcsv(trim($DO->epd_types, '{}'));
        }

        if (isset($DO->process_db_ids)) {
            $this->processDbIds = str_getcsv(trim($DO->process_db_ids, '{}'));
        }

        if (isset($DO->process_config_name)) {
            $this->processConfigName = $DO->process_config_name;
        }
    }
    // End initByDataObject
}
