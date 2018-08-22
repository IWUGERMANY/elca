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
namespace Elca\View\helpers;

use Beibob\HtmlTools\HtmlSubmitButton;

/**
 * Implements a form submit button.
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlSubmitButton extends HtmlSubmitButton
{
    /**
     * Creates a new html submit button
     *
     * @param string $name    the name of the button
     * @param string $value   the value (i.e. the caption of the button)
     */
    public function __construct($name, $value, $isSubmitChangesButton = false)
    {
        parent::__construct($name, $value);

        if($isSubmitChangesButton)
            $this->addClass('submit-changes');
    }
    // End __construct
}
// End ElcaHtmlSubmitButton
