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
use Elca\Model\Navigation\ElcaTabs;

/**
 * Builds a tab navigation, based on ElcaTabsView
 *
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaTabsView extends HtmlView
{
    /**
     * Tabs
     */
    private $Tabs;

    /**
     * Active tab ident
     */
    private $activeTabIdent;

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

        // set active tab
        $this->activeTabIdent = $this->get('activeTab');

        // set tabs
        $this->Tabs = ElcaTabs::getInstance();
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Called after rendering the template
     */
    protected function beforeRender()
    {
        if(!$this->Tabs->hasItems())
            return;

        $FrontController = FrontController::getInstance();

        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => $this->get('context','')]));

        $Ul = $Container->appendChild($this->getUl(['class' => 'elca-tabs clearfix']));
        foreach($this->Tabs->getItems() as $Item)
        {
            $ident = $Item->getIdent();

            $attributes = [];
            $attributes['id'] = 'tab-'. $ident;
            $attributes['class'] = 'elca-tab';

            // check for active tab
            if($ident == $this->activeTabIdent)
                $attributes['class'] .= ' active';

            $Li = $Ul->appendChild($this->getLi($attributes));

            $attributes = $Item->getArgs();
            $attributes['tab'] = $ident;

            $Li->appendChild($this->getA(['href' => $FrontController->getUrlTo($Item->getCtrlName(), $Item->getAction(), $attributes)], $Item->getCaption()));

            //$SeparatorLi = $Ul->appendChild($this->getLi(array('class' => 'elca-tab-separator')));
            //$SeparatorLi->appendChild($this->getSpan('|'));
        }

        /**
         * Remove last separator
         */
        //$SeparatorLi->parentNode->removeChild($SeparatorLi);

        $Container->appendChild($this->getDiv(['id' => 'tabContent']));
    }
    // End afterRender
}
// End ElcaTabsView
