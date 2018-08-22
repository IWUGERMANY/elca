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

namespace ImportAssistant\Model;

use Elca\Validator\ElcaValidator;
use ImportAssistant\Model\Import\Element;
use ImportAssistant\Model\Import\ProjectVariant;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;

class Validator extends ElcaValidator
{
    /**
     * @param ProjectVariant $variant
     * @return bool
     */
    public function assertElements300(ProjectVariant $variant)
    {
        foreach ($variant->elements() as $element) {
            if (((string)$element->dinCode())[0] === '3') {
                $this->assertElement($element);
            }
        }

        return $this->isValid();
    }

    /**
     * @param ProjectVariant $variant
     * @return bool
     */
    public function assertElements400(ProjectVariant $variant)
    {
        foreach ($variant->elements() as $element) {
            if (((string)$element->dinCode())[0] === '4') {
                $this->assertElement($element);
            }
        }

        return $this->isValid();
    }

    /**
     * @param ProjectVariant $variant
     * @return bool
     */
    public function assertFinalEnergy(ProjectVariant $variant)
    {
        foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
            $this->assertMapping(
                $finalEnergyDemand->materialMapping(),
                $finalEnergyDemand->uuid(),
                $finalEnergyDemand->materialMapping()->materialName().' has no mapping'
            );
        }

        foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
            $this->assertMapping(
                $finalEnergySupply->materialMapping(),
                $finalEnergySupply->uuid(),
                $finalEnergySupply->materialMapping()->materialName().' has no mapping'
            );
        }

        return $this->isValid();
    }


    /**
     * @param Element $element
     * @return bool
     */
    private function assertElement(Element $element)
    {
        foreach ($element->allComponents() as $component) {
            $this->assertMapping(
                $component->materialMapping(),
                $component->uuid(),
                $component->materialMapping()->materialName().' has no mapping'
            );
        }
    }

    /**
     * @param MaterialMapping $mapping
     * @param                 $property
     * @param                 $error
     * @return bool
     */
    private function assertMapping(MaterialMapping $mapping, $property, $error): bool
    {
        if (false === $mapping->hasMapping()) {
            return $this->setError($property, $error);
        }

        return $this->setAsserted($property);
    }
}
