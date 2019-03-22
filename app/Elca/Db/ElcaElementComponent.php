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
use Exception;
use PDO;

/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementComponent extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.element_components';

    /**
     * elementComponentId
     */
    private $id;

    /**
     * associated element id
     */
    private $elementId;

    /**
     * process config id
     */
    private $processConfigId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * process conversion id
     */
    private $processConversionId;

    /**
     * life time
     */
    private $lifeTime;

    /**
     * life time delay
     */
    private $lifeTimeDelay;

    /**
     * @var string
     */
    private $lifeTimeInfo;

    /**
     * indicated if the lca for this component should be calculated
     */
    private $calcLca;

    /**
     * indicates if the component is pre-existing in extant buildings
     */
    private $isExtant;

    /**
     * indicates if this component is a layer
     */
    private $isLayer;

    /**
     * position of layer
     */
    private $layerPosition;

    /**
     * size of layer in [m]
     */
    private $layerSize;

    /**
     * references another component as sibling within the same layer
     */
    private $layerSiblingId;

    /**
     * proportion of area (only valid with sibling)
     */
    private $layerAreaRatio;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    /**
     * length of the layer
     */
    private $layerLength;

    /**
     * width of the layer
     */
    private $layerWidth;

    /**
     * Ext: element name
     */
    public $elementName;

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                  => PDO::PARAM_INT,
                                        'elementId'           => PDO::PARAM_INT,
                                        'processConfigId'     => PDO::PARAM_INT,
                                        'quantity'            => PDO::PARAM_STR,
                                        'processConversionId' => PDO::PARAM_INT,
                                        'lifeTime'            => PDO::PARAM_INT,
                                        'lifeTimeDelay'       => PDO::PARAM_INT,
                                        'lifeTimeInfo'        => PDO::PARAM_STR,
                                        'calcLca'             => PDO::PARAM_BOOL,
                                        'isExtant'            => PDO::PARAM_BOOL,
                                        'isLayer'             => PDO::PARAM_BOOL,
                                        'layerPosition'       => PDO::PARAM_INT,
                                        'layerSize'           => PDO::PARAM_STR,
                                        'layerSiblingId'      => PDO::PARAM_INT,
                                        'layerAreaRatio'      => PDO::PARAM_STR,
                                        'created'             => PDO::PARAM_STR,
                                        'modified'            => PDO::PARAM_STR,
                                        'layerLength'         => PDO::PARAM_STR,
                                        'layerWidth'          => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('element_name' => PDO::PARAM_STR);


    /**
     * Creates the object
     *
     * @param  integer     $elementId           - associated element id
     * @param  integer     $processConfigId     - process config id
     * @param  integer     $processConversionId - process conversion id
     * @param  integer     $lifeTime            - life time
     * @param  boolean     $isLayer             - indicates if this component is a layer
     * @param int|\number $quantity            - quantity
     * @param  boolean     $calcLca             - indicated if the lca for this component should be calculated
     * @param bool         $isExtant            - indicates if the component is pre-existing in extant buildings
     * @param  integer     $layerPosition       - position of layer
     * @param  number     $layerSize           - size of layer in [m]
     * @param  integer     $layerSiblingId      - references another component as sibling within the same layer
     * @param int|\number $layerAreaRatio      - proportion of area (only valid with sibling)
     * @param int|\number $layerLength         - length of the layer
     * @param int|\number $layerWidth          - width of the layer
     * @return ElcaElementComponent
     */
    public static function create($elementId, $processConfigId, $processConversionId, $lifeTime, $isLayer, $quantity = 1, $calcLca = true, $isExtant = false, $layerPosition = null, $layerSize = null, $layerSiblingId = null, $layerAreaRatio = 1, $layerLength = 1, $layerWidth = 1, $lifeTimeDelay = 0, $lifeTimeInfo = null)
    {
        $ElcaElementComponent = new ElcaElementComponent();
        $ElcaElementComponent->setElementId($elementId);
        $ElcaElementComponent->setProcessConfigId($processConfigId);
        $ElcaElementComponent->setProcessConversionId($processConversionId);
        $ElcaElementComponent->setLifeTime($lifeTime);
        $ElcaElementComponent->setLifeTimeDelay($lifeTimeDelay);
        $ElcaElementComponent->setLifeTimeInfo($lifeTimeInfo);
        $ElcaElementComponent->setIsLayer($isLayer);
        $ElcaElementComponent->setQuantity($quantity);
        $ElcaElementComponent->setCalcLca($calcLca);
        $ElcaElementComponent->setIsExtant($isExtant);
        $ElcaElementComponent->setLayerPosition($layerPosition);
        $ElcaElementComponent->setLayerSize($layerSize);
        $ElcaElementComponent->setLayerSiblingId($layerSiblingId);
        $ElcaElementComponent->setLayerAreaRatio($layerAreaRatio);
        $ElcaElementComponent->setLayerLength($layerLength);
        $ElcaElementComponent->setLayerWidth($layerWidth);

        if($ElcaElementComponent->getValidator()->isValid())
            $ElcaElementComponent->insert();

        return $ElcaElementComponent;
    }
    // End create


    /**
     * Creates a sibling of this component
     *
     * @throws Exception
     * @return \ElcaElementComponent -
     */
    public function createSibling()
    {
        if(!$this->isInitialized() || !$this->isLayer() || $this->hasLayerSibling())
            return new ElcaElementComponent();

        try
        {
            $this->Dbh->begin();

            $Sibling = self::create($this->elementId, $this->processConfigId, $this->processConversionId,
                                    $this->lifeTime, $this->isLayer, $this->quantity, $this->calcLca, $this->isExtant,
                                    $this->layerPosition, $this->layerSize, $this->id, 0.5, 1, 1, $this->lifeTimeDelay, $this->lifeTimeInfo);

            $this->setLayerSiblingId($Sibling->getId());
            $this->setLayerAreaRatio(0.5);
            $this->update();

            $this->Dbh->commit();
        }
        catch(Exception $Exception)
        {
            $this->Dbh->rollback();
            throw $Exception;
        }

        return $Sibling;
    }
    // End createSibling



    /**
     * Inits a `ElcaElementComponent' by its primary key
     *
     * @param  integer  $id    - elementComponentId
     * @param  boolean  $force - Bypass caching
     * @return ElcaElementComponent
     */
    public static function findById($id, $force = false)
    {
        if(!is_numeric($id))
            return new ElcaElementComponent();

        $sql = sprintf("SELECT id
                             , element_id
                             , process_config_id
                             , quantity
                             , process_conversion_id
                             , life_time
                             , life_time_delay
                             , life_time_info
                             , calc_lca
                             , is_extant
                             , is_layer
                             , layer_position
                             , layer_size
                             , layer_sibling_id
                             , layer_area_ratio
                             , created
                             , modified
                             , layer_length
                             , layer_width
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById



    /**
     * Get the max position by elementId
     *
     * @return int
     */
    public static function getMaxLayerPosition($elementId)
    {
        $sql = sprintf('SELECT max(layer_position) AS max_position FROM %s WHERE is_layer = true AND element_id = :elementId'
                       , self::TABLE_NAME);

        $Stmt = self::prepareStatement($sql, array('elementId' => $elementId));
        $Stmt->execute();

        if(!$DataObject = $Stmt->fetchObject())
            return null;

        return $DataObject->max_position;
    }
    // End getMaxLayerPosition



    /**
     * Creates a copy from this element component
     *
     * @param  int $elementId
     * @param  int $layerSiblingId
     * @return ElcaElementComponent - the new element component copy
     */
    public function copy($elementId, $layerSiblingId = null, $copyCacheItems = false, $resetAreaRatio = false, $newLayerPosition = null)
    {
        if(!$this->isInitialized() || !$elementId)
            return new ElcaElementComponent();

        $Copy = self::create(
            $elementId,
            $this->processConfigId,
            $this->processConversionId,
            $this->lifeTime,
            $this->isLayer,
            $this->quantity,
            $this->calcLca,
            $this->isExtant,
            $newLayerPosition ?? $this->layerPosition,
            $this->layerSize,
            $layerSiblingId,
            $resetAreaRatio
                ? 1
                : $this->layerAreaRatio,
            $this->layerLength,
            $this->layerWidth,
            $this->lifeTimeDelay,
            $this->lifeTimeInfo
        );

        if($copyCacheItems)
            ElcaCacheElementComponent::findByElementComponentId($this->id)->copy($Copy->getId());

        /**
         * Copy attributes
         */
        foreach($this->getAttributes() as $Attr)
            ElcaElementComponentAttribute::create($Copy->getId(), $Attr->getIdent(), $Attr->getNumericValue(), $Attr->getTextValue());

        return $Copy;
    }
    // End copy


    /**
     * Sets the property elementId
     *
     * @param  integer $elementId - associated element id
     * @return void
     */
    public function setElementId($elementId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementId', $elementId))
            return;

        $this->elementId = (int)$elementId;
    }
    // End setElementId


    /**
     * Sets the property processConfigId
     *
     * @param  integer $processConfigId - process config id
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
     * Sets the property quantity
     *
     * @param int|number $quantity - quantity
     * @return void
     */
    public function setQuantity($quantity = 1)
    {
        $this->quantity = $quantity;
    }
    // End setQuantity


    /**
     * Sets the property processConversionId
     *
     * @param  integer $processConversionId - process conversion id
     * @return void
     */
    public function setProcessConversionId($processConversionId)
    {
        if(!$this->getValidator()->assertNotEmpty('processConversionId', $processConversionId))
            return;

        $this->processConversionId = (int)$processConversionId;
    }
    // End setProcessConversionId


    /**
     * Sets the property lifeTime
     *
     * @param  integer $lifeTime - life time
     * @return void
     */
    public function setLifeTime($lifeTime)
    {
        if(!$this->getValidator()->assertNotEmpty('lifeTime', $lifeTime))
            return;

        $this->lifeTime = (int)$lifeTime;
    }
    // End setLifeTime

    /**
     * Sets the property lifeTimeDelay
     *
     * @param  integer $lifeTimeDelay - life time delay
     * @return void
     */
    public function setLifeTimeDelay($lifeTimeDelay = 0)
    {
        $this->lifeTimeDelay = (int)$lifeTimeDelay;
    }
    // End setLifeTimeDelay

    /**
     * @param string $lifeTimeInfo
     */
    public function setLifeTimeInfo($lifeTimeInfo)
    {
        $this->lifeTimeInfo = $lifeTimeInfo;
    }

    /**
     * Sets the property calcLca
     *
     * @param  boolean $calcLca - indicated if the lca for this component should be calculated
     * @return void
     */
    public function setCalcLca($calcLca = true)
    {
        $this->calcLca = (bool)$calcLca;
    }
    // End setCalcLca


    /**
     * Sets the property isExtant
     *
     * @param boolean $isExtant - indicates if the component is pre-existing in extant buildings
     */
    public function setIsExtant($isExtant = false)
    {
        $this->isExtant = (bool)$isExtant;
    }
    // End setIsExtant


    /**
     * Sets the property isLayer
     *
     * @param  boolean $isLayer - indicates if this component is a layer
     * @return void
     */
    public function setIsLayer($isLayer)
    {
        if(!$this->getValidator()->assertNotEmpty('isLayer', $isLayer))
            return;

        $this->isLayer = (bool)$isLayer;
    }
    // End setIsLayer


    /**
     * Sets the property layerPosition
     *
     * @param  integer $layerPosition - position of layer
     * @return void
     */
    public function setLayerPosition($layerPosition = null)
    {
        $this->layerPosition = $layerPosition;
    }
    // End setLayerPosition


    /**
     * Sets the property layerSize
     *
     * @param  number $layerSize - size of layer in [m]
     * @return void
     */
    public function setLayerSize($layerSize = null)
    {
        $this->layerSize = $layerSize;
    }
    // End setLayerSize


    /**
     * Sets the property layerLength
     *
     * @param int|number $layerLength - length of layer in [m]
     * @return void
     */
    public function setLayerLength($layerLength = 1)
    {
        $this->layerLength = $layerLength;
    }
    // End setLayerLength


    /**
     * Sets the property width
     *
     * @param int|number $layerWidth - width of layer in [m]
     * @return void
     */
    public function setLayerWidth($layerWidth = 1)
    {
        $this->layerWidth = $layerWidth;
    }
    // End setLayerWidth


    /**
     * Sets the property layerSiblingId
     *
     * @param  integer $layerSiblingId - references another component as sibling within the same layer
     * @return void
     */
    public function setLayerSiblingId($layerSiblingId = null)
    {
        $this->layerSiblingId = $layerSiblingId;
    }
    // End setLayerSiblingId


    /**
     * Sets the property layerAreaRatio
     *
     * @param int|number $layerAreaRatio - proportion of area (only valid with sibling)
     * @return void
     */
    public function setLayerAreaRatio($layerAreaRatio = 1)
    {
        $this->layerAreaRatio = $layerAreaRatio;
    }
    // End setLayerAreaRatio



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
     * Returns the property elementId
     *
     * @return integer
     */
    public function getElementId()
    {
        return $this->elementId;
    }
    // End getElementId



    /**
     * Returns the associated ElcaElement by property elementId
     *
     * @param  boolean  $force
     * @return ElcaElement
     */
    public function getElement($force = false)
    {
        return ElcaElement::findById($this->elementId, $force);
    }
    // End getElement



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



    /**
     * Returns the property quantity
     *
     * @return number
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    // End getQuantity



    /**
     * Returns the property processConversionId
     *
     * @return integer
     */
    public function getProcessConversionId()
    {
        return $this->processConversionId;
    }
    // End getProcessConversionId



    /**
     * Returns the associated ElcaProcessConversion by property processConversionId
     *
     * @param  boolean  $force
     * @return ElcaProcessConversion
     */
    public function getProcessConversion($force = false)
    {
        return ElcaProcessConversion::findById($this->processConversionId, $force);
    }
    // End getProcessConversion



    /**
     * Returns the property lifeTime
     *
     * @return integer
     */
    public function getLifeTime()
    {
        return $this->lifeTime;
    }
    // End getLifeTime



    /**
     * Returns the property lifeTimeDelay
     *
     * @return integer
     */
    public function getLifeTimeDelay()
    {
        return $this->lifeTimeDelay;
    }

    /**
     * @return string
     */
    public function getLifeTimeInfo()
    {
        return $this->lifeTimeInfo;
    }


    /**
     * Returns the property calcLca
     *
     * @return boolean
     */
    public function getCalcLca()
    {
        return $this->calcLca;
    }
    // End getCalcLca


    /**
     * Returns the property isExtant
     *
     * @return boolean
     */
    public function isExtant()
    {
        return $this->isExtant;
    }
    // End isExtant


    /**
     * Returns the property isLayer
     *
     * @return boolean
     */
    public function isLayer()
    {
        return $this->isLayer;
    }
    // End isLayer



    /**
     * Returns the property layerPosition
     *
     * @return integer
     */
    public function getLayerPosition()
    {
        return $this->layerPosition;
    }
    // End getLayerPosition



    /**
     * Returns the property layerSize
     *
     * @return number
     */
    public function getLayerSize()
    {
        return $this->layerSize;
    }
    // End getLayerSize



    /**
     * Returns the property layerLength
     *
     * @return number
     */
    public function getLayerLength()
    {
        return $this->layerLength;
    }
    // End getLayerLength



    /**
     * Returns the property layerWidth
     *
     * @return number
     */
    public function getLayerWidth()
    {
        return $this->layerWidth;
    }
    // End getLayerWidth




    /**
     * Returns the property layerSiblingId
     *
     * @return integer
     */
    public function getLayerSiblingId()
    {
        return $this->layerSiblingId;
    }
    // End getLayerSiblingId



    /**
     * Returns true if the property layerSiblingId is set
     *
     * @return boolean
     */
    public function hasLayerSibling()
    {
        return (bool)$this->layerSiblingId;
    }
    // End hasLayerSibling



    /**
     * Returns the associated ElcaElementComponent by property layerSiblingId
     *
     * @param  boolean  $force
     * @return ElcaElementComponent
     */
    public function getLayerSibling($force = false)
    {
        return ElcaElementComponent::findById($this->layerSiblingId, $force);
    }
    // End getLayerSibling



    /**
     * Returns the property layerAreaRatio
     *
     * @return number
     */
    public function getLayerAreaRatio()
    {
        return $this->layerAreaRatio;
    }
    // End getLayerAreaRatio



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
     * @return null|number
     */
    public function getLayerArea()
    {
        if (!$this->isLayer())
            return null;

        return $this->layerLength * $this->layerWidth * $this->layerAreaRatio;
    }


    /**
     * Returns associated element attributes
     *
     * @param bool $force
     * @return ElcaElementComponentAttributeSet
     */
    public function getAttributes($force = false)
    {
        return ElcaElementComponentAttributeSet::find(['element_component_id' => $this->getId()], null, null, null, $force);
    }
    // End getAttributes

    /**
     * Returns a associated element attribute
     *
     * @param string $ident
     * @param bool $force
     * @return ElcaElementComponentAttribute
     */
    public function getAttribute($ident, $force = false)
    {
        return ElcaElementComponentAttribute::findByElementComponentIdAndIdent($this->getId(), $ident, $force);
    }
    // End getAttribute


    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - elementComponentId
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
                           SET element_id          = :elementId
                             , process_config_id   = :processConfigId
                             , quantity            = :quantity
                             , process_conversion_id = :processConversionId
                             , life_time           = :lifeTime
                             , life_time_delay     = :lifeTimeDelay
                             , life_time_info      = :lifeTimeInfo
                             , calc_lca            = :calcLca
                             , is_extant           = :isExtant
                             , is_layer            = :isLayer
                             , layer_position      = :layerPosition
                             , layer_size          = :layerSize
                             , layer_sibling_id    = :layerSiblingId
                             , layer_area_ratio    = :layerAreaRatio
                             , created             = :created
                             , modified            = :modified
                             , layer_length        = :layerLength
                             , layer_width         = :layerWidth
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('id'                 => $this->id,
                                        'elementId'          => $this->elementId,
                                        'processConfigId'    => $this->processConfigId,
                                        'quantity'           => $this->quantity,
                                        'processConversionId' => $this->processConversionId,
                                        'lifeTime'           => $this->lifeTime,
                                        'lifeTimeDelay'      => $this->lifeTimeDelay,
                                        'lifeTimeInfo'       => $this->lifeTimeInfo,
                                        'calcLca'            => $this->calcLca,
                                        'isExtant'           => $this->isExtant,
                                        'isLayer'            => $this->isLayer,
                                        'layerPosition'      => $this->layerPosition,
                                        'layerSize'          => $this->layerSize,
                                        'layerSiblingId'     => $this->layerSiblingId,
                                        'layerAreaRatio'     => $this->layerAreaRatio,
                                        'created'            => $this->created,
                                        'modified'           => $this->modified,
                                        'layerLength'        => $this->layerLength,
                                        'layerWidth'         => $this->layerWidth)
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
        $this->id                  = $this->getNextSequenceValue();
        $this->created             = self::getCurrentTime();
        $this->modified            = null;

        $sql = sprintf("INSERT INTO %s (id, element_id, process_config_id, quantity, process_conversion_id, life_time, life_time_delay, life_time_info, calc_lca, is_extant, is_layer, layer_position, layer_size, layer_sibling_id, layer_area_ratio, created, modified, layer_length, layer_width)
                               VALUES  (:id, :elementId, :processConfigId, :quantity, :processConversionId, :lifeTime, :lifeTimeDelay, :lifeTimeInfo, :calcLca, :isExtant, :isLayer, :layerPosition, :layerSize, :layerSiblingId, :layerAreaRatio, :created, :modified, :layerLength, :layerWidth)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('id'                 => $this->id,
                                        'elementId'          => $this->elementId,
                                        'processConfigId'    => $this->processConfigId,
                                        'quantity'           => $this->quantity,
                                        'processConversionId' => $this->processConversionId,
                                        'lifeTime'           => $this->lifeTime,
                                        'lifeTimeDelay'      => $this->lifeTimeDelay,
                                        'lifeTimeInfo'       => $this->lifeTimeInfo,
                                        'calcLca'            => $this->calcLca,
                                        'isExtant'           => $this->isExtant,
                                        'isLayer'            => $this->isLayer,
                                        'layerPosition'      => $this->layerPosition,
                                        'layerSize'          => $this->layerSize,
                                        'layerSiblingId'     => $this->layerSiblingId,
                                        'layerAreaRatio'     => $this->layerAreaRatio,
                                        'created'            => $this->created,
                                        'modified'           => $this->modified,
                                        'layerLength'        => $this->layerLength,
                                        'layerWidth'         => $this->layerWidth)
                                  );
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
        $this->id                  = (int)$DO->id;
        $this->elementId           = (int)$DO->element_id;
        $this->processConfigId     = (int)$DO->process_config_id;
        $this->quantity            = $DO->quantity;
        $this->processConversionId = (int)$DO->process_conversion_id;
        $this->lifeTime            = (int)$DO->life_time;
        $this->lifeTimeDelay       = (int)$DO->life_time_delay;
        $this->lifeTimeInfo        = $DO->life_time_info;
        $this->calcLca             = (bool)$DO->calc_lca;
        $this->isExtant            = (bool)$DO->is_extant;
        $this->isLayer             = (bool)$DO->is_layer;
        $this->layerPosition       = $DO->layer_position;
        $this->layerSize           = $DO->layer_size;
        $this->layerSiblingId      = $DO->layer_sibling_id;
        $this->layerAreaRatio      = $DO->layer_area_ratio;
        $this->created             = $DO->created;
        $this->modified            = $DO->modified;
        $this->layerLength         = $DO->layer_length;
        $this->layerWidth          = $DO->layer_width;

        /**
         * Set extensions
         */
        if(isset($DO->element_name)) $this->elementName = $DO->element_name;
    }
    // End initByDataObject
}
// End class ElcaElementComponent