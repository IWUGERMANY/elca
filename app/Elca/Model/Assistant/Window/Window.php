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

use Assert\Assertion;
use Elca\Model\Common\Geometry\Rectangle;

/**
 * Window
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Window
{
    const DEFAULT_SEALING_WIDTH = 0.02;
    const DEFAULT_SEALING_SIZE = 0.05;
    const DEFAULT_FIXED_FRAME_WIDTH = 0.07;
    const DEFAULT_SASH_FRAME_WIDTH = 0.05;
    const MIN_GLASS_HEIGHT = 0.01;
    const MIN_GLASS_WIDTH = 0.01;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Rectangle
     */
    private $boundary;

    /**
     * @var Frame
     */
    private $sealing;

    /**
     * @var FixedFrame
     */
    private $fixedFrame;

    /**
     * @var Sill
     */
    private $sillIndoor;

    /**
     * @var Sill
     */
    private $sillOutdoor;

    /**
     * @var Soffit
     */
    private $soffitIndoor;

    /**
     * @var Soffit
     */
    private $soffitOutdoor;

    /**
     * @var Component
     */
    private $handles;

    /**
     * @var Component
     */
    private $fittings;

    /**
     * @var int
     */
    private $glassMaterialId;

    /**
     * Sunscreen indoor
     */
    private $sunscreenIndoorMaterialId;

    /**
     * Sunscreen outdoor
     */
    private $sunscreenOutdoorMaterialId;

    /**
     * @param                 $name
     * @param Rectangle       $windowBoundary
     * @param                 $sealingWidth
     * @param                 $fixedFrameWidth
     * @param                 $sealingMaterialId
     * @param                 $fixedFrameMaterialId
     * @param                 $glassMaterialId
     * @param int             $numberOfMullions
     * @param int             $numberOfTransoms
     * @param bool            $fixedMullionsTransoms
     * @param bool            $hasTopLight
     * @param int             $topLightHeight
     * @param array           $tileWidths
     * @param array           $tileHeights
     */
    public function __construct(
        $name,
        Rectangle $windowBoundary,
        $sealingWidth,
        $fixedFrameWidth,
        $sealingMaterialId,
        $fixedFrameMaterialId,
        $glassMaterialId,
        $sashFrameMaterialId = null,
        $sashFrameWidth = 0,
        $numberOfMullions = 0,
        $numberOfTransoms = 0,
        $fixedMullionsTransoms = false,
        $hasTopLight = false,
        $topLightHeight = 0,
        array $tileWidths = [],
        array $tileHeights = []
    ) {
        Assertion::notBlank($name);

        $this->name             = $name;
        $this->boundary         = $windowBoundary;
        $this->numberOfMullions = 0;
        $this->numberOfTransoms = 0;

        $this->specifySealing($sealingMaterialId, $sealingWidth);
        $this->specifyFixedFrame(
            $fixedFrameMaterialId,
            $fixedFrameWidth,
            $sashFrameMaterialId,
            $sashFrameWidth,
            $numberOfMullions,
            $numberOfTransoms,
            $fixedMullionsTransoms,
            $hasTopLight,
            $topLightHeight,
            $tileWidths,
            $tileHeights
        );
        $this->specifyGlass($glassMaterialId);
    }

    /**
     * @return Window
     */
    public static function getDefault($initialName = null)
    {
        return new self(
            $initialName ?: 'Neues Fenster',
            new Rectangle(
                1, 1
            ),
            Window::DEFAULT_SEALING_WIDTH,
            Window::DEFAULT_FIXED_FRAME_WIDTH,
            null, null, null
        );
    }

    /**
     * @param Sill $sill
     */
    public function setIndoorSill(Sill $sill)
    {
        $this->sillIndoor = $sill;
    }

    /**
     * @param Sill $sill
     */
    public function setOutdoorSill(Sill $sill)
    {
        $this->sillOutdoor = $sill;
    }

    /**
     * @param Soffit $soffit
     */
    public function setIndoorSoffit(Soffit $soffit)
    {
        $this->soffitIndoor = $soffit;
    }

    /**
     * @param Soffit $soffit
     */
    public function setOutdoorSoffit(Soffit $soffit)
    {
        $this->soffitOutdoor = $soffit;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
    public function getSealingWidth()
    {
        if ($this->sealing) {
            return $this->sealing->getFrameWidth();
        }

        return self::DEFAULT_SEALING_WIDTH;
    }

    /**
     * @return number
     */
    public function getSealingSize()
    {
        return self::DEFAULT_SEALING_SIZE;
    }

    /**
     * @return Rectangle
     */
    public function getOpeningBoundary()
    {
        if ($this->sealing) {
            return $this->sealing->getOuterBoundary();
        }

        return $this->boundary;
    }

    /**
     * @return Frame
     */
    public function getSealing()
    {
        return $this->sealing;
    }

    /**
     * @return bool
     */
    public function hasFixedFrame()
    {
        return $this->fixedFrame !== null;
    }

    /**
     * @return FixedFrame
     */
    public function getFixedFrame()
    {
        return $this->fixedFrame;
    }

    /**
     * @return int
     */
    public function getGlassMaterialId()
    {
        return $this->glassMaterialId;
    }

    /**
     * The glass ratio is meatured relative to the outer boundary
     *
     * @return float
     */
    public function getGlassRatio()
    {
        return 1 - $this->fixedFrame->getRatio();
    }

    /**
     * @return float
     */
    public function getGlassArea()
    {
        return $this->getBoundary()->getArea() * $this->getGlassRatio();
    }

    /**
     * @return bool
     */
    public function hasHandles()
    {
        return $this->handles !== null;
    }

    /**
     * @return Component
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * @param Component $handles
     */
    public function setHandles(Component $handles)
    {
        $this->handles = $handles;
    }

    /**
     * @return bool
     */
    public function hasFittings()
    {
        return $this->fittings !== null;
    }

    /**
     * @return Component
     */
    public function getFittings()
    {
        return $this->fittings;
    }

    /**
     * @param Component $fittings
     */
    public function setFittings(Component $fittings)
    {
        $this->fittings = $fittings;
    }

    /**
     * @return bool
     */
    public function hasSillIndoor()
    {
        return $this->sillIndoor !== null && $this->sillIndoor->getMaterialId() !== null;
    }

    /**
     * @return bool
     */
    public function hasSillOutdoor()
    {
        return $this->sillOutdoor != null && $this->sillOutdoor->getMaterialId() !== null;
    }

    /**
     * @return bool
     */
    public function hasSoffitIndoor()
    {
        return $this->soffitIndoor !== null && $this->soffitIndoor->getMaterialId() !== null;
    }

    /**
     * @return bool
     */
    public function hasSoffitOutdoor()
    {
        return $this->soffitOutdoor !== null && $this->soffitOutdoor->getMaterialId() !== null;
    }

    /**
     * @return Sill|null
     */
    public function getSillIndoor()
    {
        return $this->sillIndoor;
    }

    /**
     * @return Sill|null
     */
    public function getSillOutdoor()
    {
        return $this->sillOutdoor;
    }

    /**
     * @return Soffit|null
     */
    public function getSoffitIndoor()
    {
        return $this->soffitIndoor;
    }

    /**
     * @return Soffit|null
     */
    public function getSoffitOutdoor()
    {
        return $this->soffitOutdoor;
    }

    /**
     * @return bool
     */
    public function hasSunscreenIndoor()
    {
        return $this->sunscreenIndoorMaterialId !== null;
    }

    /**
     * @return mixed
     */
    public function getSunscreenIndoorMaterialId()
    {
        return $this->sunscreenIndoorMaterialId;
    }

    /**
     * @param int $sunscreenIndoorMaterialId
     */
    public function setSunscreenIndoorMaterialId($sunscreenIndoorMaterialId)
    {
        $this->sunscreenIndoorMaterialId = $sunscreenIndoorMaterialId;
    }

    /**
     * @return bool
     */
    public function hasSunscreenOutdoor()
    {
        return $this->sunscreenOutdoorMaterialId !== null;
    }

    /**
     * @return mixed
     */
    public function getSunscreenOutdoorMaterialId()
    {
        return $this->sunscreenOutdoorMaterialId;
    }

    /**
     * @param int $sunscreenOutdoorMaterialId
     */
    public function setSunscreenOutdoorMaterialId($sunscreenOutdoorMaterialId)
    {
        $this->sunscreenOutdoorMaterialId = $sunscreenOutdoorMaterialId;
    }

    public function unsetIndoorSill()
    {
        $this->sillIndoor = null;
    }

    public function unsetOutdoorSill()
    {
        $this->sillOutdoor = null;
    }

    public function unsetIndoorSoffit()
    {
        $this->soffitIndoor = null;
    }

    public function unsetOutdoorSoffit()
    {
        $this->soffitOutdoor = null;
    }

    public function unsetIndoorSunscreen()
    {
        $this->sunscreenIndoorMaterialId = null;
    }

    public function unsetOutdoorSunscreen()
    {
        $this->sunscreenOutdoorMaterialId = null;
    }

    /**
     * @return object
     */
    public function getDataObject()
    {
        $materialIds = [
            'fixedFrame'       => $this->fixedFrame->getMaterialId(),
            'sealing'          => $this->sealing->getMaterialId(),
            'sashFrame'        => $this->fixedFrame->getSashFrameMaterialId(),
            'fittings'         => $this->hasFittings() ? $this->fittings->getMaterialId() : null,
            'handles'          => $this->hasHandles() ? $this->handles->getMaterialId() : null,
            'glass'            => $this->getGlassMaterialId(),
            'sillIndoor'       => $this->hasSillIndoor() ? $this->sillIndoor->getMaterialId() : null,
            'sillOutdoor'      => $this->hasSillOutdoor() ? $this->sillOutdoor->getMaterialId() : null,
            'soffitIndoor'     => $this->hasSoffitIndoor() ? $this->soffitIndoor->getMaterialId() : null,
            'soffitOutdoor'    => $this->hasSoffitOutdoor() ? $this->soffitOutdoor->getMaterialId() : null,
            'sunscreenIndoor'  => $this->hasSunscreenIndoor() ? $this->sunscreenIndoorMaterialId : null,
            'sunscreenOutdoor' => $this->hasSunscreenOutdoor() ? $this->sunscreenOutdoorMaterialId : null,
        ];

        $tileWidths  = $this->fixedFrame->tileWidthPercentages();
        $tileHeights = $this->fixedFrame->tileHeightPercentages();

        return (object)[
            'name'                         => $this->getName(),
            'width'                        => $this->getBoundary()->getWidth(),
            'height'                       => $this->getBoundary()->getHeight(),
            'area'                         => $this->getBoundary()->getArea(),
            'sealingWidth'                 => $this->getSealingWidth() * 1000, // in mm
            'openingArea'                  => $this->getOpeningBoundary()->getArea(),
            'fixedFrameWidth'              => $this->fixedFrame->getFrameWidth() * 100, // in cm
            'sashFrameWidth'               => $this->fixedFrame->getSashFrameWidth() * 100, // in cm
            'frameRatio'                   => $this->fixedFrame->getRatio(),
            'glassRatio'                   => $this->getGlassRatio(),
            'numberOfMullions'             => $this->fixedFrame->getNumberOfMullions(),
            'numberOfTransoms'             => $this->fixedFrame->getNumberOfTransoms(),
            'fixedMullionsTransoms'        => $this->fixedFrame->hasFixedMullionsTransoms(),
            'handles'                      => $this->hasHandles() ? $this->handles->getQuantity() : null,
            'fittings'                     => $this->hasFittings() ? $this->fittings->getQuantity() : null,
            'topLightHeight'               => $this->fixedFrame->hasTopLight()
                ? $this->fixedFrame->getTopLightHeight() * 100
                : $this->fixedFrame->getTopLightMinHeight() * 100, // in cm
            'hasTopLight'                  => $this->fixedFrame->hasTopLight(),
            'soffitIndoorDepth'            => $this->soffitIndoor !== null ? $this->soffitIndoor->getDepth() * 100
                : null,
            'soffitIndoorSize'             => $this->soffitIndoor !== null ? $this->soffitIndoor->getSize() * 1000
                : null,
            'soffitOutdoorDepth'           => $this->soffitOutdoor !== null ? $this->soffitOutdoor->getDepth() * 100
                : null,
            'soffitOutdoorSize'            => $this->soffitOutdoor !== null ? $this->soffitOutdoor->getSize() * 1000
                : null,
            'sillIndoorDepth'              => $this->sillIndoor !== null ? $this->sillIndoor->getDepth() * 100 : null,
            'sillIndoorSize'               => $this->sillIndoor !== null ? $this->sillIndoor->getSize() * 1000 : null,
            'sillOutdoorDepth'             => $this->sillOutdoor !== null ? $this->sillOutdoor->getDepth() * 100 : null,
            'sillOutdoorSize'              => $this->sillOutdoor !== null ? $this->sillOutdoor->getSize() * 1000 : null,
            'processConfigId'              => $materialIds,
            'hasIndividualFrameDimensions' => $this->fixedFrame->hasIndividualFrameDimensions(),
            'tileWidth'                    => $tileWidths,
            'tileHeight'                   => $tileHeights,
        ];
    }


    /**
     * @param $materialId
     * @param $width
     */
    private function specifySealing($materialId, $width)
    {
        Assertion::numeric($width, null, 'sealingSize');

        $this->sealing = new Frame(
            new Rectangle(
                $this->boundary->getWidth() + 2 * $width,
                $this->boundary->getHeight() + 2 * $width
            ),
            $width,
            $materialId
        );
    }

    /**
     * @param       $materialId
     * @param       $width
     * @param int   $numberOfMullions
     * @param int   $numberOfTransoms
     * @param bool  $hasTopLight
     * @param int   $topLightHeight
     * @param array $tileWidths
     * @param array $tileHeights
     */
    private function specifyFixedFrame(
        $materialId,
        $width,
        $sashFrameMaterialId = null,
        $sashFrameWidth = 0,
        $numberOfMullions = 0,
        $numberOfTransoms = 0,
        $fixedMullionsTransoms = false,
        $hasTopLight = false,
        $topLightHeight = 0,
        array $tileWidths = [],
        array $tileHeights = []
    ) {
        Assertion::numeric($width, null, 'fixedFrameWidth');

        $this->fixedFrame = new FixedFrame(
            $this->boundary,
            $width,
            $materialId,
            $sashFrameMaterialId,
            $sashFrameWidth,
            $numberOfMullions,
            $numberOfTransoms,
            $fixedMullionsTransoms,
            $hasTopLight,
            $topLightHeight,
            $tileWidths,
            $tileHeights
        );
    }

    /**
     * @param $materialId
     * @param $size
     */
    private function specifyGlass($materialId)
    {
        $this->glassMaterialId = $materialId;
    }
}
