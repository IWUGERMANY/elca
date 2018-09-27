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

use Beibob\Blibs\IdFactory;
use DOMElement;
use Elca\Db\ElcaCompositeElementSet;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaSvgPattern;
use Elca\Elca;
use Elca\ElcaNumberFormat;

/**
 * ElementImage View
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class DefaultElementImageView extends ElementImageView
{
    private $configuration;

    /**
     * Badges
     */
    private $componentBadges = [];

    private $badgeCoordinates = [];


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->elementId) {
            $this->elementId = $this->get('elementId');
        }

        /**
         * Init arguments and options
         */
        $element     = ElcaElement::findById($this->elementId);
        $elementType = $element->getElementTypeNode();

        if (!$elementType->getPrefHasElementImage()) {
            return;
        }

        /**
         * Get element component config
         */
        $this->configuration = $this->generateConfig($element);
        if (!$this->configuration) {
            return;
        }

        /**
         * Canvas size
         */
        if (!$this->canvasWidth) {
            $this->canvasWidth = $this->get('width', self::CANVAS_WIDTH);
        }

        if (!$this->canvasHeight) {
            $this->canvasHeight = $this->get('height', self::CANVAS_HEIGHT);
        }

        $this->showTotalSize = $this->get('showTotalSize', true);

        /**
         * Switch to enable or disable legend
         */
        if (!$this->drawLegend) {
            $this->showTotalSize = false;
        }

        $this->yDimensionWidth = $this->showTotalSize ? self::DIMENSION_Y_WIDTH : 0;


        $halfCanvas = $this->drawLegend ? intval($this->canvasWidth / 2) : $this->canvasWidth;

        /**
         * Flip on rotation rotation
         */
        if ($this->configuration->inclination % (self::FULL_CIRCLE_ANGULAR_DEGREE / 2) == self::VERTICAL) {
            /**
             * Vertical orientation
             */
            $this->imageWidth  = $this->canvasHeight - $this->yDimensionWidth - self::IMAGE_PADDING;
            $this->imageHeight = $halfCanvas - self::IMAGE_PADDING;
        } else {
            /**
             * Horizontal orientation
             */
            $this->imageWidth  = $halfCanvas - $this->yDimensionWidth - self::IMAGE_PADDING;
            $this->imageHeight = $this->canvasHeight - self::IMAGE_PADDING;
        }
    }


    /**
     * Called before render
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $v   = 1.1;
        $svg = $this->appendChild(
            $this->getSvg(
                [
                    'height'  => '100%',
                    'width'   => '100%',
                    'viewBox' => '-10 -10 '.($this->canvasWidth - 10) * $v.' '.($this->canvasHeight - 10) * $v,
                    'xmlns'   => 'http://www.w3.org/2000/svg',
                    'id'      => 'svg-element-'.$this->elementId,
                ]
            )
        );
        if (!$this->configuration || empty($this->configuration->layers)) {
            return;
        }


        $css = 'text {font-family:Arial,sans-serif;font-size:14px;color:#666666}';
        $svg->appendChild($this->createElement('style', $css, ['type' => 'text/css']));


        $rotate = $this->configuration->inclination % self::FULL_CIRCLE_ANGULAR_DEGREE;

        /**
         * Append defs
         */
        $this->defs = $svg->appendChild($this->getDefs());
        $this->defImages($this->defs, $this->configuration->patternImages);

        /**
         * Image layer
         */
        $this->drawImage($svg, $rotate);

        /**
         * Legend
         */
        if ($this->drawLegend) {
            $this->drawLegend($svg);
        }


        $this->firstChild->removeAttribute('xmlns:default');
    }


    /**
     * defines the images for the patterns
     *
     * @param DOMElement $Defs
     * @param array      $patternImages
     */
    protected function defImages($Defs, $patternImages)
    {
        foreach ($patternImages as $image) {
            $Pattern = $Defs->appendChild(
                $this->getPattern('pattern'.$image->patternId.'-'.$this->elementId, 0, 0, $image->width, $image->height)
            );

            if ($this->buildMode != self::BUILDMODE_PDF) {
                $Pattern->appendChild($this->getImage(0, 0, $image->width, $image->height, $image->uri));
            } else {
                $this->importPatternImage($Pattern, $image->filePath, $image->patternId, $this->elementId);
            }
        }
    }


    /**
     * Draw the image
     *
     * @param     $container
     * @param int $rotate
     * @return void -
     */
    protected function drawImage($container, $rotate = 0)
    {
        $cx = $this->drawLegend ? ($this->canvasWidth / 4) : ($this->canvasWidth / 2);  // center of canvas half width
        $cy = $this->canvasHeight / 2; // center of canvas height
        $ix = ($this->imageWidth + $this->yDimensionWidth + self::IMAGE_PADDING) / 2;
        $iy = ($this->imageHeight + self::IMAGE_PADDING) / 2;

        $transform   = [];
        $transform[] = $this->translate($cx - $ix, $cy - $iy); // move center diff
        $transform[] = $this->rotate($rotate, $ix, $iy); // rotate on center

        $imageGroup = $container->appendChild($this->getGroup(['transform' => join(' ', $transform)]));

        $y = 0;
        $x = $startX = $rotate == (self::FULL_CIRCLE_ANGULAR_DEGREE / 2) || $rotate == self::VERTICAL ? 0
            : $this->yDimensionWidth;

        foreach ($this->configuration->layers as $layer) {
            $this->drawLayer($imageGroup, $layer, $x, $y, $rotate);

            /**
             * One single layer component, go to next line
             */
            $x = $startX;
            $y += $this->calcHeightInPx($this->getLayerHeight($layer));
        }

        /**
         * Add badges
         */
        if ($this->drawLegend) {
            foreach ($this->componentBadges as $index => $Badge) {
                $this->drawBadgeForComponent(
                    $imageGroup,
                    $Badge->x,
                    $Badge->y,
                    $Badge->width,
                    $Badge->height,
                    $index,
                    $rotate
                );
            }
        }

        if ($this->showTotalSize) {
            $this->drawYDimension($imageGroup, $rotate);
        }
    }


    /**
     * draws the given layer
     *
     * @param       $container
     * @param array $layer
     * @param       $x
     * @param       $y
     * @return void -
     */
    protected function drawLayer($container, $layer, $x, $y)
    {
        foreach ($layer as $index => $Component) {
            $this->drawComponent($container, $Component, $x, $y);

            /**
             * Splitted layer (two components), add next component in same line
             */
            $x += $this->imageWidth * $Component->ratio;
        }
    }


    /**
     * drawComponent
     *
     * draws the given Component
     *
     * @param          $container
     * @param stdClass $Component
     * @param float    $x
     * @param float    $y
     * @return void -
     */
    protected function drawComponent($container, $Component, $x, $y)
    {
        $width  = $this->imageWidth * $Component->ratio;
        $height = $this->calcHeightInPx($Component->size);

        $patternUrl = $this->defPattern($Component, $y);

        $container->appendChild(
            $this->getRect(
                $x,
                $y,
                $width,
                $height,
                [
                    'stroke'       => '#000',
                    'stroke-width' => 1,
                    'fill'         => 'url(#'.$patternUrl.')',
                ]
            )
        );


        /**
         * Save badge coordinates, render badges after drawing the components
         */
        $badge         = $this->componentBadges[$Component->index] = new \stdClass();
        $badge->x      = $x;
        $badge->y      = $y;
        $badge->width  = $width;
        $badge->height = $height;
    }


    /**
     * defPattern
     *
     * defines Pattern for the given Component.
     *
     * @param stdClass $component
     * @param float    $y
     * @return string patternId
     */
    protected function defPattern($component, $y)
    {
        $patternHeight = $component->category->height;
        $patternWidth  = $component->category->width;

        /**
         * Assumes that the pattern is `small' and needs scaling.
         * scales by relative layer height / pattern height
         */
        $pxHeight = $this->calcHeightInPx($component->size);
        $scale    = $pxHeight / max(1, $patternHeight);

        /**
         * A pattern starts at (0,0). Therefor translate y-pos of the pattern
         * to the layer y-position
         */
        $translateY = $y % max(1, $pxHeight);

        /**
         * Make the url refs unique
         */
        $patternId  = 'trPattern'.IdFactory::getUniqueXmlId().'-'.$this->elementId;
        $patternUrl = '#pattern'.$component->category->patternId.'-'.$this->elementId;

        $Pattern = $this->defs->appendChild(
            $this->getPattern(
                $patternId,
                0,
                0,
                $patternWidth,
                $patternHeight,
                ['xlink:href' => $patternUrl, 'patternTransform' => 'translate(0 '.$translateY.') scale('.$scale.')']
            )
        );

        return $patternId;
    }


    /**
     * drawYDimension
     *
     * @param DOMElement $element
     * @param float|int  $rotate
     * @return void -
     */
    protected function drawYDimension($element, $rotate = 0)
    {
        $startX = $rotate == (self::FULL_CIRCLE_ANGULAR_DEGREE / 2) || $rotate == self::VERTICAL ? $this->imageWidth
            : 0;

        $spaceFromLeftEdgeOfTheImage = $startX + $this->yDimensionWidth / 2;

        if (($this->getElementHeight($this->configuration) * self::CONVERSION_FACTOR_M_TO_MM) < self::THOUSAND) {
            $dimensionBoxLeftSpace = 6;
        } else {
            $dimensionBoxLeftSpace = 3;
        }

        $scaleHeight = $this->calcHeightInPx($this->getElementHeight($this->configuration));

        $dimensionBoxX = $startX;
        $dimensionBoxY = ($scaleHeight / 2) - (self::VALUE_BOX_HEIGHT / 2);

        $element->appendChild(
            $this->getLine(
                $spaceFromLeftEdgeOfTheImage + self::SIZE_OF_THE_TOP_AND_THE_BOTTOM_LINE_OF_THE_DIMENSIONS / 2,
                0,
                $spaceFromLeftEdgeOfTheImage - self::SIZE_OF_THE_TOP_AND_THE_BOTTOM_LINE_OF_THE_DIMENSIONS / 2,
                0
            )
        );
        $element->appendChild(
            $this->getLine(
                $spaceFromLeftEdgeOfTheImage,
                0,
                $spaceFromLeftEdgeOfTheImage,
                $scaleHeight - 1
            )
        );
        $element->appendChild(
            $this->getLine(
                $spaceFromLeftEdgeOfTheImage + self::SIZE_OF_THE_TOP_AND_THE_BOTTOM_LINE_OF_THE_DIMENSIONS / 2,
                $scaleHeight - 1,
                $spaceFromLeftEdgeOfTheImage - self::SIZE_OF_THE_TOP_AND_THE_BOTTOM_LINE_OF_THE_DIMENSIONS / 2,
                $scaleHeight - 1
            )
        );

        $group = $element->appendChild(
            $this->getGroup(
                [
                    'transform' => $this->rotate(
                        -1 * $rotate,
                        $dimensionBoxX + (self::VALUE_BOX_WIDTH / 2),
                        $dimensionBoxY + (self::VALUE_BOX_HEIGHT / 2)
                    ),
                ]
            )
        );

        $group->appendChild(
            $this->getRect(
                $dimensionBoxX,
                $dimensionBoxY,
                self::VALUE_BOX_WIDTH,
                self::VALUE_BOX_HEIGHT,
                ['fill' => '#ffffff']
            )
        );
        $size = $group->appendChild(
            $this->getText(
                '',
                $dimensionBoxX + $dimensionBoxLeftSpace,
                $dimensionBoxY + self::FONT_SIZE_BADGE,
                ['font-size' => self::FONT_SIZE_BADGE.'px']
            )
        );

        $size->appendChild(
            $this->getTSpan(
                ElcaNumberFormat::toString(
                    $this->getElementHeight($this->configuration) * self::CONVERSION_FACTOR_M_TO_MM,
                    0
                ),
                $dimensionBoxX + $dimensionBoxLeftSpace,
                $dimensionBoxY + self::FONT_SIZE_BADGE,
                ['font-size' => self::FONT_SIZE_BADGE.'px']
            )
        );
        $size->appendChild(
            $this->getTSpan(
                'mm',
                $dimensionBoxX + $dimensionBoxLeftSpace,
                $dimensionBoxY + self::FONT_SIZE_BADGE + (self::LINE_HEIGHT / 2),
                ['font-size' => self::FONT_SIZE_BADGE.'px']
            )
        );
    }


    /**
     * drawBadgeForComponent
     *
     * @param DOMElement $element
     * @param float      $x
     * @param float      $y
     * @param float      $width
     * @param float      $height
     * @param            $componentIndex
     * @param int        $rotate
     * @return void -
     */
    protected function drawBadgeForComponent($element, $x, $y, $width, $height, $componentIndex, $rotate)
    {
        $cx = ($x + $x + $width) / 2;
        $cy = ($y + $y + $height) / 2;

        /**
         * Look out for collisions
         */
        $collBadgeRadius = self::COMPONENT_BADGE_RADIUS * 2;
        foreach ($this->badgeCoordinates as $key => $badge) {
            if (($cx < $badge['x'] + $collBadgeRadius && $cx > $badge['x'] - $collBadgeRadius) && ($cy < $badge['y'] + $collBadgeRadius && $cy > $badge['y'] - $collBadgeRadius)) {
                $cx = $key % 2 ? $cx + $collBadgeRadius : $cx - $collBadgeRadius;
            }
        }

        $numberPosX = $cx - self::BADGE_POS_X_CORRECTION_SPACE / 2;

        /**
         * Fix digit placement > 10 (@todo: should be log10)
         */
        if ($componentIndex + 1 >= self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION) {
            $numberPosX = $cx - self::BADGE_POS_X_CORRECTION_SPACE;
        }

        $group = $element->appendChild($this->getGroup(['transform' => $this->rotate(-1 * $rotate, $cx, $cy)]));

        $this->drawBadge($group, $cx, $cy, self::COMPONENT_BADGE_RADIUS);
        $group->appendChild(
            $this->getText(
                $componentIndex + 1,
                $numberPosX + 1,
                $cy + (self::COMPONENT_BADGE_RADIUS / 2) - 1,
                ['font-size' => self::FONT_SIZE_BADGE.'px']
            )
        );

        $this->badgeCoordinates[] = ['x' => $cx, 'y' => $cy];
    }


    /**
     * Draws the legend container and content
     *
     * @param $container
     * @return void -
     */
    protected function drawLegend($container)
    {
        $transform = [];

        $lWidth  = $this->getLegendWidth();
        $lHeight = $this->getLegendHeight();

        $bWidth  = $this->canvasWidth / 2;
        $bHeight = $this->canvasHeight - self::LEGEND_PADDING_Y / 2;

        $ratio = min($bWidth / $lWidth, $bHeight / $lHeight);

        if ($ratio < 1) {
            $transform[] = $this->scale($ratio);
            $transform[] = $this->translate(
                ((1 / $ratio - 1) * $bWidth) + self::LEGEND_PADDING_X,
                self::LEGEND_PADDING_Y
            );
        } else {
            $transform[] = $this->translate(self::LEGEND_PADDING_X, self::LEGEND_PADDING_Y);
        }

        $legendGroup = $container->appendChild($this->getGroup(['transform' => implode(' ', $transform)]));

        $layerLegendGroup = $legendGroup->appendChild($this->getGroup());

        $x = $bWidth;
        $y = 0;

        foreach ($this->configuration->layers as $layer) {
            foreach ($layer as $component) {
                $this->drawLayerComponentLegend($layerLegendGroup, $component, $x, $y);
                $y += self::LINE_HEIGHT;
            }
        }

        if (count($this->configuration->singleComponents)) {
            $lineGroup = $legendGroup->appendChild($this->getGroup(['style' => 'font-style:italic']));
            $lineGroup->appendChild($this->getLine($x + 2 * self::LEGEND_BADGE_RADIUS, $y, $x + $lWidth, $y));
            $y += self::LINE_HEIGHT * .8;

            $singleComponentLegendGroup = $legendGroup->appendChild(
                $this->getGroup()
            );

            foreach ($this->configuration->singleComponents as $singleComponent) {
                $this->drawSingleComponentLegend($singleComponentLegendGroup, $singleComponent, $x, $y);
                $y += self::LINE_HEIGHT * .8;
            }
        }
    }


    /**
     * drawComponentLegend
     *
     * draws the Component Legend
     *
     * @param DOMElement $element
     * @param            $component
     * @param float      $x
     * @param float      $y
     * @return void -
     */
    protected function drawLayerComponentLegend($element, $component, $x, $y)
    {
        $legendText = $this->getLayerLegendText($component->name, $component->size);

        $numberPosX = $x - floor(self::LEGEND_BADGE_RADIUS / 2);

        if ($component->index + 1 >= self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION) {
            $numberPosX = $x - self::BADGE_POS_X_CORRECTION_SPACE;
        }

        $this->drawBadge($element, $x, $y, self::LEGEND_BADGE_RADIUS);

        $element->appendChild(
            $this->getText(
                $component->index + 1,
                $numberPosX,
                $y + self::LEGEND_BADGE_RADIUS / 2,
                ['font-size' => self::FONT_SIZE_LEGEND.'px']
            )
        );

        $element->appendChild(
            $this->getText(
                $legendText,
                $x + 2 * self::LEGEND_BADGE_RADIUS,
                $y + self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION / 2,
                ['font-size' => self::FONT_SIZE_LEGEND.'px']
            )
        );
    }

    protected function drawSingleComponentLegend($element, $component, $x, $y)
    {
        $legendText = $this->getSingleComponentLegendText($component->name, $component->quantity, $component->unit);

        $element->appendChild(
            $this->getText(
                $legendText,
                $x + 2 * self::LEGEND_BADGE_RADIUS,
                $y + self::MAX_COMPONENT_COUNT_FOR_A_BADGE_CORRECTION / 2,
                ['font-size' => self::FONT_SIZE_LEGEND.'px']
            )
        );
    }

    /**
     * Calculates the legend height
     *
     * @return float $height
     */
    protected function getLegendHeight()
    {
        $height = 0;
        foreach ($this->configuration->layers as $layer) {
            $height += self::LINE_HEIGHT * count($layer);
        }

        $height += self::LINE_HEIGHT * count($this->configuration->singleComponents);

        return $height;
    }

    /**
     * Returns the legend text
     *
     * @param  string $name - name of the component
     * @param  float  $size - size of the component in m
     * @return string
     */
    protected function getLayerLegendText($name, $size)
    {
        return sprintf(
            self::LEGEND_COMPONENT_FORMAT,
            $name,
            ElcaNumberFormat::toString($size * self::CONVERSION_FACTOR_M_TO_MM, 2)
        );
    }

    protected function getSingleComponentLegendText($name, $quantity, $unit)
    {
        return sprintf('%s, %s', $name, ElcaNumberFormat::formatQuantity($quantity, $unit));
    }

    /**
     * Returns the height of the élement image in m
     *
     * @return float $height
     */
    protected function getElementHeight()
    {
        $height = 0;
        foreach ($this->configuration->layers as $pos => $layer) {
            $height += $this->getLayerHeight($layer);
        }

        return $height;
    }

    /**
     * Gets the height of the given layer in m
     *
     * @param array $layer
     * @return float $height
     */
    protected function getLayerHeight($layer)
    {
        $height = 0;
        foreach ($layer as $key => $component) {
            if ($component->size > $height) {
                $height = $component->size;
            }
        }

        return $height;
    }

    /**
     * calculateHeight
     *
     * calculates the ratio of the given height
     * and return a new scaled height
     *
     * @param $layerHeight
     * @return float $height
     */
    protected function calcHeightInPx($layerHeight)
    {
        return $this->imageHeight / $this->getElementHeight() * $layerHeight;
    }

    /**
     * generateConfig
     *
     * @param ElcaElement $element
     * @return object
     */
    protected function generateConfig($element)
    {
        $elements = new ElcaElementSet();

        if ($element->isComposite()) {
            $compositeElements = ElcaCompositeElementSet::findByCompositeElementId(
                $element->getId(),
                [],
                ['position' => 'ASC']
            );

            foreach ($compositeElements as $compositeElement) {
                $elements->add($compositeElement->getElement());
            }
        } else {
            $elements->add($element);
        }

        $elementType = $element->getElementTypeNode();

        $config                   = new \stdClass;
        $config->layers           = [];
        $config->singleComponents = [];
        $config->inclination      = $elementType->getPrefInclination();

        $lastComponentPos     = 1;
        $lastComponentSibling = false;
        $componentIndex       = 0;
        $patternImages        = [];

        foreach ($elements as $subElement) {
            /**
             * On composite elements skip refUnit other than m2
             */
            if ($element->isComposite() && $subElement->getRefUnit() !== Elca::UNIT_M2) {
                continue;
            }

            /**
             * Skip elements without components
             */
            $components = ElcaElementComponentSet::findLayers($subElement->getId());
            if (!$components->count()) {
                continue;
            }

            $siblings = [];

            $componentCount   = count($components);
            $componentCounter = 1;

            foreach ($components as $component) {
                $componentConfig            = new \stdClass;
                $componentConfig->index     = $componentIndex++;
                $componentConfig->pos       = $component->layerPosition;
                $componentConfig->size      = $component->layerSize;
                $componentConfig->isSibling = $component->layerSiblingId !== null;
                $componentConfig->ratio     = $component->layerAreaRatio ?? 1;

                $processConfig         = $component->getProcessConfig();
                $componentConfig->name = \processConfigName($processConfig->getId());

                $svgPattern = $processConfig->getSvgPatternId() ? $processConfig->getSvgPattern()
                    : ElcaSvgPattern::findByElementComponentId($component->id);

                $category            = new \stdClass;
                $category->patternId = $svgPattern->getId();
                $category->name      = $svgPattern->getName();
                $category->width     = $svgPattern->getWidth();
                $category->height    = $svgPattern->getHeight();
                $category->uri       = $svgPattern->getImage()->getURI();
                $category->filePath  = $svgPattern->getImage()->getFullPath();

                if (!isset($patternImages[$patternId = $svgPattern->getId()])) {
                    $patternImages[$patternId] = true;
                    $config->patternImages[]   = $category;
                }

                $componentConfig->category = $category;

                if (($componentConfig->pos != $lastComponentPos) && $lastComponentSibling) {
                    $config->layers[] = $siblings;
                    $siblings         = null;
                }

                $siblings[] = $componentConfig;
                if (!$componentConfig->isSibling || ($componentCounter == $componentCount)) {
                    $config->layers[] = $siblings;
                    $siblings         = null;
                }

                $lastComponentPos     = $componentConfig->pos;
                $lastComponentSibling = $componentConfig->isSibling;
                $componentCounter++;
            }

            /**
             * @var ElcaElementComponent[]|ElcaElementComponentSet $singleComponents
             */
            $singleComponents = ElcaElementComponentSet::findSingleComponents($subElement->getId());
            if (!$singleComponents->count()) {
                continue;
            }

            foreach ($singleComponents as $component) {
                $componentConfig = new \stdClass();
                $componentConfig->name = \processConfigName($component->getProcessConfigId());
                $componentConfig->quantity = $component->getQuantity();
                $componentConfig->unit = $component->getProcessConversion()->getInUnit();

                $config->singleComponents[] = $componentConfig;
            }
        }

        return $config;
    }

    /**
     * Calculates the legend width
     *
     * @return float $width
     */
    private function getLegendWidth()
    {
        $maxWidth = 0;
        foreach ($this->configuration->layers as $pos => $layer) {
            foreach ($layer as $component) {
                $width = \utf8_strlen($this->getLayerLegendText($component->name, $component->size)) * self::CHAR_WIDTH;

                if ($width > $maxWidth) {
                    $maxWidth = $width;
                }
            }
        }

        return $maxWidth;
    }
}
