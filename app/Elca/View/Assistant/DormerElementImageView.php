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
namespace Elca\View\Assistant;

use Beibob\Blibs\IdFactory;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaSvgPattern;
use Elca\Model\Assistant\Window\FixedFrame;
use Elca\Model\Assistant\Window\Frame;
use Elca\Model\Assistant\Window\Window;
use Elca\Service\Assistant\Window\DormerAssistant;
use Elca\View\ElementImageView;

/**
 * ElementImage View
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class DormerElementImageView extends ElementImageView
{
    /**
     * canvas size
     */
    const CANVAS_WIDTH = 400;
    const CANVAS_HEIGHT = 250;

    /**
     * Legend
     */
    const LEGEND_TEXT_FORMAT = '%s: %s';
    const LEGEND_PADDING_X = 20;
    const LEGEND_PADDING_Y = 20;


    /**
     * Image size
     */
    protected $imageScale;

    /**
     * Badges
     */
    private $badges = [];
    private $badgeCoordinates = [];


    /**
     * @var array
     */
    private $patterns;
    private $materialPatterns;


    /** @var  Window  */
    private $window;


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->elementId)
            $this->elementId = $this->get('elementId');

        /**
         * Init arguments and options
         */
        $element = ElcaElement::findById($this->elementId);
        $elementType = $element->getElementTypeNode();

        if(!$elementType->getPrefHasElementImage())
            return;

        if (!$this->window = $this->get('window'))
            return;

        /**
         * Get element component config
         */
        $this->initPatterns($element);

        /**
         * Canvas size
         */
        if (!$this->canvasWidth)
            $this->canvasWidth  = $this->get('width', self::CANVAS_WIDTH);

        if (!$this->canvasHeight)
            $this->canvasHeight = $this->get('height', self::CANVAS_HEIGHT);

        /**
         * Horizontal orientation
         */
        $width = $this->window->getOpeningBoundary()->getWidth();
        $height = $this->window->getOpeningBoundary()->getHeight();

        if ($width > $height) {
            $this->imageWidth = ($this->canvasWidth - self::IMAGE_PADDING) ;
            $this->imageHeight = $this->drawLegend ? ($this->canvasHeight / 2 - self::IMAGE_PADDING) : ($this->canvasHeight - self::IMAGE_PADDING);

        } else {
            $this->imageWidth = $this->drawLegend ? ($this->canvasWidth / 2 - self::IMAGE_PADDING) : ($this->canvasWidth - self::IMAGE_PADDING);
            $this->imageHeight = ($this->canvasHeight - self::IMAGE_PADDING);
        }

        $this->imageScale = $this->getScaleFactor($width, $height, $this->imageWidth, $this->imageHeight);

    }
    // End __construct





    /**
     * Called before render
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $v = 1.1;

        $svg = $this->appendChild($this->getSvg(['height' => '100%',
                                                      'width' => '100%',
                                                      'viewBox' => '-10 -10 '. ($this->canvasWidth - 10) * $v .' '. ($this->canvasHeight - 10) * $v
        ]));


        /**
         * Append defs
         */
        $this->defs = $svg->appendChild($this->getDefs());
        $this->defPatterns($this->defs);

        /**
         * Image layer
         */
        $this->drawWindow($svg);

        /**
         * Legend
         */
        if ($this->drawLegend)
            $this->drawLegend($svg);
    }
    // End beforeRender



    /**
     * defines the images for the patterns
     *
     * @param DOMElement $defs
     * @param array $patterns
     */
    private function defPatterns($defs)
    {
        foreach($this->patterns as $pattern)
        {
            $patternElt = $defs->appendChild($this->getPattern('pattern'. $pattern->patternId. '-'. $this->elementId, 0, 0, $pattern->width, $pattern->height));
            if ($this->buildMode != self::BUILDMODE_PDF) {
                $patternElt->appendChild($this->getImage(0, 0, $pattern->width, $pattern->height, $pattern->uri));
            }
            else {
                $this->importPatternImage($patternElt, $pattern->filePath, $pattern->patternId, $this->elementId);
            }
        }
    }
    // End defImages


    /**
     * Draw the image
     *
     * @param     $Container
     * @return void -
     */
    private function drawWindow($Container)
    {
        $imageGroup = $Container->appendChild($this->getGroup());

        $y = 0;
        $x = 0;

        if ($sealing = $this->window->getSealing()) {
            $this->drawFrame($imageGroup, $sealing, $x, $y, t('Anschlussfuge'));

            $x += $this->calcInPx($sealing->getFrameWidth());
            $y += $this->calcInPx($sealing->getFrameWidth());
        }

        $fixedFrame = $this->window->getFixedFrame();
        $this->drawFrame($imageGroup, $fixedFrame, $x, $y, t('Blendrahmen'));

        $x += $this->calcInPx($fixedFrame->getFrameWidth());
        $y += $this->calcInPx($fixedFrame->getFrameWidth());

        $this->drawTopLight($imageGroup, $fixedFrame, $x, $y);
        $this->drawMullions($imageGroup, $fixedFrame, $x, $y);
        $this->drawTransoms($imageGroup, $fixedFrame, $x, $y);

        if ($fixedFrame->hasTopLightFrame()) {
            $topLightFrame = $fixedFrame->getTopLightFrame();
            $frameWidth = $this->calcInPx($topLightFrame->getFrameWidth());
            $this->drawFrame($imageGroup, $topLightFrame, $x, $y);
            $this->drawGlass($imageGroup, $topLightFrame, $x + $frameWidth, $y + $frameWidth);

            $y += $this->calcInPx($topLightFrame->getHeight());

            if ($fixedFrame->hasFixedMullionsTransoms()) {
                $y += $this->calcInPx($fixedFrame->getFrameWidth());
            }
        }

        $this->drawTiles($imageGroup, $fixedFrame, $x, $y);

        /**
         * Add badges
         */
        if ($this->drawLegend)
        {
            foreach($this->badges as $index => $badge)
                $this->drawBadgeForComponent($imageGroup, $badge->x, $badge->y, $badge->width, $badge->height, $badge->index);
        }
    }
    // End drawImage


    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param DOMNode $container
     * @param Frame $frame
     * @param float $x
     * @param float $y
     */
    private function drawFrame(DOMNode $container, Frame $frame, $x, $y, $badge = null)
    {
        $outerBoundary = $frame->getOuterBoundary();
        $innerBoundary = $frame->getInnerBoundary();
        $frameWidth = $this->calcInPx($frame->getFrameWidth());

        $width = $this->calcInPx($outerBoundary->getWidth());
        $height = $this->calcInPx($outerBoundary->getHeight());

        $patternUrl = $this->defPattern($frame->getMaterialId(), $x, $y, $width, $height);

        $g = $container->appendChild($this->getGroup());
        $g->appendChild($this->getRect($x, $y, $width, $height, ['stroke' => '#000',
                                                                 'stroke-width' => 1,
                                                                 'fill' => 'url(#' . $patternUrl . ')'
        ]));

        $width = $this->calcInPx($innerBoundary->getWidth());
        $height = $this->calcInPx($innerBoundary->getHeight());

        $g->appendChild($this->getRect($x + $frameWidth, $y + $frameWidth, $width, $height, [
            'stroke-width' => 0,
            'fill' => '#fff'
        ]));

        /**
         * Save badge coordinates, render badges after drawing the components
         */
        if ($badge !== null)
            $this->addBadge($badge, $frame->getMaterialId(), $x, $y, $width, $frameWidth);
    }
    // end drawComponent

    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param DOMNode $container
     * @param Frame $frame
     * @param float $x
     * @param float $y
     */
    private function drawMullions(DOMNode $container, FixedFrame $frame, $x, $y)
    {
        if (!$frame->hasFixedMullionsTransoms()) {
            return;
        }

        $innerBoundary = $frame->getInnerBoundary();
        $frameWidth = $this->calcInPx($frame->getFrameWidth());

        $widths = $frame->tileWidths();
        array_pop($widths);

        if ($frame->hasTopLight()) {
            $y += $this->calcInPx($frame->getTopLightHeight());
            $height = $this->calcInPx($innerBoundary->getHeight() - $frame->getTopLightHeight());

        } else {
            $height = $this->calcInPx($innerBoundary->getHeight());
        }

        foreach ($widths as $width) {
            $x += $this->calcInPx($width);

            $patternUrl = $this->defPattern($frame->getMaterialId(), $x, $y, $frameWidth, $height);

            $g = $container->appendChild($this->getGroup());
            $g->appendChild($this->getRect($x, $y - 1, $frameWidth, $height + 2, [
                'fill' => 'url(#' . $patternUrl . ')'
            ]));

            if ($frame->hasFixedMullionsTransoms()) {
                $x += $frameWidth;
            }
        }
    }


    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param DOMNode $container
     * @param Frame $frame
     * @param float $x
     * @param float $y
     */
    private function drawTransoms(DOMNode $container, FixedFrame $frame, $x, $y)
    {
        if (!$frame->hasFixedMullionsTransoms()) {
            return;
        }

        $innerBoundary = $frame->getInnerBoundary();
        $frameWidth = $this->calcInPx($frame->getFrameWidth());

        $heights = $frame->tileHeights();
        array_pop($heights);
        $width = $this->calcInPx($innerBoundary->getWidth());

        if ($frame->hasTopLight()) {
            $y += $this->calcInPx($frame->getTopLightHeight());
        }

        foreach ($heights as $height) {
            $y += $this->calcInPx($height);

            $patternUrl = $this->defPattern($frame->getMaterialId(), $x, $y, $frameWidth, $width);

            $g = $container->appendChild($this->getGroup());
            $g->appendChild($this->getRect($x - 1, $y, $width + 2, $frameWidth, [
                'fill' => 'url(#' . $patternUrl . ')'
            ]));

            if ($frame->hasFixedMullionsTransoms()) {
                $y += $frameWidth;
            }
        }
    }


    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param DOMNode $container
     * @param Frame $frame
     * @param float $x
     * @param float $y
     */
    private function drawTopLight(DOMNode $container, FixedFrame $frame, $x, $y)
    {
        if (!$frame->hasTopLight()) {
            return;
        }

        $y += $this->calcInPx($frame->getTopLightHeight());

        if ($frame->hasFixedMullionsTransoms()) {
            $y -= $this->calcInPx($frame->getFrameWidth());
        }

        $innerBoundary = $frame->getInnerBoundary();
        $frameWidth = $this->calcInPx($frame->getFrameWidth());

        $width = $this->calcInPx($innerBoundary->getWidth());

        $patternUrl = $this->defPattern($frame->getMaterialId(), $x, $y, $frameWidth, $width);

        $g = $container->appendChild($this->getGroup());
        $g->appendChild($this->getRect($x - 1, $y, $width + 2, $frameWidth, [
            'fill' => 'url(#' . $patternUrl . ')'
        ]));
    }

    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param DOMNode $container
     * @param Frame $sashFrame
     * @param float $x
     * @param float $y
     */
    private function drawGlass(DOMNode $container, Frame $sashFrame, $x, $y, $badge = null)
    {
        $boundary = $sashFrame->getInnerBoundary();

        $width = $this->calcInPx($boundary->getWidth());
        $height = $this->calcInPx($boundary->getHeight());

        $patternUrl = $this->defPattern($this->window->getGlassMaterialId(), $x, $y, $width, $height);

        $g = $container->appendChild($this->getGroup());
        $g->appendChild($this->getRect($x, $y, $width, $height, ['stroke' => '#000',
                                                                 'stroke-width' => 1,
                                                                 'fill' => 'url(#' . $patternUrl . ')'
        ]));

        /**
         * Save badge coordinates, render badges after drawing the components
         */
        if ($badge !== null)
            $this->addBadge($badge, $this->window->getGlassMaterialId(), $x, $y, $width, $height);
    }
    // end drawComponent


    /**
     *
     */
    private function addBadge($title, $materialId, $x, $y, $width, $height)
    {
        /**
         * Save badge coordinates, render badges after drawing the components
         */
        $badge = new \stdClass();
        $badge->index = count($this->badges);
        $badge->title = $title;
        $badge->materialId = $materialId;
        $badge->x = $x;
        $badge->y = $y;
        $badge->width = $width;
        $badge->height = $height;

        $this->badges[] = $badge;
    }


    /**
     * defPattern
     *
     * defines Pattern for the given Component.
     *
     * @param int $materialId
     * @param float    $y
     * @return string patternId
     */
    private function defPattern($materialId, $x, $y, $width, $height)
    {
        $patternHeight = $this->materialPatterns[$materialId]->pattern->height;
        $patternWidth = $this->materialPatterns[$materialId]->pattern->width;

        $scale = $this->getScaleFactor(
            max($width, $patternWidth),
            max($height, $patternHeight),
            $width,
            $height
        );

        /**
         * A pattern starts at (0,0). Therefor translate y-pos of the pattern
         * to the layer y-position
         */
        $translateX = $x % max(1, $width);
        $translateY = $y % max(1, $height);

        /**
         * Make the url refs unique
         */
        $patternId = 'trPattern'. IdFactory::getUniqueXmlId() .'-'. $this->elementId;
        $patternUrl = '#pattern'. $this->materialPatterns[$materialId]->pattern->patternId .'-'. $this->elementId;

        $this->defs->appendChild(
            $this->getPattern(
                $patternId,
                0,
                0,
                $patternWidth,
                $patternHeight,
                [
                    'xlink:href' => $patternUrl,
                    //'patternTransform' => 'translate('. $translateX .' '. $translateY .')'
                    'patternTransform' => 'translate('. $translateX .' '. $translateY .') scale('. $scale .')'
                ]
            )
        );

        return $patternId;
    }
    // end defPattern


    /**
     * drawBadgeForComponent
     *
     * @param DOMElement $container
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param            $componentIndex
     * @return void -
     */
    private function drawBadgeForComponent($container, $x, $y, $width, $height, $componentIndex)
    {
        $cx = (2 * $x + $width) / 2;
        $cy = (2 * $y + $height) / 2;

        /**
         * Look out for collisions
         */
        $collBadgeRadius = self::COMPONENT_BADGE_RADIUS * 2;
        foreach($this->badgeCoordinates as $key => $badge)
        {
            if(($cx < $badge['x'] + $collBadgeRadius && $cx > $badge['x'] - $collBadgeRadius) && ($cy < $badge['y'] + $collBadgeRadius && $cy > $badge['y'] - $collBadgeRadius))
                $cx = $key % 2? $cx + $collBadgeRadius : $cx - $collBadgeRadius;
        }

        $numberPosX = $cx - self::BADGE_POS_X_CORRECTION_SPACE / 2;

        /**
         * Fix digit placement > 10 (@todo: should be log10)
         */
        if($componentIndex + 1 >= self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION)
            $numberPosX = $cx - self::BADGE_POS_X_CORRECTION_SPACE;

        $group = $container->appendChild($this->getGroup());

        $this->drawBadge($group, $cx, $cy, self::COMPONENT_BADGE_RADIUS);
        $group->appendChild($this->getText($componentIndex + 1 , $numberPosX + 1, $cy + (self::COMPONENT_BADGE_RADIUS / 2) - 1,
                                           ['font-size' => self::FONT_SIZE_BADGE.'px']));

        $this->badgeCoordinates[] = ['x' => $cx, 'y' => $cy];
    }
    //end drawBadgeForComponent


    /**
     * Draws the legend container and content
     *
     * @param $Container
     * @return void -
     */
    private function drawLegend($Container)
    {
        $transform = [];

        $windowWidth = $this->window->getOpeningBoundary()->getWidth();
        $windowHeight = $this->window->getOpeningBoundary()->getHeight();

        $lWidth = $this->getLegendWidth();
        $lHeight = $this->getLegendHeight();

        if ($windowWidth <= $windowHeight) {
            $bWidth  = $this->canvasWidth / 2;
            $bHeight = $this->canvasHeight - self::LEGEND_PADDING_Y / 2;
        } else {
            $bWidth  = $this->canvasWidth;
            $bHeight = $this->canvasHeight /2 + self::LEGEND_PADDING_Y / 2;

        }
        $ratio = min($bWidth / $lWidth, $bHeight / $lHeight);

        if ($windowWidth <= $windowHeight) {
            $x = $bWidth;
            $y = 0;

            if($ratio < 1) {
                $translateX = ((1 / $ratio - 1) * $bWidth) + self::LEGEND_PADDING_X;
                $translateY = self::LEGEND_PADDING_Y;
            } else {
                $translateX = self::LEGEND_PADDING_X;
                $translateY = self::LEGEND_PADDING_Y;
            }

        } else {
            $x = 0;
            $y = $bHeight;

            if($ratio < 1) {
                $translateX = self::LEGEND_PADDING_X;
                $translateY = ((1 / $ratio - 1) * $bHeight) + self::LEGEND_PADDING_Y;
            } else {
                $translateX = self::LEGEND_PADDING_X;
                $translateY = self::LEGEND_PADDING_Y;
            }
        }


        if($ratio < 1)
            $transform[] = $this->scale($ratio);

        $transform[] = $this->translate($translateX, $translateY);

        $legendGroup = $Container->appendChild($this->getGroup(['transform' => join(' ', $transform)]));


        foreach ($this->badges as $badge) {
            $this->drawComponentLegend($legendGroup, $badge, $x, $y);
            $y += self::LINE_HEIGHT;
        }

    }
    //end drawLegend


    /**
     * drawComponentLegend
     *
     * draws the Component Legend
     *
     * @param DOMElement $container
     * @param            $Component
     * @param float      $x
     * @param float      $y
     * @return void -
     */
    private function drawComponentLegend($container, $badge, $x, $y)
    {
        $legendText = $this->getLegendText($badge);

        $numberPosX = $x - floor(self::LEGEND_BADGE_RADIUS / 2);

        if($badge->index + 1 >= self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION)
            $numberPosX = $x - self::BADGE_POS_X_CORRECTION_SPACE;

        $this->drawBadge($container, $x, $y, self::LEGEND_BADGE_RADIUS);

        $container->appendChild($this->getText($badge->index + 1, $numberPosX,
                                             $y + self::LEGEND_BADGE_RADIUS / 2,
                                             ['font-size' => self::FONT_SIZE_LEGEND.'px']));

        $container->appendChild($this->getText($legendText, $x + 2 * self::LEGEND_BADGE_RADIUS,
                                             $y + self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION / 2,
                                             ['font-size' => self::FONT_SIZE_LEGEND.'px']));
    }
    // End drawComponentLegend


    /**
     * Calculates the legend height
     *
     * @return float $height
     */
    private function getLegendHeight()
    {
        return count($this->badges) * self::LINE_HEIGHT;
    }
    // end getLegendHeight


    /**
     * Calculates the legend width
     *
     * @return float $width
     */
    private function getLegendWidth()
    {
        $maxWidth = 0;
        foreach($this->badges as $badge) {
            $width = \utf8_strlen($this->getLegendText($badge)) * self::CHAR_WIDTH;

            if($width > $maxWidth)
                $maxWidth = $width;
        }

        return $maxWidth;
    }
    // end getLegendWidth



    /**
     * Returns the legend text
     *
     * @param  object $badge
     * @return string
     */
    private function getLegendText($badge)
    {
        return sprintf(self::LEGEND_TEXT_FORMAT, $badge->title, $this->materialPatterns[$badge->materialId]->material);
    }
    // End getLegendText



    /**
     * calculateHeight
     *
     * calculates the ratio of the given height
     * and return a new scaled height
     *
     * @param $value
     * @return float $height
     */
    private function calcInPx($value)
    {
        return $this->imageScale * $value;
    }
    //end calculateHeight

    /**
     * @param $width
     * @param $height
     * @param $maxWidth
     * @param $maxHeight
     * @return float
     */
    protected function getScaleFactor($width, $height, $maxWidth, $maxHeight)
    {
        $outerRatio = $maxHeight / $maxWidth;
        $innerRatio = $height / $width;

        return $innerRatio >= $outerRatio
            ? $maxHeight / $height
            : $maxWidth / $width;
    }

    /**
     * initPatterns
     *
     * @param ElcaElement $element
    */
    private function initPatterns($element)
    {
        $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), DormerAssistant::IDENT);
        if ($attr->isInitialized() && $attr->getNumericValue() !== null) {
            $elementId = $attr->getNumericValue();
            $element = ElcaElement::findById($elementId);
        }

        $this->patterns = [];
        $this->materialPatterns = [];

        $components = ElcaElementComponentSet::findByElementIdAndAttributeIdent($element->getId(), DormerAssistant::IDENT);
        foreach($components as $component)
        {
            $processConfig = $component->getProcessConfig();
            $svgPattern = $processConfig->getSvgPatternId()? $processConfig->getSvgPattern() : ElcaSvgPattern::findByElementComponentId($component->id);

            if (!isset($this->patterns[$svgPattern->getId()])) {
                $pattern = new \stdClass;
                $pattern->patternId = $svgPattern->getId();
                $pattern->name = $svgPattern->getName();
                $pattern->width = $svgPattern->getWidth();
                $pattern->height = $svgPattern->getHeight();
                $pattern->uri = $svgPattern->getImage()->getURI();
                $pattern->filePath = $svgPattern->getImage()->getFullPath();

                $this->patterns[$svgPattern->getId()] = $pattern;
            }

            $this->materialPatterns[$processConfig->getId()] = (object)[
                'pattern' => $this->patterns[$svgPattern->getId()],
                'material' =>  $processConfig->getName()
            ];
        }
    }

    /**
     * @param $imageGroup
     * @param $fixedFrame
     * @param $x
     * @param $y
     */
    private function drawTiles($imageGroup, FixedFrame $fixedFrame, $x, $y)
    {
        $tiles = $fixedFrame->getTiles();

        $startX         = $x;
        $sashFrameWidth = $this->calcInPx($fixedFrame->getSashFrameWidth());
        $height = 0;

        foreach ($tiles as $column => $rows) {
            foreach ($rows as $row => $tileFrame) {
                if ($column === 0 && $row === 0) {
                    $frameBadge = t('Flügelrahmen');
                    $glassBadge = t('Verglasung');
                } else {
                    $frameBadge = $glassBadge = null;
                }

                if ($sashFrameWidth) {
                    $this->drawFrame($imageGroup, $tileFrame, $x, $y, $frameBadge);
                }

                $this->drawGlass($imageGroup, $tileFrame, $x + $sashFrameWidth, $y + $sashFrameWidth, $glassBadge);

                $x += $this->calcInPx($tileFrame->getOuterBoundary()->getWidth());

                if ($fixedFrame->hasFixedMullionsTransoms()) {
                    $x += $this->calcInPx($fixedFrame->getFrameWidth());
                }

                $height = $tileFrame->getOuterBoundary()->getHeight();
            }

            $y += $this->calcInPx($height);

            if ($fixedFrame->hasFixedMullionsTransoms()) {
                $y += $this->calcInPx($fixedFrame->getFrameWidth());
            }

            $x = $startX;
        }
    }
}
// End WindowElementImageView
