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

abstract class SequenceIdentifier implements Identifier
{
    /**
     * @var int
     */
    private $id;

    /**
     * SequenceIdentifier constructor.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        if (false === \is_numeric($id)) {
            throw new \UnexpectedValueException('Invalid identifier');
        }

        $this->id = (int)$id;
    }

    /**
     * @return int
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
     * @param SequenceIdentifier $object
     * @return bool
     */
    public function equals(SequenceIdentifier $object)
    {
        return $this->id === $object->id;
    }
}

