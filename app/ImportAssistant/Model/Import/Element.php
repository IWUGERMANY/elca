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

use Elca\Elca;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;

class Element
{
    private $uuid;

    private $dinCode;

    private $name;

    private $description;

    private $quantity;

    private $refUnit;

    private $attributes;

    /**
     * @var array
     */
    private $layerComponents;

    /**
     * @var array
     */
    private $singleComponents;

    /**
     * Element constructor.
     *
     * @param        $uuid
     * @param        $dinCode
     * @param string $name
     * @param        $quantity
     * @param string $refUnit
     * @param array  $layerComponents
     * @param array  $singleComponents
     * @param array  $attributes
     * @param string $description
     * @internal param $components
     */
    public function __construct($uuid, $dinCode, string $name, $quantity, string $refUnit, string $description = null)
    {
        $this->uuid             = $uuid;
        $this->dinCode          = $dinCode;
        $this->name             = $name;
        $this->description      = $description;
        $this->quantity         = $quantity;
        $this->refUnit          = $refUnit;
        $this->layerComponents  = [];
        $this->singleComponents = [];
        $this->attributes       = [];

    }

    /**
     * @param MaterialMappingInfo $mappingInfo
     * @param                     $quantity
     * @param                     $refUnit
     * @param null                $din276Code
     */
    public function addSingleComponent(MaterialMappingInfo $mappingInfo, $quantity, $refUnit, $din276Code = null)
    {
        $materialMappings = $mappingInfo->materialMappings();

        $mappingCount = count($materialMappings);

        if (0 === $mappingCount) {
            $materialMappings[] = new MaterialMapping($mappingInfo->materialName());
        }

        foreach ($materialMappings as $index => $materialMapping) {
            $materialMapping = $this->guardUnit($materialMapping, $refUnit);

            $this->addComponent(
                new SingleComponent(
                    $mappingInfo->hasMultipleMaterialMappings()
                        ? $materialMapping->prefixMaterialName(($index + 1) . '. ')
                        : $materialMapping,
                    $quantity,
                    $refUnit,
                    $din276Code
                )
            );
        }
    }

    /**
     * @param MaterialMappingInfo $mappingInfo
     * @param                     $layerPosition
     * @param                     $size
     * @param int                 $length
     * @param int                 $width
     * @param int                 $areaRatio
     */
    public function addLayerComponent(
        MaterialMappingInfo $mappingInfo,
        $layerPosition,
        $size,
        $length = 1,
        $width = 1,
        $areaRatio = 1
    ) {
        if ($mappingInfo->hasMultipleMaterialMappings()) {

            if ($mappingInfo->requiresSibling()) {
                list($firstMapping, $secondMapping) = $mappingInfo->materialMappings();

                $firstMapping  = $this->guardUnit($firstMapping, Elca::UNIT_M3);
                $secondMapping = $this->guardUnit($secondMapping, Elca::UNIT_M3);

                $component1 = $this->addComponent(
                    new LayerComponent(
                        $firstMapping->materialName() === $secondMapping->materialName()
                            ? $firstMapping->prefixMaterialName('1. ')
                            : $firstMapping,
                        $layerPosition,
                        $size,
                        $firstMapping->ratio(),
                        $length,
                        $width
                    )
                );

                $component2 = $this->addComponent(
                    new LayerComponent(
                        $firstMapping->materialName() === $secondMapping->materialName()
                            ? $secondMapping->prefixMaterialName('2. ')
                            : $secondMapping,
                        $layerPosition,
                        $size,
                        $secondMapping->ratio(),
                        $length,
                        $width
                    )
                );

                $component1->setIsSiblingOf($component2);
                $component2->setIsSiblingOf($component1);
            } else {
                foreach ($mappingInfo->materialMappings() as $index => $materialMapping) {
                    $materialMapping = $this->guardUnit($materialMapping, Elca::UNIT_M3);

                    $this->addComponent(
                        new LayerComponent(
                            $mappingInfo->hasMultipleMaterialMappings()
                                ? $materialMapping->prefixMaterialName(($index + 1) . '. ')
                                : $materialMapping,
                            $layerPosition,
                            $size,
                            $areaRatio,
                            $length,
                            $width
                        )
                    );
                }
            }

        } else {
            $materialMapping = $mappingInfo->firstMaterialMapping();

            $materialMapping = $this->guardUnit($materialMapping, Elca::UNIT_M3);

            $this->addComponent(
                new LayerComponent(
                    $materialMapping,
                    $layerPosition,
                    $size,
                    $areaRatio,
                    $length,
                    $width
                )
            );
        }
    }

    /**
     * @param MaterialMappingInfo $mappingInfo1
     * @param MaterialMappingInfo $mappingInfo2
     * @param                     $layerPosition
     * @param                     $size1
     * @param                     $size2
     * @param                     $length1
     * @param                     $length2
     * @param                     $width1
     * @param                     $width2
     * @param                     $ratio1
     * @param                     $ratio2
     */
    public function addLayerSiblings(
        MaterialMappingInfo $mappingInfo1,
        MaterialMappingInfo $mappingInfo2,
        $layerPosition,
        $size1,
        $size2,
        $length1,
        $length2,
        $width1,
        $width2,
        $ratio1,
        $ratio2
    ) {

        $materialMapping1 = $mappingInfo1->firstMaterialMapping();
        $materialMapping2 = $mappingInfo2->firstMaterialMapping();
        $materialMapping1 = $this->guardUnit($materialMapping1, Elca::UNIT_M3);
        $materialMapping2 = $this->guardUnit($materialMapping2, Elca::UNIT_M3);

        $component1 = $this->addComponent(
            new LayerComponent(
                $materialMapping1->materialName() === $materialMapping2->materialName()
                    ? $materialMapping1->prefixMaterialName('1. ')
                    : $materialMapping1,
                $layerPosition,
                $size1,
                $ratio1,
                $length1,
                $width1
            )
        );

        $component2 = $this->addComponent(
            new LayerComponent(
                $materialMapping1->materialName() === $materialMapping2->materialName()
                    ? $materialMapping2->prefixMaterialName('2. ')
                    : $materialMapping2,
                $layerPosition,
                $size2,
                $ratio2,
                $length2,
                $width2
            )
        );

        $component1->setIsSiblingOf($component2);
        $component2->setIsSiblingOf($component1);
    }

    /**
     * @return mixed
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return mixed
     */
    public function dinCode()
    {
        return $this->dinCode;
    }

    /**
     * @param $dinCode
     */
    public function changeDinCode($dinCode)
    {
        $this->dinCode = $dinCode;
    }


    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function quantity()
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    public function refUnit()
    {
        return $this->refUnit;
    }

    /**
     * @return LayerComponent[]
     */
    public function layerComponents(): array
    {
        return $this->layerComponents;
    }

    /**
     * @return SingleComponent[]
     */
    public function singleComponents(): array
    {
        return $this->singleComponents;
    }


    /**
     * @return Attribute[]
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
     * @param string $componentUuid
     * @return bool
     */
    public function hasComponent(string $componentUuid): bool
    {
        return null !== $this->findComponentByUuid($componentUuid);
    }

    /**
     * @param string $componentUuid
     * @return Component|null
     */
    public function findComponentByUuid(string $componentUuid)
    {
        if (isset($this->layerComponents[$componentUuid])) {
            return $this->layerComponents[$componentUuid];
        }

        if (isset($this->singleComponents[$componentUuid])) {
            return $this->singleComponents[$componentUuid];
        }

        return null;
    }

    /**
     * @return array|Component[]
     */
    public function allComponents(): array
    {
        $this->sortLayers();

        $iterator = new \AppendIterator();
        $iterator->append(new \ArrayIterator($this->layerComponents()));
        $iterator->append(new \ArrayIterator($this->singleComponents()));

        return iterator_to_array($iterator);
    }

    /**
     * @return int
     */
    private function countLayers(): int
    {
        return count($this->layerComponents);
    }

    private function sortLayers()
    {
        usort(
            $this->layerComponents,
            function (Component $a, Component $b) {
                return $a->layerPosition() <=> $b->layerPosition();
            }
        );
    }

    /**
     * @param Component $component
     * @return Component
     */
    private function addComponent(Component $component)
    {
        if ($component->isLayer()) {
            $this->layerComponents[$component->uuid()] = $component;
        } else {
            $this->singleComponents[$component->uuid()] = $component;
        }

        return $component;
    }

    /**
     * @param MaterialMapping $materialMapping
     * @param string          $refUnit
     * @return MaterialMapping
     */
    private function guardUnit(MaterialMapping $materialMapping, string $refUnit)
    {
        if (!$materialMapping->hasMapping()) {
            return $materialMapping;
        }

        if (!$materialMapping->hasUnit($refUnit)) {
            return new MaterialMapping(
                $materialMapping->materialName(),
                null,
                $materialMapping->ratio(),
                null,
                []
            );
        }

        return $materialMapping;
    }
}
