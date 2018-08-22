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

class ProcessId
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string|null
     */
    private $uuid;

    /**
     * @param int $id
     * @param string $uuid
     */
    public function __construct(int $id, string $uuid = null)
    {
        $this->id = $id;
        $this->uuid = $uuid;
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this->id;
    }

    public function uuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function equals(self $object): bool
    {
        return $this->id === $object->id;
    }
}
