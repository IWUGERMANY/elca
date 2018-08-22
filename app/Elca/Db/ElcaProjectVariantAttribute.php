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
use PDO;

/**
 * Project attribute
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectVariantAttribute extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.project_variant_attributes';

    /**
     * Idents
     */
    const IDENT_LCA_BENCHMARK_COMMENT = 'elca.lca.benchmark.comment';

    /**
     * Primary key
     */
    private static $primaryKey = array('id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'id'               => PDO::PARAM_INT,
        'projectVariantId' => PDO::PARAM_INT,
        'ident'            => PDO::PARAM_STR,
        'caption'          => PDO::PARAM_STR,
        'numericValue'     => PDO::PARAM_STR,
        'textValue'        => PDO::PARAM_STR,
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array();

    private $id;

    /**
     * projectVariantId
     */
    private $projectVariantId;

    /**
     * attribute identifier
     */
    private $ident;

    /**
     * attribute caption
     */
    private $caption;

    /**
     * number value
     */
    private $numericValue;

    /**
     * text value
     */
    private $textValue;

    /**
     * Returns the value for an attribute
     *
     * @param int    $projectVariantId
     * @param string $ident
     * @param bool   $force
     * @return mixed
     */
    public static function findValue($projectVariantId, $ident, $force = false)
    {
        $attribute = ElcaProjectVariantAttribute::findByProjectVariantIdAndIdent($projectVariantId, $ident, $force);

        if ($attribute->isInitialized()) {
            return null === $attribute->numericValue ? $attribute->textValue : $attribute->numericValue;
        }

        return null;
    }
    // End findValue

    /**
     * Sets and updated the value for an attribute
     *
     * @param int    $projectVariantId
     * @param string $ident
     * @param mixed  $value
     * @param bool   $forceTextValue
     * @param string $caption
     * @return ElcaProjectVariantAttribute
     */
    public static function updateValue($projectVariantId, $ident, $value, $forceTextValue = false, $caption = null)
    {
        $textValue = $numValue = null;

        if ($forceTextValue || !is_numeric($value)) {
            $textValue = $value;
        } else {
            $numValue = $value;
        }

        $attribute = ElcaProjectVariantAttribute::findByProjectVariantIdAndIdent($projectVariantId, $ident);

        if ($attribute->isInitialized()) {
            $attribute->setTextValue($textValue);
            $attribute->setNumericValue($numValue);
            $attribute->update();
        } else {
            $attribute = ElcaProjectVariantAttribute::create(
                $projectVariantId,
                $ident,
                $caption,
                $numValue,
                $textValue
            );
        }

        return $attribute;
    }
    // End updateValue


    /**
     * Creates the object
     *
     * @param  integer $projectVariantId - projectVariantId
     * @param  string  $ident            - attribute identifier
     * @param  string  $caption          - attribute caption
     * @param  number  $numericValue     - number value
     * @param  string  $textValue        - text value
     * @return ElcaProjectVariantAttribute
     */
    public static function create($projectVariantId, $ident, $caption = null, $numericValue = null, $textValue = null)
    {
        $projectVariantAttribute = new ElcaProjectVariantAttribute();
        $projectVariantAttribute->setProjectVariantId($projectVariantId);
        $projectVariantAttribute->setIdent($ident);
        $projectVariantAttribute->setCaption(
            $caption ? $caption : (isset(self::$idents[$ident]) ? self::$idents[$ident] : '')
        );
        $projectVariantAttribute->setNumericValue($numericValue);
        $projectVariantAttribute->setTextValue($textValue);

        if ($projectVariantAttribute->getValidator()->isValid()) {
            $projectVariantAttribute->insert();
        }

        return $projectVariantAttribute;
    }
    // End create


    /**
     * Inits a `ElcaProjectVariantAttribute' by its primary key
     *
     * @param  integer $id    - projectVariantAttributeId
     * @param  boolean $force - Bypass caching
     * @return ElcaProjectVariantAttribute
     */
    public static function findById($id, $force = false)
    {
        if (!$id) {
            return new ElcaProjectVariantAttribute();
        }

        $sql = sprintf(
            "SELECT id
                             , project_variant_id
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(get_class(), $sql, array('id' => $id), $force);
    }
    // End findById


    /**
     * Inits a `ElcaProjectVariantAttribute' by its unique key (projectVariantId, ident)
     *
     * @param  integer $projectVariantId
     * @param  string  $ident            - attribute identifier
     * @param  boolean $force            - Bypass caching
     * @return ElcaProjectVariantAttribute
     */
    public static function findByProjectVariantIdAndIdent($projectVariantId, $ident, $force = false)
    {
        if (!$projectVariantId || !$ident) {
            return new ElcaProjectVariantAttribute();
        }

        $sql = sprintf(
            "SELECT id
                             , project_variant_id
                             , ident
                             , caption
                             , numeric_value
                             , text_value
                          FROM %s
                         WHERE project_variant_id = :projectVariantId
                           AND ident = :ident"
            ,
            self::TABLE_NAME
        );

        return self::findBySql(
            get_class(),
            $sql,
            array('projectVariantId' => $projectVariantId, 'ident' => $ident),
            $force
        );
    }

    /**
     * Checks, if the object exists
     *
     * @param  integer $id    - projectVariantAttributeId
     * @param  boolean $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }

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
    // End setIdent

    /**
     * Returns the columns with their types. The columns may also return extended columns
     * if the first argument is set to true. To access the type of a single column, specify
     * the column name in the second argument
     *
     * @param  boolean $extColumns
     * @param  mixed   $column
     * @return mixed
     */
    public static function getColumnTypes($extColumns = false, $column = false)
    {
        $columnTypes = $extColumns ? array_merge(self::$columnTypes, self::$extColumnTypes) : self::$columnTypes;

        if ($column) {
            return $columnTypes[$column];
        }

        return $columnTypes;
    }
    // End setCaption

    /**
     * Returns the property id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    // End setNumericValue

    /**
     * Returns the property projectVariantId
     *
     * @return integer
     */
    public function getProjectVariantId()
    {
        return $this->projectVariantId;
    }
    // End setTextValue

    /**
     * Sets the property projectVariantId
     *
     * @param  integer $projectVariantId - projectVariantId
     * @return
     */
    public function setProjectVariantId($projectVariantId)
    {
        if (!$this->getValidator()->assertNotEmpty('projectVariantId', $projectVariantId)) {
            return;
        }

        $this->projectVariantId = (int)$projectVariantId;
    }
    // End getId

    /**
     * Returns the associated ElcaProject by property projectVariantId
     *
     * @param  boolean $force
     * @return ElcaProjectVariant
     */
    public function getProjectVariant($force = false)
    {
        return ElcaProjectVariant::findById($this->projectVariantId, $force);
    }

    /**
     * Returns the property ident
     *
     * @return string
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getProject

    /**
     * Sets the property ident
     *
     * @param  string $ident - attribute identifier
     * @return
     */
    public function setIdent($ident)
    {
        if (!$this->getValidator()->assertNotEmpty('ident', $ident)) {
            return;
        }

        if (!$this->getValidator()->assertMaxLength('ident', 150, $ident)) {
            return;
        }

        $this->ident = (string)$ident;
    }
    // End getIdent

    /**
     * Returns the property caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }
    // End getCaption

    /**
     * Sets the property caption
     *
     * @param  string $caption - attribute caption
     * @return
     */
    public function setCaption($caption)
    {
        if (!$this->getValidator()->assertMaxLength('caption', 150, $caption)) {
            return;
        }

        $this->caption = (string)$caption;
    }
    // End getNumericValue

    /**
     * Returns the property numericValue
     *
     * @return number
     */
    public function getNumericValue()
    {
        return $this->numericValue;
    }
    // End getTextValue

    /**
     * Sets the property numericValue
     *
     * @param  number $numericValue - number value
     * @return
     */
    public function setNumericValue($numericValue = null)
    {
        $this->numericValue = $numericValue;
    }

    /**
     * Returns the property textValue
     *
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }
    // End exists

    /**
     * Sets the property textValue
     *
     * @param  string $textValue - text value
     * @return
     */
    public function setTextValue($textValue = null)
    {
        $this->textValue = $textValue;
    }
    // End update

    /**
     *
     */
    public function getValue()
    {
        return $this->numericValue ?? $this->textValue;
    }
    // End delete

    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $sql = sprintf(
            "UPDATE %s
                           SET project_variant_id     = :projectVariantId
                             , ident          = :ident
                             , caption        = :caption
                             , numeric_value  = :numericValue
                             , text_value     = :textValue
                         WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->updateBySql(
            $sql,
            array(
                'id'               => $this->id,
                'projectVariantId' => $this->projectVariantId,
                'ident'            => $this->ident,
                'caption'          => $this->caption,
                'numericValue'     => $this->numericValue,
                'textValue'        => $this->textValue,
            )
        );
    }
    // End getPrimaryKey

    /**
     * Deletes the object from the table
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = sprintf(
            "DELETE FROM %s
                              WHERE id = :id"
            ,
            self::TABLE_NAME
        );

        return $this->deleteBySql(
            $sql,
            array('id' => $this->id)
        );
    }
    // End getTablename

    /**
     * Returns an array with the primary key properties and
     * associates its values, if it's a valid object
     *
     * @param  boolean $propertiesOnly
     * @return array
     */
    public function getPrimaryKey($propertiesOnly = false)
    {
        if ($propertiesOnly) {
            return self::$primaryKey;
        }

        $primaryKey = array();

        foreach (self::$primaryKey as $key) {
            $primaryKey[$key] = $this->$key;
        }

        return $primaryKey;
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
        $this->id = $this->getNextSequenceValue();

        $sql = sprintf(
            "INSERT INTO %s (id, project_variant_id, ident, caption, numeric_value, text_value)
                               VALUES  (:id, :projectVariantId, :ident, :caption, :numericValue, :textValue)"
            ,
            self::TABLE_NAME
        );

        return $this->insertBySql(
            $sql,
            array(
                'id'               => $this->id,
                'projectVariantId' => $this->projectVariantId,
                'ident'            => $this->ident,
                'caption'          => $this->caption,
                'numericValue'     => $this->numericValue,
                'textValue'        => $this->textValue,
            )
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
        $this->id               = (int)$DO->id;
        $this->projectVariantId = (int)$DO->project_variant_id;
        $this->ident            = $DO->ident;
        $this->caption          = $DO->caption;
        $this->numericValue     = $DO->numeric_value;
        $this->textValue        = $DO->text_value;

        /**
         * Set extensions
         */
    }
}
