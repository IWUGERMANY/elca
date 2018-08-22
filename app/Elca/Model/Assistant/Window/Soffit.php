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

namespace Elca\Model\Assistant\Window;

use Elca\Model\Common\Geometry\Rectangle;

/**
 * Soffit
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soffit
{
    /**
     * @var Rectangle
     */
    private $boundary;

    /**
     * @var number
     */
    private $size;

    /**
     * @var int
     */
    private $materialId;

    /**
     * @param Rectangle $boundary
     * @param number    $size
     * @param int       $materialId
     */
    public function __construct(Rectangle $boundary, $size, $materialId = null)
    {
        $this->boundary = $boundary;
        $this->size = $size;
        $this->materialId = $materialId;
    }

    /**
     * @return Rectangle
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * @return number
     */
    public function getDepth()
    {
        return $this->boundary->getWidth();
    }

    /**
     * @return number
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getMaterialId()
    {
        return $this->materialId;
    }
}
