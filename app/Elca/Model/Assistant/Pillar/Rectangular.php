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

namespace Elca\Model\Assistant\Pillar;

class Rectangular implements ConstructionShape
{
    /**
     * @var number
     */
    private $width;

    /**
     * @var number
     */
    private $length;

    /**
     * Rectangular constructor.
     *
     * @param number $width
     * @param number $length
     */
    public function __construct($width, $length)
    {
        $this->width  = $width;
        $this->length = $length;
    }

    /**
     * @return number
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * @return number
     */
    public function width()
    {
        return $this->width;
    }

    /**
     * @return number
     */
    public function volume()
    {
        return $this->width() * $this->length();
    }

    /**
     * @return number
     */
    public function surface()
    {
        return 2 * ($this->width() + $this->length());
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        return (object)[
            'width'  => $this->width(),
            'length' => $this->length(),
        ];
    }
}
