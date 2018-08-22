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
use Beibob\Blibs\FloatCalc;
use Elca\Model\Common\Geometry\Rectangle;

/**
 * FixedFrame
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class FixedFrame extends Frame
{
    /**
     * Assumed min glass height
     */
    const MIN_GLASS_HEIGHT = 0.01; // in m

    /**
     * @var Frame[][]
     */
    private $tiles = [];

    /**
     * @var int
     */
    private $numberOfMullions = 0;

    /**
     * @var int
     */
    private $numberOfTransoms = 0;

    /**
     * @var Frame
     */
    private $topLightFrame;

    /**
     * @var boolean
     */
    private $hasTopLight;

    /**
     * @var boolean
     */
    private $hasIndividualFrameDimensions;

    /**
     * @var number
     */
    private $topLightHeight;

    /**
     * @var number[]
     */
    private $tileWidths;

    /**
     * @var number[]
     */
    private $tileHeights;

    /**
     * @var bool
     */
    private $fixedMullionsTransoms;

    /**
     * @var null
     */
    private $sashFrameMaterialId;

    /**
     * @var int
     */
    private $sashFrameWidth;

    /**
     * @param Rectangle $outerBoundary
     * @param number    $frameWidth
     * @param null      $materialId
     * @param null      $sashFrameMaterialId
     * @param int       $sashFrameWidth
     * @param int       $numberOfMullions
     * @param int       $numberOfTransoms
     * @param bool      $fixedMullionsTransoms
     * @param bool      $hasTopLight
     * @param int       $topLightHeight
     * @param array     $tileWidths
     * @param array     $tileHeights
     * @internal param number $size
     */
    public function __construct(Rectangle $outerBoundary, $frameWidth, $materialId, $sashFrameMaterialId = null, $sashFrameWidth = 0,
        $numberOfMullions = 0, $numberOfTransoms = 0, $fixedMullionsTransoms = false,
        $hasTopLight = false, $topLightHeight = 0, array $tileWidths = [], array $tileHeights = [])
    {
        parent::__construct($outerBoundary, $frameWidth, $materialId);

        Assertion::numeric($topLightHeight, null, 'topLightHeight');
        Assertion::integer($numberOfMullions, null, 'numberOfMullions');
        Assertion::integer($numberOfTransoms, null, 'numberOfTransoms');

        $this->sashFrameMaterialId = $sashFrameMaterialId;
        $this->sashFrameWidth = $sashFrameMaterialId ? max(0, $sashFrameWidth) : 0;

        $this->numberOfMullions = $numberOfMullions;
        $this->numberOfTransoms = $numberOfTransoms;

        $this->fixedMullionsTransoms = $numberOfMullions > 0 || $numberOfTransoms > 0 || $hasTopLight ? $fixedMullionsTransoms : false;

        if (($numberOfMullions > 0 || $numberOfTransoms > 0) && $this->sashFrameWidth === 0) {
            $this->fixedMullionsTransoms = true;
        }

        $this->hasTopLight = (bool)$hasTopLight;
        $this->topLightHeight = $hasTopLight ? $topLightHeight : 0;

        $this->initializeTiles($tileWidths, $tileHeights);
    }


    /**
     * @return int
     */
    public function getNumberOfMullions()
    {
        return $this->numberOfMullions;
    }

    /**
     * @return int
     */
    public function getNumberOfTransoms()
    {
        return $this->numberOfTransoms;
    }

    /**
     * @return int
     */
    public function numberOfTileFrames()
    {
        return ($this->numberOfMullions + 1) * ($this->numberOfTransoms + 1);
    }

    /**
     * @return boolean
     */
    public function hasFixedMullionsTransoms()
    {
        return $this->fixedMullionsTransoms;
    }

    /**
     * @return bool
     */
    public function hasTopLight()
    {
        return $this->hasTopLight;
    }

    /**
     * @return bool
     */
    public function hasTopLightFrame()
    {
        if ($this->hasTopLight()) {
            return $this->topLightFrame !== null;
        }

        return false;
    }

    /**
     * @return number
     */
    public function getTopLightHeight()
    {
        return $this->topLightHeight;
    }

    /**
     * @return Frame
     */
    public function getTopLightFrame()
    {
        return $this->topLightFrame;
    }

    /**
     * @return Frame[][]
     */
    public function getTiles()
    {
        return $this->tiles;
    }

    /**
     * @return number|null
     */
    public function getSashFrameWidth()
    {
       return $this->sashFrameWidth;
    }

    /**
     * @return int|null
     */
    public function getSashFrameMaterialId()
    {
       return $this->sashFrameMaterialId;
    }

    /**
     * Calculates the length of the frame borders incl. mullions and transoms and top light
     */
    public function getLength()
    {
        $length = parent::getLength();

        $innerBoundary = $this->getInnerBoundary();

        /**
         * Add all transoms and mullions lengths
         * and subtract conjunctions parts
         */
        if ($this->fixedMullionsTransoms) {
            $length += ($this->numberOfMullions * $innerBoundary->getHeight()
                        + $this->numberOfTransoms * $innerBoundary->getWidth()
                        - $this->numberOfMullions * $this->numberOfTransoms * $this->getFrameWidth()
            );

            if ($this->hasTopLight()) {
                $length += $this->topLightFrame->getWidth();
            }
        }


        return $length;
    }

    /**
     * @return int|number
     */
    public function getSashFramesLength()
    {
        $length = 0;

        if ($this->sashFrameWidth > 0) {
            foreach ($this->tiles as $row => $columns) {
                foreach ($columns as $tileFrame) {
                    $length += $tileFrame->getLength();
                }
            }
        }

        if ($this->hasTopLight()) {
            $length += $this->topLightFrame->getLength();
        }

        return $length;
    }

    /**
     * @return number
     */
    public function getArea()
    {
        return $this->getLength() * $this->getFrameWidth()
            + $this->getSashFramesLength() * $this->getSashFrameWidth();
    }


    /**
     * The frame ratio is meatured relative to the outer boundary
     *
     * @return float
     */
    public function getRatio()
    {
        $outerBoundaryArea = $this->getOuterBoundary()->getArea();
        $frameArea = $this->getArea();

        return $frameArea / $outerBoundaryArea;
    }


    /**
     * @return int
     */
    public function getTopLightMinHeight()
    {
        return 2 * $this->getSashFrameWidth() + self::MIN_GLASS_HEIGHT;
    }

    /**
     * @return int
     */
    public function getTopLightMaxHeight()
    {
        return $this->getInnerBoundary()->getHeight()
               - (
            (2 * $this->getSashFrameWidth() + self::MIN_GLASS_HEIGHT )
            * ($this->getNumberOfTransoms() + 1)
        );
    }

    /**
     * @return number[]
     */
    public function tileWidths()
    {
        $widths = [];
        foreach ($this->tiles[0] as $col => $frame) {
            $widths[$col] = $frame->getWidth();
        }

        return $widths;
    }

    /**
     * @return number[]
     */
    public function tileHeights()
    {
        $heights = [];
        foreach ($this->tiles as $row => $rows) {
            $heights[$row] = $rows[0]->getHeight();
        }

        return $heights;
    }

    /**
     * @return number[]
     */
    public function tileWidthPercentages()
    {
        $width = $this->hasFixedMullionsTransoms()
            ? $this->getInnerBoundary()->getWidth() - $this->numberOfMullions * $this->getFrameWidth()
            : $this->getInnerBoundary()->getWidth();

        $widths = [];
        foreach ($this->tileWidths() as $col => $tileWidth) {
            $widths[$col] = $tileWidth / $width;
        }

        return $widths;
    }

    /**
     * @return number[]
     */
    public function tileHeightPercentages()
    {
        $height = $this->hasFixedMullionsTransoms()
            ? $this->getInnerBoundary()->getHeight() - $this->numberOfTransoms * $this->getFrameWidth()
            : $this->getInnerBoundary()->getHeight();

        if ($this->hasTopLight()) {
            $height -= $this->topLightFrame->getHeight();

            if ($this->hasFixedMullionsTransoms()) {
                $height -= $this->getFrameWidth();
            }
        }

        $heights = [];
        foreach ($this->tileHeights() as $row => $tileHeight) {
            $heights[$row] = $tileHeight / $height;
        }

        return $heights;
    }

    /**
     * @return boolean
     */
    public function hasIndividualFrameDimensions()
    {
        return $this->hasIndividualFrameDimensions;
    }

    /**
     * @param array $tileWidths
     * @param array $tileHeights
     */
    private function initializeTiles(array $tileWidths, array $tileHeights)
    {
        $innerFixedBoundary = $this->getInnerBoundary();

        $maxWidth  = $innerFixedBoundary->getWidth();
        $maxHeight = ($innerFixedBoundary->getHeight() - $this->topLightHeight);

        if ($this->hasFixedMullionsTransoms()) {
            $realBoundaryWidth  = $maxWidth - $this->numberOfMullions * $this->getFrameWidth();
            $realBoundaryHeight = $maxHeight - $this->numberOfTransoms * $this->getFrameWidth();
        }
        else {
            $realBoundaryWidth  = $maxWidth;
            $realBoundaryHeight = $maxHeight;
        }

        list($tileWidths, $tileHeights) = $this->normalizeTileDimensions(
            $realBoundaryWidth,
            $realBoundaryHeight,
            $tileWidths,
            $tileHeights
        );

        if ($this->hasTopLight()) {
            $fixedFrameWidth = $this->hasFixedMullionsTransoms()? $this->getFrameWidth() : 0;
            $this->topLightFrame = new Frame(
                new Rectangle(
                    $innerFixedBoundary->getWidth(),
                    $this->topLightHeight - $fixedFrameWidth
                ),
                $this->sashFrameWidth,
                $this->sashFrameMaterialId
            );
        }

        $numberOfTiles = $this->numberOfTileFrames();

        for ($i = 0; $i < $numberOfTiles; $i++) {

            $column = (int)($i % ($this->numberOfMullions + 1));
            $row = (int)($i / ($this->numberOfMullions + 1));

            $this->tiles[$row][$column] = new Frame(
                new Rectangle(
                    $tileWidths[$column],
                    $tileHeights[$row]
                ),
                $this->sashFrameWidth,
                $this->sashFrameMaterialId
            );
        }
    }

    /**
     * @param       $boundaryWidth
     * @param       $boundaryHeight
     * @param array $tileWidths
     * @param array $tileHeights
     * @return array
     */
    private function normalizeTileDimensions($boundaryWidth, $boundaryHeight, array $tileWidths, array $tileHeights)
    {
        $defaultTileWidth = 1 / ($this->numberOfMullions + 1);
        $defaultTileHeight = 1 / ($this->numberOfTransoms + 1);

        $normalizedWidths = $normalizedHeights = [];

        $sumWidth = 0;
        $individualWidths = false;
        for ($col = 0; $col < $this->numberOfMullions + 1; $col++) {
            if (isset($tileWidths[$col]) && !FloatCalc::cmp($defaultTileWidth, $tileWidths[$col])) {
                $normalizedWidths[$col] = $boundaryWidth * $tileWidths[$col];
                $individualWidths = true;
            } else {
                $normalizedWidths[$col] = $boundaryWidth * $defaultTileWidth;
            }

            $sumWidth += $normalizedWidths[$col];
        }
        if (!FloatCalc::cmp($sumWidth, $boundaryWidth)) {
            $individualWidths = false;
            for ($col = 0; $col < $this->numberOfMullions + 1; $col++)
                $normalizedWidths[$col] = $boundaryWidth * $defaultTileWidth;
        }

        $sumHeight = 0;
        $individualHeights = false;
        for ($row = 0; $row < $this->numberOfTransoms + 1; $row++) {
            if (isset($tileHeights[$row]) && !FloatCalc::cmp($defaultTileHeight, $tileHeights[$row])) {
                $normalizedHeights[$row] = $boundaryHeight * $tileHeights[$row];
                $individualHeights = true;
            } else {
                $normalizedHeights[$row] = $boundaryHeight * $defaultTileHeight;
            }
            $sumHeight += $normalizedHeights[$row];
        }

        if (!FloatCalc::cmp($sumHeight, $boundaryHeight)) {
            $individualHeights = false;
            for ($row = 0; $row < $this->numberOfTransoms + 1; $row++)
                $normalizedHeights[$row] = $boundaryHeight * $defaultTileHeight;
        }

        $this->hasIndividualFrameDimensions = $individualWidths || $individualHeights;

        return [$normalizedWidths, $normalizedHeights];
    }


}
