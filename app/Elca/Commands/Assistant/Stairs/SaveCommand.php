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

namespace Elca\Commands\Assistant\Stairs;

use Beibob\Blibs\RequestBase;
use Elca\ElcaNumberFormat;
use Elca\Model\Assistant\Material\Material;
use Elca\Model\Assistant\Stairs\MiddleHolmStaircase;
use Elca\Model\Assistant\Stairs\SolidStaircase;
use Elca\Model\Assistant\Stairs\Staircase;
use Elca\Model\Assistant\Stairs\Steps\Cover;
use Elca\Model\Assistant\Stairs\Steps\Riser;
use Elca\Model\Assistant\Stairs\Steps\Step;
use Elca\Model\Assistant\Stairs\StringerStaircase;

class SaveCommand
{
    public $projectVariantId;
    public $elementTypeNodeId;
    public $elementId;
    public $type;
    public $name;
    public $width;
    public $stepDepth;
    public $stepHeight;
    public $numberOfSteps;
    public $coverSize;
    public $coverLength1;
    public $coverLength2;
    public $riserHeight;
    public $riserSize;
    public $solidSlabHeight;
    public $solidLength;
    public $stringerWidth;
    public $stringerHeight;
    public $stringerLength;
    public $numberOfStringers;
    public $holmWidth;
    public $holmHeight;
    public $holmSize;
    public $holmShape;
    public $holmOrientation;
    public $holmLength;
    public $numberOfPlatforms;
    public $platformWidth;
    public $platformHeight;
    public $solidMaterial1Share;
    public $solidMaterial2Share;
    public $platformConstructionElementId;
    public $platformCoverElementId;

    public $materialId = [];


    /**
     * @param RequestBase $request
     * @return SaveCommand
     * @throws \Exception
     */
    public static function createFromRequest(RequestBase $request)
    {
        $command = new self();

        $command->projectVariantId = $request->projectVariantId;
        $command->elementTypeNodeId = $request->elementTypeNodeId;
        $command->elementId = $request->e;

        $command->type = $request->type;
        $command->name = $request->name;
        $command->width = ElcaNumberFormat::fromString($request->width);
        $command->stepDepth = ElcaNumberFormat::fromString($request->stepDepth) / 100;
        $command->stepHeight = ElcaNumberFormat::fromString($request->stepHeight) / 100;
        $command->numberOfSteps = ElcaNumberFormat::fromString($request->numberOfSteps, 0);
        $command->coverSize = ElcaNumberFormat::fromString($request->coverSize) / 100;
        $command->coverLength1 = ElcaNumberFormat::fromString($request->coverLength1) / 100;
        $command->coverLength2 = $request->has('isTrapezoid')? ElcaNumberFormat::fromString($request->coverLength2) / 100 : $command->coverLength1;

        $command->riserHeight = $request->riserHeight? ElcaNumberFormat::fromString($request->riserHeight) / 100 : null;
        $command->riserSize = $request->riserSize? ElcaNumberFormat::fromString($request->riserSize) / 100 : null;

        foreach ($request->getArray('materialId') as $key => $value) {
            $command->materialId[$key] = empty($value) ? null : $value;
        }

        switch ($command->type)
        {
            case Staircase::TYPE_SOLID:
                $command->solidSlabHeight = ElcaNumberFormat::fromString($request->solidSlabHeight) / 100;
                $command->solidLength = ElcaNumberFormat::fromString($request->solidLength);
                $command->solidMaterial1Share = ElcaNumberFormat::fromString($request->solidMaterial1Share, 3, true);
                $command->solidMaterial2Share = $request->get('solidMaterial2Share') && $command->materialId['solid2']
                    ? ElcaNumberFormat::fromString($request->solidMaterial2Share, 3, true)
                    : 0;

                if (!$command->materialId['solid2'] && $command->solidMaterial1Share !== 1)
                    $command->solidMaterial1Share = 1;
                break;

            case Staircase::TYPE_STRINGER:
                $command->stringerWidth = ElcaNumberFormat::fromString($request->stringerWidth) / 100;
                $command->stringerHeight = ElcaNumberFormat::fromString($request->stringerHeight) / 100;
                $command->stringerLength = ElcaNumberFormat::fromString($request->stringerLength);
                $command->numberOfStringers = ElcaNumberFormat::fromString($request->numberOfStringers);
                break;

            case Staircase::TYPE_MIDDLE_HOLM:
                $command->holmWidth = ElcaNumberFormat::fromString($request->holmWidth) / 100;
                $command->holmHeight = ElcaNumberFormat::fromString($request->holmHeight) / 100;
                $command->holmLength = ElcaNumberFormat::fromString($request->holmLength);
                $command->holmSize = $request->holmSize? ElcaNumberFormat::fromString($request->holmSize) / 1000 : null;
                $command->holmShape = $request->holmShape? (int)$request->holmShape : null;
                $command->holmOrientation = $request->holmOrientation? (int)$request->holmOrientation : null;
                break;

            default:
                throw new \Exception('Unknown staircase type');
        }

        //$command->alternativeLength = $request->alternativeLength? ElcaNumberFormat::fromString($request->alternativeLength) : null;

        $command->numberOfPlatforms = ElcaNumberFormat::fromString($request->numberOfPlatforms, 0);
        $command->platformWidth = ElcaNumberFormat::fromString($request->platformWidth);
        $command->platformHeight = ElcaNumberFormat::fromString($request->platformHeight);
        $command->platformCoverElementId = $request->platformCoverElementId? (int)$request->platformCoverElementId : null;
        $command->platformConstructionElementId = $request->platformConstructionElementId? (int)$request->platformConstructionElementId : null;


        return $command;
    }

    /**
     * @return MiddleHolmStaircase|SolidStaircase|StringerStaircase
     * @throws \Exception
     */
    public function getStaircase()
    {
        $riser = null;
        if ($this->riserHeight && $this->riserSize && $this->materialId['riser']) {
            $riser = new Riser(
                $this->riserHeight,
                $this->width,
                $this->riserSize,
                $this->getMaterial('riser')
            );
        }

        $step = new Step(
            $this->width,
            $this->stepDepth,
            $this->stepHeight,
            new Cover(
                $this->getMaterial('cover'),
                $this->coverSize,
                $this->width,
                $this->coverLength1,
                $this->coverLength2
            ),
            $riser
        );

        switch ($this->type) {
            case Staircase::TYPE_SOLID:
                $staircase = new SolidStaircase(
                    $this->name,
                    $step,
                    $this->numberOfSteps,
                    $this->solidSlabHeight,
                    $this->getMaterial('solid1', $this->solidMaterial1Share, 1),
                    $this->getMaterial('solid2', $this->solidMaterial2Share, 0),
                    $this->solidLength
                );
                break;

            case Staircase::TYPE_STRINGER:
                $staircase = new StringerStaircase(
                    $this->name,
                    $step,
                    $this->numberOfSteps,
                    $this->stringerWidth,
                    $this->stringerHeight,
                    $this->getMaterial('stringer'),
                    $this->numberOfStringers,
                    $this->stringerLength
                );
                break;

            case Staircase::TYPE_MIDDLE_HOLM:
                $staircase = new MiddleHolmStaircase(
                    $this->name,
                    $step,
                    $this->numberOfSteps,
                    $this->holmWidth,
                    $this->holmHeight,
                    $this->getMaterial('holm'),
                    $this->holmSize,
                    $this->holmShape,
                    $this->holmOrientation,
                    $this->holmLength
                );
                break;

            default:
                throw new \Exception('Unknown staircase type');
        }

        if ($this->numberOfPlatforms > 0) {
            $staircase->specifyPlatform(
                $this->platformWidth,
                $this->platformHeight,
                $this->numberOfPlatforms
            );
        }

        return $staircase;
    }

    /**
     * @return int
     */
    public function platformConstructionElementId()
    {
        return $this->platformConstructionElementId;
    }

    /**
     * @return int
     */
    public function platformCoverElementId()
    {
        return $this->platformCoverElementId;
    }

    /**
     * @param     $key
     * @param int $share
     * @return Material|null
     */
    private function getMaterial($key, $share = 1, $defaultShare = 1)
    {
        if (isset($this->materialId[$key]))
            return new Material($this->materialId[$key], $share);

        return new Material(null, $defaultShare);
    }

}
