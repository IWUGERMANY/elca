<?php
namespace Elca\Db;

use Beibob\Blibs\DbObject;
use Elca\Model\ProcessConfig\Conversion\FlowReference;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\ProcessConversion;
use PDO;

class ElcaProcessConversionAudit extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_conversion_audit';

    /**
     * processConversionAuditId
     */
    private $id;

    /**
     * processConfigId
     */
    private $processConfigId;

    /**
     * processDbId
     */
    private $processDbId;

    /**
     * conversionId
     */
    private $conversionId;

    /**
     * input unit of measure
     */
    private $inUnit;

    /**
     * output unit of measure
     */
    private $outUnit;

    /**
     * conversion factor
     */
    private $factor;

    /**
     * ident
     */
    private $ident;

    private $flowUuid;
    private $flowVersion;


    /**
     * old input unit of measure
     */
    private $oldInUnit;

    /**
     * old output unit of measure
     */
    private $oldOutUnit;

    /**
     * old conversion factor
     */
    private $oldFactor;

    /**
     * old ident
     */
    private $oldIdent;

    private $oldFlowUuid;
    private $oldFlowVersion;

    /**
     * modification time
     */
    private $modified;

    /**
     *
     */
    private $modifiedBy;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'              => PDO::PARAM_INT,
                                        'processConfigId' => PDO::PARAM_INT,
                                        'processDbId'     => PDO::PARAM_INT,
                                        'conversionId'    => PDO::PARAM_INT,
                                        'inUnit'          => PDO::PARAM_STR,
                                        'outUnit'         => PDO::PARAM_STR,
                                        'factor'          => PDO::PARAM_STR,
                                        'ident'           => PDO::PARAM_STR,
                                        'flowUuid'     => PDO::PARAM_STR,
                                        'flowVersion'  => PDO::PARAM_STR,
                                        'oldInUnit'       => PDO::PARAM_STR,
                                        'oldOutUnit'      => PDO::PARAM_STR,
                                        'oldFactor'       => PDO::PARAM_STR,
                                        'oldIdent'        => PDO::PARAM_STR,
                                        'oldFlowUuid'     => PDO::PARAM_STR,
                                        'oldFlowVersion'  => PDO::PARAM_STR,
                                        'modified'        => PDO::PARAM_STR,
                                        'modifiedBy'      => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    public static function recordNew(ProcessConversion $processConversion, string $modifiedBy = null)
    {
        $conversion = $processConversion->conversion();

        self::create(
            $processConversion->processConfigId()->value(),
            $processConversion->processDbId()->value(),
            $processConversion->conversionId()->value(),
            $conversion->fromUnit()->value(),
            $conversion->toUnit()->value(),
            $conversion->factor(),
            $conversion->type()->value(),
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowUuid() : null,
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowVersion() : null,
            null,
            null,
            null,
            null,
            null,
            null,
            $modifiedBy
        );
    }

    public static function recordUpdate(ProcessConversion $processConversion, LinearConversion $oldConversion, FlowReference $oldFlowReference = null, string $modifiedBy = null)
    {
        $conversion = $processConversion->conversion();

        self::create(
            $processConversion->processConfigId()->value(),
            $processConversion->processDbId()->value(),
            $processConversion->conversionId()->value(),
            $conversion->fromUnit()->value(),
            $conversion->toUnit()->value(),
            $conversion->factor(),
            $conversion->type()->value(),
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowUuid() : null,
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowVersion() : null,
            $oldConversion->fromUnit()->value(),
            $oldConversion->toUnit()->value(),
            $oldConversion->factor(),
            $oldConversion->type()->value(),
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowUuid() : null,
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowVersion() : null,
            $modifiedBy
        );
    }

    public static function recordRemoval(ProcessConversion $processConversion, string $modifiedBy = null)
    {
        $oldConversion = $processConversion->conversion();

        self::create(
            $processConversion->processConfigId()->value(),
            $processConversion->processDbId()->value(),
            $processConversion->conversionId()->value(),
            null,
            null,
            null,
            null,
            null, null,
            $oldConversion->fromUnit()->value(),
            $oldConversion->toUnit()->value(),
            $oldConversion->factor(),
            $oldConversion->type()->value(),
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowUuid() : null,
            $processConversion->hasFlowReference() ? $processConversion->flowReference()->flowVersion() : null,
            $modifiedBy
        );
    }
    
    /**
     * Creates the object
     *
     * @param  int      $processConfigId - processConfigId
     * @param  int      $processDbId    - processDbId
     * @param  int      $conversionId   - conversionId
     * @param  string   $inUnit         - input unit of measure
     * @param  string   $outUnit        - output unit of measure
     * @param  float    $factor         - conversion factor
     * @param  string   $oldInUnit      - old input unit of measure
     * @param  string   $oldOutUnit     - old output unit of measure
     * @param  float    $oldFactor      - old conversion factor
     * @param  string   $ident          - ident
     * @param  string   $oldIdent       - old ident
     * @param  string   $modifiedBy     -
     * @return ElcaProcessConversionAudit
     */
    public static function create($processConfigId, $processDbId, $conversionId, $inUnit = null, $outUnit = null, $factor = null, $ident = null, $flowUuid = null, $flowVersion = null, $oldInUnit = null, $oldOutUnit = null, $oldFactor = null, $oldIdent = null, $oldFlowUuid = null, $oldFlowVersion = null, $modifiedBy = null)
    {
        $ElcaProcessConversionAudit = new ElcaProcessConversionAudit();
        $ElcaProcessConversionAudit->setProcessConfigId($processConfigId);
        $ElcaProcessConversionAudit->setProcessDbId($processDbId);
        $ElcaProcessConversionAudit->setConversionId($conversionId);
        $ElcaProcessConversionAudit->setInUnit($inUnit);
        $ElcaProcessConversionAudit->setOutUnit($outUnit);
        $ElcaProcessConversionAudit->setFactor($factor);
        $ElcaProcessConversionAudit->setOldInUnit($oldInUnit);
        $ElcaProcessConversionAudit->setOldOutUnit($oldOutUnit);
        $ElcaProcessConversionAudit->setOldFactor($oldFactor);
        $ElcaProcessConversionAudit->setIdent($ident);
        $ElcaProcessConversionAudit->setOldIdent($oldIdent);
        $ElcaProcessConversionAudit->setFlowUuid($flowUuid);
        $ElcaProcessConversionAudit->setFlowVersion($flowVersion);
        $ElcaProcessConversionAudit->setOldFlowUuid($oldFlowUuid);
        $ElcaProcessConversionAudit->setOldFlowVersion($oldFlowVersion);
        $ElcaProcessConversionAudit->setModifiedBy($modifiedBy);

        if($ElcaProcessConversionAudit->getValidator()->isValid())
            $ElcaProcessConversionAudit->insert();

        return $ElcaProcessConversionAudit;
    }
    // End create


    /**
     * Inits a `ElcaProcessConversionAudit' by its primary key
     *
     * @param  int      $id    - processConversionAuditId
     * @param  bool     $force - Bypass caching
     * @return ElcaProcessConversionAudit
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaProcessConversionAudit();

        $sql = sprintf("SELECT id
                             , process_config_id
                             , process_db_id
                             , conversion_id
                             , in_unit
                             , out_unit
                             , factor
                             , ident
                             , old_in_unit
                             , old_out_unit
                             , old_factor
                             , old_ident
                             , modified
                             , modified_by
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }


    /**
     * Sets the property processConfigId
     *
     * @param  int      $processConfigId - processConfigId
     * @return void
     */
    public function setProcessConfigId($processConfigId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConfigId', $processConfigId))
            return;

        $this->processConfigId = (int)$processConfigId;
    }
    // End setProcessConfigId


    /**
     * Sets the property processDbId
     *
     * @param  int      $processDbId - processDbId
     * @return void
     */
    public function setProcessDbId($processDbId)
    {
        if(!$this->getValidator()->assertNotEmpty('processDbId', $processDbId))
            return;

        $this->processDbId = (int)$processDbId;
    }
    // End setProcessDbId


    /**
     * Sets the property conversionId
     *
     * @param  int      $conversionId - conversionId
     * @return void
     */
    public function setConversionId($conversionId)
    {
        if(!$this->getValidator()->assertNotEmpty('conversionId', $conversionId))
            return;

        $this->conversionId = (int)$conversionId;
    }
    // End setConversionId


    /**
     * Sets the property inUnit
     *
     * @param  string   $inUnit - input unit of measure
     * @return void
     */
    public function setInUnit($inUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('inUnit', 10, $inUnit))
            return;

        $this->inUnit = $inUnit;
    }
    // End setInUnit


    /**
     * Sets the property outUnit
     *
     * @param  string   $outUnit - output unit of measure
     * @return void
     */
    public function setOutUnit($outUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('outUnit', 10, $outUnit))
            return;

        $this->outUnit = $outUnit;
    }
    // End setOutUnit


    /**
     * Sets the property factor
     *
     * @param  float    $factor - conversion factor
     * @return void
     */
    public function setFactor($factor = null)
    {
        $this->factor = $factor;
    }
    // End setFactor


    /**
     * Sets the property ident
     *
     * @param  string   $ident - ident
     * @return void
     */
    public function setIdent($ident = null)
    {
        if(!$this->getValidator()->assertMaxLength('ident', 20, $ident))
            return;

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


    /**
     * Sets the property oldInUnit
     *
     * @param  string   $oldInUnit - old input unit of measure
     * @return void
     */
    public function setOldInUnit($oldInUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('oldInUnit', 10, $oldInUnit))
            return;

        $this->oldInUnit = $oldInUnit;
    }
    // End setOldInUnit


    /**
     * Sets the property oldOutUnit
     *
     * @param  string   $oldOutUnit - old output unit of measure
     * @return void
     */
    public function setOldOutUnit($oldOutUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('oldOutUnit', 10, $oldOutUnit))
            return;

        $this->oldOutUnit = $oldOutUnit;
    }
    // End setOldOutUnit


    /**
     * Sets the property oldFactor
     *
     * @param  float    $oldFactor - old conversion factor
     * @return void
     */
    public function setOldFactor($oldFactor = null)
    {
        $this->oldFactor = $oldFactor;
    }
    // End setOldFactor


    /**
     * Sets the property oldIdent
     *
     * @param  string   $oldIdent - old ident
     * @return void
     */
    public function setOldIdent($oldIdent = null)
    {
        if(!$this->getValidator()->assertMaxLength('oldIdent', 20, $oldIdent))
            return;

        $this->oldIdent = $oldIdent;
    }

    public function oldFlowUuid()
    {
        return $this->oldFlowUuid;
    }

    public function setOldFlowUuid($oldFlowUuid): void
    {
        $this->oldFlowUuid = $oldFlowUuid;
    }

    public function oldFlowVersion()
    {
        return $this->oldFlowVersion;
    }

    public function setOldFlowVersion($oldFlowVersion): void
    {
        $this->oldFlowVersion = $oldFlowVersion;
    }
    // End setOldIdent


    /**
     * Sets the property modifiedBy
     *
     * @param  string   $modifiedBy -
     * @return void
     */
    public function setModifiedBy($modifiedBy = null)
    {
        if(!$this->getValidator()->assertMaxLength('modifiedBy', 200, $modifiedBy))
            return;

        $this->modifiedBy = $modifiedBy;
    }
    // End setModifiedBy


    /**
     * Returns the property id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    // End getId


    /**
     * Returns the property processConfigId
     *
     * @return int
     */
    public function getProcessConfigId()
    {
        return $this->processConfigId;
    }
    // End getProcessConfigId


    /**
     * Returns the property processDbId
     *
     * @return int
     */
    public function getProcessDbId()
    {
        return $this->processDbId;
    }
    // End getProcessDbId


    /**
     * Returns the property conversionId
     *
     * @return int
     */
    public function getConversionId()
    {
        return $this->conversionId;
    }
    // End getConversionId


    /**
     * Returns the property inUnit
     *
     * @return string
     */
    public function getInUnit()
    {
        return $this->inUnit;
    }
    // End getInUnit


    /**
     * Returns the property outUnit
     *
     * @return string
     */
    public function getOutUnit()
    {
        return $this->outUnit;
    }
    // End getOutUnit


    /**
     * Returns the property factor
     *
     * @return float
     */
    public function getFactor()
    {
        return $this->factor;
    }
    // End getFactor


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


    /**
     * Returns the property oldInUnit
     *
     * @return string
     */
    public function getOldInUnit()
    {
        return $this->oldInUnit;
    }
    // End getOldInUnit


    /**
     * Returns the property oldOutUnit
     *
     * @return string
     */
    public function getOldOutUnit()
    {
        return $this->oldOutUnit;
    }
    // End getOldOutUnit


    /**
     * Returns the property oldFactor
     *
     * @return float
     */
    public function getOldFactor()
    {
        return $this->oldFactor;
    }
    // End getOldFactor


    /**
     * Returns the property oldIdent
     *
     * @return string
     */
    public function getOldIdent()
    {
        return $this->oldIdent;
    }
    // End getOldIdent


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
     * Returns the property modifiedBy
     *
     * @return string
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }
    // End getModifiedBy


    /**
     * Checks, if the object exists
     *
     * @param  int      $id    - processConversionAuditId
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End exists


    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $this->modified = self::getCurrentTime();

        $sql = sprintf("UPDATE %s
                           SET process_config_id = :processConfigId
                             , process_db_id   = :processDbId
                             , conversion_id   = :conversionId
                             , in_unit         = :inUnit
                             , out_unit        = :outUnit
                             , factor          = :factor
                             , ident           = :ident
                             , flow_uuid       = :flowUuid
                             , flow_version    = :flowVersion
                             , old_in_unit     = :oldInUnit
                             , old_out_unit    = :oldOutUnit
                             , old_factor      = :oldFactor
                             , old_ident       = :oldIdent
                             , old_flow_uuid       = :oldFlowUuid
                             , old_flow_version    = :oldFlowVersion
                             , modified        = :modified
                             , modified_by     = :modifiedBy
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'processDbId'    => $this->processDbId,
                                        'conversionId'   => $this->conversionId,
                                        'inUnit'         => $this->inUnit,
                                        'outUnit'        => $this->outUnit,
                                        'factor'         => $this->factor,
                                        'ident'          => $this->ident,
                                        'flowUuid' => $this->flowUuid,
                                        'flowVersion' => $this->flowVersion,
                                        'oldInUnit'      => $this->oldInUnit,
                                        'oldOutUnit'     => $this->oldOutUnit,
                                        'oldFactor'      => $this->oldFactor,
                                        'oldIdent'       => $this->oldIdent,
                                        'oldFlowUuid' => $this->oldFlowUuid,
                                        'oldFlowVersion' => $this->oldFlowVersion,
                                        'modified'       => $this->modified,
                                        'modifiedBy'     => $this->modifiedBy)
                                  );
    }
    // End update


    /**
     * Deletes the object from the table
     *
     * @return bool
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
     * @param  bool     $propertiesOnly
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
     * @param  bool     $extColumns
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

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        $this->id              = $this->getNextSequenceValue();
        $this->modified = self::getCurrentTime();

        $sql = sprintf("INSERT INTO %s (id, process_config_id, process_db_id, conversion_id, in_unit, out_unit, factor, ident, flow_uuid, flow_version, old_in_unit, old_out_unit, old_factor, old_ident, old_flow_uuid, old_flow_version, modified, modified_by)
                               VALUES  (:id, :processConfigId, :processDbId, :conversionId, :inUnit, :outUnit, :factor, :ident, :flowUuid, :flowVersion, :oldInUnit, :oldOutUnit, :oldFactor, :oldIdent, :oldFlowUuid, :oldFlowVersion, :modified, :modifiedBy)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'             => $this->id,
                                        'processConfigId' => $this->processConfigId,
                                        'processDbId'    => $this->processDbId,
                                        'conversionId'   => $this->conversionId,
                                        'inUnit'         => $this->inUnit,
                                        'outUnit'        => $this->outUnit,
                                        'factor'         => $this->factor,
                                        'ident'          => $this->ident,
                                        'flowUuid' => $this->flowUuid,
                                        'flowVersion' => $this->flowVersion,
                                        'oldInUnit'      => $this->oldInUnit,
                                        'oldOutUnit'     => $this->oldOutUnit,
                                        'oldFactor'      => $this->oldFactor,
                                        'oldIdent'       => $this->oldIdent,
                                        'oldFlowUuid' => $this->oldFlowUuid,
                                        'oldFlowVersion' => $this->oldFlowVersion,
                                        'modified'       => $this->modified,
                                        'modifiedBy'     => $this->modifiedBy)
                                  );
    }
    // End insert


    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return bool
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->id              = (int)$DO->id;
        $this->processConfigId = (int)$DO->process_config_id;
        $this->processDbId     = (int)$DO->process_db_id;
        $this->conversionId    = (int)$DO->conversion_id;
        $this->inUnit          = $DO->in_unit;
        $this->outUnit         = $DO->out_unit;
        $this->factor          = $DO->factor;
        $this->ident           = $DO->ident;
        $this->flowUuid            = $DO->flow_uuid;
        $this->flowVersion         = $DO->flow_version;
        $this->oldInUnit       = $DO->old_in_unit;
        $this->oldOutUnit      = $DO->old_out_unit;
        $this->oldFactor       = $DO->old_factor;
        $this->oldIdent        = $DO->old_ident;
        $this->oldFlowUuid            = $DO->old_flow_uuid;
        $this->oldFlowVersion         = $DO->old_flow_version;
        $this->modified        = $DO->modified;
        $this->modifiedBy      = $DO->modified_by;


        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProcessConversionAudit