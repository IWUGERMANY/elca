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

use Assert\Assert;

final class Stage
{
    public const PROD = 'prod';
    public const USE = 'op';
    public const EOL = 'eol';
    public const REC = 'rec';
    public const MAINT = 'maint';
    public const TOTAL = 'total';

    private static $names = [
        self::PROD  => 'Production',
        self::USE   => 'Use',
        self::EOL   => 'End of life',
        self::REC   => 'Recycling',
        self::MAINT => 'Maintenance',
        self::TOTAL => 'Total',
    ];

    /**
     * @var string
     */
    private $value;

    public static function production(): Stage
    {
        return new self(self::PROD);
    }

    public static function usage(): Stage
    {
        return new self(self::USE);
    }

    public static function endOfLife(): Stage
    {
        return new self(self::EOL);
    }

    public static function recycling(): Stage
    {
        return new self(self::REC);
    }

    public static function maintenance(): Stage
    {
        return new self(self::MAINT);
    }

    public static function total(): Stage
    {
        return new self(self::TOTAL);
    }

    /**
     * Phase constructor.
     *
     * @param $value
     */
    public function __construct(string $value)
    {
        Assert::that($value)
            ->inArray(
                [self::PROD, self::USE, self::EOL, self::REC, self::MAINT, self::TOTAL],
                'Unknown stage: %s'
                );

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return self::$names[$this->value];
    }

    public function isProduction(): bool
    {
        return self::PROD === $this->value();
    }

    public function isUsage(): bool
    {
        return self::USE === $this->value();
    }

    public function isEndOfLife(): bool
    {
        return self::EOL === $this->value();
    }

    public function isRecycling(): bool
    {
        return self::REC === $this->value();
    }

    public function isMaintenance(): bool
    {
        return self::MAINT === $this->value();
    }

    public function isTotal(): bool
    {
        return self::TOTAL === $this->value();
    }

    public function equals(Stage $object): bool
    {
        return $this->value === $object->value;
    }

    public function isOneOf(array $stages): bool
    {
        return \in_array($this->value, $stages, true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
