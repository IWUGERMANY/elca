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

use PDO;
use Exception;
use Beibob\Blibs\DbObject;
/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectLocation extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_locations';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * street
     */
    private $street;

    /**
     * postcode
     */
    private $postcode;

    /**
     * city
     */
    private $city;

    /**
     * country
     */
    private $country;

    /**
     * geo location
     */
    private $geoLocation;

    /**
     * Primary key
     */
    private static $primaryKey = array('projectVariantId');

    /**
     * Column types
     */
    private static $columnTypes = array('projectVariantId' => PDO::PARAM_INT,
                                        'street'           => PDO::PARAM_STR,
                                        'postcode'         => PDO::PARAM_STR,
                                        'city'             => PDO::PARAM_STR,
                                        'country'          => PDO::PARAM_STR,
                                        'geoLocation'      => PDO::PARAM_STR);

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  string   $street          - street
     * @param  string   $postcode        - postcode
     * @param  string   $city            - city
     * @param  string   $country         - country
     * @param  string   $geoLocation     - geo location
     */
    public static function create($projectVariantId, $street = null, $postcode = null, $city = null, $country = null, $geoLocation = null)
    {
        $ElcaProjectLocation = new ElcaProjectLocation();
        $ElcaProjectLocation->setProjectVariantId($projectVariantId);
        $ElcaProjectLocation->setStreet($street);
        $ElcaProjectLocation->setPostcode($postcode);
        $ElcaProjectLocation->setCity($city);
        $ElcaProjectLocation->setCountry($country);
        $ElcaProjectLocation->setGeoLocation($geoLocation);

        if($ElcaProjectLocation->getValidator()->isValid())
            $ElcaProjectLocation->insert();

        return $ElcaProjectLocation;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `ElcaProjectLocation' by its primary key
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  boolean  $force           - Bypass caching
     * @return ElcaProjectLocation
     */
    public static function findByProjectVariantId($projectVariantId, $force = false)
    {
        if(!$projectVariantId)
            return new ElcaProjectLocation();

        $sql = sprintf("SELECT project_variant_id
                             , street
                             , postcode
                             , city
                             , country
                             , geo_location
                          FROM %s
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, array('projectVariantId' => $projectVariantId), $force);
    }
    // End findByProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a deep copy from this ProjectLocation
     *
     * @param  int $projectVariantId new project variant id
     * @return ElcaProjectLocation - the new copy object
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized() || !$projectVariantId)
            return new ElcaProjectLocation();

        $Copy = self::create($projectVariantId,
                             $this->street,
                             $this->postcode,
                             $this->city,
                             $this->country,
                             $this->geoLocation
                             );

        return $Copy;
    }
    // End copy

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property projectVariantId
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @return
     */
    public function setProjectVariantId($projectVariantId)
    {
        if(!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId))
            return;

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End setProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property street
     *
     * @param  string   $street - street
     * @return
     */
    public function setStreet($street = null)
    {
        if(!$this->getValidator()->assertMaxLength('street', 250, $street))
            return;

        $this->street = $street;
    }
    // End setStreet

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property postcode
     *
     * @param  string   $postcode - postcode
     * @return
     */
    public function setPostcode($postcode = null)
    {
        if(!$this->getValidator()->assertMaxLength('postcode', 10, $postcode))
            return;

        $this->postcode = $postcode;
    }
    // End setPostcode

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property city
     *
     * @param  string   $city  - city
     * @return
     */
    public function setCity($city = null)
    {
        if(!$this->getValidator()->assertMaxLength('city', 250, $city))
            return;

        $this->city = $city;
    }
    // End setCity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property country
     *
     * @param  string   $country - country
     * @return
     */
    public function setCountry($country = null)
    {
        if(!$this->getValidator()->assertMaxLength('country', 250, $country))
            return;

        $this->country = $country;
    }
    // End setCountry

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property geoLocation
     *
     * @param  string   $geoLocation - geo location
     * @return
     */
    public function setGeoLocation($geoLocation = null)
    {
        $this->geoLocation = $geoLocation;
    }
    // End setGeoLocation

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }
    // End getStreet

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }
    // End getPostcode

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }
    // End getCity

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
    // End getCountry

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property geoLocation
     *
     * @return string
     */
    public function getGeoLocation()
    {
        return $this->geoLocation;
    }
    // End getGeoLocation

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer  $projectVariantId - projectVariantId
     * @param  boolean  $force           - Bypass caching
     * @return boolean
     */
    public static function exists($projectVariantId, $force = false)
    {
        return self::findByProjectVariantId($projectVariantId, $force)->isInitialized();
    }
    // End exists

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf("UPDATE %s
                           SET street           = :street
                             , postcode         = :postcode
                             , city             = :city
                             , country          = :country
                             , geo_location     = :geoLocation
                         WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'street'          => $this->street,
                                        'postcode'        => $this->postcode,
                                        'city'            => $this->city,
                                        'country'         => $this->country,
                                        'geoLocation'     => $this->geoLocation)
                                  );
    }
    // End update

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s
                              WHERE project_variant_id = :projectVariantId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId));
    }
    // End delete

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////

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

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {

        $sql = sprintf("INSERT INTO %s (project_variant_id, street, postcode, city, country, geo_location)
                               VALUES  (:projectVariantId, :street, :postcode, :city, :country, :geoLocation)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('projectVariantId' => $this->projectVariantId,
                                        'street'          => $this->street,
                                        'postcode'        => $this->postcode,
                                        'city'            => $this->city,
                                        'country'         => $this->country,
                                        'geoLocation'     => $this->geoLocation)
                                  );
    }
    // End insert

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the object with row values
     *
     * @param  \stdClass $DO - Data object
     * @return boolean
     */
    protected function initByDataObject(\stdClass $DO = null)
    {
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->street           = $DO->street;
        $this->postcode         = $DO->postcode;
        $this->city             = $DO->city;
        $this->country          = $DO->country;
        $this->geoLocation      = $DO->geo_location;

        /**
         * Set extensions
         */
    }
    // End initByDataObject

    //////////////////////////////////////////////////////////////////////////////////////
}
// End class ElcaProjectLocation