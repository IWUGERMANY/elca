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

namespace ImportAssistant\Model\MaterialMapping;

class MaterialMapping
{
    private $materialName;

    private $mapsToProcessConfigId;

    /**
     * @var null|string
     */
    private $processConfigName;

    private $ratio;

    /**
     * @var array
     */
    private $validUnits;

    /**
     * @var int|null
     */
    private $surrogateId;

    /**
     * @var array
     */
    private $epdSubTypes;

    /**
     * @var array
     */
    private $processDbIds;

    /**
     * MaterialMappingInfo constructor.
     *
     * @param string      $materialName
     * @param int         $mapsToProcessConfigId
     * @param int         $ratio
     * @param string|null $processConfigName
     * @param array       $validUnits
     * @param array       $epdSubTypes
     * @param array       $processDbIds
     */
    public function __construct(
        string $materialName,
        int $mapsToProcessConfigId = null,
        $ratio = null,
        string $processConfigName = null,
        array $validUnits = [],
        array $epdSubTypes = [],
        array $processDbIds = []
    ) {
        $this->materialName          = $materialName;
        $this->mapsToProcessConfigId = $mapsToProcessConfigId;
        $this->ratio                 = $ratio ?? 1;
        $this->validUnits            = array_combine($validUnits, $validUnits);
        $this->processConfigName = $processConfigName;
        $this->epdSubTypes = $epdSubTypes;
        $this->processDbIds = $processDbIds;
    }

    /**
     * @return string
     */
    public function materialName()
    {
        return $this->materialName;
    }

    /**
     * @return bool
     */
    public function hasMapping()
    {
        return null !== $this->mapsToProcessConfigId;
    }

    /**
     * @return int|null
     */
    public function mapsToProcessConfigId()
    {
        return $this->mapsToProcessConfigId;
    }

    /**
     * @return int
     */
    public function ratio()
    {
        return $this->ratio;
    }

    public function processConfigName() : ?string
    {
        return $this->processConfigName;
    }

    /**
     * @return array
     */
    public function validUnits()
    {
        return $this->validUnits;
    }

    /**
     * @return array
     */
    public function epdSubTypes()
    {
        return $this->epdSubTypes;
    }

    /**
     * @return array
     */
    public function processDbIds()
    {
        return $this->processDbIds;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->materialName();
    }

    /**
     * @param $refUnit
     * @return bool
     */
    public function hasUnit($refUnit)
    {
        return isset($this->validUnits[$refUnit]);
    }

    /**
     * @param string $prefix
     * @return MaterialMapping
     */
    public function prefixMaterialName(string $prefix): MaterialMapping
    {
        return new MaterialMapping(
            $prefix . $this->materialName(),
            $this->mapsToProcessConfigId(),
            $this->ratio(),
            $this->processConfigName(),
            $this->validUnits(),
            $this->epdSubTypes(),
            $this->processDbIds()
        );
    }

    /**
     * @return int|null
     */
    public function surrogateId()
    {
        return $this->surrogateId;
    }

    /**
     * @param int $surrogateId
     */
    public function setSurrogateId(int $surrogateId)
    {
        $this->surrogateId = $surrogateId;
    }
}
