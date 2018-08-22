<?php
namespace Elca\Db;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * 
 *
 * @package    -
 * @class      ElcaProjectAccessToken
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2017 BEIBOB Medienfreunde
 */
class ElcaProjectAccessToken extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_access_tokens';

    /**
     * projectAccessToken
     */
    private $token;

    /**
     * projectId
     */
    private $projectId;

    /**
     * userId of user which gets privileges
     */
    private $userId;

    /**
     * user email address
     */
    private $userEmail;

    /**
     * privilege to edit
     */
    private $canEdit;

    /**
     * confirmed state
     */
    private $isConfirmed;

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
    private static $primaryKey = array('token');

    /**
     * Column types
     */
    private static $columnTypes = array('token'          => PDO::PARAM_STR,
                                        'projectId'      => PDO::PARAM_INT,
                                        'userId'         => PDO::PARAM_INT,
                                        'userEmail'      => PDO::PARAM_STR,
                                        'canEdit'        => PDO::PARAM_BOOL,
                                        'isConfirmed'    => PDO::PARAM_BOOL,
                                        'created'        => PDO::PARAM_STR,
                                        'modified'       => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  string   $token      - projectAccessToken
     * @param  int      $projectId  - projectId
     * @param  string   $userEmail  - user email address
     * @param  int      $userId     - userId of user which gets privileges
     * @param  bool     $canEdit    - privilege to edit
     * @param  bool     $isConfirmed - confirmed state
     * @return ElcaProjectAccessToken
     */
    public static function create($token, $projectId, $userEmail, $userId = null, $canEdit = false, $isConfirmed = false)
    {
        $elcaProjectAccessToken = new ElcaProjectAccessToken();
        $elcaProjectAccessToken->setToken($token);
        $elcaProjectAccessToken->setProjectId($projectId);
        $elcaProjectAccessToken->setUserEmail($userEmail);
        $elcaProjectAccessToken->setUserId($userId);
        $elcaProjectAccessToken->setCanEdit($canEdit);
        $elcaProjectAccessToken->setIsConfirmed($isConfirmed);
        
        if($elcaProjectAccessToken->getValidator()->isValid())
            $elcaProjectAccessToken->insert();
        
        return $elcaProjectAccessToken;
    }
    // End create
    

    /**
     * Inits a `ElcaProjectAccessToken' by its primary key
     *
     * @param  string   $token - projectAccessToken
     * @param  bool     $force - Bypass caching
     * @return ElcaProjectAccessToken
     */
    public static function findByToken($token, $force = false)
    {
        if(!$token)
            return new ElcaProjectAccessToken();
        
        $sql = sprintf("SELECT token
                             , project_id
                             , user_id
                             , user_email
                             , can_edit
                             , is_confirmed
                             , created
                             , modified
                          FROM %s
                         WHERE token = :token"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('token' => $token), $force);
    }

    public static function findByProjectIdAndUserId($projectId, $userId, $force = false)
    {
        if (!$projectId || !$userId) {
            return new ElcaProjectAccessToken();
        }

        $sql = sprintf("SELECT token
                             , project_id
                             , user_id
                             , user_email
                             , can_edit
                             , is_confirmed
                             , created
                             , modified
                          FROM %s
                         WHERE (project_id, user_id) = (:projectId, :userId)"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, ['projectId' => $projectId, 'userId' => $userId], $force);
    }


    /**
     * Sets the property token
     *
     * @param  string   $token - projectAccessToken
     * @return void
     */
    public function setToken($token)
    {
        if(!$this->getValidator()->assertNotEmpty('token', $token))
            return;
        
        $this->token = (string)$token;
    }
    // End setToken
    

    /**
     * Sets the property projectId
     *
     * @param  int      $projectId - projectId
     * @return void
     */
    public function setProjectId($projectId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;
        
        $this->projectId = (int)$projectId;
    }
    // End setProjectId
    

    /**
     * Sets the property userId
     *
     * @param  int      $userId - userId of user which gets privileges
     * @return void
     */
    public function setUserId($userId = null)
    {
        $this->userId = $userId;
    }
    // End setUserId
    

    /**
     * Sets the property userEmail
     *
     * @param  string   $userEmail - user email address
     * @return void
     */
    public function setUserEmail($userEmail)
    {
        if(!$this->getValidator()->assertNotEmpty('userEmail', $userEmail))
            return;
        
        if(!$this->getValidator()->assertMaxLength('userEmail', 200, $userEmail))
            return;
        
        $this->userEmail = (string)$userEmail;
    }
    // End setUserEmail
    

    /**
     * Sets the property canEdit
     *
     * @param  bool     $canEdit - privilege to edit
     * @return void
     */
    public function setCanEdit($canEdit = false)
    {
        $this->canEdit = (bool)$canEdit;
    }
    // End setCanEdit
    

    /**
     * Sets the property isConfirmed
     *
     * @param  bool     $isConfirmed - confirmed state
     * @return void
     */
    public function setIsConfirmed($isConfirmed = false)
    {
        $this->isConfirmed = (bool)$isConfirmed;
    }
    // End setIsConfirmed
    

    /**
     * Returns the property token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    // End getToken
    

    /**
     * Returns the property projectId
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
    // End getProjectId
    

    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  bool     $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject
    

    /**
     * Returns the property userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
    // End getUserId
    

    /**
     * Returns the associated User by property userId
     *
     * @param  bool     $force
     * @return User
     */
    public function getUser($force = false)
    {
        return User::findById($this->userId, $force);
    }
    // End getUser
    

    /**
     * Returns the property userEmail
     *
     * @return string
     */
    public function getUserEmail()
    {
        return $this->userEmail;
    }
    // End getUserEmail
    

    /**
     * Returns the property canEdit
     *
     * @return bool
     */
    public function getCanEdit()
    {
        return $this->canEdit;
    }
    // End getCanEdit
    

    /**
     * Returns the property isConfirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->isConfirmed;
    }
    // End isConfirmed
    

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
     * @param  string   $token - projectAccessToken
     * @param  bool     $force - Bypass caching
     * @return bool
     */
    public static function exists($token, $force = false)
    {
        return self::findByToken($token, $force)->isInitialized();
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
                           SET project_id     = :projectId
                             , user_id        = :userId
                             , user_email     = :userEmail
                             , can_edit       = :canEdit
                             , is_confirmed   = :isConfirmed
                             , created        = :created
                             , modified       = :modified
                         WHERE token = :token"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('token'         => $this->token,
                                        'projectId'     => $this->projectId,
                                        'userId'        => $this->userId,
                                        'userEmail'     => $this->userEmail,
                                        'canEdit'       => $this->canEdit,
                                        'isConfirmed'   => $this->isConfirmed,
                                        'created'       => $this->created,
                                        'modified'      => $this->modified)
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
                              WHERE token = :token"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('token' => $this->token));
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
        $this->created        = self::getCurrentTime();
        $this->modified       = null;
        
        $sql = sprintf("INSERT INTO %s (token, project_id, user_id, user_email, can_edit, is_confirmed, created, modified)
                               VALUES  (:token, :projectId, :userId, :userEmail, :canEdit, :isConfirmed, :created, :modified)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('token'         => $this->token,
                                        'projectId'     => $this->projectId,
                                        'userId'        => $this->userId,
                                        'userEmail'     => $this->userEmail,
                                        'canEdit'       => $this->canEdit,
                                        'isConfirmed'   => $this->isConfirmed,
                                        'created'       => $this->created,
                                        'modified'      => $this->modified)
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
        $this->token          = $DO->token;
        $this->projectId      = (int)$DO->project_id;
        $this->userId         = $DO->user_id;
        $this->userEmail      = $DO->user_email;
        $this->canEdit        = (bool)$DO->can_edit;
        $this->isConfirmed    = (bool)$DO->is_confirmed;
        $this->created        = $DO->created;
        $this->modified       = $DO->modified;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaProjectAccessToken