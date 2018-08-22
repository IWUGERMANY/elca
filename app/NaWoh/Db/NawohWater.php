<?php

namespace NaWoh\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaProject;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      NawohWater
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class NawohWater extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'nawoh.water';

    /**
     * 
     */
    private $projectId;

    /**
     * 
     */
    private $mitBadewanne;

    /**
     * 
     */
    private $toiletteVoll;

    /**
     * 
     */
    private $toiletteSpartaste;

    /**
     * 
     */
    private $dusche;

    /**
     * 
     */
    private $badewanneGesamt;

    /**
     * 
     */
    private $wasserhaehneBad;

    /**
     * 
     */
    private $wasserhaehneKueche;

    /**
     * 
     */
    private $waschmaschine;

    /**
     * 
     */
    private $geschirrspueler;

    /**
     * Primary key
     */
    private static $primaryKey = array('projectId');

    /**
     * Column types
     */
    private static $columnTypes = array('projectId'          => PDO::PARAM_INT,
                                        'mitBadewanne'       => PDO::PARAM_BOOL,
                                        'toiletteVoll'       => PDO::PARAM_STR,
                                        'toiletteSpartaste'  => PDO::PARAM_STR,
                                        'dusche'             => PDO::PARAM_STR,
                                        'badewanneGesamt'    => PDO::PARAM_STR,
                                        'wasserhaehneBad'    => PDO::PARAM_STR,
                                        'wasserhaehneKueche' => PDO::PARAM_STR,
                                        'waschmaschine'      => PDO::PARAM_STR,
                                        'geschirrspueler'   => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  int      $projectId         - 
     * @param  bool     $mitBadewanne      - 
     * @param  float    $toiletteVoll      - 
     * @param  float    $toiletteSpartaste - 
     * @param  float    $dusche            - 
     * @param  float    $badewanneGesamt   - 
     * @param  float    $wasserhaehneBad   - 
     * @param  float    $wasserhaehneKueche - 
     * @param  float    $waschmaschine     - 
     * @param  float    $geschirrspueler  -
     * @return NawohWater
     */
    public static function create($projectId, $mitBadewanne, $toiletteVoll = null, $toiletteSpartaste = null, $dusche = null, $badewanneGesamt = null, $wasserhaehneBad = null, $wasserhaehneKueche = null, $waschmaschine = 40, $geschirrspueler = 15)
    {
        $NawohWater = new NawohWater();
        $NawohWater->setProjectId($projectId);
        $NawohWater->setMitBadewanne($mitBadewanne);
        $NawohWater->setToiletteVoll($toiletteVoll);
        $NawohWater->setToiletteSpartaste($toiletteSpartaste);
        $NawohWater->setDusche($dusche);
        $NawohWater->setBadewanneGesamt($badewanneGesamt);
        $NawohWater->setWasserhaehneBad($wasserhaehneBad);
        $NawohWater->setWasserhaehneKueche($wasserhaehneKueche);
        $NawohWater->setWaschmaschine($waschmaschine);
        $NawohWater->setGeschirrspueler($geschirrspueler);
        
        if($NawohWater->getValidator()->isValid())
            $NawohWater->insert();
        
        return $NawohWater;
    }
    // End create
    

    /**
     * Inits a `NawohWater' by its primary key
     *
     * @param  int      $projectId - 
     * @param  bool     $force    - Bypass caching
     * @return NawohWater
     */
    public static function findByProjectId($projectId, $force = false)
    {
        if(!$projectId)
            return new NawohWater();
        
        $sql = sprintf("SELECT project_id
                             , mit_badewanne
                             , toilette_voll
                             , toilette_spartaste
                             , dusche
                             , badewanne_gesamt
                             , wasserhaehne_bad
                             , wasserhaehne_kueche
                             , waschmaschine
                             , geschirrspueler
                          FROM %s
                         WHERE project_id = :projectId"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, array('projectId' => $projectId), $force);
    }
    // End findByProjectId
    

    /**
     * Sets the property projectId
     *
     * @param  int      $projectId - 
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
     * Sets the property mitBadewanne
     *
     * @param  bool     $mitBadewanne - 
     * @return void
     */
    public function setMitBadewanne($mitBadewanne)
    {
        if(!$this->getValidator()->assertNotEmpty('mitBadewanne', $mitBadewanne))
            return;
        
        $this->mitBadewanne = (bool)$mitBadewanne;
    }
    // End setMitBadewanne
    

    /**
     * Sets the property toiletteVoll
     *
     * @param  float    $toiletteVoll - 
     * @return void
     */
    public function setToiletteVoll($toiletteVoll = null)
    {
        $this->toiletteVoll = $toiletteVoll;
    }
    // End setToiletteVoll
    

    /**
     * Sets the property toiletteSpartaste
     *
     * @param  float    $toiletteSpartaste - 
     * @return void
     */
    public function setToiletteSpartaste($toiletteSpartaste = null)
    {
        $this->toiletteSpartaste = $toiletteSpartaste;
    }
    // End setToiletteSpartaste
    

    /**
     * Sets the property dusche
     *
     * @param  float    $dusche - 
     * @return void
     */
    public function setDusche($dusche = null)
    {
        $this->dusche = $dusche;
    }
    // End setDusche
    

    /**
     * Sets the property badewanneGesamt
     *
     * @param  float    $badewanneGesamt - 
     * @return void
     */
    public function setBadewanneGesamt($badewanneGesamt = null)
    {
        $this->badewanneGesamt = $badewanneGesamt;
    }
    // End setBadewanneGesamt
    

    /**
     * Sets the property wasserhaehneBad
     *
     * @param  float    $wasserhaehneBad - 
     * @return void
     */
    public function setWasserhaehneBad($wasserhaehneBad = null)
    {
        $this->wasserhaehneBad = $wasserhaehneBad;
    }
    // End setWasserhaehneBad
    

    /**
     * Sets the property wasserhaehneKueche
     *
     * @param  float    $wasserhaehneKueche - 
     * @return void
     */
    public function setWasserhaehneKueche($wasserhaehneKueche = null)
    {
        $this->wasserhaehneKueche = $wasserhaehneKueche;
    }
    // End setWasserhaehneKueche
    

    /**
     * Sets the property waschmaschine
     *
     * @param  float    $waschmaschine - 
     * @return void
     */
    public function setWaschmaschine($waschmaschine = 40)
    {
        $this->waschmaschine = $waschmaschine;
    }
    // End setWaschmaschine
    

    /**
     * Sets the property geschirrspueler
     *
     * @param  float    $geschirrspueler -
     * @return void
     */
    public function setGeschirrspueler($geschirrspueler = 15)
    {
        $this->geschirrspueler = $geschirrspueler;
    }
    // End setGeschirrspueler
    

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
     * Returns the property mitBadewanne
     *
     * @return bool
     */
    public function getMitBadewanne()
    {
        return $this->mitBadewanne;
    }
    // End getMitBadewanne
    

    /**
     * Returns the property toiletteVoll
     *
     * @return float
     */
    public function getToiletteVoll()
    {
        return $this->toiletteVoll;
    }
    // End getToiletteVoll
    

    /**
     * Returns the property toiletteSpartaste
     *
     * @return float
     */
    public function getToiletteSpartaste()
    {
        return $this->toiletteSpartaste;
    }
    // End getToiletteSpartaste
    

    /**
     * Returns the property dusche
     *
     * @return float
     */
    public function getDusche()
    {
        return $this->dusche;
    }
    // End getDusche
    

    /**
     * Returns the property badewanneGesamt
     *
     * @return float
     */
    public function getBadewanneGesamt()
    {
        return $this->badewanneGesamt;
    }
    // End getBadewanneGesamt
    

    /**
     * Returns the property wasserhaehneBad
     *
     * @return float
     */
    public function getWasserhaehneBad()
    {
        return $this->wasserhaehneBad;
    }
    // End getWasserhaehneBad
    

    /**
     * Returns the property wasserhaehneKueche
     *
     * @return float
     */
    public function getWasserhaehneKueche()
    {
        return $this->wasserhaehneKueche;
    }
    // End getWasserhaehneKueche
    

    /**
     * Returns the property waschmaschine
     *
     * @return float
     */
    public function getWaschmaschine()
    {
        return $this->waschmaschine;
    }
    // End getWaschmaschine
    

    /**
     * Returns the property geschirrspueler
     *
     * @return float
     */
    public function getGeschirrspueler()
    {
        return $this->geschirrspueler;
    }
    // End getGeschirrspueler
    

    /**
     * Checks, if the object exists
     *
     * @param  int      $projectId - 
     * @param  bool     $force    - Bypass caching
     * @return bool
     */
    public static function exists($projectId, $force = false)
    {
        return self::findByProjectId($projectId, $force)->isInitialized();
    }
    // End exists
    

    /**
     * Updates the object in the table
     *
     * @return bool
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET mit_badewanne      = :mitBadewanne
                             , toilette_voll      = :toiletteVoll
                             , toilette_spartaste = :toiletteSpartaste
                             , dusche             = :dusche
                             , badewanne_gesamt   = :badewanneGesamt
                             , wasserhaehne_bad   = :wasserhaehneBad
                             , wasserhaehne_kueche = :wasserhaehneKueche
                             , waschmaschine      = :waschmaschine
                             , geschirrspueler   = :geschirrspueler
                         WHERE project_id = :projectId"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('projectId'         => $this->projectId,
                                        'mitBadewanne'      => $this->mitBadewanne,
                                        'toiletteVoll'      => $this->toiletteVoll,
                                        'toiletteSpartaste' => $this->toiletteSpartaste,
                                        'dusche'            => $this->dusche,
                                        'badewanneGesamt'   => $this->badewanneGesamt,
                                        'wasserhaehneBad'   => $this->wasserhaehneBad,
                                        'wasserhaehneKueche' => $this->wasserhaehneKueche,
                                        'waschmaschine'     => $this->waschmaschine,
                                        'geschirrspueler'  => $this->geschirrspueler)
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
                              WHERE project_id = :projectId"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  array('projectId' => $this->projectId));
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
        
        $sql = sprintf("INSERT INTO %s (project_id, mit_badewanne, toilette_voll, toilette_spartaste, dusche, badewanne_gesamt, wasserhaehne_bad, wasserhaehne_kueche, waschmaschine, geschirrspueler)
                               VALUES  (:projectId, :mitBadewanne, :toiletteVoll, :toiletteSpartaste, :dusche, :badewanneGesamt, :wasserhaehneBad, :wasserhaehneKueche, :waschmaschine, :geschirrspueler)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('projectId'         => $this->projectId,
                                        'mitBadewanne'      => $this->mitBadewanne,
                                        'toiletteVoll'      => $this->toiletteVoll,
                                        'toiletteSpartaste' => $this->toiletteSpartaste,
                                        'dusche'            => $this->dusche,
                                        'badewanneGesamt'   => $this->badewanneGesamt,
                                        'wasserhaehneBad'   => $this->wasserhaehneBad,
                                        'wasserhaehneKueche' => $this->wasserhaehneKueche,
                                        'waschmaschine'     => $this->waschmaschine,
                                        'geschirrspueler'  => $this->geschirrspueler)
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
        $this->projectId          = (int)$DO->project_id;
        $this->mitBadewanne       = (bool)$DO->mit_badewanne;
        $this->toiletteVoll       = $DO->toilette_voll;
        $this->toiletteSpartaste  = $DO->toilette_spartaste;
        $this->dusche             = $DO->dusche;
        $this->badewanneGesamt    = $DO->badewanne_gesamt;
        $this->wasserhaehneBad    = $DO->wasserhaehne_bad;
        $this->wasserhaehneKueche = $DO->wasserhaehne_kueche;
        $this->waschmaschine      = $DO->waschmaschine;
        $this->geschirrspueler   = $DO->geschirrspueler;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class NawohWater