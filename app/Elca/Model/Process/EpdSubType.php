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

class EpdSubType
{
    private const GENERIC = 'generic';
    private const AVERAGE = 'average';
    private const REPRESENTATIVE = 'representative';
    private const SPECIFIC = 'specific';

    private $subType;

    public static function generic(): EpdSubType
    {
        return new self(self::GENERIC);
    }

    public static function average(): EpdSubType
    {
        return new self(self::AVERAGE);
    }
    public static function representative(): EpdSubType
    {
        return new self(self::REPRESENTATIVE);
    }
    public static function specific(): EpdSubType
    {
        return new self(self::SPECIFIC);
    }

    public function __construct(string $subType)
    {
        $this->subType = $subType;
    }

    public function value(): string
    {
        return $this->subType;
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function equals(EpdSubType $epdSubType): bool
    {
        return $this->value() === $epdSubType->value();
    }
}
