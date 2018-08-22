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

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Elca\Db\ElcaElementSet;
use Elca\Elca;
use Elca\Model\Navigation\ElcaNavigation;

/**
 * Builds the project content head with title, phases and variants
 *
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectNavigationView extends HtmlView
{
    /**
     * Called before render
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $FrontController = FrontController::getInstance();
        $activeCtrlName = $this->get('activeCtrlName', $FrontController->getActionControllerName());

        $Navigation = ElcaNavigation::getInstance('projectNav');
        if(!$Navigation->hasChildren())
        {
            $ProjectVariant = Elca::getInstance()->getProjectVariant();

            $Navigation->add('Projektdaten', null, 'Elca\Controller\ProjectDataCtrl', 'general');

            // show items only if not in beginning phase or has already elements
            if($ProjectVariant->getPhase()->getStep() > 0 ||
               ElcaElementSet::dbCount(['project_variant_id' => $ProjectVariant->getId()]) > 0)
            {
                $Navigation->add('Baukonstruktion', null, 'Elca\Controller\ProjectElementsCtrl');
                $Navigation->add('Auswertungen', null, 'Elca\Controller\ProjectReportsCtrl', 'summary');
            }
        }

        $Ul = $this->appendChild($this->getUl(['id' => 'projectNavigation', 'class' => 'clearfix']));
        foreach($Navigation->getChildren() as $Child)
        {
            $Li = $Ul->appendChild($this->getLi());

            $args = [];
            $args['href'] = $FrontController->getUrlTo($Child->getCtrlName(), $Child->getAction());
            $args['class'] = 'page';

            // mark active item
            if($activeCtrlName == $Child->getCtrlName())
                $args['class'] .= ' active';

            $Li->appendChild($this->getA($args, ucfirst(t($Child->getCaption()))));
        }
    }
    // End beforeRender
}
// End ElcaProjectNavigationView
