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

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Interfaces\Logger;
use Beibob\Blibs\Log;
use Elca\Controller\Assistant\WindowCtrl;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaAssistantSubElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementAttributeSet;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Assistant\Window\Assembler;
use Elca\Model\Assistant\Window\Window;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Import\ImportObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Elca\Service\Assistant\AbstractAssistant;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\Assistant\WindowElementImageView;
use Elca\View\DefaultElementImageView;

/**
 * WindowAssistant
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class WindowAssistant extends AbstractAssistant implements ElementObserver, ElcaProjectVariantObserver, ImportObserver
{
    const IDENT = 'window-assistant';

    const ELEMENT_IMAGE_VIEW = WindowElementImageView::class;

    /**
     * @var Elca
     */
    private $elca;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor, Logger $logger)
    {
        $this->setConfiguration(
            new Configuration(
                [
                    330,  // Außenwände
                    334,  // Fenster und Türen
                    335,
                    336,
                    338,
                ],
                self::IDENT,
                'Fensterassistent',
                WindowCtrl::class,
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

        $this->elca         = Elca::getInstance();
        $this->lcaProcessor = $lcaProcessor;
        $this->logger       = $logger;
    }

    /**
     * @param ElcaElement $element
     * @return bool
     */
    public function provideForElement(ElcaElement $element)
    {
        return parent::provideForElement($element);
    }

    /**
     * @param ElcaElementType $elementType
     * @param string          $context
     * @return bool
     */
    public function provideForElementType(ElcaElementType $elementType, $context)
    {
        return parent::provideForElementType($elementType, $context);
    }


    /**
     * @param $elementId
     * @return bool
     */
    public function isWindowOrComponentElement($elementId)
    {
        return ElcaAssistantElement::findByElementId($elementId, self::IDENT)->isInitialized();
    }

    /**
     * @param $elementId
     * @return bool
     */
    public function isWindowMainElement($elementId)
    {
        $assistantElement = ElcaAssistantElement::findByElementId($elementId, self::IDENT);

        if (!$assistantElement->isInitialized()) {
            return false;
        }

        return $elementId === $assistantElement->getMainElementId();
    }

    /**
     * @param int $elementId
     * @return Window
     */
    public function getWindowFromElement($elementId = null)
    {
        $window = null;

        if ($elementId) {
            $assistantElement = ElcaAssistantElement::findByElementId($elementId, self::IDENT);

            $window = $assistantElement->getDeserializedConfig();
        }

        if ($window === null || !$window instanceof Window) {

            $initName = $elementId ? ElcaElement::findById($elementId)->getName() : null;
            $window   = Window::getDefault($initName);
        }

        return $window;
    }

    /**
     * @param int $elementId
     * @return Window
     * @deprecated only used for migration
     */
    public function _getWindowFromElement($elementId = null)
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

            $initName = $elementId ? ElcaElement::findById($elementId)->getName() : null;
            $window   = Window::getDefault($initName);
        }

        return $window;
    }

    /**
     * @param int    $windowElementId
     * @param Window $window
     * @throws \Exception
     */
    public function saveWindowForElement($windowElementId, Window $window)
    {
        $assistantElement = ElcaAssistantElement::findByElementId($windowElementId);

        if ($assistantElement->isInitialized()) {
            $assistantElement->setUnserializedConfig($window);
            $assistantElement->update();
        } else {
            $windowElement = ElcaElement::findById($windowElementId);

            if (!$windowElement->isInitialized()) {
                throw new \Exception('Trying to add window data for uninitialized element');
            }

            $assistantElement = ElcaAssistantElement::createWithUnserializedConfig($windowElementId,
                self::IDENT,
                $windowElement->getProjectVariantId(),
                $window,
                $windowElement->isReference(),
                $windowElement->isPublic(),
                $windowElement->getOwnerId(),
                $windowElement->getAccessGroupId()
            );

            ElcaAssistantSubElement::create($assistantElement->getId(), $windowElementId, Assembler::IDENT_WINDOW);
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

        if (!$element->getProjectVariantId()) {
            return;
        }

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

        if (!$windowElement->isInitialized()) {
            return [];
        }

        $assistantElement = ElcaAssistantElement::findByElementId($windowElementId);

        $elements = [];

        /**
         * @var ElcaAssistantSubElement $subElement
         */
        foreach ($assistantElement->getSubElements() as $subElement) {
            if (false === $includeWindowElement && $subElement->getElementId() === $windowElementId) {
                continue;
            }

            $elements[] = $subElement->getElement();
        }

        return $elements;
    }

    /**
     * @param $windowElementId
     * @return ElcaElement[]
     * @deprecated only used for migration
     */
    public function _findElementsForWindowElement($windowElementId, $includeWindowElement = false)
    {
        $windowElement = ElcaElement::findById($windowElementId);

        if (!$windowElement->isInitialized()) {
            return [];
        }

        $elements = [];
        if ($includeWindowElement) {
            $elements[] = $windowElement;
        }

        $attrSet = ElcaElementAttributeSet::find([
            'ident'         => self::IDENT,
            'numeric_value' => $windowElementId,
        ]);

        if (!$attrSet->count()) {
            return $elements;
        }

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
            $assistantElement = ElcaAssistantElement::findByElementId($elementId, self::IDENT);

            if ($assistantElement->isInitialized()) {
                if ($assistantElement->getMainElementId() !== $elementId) {
                    $elementId = $assistantElement->getMainElementId();
                }
            }
        }

        return $elementId;
    }

    /**
     * Checks if given elementId is a window element or a associated window component element.
     * In latter case it resolves and returns the window element id
     *
     * @param $elementId
     * @return int
     * @deprecated only used by migration
     */
    public function _getWindowElementId($elementId)
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
        if (!$this->isWindowOrComponentElement($elementToDelete->getId())) {
            return true;
        }

        try {

            if (!$this->isWindowMainElement($elementToDelete->getId())) {
                $assistantElement    = ElcaAssistantElement::findByElementId($elementToDelete->getId(), self::IDENT);
                $assistantSubElement = ElcaAssistantSubElement::findByPk($assistantElement->getId(),
                    $elementToDelete->getId());

                $window = $this->getWindowFromElement($elementToDelete->getId());

                switch ($assistantSubElement->getIdent()) {
                    case Assembler::IDENT_INDOOR_SILL:
                        $window->unsetIndoorSill();
                        break;
                    case Assembler::IDENT_OUTDOOR_SILL:
                        $window->unsetOutdoorSill();
                        break;
                    case Assembler::IDENT_INDOOR_SOFFIT:
                        $window->unsetIndoorSoffit();
                        break;
                    case Assembler::IDENT_OUTDOOR_SOFFIT:
                        $window->unsetOutdoorSoffit();
                        break;
                    case Assembler::IDENT_INDOOR_SUNSCREEN:
                        $window->unsetIndoorSunscreen();
                        break;
                    case Assembler::IDENT_OUTDOOR_SUNSCREEN:
                        $window->unsetOutdoorSunscreen();
                        break;

                    default:
                        return true;
                }

                $this->saveWindowForElement($assistantElement->getMainElementId(), $window);
            }
        }
        catch (\Exception $e) {
            Log::getInstance()->error('Caught exception: ' . $e->getMessage(), __METHOD__);
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
        if (!$this->isWindowMainElement($updatedElement->getId())) {
            return;
        }

        $needLcaProcessing = false;

        $elements = $this->findElementsForWindowElement($updatedElement->getId());
        $quantity = $updatedElement->getQuantity();

        foreach ($elements as $element) {
            if ($quantity === $element->getQuantity()) {
                continue;
            }

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
    }

    /**
     * @param ElcaProjectVariant $oldVariant
     * @param ElcaProjectVariant $newVariant
     * @return mixed|void
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
        // @todo remove eventually in the future
        $this->legacyProjectImport($project);
    }

    /**
     * @param ElcaElement $element
     * @return void
     */
    public function onElementImport(ElcaElement $element)
    {

    }

    protected function migrate(int $elementId)
    {
        $dbh = DbHandle::getInstance();
        try {
            $dbh->begin();
            $windowElementId = $this->_getWindowElementId($elementId);

            $windowElement = ElcaElement::findById($windowElementId);

            if (!$windowElement->isInitialized()) {
                throw new \UnexpectedValueException('Migration of assistant element ' . $elementId . ' failed: Main element could not be initialized');
            }

            $this->logger->notice(sprintf('Migrating %s element(s) `%s\' (%s)', self::IDENT, $windowElement->getName(),
                $windowElement->getId()), __FUNCTION__);

            $windowConfiguration = $this->_getWindowFromElement($windowElementId);

            $assistantElement = ElcaAssistantElement::createWithUnserializedConfig($windowElementId,
                self::IDENT,
                $windowElement->getProjectVariantId(),
                $windowConfiguration,
                $windowElement->isReference(),
                $windowElement->isPublic(),
                $windowElement->getOwnerId(),
                $windowElement->getAccessGroupId()
            );

            $windowElements = $this->_findElementsForWindowElement($windowElementId, true);

            foreach ($windowElements as $assignedElement) {
                $assignedAttribute = $assignedElement->getAttribute(self::IDENT);

                $ident = $windowElementId === $assignedElement->getId()
                    ? Assembler::IDENT_WINDOW
                    : $assignedAttribute->getTextValue();

                ElcaAssistantSubElement::create($assistantElement->getId(), $assignedElement->getId(), $ident);

                $elementAttribute = ElcaElementAttribute::findByElementIdAndIdent($assignedElement->getId(),
                    self::IDENT);
                $elementAttribute->delete();
            }

            $dbh->commit();
        }
        catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }
    }

    /**
     * Legacy data model (based on attributes) still required for old import files
     *
     * This shouldn't interfere with the new model, since there won't be any assistant related attributes
     * exported.
     *
     * @deprecated
     */
    protected function legacyProjectImport(ElcaProject $project): void
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
                $window        = $this->_getWindowFromElement($windowElement->getId());

                $this->logger->notice(sprintf('Importing legacy %s element(s) `%s\' (%s)', self::IDENT,
                    $windowElement->getName(),
                    $windowElement->getId()), __FUNCTION__);

                $assistantElement = ElcaAssistantElement::createWithUnserializedConfig($windowElement->getId(),
                    self::IDENT,
                    $windowElement->getProjectVariantId(),
                    $window,
                    $windowElement->isReference(),
                    $windowElement->isPublic(),
                    $windowElement->getOwnerId(),
                    $windowElement->getAccessGroupId()
                );
                ElcaAssistantSubElement::create($assistantElement->getId(), $windowElement->getId(),
                    Assembler::IDENT_WINDOW);

                $assembler = new Assembler($window, $projectVariant->getId());
                $assembler->update($windowElement);

                $this->saveWindowForElement($windowElement->getId(), $window);
            }

            // remove all legacy attributes
            $elementAttributes = ElcaElementAttributeSet::findWithinProjectVariantByIdent($projectVariant->getId(),
                self::IDENT);
            foreach ($elementAttributes as $elementAttribute) {
                $elementAttribute->delete();
            }

        }
    }
}
