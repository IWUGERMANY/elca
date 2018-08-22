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

namespace Elca\Model\ProcessConfig;

use Elca\Model\Process\ProcessCategoryId;
use Ramsey\Uuid\Uuid;

class ProcessConfig
{
    /**
     * @var ProcessConfigId
     */
    private $id;

    /**
     * @var ProcessCategoryId
     */
    private $categoryId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var SvgPatternId
     */
    private $svgPatternId;

    /**
     * @var UsefulLifes
     */
    private $usefulLifes;

    /**
     * @var bool
     */
    private $isPublished;

    /**
     * @var float
     */
    private $defaultSize;

    /**
     * Hi/Hs
     *
     * @var float
     */
    private $energyEfficiency;

    /**
     * @var string
     */
    private $uuid;

    public function __construct(
        ProcessConfigId $id, ProcessCategoryId $categoryId, string $name, UsefulLifes $usefulLifes = null,
        string $description = null, float $defaultSize = null, float $energyEfficiency = null
    ) {
        $this->id               = $id;
        $this->categoryId       = $categoryId;
        $this->name             = $name;
        $this->description      = $description;
        $this->usefulLifes      = $usefulLifes;
        $this->defaultSize      = $defaultSize;
        $this->energyEfficiency = $energyEfficiency;

        $this->uuid             = Uuid::uuid4();
        $this->isPublished = true;
    }

    public function id(): ProcessConfigId
    {
        return $this->id;
    }

    public function categoryId(): ProcessCategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function svgPatternId(): ?SvgPatternId
    {
        return $this->svgPatternId;
    }

    public function usefulLifes(): ?UsefulLifes
    {
        return $this->usefulLifes;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function defaultSize(): ?float
    {
        return $this->defaultSize;
    }

    public function energyEfficiency(): ?float
    {
        return $this->energyEfficiency;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }
}
