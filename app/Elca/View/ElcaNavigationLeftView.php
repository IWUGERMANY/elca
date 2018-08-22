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
use DOMElement;
use DOMNode;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaNavItem;

/**
 * Builds the navigation
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaNavigationLeftView extends HtmlView
{
    /**
     * FrontController
     */
    private $frontController;

    /**
     * @var ElcaNavigation
     */
    private $navigation;

    /**
     * Inits the view
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init instances
         */
        $this->frontController = FrontController::getInstance();
        $this->navigation      = $this->has('navigation')? $this->get('navigation') : ElcaNavigation::getInstance();

        /**
         * Check if a single navigation item is requested to build
         */
        if (!$this->has('activeItemId')) {
            $this->setTplName('elca_nav_left', 'elca');
        }
    }
    // End beforeRender


    /**
     * Builds the content navigation to the left
     *
     * @return void -
     */
    protected function beforeRender()
    {
        if ($this->has('activeItemId')) {
            if ($item = $this->navigation->getItemById($this->get('activeItemId'))) {
                switch ($item->getLevel()) {
                    case 2:  $this->appendLevel2Item($this, $item); break;
                    case 1:  $this->appendLevel1Item($this, $item); break;
                    default: $this->appendChild($this->getText()); break;
                }
            }
            else {
                $this->appendChild($this->getText());
            }
        }
        else {
            $this->buildLevel1($this->navigation);
        }
    }
    // End beforeRender


    /**
     * Builds the content navigation to the left
     *
     * @param ElcaNavItem $activeNavRootItem
     * @return void -
     */
    protected function buildLevel1(ElcaNavItem $activeNavRootItem)
    {
        $container = $this->getElementById('nav-left-wrapper');

        /**
         * Append level 1 items
         */
        $ul = $container->appendChild($this->getUl(['class' => 'nav-level1']));

        foreach ($activeNavRootItem->getChildren() as $navItemLevel1) {
            $this->appendLevel1Item($ul, $navItemLevel1);
        }
    }
    // End buildLevel2


    /**
     * Append level 2 items
     *
     * @param DOMElement|DOMNode                 $Ul
     * @param \Elca\Model\Navigation\ElcaNavItem $navItemLevel1
     */
    private function appendLevel1Item(DOMNode $Ul, ElcaNavItem $navItemLevel1)
    {
        /**
         * navigation level 1 captions / toggle
         */
        $liAttr = [];
        $liAttr['id'] = 'nav_'.$navItemLevel1->getId();
        $liAttr['class'] = 'navigation';

        if($navItemLevel1->isActive())
            $liAttr['class'] .= ' open active';

        $liAttr = $this->addDataAttributes($navItemLevel1, $liAttr);

        $li = $Ul->appendChild($this->getLi($liAttr));
        $h4 = $li->appendChild($this->getH4('', ['class' => 'nav-toggle']));

        $navlevel1Caption = $this->getNavLevel1Caption($navItemLevel1);

        if ($ctrl = $navItemLevel1->getCtrlName()) {
            $linkAttr = ['class' => 'page',
                              'title' => $navlevel1Caption,
                              'href' => FrontController::getInstance()->getUrlTo($navItemLevel1->getCtrlName(), $navItemLevel1->getAction(), $navItemLevel1->getArgs())];

            $h4->appendChild($this->getA($linkAttr, $navlevel1Caption));
        }
        else {
            $h4->appendChild($this->getSpan($navlevel1Caption, ['title' => $navlevel1Caption]));
        }

        /**
         * navigation level 2 captions
         */
        $navItemsLevel2 = $navItemLevel1->getChildren();
        if (!\count($navItemsLevel2)) {
            return;
        }

        $attr = [];
        $attr['class'] = 'nav-toggle-item nav-level2';
        $level1Ul = $li->appendChild($this->getUl($attr));

        foreach ($navItemLevel1->getChildren() as $navItemLevel2) {
            $this->appendLevel2Item($level1Ul, $navItemLevel2);
        }
    }
    // End appendLevel1Item


    /**
     * Append level 2 items
     *
     * @param DOMElement|DOMNode $Ul
     * @param ElcaNavItem        $navItemLevel2
     */
    private function appendLevel2Item(DOMNode $Ul, ElcaNavItem $navItemLevel2)
    {
        $liAttr = ['id' => 'nav_'.$navItemLevel2->getId()];
        $liAttr['class'] = 'navigation';

        if ($navItemLevel2->isActive()) {
            $liAttr['class'] .= ' active';
        }

        $liAttr = $this->addDataAttributes($navItemLevel2, $liAttr);

        $li = $Ul->appendChild($this->getLi($liAttr));
        $linkAttr = [
            'class' => 'page',
            'href' => FrontController::getInstance()->getUrlTo($navItemLevel2->getCtrlName(), $navItemLevel2->getAction(), $navItemLevel2->getArgs())
        ];

        $li->appendChild($this->getA($linkAttr, $this->getNavLevel2Caption($navItemLevel2)));
    }

    protected function getNavLevel1Caption(ElcaNavItem $navItem): string
    {
        return ucfirst($navItem->getCaption());
    }

    protected function getNavLevel2Caption(ElcaNavItem $navItem): string
    {
        return $navItem->getCaption();
    }

    protected function addDataAttributes(ElcaNavItem $navItem, array $attributes)
    {
        if ($navItem->hasData()) {
            foreach ($navItem->getData() as $key => $value) {
                $attributes['data-'. \strtr($key, '_', '-')] = $value;
            }
        }

        return $attributes;
    }
}
