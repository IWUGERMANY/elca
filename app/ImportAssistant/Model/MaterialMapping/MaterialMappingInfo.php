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

class MaterialMappingInfo
{
    private $materialName;

    private $processDbId;

    private $requiresSibling;

    private $requiresAdditionalComponent;

    /**
     * @var MaterialMapping[]
     */
    private $materialMappings;

    /**
     * MaterialMappingInfo constructor.
     *
     * @param string $materialName
     * @param int    $processDbId
     * @param array  $materialMappings
     * @param bool   $requiresSibling
     * @param bool   $requiresAdditionalComponent
     */
    public function __construct(
        string $materialName,
        int $processDbId,
        array $materialMappings = [],
        bool $requiresSibling = false,
        bool $requiresAdditionalComponent = false
    ) {
        $this->materialName                = $materialName;
        $this->processDbId = $processDbId;
        $this->materialMappings            = $materialMappings;
        $this->requiresSibling             = $requiresSibling;
        $this->requiresAdditionalComponent = $requiresAdditionalComponent;
    }

    /**
     * @return string
     */
    public function materialName()
    {
        return $this->materialName;
    }

    /**
     * @return int
     */
    public function processDbId() : int
    {
        return $this->processDbId;
    }

    /**
     * @return bool
     */
    public function requiresSibling()
    {
        return $this->requiresSibling;
    }

    /**
     * @return bool
     */
    public function requiresAdditionalComponent()
    {
        return $this->requiresAdditionalComponent;
    }

    /**
     * @return bool
     */
    public function hasMultipleMaterialMappings()
    {
        return count($this->materialMappings) > 1;
    }

    /**
     * @return MaterialMapping[]
     */
    public function materialMappings()
    {
        return $this->materialMappings;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->materialName();
    }

    public function firstMaterialMapping()
    {
        if (!$this->materialMappings) {
            return new MaterialMapping($this->materialName());
        }

        return reset($this->materialMappings);
    }

    public function equals(MaterialMappingInfo $mappingInfo): bool
    {
        if ($this == $mappingInfo) {
            return true;
        }

        $thisIds = $this->mappingSurrogateIds();
        $thatIds = $mappingInfo->mappingSurrogateIds();

        if (count($thisIds) !== count($thatIds)) {
            return false;
        }

        sort($thisIds);
        sort($thatIds);

        return $thisIds == $thatIds;
    }

    private function mappingSurrogateIds(): array
    {
        return array_map(
            function (MaterialMapping $mapping) {
                return $mapping->surrogateId();
            },
            $this->materialMappings
        );
    }

}
