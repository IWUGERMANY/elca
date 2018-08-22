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

use Beibob\Blibs\GroupSet;
use Beibob\Blibs\HtmlView;
use Elca\Security\ElcaAccess;

/**
 * Builds the groups view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaGroupsView extends HtmlView
{
    /**
     * Lädt ein Template
     */
    public function __construct(array $args = [])
    {
        parent::__construct('elca_groups');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Renders the view
     */
    protected function beforeRender()
    {
        $Groups = GroupSet::find(['is_usergroup' => false], ['lower(name)' => 'ASC']);

        if(!count($Groups))
            return;

        $NoGroupsElt = $this->getElementById('no-groups');
        $NoGroupsElt->parentNode->removeChild($NoGroupsElt);

        $Access = ElcaAccess::getInstance();

        $Ul = $this->getElementById('elca-groups')->appendChild($this->getUl());

        foreach($Groups as $Group)
        {
            $Li = $Ul->appendChild($this->getLi(['id' => 'user-' . $Group->getId() ]));

            $Include = $Li->appendChild($this->createElement('include'));
            $Include->setAttribute('name', 'Elca\View\ElcaGroupSheetView');
            $Include->setAttribute('itemId', $Group->getId());
            $Include->setAttribute('headline', $Group->getName());
            $Include->setAttribute('hasAdminPrivileges', $Access->hasAdminPrivileges());
        }
    }
    // End render

    //////////////////////////////////////////////////////////////////////////////////////
}
// End EbobAdminLoginView
