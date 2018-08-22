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

declare(strict_types=1);

namespace ImportAssistant\Model\MaterialMapping;

use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaProcessConfigAttribute;
use ImportAssistant\Db\ImportAssistantProcessConfigMapping;
use ImportAssistant\Db\ImportAssistantProcessConfigMappingSet;

class MaterialMappingInfoRepository
{
    /**
     * @param int $processDbId
     * @return MaterialMappingInfo[]
     */
    public function findByProcessDbId(int $processDbId)
    {
        $mappings = ImportAssistantProcessConfigMappingSet::findByProcessDbId($processDbId);
        $map      = $this->buildMap($mappings);

        $result = [];
        foreach ($map as $materialName => $mappings) {
            $result[$materialName] = $this->buildMaterialMappingInfo($mappings);
        }

        return $result;
    }

    public function findById(int $id): ?MaterialMappingInfo
    {
        $mapping = ImportAssistantProcessConfigMapping::findById($id);

        $mappings = ImportAssistantProcessConfigMappingSet::findByMaterialName($mapping->getMaterialName(), $mapping->getProcessDbId(), ['id' => 'ASC']);

        if ($mappings->isEmpty()) {
            return null;
        }

        $map = $this->buildMap($mappings);

        return $this->buildMaterialMappingInfo($map[$mapping->getMaterialName()]);
    }

    public function findByMaterialName(string $materialName, int $processDbId) : ?MaterialMappingInfo
    {
        $mappings = ImportAssistantProcessConfigMappingSet::findByMaterialName($materialName, $processDbId, ['id' => 'ASC']);

        if ($mappings->isEmpty()) {
            return null;
        }

        $map = $this->buildMap($mappings);

        return $this->buildMaterialMappingInfo($map[$materialName]);
    }

    public function add(MaterialMappingInfo $mappingInfo)
    {
        $foundMappingInfo = $this->findByMaterialName($mappingInfo->materialName(), $mappingInfo->processDbId());

        if (null !== $foundMappingInfo && false === $foundMappingInfo->equals($mappingInfo)) {
            throw new \UnexpectedValueException('A mapping with that name already exists');
        }

        DbHandle::getInstance()
                ->atomic(
                    function () use ($mappingInfo) {
                        foreach ($mappingInfo->materialMappings() as $mapping) {
                            $this->createMapping($mapping, $mappingInfo);
                        }
                    }
                );
    }

    public function save(MaterialMappingInfo $mappingInfo)
    {
        DbHandle::getInstance()
                ->atomic(
                    function () use ($mappingInfo) {
                        /**
                         * @var ImportAssistantProcessConfigMapping $oldMapping
                         */
                        $oldMappings = ImportAssistantProcessConfigMappingSet::findByMaterialName($mappingInfo->materialName(), $mappingInfo->processDbId());

                        $oldMappingMap = [];
                        foreach ($oldMappings as $oldMapping) {
                            $oldMappingMap[$oldMapping->getId()] = $oldMapping;
                        }

                        $mappings = $mappingInfo->materialMappings();
                        if (null === $mappings[0]->surrogateId()) {
                            throw new \UnexpectedValueException('Cannot save a mapping without surrogate id');
                        }

                        // update or create mappings
                        foreach ($mappings as $mapping) {
                            if ($mapping->surrogateId()) {
                                $this->updateMapping($mapping, $mappingInfo);
                                unset($oldMappingMap[$mapping->surrogateId()]);
                            } else {
                                $this->createMapping($mapping, $mappingInfo);
                            }
                        }

                        // remove stale mappings
                        foreach ($oldMappingMap as $oldMapping) {
                            $oldMapping->delete();
                        }
                    }
                );
    }

    public function remove(MaterialMappingInfo $mappingInfo)
    {
        DbHandle::getInstance()
                ->atomic(
                    function () use ($mappingInfo) {

                        $processConfigIds = [];
                        foreach ($mappingInfo->materialMappings() as $mapping) {
                            if (null === $mapping->surrogateId()) {
                                throw new \UnexpectedValueException('Cannot delete a mapping without surrogate id');
                            }

                            $dbMapping = ImportAssistantProcessConfigMapping::findById($mapping->surrogateId());

                            if (false === $dbMapping->isInitialized()) {
                                throw new \UnexpectedValueException(
                                    'Mapping with id='.$mapping->surrogateId().' not found'
                                );
                            }
                            $processConfigIds[$dbMapping->getProcessConfigId()] = false;
                            $dbMapping->delete();

                        }

                        $this->updateProcessConfigAttributes($processConfigIds);
                    }
                );
    }

    public function copy(int $fromProcessDbId, int $toProcessDbId) : int
    {
        return ImportAssistantProcessConfigMappingSet::copy($fromProcessDbId, $toProcessDbId);
    }


    public function removeByProcessDbId(int $processDbId)
    {
        ImportAssistantProcessConfigMappingSet::removeByProcessDbId($processDbId);
    }

    /**
     * @param $mappings
     * @return array
     */
    private function buildMap(ImportAssistantProcessConfigMappingSet $mappings): array
    {
        $map = [];
        foreach ($mappings as $mapping) {
            $materialName = $mapping->getMaterialName();

            if (!isset($map[$materialName])) {
                $map[$materialName] = [];
            }

            $map[$materialName][] = $mapping;
        }

        return $map;
    }

    /**
     * @param array $mappings
     * @return MaterialMappingInfo
     */
    private function buildMaterialMappingInfo(array $mappings): MaterialMappingInfo
    {
        /**
         * @var ImportAssistantProcessConfigMapping[] $mappings
         * @var ImportAssistantProcessConfigMapping   $mapping
         */
        $mapping = current($mappings);

        return new MaterialMappingInfo(
            $mapping->getMaterialName(),
            $mapping->getProcessDbId(),
            array_map(
                function (ImportAssistantProcessConfigMapping $mapping) {
                    $materialMapping = new MaterialMapping(
                        $mapping->getMaterialName(),
                        $mapping->getProcessConfigId(),
                        null !== $mapping->getSiblingRatio() ? (float)$mapping->getSiblingRatio() : null,
                        $mapping->processConfigName(),
                        $mapping->getUnits(),
                        $mapping->getEpdSubTypes(),
                        $mapping->getProcessDbIds()
                    );

                    $materialMapping->setSurrogateId($mapping->getId());

                    return $materialMapping;

                },
                $mappings
            ),
            $mapping->isSibling(),
            $mapping->getRequiredAdditionalLayer()
        );
    }

    /**
     * @param $mapping
     * @param $mappingInfo
     */
    private function updateMapping(MaterialMapping $mapping, MaterialMappingInfo $mappingInfo)
    {
        $dbMapping = ImportAssistantProcessConfigMapping::findById($mapping->surrogateId());

        if (false === $dbMapping->isInitialized()) {
            throw new \UnexpectedValueException(
                'Mapping with id='.$mapping->surrogateId().' not found'
            );
        }

        $processConfigIds = [];
        $processConfigIds[$dbMapping->getProcessConfigId()] = false;

        $dbMapping->setMaterialName($mappingInfo->materialName());
        $dbMapping->setProcessConfigId($mapping->mapsToProcessConfigId());
        $dbMapping->setSiblingRatio($mapping->ratio());
        $dbMapping->setIsSibling($mappingInfo->requiresSibling());
        $dbMapping->setRequiredAdditionalLayer($mappingInfo->requiresAdditionalComponent());
        $dbMapping->update();

        $processConfigIds[$dbMapping->getProcessConfigId()] = true;

        $this->updateProcessConfigAttributes($processConfigIds);
    }

    /**
     * @param $mapping
     * @param $mappingInfo
     */
    private function createMapping(MaterialMapping $mapping, MaterialMappingInfo $mappingInfo)
    {
        if (null !== $mapping->surrogateId()) {
            throw new \UnexpectedValueException(
                'Cannot add a new mapping with id='. $mapping->surrogateId()
            );
        }

        $dbMapping = ImportAssistantProcessConfigMapping::create(
            $mappingInfo->materialName(),
            $mappingInfo->processDbId(),
            $mapping->mapsToProcessConfigId(),
            $mappingInfo->requiresSibling(),
            $mapping->ratio(),
            $mappingInfo->requiresAdditionalComponent()
        );

        $mapping->setSurrogateId($dbMapping->getId());

        $this->updateProcessConfigAttributes([$mapping->mapsToProcessConfigId() => true]);
    }

    private function updateProcessConfigAttributes(array $processConfigIds)
    {
        foreach ($processConfigIds as $processConfigId => $value) {
            if ($value) {
                ElcaProcessConfigAttribute::updateValue(
                    $processConfigId,
                    ElcaProcessConfigAttribute::IDENT_4108_COMPAT,
                    (int)$value
                );

                continue;
            }

            if (0 === ImportAssistantProcessConfigMappingSet::countByProcessConfigId($processConfigId, true)) {
                ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent(
                    $processConfigId,
                    ElcaProcessConfigAttribute::IDENT_4108_COMPAT
                )->delete();
            }
        }
    }
}
