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

use Beibob\HtmlTools\HtmlLink;
use DOMDocument;

/**
 * Toggle link
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlToggleLink extends HtmlLink
{
    /**
     * Toggle state
     */
    private $isOpened = false;


    /**
     * Creates a new static text element
     *
     * @param string $value the text to display
     */
    public function __construct($href = '#', $isOpened = false)
    {
        if($this->isOpened = $isOpened)
            $caption = t('öffnen');
        else
            $caption = t('schließen');

        parent::__construct($caption, $href);
    }
    // End __construct


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $this->addClass('no-history toggle-link');

        if($this->isOpened)
        {
            $this->setAttribute('title', t('Werte verbergen'));
            $this->addClass('open');
        }
        else
            $this->setAttribute('title', t('Werte anzeigen'));


        return parent::build($Document);
    }
    // End build

}
// End ElcaHtmlToggleLink
