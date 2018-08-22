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
use Elca\Db\ElcaSvgPatternSet;
use Elca\Security\ElcaAccess;

/**
 * Builds list of svg patterns
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaAdminSvgPatternsView extends HtmlView
{
    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_svg_patterns', 'elca');
    }
    // End init


    /**
     * Renders the view
     */
    protected function beforeRender()
    {
        $SvgPatternSet = ElcaSvgPatternSet::find(null, ['name' => 'ASC', 'id' => 'DESC']);

        if(!count($SvgPatternSet))
            return;

        $NoUsersElt = $this->getElementById('no-users');
        $NoUsersElt->parentNode->removeChild($NoUsersElt);

        $Access = ElcaAccess::getInstance();
        $hasAdminPrivileges = $Access->hasAdminPrivileges();
        $currentUserId = $Access->getUserId();

        $Ul = $this->getElementById('elca-svg-patterns')->appendChild($this->getUl());
        foreach($SvgPatternSet as $SvgPattern)
        {
            $Li = $Ul->appendChild($this->getLi(['id' => 'svg-pattern-' . $SvgPattern->getId() ]));

            $Include = $Li->appendChild($this->createElement('include'));
            $Include->setAttribute('name', 'Elca\View\ElcaSvgPatternSheetView');
            $Include->setAttribute('itemId', $SvgPattern->getId());
            $Include->setAttribute('headline', $SvgPattern->getName());
        }
    }
    // End render

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaAdminSvgPatternsView
