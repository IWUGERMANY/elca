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

/**
 *
 */
class ElcaModalProcessingView extends HtmlView
{
    /**
     * Default headline
     */
    const DEFAULT_HEADLINE = 'LCA Berechnung';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_modal_processing', 'elca');

        if(!$this->has('headline'))
            $this->assign('headline', self::DEFAULT_HEADLINE);

        if($this->has('reload'))
            $this->assign('reload', $this->get('reload')? 'true' : 'false');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called before rendering
     */
    protected function beforeRender()
    {
        $Description = $this->getElementById('description', true);

        if(!$this->has('description'))
            $Description->parentNode->removeChild($Description);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaModalProcessingView