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

namespace Lcc\Db;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\Log;
use Elca\Db\ElcaProjectVariant;
use Exception;
use Lcc\LccModule;
use PDO;
/**
 * {BLIBSLICENCE}
 *
 * -
 *
 * @package    -
 * @class      LccProjectVersion
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2013 BEIBOB Medienfreunde
 *
 * $Id$
 */
class LccProjectVersion extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'lcc.project_versions';

    /**
     * Stored procedure to compute lcc results
     */
    const PROCEDURE_COMPUTE_GENERAL_RESULTS = 'lcc.compute_general_results';
    const PROCEDURE_COMPUTE_DETAILED_RESULTS = 'lcc.compute_detailed_results';

    /**
     * Alternative percentage kgu
     */
    const KGU_ALTERNATIVE_PERCENTAGE = 0.6;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * projectVariantId
     */
    private $projectVariantId;
    private $calcMethod;

    /**
     * versionId
     */
    private $versionId;

    /**
     * Sonderbedingungen Kategorie 1 oder 2
     */
    private $category;

    /**
     * Bauwerk- Baukonstruktion
     */
    private $costs300;

    /**
     * Bauwerk-Technische Anlagen
     */
    private $costs400;

    /**
     * Technische Anlagen in Aussenanlagen
     */
    private $costs500;

    /**
     * kgu alternative value for kg300
     */
    private $kgu300Alt;

    /**
     * kgu alternative value for kg400
     */
    private $kgu400Alt;

    /**
     * kgu alternative value for kg500
     */
    private $kgu500Alt;

    /**
     * Primary key
     */
    private static $primaryKey = ['projectVariantId', 'calcMethod'];

    /**
     * Column types
     */
    private static $columnTypes = ['projectVariantId' => PDO::PARAM_INT,
                                   'calcMethod'       => PDO::PARAM_INT,
                                        'versionId'        => PDO::PARAM_INT,
                                        'category'         => PDO::PARAM_INT,
                                        'costs300'         => PDO::PARAM_STR,
                                        'costs400'         => PDO::PARAM_STR,
                                        'costs500'         => PDO::PARAM_STR,
                                        'kgu300Alt'        => PDO::PARAM_STR,
                                        'kgu400Alt'        => PDO::PARAM_STR,
                                        'kgu500Alt'        => PDO::PARAM_STR];

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates the object
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  integer $versionId        - versionId
     * @param  integer $category         - Sonderbedingungen Kategorie 1 oder 2
     * @param  number  $costs300         - Bauwerk- Baukonstruktion
     * @param  number  $costs400         - Bauwerk-Technische Anlagen
     * @param  number  $costs500         - Technische Anlagen in Aussenanlagen
     * @param null     $kgu300Alt
     * @param null     $kgu400Alt
     * @param null     $kgu500Alt
     * @return LccProjectVersion
     */
    public static function create($projectVariantId, $calcMethod, $versionId, $category = 1, $costs300 = null, $costs400 = null, $costs500 = null, $kgu300Alt = null, $kgu400Alt = null, $kgu500Alt = null)
    {
        $LccProjectVersion = new LccProjectVersion();
        $LccProjectVersion->setProjectVariantId($projectVariantId);
        $LccProjectVersion->setCalcMethod($calcMethod);
        $LccProjectVersion->setVersionId($versionId);
        $LccProjectVersion->setCategory($category);
        $LccProjectVersion->setCosts300($costs300);
        $LccProjectVersion->setCosts400($costs400);
        $LccProjectVersion->setCosts500($costs500);
        $LccProjectVersion->setKgu300Alt($kgu300Alt);
        $LccProjectVersion->setKgu400Alt($kgu400Alt);
        $LccProjectVersion->setKgu500Alt($kgu500Alt);

        if($LccProjectVersion->getValidator()->isValid())
            $LccProjectVersion->insert();

        return $LccProjectVersion;
    }
    // End create

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits a `LccProjectVersion' by its primary key
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  boolean $force            - Bypass caching
     * @return LccProjectVersion
     * @throws \Beibob\Blibs\Exception
     */
    public static function findByPK($projectVariantId, $calcMethod, $force = false)
    {
        if(!$projectVariantId)
            return new LccProjectVersion();

        $sql = sprintf("SELECT project_variant_id
                             , calc_method
                             , version_id
                             , category
                             , costs_300
                             , costs_400
                             , costs_500
                             , kgu300_alt
                             , kgu400_alt
                             , kgu500_alt
                          FROM %s
                         WHERE (project_variant_id, calc_method) = (:projectVariantId, :calcMethod)"
                       , self::TABLE_NAME
                       );

        return self::findBySql(get_class(), $sql, [
            'projectVariantId' => $projectVariantId,
            'calcMethod'       => $calcMethod,
        ], $force);
    }
    // End findByProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Compute lcc
     */
    public function computeLcc()
    {
        if(!$this->isInitialized())
            return false;

        $ProjectVariant = $this->getProjectVariant();
        $Project = $ProjectVariant->getProject();

        Log::getInstance()->notice('Computing LCC '. ($this->getCalcMethod() === LccModule::CALC_METHOD_GENERAL? 'general' : 'detailed') .' for Project `'. $Project->getName().'\' ('.$Project->getId().'), Variant `'. $ProjectVariant->getName().'\' ('.$ProjectVariant->getId().')', __METHOD__);

        $this->Dbh->query(
            sprintf(
                'SELECT %s(%d::int)',
                ($this->calcMethod == LccModule::CALC_METHOD_GENERAL
                    ? self::PROCEDURE_COMPUTE_GENERAL_RESULTS
                    : self::PROCEDURE_COMPUTE_DETAILED_RESULTS),
                $this->projectVariantId
            )
        );

        return true;
    }
    // End computeLcc

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy of this version for a new projectVariantId
     *
     * @param $projectVariantId
     * @return LccProjectVersion
     * @throws Exception
     */
    public function copy($projectVariantId)
    {
        if(!$this->isInitialized())
            return new LccProjectVersion();

        try
        {
            $this->Dbh->begin();

            $Copy = self::create($projectVariantId,
                                 $this->calcMethod,
                                 $this->versionId,
                                 $this->category,
                                 $this->costs300,
                                 $this->costs400,
                                 $this->costs500,
                                 $this->kgu300Alt,
                                 $this->kgu400Alt,
                                 $this->kgu500Alt);

            /**
             * Copy costs
             */
            foreach(LccProjectCostSet::find([
                'project_variant_id' => $this->projectVariantId,
                'calc_method' => $this->calcMethod
            ]) as $ProjectCost)
                $ProjectCost->copy($projectVariantId);

            /**
             * Compute lcc for this new variant
             */
            $Copy->computeLcc();

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
     * Sets the property versionId
     *
     * @param  integer  $versionId - versionId
     * @return
     */
    public function setVersionId($versionId)
    {
        if(!$this->getValidator()->assertNotEmpty('versionId', $versionId))
            return;

        $this->versionId = (int)$versionId;
    }
    // End setVersionId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property category
     *
     * @param  integer  $category - Sonderbedingungen Kategorie 1 oder 2
     * @return
     */
    public function setCategory($category = 1)
    {
        $this->category = (int)$category;
    }
    // End setCategory

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costs300
     *
     * @param  number  $costs300 - Bauwerk- Baukonstruktion
     * @return
     */
    public function setCosts300($costs300 = null)
    {
        $this->costs300 = $costs300;
    }
    // End setCosts300

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costs400
     *
     * @param  number  $costs400 - Bauwerk-Technische Anlagen
     * @return
     */
    public function setCosts400($costs400 = null)
    {
        $this->costs400 = $costs400;
    }
    // End setCosts400

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property costs500
     *
     * @param  number  $costs500 - Technische Anlagen in Aussenanlagen
     * @return
     */
    public function setCosts500($costs500 = null)
    {
        $this->costs500 = $costs500;
    }
    // End setCosts500

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property kgu300Alt
     *
     * @param  number  $kgu300Alt - kgu alternative value for kg300
     * @return
     */
    public function setKgu300Alt($kgu300Alt = null)
    {
        $this->kgu300Alt = $kgu300Alt;
    }
    // End setKgu300Alt

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property kgu400Alt
     *
     * @param  number  $kgu400Alt - kgu alternative value for kg400
     * @return
     */
    public function setKgu400Alt($kgu400Alt = null)
    {
        $this->kgu400Alt = $kgu400Alt;
    }
    // End setKgu400Alt

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the property kgu500Alt
     *
     * @param  number  $kgu500Alt - kgu alternative value for kg500
     * @return
     */
    public function setKgu500Alt($kgu500Alt = null)
    {
        $this->kgu500Alt = $kgu500Alt;
    }
    // End setKgu500Alt

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
     * Returns the property versionId
     *
     * @return integer
     */
    public function getVersionId()
    {
        return $this->versionId;
    }
    // End getVersionId

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the associated LccVersion by property versionId
     *
     * @param  boolean  $force
     * @return LccVersion
     */
    public function getVersion($force = false)
    {
        return LccVersion::findById($this->versionId, $force);
    }
    // End getVersion

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property category
     *
     * @return integer
     */
    public function getCategory()
    {
        return $this->category;
    }
    // End getCategory

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property costs300
     *
     * @return number
     */
    public function getCosts300()
    {
        return $this->costs300;
    }
    // End getCosts300

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property costs400
     *
     * @return number
     */
    public function getCosts400()
    {
        return $this->costs400;
    }
    // End getCosts400

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property costs500
     *
     * @return number
     */
    public function getCosts500()
    {
        return $this->costs500;
    }
    // End getCosts500

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property kgu300Alt
     *
     * @return number
     */
    public function getKgu300Alt()
    {
        return $this->kgu300Alt;
    }
    // End getKgu300Alt

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property kgu400Alt
     *
     * @return number
     */
    public function getKgu400Alt()
    {
        return $this->kgu400Alt;
    }
    // End getKgu400Alt

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the property kgu500Alt
     *
     * @return number
     */
    public function getKgu500Alt()
    {
        return $this->kgu500Alt;
    }
    // End getKgu500Alt

    /**
     * @return mixed
     */
    public function getCalcMethod()
    {
        return $this->calcMethod;
    }

    /**
     * @param mixed $calcMethod
     */
    public function setCalcMethod($calcMethod)
    {
        $this->calcMethod = $calcMethod;
    }

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks, if the object exists
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param          $calcMethod
     * @param  boolean $force            - Bypass caching
     * @return bool
     */
    public static function exists($projectVariantId, $calcMethod, $force = false)
    {
        return self::findByPK($projectVariantId, $calcMethod, $force)->isInitialized();
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
                           SET version_id       = :versionId
                             , category         = :category
                             , costs_300        = :costs300
                             , costs_400        = :costs400
                             , costs_500        = :costs500
                             , kgu300_alt       = :kgu300Alt
                             , kgu400_alt       = :kgu400Alt
                             , kgu500_alt       = :kgu500Alt
                         WHERE (project_variant_id, calc_method) = (:projectVariantId, :calcMethod)"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                   'calcMethod' => $this->calcMethod,
                                        'versionId'       => $this->versionId,
                                        'category'        => $this->category,
                                        'costs300'        => $this->costs300,
                                        'costs400'        => $this->costs400,
                                        'costs500'        => $this->costs500,
                                        'kgu300Alt'       => $this->kgu300Alt,
                                        'kgu400Alt'       => $this->kgu400Alt,
                                        'kgu500Alt'       => $this->kgu500Alt]
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
                              WHERE (project_variant_id, calc_method) = (:projectVariantId, :calcMethod)"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql, [
            'projectVariantId' => $this->projectVariantId,
            'calcMethod' => $this->calcMethod,
            ]
        );
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

        $primaryKey = [];

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

        $sql = sprintf("INSERT INTO %s (project_variant_id, calc_method, version_id, category, costs_300, costs_400, costs_500, kgu300_alt, kgu400_alt, kgu500_alt)
                               VALUES  (:projectVariantId, :calcMethod, :versionId, :category, :costs300, :costs400, :costs500, :kgu300Alt, :kgu400Alt, :kgu500Alt)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  ['projectVariantId' => $this->projectVariantId,
                                   'calcMethod' => $this->calcMethod,
                                   'versionId'       => $this->versionId,
                                   'category'        => $this->category,
                                   'costs300'        => $this->costs300,
                                   'costs400'        => $this->costs400,
                                   'costs500'        => $this->costs500,
                                   'kgu300Alt'       => $this->kgu300Alt,
                                   'kgu400Alt'       => $this->kgu400Alt,
                                   'kgu500Alt'       => $this->kgu500Alt]
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
        $this->calcMethod       = $DO->calc_method;
        $this->versionId        = (int)$DO->version_id;
        $this->category         = (int)$DO->category;
        $this->costs300         = $DO->costs_300;
        $this->costs400         = $DO->costs_400;
        $this->costs500         = $DO->costs_500;
        $this->kgu300Alt        = $DO->kgu300_alt;
        $this->kgu400Alt        = $DO->kgu400_alt;
        $this->kgu500Alt        = $DO->kgu500_alt;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class LccProjectVersion