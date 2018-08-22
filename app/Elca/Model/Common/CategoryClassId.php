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

namespace Elca\Model\Common;

use Assert\InvalidArgumentException;

class CategoryClassId
{
    /**
     * @var int[]
     */
    private $levels;

    public static function fromString(string $categoryClassId): self
    {
        $levels = \explode('.', $categoryClassId);

        $cleaned = \array_map(
            function (string $level) {
                return (int)$level;
            },
            $levels
        );

        return new self(...$cleaned);
    }

    public function __construct(int ...$levels)
    {
        if (!$levels) {
            throw new \InvalidArgumentException('At least one level has to be specified');
        }

        $this->levels = $levels;
    }

    public function numberOfLevels(): int
    {
        return \count($this->levels);
    }

    /**
     * @param int $nthLevel starting at 1
     * @return string
     */
    public function nthLevel(int $nthLevel): string
    {
        if (0 === $nthLevel || $nthLevel > $this->numberOfLevels()) {
            throw new \InvalidArgumentException('Invalid index');
        }

        $value = $this->levels[$nthLevel - 1];

        return $nthLevel > 1 ? $this->formatValue($value) : (string)$value;
    }

    public function parent(): CategoryClassId
    {
        $levels = $this->levels;
        \array_pop($levels);

        return new self(...$levels);
    }

    public function nextSibling(): CategoryClassId
    {
        $siblingLevels = $this->levels;
        ++$siblingLevels[$this->lastLevelIndex()];

        return new self(...$siblingLevels);
    }

    public function previousSibling(): CategoryClassId
    {
        $lastLevelIndex = $this->lastLevelIndex();

        $siblingLevels = $this->levels;
        --$siblingLevels[$lastLevelIndex];

        return new self(...$siblingLevels);
    }

    public function toString(): string
    {
        $levels = $this->levels;
        $parts = [
            (string)\array_shift($levels)
        ];

        foreach ($levels as $level) {
            $parts[] = $this->formatValue($level);
        }

        return \implode('.', $parts);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function lastLevelIndex(): int
    {
        return \count($this->levels) - 1;
    }

    private function formatValue(int $level): string
    {
        return \str_pad((string)$level, 2, '0', STR_PAD_LEFT);
    }
}
