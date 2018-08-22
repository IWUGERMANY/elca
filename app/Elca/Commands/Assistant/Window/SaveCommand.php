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

namespace Elca\Commands\Assistant\Window;

use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\RequestBase;
use Elca\ElcaNumberFormat;
use Elca\Model\Assistant\Window\Component;
use Elca\Model\Assistant\Window\Sill;
use Elca\Model\Assistant\Window\Soffit;
use Elca\Model\Assistant\Window\Window;
use Elca\Model\Common\Geometry\Rectangle;

/**
 * SaveCommand
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class SaveCommand
{
    public $projectVariantId;
    public $elementId;
    public $elementTypeNodeId;
    public $name;
    public $fixedFrameWidth;
    public $sashFrameWidth;
    public $sealingWidth;
    public $frameRatio;
    public $glassRatio;
    public $glassSize;
    public $handles;
    public $width;
    public $height;
    public $processConfigId;
    public $mullions;
    public $transoms;
    public $fittings;
    public $soffitIndoorDepth;
    public $soffitIndoorSize;
    public $soffitOutdoorDepth;
    public $soffitOutdoorSize;
    public $sillIndoorDepth;
    public $sillIndoorSize;
    public $sillOutdoorDepth;
    public $sillOutdoorSize;
    public $hasTopLight;
    public $topLightHeight;
    public $tileWidths = [];
    public $tileHeights = [];
    public $fixedMullionsTransoms;

    /**
     * @param RequestBase $request
     * @return SaveCommand
     */
    public static function createFromRequest(RequestBase $request)
    {
        $command = new self();

        $command->projectVariantId         = $request->projectVariantId;
        $command->elementTypeNodeId        = $request->elementTypeNodeId;
        $command->elementId                = $request->e;
        $command->name                     = $request->name;
        $command->width                    = ElcaNumberFormat::fromString($request->width);
        $command->height                   = ElcaNumberFormat::fromString($request->height);
        $command->fixedFrameWidth          = ElcaNumberFormat::fromString($request->fixedFrameWidth) / 100; // from cm
        $command->sashFrameWidth           = ElcaNumberFormat::fromString($request->sashFrameWidth) / 100; // from cm
        $command->glassSize                = ElcaNumberFormat::fromString($request->glassSize) / 1000;  // from mm
        $command->hasTopLight              = $request->has('hasTopLight');
        $command->topLightHeight           = ElcaNumberFormat::fromString($request->topLightHeight)
            ? ElcaNumberFormat::fromString($request->topLightHeight) / 100
            : 2 * $command->sashFrameWidth + Window::MIN_GLASS_HEIGHT ; // from cm

        $command->fixedMullionsTransoms    = $request->has('fixedMullionsTransoms');
        $command->sealingWidth             = ElcaNumberFormat::fromString($request->sealingWidth) / 1000; // from mm
        $command->frameRatio               = ElcaNumberFormat::fromString($request->frameRatio, 1, true);
        $command->glassRatio               = ElcaNumberFormat::fromString($request->glassRatio, 1, true);
        $command->mullions                 = (int)ElcaNumberFormat::fromString($request->numberOfMullions, 0);
        $command->transoms                 = (int)ElcaNumberFormat::fromString($request->numberOfTransoms, 0);
        $command->fittings                 = $request->fittings? ElcaNumberFormat::fromString($request->fittings, 0) : null;
        $command->handles                  = $request->handles? ElcaNumberFormat::fromString($request->handles, 0) : null;
        $command->soffitIndoorDepth        = $request->soffitIndoorDepth  ? ElcaNumberFormat::fromString($request->soffitIndoorDepth) / 100 : null; // in cm
        $command->soffitIndoorSize         = $request->soffitIndoorSize   ? ElcaNumberFormat::fromString($request->soffitIndoorSize) / 1000 : null; // in mm
        $command->soffitOutdoorDepth       = $request->soffitOutdoorDepth ? ElcaNumberFormat::fromString($request->soffitOutdoorDepth)  / 100 : null; // in cm
        $command->soffitOutdoorSize        = $request->soffitOutdoorSize  ? ElcaNumberFormat::fromString($request->soffitOutdoorSize) / 1000 : null; // in mm
        $command->sillIndoorDepth          = $request->sillIndoorDepth    ? ElcaNumberFormat::fromString($request->sillIndoorDepth) / 100 : null; // in cm
        $command->sillIndoorSize           = $request->sillIndoorSize     ? ElcaNumberFormat::fromString($request->sillIndoorSize) / 1000 : null; // in mm
        $command->sillOutdoorDepth         = $request->sillOutdoorDepth   ? ElcaNumberFormat::fromString($request->sillOutdoorDepth) / 100 : null; // in cm
        $command->sillOutdoorSize          = $request->sillOutdoorSize    ? ElcaNumberFormat::fromString($request->sillOutdoorSize) / 1000 : null; // in mm

        foreach ($request->getArray('processConfigId') as $key => $value) {
           $command->processConfigId[$key] = empty($value)? null : $value;
        }
        foreach ($request->getArray('tileWidth') as $key => $value) {
            $command->tileWidths[(int)$key] = ElcaNumberFormat::fromString($value, 2, true);
        }
        foreach ($request->getArray('tileHeight') as $key => $value) {
            $command->tileHeights[(int)$key] = ElcaNumberFormat::fromString($value, 2, true);
        }

        return $command;
    }

    /**
     * @return Window
     */
    public function getWindow()
    {
        $window = new Window(
            $this->name,
            new Rectangle(
                $this->width,
                $this->height
            ),
            $this->sealingWidth,
            $this->fixedFrameWidth,
            $this->processConfigId['sealing'],
            $this->processConfigId['fixedFrame'],
            $this->processConfigId['glass'],
            !empty($this->processConfigId['sashFrame']) ? $this->processConfigId['sashFrame'] : null,
            $this->sashFrameWidth ?: max(0, $this->sashFrameWidth),
            $this->mullions,
            $this->transoms,
            $this->fixedMullionsTransoms,
            $this->hasTopLight,
            $this->topLightHeight,
            $this->tileWidths,
            $this->tileHeights
        );

//        if (!empty($this->processConfigId['sashFrame']) || $this->sashFrameWidth) {
//            $window->getFixedFrame()->specifySashFrames(
//                $this->processConfigId['sashFrame'],
//                max(0, $this->sashFrameWidth)
//            );
//        }

        if ($this->handles || isset($this->processConfigId['handles'])) {
            $window->setHandles(
                new Component(
                    $this->processConfigId['handles'],
                    (int)$this->handles
                )
            );
        }

        if ($this->fittings || isset($this->processConfigId['fittings'])) {
            $window->setFittings(
                new Component(
                    $this->processConfigId['fittings'],
                    (int)$this->fittings
                )
            );
        }

        if (is_numeric($this->sillIndoorDepth) && FloatCalc::gt($this->sillIndoorDepth, 0) &&
            is_numeric($this->sillIndoorSize) && FloatCalc::gt($this->sillIndoorSize, 0) ||
            isset($this->processConfigId['sillIndoor'])
        ) {
            $window->setIndoorSill(
                new Sill(
                    new Rectangle(
                        $this->width,
                        max(0, $this->sillIndoorDepth)
                    ),
                    $this->sillIndoorSize,
                    !empty($this->processConfigId['sillIndoor']) ? $this->processConfigId['sillIndoor'] : null
                )
            );
        }
        if (FloatCalc::gt($this->sillOutdoorDepth, 0) && FloatCalc::gt($this->sillOutdoorSize, 0)) {
            $window->setOutdoorSill(
                new Sill(
                    new Rectangle(
                        $this->width,
                        max(0, $this->sillOutdoorDepth)
                    ),
                    max(0, $this->sillOutdoorSize),
                    !empty($this->processConfigId['sillOutdoor']) ? $this->processConfigId['sillOutdoor'] : null
                )
            );
        }
        if ($this->soffitIndoorDepth || $this->soffitIndoorSize || isset($this->processConfigId['soffitIndoor'])) {
            $window->setIndoorSoffit(
                new Soffit(
                    new Rectangle(
                        max(0, $this->soffitIndoorDepth),
                        $this->height * 2 + $this->width
                    ),
                    max(0, $this->soffitIndoorSize),
                    !empty($this->processConfigId['soffitIndoor']) ? $this->processConfigId['soffitIndoor'] : null
                )
            );
        }
        if ($this->soffitOutdoorDepth || $this->soffitOutdoorSize || isset($this->processConfigId['soffitOutdoor'])) {
            $window->setOutdoorSoffit(
                new Soffit(
                    new Rectangle(
                        max(0, $this->soffitOutdoorDepth),
                        $this->height * 2 + $this->width
                    ),
                    max(0, $this->soffitOutdoorSize),
                    !empty($this->processConfigId['soffitOutdoor']) ? $this->processConfigId['soffitOutdoor'] : null
                )
            );
        }

        if (isset($this->processConfigId['sunscreenIndoor'])) {
            $window->setSunscreenIndoorMaterialId($this->processConfigId['sunscreenIndoor']);
        }

        if (isset($this->processConfigId['sunscreenOutdoor'])) {
            $window->setSunscreenOutdoorMaterialId($this->processConfigId['sunscreenOutdoor']);
        }

        return $window;
    }
}
