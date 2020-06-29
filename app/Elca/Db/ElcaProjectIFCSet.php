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
use Beibob\Blibs\DbObjectSet;
use Exception;
use PDO;

/**
 * eLCA project ifc
 *
 * @package elca
 * @author  Fabian Möller <fab@beibob.de>
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectIFCSet extends DbObjectSet
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.ifc_project';
	
	
    /**
     * Primary key
     */
    private static $primaryKey = array('ifc_project_id');

    /**
     * Column types
     */
    private static $columnTypes = array(
        'ifcProjectId'     => PDO::PARAM_INT,
        'projectsId'       => PDO::PARAM_INT,
        'created'          => PDO::PARAM_STR
    );

    /**
     * ifcProjectId
     */
    private $ifcProjectId;

    /**
     * Project ID
     */
    private $projectsId;

    
    /**
     * creation time
     */
    private $created;

   
    /**
     * Save data 
     */
    public static function createIFCproject($id)
    {

		$sql = sprintf(
			'INSERT INTO %s (projects_id)
			 VALUES  (:projects_id)'
			,
			self::TABLE_NAME
		);
		
		$Stmt = DbObject::prepareStatement($sql,['projects_id' => $id] ); 
		if (!$Stmt->execute()) {
			throw new \Exception(DbObject::getSqlErrorMessage($sql, $id));
        }
        return true;
    }
	

    /**
     * find ifc project
     *
     * @param  integer $id    - projectId
     * @param  boolean $force - Bypass caching
     * @return 
     */
    public static function findIFCprojectById($id, $force = false)
    {
        if (!$id) {
            throw new \Exception('No project ID available');
        }

        $sql = sprintf(
            "SELECT projects_id
                            FROM %s
                            WHERE projects_id = :projects_id"
            ,self::TABLE_NAME
        );
		
		$Stmt = DbObject::prepareStatement($sql,['projects_id' => $id] ); 
		if (!$Stmt->execute()) {
			throw new \Exception(DbObject::getSqlErrorMessage($sql, ["id" => $id]));
        }
		
		$DataObject = $Stmt->fetch(PDO::FETCH_ASSOC);
		return $DataObject;
    }

    

    
    /**
     * Checks, if the procect exists as ifc project
     *
     * @param  integer $id    - projectId
     * @param  boolean $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findIFCprojectById($id, $force);
    }
    // End copy


    
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
    

    /**
     * Returns the ifc project id
     *
     * @return integer
     */
    public function getIfcProjectId()
    {
        return $this->ifcProjectId;
    }

    /**
     * Returns the project id
     *
     * @return integer
     */
    public function getProjectsId()
    {
        return $this->projectsId;
    }
	
	
    
}
// End class 