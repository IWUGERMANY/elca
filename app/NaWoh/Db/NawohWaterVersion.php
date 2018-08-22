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

namespace NaWoh\Db;

use Beibob\Blibs\DbObject;
use PDO;

/**
 * 
 *
 * @package    -
 * @class      NawohWaterVersion
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2018 BEIBOB Medienfreunde
 */
class NawohWaterVersion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'nawoh.water_versions';

    /**
     * 
     */
    private $id;

    /**
     * 
     */
    private $name;

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
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array('id'                 => PDO::PARAM_INT,
                                        'name'               => PDO::PARAM_STR,
                                        'mitBadewanne'       => PDO::PARAM_BOOL,
                                        'toiletteVoll'       => PDO::PARAM_STR,
                                        'toiletteSpartaste'  => PDO::PARAM_STR,
                                        'dusche'             => PDO::PARAM_STR,
                                        'badewanneGesamt'    => PDO::PARAM_STR,
                                        'wasserhaehneBad'    => PDO::PARAM_STR,
                                        'wasserhaehneKueche' => PDO::PARAM_STR,
                                        'waschmaschine'      => PDO::PARAM_STR,
                                        'geschirrspueler'    => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    /**
     * Creates the object
     *
     * @param  string   $name              - 
     * @param  bool     $mitBadewanne      - 
     * @param  float    $toiletteVoll      - 
     * @param  float    $toiletteSpartaste - 
     * @param  float    $dusche            - 
     * @param  float    $wasserhaehneBad   - 
     * @param  float    $wasserhaehneKueche - 
     * @param  float    $waschmaschine     - 
     * @param  float    $geschirrspueler   - 
     * @param  int      $projectId         - 
     * @param  float    $badewanneGesamt   - 
     * @return NawohWaterVersion
     */
    public static function create($name, $mitBadewanne, $toiletteVoll, $toiletteSpartaste, $dusche, $wasserhaehneBad, $wasserhaehneKueche, $waschmaschine, $geschirrspueler, $badewanneGesamt = null)
    {
        $NawohWaterVersion = new NawohWaterVersion();
        $NawohWaterVersion->setName($name);
        $NawohWaterVersion->setMitBadewanne($mitBadewanne);
        $NawohWaterVersion->setToiletteVoll($toiletteVoll);
        $NawohWaterVersion->setToiletteSpartaste($toiletteSpartaste);
        $NawohWaterVersion->setDusche($dusche);
        $NawohWaterVersion->setWasserhaehneBad($wasserhaehneBad);
        $NawohWaterVersion->setWasserhaehneKueche($wasserhaehneKueche);
        $NawohWaterVersion->setWaschmaschine($waschmaschine);
        $NawohWaterVersion->setGeschirrspueler($geschirrspueler);
        $NawohWaterVersion->setBadewanneGesamt($badewanneGesamt);
        
        if($NawohWaterVersion->getValidator()->isValid())
            $NawohWaterVersion->insert();
        
        return $NawohWaterVersion;
    }
    // End create
    

    /**
     * Inits a `NawohWaterVersion' by its primary key
     *
     * @param  int      $id    - 
     * @param  bool     $force - Bypass caching
     * @return NawohWaterVersion
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new NawohWaterVersion();

        $sql = sprintf("SELECT id
                             , name
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
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById

    public static function findLatestByTub($mitBadewanne = false, $force = false)
    {
        $sql = sprintf("SELECT id
                             , name
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
                         WHERE mit_badewanne = :mitBadewanne"
            , self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('mitBadewanne' => $mitBadewanne), $force);
    }

    /**
     * Sets the property name
     *
     * @param  string   $name  - 
     * @return void
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;
        
        if(!$this->getValidator()->assertMaxLength('name', 255, $name))
            return;
        
        $this->name = (string)$name;
    }
    // End setName
    

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
    public function setToiletteVoll($toiletteVoll)
    {
        if(!$this->getValidator()->assertNotEmpty('toiletteVoll', $toiletteVoll))
            return;
        
        $this->toiletteVoll = $toiletteVoll;
    }
    // End setToiletteVoll
    

    /**
     * Sets the property toiletteSpartaste
     *
     * @param  float    $toiletteSpartaste - 
     * @return void
     */
    public function setToiletteSpartaste($toiletteSpartaste)
    {
        if(!$this->getValidator()->assertNotEmpty('toiletteSpartaste', $toiletteSpartaste))
            return;
        
        $this->toiletteSpartaste = $toiletteSpartaste;
    }
    // End setToiletteSpartaste
    

    /**
     * Sets the property dusche
     *
     * @param  float    $dusche - 
     * @return void
     */
    public function setDusche($dusche)
    {
        if(!$this->getValidator()->assertNotEmpty('dusche', $dusche))
            return;
        
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
    public function setWasserhaehneBad($wasserhaehneBad)
    {
        if(!$this->getValidator()->assertNotEmpty('wasserhaehneBad', $wasserhaehneBad))
            return;
        
        $this->wasserhaehneBad = $wasserhaehneBad;
    }
    // End setWasserhaehneBad
    

    /**
     * Sets the property wasserhaehneKueche
     *
     * @param  float    $wasserhaehneKueche - 
     * @return void
     */
    public function setWasserhaehneKueche($wasserhaehneKueche)
    {
        if(!$this->getValidator()->assertNotEmpty('wasserhaehneKueche', $wasserhaehneKueche))
            return;
        
        $this->wasserhaehneKueche = $wasserhaehneKueche;
    }
    // End setWasserhaehneKueche
    

    /**
     * Sets the property waschmaschine
     *
     * @param  float    $waschmaschine - 
     * @return void
     */
    public function setWaschmaschine($waschmaschine)
    {
        if(!$this->getValidator()->assertNotEmpty('waschmaschine', $waschmaschine))
            return;
        
        $this->waschmaschine = $waschmaschine;
    }
    // End setWaschmaschine
    

    /**
     * Sets the property geschirrspueler
     *
     * @param  float    $geschirrspueler - 
     * @return void
     */
    public function setGeschirrspueler($geschirrspueler)
    {
        if(!$this->getValidator()->assertNotEmpty('geschirrspueler', $geschirrspueler))
            return;
        
        $this->geschirrspueler = $geschirrspueler;
    }
    // End setGeschirrspueler
    

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
     * @param  int      $id    - 
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
        $sql = sprintf("UPDATE %s
                           SET name               = :name
                             , mit_badewanne      = :mitBadewanne
                             , toilette_voll      = :toiletteVoll
                             , toilette_spartaste = :toiletteSpartaste
                             , dusche             = :dusche
                             , badewanne_gesamt   = :badewanneGesamt
                             , wasserhaehne_bad   = :wasserhaehneBad
                             , wasserhaehne_kueche = :wasserhaehneKueche
                             , waschmaschine      = :waschmaschine
                             , geschirrspueler    = :geschirrspueler
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  array('id'                => $this->id,
                                        'name'              => $this->name,
                                        'mitBadewanne'      => $this->mitBadewanne,
                                        'toiletteVoll'      => $this->toiletteVoll,
                                        'toiletteSpartaste' => $this->toiletteSpartaste,
                                        'dusche'            => $this->dusche,
                                        'badewanneGesamt'   => $this->badewanneGesamt,
                                        'wasserhaehneBad'   => $this->wasserhaehneBad,
                                        'wasserhaehneKueche' => $this->wasserhaehneKueche,
                                        'waschmaschine'     => $this->waschmaschine,
                                        'geschirrspueler'   => $this->geschirrspueler)
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
        $this->id                 = $this->getNextSequenceValue();
        
        $sql = sprintf("INSERT INTO %s (id, name, mit_badewanne, toilette_voll, toilette_spartaste, dusche, badewanne_gesamt, wasserhaehne_bad, wasserhaehne_kueche, waschmaschine, geschirrspueler)
                               VALUES  (:id, :name, :mitBadewanne, :toiletteVoll, :toiletteSpartaste, :dusche, :badewanneGesamt, :wasserhaehneBad, :wasserhaehneKueche, :waschmaschine, :geschirrspueler)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  array('id'                => $this->id,
                                        'name'              => $this->name,
                                        'mitBadewanne'      => $this->mitBadewanne,
                                        'toiletteVoll'      => $this->toiletteVoll,
                                        'toiletteSpartaste' => $this->toiletteSpartaste,
                                        'dusche'            => $this->dusche,
                                        'badewanneGesamt'   => $this->badewanneGesamt,
                                        'wasserhaehneBad'   => $this->wasserhaehneBad,
                                        'wasserhaehneKueche' => $this->wasserhaehneKueche,
                                        'waschmaschine'     => $this->waschmaschine,
                                        'geschirrspueler'   => $this->geschirrspueler)
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
        $this->id                 = (int)$DO->id;
        $this->name               = $DO->name;
        $this->mitBadewanne       = (bool)$DO->mit_badewanne;
        $this->toiletteVoll       = $DO->toilette_voll;
        $this->toiletteSpartaste  = $DO->toilette_spartaste;
        $this->dusche             = $DO->dusche;
        $this->badewanneGesamt    = $DO->badewanne_gesamt;
        $this->wasserhaehneBad    = $DO->wasserhaehne_bad;
        $this->wasserhaehneKueche = $DO->wasserhaehne_kueche;
        $this->waschmaschine      = $DO->waschmaschine;
        $this->geschirrspueler    = $DO->geschirrspueler;
        
        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class NawohWaterVersion