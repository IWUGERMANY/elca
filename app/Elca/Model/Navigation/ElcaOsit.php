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
namespace Elca\Model\Navigation;

use Beibob\Blibs\Url;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessConfig;

/**
 * Manages osit items
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaOsit
{
    /**
     * Singleton instance
     */
    private static $Instance;

    /**
     * Osit stack
     */
    private $stack = [];

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns and inits the singelton
     *
     * @return ElcaOsit
     */
    public static function getInstance()
    {
        if(self::$Instance instanceOf ElcaOsit)
            return self::$Instance;

        return self::$Instance = new ElcaOsit();
    }
    // End getInstance

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the processConfig list scenario
     *
     * @param $categoryId
     */
    public function setProcessConfigListScenario($categoryId = null, $isSearch = false)
    {
        if ($isSearch) {
            $this->setProcessConfigSearchScenario();
        }

        if (null !== $categoryId) {
            $categories = ElcaProcessCategorySet::findParentsById($categoryId);
            $url = '/processes/list/?c='.$categoryId.'&back' . ($isSearch ? '=search' : '');
            $this->add(new ElcaOsitItem(t($categories[1]->getName()), $url, t($categories[0]->getName())));
        }
        else {
            $this->setProcessConfigSearchScenario();
        }
    }

    /**
     * Sets the processConfig list scenario
     *
     * @param null $backUrl
     */
    public function setProcessSanityScenario($backUrl = null)
    {
        $this->add(new ElcaOsitItem(t('Probleme'), $backUrl, t('Pflege')));
    }

    /**
     * Sets the processConfig list scenario
     *
     * @param null $backUrl
     */
    public function setProcessConfigSearchScenario()
    {
        $this->add(new ElcaOsitItem(t('Suche'), '/processes/?back', t('Baustoffe')));
    }

    /**
     * Sets the processConfig  scenario
     */
    public function setProcessConfigScenario($categoryId, $processConfigId = null, $isSearch = false)
    {
        $this->setProcessConfigListScenario($categoryId, $isSearch);

        if($processConfigId) {
            $this->add(new ElcaOsitItem(ElcaProcessConfig::findById($processConfigId)->getName(), null, t('Baustoff')));
        }
        else {
            $this->add(new ElcaOsitItem(t('Neuen Baustoff anlegen')));
        }
    }

    /**
     * Sets the processConfig  scenario
     */
    public function setProcessConfigFromSearchScenario($categoryId, $processConfigId)
    {
        $this->setProcessConfigSearchScenario();
        $this->setProcessConfigScenario($categoryId, $processConfigId, true);
    }

    /**
     * Sets the processConfig  scenario
     */
    public function setProcessConfigFromSanitiesScenario($processConfigId, $backUrl)
    {
        $this->setProcessSanityScenario($backUrl);
        $this->add(new ElcaOsitItem(ElcaProcessConfig::findById($processConfigId)->getName(), null, t('Baustoff')));
    }

    /**
     * Sets the scenario for lists
     *
     * @param  int $elementTypeId
     * @return ElcaOsitItem
     */
    public function setListScenario($elementTypeId, $inProjectContext = false, $clearList = true)
    {
        /**
         * Clear the list in case there is no active projectElement
         */
        if($clearList)
            $this->clear();

        if($inProjectContext)
            $url = '/project-elements';
        else
            $url = '/elements';

        $url .= '/list/?t='.$elementTypeId.'&back';

        $ParentTypes = ElcaElementTypeSet::findParentsById($elementTypeId);
        $count = $ParentTypes->count();
        $this->add(new ElcaOsitItem($ParentTypes[$count - 1]->getDinCode() . ' ' . t($ParentTypes[$count - 1]->getName()), $url, t($ParentTypes[$count - 2]->getName()), null));
    }
    // End setListScenario

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the scenario for elements
     *
     * @param  int $elementTypeId
     * @return ElcaOsitItem
     */
    public function setElementScenario($elementTypeId, $elementId = null, $compositeElementId = null, $activeTabIdent = null)
    {
        $this->setListScenario($elementTypeId);

        if($elementId)
        {
            if($compositeElementId)
            {
                $CompositeElement = ElcaElement::findById($compositeElementId);

                $url = '/elements/'.$compositeElementId.'/';
                if ($activeTabIdent)
                    $url .= '?tab='.$activeTabIdent;

                $this->add(new ElcaOsitItem($CompositeElement->getName(), $url, t('Bauteilvorlage'), 'library'));
            }

            $Element = ElcaElement::findById($elementId);
            $this->add(new ElcaOsitItem($Element->getName() .' ['.$elementId.']', null, $Element->isComposite()? t('Bauteilvorlage') : t('Bauteilkomponentenvorlage'), 'library'));
        }
        else
            $this->add(new ElcaOsitItem(t('Neue Bauteilvorlage anlegen'), null, null, 'library'));
    }
    // End setElementScenario

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the scenario for project elements
     *
     * @param  int $elementTypeId
     * @return ElcaOsitItem
     */
    public function setProjectElementScenario($elementTypeId, $elementId = null, $compositeElementId = null, $activeTabIdent = null)
    {
        /**
         * Always a fresh start
         */
        $this->setListScenario($elementTypeId, true, true);

        if($elementId)
        {
            if($compositeElementId)
            {
                $CompositeElement = ElcaElement::findById($compositeElementId);
                $url = '/project-elements/'.$compositeElementId.'/';
                if ($activeTabIdent)
                    $url .= '?tab='.$activeTabIdent;

                $this->add(new ElcaOsitItem($CompositeElement->getName(), $url, t('Bauteil')));
            }

            $Element = ElcaElement::findById($elementId);
            $this->add(new ElcaOsitItem($Element->getName().' ['.$elementId.']', '/project-elements/'.$elementId.'/', $Element->isComposite()? t('Bauteil') : t('Bauteilkomponente')));
        }
        else
            $this->add(new ElcaOsitItem(t('Neues Bauteil erstellen'), '/project-elements/create/?t='.$elementTypeId));
    }
    // End setProjectElementScenario

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the scenario for the selector
     *
     * @param  int $elementTypeId
     * @return ElcaOsitItem
     */
    public function setSelectorScenario($elementTypeId)
    {
        $this->setListScenario($elementTypeId, true, false);

        $Url = Url::factory('/project-elements/createFromTemplate/', ['t' => $elementTypeId]);

        $ElementType = ElcaElementType::findByNodeId($elementTypeId);

        if($ElementType->isCompositeLevel())
        {
            $this->add(new ElcaOsitItem(t('Neues Bauteil von Vorlage erstellen'), (string)$Url, null, null, true));
            $this->add(new ElcaOsitItem(t('Bauteilvorlage wählen'), null, null, 'library'));
        }
        else
        {
            $this->add(new ElcaOsitItem(t('Neue Bauteilkomponente von Vorlage erstellen'), (string)$Url, null, null, true));
            $this->add(new ElcaOsitItem(t('Bauteilkomponentenvorlage wählen'), null, null, 'library'));
        }
    }
    // End setSelectorScenario

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds an item to the stack
     *
     * @param  ElcaOsitItem $Item
     * @return ElcaOsitItem
     */
    public function add(ElcaOsitItem $Item)
    {
        return $this->stack[$Item->getUrl()] = $Item;
    }
    // End add

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the active item
     *
     * @param  -
     * @return ElcaOsitItem
     */
    public function getActiveItem()
    {
        return end($this->stack);
    }
    // End getActiveItem

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns all osit items
     *
     * @param  -
     * @return array
     */
    public function getItems()
    {
        return $this->stack;
    }
    // End getItems

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns true if items are in stack
     *
     * @param  -
     * @return array
     */
    public function hasItems()
    {
        return (bool)count($this->stack);
    }
    // End hasItems

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Removes all items from the stack
     *
     * @param  -
     * @return -
     */
    public function clear()
    {
        $this->stack = [];
    }
    // End clear

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Debugging
     *
     * @param  -
     * @return -
     */
    public function show()
    {
        show(join(' > ', array_keys($this->stack)));
    }
    // End clear

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaOsit
