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

use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\Media;
use Imagick;
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
class ElcaSvgPattern extends DbObject
{
    /**
     * Tablename
     */
    const TABLE_NAME = 'elca.svg_patterns';



    /**
     * svgPatternId
     */
    private $id;

    /**
     * pattern name
     */
    private $name;

    /**
     * description
     */
    private $description;

    /**
     * width
     */
    private $width;

    /**
     * height
     */
    private $height;

    /**
     * imageId
     */
    private $imageId;

    /**
     * image url
     */
    private $imageUrl;

    /**
     * Primary key
     */
    private static $primaryKey = ['id'];

    /**
     * Column types
     */
    private static $columnTypes = ['id'             => PDO::PARAM_INT,
                                        'name'           => PDO::PARAM_STR,
                                        'description'    => PDO::PARAM_STR,
                                        'width'          => PDO::PARAM_STR,
                                        'height'         => PDO::PARAM_STR,
                                        'imageId'        => PDO::PARAM_INT,
                                        'created'        => PDO::PARAM_STR,
                                        'modified'       => PDO::PARAM_STR];

    /**
     *
     * Valid mimetypes
     *
     * @var array
     */
    public static $validMimetypes = ['gif' => 'image/gif', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'jpg' => 'image/jpeg'];


    /**
     * docroot relative path to imagedirectory
     */
    const IMAGE_DIRECTORY_NAME = 'img/elca/patterns/';

    /**
     * Extended column types
     */
    private static $extColumnTypes = [];


    // public


    /**
     * Creates the object
     *
     * @param  string $name   - pattern name
     * @param  float  $width  - width
     * @param  float  $height - height
     * @param null    $imageId
     * @param null    $description
     * @return ElcaSvgPattern
     */
    public static function create($name, $width, $height, $imageId = null, $description = null)
    {
        $ElcaSvgPattern = new ElcaSvgPattern();
        $ElcaSvgPattern->setName($name);
        $ElcaSvgPattern->setDescription($description);
        $ElcaSvgPattern->setWidth($width);
        $ElcaSvgPattern->setHeight($height);
        $ElcaSvgPattern->setImageId($imageId);
        
        if($ElcaSvgPattern->getValidator()->isValid())
            $ElcaSvgPattern->insert();
        
        return $ElcaSvgPattern;
    }
    // End create
    


    /**
     * Inits a `ElcaSvgPattern' by its primary key
     *
     * @param  integer  $id    - svgPatternId
     * @param  boolean  $force - Bypass caching
     * @return ElcaSvgPattern
     */
    public static function findById($id, $force = false)
    {
        if(!$id)
            return new ElcaSvgPattern();
        
        $sql = sprintf("SELECT id
                             , name
                             , description
                             , width
                             , height
                             , image_id
                             , created
                             , modified
                          FROM %s
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, ['id' => $id], $force);
    }
    // End findById


    /**
     * Inits a `ElcaSvgPattern' by element component id
     *
     * @param          $componentId
     * @param  boolean $force - Bypass caching
     * @return ElcaSvgPattern
     */
    public static function findByElementComponentId($componentId, $force = false)
    {
        if(!$componentId)
            return new ElcaSvgPattern();
        
        $sql = sprintf("SELECT p.id
                             , p.name
                             , p.description
                             , p.width
                             , p.height
                             , p.image_id
                             , p.created
                             , p.modified
                          FROM %s p
                          JOIN %s c  ON p.id = c.svg_pattern_id
                          JOIN %s pc ON pc.process_category_node_id = c.node_id
                          JOIN %s ec ON pc.id = ec.process_config_id
                         WHERE ec.id = :componentId"
                       , self::TABLE_NAME
                       , ElcaProcessCategory::TABLE_NAME
                       , ElcaProcessConfig::TABLE_NAME
                       , ElcaElementComponent::TABLE_NAME
                       );
        
        return self::findBySql(get_class(), $sql, ['componentId' => $componentId], $force);
    }
    // End findByElementComponentId


    /**
     * Sets the property name
     *
     * @param  string $name - pattern name
     * @return void
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
     * Sets the property description
     *
     * @param $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    // End setWidth


    /**
     * Sets the property width
     *
     * @param  float $width - width
     * @return void
     */
    public function setWidth($width)
    {
        if(!$this->getValidator()->assertNotEmpty('width', $width))
            return;

        $this->width = $width;
    }
    // End setWidth



    /**
     * Sets the property height
     *
     * @param  float  $height - height
     * @return 
     */
    public function setHeight($height)
    {
        if(!$this->getValidator()->assertNotEmpty('height', $height))
            return;
        
        $this->height = $height;
    }
    // End setHeight


    /**
     * Sets the property imageId
     *
     * @param int $imageId
     */
    public function setImageId($imageId = null)
    {
        $this->imageId = $imageId;
    }
    // End setImageId

    /**
     *
     * Sets pattern image by upload
     *
     * @param $fileKeyArray
     *
     * @return Media
     */
    public function setImageByUpload($fileKeyArray)
    {
        if (!$fileKeyArray || !File::uploadFileExists($fileKeyArray))
            return null;

        $Media = null;

        try {
            if (!File::checkUploadError($fileKeyArray))
                return null;

            $Media = Media::createByUpload($fileKeyArray, self::getImageDirectory());

            if (!is_object($Media) || !$Media->isInitialized())
                return null;

            // keep old image reference
            $OldImage = $this->getImage();

            // set new image
            $this->setImageId($Media->getId());

            $Image = new Imagick($Media->getFile()->getFilepath());
            $this->setWidth($Image->getImageWidth());
            $this->setHeight($Image->getImageHeight());

            $this->update();

            // delete old image
            if ($OldImage->isInitialized()) {
                $OldImage->delete();
            }

            return $this->getImage();
        }
        catch (Exception $e)
        {
            if (is_object($Media) && $Media->isInitialized())
                $Media->delete();

            return null;
        }
    }
    // End setImageByUpload


    /**
     * Returns the property id
     *
     * @return integer
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
     * Returns the property description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    // End getDescription



    /**
     * Returns the property width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }
    // End getWidth
    


    /**
     * Returns the property height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }
    // End getHeight


    /**
     * Returns the property imageId
     *
     * @return int
     */
    public function getImageId()
    {
        return $this->imageId;
    }
    // End getImageId


    /**
     * Returns the Image
     *
     * @return Media
     */
    public function getImage()
    {
        return Media::findById($this->imageId);
    }
    // End getImage


    /**
     * Checks if pattern image file is available
     * @return bool
     */
    public function hasImage()
    {
       return !is_null($this->imageId);
    }
    // End hasImage


    /**
     * Returns the url of the image
     *
     * @return string
     */
    public function getImageUrl()
    {
        if(isset($this->imageUrl))
            return $this->imageUrl;

        return $this->imageUrl = $this->getImage()->getURI();
    }
    // End getImageUrl


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
     * How often this pattern is used?
     *
     * @returns int
     */
    public function getUsageCount()
    {
        return array_sum([ElcaProcessConfigSet::dbCount(['svg_pattern_id' => $this->getId()]), ElcaProcessCategorySet::dbCount(['svg_pattern_id' => $this->getId()])]);
    }
    // End getUsageCount


    /**
     * Checks, if the object exists
     *
     * @param  integer  $id    - svgPatternId
     * @param  boolean  $force - Bypass caching
     * @return boolean
     */
    public static function exists($id, $force = false)
    {
        return self::findById($id, $force)->isInitialized();
    }
    // End exists
    


    /**
     * Updates the object in the table
     *
     * @return boolean
     */
    public function update()
    {
        $this->modified = self::getCurrentTime();

        $sql = sprintf("UPDATE %s
                           SET name           = :name
                             , description    = :description
                             , width          = :width
                             , height         = :height
                             , image_id       = :imageId
                             , created        = :created
                             , modified       = :modified
                         WHERE id = :id"
                       , self::TABLE_NAME
                       );
        
        return $this->updateBySql($sql,
                                  ['id'            => $this->id,
                                        'name'          => $this->name,
                                        'description'   => $this->description,
                                        'width'         => $this->width,
                                        'height'        => $this->height,
                                        'imageId'       => $this->imageId,
                                        'modified'      => $this->modified,
                                        'created'       => $this->created]
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
        if ($this->hasImage())
            $this->getImage()->delete();

        $sql = sprintf("DELETE FROM %s
                              WHERE id = :id"
                       , self::TABLE_NAME
                      );
        
        return $this->deleteBySql($sql,
                                  ['id' => $this->id]);
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
     *
     */
    public static function getImageDirectory()
    {
        $Config = Environment::getInstance()->getConfig();
        return $Config->docRoot . '/' . self::IMAGE_DIRECTORY_NAME;
    }
    // End getImageDirectory


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
        $this->id = $this->getNextSequenceValue();
        $this->created        = self::getCurrentTime();
        $this->modified       = null;

        $sql = sprintf("INSERT INTO %s (id, name, description, width, height, image_id, created, modified)
                               VALUES  (:id, :name, :description, :width, :height, :imageId, :created, :modified)"
                       , self::TABLE_NAME
                       );
        
        return $this->insertBySql($sql,
                                  ['id'            => $this->id,
                                        'name'          => $this->name,
                                        'description'   => $this->description,
                                        'width'         => $this->width,
                                        'height'        => $this->height,
                                        'imageId'       => $this->imageId,
                                        'created'       => $this->created,
                                        'modified'      => $this->modified]
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
        $this->id             = (int)$DO->id;
        $this->name           = $DO->name;
        $this->description    = $DO->description;
        $this->width          = $DO->width;
        $this->height         = $DO->height;
        $this->imageId        = $DO->image_id;
        $this->created        = $DO->created;
        $this->modified        = $DO->modified;

        /**
         * Set extensions
         */
    }
    // End initByDataObject
}
// End class ElcaSvgPattern