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
namespace Soda4Lca\View;

use Beibob\Blibs\HtmlView;
use Elca\Security\ElcaAccess;
use Soda4Lca\Db\Soda4LcaReportSet;

/**
 *
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soda4LcaDatabasesView extends HtmlView
{
    /**
     * Constructs the Document
     *
     * @param
     * @return -
     */
    public function __construct()
    {
        parent::__construct('soda4lca_databases', 'Soda4Lca');
    }
    // End __construct


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $conditions = [];

        if(!ElcaAccess::getInstance()->hasAdminPrivileges())
            $conditions['access_group_id'] = ElcaAccess::getInstance()->getUserGroupId();

        $Databases = Soda4LcaReportSet::findImportedDatabases();

        if(!count($Databases))
            return;

        $NoDbsElt = $this->getElementById('noDatabases');
        $NoDbsElt->parentNode->removeChild($NoDbsElt);

        $Ul = $this->getElementById('soda4lcaDatabases')->appendChild($this->getUl());
        foreach($Databases as $Database)
        {
            $Li = $Ul->appendChild($this->getLi(['id' => 'db-' . $Database->import_id]));

            $View = new Soda4LcaDatabaseSheetView();
            $View->assign('itemId', $Database->import_id);
            $View->assign('headline', $Database->name);
            $View->assign('Db', $Database);
            $View->process();
            $View->appendTo($Li);
        }
    }
    // End beforeRender

}
// End Soda4LcaDatabasesView
