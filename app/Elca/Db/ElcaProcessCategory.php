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

use Beibob\Blibs\NestedNode;
use PDO;
use Beibob\Blibs\DbObject;
/**
 *
 * @package elca
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 *
 * @translate db Elca\Db\ElcaProcessCategorySet::find() name
 * @translate db Elca\Db\ElcaProcessCategorySet::findExtended() parentNodeName
 */
class ElcaProcessCategory extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.process_categories';

    /**
     * Root Node ident
     */
    const ROOT_NODE = 'ELCA_PROCESS_CATEGORIES';

    const REF_NUM_OTHERS = '9';
    const REF_NUM_OTHERS_INTERN = '9.99';
    const CAPTION_OTHERS_INTERN = 'eLCA Intern';

    /**
     * processCategoryId
     */
    private $nodeId;

    /**
     * name
     */
    private $name;

    /**
     * reference number
     */
    private $refNum;

    /**
     * svgPatternId
     */
    private $svgPatternId;

    /**
     * ident
     */
    private $ident;

    /**
     * ext: refNum of the parent node
     */
    private $parentNodeRefNum;

    /**
     * ext: name of the parent node
     */
    private $parentNodeName;

    /**
     * ext: node level
     */
    private $level;

    /**
     * Primary key
     */
    private static $primaryKey = array('nodeId');

    /**
     * Column types
     */
    private static $columnTypes = array('nodeId'         => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'refNum'         => PDO::PARAM_STR,
                                        'svgPatternId'   => PDO::PARAM_INT
    );

    /**
     * Extended column types
     */
    private static $extColumnTypes = array('parentNodeName' => PDO::PARAM_STR,
                                           'parentNodeRefNum' => PDO::PARAM_STR,
                                           'level' => PDO::PARAM_INT
    );


    // public


    /**
     * Creates the object
     *
     * @param  integer $nodeId - processCategoryId
     * @param  string  $name   - name
     * @param  string  $refNum - reference number
     * @param  int     $svgPatternId
     * @return ElcaProcessCategory
     */
    public static function create($nodeId, $name, $refNum = null, $svgPatternId = null)
    {
        $ElcaProcessCategory = new ElcaProcessCategory();
        $ElcaProcessCategory->setNodeId($nodeId);
        $ElcaProcessCategory->setName($name);
        $ElcaProcessCategory->setRefNum($refNum);
        $ElcaProcessCategory->setSvgPatternId($svgPatternId);

        if($ElcaProcessCategory->getValidator()->isValid())
            $ElcaProcessCategory->insert();

        return $ElcaProcessCategory;
    }
    // End create

    /**
     * @return ElcaProcessCategory
     */
    public static function createOthersInternNode()
    {
        $othersCategory = self::findByRefNum(self::REF_NUM_OTHERS);

        $othersInternCategoryNode = NestedNode::createAsChildOf(
            $othersCategory->getNode(),
            self::REF_NUM_OTHERS_INTERN
        );

        return ElcaProcessCategory::create(
            $othersInternCategoryNode->getId(),
            self::CAPTION_OTHERS_INTERN,
            self::REF_NUM_OTHERS_INTERN
        );
    }

    /**
     * Inits a `ElcaProcessCategory' by its root node
     *
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessCategory
     */
    public static function findRoot($force = false)
    {
        $sql = sprintf("SELECT node_id
                             , name
                             , ref_num
                             , svg_pattern_id
                             , ident
                          FROM %s
                         WHERE ident = :rootIdent"
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       );

        return self::findBySql(get_class(), $sql, array('rootIdent' => self::ROOT_NODE), $force);
    }
    // End findRoot



    /**
     * Inits a `ElcaProcessCategory' by its primary key
     *
     * @param  integer  $nodeId - processCategoryId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessCategory
     */
    public static function findByNodeId($nodeId, $force = false)
    {
        if(!$nodeId)
            return new ElcaProcessCategory();

        $sql = sprintf("SELECT node_id
                             , name
                             , ref_num
                             , svg_pattern_id
                             , ident
                          FROM %s
                         WHERE node_id = :nodeId"
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       );

        return self::findBySql(get_class(), $sql, array('nodeId' => $nodeId), $force);
    }
    // End findByNodeId



    /**
     * Inits a parent `ElcaProcessCategory' by a childNodeId
     *
     * @param  integer  $nodeId - nodeId
     * @param  boolean  $force - Bypass caching
     * @return ElcaProcessCategory
     */
    public static function findParentByNodeId($nodeId, $force = false)
    {
        if(!$nodeId)
            return new ElcaProcessCategory();

        $sql = sprintf("SELECT p.node_id
                             , p.name
                             , p.ref_num
                             , p.svg_pattern_id
                             , p.ident
                          FROM %s p
                          JOIN %s c ON c.lft BETWEEN p.lft AND p.rgt AND p.level = c.level - 1
                         WHERE c.node_id = :nodeId"
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       );

        return self::findBySql(get_class(), $sql, array('nodeId' => $nodeId), $force);
    }
    // End findParentByNodeId



    /**
     * Inits a `ElcaProcessCategory' by its ident
     *
     * @param  integer  $ident
     * @param  boolean  $force    - Bypass caching
     * @return ElcaProcessCategory
     */
    public static function findByIdent($ident, $force = false)
    {
        if(!$ident)
            return new ElcaProcessCategory();

        $sql = sprintf("SELECT node_id
                             , name
                             , ref_num
                             , svg_pattern_id
                             , ident
                          FROM %s
                         WHERE ident = :ident"
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       );

        return self::findBySql(get_class(), $sql, array('ident' => $ident), $force);
    }
    // End findByIdent



    /**
     * Inits a `ElcaProcessCategory' by its refnum
     *
     * @param  string  $refNum    - reference number
     * @param  boolean  $force    - Bypass caching
     * @return ElcaProcessCategory
     */
    public static function findByRefNum($refNum, $force = false)
    {
        if(!$refNum)
            return new ElcaProcessCategory();

        $sql = sprintf("SELECT node_id
                             , name
                             , ref_num
                             , svg_pattern_id
                             , ident
                          FROM %s
                         WHERE ref_num = :refNum"
                       , ElcaProcessCategorySet::VIEW_ELCA_PROCESS_CATEGORIES
                       );

        return self::findBySql(get_class(), $sql, array('refNum' => $refNum), $force);
    }
    // End findByRefNum



    /**
     * Sets the property nodeId
     *
     * @param  integer  $nodeId - processCategoryId
     * @return
     */
    public function setNodeId($nodeId)
    {
        if(!$this->getValidator()->assertNotEmpty('nodeId', $nodeId))
            return;

        $this->nodeId = (int)$nodeId;
    }
    // End setNodeId



    /**
     * Sets the property name
     *
     * @param  string   $name  - name
     * @return
     */
    public function setName($name)
    {
        if(!$this->getValidator()->assertNotEmpty('name', $name))
            return;

        if(!$this->getValidator()->assertMaxLength('name', 150, $name))
            return;

        $this->name = (string)$name;
    }
    // End setName



    /**
     * Sets the property refNum
     *
     * @param  string   $refNum - reference number
     * @return
     */
    public function setRefNum($refNum = null)
    {
        if(!$this->getValidator()->assertMaxLength('refNum', 50, $refNum))
            return;

        $this->refNum = $refNum;
    }
    // End setRefNum



    /**
     * Sets the property svgPatternId
     *
     * @param  integer $svgPatternId
     * @return void
     */
    public function setSvgPatternId($svgPatternId = null)
    {
        $this->svgPatternId = $svgPatternId;
    }
    // End setSvgPatternId


    /**
     * Returns the property nodeId
     *
     * @return integer
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }
    // End getNodeId



    /**
     * Returns the associated NestedNode by property nodeId
     *
     * @param  boolean  $force
     * @return NestedNode
     */
    public function getNode($force = false)
    {
        return NestedNode::findById($this->nodeId, $force);
    }
    // End getNode



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
     * Returns the property refNum
     *
     * @return string
     */
    public function getRefNum()
    {
        return $this->refNum;
    }
    // End getRefNum



    /**
     * Returns the property svgPatternId
     *
     * @return int
     */
    public function getSvgPatternId()
    {
        return $this->svgPatternId;
    }
    // End getSvgPatternId



    /**
     * Returns the svg pattern
     *
     * @return ElcaSvgPattern
     */
    public function getSvgPattern()
    {
        return ElcaSvgPattern::findById($this->svgPatternId);
    }
    // End getSvgPattern

    
    /**
     * Returns the property ident
     *
     * @return number
     */
    public function getIdent()
    {
        return $this->ident;
    }
    // End getIdent


    /**
     * Returns the property level
     *
     * @return number
     */
    public function getLevel()
    {
        return isset($this->level)? $this->level : $this->getNode()->getLevel();
    }
    // End getLevel


    /**
     * Returns the name of the parent node
     *
     * @return string
     */
    public function getParentNodeRefNum()
    {
        if($this->parentNodeRefNum)
            return $this->parentNodeRefNum;

        return self::findParentByNodeId($this->nodeId)->getRefNum();
    }
    // End getParentNodeName


    /**
     * Returns the name of the parent node
     *
     * @return string
     */
    public function getParentNodeName()
    {
        if($this->parentNodeName)
            return $this->parentNodeName;

        return self::findParentByNodeId($this->nodeId)->getName();
    }
    // End getParentNodeName



    /**
     * Returns a list of categories associated which are childs of this type
     *
     * @param  -
     * @return ElcaProcessCategorySet
     */
    public function getChildren()
    {
        return ElcaProcessCategorySet::findByParentType($this);
    }
    // End getChildren



    /**
     * Checks, if the object exists
     *
     * @param  integer  $nodeId - processCategoryId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($nodeId, $force = false)
    {
        return self::findByNodeId($nodeId, $force)->isInitialized();
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
                           SET name           = :name
                             , ref_num        = :refNum
                             , svg_pattern_id = :svgPatternId
                         WHERE node_id = :nodeId"
                       , self::TABLE_NAME
                       );

        return $this->updateBySql($sql,
                                  array('nodeId'        => $this->nodeId,
                                        'name'          => $this->name,
                                        'refNum'        => $this->refNum,
                                        'svgPatternId'  => $this->svgPatternId
                                  )
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
                              WHERE node_id = :nodeId"
                       , self::TABLE_NAME
                      );

        return $this->deleteBySql($sql,
                                  array('nodeId' => $this->nodeId));
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


    // protected


    /**
     * Inserts a new object in the table
     *
     * @return boolean
     */
    protected function insert()
    {

        $sql = sprintf("INSERT INTO %s (node_id, name, ref_num, svg_pattern_id)
                               VALUES  (:nodeId, :name, :refNum, :svgPatternId)"
                       , self::TABLE_NAME
                       );

        return $this->insertBySql($sql,
                                  array('nodeId'        => $this->nodeId,
                                        'name'          => $this->name,
                                        'refNum'        => $this->refNum,
                                        'svgPatternId'  => $this->svgPatternId
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
        $this->nodeId         = (int)$DO->node_id;
        $this->name           = $DO->name;
        $this->refNum         = $DO->ref_num;
        $this->svgPatternId   = $DO->svg_pattern_id;

        /**
         * Set extensions
         */
        if(isset($DO->ident))            $this->ident = $DO->ident;
        if(isset($DO->parent_node_name)) $this->parentNodeName = $DO->parent_node_name;
        if(isset($DO->parent_node_ref_num)) $this->parentNodeRefNum = $DO->parent_node_ref_num;
        if(isset($DO->level))            $this->level = $DO->level;
    }
    // End initByDataObject
}
// End class ElcaProcessCategory