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

class ProjectVariant
{
    private $name;
    private $street;
    private $postcode;
    private $city;
    private $country;

    private $grossFloorSpace;
    private $netFloorSpace;
    private $floorSpace;
    private $propertySize;

    private $elements;

    private $finalEnergyDemands;
    private $finalEnergySupplies;
    private $refModels;

    private $ngfEnEv;

    private $enEvVersion;

    /**
     * ProjectVariant constructor.
     *
     * @param       $name
     * @param       $street
     * @param       $postcode
     * @param       $city
     * @param       $country
     * @param       $grossFloorSpace
     * @param       $netFloorSpace
     * @param       $floorSpace
     * @param       $propertySize
     * @param       $ngfEnEv
     * @param       $enEvVersion
     * @param array $elements
     * @param array $finalEnergyDemands
     * @param array $finalEnergySupplies
     * @param array $refModels
     */
    public function __construct(
        $name,
        $street,
        $postcode,
        $city,
        $country,
        $grossFloorSpace,
        $netFloorSpace,
        $floorSpace,
        $propertySize,
        $ngfEnEv,
        $enEvVersion,
        array $elements = [],
        array $finalEnergyDemands = [],
        array $finalEnergySupplies = [],
        array $refModels = []
    ) {
        $this->name                = $name;
        $this->street              = $street;
        $this->postcode            = $postcode;
        $this->city                = $city;
        $this->country             = $country;
        $this->grossFloorSpace     = $grossFloorSpace;
        $this->netFloorSpace       = $netFloorSpace;
        $this->floorSpace          = $floorSpace;
        $this->propertySize        = $propertySize;
        $this->elements            = $elements;
        $this->finalEnergyDemands  = $finalEnergyDemands;
        $this->finalEnergySupplies = $finalEnergySupplies;
        $this->refModels           = $refModels;
        $this->ngfEnEv = $ngfEnEv;
        $this->enEvVersion = $enEvVersion;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function street()
    {
        return $this->street;
    }

    /**
     * @return mixed
     */
    public function postcode()
    {
        return $this->postcode;
    }

    /**
     * @return mixed
     */
    public function city()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function country()
    {
        return $this->country;
    }

    /**
     * @return mixed
     */
    public function grossFloorSpace()
    {
        return $this->grossFloorSpace;
    }

    /**
     * @return mixed
     */
    public function netFloorSpace()
    {
        return $this->netFloorSpace;
    }

    /**
     * @return mixed
     */
    public function floorSpace()
    {
        return $this->floorSpace;
    }

    /**
     * @return mixed
     */
    public function propertySize()
    {
        return $this->propertySize;
    }

    /**
     * @return Element[]
     */
    public function elements()
    {
        return $this->elements;
    }

    /**
     * @return array|FinalEnergyDemand[]
     */
    public function finalEnergyDemands()
    {
        return $this->finalEnergyDemands;
    }

    /**
     * @return array|FinalEnergySupply[]
     */
    public function finalEnergySupplies()
    {
        return $this->finalEnergySupplies;
    }

    /**
     * @return array|RefModel[]
     */
    public function refModels()
    {
        return $this->refModels;
    }

    /**
     * @return mixed
     */
    public function ngfEnEv()
    {
        return $this->ngfEnEv;
    }

    /**
     * @return mixed
     */
    public function enEvVersion()
    {
        return $this->enEvVersion;
    }
}
