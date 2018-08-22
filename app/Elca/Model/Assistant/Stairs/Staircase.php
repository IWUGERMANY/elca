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

namespace Elca\Model\Assistant\Stairs;

use Elca\Model\Assistant\Stairs\Construction\Construction;
use Elca\Model\Assistant\Stairs\Steps\Step;
use Elca\Model\Assistant\Stairs\Steps\Steps;

/**
 * Staircase
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
abstract class Staircase
{
    const TYPE_SOLID = 'solid';
    const TYPE_STRINGER = 'stringer';
    const TYPE_MIDDLE_HOLM = 'middle-holm';

    const DEFAULT_WIDTH = 1;
    const DEFAULT_STEP_DEPTH = 0.25;
    const DEFAULT_STEP_HEIGHT = 0.25;
    const DEFAULT_COVER_SIZE = 0.02;
    const DEFAULT_SLAB_HEIGHT = 0.2;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Steps
     */
    private $steps;

    /**
     * @var Construction
     */
    private $construction;

    /**
     * @var Platform
     */
    private $platform;

    /**
     * Staircase constructor.
     *
     * @param Step $step
     * @param int  $numberOfSteps
     */
    public function __construct($name, Step $step, $numberOfSteps = 1)
    {
        $this->name = $name;

        if ($numberOfSteps < 1)
            $numberOfSteps = 1;

        $this->steps = new Steps($step, $numberOfSteps);
    }

    abstract public function getType();

    /**
     * @param     $width
     * @param     $height
     * @param int $amount
     */
    public function specifyPlatform($width, $height, $amount = 1)
    {
        $this->platform = new Platform($width, $height, $amount);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Steps
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @return Construction
     */
    public function getConstruction()
    {
        return $this->construction;
    }

    /**
     * @return bool
     */
    public function hasPlatform()
    {
        return $this->platform !== null;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        $step = $this->steps->getStep();
        $construction = $this->getConstruction();
        $data = [
            'name' => $this->name,
            'width' => $step->getWidth(),
            'stepDepth' => $step->getDepth() * 100,
            'stepHeight' => $step->getHeight() * 100,
            'stepDegree' => $step->getDegree() * 100,
            'numberOfSteps' => $this->steps->getAmount(),
            'stepsLength' => $construction->getCalculatedLength($this->getSteps()),
            'coverSize' => $step->getCover()->getSize() * 100,
            'coverLength1' => $step->getCover()->getLength1() * 100,
            'coverLength2' => $step->getCover()->getLength2() * 100,
            'isTrapezoid' => $step->getCover()->isTrapezoid(),
            'riserHeight' => $step->hasRiser()? $step->getRiser()->getHeight() * 100 : null,
            'riserSize' => $step->hasRiser() && $step->getRiser()->getSize() ? $step->getRiser()->getSize() * 100 : null,
            'materialId' => [
                'cover' => $step->getCover()->getMaterial()->getMaterialId(),
                'riser' => $step->hasRiser()? $step->getRiser()->getMaterial()->getMaterialId() : null
            ]
        ];

        foreach ($this->getConstruction()->getDataObject() as $key => $value) {
            if (is_array($value)) {
                $data[$key] = array_merge($data[$key], $value);
            } else {
                $data[$key] = $value;
            }
        }

        if ($this->hasPlatform()) {
            foreach ($this->getPlatform()->getDataObject() as $key => $value) {
                $data[$key] = $value;
            }
        }

        return (object)$data;
    }


    /**
     * @param Construction $construction
     */
    protected function setConstruction(Construction $construction)
    {
        $this->construction = $construction;
    }

}
