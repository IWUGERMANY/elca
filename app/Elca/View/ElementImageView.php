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
namespace Elca\View;

use Beibob\Blibs\SvgView;
use DOMElement;

/**
 * ElementImage View
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
abstract class ElementImageView extends SvgView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_SCREEN = 'screen';
    const BUILDMODE_PDF = 'pdf';

    /**
     * Default canvas size
     */
    const CANVAS_WIDTH = 630;
    const CANVAS_HEIGHT = 250;

    /**
     * Image
     */
    const IMAGE_PADDING = 5;

    /**
     * Legend
     */
    const LEGEND_COMPONENT_FORMAT = '%s, %smm'; // 1. name, 2. size
    const LEGEND_PADDING_X = 40;
    const LEGEND_PADDING_Y = 20;

    /**
     * Total size
     */
    const DIMENSION_Y_WIDTH = 36; // in px
    const SIZE_OF_THE_TOP_AND_THE_BOTTOM_LINE_OF_THE_DIMENSIONS = 8;
    const VALUE_BOX_WIDTH = 35;
    const VALUE_BOX_HEIGHT = 30;


    /**
     * Badge
     */
    const LEGEND_BADGE_RADIUS = 11;
    const COMPONENT_BADGE_RADIUS = 9;
    const BADGE_POS_X_CORRECTION_SPACE = 8;
    const MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION = 10;


    /**
     * Font defaults
     */
    const FONT_SIZE_BADGE = 12;
    const FONT_SIZE_LEGEND = 14;
    const CHAR_WIDTH = 8;
    const LINE_HEIGHT = 25;


    /**
     * Helper
     */
    const CONVERSION_FACTOR_M_TO_MM  = 1000;
    const THOUSAND = 1000;
    const VERTICAL = 90;
    const FULL_CIRCLE_ANGULAR_DEGREE = 360;


    /**
     * current element id
     */
    protected $elementId;

    /**
     * Canvas size
     */
    protected $canvasWidth;
    protected $canvasHeight;

    /**
     * Image size
     */
    protected $imageWidth;
    protected $imageHeight;

    /**
     * Y-Dimension shows total size
     */
    protected $showTotalSize = false;
    protected $yDimensionWidth = self::DIMENSION_Y_WIDTH;

    /**
     * Defs container
     */
    protected $defs;


    /**
     * @var bool
     */
    protected $drawLegend = true;

    /**
     * @var string current buildMode
     */
    protected $buildMode = self::BUILDMODE_SCREEN;

    /**
     * @param $elementId
     * @return $this
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
    }

    /**
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function setDimension($width, $height)
    {
        $this->canvasWidth = $width;
        $this->canvasHeight = $height;
    }

    /**
     * @return $this
     */
    public function enableTotalSize()
    {
        $this->showTotalSize = true;
    }

    /**
     *
     */
    public function disableLegend()
    {
        $this->drawLegend = false;
    }

    /**
     *
     */
    public function setBuildmode($buildMode)
    {
        $this->buildMode = $buildMode;
    }

    /**
     * Draws a single badge
     *
     * @param $Container
     * @param $x
     * @param $y
     * @param $r
     * @return mixed -
     */
    protected function drawBadge($Container, $x, $y, $r)
    {
        return $Container->appendChild($this->getCircle($x, $y, $r, ['fill' => '#fff']));
    }
    // End drawBadge


    /**
     * @param DOMElement $AppendTo
     * @param            $filepath
     * @param            $patternId
     * @param            $elementId
     *
     * @return \DOMNode
     */
    protected function importPatternImage(\DOMElement $AppendTo, $filepath, $patternId, $elementId)
    {
        /**
         * Force the default namespace of parent svg
         */
        $xml = file_get_contents($filepath);
        $xml = preg_replace('/ xmlns[^=]*="[^"]*"/i', '', $xml);
        $xml = str_replace('<svg ', '<svg xmlns:default="http://www.w3.org/2000/svg" ', $xml);
        $svgDoc = new \DOMDocument();

        $svgDoc->loadXML($xml, LIBXML_NSCLEAN);

        /**
         * Make css declarations unique for each pattern
         */
        $styles = $svgDoc->getElementsByTagName('style');
        foreach ($styles as $styleElt)
        {
            $declerations = [];
            preg_match_all("/([^{]+)\s*\{\s*([^}]+)\s*}/miu", str_replace("\n", " ", str_replace("\r", "", $styleElt->nodeValue)), $declerations);

            $css = '';
            if (!isset($declerations[0]) || !is_array($declerations[0]) || count($declerations[0]) < 1)
                continue;

            foreach($declerations[0] as $def)
                $css .= '#pattern'. $patternId. '-'. $elementId . $def . " \n";

            $styleElt->nodeValue = $css;
        }

        /**
         * Append the pattern
         */
        return $AppendTo->appendChild($this->importNode($svgDoc->getElementsByTagName('svg')->item(0), true));
    }
}