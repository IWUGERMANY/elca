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
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\Group;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Elca\Elca;
use Exception;
use PDO;

/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.elements';

    /**
     * elementId
     */
    private $id;

    /**
     * element type node id
     */
    private $elementTypeNodeId;

    /**
     * element name
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * indicates a reference element
     */
    private $isReference;

    private $isPublic;

    /**
     * access group id
     */
    private $accessGroupId;

    /**
     * project variant id
     */
    private $projectVariantId;

    /**
     * quantity
     */
    private $quantity;

    /**
     * reference unit of measure
     */
    private $refUnit;

    /**
     * is a copy of element with id
     */
    private $copyOfElementId;

    /**
     * owner id of this element
     */
    private $ownerId;

    /**
     * indicates a composite element
     */
    private $isComposite;

    /**
     * uuid
     */
    private $uuid;

    /**
     * creation time
     */
    private $created;

    /**
     * modification time
     */
    private $modified;

    private  $processDbIds;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = ['id'                => PDO::PARAM_INT,
                                        'elementTypeNodeId' => PDO::PARAM_INT,
                                        'name'              => PDO::PARAM_STR,
                                        'description'       => PDO::PARAM_STR,
                                        'isReference'       => PDO::PARAM_BOOL,
                                        'isPublic'          => PDO::PARAM_BOOL,
                                        'accessGroupId'     => PDO::PARAM_INT,
                                        'projectVariantId'  => PDO::PARAM_INT,
                                        'quantity'          => PDO::PARAM_STR,
                                        'refUnit'           => PDO::PARAM_STR,
                                        'copyOfElementId'   => PDO::PARAM_INT,
                                        'ownerId'           => PDO::PARAM_INT,
                                        'isComposite'        => PDO::PARAM_BOOL,
                                        'uuid'              => PDO::PARAM_STR,
                                        'created'           => PDO::PARAM_STR,
                                        'modified'          => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];


    // public


    /**
     * Creates the object
     *
     * @param  integer $elementTypeNodeId - element type node id
     * @param  string  $name              - element name
     * @param  string  $description       - description
     * @param  boolean $isPublic       - indicates a public element
     * @param  integer $accessGroupId     - access group id
     * @param  integer $projectVariantId  - project variant id
     * @param  number $quantity          - quantity
     * @param  string  $refUnit           - reference unit of measure
     * @param  integer $copyOfElementId   - is a copy of element with id
     * @param  integer $ownerId           - owner id of this element
     * @param  string  $uuid
     * @return ElcaElement
     */
    public static function create($elementTypeNodeId, $name, $description = null, $isPublic = false, $accessGroupId = null, $projectVariantId = null, $quantity = null, $refUnit = null, $copyOfElementId = null, $ownerId = null, $uuid = null, $isReference = false)
    {
        $elcaElement = new ElcaElement();
        $elcaElement->setElementTypeNodeId($elementTypeNodeId);
        $elcaElement->setName($name);
        $elcaElement->setDescription($description);
        $elcaElement->setIsPublic($isPublic);
        $elcaElement->setIsReference($isReference);
        $elcaElement->setAccessGroupId($accessGroupId);
        $elcaElement->setProjectVariantId($projectVariantId);
        $elcaElement->setQuantity($quantity);
        $elcaElement->setRefUnit($refUnit);
        $elcaElement->setCopyOfElementId($copyOfElementId);
        $elcaElement->setOwnerId($ownerId);

        if($uuid)
            $elcaElement->setUuid($uuid);

        /**
         * Composite elements are always at composite level in type hierachy
         */
        $elcaElement->setIsComposite(ElcaElementType::findByNodeId($elementTypeNodeId)->isCompositeLevel());

        if($elcaElement->getValidator()->isValid())
            $elcaElement->insert();

        return $elcaElement;
    }
    // End create



    /**
     * Inits the object by DataObject
     *
     * @param  \stdClass $DO - Data object
     * @return this
     */
    public static function initByDO(\stdClass $DO = null)
    {
        $ElcaElement = new ElcaElement();
        $ElcaElement->initByDataObject($DO);
        $ElcaElement->setInitialized();
        return $ElcaElement;
    }
    // End initByDO



    /**
     * Inits a `ElcaElement' by its primary key
     *
     * @param  integer  $id    - elementId
     * @param  boolean  $force - Bypass caching
     * @return ElcaElement
     */
    public static function findById($id, $force = false)
    {
        if(!is_numeric($id))
            return new ElcaElement();

        $sql = sprintf("SELECT id
                             , element_type_node_id
                             , name
                             , description
                             , is_reference
                             , is_public
                             , access_group_id
                             , project_variant_id
                             , quantity
                             , ref_unit
                             , copy_of_element_id
                             , owner_id
                             , is_composite
                             , uuid
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById



    /**
     * Inits a `ElcaElement' by its uuid
     *
     * @param  integer  $uuid
     * @param  boolean  $force - Bypass caching
     * @return ElcaElement
     */
    public static function findByUuid($uuid, $force = false)
    {
        if(!$uuid)
            return new ElcaElement();

        $sql = sprintf("SELECT id
                             , element_type_node_id
                             , name
                             , description
                             , is_reference
                             , is_public
                             , access_group_id
                             , project_variant_id
                             , quantity
                             , ref_unit
                             , copy_of_element_id
                             , owner_id
                             , is_composite
                             , uuid
                             , created
                             , modified
                          FROM %s
                         WHERE uuid = :uuid"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['uuid' => $uuid], $force);
    }
    // End findByUuid



    /**
     * Returns a new unique name
     *
     * @param  ElcaElement
     * @return string
     */
    public static function findNewUniqueName(ElcaElement $Element)
    {
        $initValues = ['elementTypeNodeId' => $Element->getElementTypeNodeId(),
                            'name' => '% ' . t('Kopie von') . ' ' . $Element->getName()];

        if($projectVariantId = $Element->getProjectVariantId())
            $initValues['projectVariantId'] = $projectVariantId;

        $sql = sprintf('SELECT name
                          FROM %s
                         WHERE element_type_node_id = :elementTypeNodeId
                           AND %s
                           AND name ILIKE :name
                      ORDER BY name DESC
                         LIMIT 1'
                       , self::TABLE_NAME
                       , $Element->getProjectVariantId()? 'project_variant_id = :projectVariantId' : 'project_variant_id IS NULL'
                       );

        $results = [];
        parent::executeSql(get_class(), $sql, $initValues, $results);

        $name = '';
        if(count($results))
        {
            $DO = array_shift($results);
            if(preg_match('/^(\d+)\. ' . t('Kopie von') . ' /u', $DO->name, $matches))
            {
                $counter = (int)$matches[1];
                $name = ++$counter . '. ' . t('Kopie von') . ' ' . $Element->getName();
            }
        }

        if(!$name)
            $name = '1. ' . t('Kopie von') . ' ' . $Element->getName();

        return $name;
    }
    // End findNewUniqueName


    /**
     * Creates a deep copy from this element
     *
     * @param  int  $ownerId
     * @param  int  $projectVariantId   new project Variant id
     * @param  int  $accessGroupId
     * @param  bool $copyName           -> namen 1:1 kopieren
     * @param  bool $copyCacheItems     - copies all cache items
     * @param  int  $compositeElementId - sets a new composite elementId (need for deep cloning composite elements)
     * @param null  $compositePosition
     * @throws Exception
     * @return ElcaElement - the new element copy
     */
    public function copy($ownerId, $projectVariantId = null, $accessGroupId = null, $copyName = false, $copyCacheItems = false, $compositeElementId = null, $compositePosition = null)
    {
        if(!$this->isInitialized())
            return new ElcaElement();

        if(!$ownerId)
            $ownerId = UserStore::getInstance()->getUser()->getId();

        try
        {
            $this->Dbh->begin();

            /**
             * Quantity depends on...
             *
             * the copy will be a project element
             */
            if($projectVariantId)
            {
                // and is copied from template
                // and is within composite elements
                // and is transparent
                if(!$this->projectVariantId &&
                   $compositeElementId      &&
                   $this->getElementTypeNode()->isOpaque() === false)
                    $quantity = 0;
                else
                    $quantity = $this->quantity;
            }
            // or a template elements,
            else
                $quantity = 1;

            $Copy = self::create($this->elementTypeNodeId,
                                 $copyName? $this->name : self::findNewUniqueName($this),
                                 $this->description,
                                 false, // copy is never a reference element
                                 $accessGroupId? $accessGroupId : $this->accessGroupId,
                                 $projectVariantId,
                                 $quantity,
                                 $this->refUnit,
                                 $projectVariantId? $this->id : null, // copyOfElementId only on project elements
                                 $ownerId);

            /**
             * Copy components
             */
            if($Copy->isInitialized())
            {
                if($copyCacheItems)
                {
                    /**
                     * Retrieve the cached composite element itemId for the given compositeElementId
                     * Then copy the cached element and assign this itemId
                     */
                    $compositeItemId = null;

                    if($compositeElementId)
                        $compositeItemId = ElcaCacheElement::findByElementId($compositeElementId)->getItemId();

                    ElcaCacheElement::findByElementId($this->id)->copy($Copy->getId(), $compositeItemId);
                }

                if($this->isComposite())
                {
                    /**
                     * Always clone sub elements.
                     */
                    foreach($this->getCompositeElements(null, true) as $Assignment)
                    {
                        $SubElement = $Assignment->getElement();
                        $SubElement->copy($ownerId, $projectVariantId, $accessGroupId, true, $copyCacheItems, $Copy->getId(), $Assignment->getPosition());
                    }
                }
                else
                {
                    /**
                     * Assign element to compositeElementId
                     */
                    if($compositeElementId)
                    {
                        $position = $compositePosition? $compositePosition : ElcaCompositeElement::getMaxCompositePosition($compositeElementId) + 1;
                        ElcaCompositeElement::create($compositeElementId, $position, $Copy->getId());
                    }

                    /**
                     * Clone all components
                     */
                    $Components = ElcaElementComponentSet::findByElementId($this->getId(), [], ['layer_position' => 'ASC', 'id' => 'ASC']);

                    $siblings = [];
                    foreach($Components as $Component)
                    {
                        if($Component->hasLayerSibling())
                        {
                            if(!isset($siblings[$Component->getId()]))
                            {
                                $CopyComponent = $Component->copy($Copy->getId(), null, $copyCacheItems);
                                $CopySibling = $Component->getLayerSibling()->copy($Copy->getId(), $CopyComponent->getId(), $copyCacheItems);

                                $CopyComponent->setLayerSiblingId($CopySibling->getId());
                                $CopyComponent->update();

                                $siblings[$Component->getLayerSiblingId()] = true;
                            }
                        }
                        else
                            $Component->copy($Copy->getId(), null, $copyCacheItems);
                    }
                }

                // if this a template element, copy constrDesigns and catalogs
                if($Copy->isTemplate())
                {
                    foreach($this->getConstrCatalogs() as $ConstrCatalog)
                        $Copy->assignConstrCatalogId($ConstrCatalog->getId());

                    foreach($this->getConstrDesigns() as $ConstrDesign)
                        $Copy->assignConstrDesignId($ConstrDesign->getId());
                }

                /**
                 * Copy element attributes
                 */
                foreach($this->getAttributes() as $Attr)
                    ElcaElementAttribute::create($Copy->getId(), $Attr->getIdent(), $Attr->getCaption(), $Attr->getNumericValue(), $Attr->getTextValue());
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
     * Sets the property elementTypeNodeId
     *
     * @param  integer $elementTypeNodeId - element type node id
     * @return void
     */
    public function setElementTypeNodeId($elementTypeNodeId)
    {
        if(!$this->getValidator()->assertNotEmpty('elementTypeNodeId', $elementTypeNodeId))
            return;

        $this->elementTypeNodeId = (int)$elementTypeNodeId;
    }
    // End setElementTypeNodeId


    /**
     * Sets the property name
     *
     * @param  string $name - element name
     * @return void
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
     * Sets the property description
     *
     * @param  string $description - description
     * @return void
     */
    public function setDescription($description = null)
    {
        $this->description = $description;
    }
    // End setDescription

    /**
     * @param string $text
     */
    public function addDescriptionLine(string $text)
    {
        if ($this->description) {
            $this->description .= \PHP_EOL;
        }

        $this->description .= $text . \PHP_EOL;
    }

    /**
     * Sets the property isReference
     *
     * @param  boolean $isReference - indicates a reference element
     * @return void
     */
    public function setIsReference($isReference = false)
    {
        $this->isReference = (bool)$isReference;
    }

    /**
     * Sets the property isPublic
     *
     * @param  boolean $isPublic - indicates a reference element
     * @return void
     */
    public function setIsPublic($isPublic = false)
    {
        $this->isPublic = (bool)$isPublic;
    }

    /**
     * Sets the property accessGroupId
     *
     * @param  integer $accessGroupId - access group id
     * @return void
     */
    public function setAccessGroupId($accessGroupId = null)
    {
        $this->accessGroupId = $accessGroupId;
    }
    // End setAccessGroupId


    /**
     * Sets the property projectVariantId
     *
     * @param  integer $projectVariantId - project variant id
     * @return void
     */
    public function setProjectVariantId($projectVariantId = null)
    {
        $this->projectVariantId = $projectVariantId;
    }
    // End setProjectVariantId


    /**
     * Sets the property quantity
     *
     * @param  number $quantity - quantity
     * @return void
     */
    public function setQuantity($quantity = null)
    {
        $this->quantity = $quantity;
    }
    // End setQuantity


    /**
     * Sets the property refUnit
     *
     * @param  string $refUnit - reference unit of measure
     * @return void
     */
    public function setRefUnit($refUnit = null)
    {
        if(!$this->getValidator()->assertMaxLength('refUnit', 10, $refUnit))
            return;

        $this->refUnit = $refUnit;
    }
    // End setRefUnit


    /**
     * Sets the property copyOfElementId
     *
     * @param  integer $copyOfElementId - is a copy of element with id
     * @return void
     */
    public function setCopyOfElementId($copyOfElementId = null)
    {
        $this->copyOfElementId = $copyOfElementId;
    }
    // End setCopyOfElementId


    /**
     * Sets the property ownerId
     *
     * @param  integer $ownerId - owner id of this element
     * @return void
     */
    public function setOwnerId($ownerId = null)
    {
        $this->ownerId = $ownerId;
    }
    // End setOwnerId


    /**
     * Sets the property uuid
     *
     * @param  string $uuid - uuid
     * @return void
     */
    public function setUuid($uuid)
    {
        if(!$this->getValidator()->assertNotEmpty('uuid', $uuid))
            return;

        $this->uuid = (string)$uuid;
    }
    // End setUuid


    /**
     * Sets the property isComposite
     *
     * @param  boolean $isComposite - indicates a composite element
     * @return void
     */
    public function setIsComposite($isComposite = false)
    {
        $this->isComposite = (bool)$isComposite;
    }
    // End setIsComposite



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
     * Returns the property elementTypeNodeId
     *
     * @return integer
     */
    public function getElementTypeNodeId()
    {
        return $this->elementTypeNodeId;
    }
    // End getElementTypeNodeId



    /**
     * Returns the associated ElcaElementType by property elementTypeNodeId
     *
     * @param  boolean  $force
     * @return ElcaElementType
     */
    public function getElementTypeNode($force = false)
    {
        return ElcaElementType::findByNodeId($this->elementTypeNodeId, $force);
    }
    // End getElementTypeNode



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
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getDescription



    /**
     * Returns the property isReference
     *
     * @return boolean
     */
    public function isReference()
    {
        return $this->isReference;
    }

    /**
     * Returns the property isReference
     *
     * @return boolean
     */
    public function isPublic()
    {
        return $this->isPublic;
    }

    /**
     * Returns the property accessGroupId
     *
     * @return integer
     */
    public function getAccessGroupId()
    {
        return $this->accessGroupId;
    }
    // End getAccessGroupId



    /**
     * Returns the associated Group by property accessGroupId
     *
     * @param  boolean  $force
     * @return Group
     */
    public function getAccessGroup($force = false)
    {
        return Group::findById($this->accessGroupId, $force);
    }
    // End getAccessGroup



    /**
     * Returns the property projectVariantId
     *
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId



    /**
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  boolean  $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }
    // End getProjectVariant



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
     * Returns the property refUnit
     *
     * @return string
     */
    public function getRefUnit()
    {
        return $this->refUnit;
    }
    // End getRefUnit



    /**
     * Returns the property copyOfElementId
     *
     * @return integer
     */
    public function getCopyOfElementId()
    {
        return $this->copyOfElementId;
    }
    // End getCopyOfElementId



    /**
     * Returns the associated ElcaElement by property copyOfElementId
     *
     * @param  boolean  $force
     * @return ElcaElement
     */
    public function getCopyOfElement($force = false)
    {
        return ElcaElement::findById($this->copyOfElementId, $force);
    }
    // End getCopyOfElement



    /**
     * Returns the property ownerId
     *
     * @return integer
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }
    // End getOwnerId



    /**
     * Returns the associated User by property ownerId
     *
     * @param  boolean  $force
     * @return User
     */
    public function getOwner($force = false)
    {
        return User::findById($this->ownerId, $force);
    }
    // End getOwner



    /**
     * Returns the property isComposite
     *
     * @return boolean
     */
    public function isComposite()
    {
        return (bool)$this->isComposite;
    }
    // End isComposite


    /**
     * Returns true if this element is assigned at least to one composite element
     *
     * @param bool $force
     * @return boolean
     */
    public function hasCompositeElement($force = false)
    {
        if(!$this->isInitialized())
            return false;

        return ElcaCompositeElementSet::dbCount(['element_id' => $this->id], $force) > 0;
    }
    // End hasCompositeElement


    /**
     * Returns the composite element, if this element has one
     *
     * @param bool $force
     * @return ElcaElement
     */
    public function getCompositeElement($force = false)
    {
        if(!$this->isInitialized() || !$this->hasCompositeElement())
            return null;

        $Assignments = $this->getCompositeElements();
        return $Assignments[0]->getCompositeElement();
    }
    // End getCompositeElement


    /**
     * Returns the count of assigned composite elements
     *
     * @param bool $force
     * @return int
     */
    public function countCompositeElement($force = false)
    {
        if(!$this->isInitialized())
            return null;

        return ElcaCompositeElementSet::dbCount(['element_id' => $this->id], $force);
    }
    // End countCompositeElement



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
     * Returns true if the element is a template (not associated with a
     * project variant)
     *
     * @return boolean
     */
    public function isTemplate()
    {
        return $this->isInitialized() && is_null($this->projectVariantId);
    }
    // End isTemplate

    public function getProcessDbIds(array $orderBy = null)
    {
        if (null === $this->processDbIds) {
            $this->processDbIds = ElcaProcessDbSet::findElementCompatibles($this, $orderBy)->getArrayBy('id');
        }

        return $this->processDbIds;
    }

    /**
     * Returns associated element components
     *
     * @return ElcaElementComponentSet
     */
    public function getComponents()
    {
        return ElcaElementComponentSet::findByElementId($this->getId());
    }
    // End getComponents

    public function hasLayers() : bool
    {
        return ElcaElementComponentSet::findByElementId($this->getId(), ['is_layer' => true], null, 1)->count() > 0;
    }

    /**
     * Returns assigned elements if this is a composite element or
     * assigned composite elements if this is a assigned element
     *
     * @param array $orderBy
     * @param bool  $force
     * @return ElcaCompositeElementSet|ElcaCompositeElement[]
     */
    public function getCompositeElements(array $orderBy = null, $force = false)
    {
        if($this->isComposite())
            $Assignments = ElcaCompositeElementSet::findByCompositeElementId($this->getId(), [], $orderBy, null, null, $force);
        else
            $Assignments = ElcaCompositeElementSet::findByElementId($this->getId(), [], $orderBy, null, null, $force);

        return $Assignments;
    }
    // End getCompositeElements


    /**
     * Returns true if the elements element type is opque
     */
    public function isOpaque()
    {
        return $this->getElementTypeNode()->isOpaque();
    }
    // End isOpaque


    /**
     * Returns the current area of opaque elements if this is a composite element
     *
     * @param  boolean $force
     * @return float
     */
    public function getOpaqueArea($force = false)
    {
        /**
         * This works only on composite elements with refUnit m2
         */
        if($this->refUnit != Elca::UNIT_M2 || !$this->isComposite())
            return null;

        /**
         * In template context, opaque area is always the quantity
         */
        if(!$this->projectVariantId)
            return $this->getQuantity();

        /**
         * Opaque area is total area minus the sum of non-opaque elements
         * but never negative
         */
        return max(0, ($this->getQuantity() - $this->getNonOpaqueArea($force)));
    }
    // End getOpaqueArea

    /**
     * @param bool $force
     * @return int|float
     */
    public function getNonOpaqueArea($force = false)
    {
        $nonOpaqueArea = 0;
        foreach($this->getCompositeElements(null, $force) as $assignment)
        {
            $element = $assignment->getElement();

            // sum area of non opaque elements up
            if($element->getElementTypeNode()->isOpaque() === false)
                $nonOpaqueArea += $element->getMaxSurface(true);
        }

        return $nonOpaqueArea;
    }


    /**
     * Returns the surface area of the element.
     * If this element is measured in m2, return quantity.
     * Otherwise get the maximum surface
     *
     * @returns float
     */
    public function getSurface()
    {
        if ($this->refUnit == Elca::UNIT_M2)
            return $this->quantity;

        $surface = 0;

        if ($this->isComposite()) {

            foreach ($this->getCompositeElements() as $Assignment) {
                $Elt = $Assignment->getElement();
                if($Elt->isOpaque() === false)
                    continue;

                $surface = max($surface, $Elt->getMaxSurface());
            }

        } else {
            $surface = $this->getMaxSurface();
        }

        return $surface * $this->quantity;
    }
    // End getSurface


    /**
     * Assigns a constrDesignId
     *
     * @param  integer  $constrDesignId - construction design
     * @return ElcaElementConstrDesign
     */
    public function assignConstrDesignId($constrDesignId)
    {
        $ElementConstrDesign = ElcaElementConstrDesign::findByPk($this->id, $constrDesignId);

        if(!$ElementConstrDesign->isInitialized())
            $ElementConstrDesign = ElcaElementConstrDesign::create($this->id, $constrDesignId);

        return $ElementConstrDesign;
    }
    // End assignConstrDesignId



    /**
     * Unassigns a constrDesignId
     *
     * @param  integer  $constrDesignId - construction design
     * @return ElcaElementConstrDesign
     */
    public function unassignConstrDesignId($constrDesignId)
    {
        $ElementConstrDesign = ElcaElementConstrDesign::findByPk($this->id, $constrDesignId);

        if($ElementConstrDesign->isInitialized())
            $ElementConstrDesign->delete();
    }
    // End unassignConstrDesignId


    /**
     * Returns a list of associated constr designs
     *
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @internal param $ -
     * @return ElcaConstructionDesignSet
     */
    public function getConstrDesigns(array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return ElcaConstrDesignSet::findByElementId($this->getId(), $orderBy, $limit, $offset, $force);
    }
    // End getConstrDesigns



    /**
     * Assigns a constrCatalogId
     *
     * @param  integer  $constrCatalogId - construction design
     * @return ElcaElementConstrCatalog
     */
    public function assignConstrCatalogId($constrCatalogId)
    {
        $ElementConstrCatalog = ElcaElementConstrCatalog::findByPk($this->id, $constrCatalogId);

        if(!$ElementConstrCatalog->isInitialized())
            $ElementConstrCatalog = ElcaElementConstrCatalog::create($this->id, $constrCatalogId);

        return $ElementConstrCatalog;
    }
    // End assignConstrCatalogId



    /**
     * Unassigns a constrCatalogId
     *
     * @param  integer  $constrCatalogId - construction design
     * @return ElcaElementConstrCatalog
     */
    public function unassignConstrCatalogId($constrCatalogId)
    {
        $ElementConstrCatalog = ElcaElementConstrCatalog::findByPk($this->id, $constrCatalogId);

        if($ElementConstrCatalog->isInitialized())
            $ElementConstrCatalog->delete();
    }
    // End unassignConstrCatalogId


    /**
     * Returns a list of associated constr designs
     *
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     * @param bool  $force
     * @internal param $ -
     * @return ElcaConstructionCatalogSet
     */
    public function getConstrCatalogs(array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return ElcaConstrCatalogSet::findByElementId($this->getId(), $orderBy, $limit, $offset, $force);
    }
    // End getConstrCatalogs


    /**
     * Returns associated element attributes
     *
     * @param bool $force
     * @return ElcaElementAttributeSet
     */
    public function getAttributes($force = false)
    {
        return ElcaElementAttributeSet::find(['element_id' => $this->getId()], null, null, null, $force);
    }
    // End getAttributes

    /**
     * Returns a associated element attribute
     *
     * @param string $ident
     * @param bool $force
     * @return ElcaElementAttribute
     */
    public function getAttribute($ident, $force = false)
    {
        return ElcaElementAttribute::findByElementIdAndIdent($this->getId(), $ident, $force);
    }
    // End getAttribute

    /**
     * Returns the maximum area over all geometric components
     *
     * @param bool $factorInQuantity
     * @return float
     */
    public function getMaxSurface($factorInQuantity = false)
    {
        $sql = sprintf("SELECT coalesce(max(layer_width * layer_length), 0) AS surface
                          FROM %s
                         WHERE element_id = :elementId
                           AND is_layer = true"
                       , ElcaElementComponent::TABLE_NAME
                       );

        $returnValues = [];

        if(!self::executeSql(get_class(), $sql, ['elementId' => $this->id], $returnValues))
            return null;

        $DO = array_shift($returnValues);
        return $factorInQuantity? $DO->surface * $this->quantity : $DO->surface;
    }
    // End getSurface


    /**
     * Determines the isExtant status of this element
     *
     * Returns
     *     false, if at least one element component is not marked extant
     *     true, if all element components are marked extant
     *     null, if this element is either a composite element or has not components at all (yet)
     *
     * @return boolean|null
     */
    public function isExtant()
    {
        if ($this->isComposite())
            return null;

        $Components = ElcaElementComponentSet::findByElementId($this->id);
        if (!$Components->count())
            return null;

        foreach ($Components as $Component) {
            if (!$Component->isExtant())
                return false;
        }

        return true;
    }
    // End isExtant

    /**
     * Determines if at least one component of this composite element has isExtant set to true
     *
     * @return boolean|null
     */
    public function hasExtants()
    {
        if ($this->isComposite())
            return null;

        $Components = ElcaElementComponentSet::findByElementId($this->id);
        if (!$Components->count())
            return null;

        foreach ($Components as $Component) {
            if ($Component->isExtant())
                return true;
        }

        return false;
    }
    // End hasExtant

    /**
     * Sets the isExtant status of all assigned components
     * Returns true, if a component was updated
     *
     * @param boolean $isExtant
     * @return bool
     */
    public function setIsExtant($isExtant = true)
    {
        if ($this->isComposite())
            return false;

        $updated = false;

        /** @var ElcaElementComponent $Component */
        foreach (ElcaElementComponentSet::findByElementId($this->id) as $Component) {
            if ($Component->isExtant() === $isExtant)
                continue;

            $Component->setIsExtant($isExtant);

            /**
             * Reset lifeTime delay to 0 if not extant
             */
            if (!$isExtant) {
                $Component->setLifeTimeDelay(0);
            }

            $Component->update();

            $updated = true;
        }

        return $updated;
    }
    // End setIsExtant


    /**
     * Reindexes the layers of this element
     */
    public function reindexLayers()
    {
        if($this->isComposite())
            return;

        $Layers = ElcaElementComponentSet::findLayers($this->getId());

        $siblings = [];
        $pos = 1;
        foreach($Layers as $Layer)
        {
            if($Layer->getLayerPosition() != $pos) {
                $Layer->setLayerPosition($pos);
                $Layer->update();
            }

            if(($siblingId = $Layer->getLayerSiblingId()) && !isset($siblings[$Layer->getId()])) {
                $siblings[$siblingId] = $pos;
            }
            else {
                $pos++;
            }
        }
    }
    // End reindexLayers



    /**
     * Reindexes the elements of this composite element
     */
    public function reindexCompositeElements()
    {
        if(!$this->isComposite())
            return;

        $Assignments = $this->getCompositeElements();

        $pos = 1;
        /** @var ElcaCompositeElement $Assignment */
        foreach($Assignments as $Assignment)
        {
            if($Assignment->getPosition() != $pos) {
                $Assignment->setPosition($pos);
                $Assignment->update();
            }

            $pos++;
        }
    }
    // End reindexCompositeElements



    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - elementId
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
                           SET element_type_node_id = :elementTypeNodeId
                             , name                 = :name
                             , description          = :description
                             , is_reference         = :isReference
                             , is_public            = :isPublic
                             , access_group_id      = :accessGroupId
                             , project_variant_id   = :projectVariantId
                             , quantity             = :quantity
                             , ref_unit             = :refUnit
                             , copy_of_element_id   = :copyOfElementId
                             , owner_id             = :ownerId
                             , is_composite         = :isComposite
                             , uuid                 = :uuid
                             , created              = :created
                             , modified             = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['id'               => $this->id,
                                        'elementTypeNodeId' => $this->elementTypeNodeId,
                                        'name'             => $this->name,
                                        'description'      => $this->description,
                                        'isReference'      => $this->isReference,
                                        'isPublic'      => $this->isPublic,
                                        'accessGroupId'    => $this->accessGroupId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'quantity'         => $this->quantity,
                                        'refUnit'          => $this->refUnit,
                                        'copyOfElementId'  => $this->copyOfElementId,
                                        'ownerId'          => $this->ownerId,
                                        'isComposite'      => $this->isComposite,
                                        'uuid'             => $this->uuid,
                                        'created'          => $this->created,
                                        'modified'         => $this->modified]
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
                                  ['id' => $this->id]);
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

        $primaryKey = [];

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

    /**
     * @return bool
     */
    public function geometryAndRefUnitMatches()
    {
        if ($this->isComposite() ||
            $this->getRefUnit() !== Elca::UNIT_M2 ||
            false === $this->hasLayers() ||
            false === $this->getElementTypeNode()->isConstructional()
        ) {
            return true;
        }

        return FloatCalc::cmp($this->getMaxSurface(), 1);
    }


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {
        $this->id                = $this->getNextSequenceValue();
        $this->created           = self::getCurrentTime();
        $this->modified          = null;

        if(!$this->uuid)
            $this->uuid = $this->queryExpression('uuid_generate_v4()');

        $sql = sprintf("INSERT INTO %s (id, element_type_node_id, name, description, is_reference, is_public, access_group_id, project_variant_id, quantity, ref_unit, copy_of_element_id, owner_id, is_composite, uuid, created, modified)
                               VALUES  (:id, :elementTypeNodeId, :name, :description, :isReference, :isPublic, :accessGroupId, :projectVariantId, :quantity, :refUnit, :copyOfElementId, :ownerId, :isComposite, :uuid, :created, :modified)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['id'               => $this->id,
                                        'elementTypeNodeId' => $this->elementTypeNodeId,
                                        'name'             => $this->name,
                                        'description'      => $this->description,
                                        'isReference'      => $this->isReference,
                                        'isPublic'      => $this->isPublic,
                                        'accessGroupId'    => $this->accessGroupId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'quantity'         => $this->quantity,
                                        'refUnit'          => $this->refUnit,
                                        'copyOfElementId'  => $this->copyOfElementId,
                                        'ownerId'          => $this->ownerId,
                                        'isComposite'      => $this->isComposite,
                                        'uuid'             => $this->uuid,
                                        'created'          => $this->created,
                                        'modified'         => $this->modified]
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
        $this->id                = (int)$DO->id;
        $this->elementTypeNodeId = (int)$DO->element_type_node_id;
        $this->name              = $DO->name;
        $this->description       = $DO->description;
        $this->isReference       = (bool)$DO->is_reference;
        $this->isPublic          = (bool)$DO->is_public;
        $this->accessGroupId     = $DO->access_group_id;
        $this->projectVariantId  = $DO->project_variant_id;
        $this->quantity          = $DO->quantity;
        $this->refUnit           = $DO->ref_unit;
        $this->copyOfElementId   = $DO->copy_of_element_id;
        $this->ownerId           = $DO->owner_id;
        $this->isComposite       = (bool)$DO->is_composite;
        $this->uuid              = $DO->uuid;
        $this->created           = $DO->created;
        $this->modified          = $DO->modified;

        /**
         * Set extensions
         */
        if (isset($DO->process_db_ids)) {
            $this->processDbIds = '{}' !== $DO->process_db_ids
                ? str_getcsv(trim($DO->process_db_ids, '{}'))
                : [];
        }
    }
    // End initByDataObject
}
// End class ElcaElement