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
use Beibob\Blibs\UserStore;
use Elca\Elca;
use Elca\Security\ElcaAccess;

/**
 * Create the link to the manual on the info page
 *
 * @package elca
 * @author  Michael Boeneke <boeneke@online-now.de>
 * @author  Tobias Lode <tobias@beibob.de>
 * 
 */
class ElcaInfoHandbookView extends HtmlView
{
    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_info_handbook');
    }
  

    protected function beforeRender()
    {
        $Container = $this->getElementById('downloadhandbook');
		
		// ON! Kurzversion - Handbuch öffentlich (HandbookCtrl)
		$Container->appendChild($this->getLi([], $this->getA(['href' => '/handbook/', 'class' => 'iconsheet no-xhr', 'target' => '_blank'], ucfirst(t('Handbuch')))));

        /* Version über exports / login
		$User = UserStore::getInstance()->getUser();
        if ($User->isInitialized()) {

            // Handbook
            $Container->appendChild($this->getLi([], $this->getA(['href' => '/exports/handbook/', 'class' => 'iconsheet no-xhr', 'target' => '_blank'], ucfirst(t('Handbuch')))));

        } else {

            // Anmelden
            $Container->appendChild($this->getLi([], $this->getA(['href' => '/login/', 'class' => 'no-xhr'], ucfirst(t('Anmelden')))));
		}
		*/
    }
	// End beforeRender
}
// End ElcaInfoHandbookView
