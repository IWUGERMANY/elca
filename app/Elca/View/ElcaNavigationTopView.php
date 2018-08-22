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

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Elca\Model\Navigation\ElcaNavigation;

/**
 * Builds the navigation
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaNavigationTopView extends HtmlView
{
    /**
     * left nav captions
     *
     * @translate value 'Projekte';
     * @translate value 'Bauteilvorlagen';
     * @translate value 'Baustoffe';
     */
    const NAV_PROJECTS  = 'Projekte';
    const NAV_ELEMENTS  = 'Bauteilvorlagen';
    const NAV_ASSETS    = 'Baustoffe';


    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_nav_top');
    }
    // End beforeRender


    /**
     * Builds the content navigation to the left
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        /**
         * main navigation items
         */
        $Navigation = ElcaNavigation::getInstance();
        $Navigation->add(t(self::NAV_PROJECTS), null, 'Elca\Controller\ProjectsCtrl');
        $Navigation->add(t(self::NAV_ELEMENTS), null, 'Elca\Controller\ElementsCtrl');
        $Navigation->add(t(self::NAV_ASSETS), null, 'Elca\Controller\ProcessesCtrl');

        $container = $this->getElementById('container', true);
        $Ul = $container->appendChild($this->getUl(['id' => 'navTop']));

        foreach($Navigation->getChildren() as $index => $NavItem)
        {
            $Li = $Ul->appendChild($this->getLi());

            if ($index === 0) {
                $Li->setAttribute('class', 'first');
            }

            $linkAttr = ['href' => FrontController::getInstance()->getUrlTo($NavItem->getCtrlName()),
                              'class' => 'no-xhr'];
            if($NavItem->isActive())
                $linkAttr['class'] .= ' active';

            $Li->appendChild($this->getA($linkAttr, ucfirst($NavItem->getCaption())));
        }

        $Ul = $container->appendChild($this->getUl(['id' => 'languageChooser']));

        $ElcaLocale = Environment::getInstance()->getContainer()->get('Elca\Service\ElcaLocale');

        foreach ($ElcaLocale->getSupportedLocales() as $locale)
        {
            $liAttr = [];
            if ($ElcaLocale->getLocale() == $locale)
                $liAttr['class'] = 'active';
            $Li = $Ul->appendChild($this->getLi($liAttr));
            $Li->appendChild($this->getA(['class' => 'no-xhr', 'href' => FrontController::getInstance()->getUrlTo(null, 'lang', ['lang' => $locale])], $locale));

            $Sep = $Ul->appendChild($this->getLi([], '|'));
        }
        $Sep->parentNode->removeChild($Sep);

        $this->getElementById('navTopWrapper')->appendChild($this->getDiv(['class' => 'clear']));
    }
    // End beforeRender
}
// End ElcaNavigationTopView
