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

namespace Elca\Service\Assistant\Window;

use Beibob\Blibs\Log;
use Elca\Controller\Assistant\DormerCtrl;
use Elca\Controller\Assistant\WindowCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementAttributeSet;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Assistant\Window\DormerAssembler;
use Elca\Model\Assistant\Window\Window;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Import\ImportObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Elca\Service\Assistant\AbstractAssistant;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\Assistant\DormerElementImageView;
use Elca\View\DefaultElementImageView;

/**
 * DormerAssistant
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class DormerAssistant extends AbstractAssistant implements ElementObserver, ElcaProjectVariantObserver, ImportObserver
{
    const IDENT = 'dormer-assistant';

    const ELEMENT_IMAGE_VIEW = DormerElementImageView::class;

    /**
     * @var Elca
     */
    private $elca;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor)
    {
        $this->setConfiguration(
            new Configuration(
                [
                    360,  // Dächer
                    362,  // Dachfenster
                ],
                self::IDENT,
                'Fensterassistent',
                DormerCtrl::class,
                'default',
                [
                    ElementAssistant::PROPERTY_NAME,
                    ElementAssistant::PROPERTY_REF_UNIT,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_SIZE,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_AREA_RATIO,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_LENGTH,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_WIDTH,
                    ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID,
                    ElementAssistant::PROPERTY_COMPONENT_QUANTITY,
                    ElementAssistant::PROPERTY_COMPONENT_CONVERSION_ID,
                ],
                [
                    ElementAssistant::FUNCTION_COMPONENT_DELETE,
                    ElementAssistant::FUNCTION_COMPONENT_DELETE_COMPONENT,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER,
                    ElementAssistant::FUNCTION_ELEMENT_MOVE,
                    ElementAssistant::FUNCTION_ELEMENT_ADD_ELEMENT,
                    ElementAssistant::FUNCTION_ELEMENT_DELETE_ELEMENT,
                    ElementAssistant::FUNCTION_ELEMENT_UNASSIGN_ELEMENT,
                ]
            )
        );

        $this->elca = Elca::getInstance();
        $this->lcaProcessor = $lcaProcessor;
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
     * @param $elementId
     * @return bool
     */
    public function isWindowOrComponentElement($elementId)
    {
        return ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT)->isInitialized();
    }

    /**
     * @param $elementId
     * @return bool
     */
    public function isWindowComponentElement($elementId)
    {
        $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT);

        if (!$attr->isInitialized())
            return false;

        return $attr->getNumericValue() !== null;
    }

    /**
     * @param $elementId
     * @return bool
     */
    public function isWindowElement($elementId)
    {
        $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT);

        if (!$attr->isInitialized())
            return false;

        return $attr->getNumericValue() === null;
    }

    /**
     * @param int $elementId
     * @return Window
     */
    public function getWindowFromElement($elementId = null)
    {
        $window = null;

        $elementId = $this->getWindowElementId($elementId);

        if ($elementId) {
            $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT);

            if ($textValue = $attr->getTextValue()) {
                $window = unserialize(base64_decode($textValue));
            }
        }

        if ($window === null || !$window instanceof Window) {

            $initName = $elementId? ElcaElement::findById($elementId)->getName() : t('Neues Dachfenster');
            $window = Window::getDefault($initName);
        }

        return $window;
    }

    /**
     * @param int $windowElementId
     * @param Window $window
     * @throws \Exception
     */
    public function saveWindowForElement($windowElementId, Window $window)
    {
        $windowElementId = $this->getWindowElementId($windowElementId);

        $element = ElcaElement::findById($windowElementId);
        if (!$element->isInitialized() )
            throw new \Exception('Trying to save window data for uninitialized element');

        $serializedWindow = base64_encode(serialize($window));

        // save attribute with window data
        $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), DormerAssistant::IDENT);

        if ($attr->isInitialized()) {
            $attr->setTextValue($serializedWindow);
            $attr->update();
        }
        else {
            // save attribute with window data
            ElcaElementAttribute::create($windowElementId,
                self::IDENT,
                self::IDENT,
                null,
                $serializedWindow
            );
        }
    }

    /**
     * @param int $elementId
     *
     * @return DefaultElementImageView
     */
    public function getElementImageView($elementId)
    {
        $viewName = self::ELEMENT_IMAGE_VIEW;

        $view = new $viewName();
        $view->assign('window', $this->getWindowFromElement($elementId));

        return $view;
    }


    /**
     * @param             $property
     * @param ElcaElement $element
     * @return bool
     */
    public function isLockedProperty($property, ElcaElement $element = null)
    {
        return in_array($property, $this->getConfiguration()->getLockedProperties());
    }

    /**
     * @param                           $name
     * @param ElcaElement|null          $element
     * @param ElcaElementComponent|null $component
     * @return bool
     */
    public function isLockedFunction($name, ElcaElement $element = null, ElcaElementComponent $component = null)
    {
        return in_array($name, $this->getConfiguration()->getLockedFunctions());
    }

    /**
     * @return string
     */
    public function getIdent()
    {
        return self::IDENT;
    }

    /**
     * @param $windowElementId
     */
    public function computeLcaForWindowElement($windowElementId, $includeWindowElement = true)
    {
        $element = ElcaElement::findById($windowElementId);

        if (!$element->getProjectVariantId())
            return;

        $elements = $this->findElementsForWindowElement($windowElementId, $includeWindowElement);

        foreach ($elements as $element) {
            $this->lcaProcessor
            ->computeElement($element);
        }

        $this->lcaProcessor->updateCache($element->getProjectVariant()->getProjectId());
    }

    /**
     * @param $windowElementId
     * @return ElcaElement[]
     */
    public function findElementsForWindowElement($windowElementId, $includeWindowElement = false)
    {
        $windowElement = ElcaElement::findById($windowElementId);

        if (!$windowElement->isInitialized())
            return [];

        $elements = [];
        if ($includeWindowElement)
            $elements[] = $windowElement;

        $attrSet = ElcaElementAttributeSet::find([
            'ident' => DormerAssistant::IDENT,
            'numeric_value' => $windowElementId
        ]);

        if (!$attrSet->count())
            return $elements;

        foreach ($attrSet as $attr) {
            $elements[] = $attr->getElement();
        }

        return $elements;
    }

    /**
     * Checks if given elementId is a window element or a associated window component element.
     * In latter case it resolves and returns the window element id
     *
     * @param $elementId
     * @return int
     */
    public function getWindowElementId($elementId)
    {
        if ($elementId) {
            $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, self::IDENT);

            if ($attr->isInitialized()) {
                if ($attr->getNumericValue() !== null) {
                    $elementId = $attr->getNumericValue();
                }
            }
        }

        return $elementId;
    }

    public function onElementDelete(ElcaElement $elementToDelete)
    {
        if (!$this->isWindowOrComponentElement($elementToDelete->getId()))
            return true;

        try {

        if ($this->isWindowElement($elementToDelete->getId())) {
            $elementTypeNodeIds = [];
            $projectVariantId = $elementToDelete->getProjectVariantId();
            $elements = $this->findElementsForWindowElement($elementToDelete->getId());
            foreach ($elements as $elt) {
                $elementTypeNodeIds[] = $elt->getElementTypeNodeId();
                $elt->delete();
            }

            if ($projectVariantId) {
                /**
                 * Update type tree for all associated elementTypeNodeIds
                 */
                foreach ($elementTypeNodeIds as $elementTypeNodeId) {
                    $this->lcaProcessor->updateElementTypeTree($projectVariantId, $elementTypeNodeId);
                }
            }
        } else {
            $attr = $this->getElementAttribute($elementToDelete->getId());

            if (!$attr->isInitialized())
                return true;

            $windowElementId = $this->getWindowElementId($elementToDelete->getId());
            $window = $this->getWindowFromElement($windowElementId);

            switch ($attr->getTextValue()) {
                case DormerAssembler::IDENT_INDOOR_SILL:
                    $window->unsetIndoorSill();
                    break;
                case DormerAssembler::IDENT_OUTDOOR_SILL:
                    $window->unsetOutdoorSill();
                    break;
                case DormerAssembler::IDENT_INDOOR_SOFFIT:
                    $window->unsetIndoorSoffit();
                    break;
                case DormerAssembler::IDENT_OUTDOOR_SOFFIT:
                    $window->unsetOutdoorSoffit();
                    break;
                case DormerAssembler::IDENT_INDOOR_SUNSCREEN:
                    $window->unsetIndoorSunscreen();
                    break;
                case DormerAssembler::IDENT_OUTDOOR_SUNSCREEN:
                    $window->unsetOutdoorSunscreen();
                    break;

                default:
                    return true;
            }

            $this->saveWindowForElement($windowElementId, $window);
        }
        } catch (\Exception $e) {
            Log::getInstance()->error('Caught exception: '. $e->getMessage(), __METHOD__);
            Log::getInstance()->error($e->getTraceAsString(), __METHOD__);
        }

        return true;
    }

    /**
     * @param int  $elementId
     * @param null $projectVariantId
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
        if (!$this->isWindowElement($updatedElement->getId()))
            return;

        $needLcaProcessing = false;

        $elements = $this->findElementsForWindowElement($updatedElement->getId());
        $quantity = $updatedElement->getQuantity();

        foreach ($elements as $element) {
            if ($quantity === $element->getQuantity())
                continue;

            $element->setQuantity($quantity);
            $element->update();

            $needLcaProcessing = true;
        }

        if ($needLcaProcessing) {
            $this->computeLcaForWindowElement($updatedElement->getId(), false);
        }
    }

    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
    public function onElementCopy(ElcaElement $element, ElcaElement $copiedElement)
    {
        if (!$this->isWindowOrComponentElement($element->getId()))
            return;

        /**
         * Update window element
         */
        if ($this->isWindowElement($element->getId()))
        {
            $window = $this->getWindowFromElement($copiedElement->getId());
            $window->setName($copiedElement->getName());

            $assember = new DormerAssembler($window, $copiedElement->getProjectVariantId());
            $assember->update($copiedElement);

            $this->saveWindowForElement($copiedElement->getId(), $window);
            $this->computeLcaForWindowElement($copiedElement->getId(), false);
        } else {
            /**
             * Remove attribute from copied element
             */
            $attr = ElcaElementAttribute::findByElementIdAndIdent($copiedElement->getId(), self::IDENT);
            if ($attr->isInitialized())
                $attr->delete();
        }
    }

    /**
     * @param ElcaProjectVariant $oldVariant
     * @param ElcaProjectVariant $newVariant
     * @return mixed|void
     */
    public function onProjectVariantCopy(ElcaProjectVariant $oldVariant, ElcaProjectVariant $newVariant)
    {
        /**
         * Find all window element attributes
         */
        $newVariantWindowElementAttributes = ElcaElementAttributeSet::findWithinProjectVariantByIdentAndNumericValue(
            $newVariant->getId(),
            self::IDENT,
            null
        );

        /**
         * @var ElcaElementAttribute $newVariantWindowElementAttribute
         */
        foreach ($newVariantWindowElementAttributes as $newVariantWindowElementAttribute) {

            $windowElement = $newVariantWindowElementAttribute->getElement();

            /**
             * Find metadata which need to be reference the new window element
             */
            $attributesOfElementsToReconstruct = ElcaElementAttributeSet::findWithinProjectVariantByIdentAndNumericValue(
                $newVariant->getId(), self::IDENT, $windowElement->getCopyOfElementId()
            );

            /**
             * @var ElcaElementAttribute $attribute
             */
            foreach ($attributesOfElementsToReconstruct as $attribute) {
                $attribute->setNumericValue($windowElement->getId());
                $attribute->update();
            }
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
     * @param ElcaProject $project
     * @return void
     */
    public function onProjectImport(ElcaProject $project)
    {
        foreach ($project->getProjectVariants() as $projectVariant) {

            /**
             * Find all window component element attributes and delete them
             */
            $windowComponentElementAttributes = ElcaElementAttributeSet::findWithinProjectVariantByIdentAndNumericValue(
                $projectVariant->getId(),
                self::IDENT,
                true // is not null
            );

            foreach ($windowComponentElementAttributes as $windowComponentElementAttribute) {
                $element = $windowComponentElementAttribute->getElement();
                $element->delete();
            }

            /**
             * Find all window element attributes and recreate them
             */
            $windowElementAttributes = ElcaElementAttributeSet::findWithinProjectVariantByIdentAndNumericValue(
                $projectVariant->getId(),
                self::IDENT,
                null
            );

            /**
             * @var ElcaElementAttribute $windowElementAttribute
             */
            foreach ($windowElementAttributes as $windowElementAttribute) {
                $windowElement = $windowElementAttribute->getElement();

                $window = $this->getWindowFromElement($windowElement->getId());

                $assember = new DormerAssembler($window, $projectVariant->getId());
                $assember->update($windowElement);

                $this->saveWindowForElement($windowElement->getId(), $window);
            }
        }
    }

    /**
     * @param ElcaElement $element
     * @return void
     */
    public function onElementImport(ElcaElement $element)
    {

    }
}