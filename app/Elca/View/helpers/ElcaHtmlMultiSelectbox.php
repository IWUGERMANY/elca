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
use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlMultiSelectbox;
use DOMDocument;


/**
 * ElcaHtmlMultiSelectbox ${CARET}
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ElcaHtmlMultiSelectbox extends HtmlMultiSelectbox
{
    public function build(DOMDocument $Document)
    {
        $factory = new HtmlDOMFactory($Document);
        $container = parent::build($Document);

        $Span = $container->appendChild($factory->getSpan('', ['class' => 'select-helpers']));
        $Span->appendChild($factory->getA(['href' => '#', 'rel' => 'all'], t('Alle')));
        $Span->appendChild($factory->getA(['href' => '#', 'rel' => 'invert'], t('Invertieren')));

        return $container;
    }

}
// End ElcaHtmlMultiSelectbox