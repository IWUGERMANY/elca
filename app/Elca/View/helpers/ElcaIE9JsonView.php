<?php
/**
 * This file is part of blibs - mvc development framework
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *                    BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * blibs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * blibs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with blibs. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Elca\View\helpers;

use Beibob\Blibs\JsonView;

/**
 * A JsonView class
 *
 * @package blibs
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 *
 */
class ElcaIE9JsonView extends JsonView
{
    /**
     * Returns the content as string
     *
     * @param  -
     * @return string
     */
    public function __toString()
    {
        /**
         * This is a hack to allow sending the json data via ajax into an iframe
         * For this to work, the data have to be embedded into an valid dom element
         */
        return '<textarea>'. json_encode($this->getData()) .'</textarea>';
    }
    // End __toString
}
// End ElcaIE9JsonView
