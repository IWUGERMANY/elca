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

use Beibob\Blibs\DbObjectSet;
use Elca\Elca;

/**
 * Handles a set of ElcaElementAttribute
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementAttributeSet extends DbObjectSet
{

    //////////////////////////////////////////////////////////////////////////////////////
    // public
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Find all eol, separation and recycling attributes for one project variant
     *
     * @param          $projectVariantId
     * @param  array   $initValues - key value array
     * @param  array   $orderBy    - array map of columns on to directions array('id' => 'DESC')
     * @param  integer $limit      - limit on resultset
     * @param  integer $offset     - offset on resultset
     * @param  boolean $force      - Bypass caching
     * @return ElcaElementAttributeSet
     */
    public static function findEolSeparationRecyclingByProjecVariantId($projectVariantId, array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        if (!$projectVariantId)
            return new ElcaElementAttributeSet();

        if (is_null($initValues))
            $initValues = [];

        $initValues['project_variant_id'] = $projectVariantId;
        $conditions = self::buildConditions($initValues);

        $initValues['identEol'] = Elca::ELEMENT_ATTR_EOL;
        $initValues['identSeparation'] = Elca::ELEMENT_ATTR_SEPARATION;
        $initValues['identRecycling'] = Elca::ELEMENT_ATTR_RECYCLING;

        $sql = sprintf('SELECT a.*
                             , e.name AS element_name
                             , t.din_code AS element_type_node_din_code
                             , t.name AS element_type_node_name
                          FROM %s e
                          JOIN %s t ON t.node_id = e.element_type_node_id
                          JOIN %s a ON e.id = a.element_id
                         WHERE a.ident IN (:identEol, :identSeparation, :identRecycling)'
                    , ElcaElement::TABLE_NAME
                    , ElcaElementTypeSet::VIEW_ELCA_ELEMENT_TYPES
                    , ElcaElementAttribute::TABLE_NAME
        );

        if ($conditions)
            $sql .= ' AND '. $conditions;

        if ($orderBy = self::buildOrderView($orderBy, $limit, $offset))
            $sql .= ' '. $orderBy;

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findEolSeparationRecyclingByProjectVariantId

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Inits a `ElcaElementAttribute' by its unique key (elementId, ident)
     *
     * @param  integer  $elementId - elementId
     * @param  string   $ident    - attribute identifier
     * @param  boolean  $force    - Bypass caching
     * @return ElcaElementAttributeSet|ElcaElementAttribute[]
     */
    public static function findWithinProjectVariantByIdentAndNumericValue($projectVariantId, $ident, $numericValue = null, $force = false)
    {
        if (!$projectVariantId) {
            return new ElcaElementAttributeSet();
        }

        $initValues = ['projectVariantId' => $projectVariantId, 'ident' => $ident];

        if (null === $numericValue) {
            $numericSql = 'IS NULL';
        } else {
            if (true === $numericValue) {
                $numericSql = ' IS NOT NULL';
            } else {
                $numericSql = ' = :numericValue';
                $initValues['numericValue'] = $numericValue;
            }
        }

        $sql = sprintf("SELECT a.id
                             , a.element_id
                             , a.ident
                             , a.caption
                             , a.numeric_value
                             , a.text_value
                          FROM %s a
                          JOIN %s e ON e.id = a.element_id
                         WHERE e.project_variant_id = :projectVariantId
                           AND a.ident = :ident
                           AND a.numeric_value %s
                           "
            , ElcaElementAttribute::TABLE_NAME
            , ElcaElement::TABLE_NAME
            , $numericSql
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByElementIdAndIdent

    /**
     * Inits a `ElcaElementAttribute' by its unique key (elementId, ident)
     *
     * @param         $projectVariantId
     * @param string  $ident - attribute identifier
     * @param boolean $force - Bypass caching
     * @return ElcaElementAttributeSet|ElcaElementAttribute[]
     * @throws \Beibob\Blibs\Exception
     */
    public static function findWithinProjectVariantByIdent($projectVariantId, $ident, $force = false)
    {
        if (!$projectVariantId) {
            return new ElcaElementAttributeSet();
        }

        $initValues = ['projectVariantId' => $projectVariantId, 'ident' => $ident];

        $sql = sprintf("SELECT a.id
                             , a.element_id
                             , a.ident
                             , a.caption
                             , a.numeric_value
                             , a.text_value
                          FROM %s a
                          JOIN %s e ON e.id = a.element_id
                         WHERE e.project_variant_id = :projectVariantId
                           AND a.ident = :ident
                           "
            , ElcaElementAttribute::TABLE_NAME
            , ElcaElement::TABLE_NAME
        );

        return self::_findBySql(get_class(), $sql, $initValues, $force);
    }
    // End findByElementIdAndIdent

    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  integer  $limit     - limit on resultset
     * @param  integer  $offset    - offset on resultset
     * @param  boolean  $force     - Bypass caching
     * @return ElcaElementAttributeSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ElcaElementAttribute::getTablename(), $initValues, $orderBy, $limit, $offset, $force);
    }
    // End find
    
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  boolean  $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ElcaElementAttribute::getTablename(), $initValues, $force);
    }
    // End dbCount
}
// End class ElcaElementAttributeSet