<?php
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Model\Common\Attribute;

use Utils\Model\SurrogateIdTrait;

abstract class Attribute
{
    use SurrogateIdTrait;

    /**
     * @var string
     */
    private $ident;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(string $ident, $value)
    {
        $this->ident = $ident;
        $this->value = $value;
    }

    public function ident(): string
    {
        return $this->ident;
    }

    public function value()
    {
        return $this->value;
    }

    public function hasNumericValue(): bool
    {
        return \is_float($this->value) || \is_int($this->value);
    }

    public function hasTextValue(): bool
    {
        return \is_string($this->value);
    }

    public function equals(Attribute $object): bool
    {
        return $this == $object;
    }
}