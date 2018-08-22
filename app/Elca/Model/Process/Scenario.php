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

namespace Elca\Model\Process;

use Utils\Model\SurrogateIdTrait;

class Scenario
{
    use SurrogateIdTrait;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isDefault;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $group;

    /**
     * Scenario constructor.
     *
     * @param string      $name
     * @param string      $group
     * @param bool        $isDefault
     * @param string|null $description
     */
    public function __construct(string $name, bool $isDefault = false, string $description = null, string $group = null)
    {
        $this->name      = $name;
        $this->isDefault = $isDefault;
        $this->description = $description;
        $this->group     = $group;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @return string
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function group(): ?string
    {
        return $this->group;
    }
}
