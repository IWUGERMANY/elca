<?php
namespace ImportAssistant\Db;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\DbObjectSet;

/**
 * Handles a set of ImportAssistantProcessConfigMapping
 *
 * @package    -
 * @class      ImportAssistantProcessConfigMappingSet
 * @author Fabian Möller <fab@beibob.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @copyright  2017 BEIBOB Medienfreunde
 */
class ImportAssistantProcessConfigMappingSet extends DbObjectSet
{
    /**
     * Lazy find
     *
     * @param  array    $initValues - key value array
     * @param  array    $orderBy   - array map of columns on to directions array('id' => 'DESC')
     * @param  int      $limit     - limit on resultset
     * @param  int      $offset    - offset on resultset
     * @param  bool     $force     - Bypass caching
     * @return ImportAssistantProcessConfigMappingSet
     */
    public static function find(array $initValues = null, array $orderBy = null, $limit = null, $offset = null, $force = false)
    {
        return self::_find(get_class(), ImportAssistantProcessConfigMapping::VIEW_PROCESS_CONFIG_MAPPING_CONVERSIONS, $initValues, $orderBy, $limit, $offset, $force);
    }

    public static function findByProcessDbId(
        int $processDbId, array $initValues = [], array $orderBy = null, $limit = null, $offset = null, $force = false
    ) {
        $initValues['process_db_id'] = $processDbId;

        return self::_find(get_class(), ImportAssistantProcessConfigMapping::VIEW_PROCESS_CONFIG_MAPPING_CONVERSIONS, $initValues, $orderBy, $limit, $offset, $force);
    }


    /**
     * Lazy count
     *
     * @param  array    $initValues - key value array
     * @param  bool     $force     - Bypass caching
     * @return int
     */
    public static function dbCount(array $initValues = null, $force = false)
    {
        return self::_count(get_class(), ImportAssistantProcessConfigMapping::TABLE_NAME, $initValues, $force);
    }

    public static function findByMaterialName(string $materialName, int $processDbId, array $orderBy = null, $limit = null, $offset = null, bool $force = false)
    {
        return self::findByProcessDbId($processDbId,
            [
                'material_name' => $materialName,
            ],
            $orderBy, $limit, $offset, $force
        );
    }

    public static function countByProcessConfigId($processConfigId, $force = false)
    {
        return self::_count(
            get_class(),
            ImportAssistantProcessConfigMapping::VIEW_PROCESS_CONFIG_MAPPING_CONVERSIONS,
            [
                'process_config_id' => $processConfigId,
            ],
            $force
        );
    }

    public static function removeByProcessDbId(int $processDbId)
    {
        if (!$processDbId) {
            return;
        }

        $sql = sprintf(
            'DELETE FROM %s
                         WHERE process_db_id = :processDbId'
            ,
            ImportAssistantProcessConfigMapping::TABLE_NAME
        );

        $initValues =  [
            'processDbId' => $processDbId,
        ];

        $statement = DbObject::prepareStatement($sql, $initValues);

        if (!$statement->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage(
                ImportAssistantProcessConfigMapping::class,
                $sql,
                $initValues));
        }
    }

    public static function copy($fromProcessDbId, $toProcessDbId) : int
    {
        $sql = sprintf(
            'INSERT INTO %s (material_name, process_config_id, process_db_id, is_sibling, required_additional_layer)
                         SELECT material_name, 
                                process_config_id, 
                                :toProcessDbId, 
                                is_sibling, 
                                required_additional_layer
                            FROM %s
                         WHERE process_db_id = :fromProcessDbId'
            ,
            ImportAssistantProcessConfigMapping::TABLE_NAME,
            ImportAssistantProcessConfigMapping::TABLE_NAME
        );

        $initValues = [
            'toProcessDbId'   => $toProcessDbId,
            'fromProcessDbId' => $fromProcessDbId,
        ];
        $statement  = DbObject::prepareStatement(
            $sql,
            $initValues
        );

        if (!$statement->execute()) {
            throw new \Exception(DbObject::getSqlErrorMessage(
                ImportAssistantProcessConfigMapping::class,
                $sql,
                $initValues)
            );
        }

        return $statement->rowCount();
    }
}
