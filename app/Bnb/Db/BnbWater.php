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

namespace Bnb\Db;

use Beibob\Blibs\DbObject;
use Elca\Db\ElcaProject;
use PDO;

/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      BnbWater
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class BnbWater extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'bnb.water';



    /**
     * projectId
     */
    private $projectId;

    /**
     * Niederschlagsmenge am Standort in mm
     */
    private $niederschlagsmenge;

    /**
     * Anzahl Personen
     */
    private $anzahlPersonen;

    /**
     * Waschtisch in l
     */
    private $sanitaerWaschtisch;

    /**
     * Spuelung WC Spartaste in l
     */
    private $sanitaerWcSpar;

    /**
     * Spuelung WC in l
     */
    private $sanitaerWc;

    /**
     * Urinal
     */
    private $sanitaerUrinal;

    /**
     * Armatur Dusche
     */
    private $sanitaerDusche;

    /**
     * Armatur Teeküche
     */
    private $sanitaerTeekueche;

    /**
     * Reinigung Santitaerbereiche
     */
    private $reinigungSanitaer;

    /**
     * Reinigung Lobby
     */
    private $reinigungLobby;

    /**
     * Reinigung Verkehrsflaeche
     */
    private $reinigungVerkehrsflaeche;

    /**
     * Reinigung Buero
     */
    private $reinigungBuero;

    /**
     * Reinigung Nebenraum
     */
    private $reinigungKeller;

    /**
     * Dach1 Flaeche
     */
    private $dach1Flaeche;

    /**
     * Ertragsbeiwert Dach1
     */
    private $dach1Ertragsbeiwert;

    /**
     * Dach2 Flaeche
     */
    private $dach2Flaeche;

    /**
     * Ertragsbeiwert Dach2
     */
    private $dach2Ertragsbeiwert;

    /**
     * Dach3 Flaeche
     */
    private $dach3Flaeche;

    /**
     * Ertragsbeiwert Dach3
     */
    private $dach3Ertragsbeiwert;

    /**
     * Dach4 Flaeche
     */
    private $dach4Flaeche;

    /**
     * Ertragsbeiwert Dach4
     */
    private $dach4Ertragsbeiwert;

    /**
     * Menge des auf dem Grundstueck versickerten Niederschlagswassers
     */
    private $niederschlagVersickert;

    /**
     * Menge des genutzten Niederschlagswasser
     */
    private $niederschlagGenutzt;
    private $niederschlagGenutztOhneWandlung;
    private $niederschlagKanalisation;

    /**
     * Menge des genutzten Brauchwassers
     */
    private $brauchwasser;

    /**
     * Menge des auf dem Grundstueck gereinigten Brauchwassers
     */
    private $brauchwasserGereinigt;

    /**
     * Primary key
     */
    private static $primaryKey = ['projectId'];

    /**
     * Column types
     */
    private static $columnTypes = ['projectId'                => PDO::PARAM_INT,
                                        'niederschlagsmenge'       => PDO::PARAM_STR,
                                        'anzahlPersonen'           => PDO::PARAM_INT,
                                        'sanitaerWaschtisch'       => PDO::PARAM_STR,
                                        'sanitaerWcSpar'           => PDO::PARAM_STR,
                                        'sanitaerWc'               => PDO::PARAM_STR,
                                        'sanitaerUrinal'           => PDO::PARAM_STR,
                                        'sanitaerDusche'           => PDO::PARAM_STR,
                                        'sanitaerTeekueche'        => PDO::PARAM_STR,
                                        'reinigungSanitaer'        => PDO::PARAM_STR,
                                        'reinigungLobby'           => PDO::PARAM_STR,
                                        'reinigungVerkehrsflaeche' => PDO::PARAM_STR,
                                        'reinigungBuero'           => PDO::PARAM_STR,
                                        'reinigungKeller'          => PDO::PARAM_STR,
                                        'dach1Flaeche'             => PDO::PARAM_STR,
                                        'dach1Ertragsbeiwert'      => PDO::PARAM_STR,
                                        'dach2Flaeche'             => PDO::PARAM_STR,
                                        'dach2Ertragsbeiwert'      => PDO::PARAM_STR,
                                        'dach3Flaeche'             => PDO::PARAM_STR,
                                        'dach3Ertragsbeiwert'      => PDO::PARAM_STR,
                                        'dach4Flaeche'             => PDO::PARAM_STR,
                                        'dach4Ertragsbeiwert'      => PDO::PARAM_STR,
                                        'niederschlagVersickert'   => PDO::PARAM_STR,
                                        'niederschlagGenutzt'      => PDO::PARAM_STR,
                                        'niederschlagGenutztOhneWandlung'      => PDO::PARAM_STR,
                                   'niederschlagKanalisation'      => PDO::PARAM_STR,
                                        'brauchwasser'             => PDO::PARAM_STR,
                                        'brauchwasserGereinigt'    => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];


    // public


    /**
     * Creates the object
     *
     * @param  integer $projectId                - projectId
     * @param  number $niederschlagsmenge       - Niederschlagsmenge am Standort in mm
     * @param  integer $anzahlPersonen           - Anzahl Personen
     * @param  number $sanitaerWaschtisch       - Waschtisch in l
     * @param  number $sanitaerWcSpar           - Spuelung WC Spartaste in l
     * @param  number $sanitaerWc               - Spuelung WC in l
     * @param  number $sanitaerUrinal           - Urinal
     * @param  number $sanitaerDusche           - Armatur Dusche
     * @param  number $sanitaerTeekueche        - Armatur Teeküche
     * @param  number $reinigungSanitaer        - Reinigung Santitaerbereiche
     * @param  number $reinigungLobby           - Reinigung Lobby
     * @param  number $reinigungVerkehrsflaeche - Reinigung Verkehrsflaeche
     * @param  number $reinigungBuero           - Reinigung Buero
     * @param  number $reinigungKeller          - Reinigung Nebenraum
     * @param  number $dach1Flaeche             - Dach1 Flaeche
     * @param  number $dach1Ertragsbeiwert      - Ertragsbeiwert Dach1
     * @param  number $dach2Flaeche             - Dach2 Flaeche
     * @param  number $dach2Ertragsbeiwert      - Ertragsbeiwert Dach2
     * @param  number $dach3Flaeche             - Dach3 Flaeche
     * @param  number $dach3Ertragsbeiwert      - Ertragsbeiwert Dach3
     * @param  number $dach4Flaeche             - Dach4 Flaeche
     * @param  number $dach4Ertragsbeiwert      - Ertragsbeiwert Dach4
     * @param  number $niederschlagVersickert   - Menge des auf dem Grundstueck versickerten Niederschlagswassers
     * @param  number $niederschlagGenutzt      - Menge des genutzten Niederschlagswasser
     * @param  number $brauchwasser             - Menge des genutzten Brauchwassers
     * @param  number $brauchwasserGereinigt    - Menge des auf dem Grundstueck gereinigten Brauchwassers
     * @return BnbWater
     */
    public static function create($projectId, $niederschlagsmenge = null, $anzahlPersonen = null, $sanitaerWaschtisch = null, $sanitaerWcSpar = null, $sanitaerWc = null, $sanitaerUrinal = null, $sanitaerDusche = null, $sanitaerTeekueche = null, $reinigungSanitaer = null, $reinigungLobby = null, $reinigungVerkehrsflaeche = null, $reinigungBuero = null, $reinigungKeller = null, $dach1Flaeche = null, $dach1Ertragsbeiwert = null, $dach2Flaeche = null, $dach2Ertragsbeiwert = null, $dach3Flaeche = null, $dach3Ertragsbeiwert = null, $dach4Flaeche = null, $dach4Ertragsbeiwert = null, $niederschlagVersickert = null, $niederschlagGenutzt = null, $niederschlagGenutztOhneWandlung = null, $niederschlagKanalisation = null, $brauchwasser = null, $brauchwasserGereinigt = null)
    {
        $BnbWater = new BnbWater();
        $BnbWater->setProjectId($projectId);
        $BnbWater->setNiederschlagsmenge($niederschlagsmenge);
        $BnbWater->setAnzahlPersonen($anzahlPersonen);
        $BnbWater->setSanitaerWaschtisch($sanitaerWaschtisch);
        $BnbWater->setSanitaerWcSpar($sanitaerWcSpar);
        $BnbWater->setSanitaerWc($sanitaerWc);
        $BnbWater->setSanitaerUrinal($sanitaerUrinal);
        $BnbWater->setSanitaerDusche($sanitaerDusche);
        $BnbWater->setSanitaerTeekueche($sanitaerTeekueche);
        $BnbWater->setReinigungSanitaer($reinigungSanitaer);
        $BnbWater->setReinigungLobby($reinigungLobby);
        $BnbWater->setReinigungVerkehrsflaeche($reinigungVerkehrsflaeche);
        $BnbWater->setReinigungBuero($reinigungBuero);
        $BnbWater->setReinigungKeller($reinigungKeller);
        $BnbWater->setDach1Flaeche($dach1Flaeche);
        $BnbWater->setDach1Ertragsbeiwert($dach1Ertragsbeiwert);
        $BnbWater->setDach2Flaeche($dach2Flaeche);
        $BnbWater->setDach2Ertragsbeiwert($dach2Ertragsbeiwert);
        $BnbWater->setDach3Flaeche($dach3Flaeche);
        $BnbWater->setDach3Ertragsbeiwert($dach3Ertragsbeiwert);
        $BnbWater->setDach4Flaeche($dach4Flaeche);
        $BnbWater->setDach4Ertragsbeiwert($dach4Ertragsbeiwert);
        $BnbWater->setNiederschlagVersickert($niederschlagVersickert);
        $BnbWater->setNiederschlagGenutzt($niederschlagGenutzt);
        $BnbWater->setNiederschlagGenutztOhneWandlung($niederschlagGenutztOhneWandlung);
        $BnbWater->setNiederschlagKanalisation($niederschlagKanalisation);
        $BnbWater->setBrauchwasser($brauchwasser);
        $BnbWater->setBrauchwasserGereinigt($brauchwasserGereinigt);

        if($BnbWater->getValidator()->isValid())
            $BnbWater->insert();

        return $BnbWater;
    }
    // End create



    /**
     * Inits a `BnbWater' by its primary key
     *
     * @param  integer  $projectId - projectId
     * @param  boolean  $force    - Bypass caching
     * @return BnbWater
     */
    public static function findByProjectId($projectId, $force = false)
    {
        if(!$projectId)
            return new BnbWater();

        $sql = sprintf("SELECT project_id
                             , niederschlagsmenge
                             , anzahl_personen
                             , sanitaer_waschtisch
                             , sanitaer_wc_spar
                             , sanitaer_wc
                             , sanitaer_urinal
                             , sanitaer_dusche
                             , sanitaer_teekueche
                             , reinigung_sanitaer
                             , reinigung_lobby
                             , reinigung_verkehrsflaeche
                             , reinigung_buero
                             , reinigung_keller
                             , dach1_flaeche
                             , dach1_ertragsbeiwert
                             , dach2_flaeche
                             , dach2_ertragsbeiwert
                             , dach3_flaeche
                             , dach3_ertragsbeiwert
                             , dach4_flaeche
                             , dach4_ertragsbeiwert
                             , niederschlag_versickert
                             , brauchwasser
                             , niederschlag_genutzt
                             , niederschlag_genutzt_ohne_wandlung
                             , niederschlag_kanalisation
                             , brauchwasser_gereinigt
                          FROM %s
                         WHERE project_id = :projectId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, ['projectId' => $projectId], $force);
    }
    // End findByProjectId


    /**
     * Sets the property projectId
     *
     * @param  integer  $projectId - projectId
     * @return
     */
    public function setProjectId($projectId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectId', $projectId))
            return;

        $this->projectId = (int)$projectId;
    }
    // End setProjectId


    /**
     * Sets the property niederschlagsmenge
     *
     * @param  number  $niederschlagsmenge - Niederschlagsmenge am Standort in mm
     * @return
     */
    public function setNiederschlagsmenge($niederschlagsmenge = null)
    {
        $this->niederschlagsmenge = $niederschlagsmenge;
    }
    // End setNiederschlagsmenge


    /**
     * Sets the property anzahlPersonen
     *
     * @param  integer  $anzahlPersonen - Anzahl Personen
     * @return
     */
    public function setAnzahlPersonen($anzahlPersonen = null)
    {
        $this->anzahlPersonen = $anzahlPersonen;
    }
    // End setAnzahlPersonen


    /**
     * Sets the property sanitaerWaschtisch
     *
     * @param  number  $sanitaerWaschtisch - Waschtisch in l
     * @return
     */
    public function setSanitaerWaschtisch($sanitaerWaschtisch = null)
    {
        $this->sanitaerWaschtisch = $sanitaerWaschtisch;
    }
    // End setSanitaerWaschtisch



    /**
     * Sets the property sanitaerWcSpar
     *
     * @param  number  $sanitaerWcSpar - Spuelung WC Spartaste in l
     * @return
     */
    public function setSanitaerWcSpar($sanitaerWcSpar = null)
    {
        $this->sanitaerWcSpar = $sanitaerWcSpar;
    }
    // End setSanitaerWcSpar


    /**
     * Sets the property sanitaerWc
     *
     * @param  number  $sanitaerWc - Spuelung WC in l
     * @return
     */
    public function setSanitaerWc($sanitaerWc = null)
    {
        $this->sanitaerWc = $sanitaerWc;
    }
    // End setSanitaerWc


    /**
     * Sets the property sanitaerUrinal
     *
     * @param  number  $sanitaerUrinal - Urinal
     * @return
     */
    public function setSanitaerUrinal($sanitaerUrinal = null)
    {
        $this->sanitaerUrinal = $sanitaerUrinal;
    }
    // End setSanitaerUrinal


    /**
     * Sets the property sanitaerDusche
     *
     * @param  number  $sanitaerDusche - Armatur Dusche
     * @return
     */
    public function setSanitaerDusche($sanitaerDusche = null)
    {
        $this->sanitaerDusche = $sanitaerDusche;
    }
    // End setSanitaerDusche


    /**
     * Sets the property sanitaerTeekueche
     *
     * @param  number  $sanitaerTeekueche - Armatur Teeküche
     * @return
     */
    public function setSanitaerTeekueche($sanitaerTeekueche = null)
    {
        $this->sanitaerTeekueche = $sanitaerTeekueche;
    }
    // End setSanitaerTeekueche


    /**
     * Sets the property reinigungSanitaer
     *
     * @param  number  $reinigungSanitaer - Reinigung Santitaerbereiche
     * @return
     */
    public function setReinigungSanitaer($reinigungSanitaer = null)
    {
        $this->reinigungSanitaer = $reinigungSanitaer;
    }
    // End setReinigungSanitaer


    /**
     * Sets the property reinigungLobby
     *
     * @param  number  $reinigungLobby - Reinigung Lobby
     * @return
     */
    public function setReinigungLobby($reinigungLobby = null)
    {
        $this->reinigungLobby = $reinigungLobby;
    }
    // End setReinigungLobby


    /**
     * Sets the property reinigungVerkehrsflaeche
     *
     * @param  number  $reinigungVerkehrsflaeche - Reinigung Verkehrsflaeche
     * @return
     */
    public function setReinigungVerkehrsflaeche($reinigungVerkehrsflaeche = null)
    {
        $this->reinigungVerkehrsflaeche = $reinigungVerkehrsflaeche;
    }
    // End setReinigungVerkehrsflaeche


    /**
     * Sets the property reinigungBuero
     *
     * @param  number  $reinigungBuero - Reinigung Buero
     * @return
     */
    public function setReinigungBuero($reinigungBuero = null)
    {
        $this->reinigungBuero = $reinigungBuero;
    }
    // End setReinigungBuero


    /**
     * Sets the property reinigungKeller
     *
     * @param  number  $reinigungKeller - Reinigung Nebenraum
     * @return
     */
    public function setReinigungKeller($reinigungKeller = null)
    {
        $this->reinigungKeller = $reinigungKeller;
    }
    // End setReinigungKeller


    /**
     * Sets the property dach1Flaeche
     *
     * @param  number  $dach1Flaeche - Dach1 Flaeche
     * @return
     */
    public function setDach1Flaeche($dach1Flaeche = null)
    {
        $this->dach1Flaeche = $dach1Flaeche;
    }
    // End setDach1Flaeche


    /**
     * Sets the property dach1Ertragsbeiwert
     *
     * @param  number  $dach1Ertragsbeiwert - Ertragsbeiwert Dach1
     * @return
     */
    public function setDach1Ertragsbeiwert($dach1Ertragsbeiwert = null)
    {
        $this->dach1Ertragsbeiwert = $dach1Ertragsbeiwert;
    }
    // End setDach1Ertragsbeiwert


    /**
     * Sets the property dach2Flaeche
     *
     * @param  number  $dach2Flaeche - Dach2 Flaeche
     * @return
     */
    public function setDach2Flaeche($dach2Flaeche = null)
    {
        $this->dach2Flaeche = $dach2Flaeche;
    }
    // End setDach2Flaeche


    /**
     * Sets the property dach2Ertragsbeiwert
     *
     * @param  number  $dach2Ertragsbeiwert - Ertragsbeiwert Dach2
     * @return
     */
    public function setDach2Ertragsbeiwert($dach2Ertragsbeiwert = null)
    {
        $this->dach2Ertragsbeiwert = $dach2Ertragsbeiwert;
    }
    // End setDach2Ertragsbeiwert


    /**
     * Sets the property dach3Flaeche
     *
     * @param  number  $dach3Flaeche - Dach3 Flaeche
     * @return
     */
    public function setDach3Flaeche($dach3Flaeche = null)
    {
        $this->dach3Flaeche = $dach3Flaeche;
    }
    // End setDach3Flaeche


    /**
     * Sets the property dach3Ertragsbeiwert
     *
     * @param  number  $dach3Ertragsbeiwert - Ertragsbeiwert Dach3
     * @return
     */
    public function setDach3Ertragsbeiwert($dach3Ertragsbeiwert = null)
    {
        $this->dach3Ertragsbeiwert = $dach3Ertragsbeiwert;
    }
    // End setDach3Ertragsbeiwert


    /**
     * Sets the property dach4Flaeche
     *
     * @param  number  $dach4Flaeche - Dach4 Flaeche
     * @return
     */
    public function setDach4Flaeche($dach4Flaeche = null)
    {
        $this->dach4Flaeche = $dach4Flaeche;
    }
    // End setDach4Flaeche


    /**
     * Sets the property dach4Ertragsbeiwert
     *
     * @param  number  $dach4Ertragsbeiwert - Ertragsbeiwert Dach4
     * @return
     */
    public function setDach4Ertragsbeiwert($dach4Ertragsbeiwert = null)
    {
        $this->dach4Ertragsbeiwert = $dach4Ertragsbeiwert;
    }
    // End setDach4Ertragsbeiwert


    /**
     * Sets the property niederschlagVersickert
     *
     * @param  number  $niederschlagVersickert - Menge des auf dem Grundstueck versickerten Niederschlagswassers
     * @return
     */
    public function setNiederschlagVersickert($niederschlagVersickert = null)
    {
        $this->niederschlagVersickert = $niederschlagVersickert;
    }
    // End setNiederschlagVersickert


    /**
     * Sets the property niederschlagGenutzt
     *
     * @param  number  $niederschlagGenutzt - Menge des genutzten Niederschlagswasser
     * @return
     */
    public function setNiederschlagGenutzt($niederschlagGenutzt = null)
    {
        $this->niederschlagGenutzt = $niederschlagGenutzt;
    }
    // End setNiederschlagGenutzt

    /**
     * Sets the property niederschlagGenutzt
     *
     * @param  number  $niederschlagGenutzt - Menge des genutzten Niederschlagswasser
     * @return
     */
    public function setNiederschlagGenutztOhneWandlung($niederschlagGenutzt = null)
    {
        $this->niederschlagGenutztOhneWandlung = $niederschlagGenutzt;
    }

    /**
     * Sets the property niederschlagGenutzt
     *
     * @param  number  $niederschlagGenutzt - Menge des genutzten Niederschlagswasser
     * @return
     */
    public function setNiederschlagKanalisation($niederschlagGenutzt = null)
    {
        $this->niederschlagKanalisation = $niederschlagGenutzt;
    }

    /**
     * Sets the property brauchwasser
     *
     * @param  number  $brauchwasser - Menge des genutzten Brauchwassers
     * @return
     */
    public function setBrauchwasser($brauchwasser = null)
    {
        $this->brauchwasser = $brauchwasser;
    }
    // End setBrauchwasser


    /**
     * Sets the property brauchwasserGereinigt
     *
     * @param  number  $brauchwasserGereinigt - Menge des auf dem Grundstueck gereinigten Brauchwassers
     * @return
     */
    public function setBrauchwasserGereinigt($brauchwasserGereinigt = null)
    {
        $this->brauchwasserGereinigt = $brauchwasserGereinigt;
    }
    // End setBrauchwasserGereinigt


    /**
     * Returns the property projectId
     *
     * @return integer
     */
    public function getProjectId()
    {
        return $this->projectId;
    }
    // End getProjectId


    /**
     * Returns the associated ElcaProject by property projectId
     *
     * @param  boolean  $force
     * @return ElcaProject
     */
    public function getProject($force = false)
    {
        return ElcaProject::findById($this->projectId, $force);
    }
    // End getProject


    /**
     * Returns the property niederschlagsmenge
     *
     * @return number
     */
    public function getNiederschlagsmenge()
    {
        return $this->niederschlagsmenge;
    }
    // End getNiederschlagsmenge


    /**
     * Returns the property anzahlPersonen
     *
     * @return integer
     */
    public function getAnzahlPersonen()
    {
        return $this->anzahlPersonen;
    }
    // End getAnzahlPersonen


    /**
     * Returns the property sanitaerWaschtisch
     *
     * @return number
     */
    public function getSanitaerWaschtisch()
    {
        return $this->sanitaerWaschtisch;
    }
    // End getSanitaerWaschtisch


    /**
     * Returns the property sanitaerWcSpar
     *
     * @return number
     */
    public function getSanitaerWcSpar()
    {
        return $this->sanitaerWcSpar;
    }
    // End getSanitaerWcSpar


    /**
     * Returns the property sanitaerWc
     *
     * @return number
     */
    public function getSanitaerWc()
    {
        return $this->sanitaerWc;
    }
    // End getSanitaerWc


    /**
     * Returns the property sanitaerUrinal
     *
     * @return number
     */
    public function getSanitaerUrinal()
    {
        return $this->sanitaerUrinal;
    }
    // End getSanitaerUrinal


    /**
     * Returns the property sanitaerDusche
     *
     * @return number
     */
    public function getSanitaerDusche()
    {
        return $this->sanitaerDusche;
    }
    // End getSanitaerDusche


    /**
     * Returns the property sanitaerTeekueche
     *
     * @return number
     */
    public function getSanitaerTeekueche()
    {
        return $this->sanitaerTeekueche;
    }
    // End getSanitaerTeekueche


    /**
     * Returns the property reinigungSanitaer
     *
     * @return number
     */
    public function getReinigungSanitaer()
    {
        return $this->reinigungSanitaer;
    }
    // End getReinigungSanitaer


    /**
     * Returns the property reinigungLobby
     *
     * @return number
     */
    public function getReinigungLobby()
    {
        return $this->reinigungLobby;
    }
    // End getReinigungLobby


    /**
     * Returns the property reinigungVerkehrsflaeche
     *
     * @return number
     */
    public function getReinigungVerkehrsflaeche()
    {
        return $this->reinigungVerkehrsflaeche;
    }
    // End getReinigungVerkehrsflaeche


    /**
     * Returns the property reinigungBuero
     *
     * @return number
     */
    public function getReinigungBuero()
    {
        return $this->reinigungBuero;
    }
    // End getReinigungBuero


    /**
     * Returns the property reinigungKeller
     *
     * @return number
     */
    public function getReinigungKeller()
    {
        return $this->reinigungKeller;
    }
    // End getReinigungKeller


    /**
     * Returns the property dach1Flaeche
     *
     * @return number
     */
    public function getDach1Flaeche()
    {
        return $this->dach1Flaeche;
    }
    // End getDach1Flaeche


    /**
     * Returns the property dach1Ertragsbeiwert
     *
     * @return number
     */
    public function getDach1Ertragsbeiwert()
    {
        return $this->dach1Ertragsbeiwert;
    }
    // End getDach1Ertragsbeiwert


    /**
     * Returns the property dach2Flaeche
     *
     * @return number
     */
    public function getDach2Flaeche()
    {
        return $this->dach2Flaeche;
    }
    // End getDach2Flaeche


    /**
     * Returns the property dach2Ertragsbeiwert
     *
     * @return number
     */
    public function getDach2Ertragsbeiwert()
    {
        return $this->dach2Ertragsbeiwert;
    }
    // End getDach2Ertragsbeiwert


    /**
     * Returns the property dach3Flaeche
     *
     * @return number
     */
    public function getDach3Flaeche()
    {
        return $this->dach3Flaeche;
    }
    // End getDach3Flaeche


    /**
     * Returns the property dach3Ertragsbeiwert
     *
     * @return number
     */
    public function getDach3Ertragsbeiwert()
    {
        return $this->dach3Ertragsbeiwert;
    }
    // End getDach3Ertragsbeiwert


    /**
     * Returns the property dach4Flaeche
     *
     * @return number
     */
    public function getDach4Flaeche()
    {
        return $this->dach4Flaeche;
    }
    // End getDach4Flaeche


    /**
     * Returns the property dach4Ertragsbeiwert
     *
     * @return number
     */
    public function getDach4Ertragsbeiwert()
    {
        return $this->dach4Ertragsbeiwert;
    }
    // End getDach4Ertragsbeiwert


    /**
     * Returns the property niederschlagVersickert
     *
     * @return number
     */
    public function getNiederschlagVersickert()
    {
        return $this->niederschlagVersickert;
    }
    // End getNiederschlagVersickert


    /**
     * Returns the property niederschlagGenutzt
     *
     * @return number
     */
    public function getNiederschlagGenutzt()
    {
        return $this->niederschlagGenutzt;
    }
    // End getNiederschlagGenutzt

    /**
     * Returns the property niederschlagGenutzt
     *
     * @return number
     */
    public function getNiederschlagGenutztOhneWandlung()
    {
        return $this->niederschlagGenutztOhneWandlung;
    }

    /**
     * Returns the property niederschlagGenutzt
     *
     * @return number
     */
    public function getNiederschlagKanalisation()
    {
        return $this->niederschlagKanalisation;
    }

    /**
     * Returns the property brauchwasser
     *
     * @return number
     */
    public function getBrauchwasser()
    {
        return $this->brauchwasser;
    }
    // End getBrauchwasser


    /**
     * Returns the property brauchwasserGereinigt
     *
     * @return number
     */
    public function getBrauchwasserGereinigt()
    {
        return $this->brauchwasserGereinigt;
    }
    // End getBrauchwasserGereinigt

    /**
     * Checks, if the object exists
     *
     * @param  integer  $projectId - projectId
     * @param  boolean  $force    - Bypass caching
     * @return boolean
     */
    public static function exists($projectId, $force = false)
    {
        return self::findByProjectId($projectId, $force)->isInitialized();
    }
    // End exists


    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET niederschlagsmenge       = :niederschlagsmenge
                             , anzahl_personen          = :anzahlPersonen
                             , sanitaer_waschtisch      = :sanitaerWaschtisch
                             , sanitaer_wc_spar         = :sanitaerWcSpar
                             , sanitaer_wc              = :sanitaerWc
                             , sanitaer_urinal          = :sanitaerUrinal
                             , sanitaer_dusche          = :sanitaerDusche
                             , sanitaer_teekueche       = :sanitaerTeekueche
                             , reinigung_sanitaer       = :reinigungSanitaer
                             , reinigung_lobby          = :reinigungLobby
                             , reinigung_verkehrsflaeche = :reinigungVerkehrsflaeche
                             , reinigung_buero          = :reinigungBuero
                             , reinigung_keller         = :reinigungKeller
                             , dach1_flaeche            = :dach1Flaeche
                             , dach1_ertragsbeiwert     = :dach1Ertragsbeiwert
                             , dach2_flaeche            = :dach2Flaeche
                             , dach2_ertragsbeiwert     = :dach2Ertragsbeiwert
                             , dach3_flaeche            = :dach3Flaeche
                             , dach3_ertragsbeiwert     = :dach3Ertragsbeiwert
                             , dach4_flaeche            = :dach4Flaeche
                             , dach4_ertragsbeiwert     = :dach4Ertragsbeiwert
                             , niederschlag_versickert  = :niederschlagVersickert
                             , niederschlag_genutzt     = :niederschlagGenutzt
                             , niederschlag_genutzt_ohne_wandlung     = :niederschlagGenutztOhneWandlung
                             , niederschlag_kanalisation     = :niederschlagKanalisation
                             , brauchwasser             = :brauchwasser
                             , brauchwasser_gereinigt   = :brauchwasserGereinigt
                         WHERE project_id = :projectId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['projectId'               => $this->projectId,
                                        'niederschlagsmenge'      => $this->niederschlagsmenge,
                                        'anzahlPersonen'          => $this->anzahlPersonen,
                                        'sanitaerWaschtisch'      => $this->sanitaerWaschtisch,
                                        'sanitaerWcSpar'          => $this->sanitaerWcSpar,
                                        'sanitaerWc'              => $this->sanitaerWc,
                                        'sanitaerUrinal'          => $this->sanitaerUrinal,
                                        'sanitaerDusche'          => $this->sanitaerDusche,
                                        'sanitaerTeekueche'       => $this->sanitaerTeekueche,
                                        'reinigungSanitaer'       => $this->reinigungSanitaer,
                                        'reinigungLobby'          => $this->reinigungLobby,
                                        'reinigungVerkehrsflaeche' => $this->reinigungVerkehrsflaeche,
                                        'reinigungBuero'          => $this->reinigungBuero,
                                        'reinigungKeller'         => $this->reinigungKeller,
                                        'dach1Flaeche'            => $this->dach1Flaeche,
                                        'dach1Ertragsbeiwert'     => $this->dach1Ertragsbeiwert,
                                        'dach2Flaeche'            => $this->dach2Flaeche,
                                        'dach2Ertragsbeiwert'     => $this->dach2Ertragsbeiwert,
                                        'dach3Flaeche'            => $this->dach3Flaeche,
                                        'dach3Ertragsbeiwert'     => $this->dach3Ertragsbeiwert,
                                        'dach4Flaeche'            => $this->dach4Flaeche,
                                        'dach4Ertragsbeiwert'     => $this->dach4Ertragsbeiwert,
                                        'niederschlagVersickert'  => $this->niederschlagVersickert,
                                        'niederschlagGenutzt'     => $this->niederschlagGenutzt,
                                        'niederschlagGenutztOhneWandlung'     => $this->niederschlagGenutztOhneWandlung,
                                   'niederschlagKanalisation'     => $this->niederschlagKanalisation,
                                        'brauchwasser'            => $this->brauchwasser,
                                        'brauchwasserGereinigt'   => $this->brauchwasserGereinigt]
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
                              WHERE project_id = :projectId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  ['projectId' => $this->projectId]);
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
    // End getColumnTypes


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {

        $sql = sprintf("INSERT INTO %s (project_id, niederschlagsmenge, anzahl_personen, sanitaer_waschtisch, sanitaer_wc_spar, sanitaer_wc, sanitaer_urinal, sanitaer_dusche, sanitaer_teekueche, reinigung_sanitaer, reinigung_lobby, reinigung_verkehrsflaeche, reinigung_buero, reinigung_keller, dach1_flaeche, dach1_ertragsbeiwert, dach2_flaeche, dach2_ertragsbeiwert, dach3_flaeche, dach3_ertragsbeiwert, dach4_flaeche, dach4_ertragsbeiwert, niederschlag_versickert, niederschlag_genutzt, niederschlag_genutzt_ohne_wandlung, niederschlag_kanalisation, brauchwasser, brauchwasser_gereinigt)
                               VALUES  (:projectId, :niederschlagsmenge, :anzahlPersonen, :sanitaerWaschtisch, :sanitaerWcSpar, :sanitaerWc, :sanitaerUrinal, :sanitaerDusche, :sanitaerTeekueche, :reinigungSanitaer, :reinigungLobby, :reinigungVerkehrsflaeche, :reinigungBuero, :reinigungKeller, :dach1Flaeche, :dach1Ertragsbeiwert, :dach2Flaeche, :dach2Ertragsbeiwert, :dach3Flaeche, :dach3Ertragsbeiwert, :dach4Flaeche, :dach4Ertragsbeiwert, :niederschlagVersickert, :niederschlagGenutzt, :niederschlagGenutztOhneWandlung, :niederschlagKanalisation, :brauchwasser, :brauchwasserGereinigt)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['projectId'               => $this->projectId,
                                        'niederschlagsmenge'      => $this->niederschlagsmenge,
                                        'anzahlPersonen'          => $this->anzahlPersonen,
                                        'sanitaerWaschtisch'      => $this->sanitaerWaschtisch,
                                        'sanitaerWcSpar'          => $this->sanitaerWcSpar,
                                        'sanitaerWc'              => $this->sanitaerWc,
                                        'sanitaerUrinal'          => $this->sanitaerUrinal,
                                        'sanitaerDusche'          => $this->sanitaerDusche,
                                        'sanitaerTeekueche'       => $this->sanitaerTeekueche,
                                        'reinigungSanitaer'       => $this->reinigungSanitaer,
                                        'reinigungLobby'          => $this->reinigungLobby,
                                        'reinigungVerkehrsflaeche' => $this->reinigungVerkehrsflaeche,
                                        'reinigungBuero'          => $this->reinigungBuero,
                                        'reinigungKeller'         => $this->reinigungKeller,
                                        'dach1Flaeche'            => $this->dach1Flaeche,
                                        'dach1Ertragsbeiwert'     => $this->dach1Ertragsbeiwert,
                                        'dach2Flaeche'            => $this->dach2Flaeche,
                                        'dach2Ertragsbeiwert'     => $this->dach2Ertragsbeiwert,
                                        'dach3Flaeche'            => $this->dach3Flaeche,
                                        'dach3Ertragsbeiwert'     => $this->dach3Ertragsbeiwert,
                                        'dach4Flaeche'            => $this->dach4Flaeche,
                                        'dach4Ertragsbeiwert'     => $this->dach4Ertragsbeiwert,
                                        'niederschlagVersickert'  => $this->niederschlagVersickert,
                                        'niederschlagGenutzt'     => $this->niederschlagGenutzt,
                                   'niederschlagGenutztOhneWandlung'     => $this->niederschlagGenutztOhneWandlung,
                                   'niederschlagKanalisation'     => $this->niederschlagKanalisation,
                                   'brauchwasser'            => $this->brauchwasser,
                                        'brauchwasserGereinigt'   => $this->brauchwasserGereinigt]
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
        $this->projectId                = (int)$DO->project_id;
        $this->niederschlagsmenge       = $DO->niederschlagsmenge;
        $this->anzahlPersonen           = $DO->anzahl_personen;
        $this->sanitaerWaschtisch       = $DO->sanitaer_waschtisch;
        $this->sanitaerWcSpar           = $DO->sanitaer_wc_spar;
        $this->sanitaerWc               = $DO->sanitaer_wc;
        $this->sanitaerUrinal           = $DO->sanitaer_urinal;
        $this->sanitaerDusche           = $DO->sanitaer_dusche;
        $this->sanitaerTeekueche        = $DO->sanitaer_teekueche;
        $this->reinigungSanitaer        = $DO->reinigung_sanitaer;
        $this->reinigungLobby           = $DO->reinigung_lobby;
        $this->reinigungVerkehrsflaeche = $DO->reinigung_verkehrsflaeche;
        $this->reinigungBuero           = $DO->reinigung_buero;
        $this->reinigungKeller          = $DO->reinigung_keller;
        $this->dach1Flaeche             = $DO->dach1_flaeche;
        $this->dach1Ertragsbeiwert      = $DO->dach1_ertragsbeiwert;
        $this->dach2Flaeche             = $DO->dach2_flaeche;
        $this->dach2Ertragsbeiwert      = $DO->dach2_ertragsbeiwert;
        $this->dach3Flaeche             = $DO->dach3_flaeche;
        $this->dach3Ertragsbeiwert      = $DO->dach3_ertragsbeiwert;
        $this->dach4Flaeche             = $DO->dach4_flaeche;
        $this->dach4Ertragsbeiwert      = $DO->dach4_ertragsbeiwert;
        $this->niederschlagVersickert   = $DO->niederschlag_versickert;
        $this->niederschlagGenutzt      = $DO->niederschlag_genutzt;
        $this->niederschlagGenutztOhneWandlung      = $DO->niederschlag_genutzt_ohne_wandlung;
        $this->niederschlagKanalisation = $DO->niederschlag_kanalisation;
        $this->brauchwasser             = $DO->brauchwasser;
        $this->brauchwasserGereinigt    = $DO->brauchwasser_gereinigt;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End BnbWater