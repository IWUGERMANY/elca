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

namespace ImportAssistant\Model\Import;

use ImportAssistant\Model\MaterialMapping\MaterialMapping;

class Project
{
    private $processDbId;
    private $name;
    private $description;
    private $projectNr;

    private $attributes;

    /**
     * @var array|ProjectVariant[]
     */
    private $variants;

    /**
     * Project constructor.
     *
     * @param int    $processDbId
     * @param string $name
     * @param array  $variants
     * @param array  $attributes
     * @param string $description
     * @param string $projectNr
     */
    public function __construct(int $processDbId, string $name, array $variants, array $attributes = [], string $description = null, string $projectNr = null)
    {
        $this->processDbId = $processDbId;
        $this->name        = $name;
        $this->description = $description;
        $this->projectNr   = $projectNr;
        $this->attributes  = $attributes;
        $this->variants    = $variants;
    }

    /**
     * @param string $uuid
     * @return null|MaterialMapping
     */
    public function findMappedMaterialForUuid(string $uuid)
    {
        foreach ($this->variants as $variant) {
            if ($result = $this->findMappedMaterialInElements($variant, $uuid)) {
                return $result;
            };
            if ($result = $this->findMappedMaterialInFinalEnergyDemands($variant, $uuid)) {
                return $result;
            };
            if ($result = $this->findMappedMaterialInFinalEnergySupplies($variant, $uuid)) {
                return $result;
            };
        }

        return null;
    }

    /**
     * @param string $uuid
     * @param int    $newProcessConfigId
     */
    public function replaceMappedProcessConfigIdForUuid(string $uuid, int $newProcessConfigId)
    {
        foreach ($this->variants as $variant) {
            if ($this->replaceMappedProcessConfigInElements($variant, $uuid, $newProcessConfigId)) {
                break;
            }
            if ($this->replaceMappedProcessConfigInFinalEnergyDemands($variant, $uuid, $newProcessConfigId)) {
                break;
            }
            if ($this->replaceMappedProcessConfigInFinalEnergySupplies($variant, $uuid, $newProcessConfigId)) {
                break;
            }
        }
    }

    /**
     * @param string $uuid
     * @param int    $newProcessConfigId
     */
    public function replaceAllMappedProcessConfigIds(string $uuid, int $newProcessConfigId)
    {
        if (!$mappedMaterial = $this->findMappedMaterialForUuid($uuid)) {
            return;
        }

        foreach ($this->variants as $variant) {
            $this->replaceAllMappedProcessConfigInElements($variant, $mappedMaterial->materialName(), $newProcessConfigId);
            $this->replaceAllMappedProcessConfigInFinalEnergyDemands($variant, $mappedMaterial->materialName(), $newProcessConfigId);
            $this->replaceAllMappedProcessConfigInFinalEnergySupplies($variant, $mappedMaterial->materialName(), $newProcessConfigId);
        }
    }

    /**
     * @return int
     */
    public function processDbId() : int
    {
        return $this->processDbId;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function projectNr()
    {
        return $this->projectNr;
    }

    /**
     * @return Attribute[]
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * @return ProjectVariant[]
     */
    public function variants()
    {
        return $this->variants;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @param int            $newProcessConfigId
     * @return bool
     */
    private function replaceMappedProcessConfigInElements(
        ProjectVariant $variant,
        string $uuid,
        int $newProcessConfigId
    )
    {
        foreach ($variant->elements() as $element) {
            foreach ($element->allComponents() as $component) {
                if ($component->uuid() === $uuid) {
                    $component->replaceMappedProcessConfigId($newProcessConfigId);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @param int            $newProcessConfigId
     * @return bool
     */
    private function replaceMappedProcessConfigInFinalEnergyDemands(
        ProjectVariant $variant,
        string $uuid,
        int $newProcessConfigId
    )
    {
        foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
            if ($finalEnergyDemand->uuid() === $uuid) {
                $finalEnergyDemand->replaceMappedProcessConfigId($newProcessConfigId);

                return true;
            }
        }

        return false;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @param int            $newProcessConfigId
     * @return bool
     */
    private function replaceMappedProcessConfigInFinalEnergySupplies(
        ProjectVariant $variant,
        string $uuid,
        int $newProcessConfigId
    )
    {
        foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
            if ($finalEnergySupply->uuid() === $uuid) {
                $finalEnergySupply->replaceMappedProcessConfigId($newProcessConfigId);

                return true;
            }
        }

        return false;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $materialName
     * @param int            $newProcessConfigId
     */
    private function replaceAllMappedProcessConfigInElements(
        ProjectVariant $variant,
        string $materialName,
        int $newProcessConfigId
    )
    {
        foreach ($variant->elements() as $element) {
            foreach ($element->allComponents() as $component) {
                if ($component->materialMapping()->materialName() === $materialName) {
                    $component->replaceMappedProcessConfigId($newProcessConfigId);
                }
            }
        }
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $materialName
     * @param int            $newProcessConfigId
     */
    private function replaceAllMappedProcessConfigInFinalEnergyDemands(
        ProjectVariant $variant,
        string $materialName,
        int $newProcessConfigId
    )
    {
        foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
            if ($finalEnergyDemand->materialMapping()->materialName() === $materialName) {
                $finalEnergyDemand->replaceMappedProcessConfigId($newProcessConfigId);
            }
        }
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $materialName
     * @param int            $newProcessConfigId
     */
    private function replaceAllMappedProcessConfigInFinalEnergySupplies(
        ProjectVariant $variant,
        string $materialName,
        int $newProcessConfigId
    )
    {
        foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
            if ($finalEnergySupply->materialMapping()->materialName() === $materialName) {
                $finalEnergySupply->replaceMappedProcessConfigId($newProcessConfigId);
            }
        }
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @return MaterialMapping
     */
    private function findMappedMaterialInElements(
        ProjectVariant $variant,
        string $uuid
    )
    {
        foreach ($variant->elements() as $element) {
            foreach ($element->allComponents() as $component) {
                if ($component->uuid() === $uuid) {
                    return $component->materialMapping();
                }
            }
        }

        return null;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @return MaterialMapping
     */
    private function findMappedMaterialInFinalEnergyDemands(
        ProjectVariant $variant,
        string $uuid
    )
    {
        foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
            if ($finalEnergyDemand->uuid() === $uuid) {
                return $finalEnergyDemand->materialMapping();
            }
        }

        return null;
    }

    /**
     * @param ProjectVariant $variant
     * @param string         $uuid
     * @return MaterialMapping
     */
    private function findMappedMaterialInFinalEnergySupplies(
        ProjectVariant $variant,
        string $uuid
    )
    {
        foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
            if ($finalEnergySupply->uuid() === $uuid) {
                return $finalEnergySupply->materialMapping();
            }
        }

        return null;
    }
}
