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

use Elca\Elca;
use Elca\Model\Assistant\Material\Material;
use Verraes\ClassFunctions\ClassFunctions;

class Pillar
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ConstructionShape
     */
    private $constructionShape;

    /**
     * @var Material
     */
    private $material1;

    /**
     * @var Material|null
     */
    private $material2;

    /**
     * @var $height
     */
    private $height;

    /**
     * @var string
     */
    private $unit;

    public static function createDefault($initName = null)
    {
        return new Pillar(
            $initName ?? 'Stütze',
            new Rectangular(1, 1),
            null
        );
    }

    /**
     * Pillar constructor.
     *
     * @param string            $name
     * @param ConstructionShape $shape
     * @param Material          $material1
     * @param Material|null     $material2
     * @param number|int        $height
     * @param string            $unit
     */
    public function __construct(
        string $name,
        ConstructionShape $shape,
        Material $material1 = null,
        Material $material2 = null,
        $height = 1,
        $unit = Elca::UNIT_M
    ) {
        $this->name              = $name;
        $this->constructionShape = $shape;
        $this->material1         = $material1;
        $this->material2         = $material2;
        $this->height            = $height;
        $this->unit              = $unit;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return number
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * @var number|int
     */
    public function changeHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return number|int
     */
    public function amount()
    {
        return Elca::UNIT_STK === $this->unit() ? 1 : $this->height();
    }

    /**
     * @return number|int
     */
    public function layerHeight()
    {
        return Elca::UNIT_STK === $this->unit() ? $this->height() : 1;
    }

    /**
     * @return string
     */
    public function unit()
    {
        return $this->unit;
    }

    /**
     * @return Material
     */
    public function material1()
    {
        return $this->material1;
    }

    /**
     * @return Material|null
     */
    public function material2()
    {
        return $this->material2;
    }

    /**
     * @return number
     */
    public function length()
    {
        return $this->constructionShape()->length();
    }

    /**
     * @return number
     */
    public function width()
    {
        return $this->constructionShape()->width();
    }

    /**
     * @return number
     */
    public function volume()
    {
        return $this->constructionShape()->volume() * $this->height();
    }

    /**
     * @return number
     */
    public function surface()
    {
        return $this->constructionShape()->surface() * $this->height();
    }

    /**
     * @return string
     */
    public function shape()
    {
        return \utf8_strtolower(ClassFunctions::short($this->constructionShape()));
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        $constructionShape = $this->constructionShape();

        $dataObject = [
            'name'           => $this->name(),
            'shape'          => get_class($constructionShape),
            'material1'      => null !== $this->material1 ? $this->material1->getMaterialId() : null,
            'material1Share' => null !== $this->material2 ? $this->material1->getShare() : 1,
            'material2'      => null !== $this->material2 ? $this->material2->getMaterialId() : null,
            'material2Share' => null !== $this->material2 ? $this->material2->getShare() : 0,
            'height'         => $this->height(),
            'unit'           => $this->unit(),
            'surface' => $this->surface(),
        ];

        foreach ($constructionShape->getDataObject() as $key => $value) {
            $dataObject[$key] = $value;
        }

        return (object)$dataObject;
    }

    /**
     * @return ConstructionShape
     */
    private function constructionShape()
    {
        return $this->constructionShape;
    }
}
