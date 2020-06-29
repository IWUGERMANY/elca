<?php
namespace Elca\Db;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\Group;
use Beibob\Blibs\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * 
 *
 * @package    -
 * @class      ElcaAssistantElement
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2020 BEIBOB Medienfreunde
 */
class ElcaAssistantElement extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.assistant_elements';

    /**
     * assistantElementId
     */
    private $id;

    /**
     * mainElementId
     */
    private $mainElementId;

    /**
     * project variant id
     */
    private $projectVariantId;

    private $assistantIdent;

    /**
     * configuration
     */
    private $config;

    /**
     * indicates a reference element
     */
    private $isReference;

    /**
     * indicates a public element
     */
    private $isPublic;

    /**
     * uuid of the element
     */
    private $uuid;

    /**
     * owner id of this element
     */
    private $ownerId;

    /**
     * access group id
     */
    private $accessGroupId;

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
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'               => PDO::PARAM_INT,
                                        'mainElementId'    => PDO::PARAM_INT,
                                        'projectVariantId' => PDO::PARAM_INT,
                                        'assistantIdent'   => PDO::PARAM_STR,
                                        'config'           => PDO::PARAM_STR,
                                        'isReference'      => PDO::PARAM_BOOL,
                                        'isPublic'         => PDO::PARAM_BOOL,
                                        'uuid'             => PDO::PARAM_STR,
                                        'ownerId'          => PDO::PARAM_INT,
                                        'accessGroupId'    => PDO::PARAM_INT,
                                        'created'          => PDO::PARAM_STR,
                                        'modified'         => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param int  $mainElementId    - mainElementId
     * @param      $assistantIdent
     * @param int  $projectVariantId - project variant id
     * @param null $unserializedConfig
     * @param bool $isReference      - indicates a reference element
     * @param bool $isPublic         - indicates a public element
     * @param int  $ownerId          - owner id of this element
     * @param int  $accessGroupId    - access group id
     * @return ElcaAssistantElement
     * @throws \Exception
     */
    public static function createWithUnserializedConfig($mainElementId, $assistantIdent, $projectVariantId = null, $unserializedConfig = null, $isReference = false, $isPublic = false, $ownerId = null, $accessGroupId = null)
    {
        $assistantElement = new ElcaAssistantElement();
        $assistantElement->setMainElementId($mainElementId);
        $assistantElement->setAssistantIdent($assistantIdent);
        $assistantElement->setProjectVariantId($projectVariantId);
        $assistantElement->setUnserializedConfig($unserializedConfig);
        $assistantElement->setIsReference($isReference);
        $assistantElement->setIsPublic($isPublic);
        $assistantElement->setUuid(Uuid::uuid4());
        $assistantElement->setOwnerId($ownerId);
        $assistantElement->setAccessGroupId($accessGroupId);
        
        if ($assistantElement->getValidator()->isValid())
            $assistantElement->insert();
        
        return $assistantElement;
    }
    // End create

    /**
     * Creates the object
     *
     * @param int  $mainElementId    - mainElementId
     * @param      $assistantIdent
     * @param int  $projectVariantId - project variant id
     * @param null $serializedConfig
     * @param bool $isReference      - indicates a reference element
     * @param bool $isPublic         - indicates a public element
     * @param int  $ownerId          - owner id of this element
     * @param int  $accessGroupId    - access group id
     * @return ElcaAssistantElement
     * @throws \Exception
     */
    public static function create($mainElementId, $assistantIdent, $projectVariantId = null, $serializedConfig = null, $isReference = false, $isPublic = false, $ownerId = null, $accessGroupId = null)
    {
        $assistantElement = new ElcaAssistantElement();
        $assistantElement->setMainElementId($mainElementId);
        $assistantElement->setAssistantIdent($assistantIdent);
        $assistantElement->setProjectVariantId($projectVariantId);
        $assistantElement->setConfig($serializedConfig);
        $assistantElement->setIsReference($isReference);
        $assistantElement->setIsPublic($isPublic);
        $assistantElement->setUuid(Uuid::uuid4());
        $assistantElement->setOwnerId($ownerId);
        $assistantElement->setAccessGroupId($accessGroupId);

        if ($assistantElement->getValidator()->isValid())
            $assistantElement->insert();

        return $assistantElement;
    }
    // End create

    /**
     * Inits a `ElcaAssistantElement' by its primary key
     *
     * @param  int      $id    - assistantElementId
     * @param  bool     $force - Bypass caching
     * @return ElcaAssistantElement
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaAssistantElement();
        
        $sql = sprintf("SELECT id
                             , main_element_id
                             , project_variant_id
                             , assistant_ident
                             , config
                             , is_reference
                             , is_public
                             , uuid
                             , owner_id
                             , access_group_id
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
     * Inits a `ElcaAssistantElement' by its unique key (uuid)
     *
     * @param  string   $uuid  - uuid of the element
     * @param  bool     $force - Bypass caching
     * @return ElcaAssistantElement
     */
    public static function findByUuid($uuid, $force = false)
    {
        if(!$uuid)
            return new ElcaAssistantElement();
        
        $sql = sprintf("SELECT id
                             , main_element_id
                             , project_variant_id
                             , assistant_ident
                             , config
                             , is_reference
                             , is_public
                             , uuid
                             , owner_id
                             , access_group_id
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
     * Inits a `ElcaAssistantElement' by its main element
     *
     * @param  string   $uuid  - uuid of the element
     * @param  bool     $force - Bypass caching
     * @return ElcaAssistantElement
     */
    public static function findByMainElementId($mainElementId, $force = false)
    {
        if(!$mainElementId)
            return new ElcaAssistantElement();

        $sql = sprintf("SELECT id
                             , main_element_id
                             , project_variant_id
                             , assistant_ident
                             , config
                             , is_reference
                             , is_public
                             , uuid
                             , owner_id
                             , access_group_id
                             , created
                             , modified
                          FROM %s
                         WHERE main_element_id = :elementId"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['elementId' => $mainElementId], $force);
    }
    // End findByUuid

    /**
     * Inits a `ElcaAssistantElement' by its elementId (and optional assistantIdent)
     *
     * @param  bool     $force - Bypass caching
     * @return ElcaAssistantElement
     */
    public static function findByElementId($elementId, $assistantIdent = null, $force = false)
    {
        if(!$elementId)
            return new ElcaAssistantElement();

        $initValues = [
            'elementId' => $elementId,
        ];

        $sql = sprintf("SELECT id
                             , main_element_id
                             , project_variant_id
                             , assistant_ident
                             , config
                             , is_reference
                             , is_public
                             , uuid
                             , owner_id
                             , access_group_id
                             , created
                             , modified
                          FROM %s e
                          JOIN %s s ON e.id = s.assistant_element_id
                         WHERE s.element_id = :elementId"
            , self::TABLE_NAME
            , ElcaAssistantSubElement::TABLE_NAME
        );

        if ($assistantIdent) {
            $sql .= ' AND e.assistant_ident = :assistantIdent';
            $initValues['assistantIdent'] = $assistantIdent;
        }


        return self::findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByUuid

    public function copy($newMainElementId, $projectVariantId = null, $ownerId = null, $accessGroupId = null)
    {
        $copy = self::createWithUnserializedConfig($newMainElementId, $this->assistantIdent, $projectVariantId,
            $this->getDeserializedConfig(), $this->isReference, $this->isPublic, $ownerId ?? $this->ownerId,
            $accessGroupId ?? $this->accessGroupId);

        return $copy;
    }


    /**
     * Sets the property mainElementId
     *
     * @param  int      $mainElementId - mainElementId
     * @return void
     */
    public function setMainElementId($mainElementId)
    {
        if(!$this->getValidator()->assertNotEmpty('mainElementId', $mainElementId))
            return;
        
        $this->mainElementId = (int)$mainElementId;
    }
    // End setMainElementId
    

    /**
     * Sets the property projectVariantId
     *
     * @param  int      $projectVariantId - project variant id
     * @return void
     */
    public function setProjectVariantId($projectVariantId = null)
    {
        $this->projectVariantId = $projectVariantId;
    }


    public function setAssistantIdent($assistantIdent): void
    {
        if(!$this->getValidator()->assertNotEmpty('assistantIdent', $assistantIdent))
            return;

        $this->assistantIdent = $assistantIdent;
    }

    /**
     * Sets the property config
     *
     * @param  string   $config - configuration
     * @return void
     */
    public function setUnserializedConfig($config = null)
    {
        $serializedConfig = base64_encode(serialize($config));

        $this->config = $serializedConfig;
    }
    // End setConfig

    /**
     * Sets the property config
     *
     * @param  string   $config - configuration
     * @return void
     */
    public function setConfig($config = null)
    {
        $this->config = $config;
    }
    // End setConfig
    

    /**
     * Sets the property isReference
     *
     * @param  bool     $isReference - indicates a reference element
     * @return void
     */
    public function setIsReference($isReference = false)
    {
        $this->isReference = (bool)$isReference;
    }
    // End setIsReference
    

    /**
     * Sets the property isPublic
     *
     * @param  bool     $isPublic - indicates a public element
     * @return void
     */
    public function setIsPublic($isPublic = false)
    {
        $this->isPublic = (bool)$isPublic;
    }
    // End setIsPublic
    

    /**
     * Sets the property uuid
     *
     * @param  string   $uuid  - uuid of the element
     * @return void
     */
    public function setUuid($uuid = '')
    {
        $this->uuid = (string)$uuid;
    }
    // End setUuid
    

    /**
     * Sets the property ownerId
     *
     * @param  int      $ownerId - owner id of this element
     * @return void
     */
    public function setOwnerId($ownerId = null)
    {
        $this->ownerId = $ownerId;
    }
    // End setOwnerId
    

    /**
     * Sets the property accessGroupId
     *
     * @param  int      $accessGroupId - access group id
     * @return void
     */
    public function setAccessGroupId($accessGroupId = null)
    {
        $this->accessGroupId = $accessGroupId;
    }
    // End setAccessGroupId
    

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
     * Returns the property mainElementId
     *
     * @return int
     */
    public function getMainElementId()
    {
        return $this->mainElementId;
    }
    // End getMainElementId
    

    /**
     * Returns the associated ElcaElement by property mainElementId
     *
     * @param  bool     $force
     * @return ElcaElement
     */
    public function getMainElement($force = false)
    {
        return ElcaElement::findById($this->mainElementId, $force);
    }
    // End getMainElement
    

    /**
     * Returns the property projectVariantId
     *
     * @return int
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End getProjectVariantId
    

    /**
     * Returns the associated ElcaProjectVariant by property projectVariantId
     *
     * @param  bool     $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }

    public function getAssistantIdent()
    {
        return $this->assistantIdent;
    }

    /**
     * Returns the property config
     *
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }
    // End getConfig

    public function getDeserializedConfig()
    {
        if (!$this->isInitialized()) {
            return null;
        }

        return unserialize(base64_decode($this->getConfig()));
    }

    /**
     * Returns the property isReference
     *
     * @return bool
     */
    public function isReference()
    {
        return $this->isReference;
    }
    // End isReference
    

    /**
     * Returns the property isPublic
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->isPublic;
    }
    // End isPublic
    

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
     * Returns the property ownerId
     *
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }
    // End getOwnerId
    

    /**
     * Returns the associated User by property ownerId
     *
     * @param  bool     $force
     * @return User
     */
    public function getOwner($force = false)
    {
        return User::findById($this->ownerId, $force);
    }
    // End getOwner
    

    /**
     * Returns the property accessGroupId
     *
     * @return int
     */
    public function getAccessGroupId()
    {
        return $this->accessGroupId;
    }
    // End getAccessGroupId
    

    /**
     * Returns the associated Group by property accessGroupId
     *
     * @param  bool     $force
     * @return Group
     */
    public function getAccessGroup($force = false)
    {
        return Group::findById($this->accessGroupId, $force);
    }
    // End getAccessGroup
    

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
     * Checks, if the object exists
     *
     * @param  int      $id    - assistantElementId
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
                           SET main_element_id  = :mainElementId
                             , project_variant_id = :projectVariantId
                             , assistant_ident  = :assistantIdent
                             , config           = :config
                             , is_reference     = :isReference
                             , is_public        = :isPublic
                             , uuid             = :uuid
                             , owner_id         = :ownerId
                             , access_group_id  = :accessGroupId
                             , created          = :created
                             , modified         = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'              => $this->id,
                                        'mainElementId'   => $this->mainElementId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'assistantIdent'  => $this->assistantIdent,
                                        'config'          => $this->config,
                                        'isReference'     => $this->isReference,
                                        'isPublic'        => $this->isPublic,
                                        'uuid'            => $this->uuid,
                                        'ownerId'         => $this->ownerId,
                                        'accessGroupId'   => $this->accessGroupId,
                                        'created'         => $this->created,
                                        'modified'        => $this->modified)
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
     * @return ElcaAssistantSubElementSet|ElcaAssistantSubElement[]
     */
    public function getSubElements()
    {
        return ElcaAssistantSubElementSet::find(['assistant_element_id' => $this->id], ['element_id' => 'ASC']);
    }

    public function isMainElement(int $elementId)
    {
        if (!$this->isInitialized()) {
            return false;
        }

        return $elementId === $this->mainElementId;
    }

    /**
     * Inserts a new object in the table
     *
     * @return bool
     */
    protected function insert()
    {
        $this->id               = $this->getNextSequenceValue();
        $this->created          = self::getCurrentTime();
        $this->modified         = null;
        
        $sql = sprintf("INSERT INTO %s (id, main_element_id, project_variant_id, assistant_ident, config, is_reference, is_public, uuid, owner_id, access_group_id, created, modified)
                               VALUES  (:id, :mainElementId, :projectVariantId, :assistantIdent, :config, :isReference, :isPublic, :uuid, :ownerId, :accessGroupId, :created, :modified)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'              => $this->id,
                                        'mainElementId'   => $this->mainElementId,
                                        'projectVariantId' => $this->projectVariantId,
                                        'assistantIdent'  => $this->assistantIdent,
                                        'config'          => $this->config,
                                        'isReference'     => $this->isReference,
                                        'isPublic'        => $this->isPublic,
                                        'uuid'            => $this->uuid,
                                        'ownerId'         => $this->ownerId,
                                        'accessGroupId'   => $this->accessGroupId,
                                        'created'         => $this->created,
                                        'modified'        => $this->modified)
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
        $this->id               = (int)$DO->id;
        $this->mainElementId    = (int)$DO->main_element_id;
        $this->projectVariantId = $DO->project_variant_id;
        $this->assistantIdent = $DO->assistant_ident;
        $this->config           = $DO->config;
        $this->isReference      = (bool)$DO->is_reference;
        $this->isPublic         = (bool)$DO->is_public;
        $this->uuid             = $DO->uuid;
        $this->ownerId          = $DO->owner_id;
        $this->accessGroupId    = $DO->access_group_id;
        $this->created          = $DO->created;
        $this->modified         = $DO->modified;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaAssistantElement