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

declare(strict_types=1);

namespace ImportAssistant\Model\Import;

use Elca\Elca;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;

class LayerComponent extends Component
{
    private $layerPosition;
    private $layerSize;
    private $layerAreaRatio;
    private $layerLength;
    private $layerWidth;

    private $isSiblingOf;

    /**
     * LayerComponent constructor.
     *
     * @param MaterialMapping $materialMapping
     * @param $layerPosition
     * @param $layerSize
     * @param $layerAreaRatio
     * @param $layerLength
     * @param $layerWidth
     */
    public function __construct(
        MaterialMapping $materialMapping,
        $layerPosition,
        $layerSize,
        $layerAreaRatio,
        $layerLength,
        $layerWidth
    ) {
        parent::__construct($materialMapping);

        $this->layerPosition   = $layerPosition;
        $this->layerSize       = (float)$layerSize;
        $this->layerAreaRatio  = (float)$layerAreaRatio;
        $this->layerLength     = (float)$layerLength;
        $this->layerWidth      = (float)$layerWidth;
    }


    /**
     * @return mixed
     */
    public function isSibling()
    {
        return null !== $this->isSiblingOf;
    }

    /**
     * @return LayerComponent
     */
    public function isSiblingOf()
    {
        return $this->isSiblingOf;
    }

    /**
     * @param LayerComponent $isSiblingOf
     */
    public function setIsSiblingOf(LayerComponent $isSiblingOf)
    {
        $this->isSiblingOf = $isSiblingOf;
    }

    public function isLayer()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function layerPosition()
    {
        return $this->layerPosition;
    }

    /**
     * @return mixed
     */
    public function layerSize()
    {
        return $this->layerSize;
    }

    /**
     * @return mixed
     */
    public function layerAreaRatio()
    {
        return $this->layerAreaRatio;
    }

    /**
     * @return mixed
     */
    public function layerLength()
    {
        return $this->layerLength;
    }

    /**
     * @return mixed
     */
    public function layerWidth()
    {
        return $this->layerWidth;
    }

    public function refUnit()
    {
        return Elca::UNIT_M3;
    }

    /**
     * @param null $dinCode
     */
    public function setDinCode($dinCode)
    {
        parent::setDinCode($dinCode);

        if ($this->isSibling() && $this->isSiblingOf()->dinCode() !== $dinCode) {
            $this->isSiblingOf()->setDinCode($this->dinCode());
        }
    }


}
