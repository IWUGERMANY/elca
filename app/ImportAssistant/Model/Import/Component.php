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

use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use Ramsey\Uuid\Uuid;

abstract class Component
{
    private $uuid;

    private $materialMapping;

    private $quantity;

    private $dinCode;

    /**
     * Component constructor.
     *
     * @param MaterialMapping $materialMapping
     * @param                 $quantity
     */
    public function __construct(MaterialMapping $materialMapping, $quantity = 1, $din276Code = null)
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->materialMapping = $materialMapping;
        $this->quantity = $quantity;
        $this->dinCode = $din276Code;
    }

    /**
     * @return string
     */
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return MaterialMapping
     */
    public function materialMapping()
    {
        return $this->materialMapping;
    }

    /**
     * @return mixed
     */
    public function quantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $newProcessConfigId
     */
    public function replaceMappedProcessConfigId(int $newProcessConfigId)
    {
        $this->materialMapping = new MaterialMapping(
            $this->materialMapping->materialName(),
            $newProcessConfigId,
            $this->materialMapping->ratio()
        );
    }

    /**
     * @return bool
     */
    public function hasDin276Code()
    {
        return null !== $this->dinCode;
    }


    /**
     * @return null
     */
    public function dinCode()
    {
        return $this->dinCode;
    }

    /**
     * @param null $dinCode
     */
    public function setDinCode($dinCode)
    {
        $this->dinCode = $dinCode;
    }

    abstract public function isLayer();
    abstract public function layerPosition();
    abstract public function layerSize();
    abstract public function layerAreaRatio();
    abstract public function layerLength();
    abstract public function layerWidth();
    abstract public function refUnit();
}
