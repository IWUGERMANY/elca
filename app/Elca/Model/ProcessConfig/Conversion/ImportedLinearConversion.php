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

namespace Elca\Model\ProcessConfig\Conversion;

use Elca\Model\Common\Unit;

class ImportedLinearConversion extends LinearConversion
{
    /**
     * @var ConversionType
     */
    private $type;

    public static function forReferenceUnit(Unit $procRefUnit)
    {
        return new self($procRefUnit, $procRefUnit, 1, ConversionType::production());
    }

    public function __construct(Unit $fromUnit, Unit $toUnit, float $factor, ConversionType $conversionType)
    {
        parent::__construct($fromUnit, $toUnit, $factor);

        $this->type = $conversionType;
    }

    public function type(): ConversionType
    {
        return $this->type ?? parent::type();
    }

    public function toLinearConversion(): LinearConversion
    {

    }

}
