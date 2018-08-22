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

class ProcessName
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $originalName;

    public function __construct(string $name, string $originalName = null)
    {
        $this->name         = $name;
        $this->originalName = $originalName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function originalName(): ?string
    {
        return $this->originalName;
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function equals(ProcessName $processName): bool
    {
        return $this == $processName;
    }
}
