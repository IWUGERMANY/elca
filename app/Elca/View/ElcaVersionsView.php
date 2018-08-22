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

use Beibob\Blibs\HtmlView;
use DOMDocument;
use Parsedown;

/**
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 */
class ElcaVersionsView extends HtmlView
{
    /**
     * @var string $history
     */
    private $history;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_versions', 'elca');
        $this->history = $this->get('history', null);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called after rendering the template
     */
    protected function afterRender()
    {
        $Elt = $this->getElementById('history', true);

        if($this->history) {

            $Parsedown = new Parsedown();
            $html = '<div>'.$Parsedown->text($this->history).'</div>';

            $DomDoc = new DOMDocument();
            $DomDoc->loadXML($html);

            if($ImportedNode = $this->importNode($DomDoc->firstChild, true)) {

                $Elt->appendChild($ImportedNode);

                $Links = $this->query('//a', $Elt);

                foreach($Links as $Link) {
                    $Link->setAttribute('target', '_blank');
                    $Link->setAttribute('class', 'no-xhr');
                }
            }
        }
        else {
            $Elt->parentNode->removeChild($Elt);
        }
    }
    // End afterRender
}
// End ElcaIndexView
