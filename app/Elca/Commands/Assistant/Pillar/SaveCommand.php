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

namespace Elca\Commands\Assistant\Pillar;

use Beibob\Blibs\HttpRequest;
use Elca\ElcaNumberFormat;
use Elca\Model\Assistant\Material\Material;
use Elca\Model\Assistant\Pillar\Cylindric;
use Elca\Model\Assistant\Pillar\Pillar;
use Elca\Model\Assistant\Pillar\Rectangular;

class SaveCommand
{
    public $name;

    public $shape;

    public $material1Id;

    public $material1Share;

    public $material2Id;

    public $material2Share;

    public $height;

    public $unit;

    // shape rectangular
    public $width;

    public $length;

    // shape cylindric
    public $radius;


    public $elementId;

    /**
     * @param $request
     * @return SaveCommand
     */
    public static function createFromRequest(HttpRequest $request)
    {
        $cmd                 = new static();
        $cmd->elementId      = $request->e;
        $cmd->name           = $request->getString('name');
        $cmd->shape          = $request->getString('shape');
        $cmd->material1Id    = $request->material1 ?  (int)$request->material1 : null;
        $cmd->material1Share = $request->material1 ? ElcaNumberFormat::fromString($request->get('material1Share'), 2, true) : null;
        $cmd->material2Id    = $request->material2 ? (int)$request->material2 : null;
        $cmd->material2Share = $request->material2 ? ElcaNumberFormat::fromString(
            $request->get('material2Share'), 2, true
        ) : null;
        $cmd->height         = $request->height ? ElcaNumberFormat::fromString($request->get('height')) : 1;
        $cmd->unit           = $request->getString('unit');

        if ('cylindric' === $cmd->shape) {
            $cmd->radius = ElcaNumberFormat::fromString($request->get('radius'));
        } else {
            $cmd->width  = ElcaNumberFormat::fromString($request->get('width'));
            $cmd->length = ElcaNumberFormat::fromString($request->get('length'));
        }

        return $cmd;
    }

    /**
     * @return Pillar
     */
    public function getPillar()
    {
        if ('cylindric' === $this->shape) {
            $constructionShape = new Cylindric($this->radius);
        } else {
            $constructionShape = new Rectangular($this->width, $this->length);
        }

        return new Pillar(
            $this->name,
            $constructionShape,
            $this->material1Id ? new Material($this->material1Id, $this->material1Share) : null,
            $this->material2Id ? new Material($this->material2Id, $this->material2Share) : null,
            $this->height,
            $this->unit
        );
    }
}
