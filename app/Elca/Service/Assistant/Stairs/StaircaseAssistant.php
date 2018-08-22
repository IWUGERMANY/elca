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

namespace Elca\Service\Assistant\Stairs;

use Beibob\Blibs\Log;
use Elca\Controller\Assistant\StaircaseCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Assistant\Material\Material;
use Elca\Model\Assistant\Stairs\Assembler;
use Elca\Model\Assistant\Stairs\SolidStaircase;
use Elca\Model\Assistant\Stairs\Staircase;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Element\SearchAndReplaceObserver;
use Elca\Model\Import\ImportObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Elca\Service\Assistant\AbstractAssistant;
use Elca\Service\Assistant\ElementAssistant;
use Elca\Service\Element\ElementService;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\Assistant\StaircaseElementImageView;
use Elca\View\ElementImageView;

/**
 * StaircaseAssistant
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class StaircaseAssistant extends AbstractAssistant implements ElementObserver, ElcaProjectVariantObserver, ImportObserver, SearchAndReplaceObserver
{
    const IDENT = 'staircase-assistant';

    const ELEMENT_IMAGE_VIEW = StaircaseElementImageView::class;

    /**
     * @var array
     */
    private $elements;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * @var ElcaMessages
     */
    private $messages;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     * @param ElcaMessages     $messages
     * @param ElementService   $elementService
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor, ElcaMessages $messages, ElementService $elementService)
    {
        $this->setConfiguration(
            new Configuration(
                [
                    350,
                    351,
                    352,
                ],
                self::IDENT,
                'Treppenassistent',
                StaircaseCtrl::class,
                'default',
                [
                    ElementAssistant::PROPERTY_NAME,
                    ElementAssistant::PROPERTY_REF_UNIT,
                    ElementAssistant::PROPERTY_QUANTITY,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_SIZE,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_AREA_RATIO,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_LENGTH,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_WIDTH,
                    ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID,
                    ElementAssistant::PROPERTY_COMPONENT_QUANTITY,
                    ElementAssistant::PROPERTY_COMPONENT_CONVERSION_ID,
                    ElementAssistant::PROPERTY_ELEMENTS_ELEMENT_ID,
                ],
                [
                    ElementAssistant::FUNCTION_COMPONENT_DELETE,
                    ElementAssistant::FUNCTION_COMPONENT_DELETE_COMPONENT,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER,
                    ElementAssistant::FUNCTION_ELEMENT_MOVE,
                    ElementAssistant::FUNCTION_ELEMENT_DELETE,
                    ElementAssistant::FUNCTION_ELEMENT_ADD_ELEMENT,
                    ElementAssistant::FUNCTION_ELEMENT_DELETE_ELEMENT,
                    ElementAssistant::FUNCTION_ELEMENT_ASSIGN_TO_ELEMENT,
                    ElementAssistant::FUNCTION_ELEMENT_UNASSIGN_ELEMENT,
                ]
            )
        );
        $this->lcaProcessor = $lcaProcessor;
        $this->messages     = $messages;
        $this->elementService = $elementService;
    }

    /**
     * @param ElcaElement $element
     * @return bool
     */
    public function provideForElement(ElcaElement $element)
    {
        if ($element->isTemplate()) {
            return false;
        }

        return parent::provideForElement($element); // TODO: Change the autogenerated stub
    }

    /**
     * @param ElcaElementType $elementType
     * @param string          $context
     * @return bool
     */
    public function provideForElementType(ElcaElementType $elementType, $context)
    {
        if ($context !== ProjectElementsCtrl::CONTEXT) {
            return false;
        }

        return parent::provideForElementType($elementType, $context);
    }


    /**
     * @param int $elementId
     * @return Staircase
     */
    public function getStaircaseFromElement($elementId = null)
    {
        $staircase = null;

        $element = $this->getStaircaseElement($elementId);

        if ($element->isInitialized()) {
            $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), self::IDENT);

            if ($textValue = $attr->getTextValue()) {
                $staircase = unserialize(base64_decode($textValue));
            }
        }

        if ($staircase === null || !$staircase instanceof Staircase) {

            $initName  = $elementId ? ElcaElement::findById($elementId)->getName() : 'Neue Treppe';
            $staircase = SolidStaircase::getDefault($initName);
        }

        return $staircase;
    }

    public function getPlatformConstructionElementIdFromElement($elementId, $force = false)
    {
        $element = $this->getElement(
            $this->getStaircaseElement($elementId),
            Assembler::IDENT_PLATFORM_CONSTRUCTION,
            $force
        );

        if (null === $element) {
            return null;
        }

        return $element->getId();
    }

    public function getPlatformCoverElementIdFromElement($elementId, $force = false)
    {
        $element = $this->getElement(
            $this->getStaircaseElement($elementId),
            Assembler::IDENT_PLATFORM_COVER,
            $force
        );

        if (null === $element) {
            return null;
        }

        return $element->getId();
    }

    /**
     * @param int       $staircaseElementId
     * @param Staircase $staircase
     * @throws \Exception
     */
    public function saveStaircaseForElement($staircaseElementId, Staircase $staircase)
    {
        $element = $this->getStaircaseElement($staircaseElementId);
        if (!$element->isInitialized()) {
            throw new \Exception('Trying to save staircase data for an uninitialized element');
        }

        $serializedStaircase = base64_encode(serialize($staircase));

        // save attribute with window data
        $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), self::IDENT);

        if ($attr->isInitialized()) {
            $attr->setTextValue($serializedStaircase);
            $attr->update();
        } else {
            // create attribute
            ElcaElementAttribute::create(
                $staircaseElementId,
                self::IDENT,
                self::IDENT,
                null,
                $serializedStaircase
            );
        }
    }

    /**
     * @param int $elementId
     * @return ElementImageView
     */
    public function getElementImageView($elementId)
    {
        $viewName = self::ELEMENT_IMAGE_VIEW;

        $view = new $viewName();
        $view->assign('staircase', $this->getStaircaseFromElement($elementId));

        return $view;
    }


    /**
     * @param             $property
     * @param ElcaElement $element
     * @return bool
     */
    public function isLockedProperty($property, ElcaElement $element = null)
    {
        if (!in_array($property, $this->getConfiguration()->getLockedProperties())) {
            return false;
        }

        switch ($property) {
            case ElementAssistant::PROPERTY_QUANTITY:
                if ($element !== null && $element->isComposite()) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @param                           $name
     * @param ElcaElement               $element
     * @param ElcaElementComponent|null $component
     * @return bool
     */
    public function isLockedFunction($name, ElcaElement $element = null, ElcaElementComponent $component = null)
    {
        if (!in_array($name, $this->getConfiguration()->getLockedFunctions())) {
            return false;
        }

        switch ($name) {
            case ElementAssistant::FUNCTION_ELEMENT_DELETE:
                if ($element !== null && $element->isComposite()) {
                    return false;
                }
                break;
            case ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER:
            case ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT:
                if ($element !== null) {
                    $attr = $element->getAttribute(self::IDENT);

                    return $attr->isInitialized() && !in_array(
                            $attr->getTextValue(),
                            [Assembler::IDENT_PLATFORM_CONSTRUCTION, Assembler::IDENT_PLATFORM_COVER]
                        );
                }
                break;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getIdent()
    {
        return self::IDENT;
    }

    /**
     * @param $compositeElementId
     */
    public function computeLcaForStaircaseElement($compositeElementId)
    {
        $element = ElcaElement::findById($compositeElementId);

        if (!$element->getProjectVariantId()) {
            return;
        }

        $this->lcaProcessor
            ->computeElement($element)
            ->updateCache($element->getProjectVariant()->getProjectId());
    }

    /**
     * @param $elementId
     * @return ElcaElement
     */
    public function getStaircaseElement($elementId)
    {
        $element = ElcaElement::findById($elementId);

        if (!$element->isInitialized()) {
            return $element;
        }

        if (!$element->isComposite()) {

            if (!$element->hasCompositeElement()) {
                $attr = $this->getElementAttribute($elementId);
                if ($attr->isInitialized()) {
                    $attr->delete();
                }

                Log::getInstance()->fatal(
                    'Expected composite element, got element `'.$elementId.'\' which is not assigned to any composite element'
                );
                throw new \Exception(
                    'Expected composite element, got element `'.$elementId.'\' which is not assigned to any composite element'
                );
            }

            return $element->getCompositeElement();
        }

        return $element;
    }

    /**
     * @param ElcaElement $elementToDelete
     * @param bool        $deleteRecursive
     * @return bool
     */
    public function onElementDelete(ElcaElement $elementToDelete)
    {
        if (!$this->isStaircaseElement($elementToDelete->getId()) || $elementToDelete->isComposite()) {
            return true;
        }

        $this->messages->add(
            'Dieses Element kann nicht gelöscht werden, da es über den Treppenassistenten verwaltet wird',
            ElcaMessages::TYPE_ERROR
        );

        return false;
    }

    /**
     * @param int $elementId
     * @return void
     */
    public function afterDeletion($elementId, $projectVariantId = null)
    {
    }

    /**
     * @param ElcaElement $createdElement
     * @return mixed
     */
    public function onElementCreate(ElcaElement $createdElement)
    {
    }

    /**
     * @param ElcaElement $updatedElement
     * @return mixed
     */
    public function onElementUpdate(ElcaElement $updatedElement)
    {
        if (!$this->isStaircaseElement($updatedElement->getId())) {
            return;
        }

        if (!$updatedElement->isComposite()) {
            return;
        }

        $assembler = new Assembler(
            $this->elementService, $this->getStaircaseFromElement($updatedElement->getId()), $updatedElement->getProjectVariantId()
        );

        $assembler->update(
            $updatedElement,
            $this->getPlatformConstructionElementIdFromElement($updatedElement->getId()),
            $this->getPlatformCoverElementIdFromElement($updatedElement->getId())
        );
    }

    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
    public function onElementCopy(ElcaElement $element, ElcaElement $copiedElement)
    {
        if (!$this->isStaircaseElement($element->getId())) {
            return;
        }

        /**
         * Update staircase
         */
        if ($element->isComposite()) {
            $staircase = $this->getStaircaseFromElement($copiedElement->getId());
            $staircase->setName($copiedElement->getName());

            $assember = new Assembler($this->elementService, $staircase, $copiedElement->getProjectVariantId());
            $assember->update(
                $copiedElement,
                $this->getPlatformConstructionElementIdFromElement($copiedElement->getId()),
                $this->getPlatformCoverElementIdFromElement($copiedElement->getId())
            );

            $this->saveStaircaseForElement($copiedElement->getId(), $staircase);
            $this->computeLcaForStaircaseElement($copiedElement->getId());
        } else {
            /**
             * Remove attribute from copied element
             */
            $attr = ElcaElementAttribute::findByElementIdAndIdent($copiedElement->getId(), self::IDENT);
            if ($attr->isInitialized()) {
                $attr->delete();
            }
        }
    }

    /**
     * @param ElcaProjectVariant $oldVariant
     * @param ElcaProjectVariant $newVariant
     */
    public function onProjectVariantCopy(ElcaProjectVariant $oldVariant, ElcaProjectVariant $newVariant)
    {
    }

    /**
     * @param ElcaProject $project
     * @return void
     */
    public function onProjectImport(ElcaProject $project)
    {
        // TODO: Implement onProjectImport() method.
    }

    /**
     * @param ElcaElement $element
     * @return void
     */
    public function onElementImport(ElcaElement $element)
    {
        // TODO: Implement onElementImport() method.
    }

    /**
     * @param ElcaElementComponent $elementComponent
     * @param                      $searchProcessConfigId
     * @param                      $replaceProcessConfigId
     * @return mixed
     */
    public function onElementComponenentSearchAndReplace(
        ElcaElementComponent $elementComponent,
        $searchProcessConfigId,
        $replaceProcessConfigId
    ) {
        if (!$this->isStaircaseElement($elementComponent->getElementId())) {
            return;
        }

        $staircase = $this->getStaircaseFromElement($elementComponent->getElementId());

        $foundAndReplaced = false;

        $construction = $staircase->getConstruction();
        foreach ($construction->getMaterials() as $index => $material) {
            if ($material->getMaterialId() === $searchProcessConfigId) {
                $construction->replaceMaterialOn(
                    $index,
                    new Material(
                        $replaceProcessConfigId,
                        $material->getShare()
                    )
                );
                $foundAndReplaced = true;
            }
        }

        $step  = $staircase->getSteps()->getStep();
        $cover = $step->getCover();
        if ($cover->getMaterial()->getMaterialId() === $searchProcessConfigId) {
            $cover->replaceMaterial(
                new Material(
                    $replaceProcessConfigId,
                    $cover->getMaterial()->getShare()
                )
            );
            $foundAndReplaced = true;
        }

        $riser = $step->getRiser();
        if ($riser && $riser->getMaterial()->getMaterialId() === $searchProcessConfigId) {
            $riser->replaceMaterial(
                new Material(
                    $replaceProcessConfigId,
                    $riser->getMaterial()->getShare()
                )
            );
            $foundAndReplaced = true;
        }

        if ($foundAndReplaced) {
            $this->saveStaircaseForElement($elementComponent->getElementId(), $staircase);
        }
    }

    /**
     * @param $elementId
     * @return ElcaElementAttribute
     */
    private function getElementAttribute($elementId)
    {
        return ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT);
    }

    /**
     * @param $elementId
     * @return bool
     */
    private function isStaircaseElement($elementId)
    {
        return $this->getElementAttribute($elementId)->isInitialized();
    }

    /**
     * @param ElcaElement $staircaseElement
     * @param string      $ident
     * @return ElcaElement|null
     */
    private function getElement(ElcaElement $staircaseElement, $ident, $force = false)
    {
        if ($force || null === $this->elements || !isset($this->elements[$staircaseElement->getId()])) {

            $compositeElementSet = $staircaseElement->getCompositeElements(null, $force);

            $this->elements[$staircaseElement->getId()] = [];
            foreach ($compositeElementSet as $assignment) {
                $attr = $assignment->getElement()->getAttribute(StaircaseAssistant::IDENT);
                if ($attr->isInitialized()) {
                    $this->elements[$staircaseElement->getId()][$attr->getTextValue()] = $assignment->getElement();
                }
            }
        }

        if (isset($this->elements[$staircaseElement->getId()][$ident]) &&
            $this->elements[$staircaseElement->getId()][$ident]->isInitialized()
        ) {
            return $this->elements[$staircaseElement->getId()][$ident];
        }

        return null;
    }
}
