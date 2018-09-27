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
use Beibob\Blibs\StringFactory;
use Beibob\Blibs\Url;
use Elca\Db\ElcaCompositeElementSet;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessDbSet;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\ElementAssistant;
use Elca\Service\Assistant\ElementAssistantRegistry;

/**
 * Builds a element sheet
 *
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de>, Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementSheetView extends ElcaSheetView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECT  = 'select';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @var ElcaElement
     */
    protected $element;

    /**
     * @var ElementAssistant
     */
    protected $assistant;

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
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

        // Element
        $this->element = ElcaElement::findById($this->get('itemId'));

        if ($this->get('hasAssistants')) {
            /** @var ElementAssistantRegistry $registry */
            $registry = Environment::getInstance()->getContainer()->get(ElementAssistantRegistry::class);

            /** @var ElementAssistant $assistant */
            if ($assistant = $registry->hasAssistantForElement($this->element)) {
                $this->assistant = $registry->getAssistantForElement($this->element);
            }
        }

    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-element-sheet');

        if($this->element->isPublic())
            $this->addClass($this->getHeadline(), 'ref-element');

        if ($this->assistant instanceof ElementAssistant) {
            $this->addClass($Container, 'element-assistant');
        }

        if($this->get('buildMode', self::BUILDMODE_DEFAULT) == self::BUILDMODE_DEFAULT)
        {
            if($this->get('canEdit', false))
                $this->addFunction('edit', '/elements/$$itemId$$/', t('Bearbeiten'), 'default page');
            else
                $this->addFunction('view', '/elements/$$itemId$$/', t('Ansehen'), 'default page');

            if($this->element->hasCompositeElement())
            {
                if($this->addElementSelectors())
                    $this->addClass($Container, 'composite-element');
            }

            if(!$this->element->isComposite()) // $this->get('canEdit', false) &&
            {
                if (!$this->assistant || !$this->assistant->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_ASSIGN_ELEMENT)) {
                    $Url = Url::factory(
                        '/elements/selectElement/',
                        [
                            'relId' => $this->element->getId(),
                            't'     => $this->element->getElementTypeNode()->getParent()->getNodeId(),
                            'b'     => ElcaElementSelectorView::BUILDMODE_COMPOSITES
                        ]
                    );

                    $this->addFunction('assign', (string)$Url, t('Mit Bauteil verknüpfen'));
                }
            }

            if (!$this->assistant || !$this->assistant->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_COPY, $this->element)) {
                $this->addFunction('copy', '/elements/copy/?id=$$itemId$$', t('Kopieren'));
            }

            if(!$this->element->isComposite() && $this->get('canEdit', false)) {

                if (!$this->assistant || !$this->assistant->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_MOVE, $this->element)) {
                    $this->addFunction('move-element', '/elements/moveElement/?id=$$itemId$$', t('Verschieben'));
                }
            }

            if(!$this->element->isPublic())
                $this->addFunction('export', '/exports/element/?id=$$itemId$$', t('Exportieren'), 'no-xhr');

            if ($this->get('canEdit', false)) {
                if (!$this->assistant || !$this->assistant->isLockedFunction(ElementAssistant::FUNCTION_ELEMENT_DELETE, $this->element)) {
                    $this->addFunction('delete', '/elements/delete/?id=$$itemId$$', t('Löschen'));
                }
            }
        }
        else
            $this->addFunction('select', '/project-elements/elementCopy/?id=$$itemId$$', t('Auswählen'), 'default');

        /**
         * Append individual content
         */
        $this->addDescription($this->element->getDescription());

        if ($processDbIds = $this->element->getProcessDbIds(['version' => 'ASC'])) {
            $processDbs = ElcaProcessDbSet::findByIds($processDbIds)->getArrayBy('name');
        }
        else {
            $processDbs = [t('keine')];
            $this->addClass($Container, 'no-process-db');
        }
        $this->addInfo(implode(', ', $processDbs), t('Baustoffdatenbanken'));

        if ($this->element->isComposite()) {
            $processConfigSet = ElcaProcessConfigSet::findByCompositeElementId($this->get('itemId'), ['name' => 'ASC']);
        }
        else {
            $processConfigSet = ElcaProcessConfigSet::findByElementId($this->get('itemId'), ['name' => 'ASC']);
        }

        $this->addInfo(
            $processConfigSet->count()
                ? implode(', ', $processConfigSet->map(
                                function(ElcaProcessConfig $processConfig) {
                                    return \processConfigName($processConfig->getId());
                                }
                                )
                )
                : t('keine'),
            t('Baustoffe'),
            null,
            true
        );

        if ($this->element->getOwnerId()) {
            $this->addInfo($this->element->getOwner()->getIdentifier(), t('Erstellt von'), null, true);
        }

        $this->addElementImage();
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     *
     */
    protected function addElementImage()
    {
        if (!$this->element->getElementTypeNode()->prefHasElementImage)
            return;

        $elementImageUrl = FrontController::getInstance()->getUrlTo('Elca\Controller\ElementImageCtrl', null, array('elementId' => $this->element->getId(), 'legend' => '0'));

        $SvgDiv = $this->getDiv(['class' => 'element-image',
                                 'data-element-id' => $this->element->getId(),
                                 'data-url' => $elementImageUrl
        ]);


        $this->getContainer()->insertBefore($SvgDiv, $this->getContainer()->firstChild);
        $this->getContainer()->setAttribute('class', $this->getContainer()->getAttribute('class') . ' has-element-image');
    }
    // End addElementImage

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends element selectors to the subheadline
     * Returns true, if accessible composite elements were added as selectors
     *
     * @return bool
     */
    protected function addElementSelectors()
    {
        $Assignments = ElcaCompositeElementSet::find(['element_id' => $this->element->getId()]);
        if(!$Assignments->count())
            return false;

        $compositeElementIds = array_unique($Assignments->getArrayBy('compositeElementId'));

        $Access = ElcaAccess::getInstance();
        if(!$Access->hasAdminPrivileges())
        {
            foreach($compositeElementIds as $index => $compositeElementId)
                if(!$Access->canAccessElement($Assignments[$index]->getCompositeElement()))
                    unset($compositeElementIds[$index]);
        }

        if(!count($compositeElementIds))
            return false;

        $SubHeadline = $this->getSubHeadline();
        $SubHeadline->appendChild($this->getText(t('Verknüpft mit Bauteil') . ' '));

        $context = $this->get('context');
        foreach($compositeElementIds as $index => $compositeElementId)
        {
            $attr = ['class' => 'element-selector page', 'href' => '/'.$context.'/'.$compositeElementId.'/', 'title' => $Assignments[$index]->getCompositeElement()->getName()];
            $SubHeadline->appendChild($this->getA($attr, StringFactory::stringMidCut($attr['title'], 40).' '));
        }

        return true;
    }
    // End addElementSelectors
}
// End ElcaSheetView
