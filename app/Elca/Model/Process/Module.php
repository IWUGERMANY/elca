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

class Module
{
    public const A1 = 'A1';
    public const A2 = 'A2';
    public const A3 = 'A3';
    public const A13 = 'A1-3';
    public const A4 = 'A4';
    public const A5 = 'A5';
    public const B1 = 'B1';
    public const B2 = 'B2';
    public const B3 = 'B3';
    public const B4 = 'B4';
    public const B5 = 'B5';
    public const B6 = 'B6';
    public const B7 = 'B7';
    public const C1 = 'C1';
    public const C2 = 'C2';
    public const C3 = 'C3';
    public const C4 = 'C4';
    public const D = 'D';

    private static $stageMap = [
        self::A13   => Stage::PROD,
        self::A1    => Stage::PROD,
        self::A2    => Stage::PROD,
        self::A3    => Stage::PROD,
        self::A4    => Stage::PROD,
        self::A5    => Stage::PROD,
        self::B1    => Stage::USE,
        self::B2    => Stage::USE,
        self::B3    => Stage::USE,
        self::B4    => Stage::USE,
        self::B5    => Stage::USE,
        self::B6    => Stage::USE,
        self::B7    => Stage::USE,
        self::C1    => Stage::EOL,
        self::C2    => Stage::EOL,
        self::C3    => Stage::EOL,
        self::C4    => Stage::EOL,
        self::D     => Stage::REC,
        Stage::PROD => Stage::PROD,
        Stage::USE  => Stage::USE,
        Stage::EOL  => Stage::EOL,
        Stage::MAINT => Stage::MAINT,
        Stage::TOTAL => Stage::TOTAL,
    ];

    /**
     * @var string
     */
    private $value;

    public static function a13(): Module
    {
        return new self(self::A13);
    }

    public static function a1(): Module
    {
        return new self(self::A1);
    }
    public static function a2(): Module
    {
        return new self(self::A2);
    }
    public static function a3(): Module
    {
        return new self(self::A3);
    }

    public static function b6(): Module
    {
        return new self(self::B6);
    }

    public static function c3(): Module
    {
        return new self(self::C3);
    }

    public static function c4(): Module
    {
        return new self(self::C4);
    }

    public static function d(): Module
    {
        return new self(self::D);
    }

    /**
     * Legacy constructor
     */
    public static function production(): Module
    {
        return new self(Stage::PROD);
    }

    /**
     * Legacy constructor
     */
    public static function usage(): Module
    {
        return new self(Stage::USE);
    }

    /**
     * Legacy constructor
     */
    public static function endOfLife(): Module
    {
        return new self(Stage::EOL);
    }

    public static function maintenance(): Module
    {
        return new self(Stage::MAINT);
    }

    public static function fromValue(string $module): Module
    {
        return new self($module);
    }

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->isLegacy() ? self::$stageMap[$this->value] : $this->value;
    }

    public function stage(): Stage
    {
        return new Stage(self::$stageMap[$this->value]);
    }

    public function isLegacy(): bool
    {
        return $this->value === self::$stageMap[$this->value];
    }

    public function isA13(): bool
    {
        return self::A13 === $this->value;
    }
    public function isA1(): bool
    {
        return self::A1 === $this->value;
    }
    public function isA2(): bool
    {
        return self::A2 === $this->value;
    }
    public function isA3(): bool
    {
        return self::A3 === $this->value;
    }
    public function isA4(): bool
    {
        return self::A4 === $this->value;
    }
    public function isA5(): bool
    {
        return self::A5 === $this->value;
    }

    public function isA1A2OrA3(): bool
    {
        return \in_array($this->value, [self::A1, self::A2, self::A3], true);
    }

    public function isMaintenance(): bool
    {
        return Stage::MAINT === $this->value;
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function equals(self $object): bool
    {
        return $this->value === $object->value;
    }
}
