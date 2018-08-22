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

namespace Elca\Model\Common\Transform;

class ArrayOfObjects
{
    private $list;

    public static function from(array $list): ArrayOfObjects
    {
        return new self($list);
    }

    /**
     * @param object[] $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    /**
     * @param array          $properties
     * @param array|callback $collectProperties
     * @return array
     */
    public function groupBy(array $properties, $collectProperties): array
    {
        if (is_array($collectProperties)) {
            $collectPropertiesFn = function ($current, $item) use ($collectProperties) {

                foreach ($collectProperties as $keyProperty => $valueProperty) {

                    $value    = $item->$valueProperty ?? null;
                    $property = $item->$keyProperty ?? $valueProperty;

                    $current->$property = $value;
                }

                return $current;
            };
        } else {
            $collectPropertiesFn = $collectProperties;
        }

        $result            = [];
        $lastPropertyIndex = count($properties) - 1;
        foreach ($this->list as $item) {

            $current = &$result;

            foreach ($properties as $index => $property) {
                if (!isset($item->$property)) {
                    continue;
                }

                $key = $item->$property;

                if (!isset($current[$key])) {
                    $current[$key] = $index < $lastPropertyIndex ? [] : new \stdClass;
                }

                $last    = &$current;
                $current = &$current[$key];
            }

            $collectPropertiesFn($current, $item);
        }

        return $result;
    }

    public function mapPropertyToObject(string $propertyName): array
    {
        $result = [];
        foreach ($this->list as $item) {
            $result[(string)$item->$propertyName()] = $item;
        }

        return $result;
    }

    public function mapPropertyToProperty(string $propertyForKey, string $propertyForValue): array
    {
        $result = [];
        foreach ($this->list as $item) {
            $result[(string)$item->$propertyForKey()] = $item->$propertyForValue();
        }

        return $result;
    }

}
