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

class ProcessDb
{
    /**
     * @var ProcessDbId
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string|null
     */
    private $sourceUri;

    /**
     * flags the database as active
     *
     * @var bool
     */
    private $isActive;

    /**
     * is EN 15804 comliant
     *
     * @var bool
     */
    private $isEn15804Compliant;

    public function __construct(ProcessDbId $id, string $name, string $version, string $uuid, ?string $sourceUri)
    {
        $this->id        = $id;
        $this->name      = $name;
        $this->version   = $version;
        $this->uuid      = $uuid;
        $this->sourceUri = $sourceUri;

        $this->isActive = false;
        $this->isEn15804Compliant = true;
    }

    public function id(): ProcessDbId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function sourceUri(): ?string
    {
        return $this->sourceUri;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isEn15804Compliant(): bool
    {
        return $this->isEn15804Compliant;
    }
}
