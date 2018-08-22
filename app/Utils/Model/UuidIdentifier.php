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

namespace Utils\Model;

use Ramsey\Uuid\Uuid;

abstract class UuidIdentifier implements Identifier
{
    /**
     * @var string
     */
    private $id;

    /**
     * @return static
     */
    public static function nextIdentity()
    {
        return new static(Uuid::uuid4()->toString());
    }

    /**
     * UuidIdentifier constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        if (false === Uuid::isValid($id)) {
            throw new \UnexpectedValueException('Invalid identifier');
        }

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function value()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->id;
    }

    /**
     * Determine equality with another Value Object
     *
     * @param UuidIdentifier $object
     * @return bool
     */
    public function equals(UuidIdentifier $object)
    {
        return $this->id === $object->id;
    }

}

