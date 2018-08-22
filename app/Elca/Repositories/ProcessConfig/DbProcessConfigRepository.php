<?php declare(strict_types=1);
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

namespace Elca\Repositories\ProcessConfig;

use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigAttribute;
use Elca\Model\Process\ProcessCategoryId;
use Elca\Model\ProcessConfig\ProcessConfig;
use Elca\Model\ProcessConfig\ProcessConfigAttribute;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Model\ProcessConfig\ProcessConfigRepository;
use Elca\Model\ProcessConfig\SvgPatternId;
use Elca\Model\ProcessConfig\UsefulLife;
use Elca\Model\ProcessConfig\UsefulLifes;
use Utils\Model\FactoryHelper;

class DbProcessConfigRepository implements ProcessConfigRepository
{
    public function findById(ProcessConfigId $id) : ?ProcessConfig
    {
        return $this->build(ElcaProcessConfig::findById($id->value()));
    }

    private function build(ElcaProcessConfig $processConfig) : ?ProcessConfig
    {
        if (!$processConfig->isInitialized()) {
            return null;
        }

        $usefulLives = null;
        $minUsefulLife = $avgUsefulLife = $maxUsefulLife = null;
        if ($processConfig->getMinLifeTime()) {
            $minUsefulLife = new UsefulLife($processConfig->getMinLifeTime(), 0, $processConfig->getMinLifeTimeInfo());
        }

        if ($processConfig->getAvgLifeTime()) {
            $avgUsefulLife = new UsefulLife($processConfig->getAvgLifeTime(), 0, $processConfig->getAvgLifeTimeInfo());
        }

        if ($processConfig->getMaxLifeTime()) {
            $maxUsefulLife = new UsefulLife($processConfig->getMaxLifeTime(), 0, $processConfig->getMaxLifeTimeInfo());
        }

        if (null !== $minUsefulLife || null !== $avgUsefulLife || null !== $maxUsefulLife) {
            $usefulLives = new UsefulLifes($minUsefulLife, $avgUsefulLife, $maxUsefulLife, $processConfig->getLifeTimeInfo());
        }

        return FactoryHelper::createInstanceWithoutConstructor(
            ProcessConfig::class,
            [
                'id' => new ProcessConfigId($processConfig->getId()),
                'categoryId' => new ProcessCategoryId($processConfig->getProcessCategoryNodeId()),
                'name' => $processConfig->getName(),
                'description' => $processConfig->getDescription(),
                'svgPatternId' => $processConfig->getSvgPatternId() ? new SvgPatternId($processConfig->getSvgPatternId()) : null,
                'usefulLifes' => $usefulLives,
                'isPublished' => $processConfig->isReference(),
                'defaultSize' => $processConfig->getDefaultSize(),
                'energyEfficiency' => $processConfig->getFHsHi(),
                'uuid' => $processConfig->getUuid(),
            ]
        );
    }

    public function findAttributeForId(ProcessConfigId $processConfigId, string $attributeIdent): ProcessConfigAttribute
    {
        $dbAttribute = ElcaProcessConfigAttribute::findByProcessConfigIdAndIdent(
            $processConfigId->value(),
            $attributeIdent
        );

        $value = null;
        if ($dbAttribute->isInitialized()) {
            $value = null !== $dbAttribute->getNumericValue()
                ? $dbAttribute->getTextValue() :
                $dbAttribute->getNumericValue();
        }

        $attribute = new ProcessConfigAttribute(
            $processConfigId,
            $attributeIdent,
            $value
        );

        if ($dbAttribute->isInitialized()) {
            $attribute->setSurrogateId($dbAttribute->getId());
        }

        return $attribute;
    }
}
