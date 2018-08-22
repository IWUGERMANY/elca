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
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Model\Navigation\ElcaNavigation;
use Elca\Model\Navigation\ElcaNavItem;
use Elca\Security\ElcaAccess;

/**
 * Builds the elements navigation view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de> & Patrick Kocurek <patrick@kocurek.de>
 */
class ElcaElementsNavigationView extends HtmlView
{
    /**
     * navItems
     */
    protected static $navItems = [300, 400, 500];

    protected $context;

    protected $activeElementTypeId;

    /**
     * @var ElcaAccess
     */
    protected $access;

    protected $controller;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view.
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('elca_elements_navigation_left');

        $this->context          = $this->get('context');

        $this->activeElementTypeId = $this->get('activeElementTypeId');
        $this->controller          = $this->get('controller');

        $this->access       = ElcaAccess::getInstance();
        $hasAdminPrivileges = $this->access->hasAdminPrivileges();
        $userGroupIds       = $this->access->getUserGroupIds();

        // init database navigation
        foreach (self::$navItems as $ident) {
            $navIdent   = 'elementTypes'.$ident;
            $navigation = ElcaNavigation::getInstance($navIdent);
            $this->assign($navIdent, $navigation);

            $mainElement = ElcaElementType::findByIdent($ident);
            if (!$mainElement->isInitialized()) {
                throw new \RuntimeException('ERROR Main Element Type '.$ident.' is not initialized!');
            }

            $elementTypeSet = $this->getElementTypes($mainElement);

            foreach ($elementTypeSet as $type) {
                $item = $this->addNavItem($navigation, $type);

                $subElementTypeSet = $this->getElementTypes($type);

                $subElemCount = 0;
                foreach ($subElementTypeSet as $subType) {
                    $this->addNavItem($item, $subType);
                    $subElemCount += $subType->getElementCount();
                }
                if ($type->getElementCount() || $subElemCount) {
                    $item->setDataValue('subElementCount', $subElemCount);
                }

            }
        }
    }

    protected function getElementTypes($type): ElcaElementTypeSet
    {
        return ElcaElementTypeSet::findNavigationByParentType(
            $type,
            null,
            $this->access->hasAdminPrivileges(),
            $this->access->getUserGroupIds()
        );
    }

    protected function addNavItem(ElcaNavItem $item, ElcaElementType $elementType): ElcaNavItem
    {
        $caption = $this->getCaption($elementType);

        $item = $item->add($caption, null, $this->get('controller'), 'list', ['t' => $elementType->getNodeId()]);

        $this->setNavItemData($item, $elementType);

        if ((int)$elementType->getNodeId() === (int)$this->activeElementTypeId) {
            $item->setActive();
        }

        return $item;
    }

    protected function getCaption(ElcaElementType $elementType): string
    {
        return $elementType->getDinCode().' '.ucfirst(t($elementType->getName()));
    }

    protected function setNavItemData(ElcaNavItem $item, ElcaElementType $elementType): void
    {
        $item->setDataValue('elementCount', $elementType->getElementCount());
    }
}




