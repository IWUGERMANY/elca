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

class FinalEnergyDemand
{
    private $uuid;

    /**
     * @var MaterialMapping
     */
    private $materialMapping;

    private $heating;
    private $water;
    private $lighting;
    private $ventilation;
    private $cooling;

    /**
     * FinalEnergyDemand constructor.
     *
     * @param MaterialMapping $materialMapping
     * @param                 $heating
     * @param                 $water
     * @param                 $lighting
     * @param                 $ventilation
     * @param                 $cooling
     */
    public function __construct(MaterialMapping $materialMapping, $heating = null, $water = null, $lighting = null, $ventilation = null, $cooling = null)
    {
        $this->uuid = Uuid::uuid4()->toString();

        $this->materialMapping = $materialMapping;
        $this->heating         = $heating;
        $this->water           = $water;
        $this->lighting        = $lighting;
        $this->ventilation     = $ventilation;
        $this->cooling         = $cooling;
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
     * @return null
     */
    public function heating()
    {
        return $this->heating;
    }

    /**
     * @return null
     */
    public function water()
    {
        return $this->water;
    }

    /**
     * @return null
     */
    public function lighting()
    {
        return $this->lighting;
    }

    /**
     * @return null
     */
    public function ventilation()
    {
        return $this->ventilation;
    }

    /**
     * @return null
     */
    public function cooling()
    {
        return $this->cooling;
    }

    /**
     * @param int $newProcessConfigId
     */
    public function replaceMappedProcessConfigId(int $newProcessConfigId)
    {
        $this->materialMapping = new MaterialMapping(
            $this->materialMapping->materialName(),
            $newProcessConfigId
        );
    }
}
