<?php declare(strict_types=1);
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

namespace Elca\Service\Project;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaElement;
use Elca\Model\Common\Unit;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\Element\ElementService;

class ProjectElementService
{
    /**
     * @var array|ElementObserver[]
     */
    private $elementObservers;

    /**
     * @var DbHandle
     */
    private $dbh;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @param array            $elementObservers
     * @param DbHandle         $dbh
     */
    public function __construct(array $elementObservers, DbHandle $dbh, ElementService $elementService, ElcaLcaProcessor $lcaProcessor)
    {
        $this->elementObservers = $elementObservers;
        $this->dbh              = $dbh;
        $this->lcaProcessor = $lcaProcessor;
        $this->elementService = $elementService;
    }

    public function copyElementFrom(
        ElcaElement $element, $ownerId = null, $projectVariantId = null, $accessGroupId = null, $copyName = false,
        $copyCacheItems = false, $compositeElementId = null, $compositePosition = null)
    {
        return $this->elementService->copyElementFrom($element, $ownerId, $projectVariantId, $accessGroupId, $copyName,
            $copyCacheItems, $compositeElementId, $compositePosition);
    }

    public function deleteElement(ElcaElement $element, $recursive = false): bool
    {
        if (!$element->isInitialized()) {
            return false;
        }

        $projectVariantId = $element->getProjectVariantId();
        $elementTypeNodeIds = [];
        $compositeElement = null;
        $oldOpaqueArea = null;

        /**
         * Store all element type nodeIds to update type tree after deletion
         */
        if ($element->isComposite()) {
            if ($recursive) {
                foreach ($element->getCompositeElements() as $assignment)
                    $elementTypeNodeIds[] = $assignment->getElement()->getElementTypeNodeId();
            }
        } else {
            $compositeElement = $element->hasCompositeElement()
                ? $element->getCompositeElements()->offsetGet(0)->getCompositeElement()
                : null;

            if ($compositeElement && !$element->isComposite()) {
                $oldOpaqueArea = $compositeElement->getOpaqueArea();
            }

            $elementTypeNodeIds[] = $element->getElementTypeNodeId();
        }

        $assistantElement = ElcaAssistantElement::findByMainElementId($element->getId());
        if ($assistantElement->isInitialized() && $assistantElement->getMainElementId() === $element->getId()) {
            foreach ($assistantElement->getSubElements() as $assistantSubElement) {
                $elementTypeNodeIds[] = $assistantSubElement->getElement()->getElementTypeNodeId();
            }
        }

        $this->elementService->deleteElement($element, $recursive);

        /**
         * Update area of opaque elements
         */
        if (null !== $oldOpaqueArea &&
           !$element->isComposite() &&
           null !== $compositeElement) {
            $this->updateAffectedOpaqueElements($compositeElement, $oldOpaqueArea);
        }

        /**
         * Update the element type cache hierarchy
         */
        if (null !== $compositeElement && $compositeElement->isInitialized()) {
            $this->lcaProcessor
                ->computeElement($compositeElement)
                ->updateCache($compositeElement->getProjectVariant()->getProjectId());
        }

        /**
         * Update type tree for all associated elementTypeNodeIds
         */
        foreach ($elementTypeNodeIds as $elementTypeNodeId) {
            $this->lcaProcessor->updateElementTypeTree($projectVariantId, $elementTypeNodeId);
        }

        return true;
    }

    public function addToCompositeElement(ElcaElement $compositeElement, ElcaElement $element, $position = null): bool
    {
        /**
         * Skip if one of the elements is not initialized or already assigned
         */
        if (!$compositeElement->isInitialized() ||
           !$element->isInitialized() ||
           (!$element->isTemplate() && $element->hasCompositeElement())) {
            return false;
        }

        /**
         * If this is a template element, create copy first
         */
        if($element->isTemplate())
        {
            $user    = UserStore::getInstance()->getUser();
            $addElement = $this->elementService->copyElementFrom(
                $element,
                $user->getId(),
                $compositeElement->getProjectVariantId(),
                $user->getGroupId(),
                true
            );
        }
        else {
            $addElement = $element;
        }

        /**
         * Remember old area of opaque elements
         */
        $oldOpaqueArea = round($compositeElement->getOpaqueArea(), 3);

        /**
         * Assign it to the composite element
         */
        if (!$this->elementService->addToCompositeElement($compositeElement, $addElement, $position)) {
            return false;
        }

        /**
         * Recalculate area of opaque elements with the exact old value
         */
        $this->updateAffectedOpaqueElements($compositeElement, $oldOpaqueArea);

        /**
         * Recalculate element
         */
        $this->lcaProcessor
                ->computeElement($compositeElement)
                ->updateCache($compositeElement->getProjectVariant()->getProjectId());

        return true;
    }


    public function removeFromCompositeElement(ElcaElement $compositeElement, $position, ElcaElement $element, $deleteElement = false)
    {
        $elementTypeNodeId = $element->getElementTypeNodeId();
        $projectVariantId = $element->getProjectVariantId();

        /**
         * Remember old area of opaque elements
         */
        $oldOpaqueArea = round($compositeElement->getOpaqueArea(), 3);

        if (!$this->elementService->removeFromCompositeElement($compositeElement, $position, $element, $deleteElement)) {
            return false;
        }

        /**
         * Recalculate area of opaque elements with the exact old value
         */
        $this->updateAffectedOpaqueElements($compositeElement, $oldOpaqueArea);

        /**
         * Recalculate elements
         */
        if (!$deleteElement) {
            $this->lcaProcessor->computeElement($element);
        }

        $this->lcaProcessor
            ->computeElement($compositeElement)
            ->updateCache($compositeElement->getProjectVariant()->getProjectId())
            /**
             * Update type tree for all associated elementTypeNodeIds
             */
            ->updateElementTypeTree($projectVariantId, $elementTypeNodeId);

        return true;
    }

    public function updateAffectedOpaqueElements(ElcaElement $compositeElement, $oldCalculatedOpaqueArea = null)
    {
        $affectedElements = new DataObjectSet();

        if (($refUnit = $compositeElement->getRefUnit()) !== Unit::SQUARE_METER) {
            return $affectedElements;
        }

        $newArea = round($compositeElement->getOpaqueArea(true), 3);

        if ($oldCalculatedOpaqueArea === $newArea) {
            return $affectedElements;
        }

        foreach ($compositeElement->getCompositeElements() as $assignment)
        {
            $element = $assignment->getElement();

            if($element->isOpaque() === false) {
                continue;
            }

            /**
             * Skip element if either the refUnit differs or
             * if the old calculated value for the opaque area differ
             */
            if($element->getRefUnit() !== $refUnit ||
               (null !== $oldCalculatedOpaqueArea && !FloatCalc::cmp($element->getQuantity(), $oldCalculatedOpaqueArea))
            ) {
                continue;
            }

            $element->setQuantity($newArea);
            $element->update();

            $affectedElements->add($element);
        }

        return $affectedElements;
    }

    public function updateQuantityOfAffectedElements(ElcaElement $compositeElement, $oldQuantity)
    {
        $affectedElements = new DataObjectSet();

        $newQuantity = $compositeElement->getQuantity();
        $refUnit = $compositeElement->getRefUnit();

        if ($oldQuantity === $newQuantity) {
            return $affectedElements;
        }

        foreach ($compositeElement->getCompositeElements() as $Assignment) {
            $element = $Assignment->getElement();

            if ($element->getRefUnit() != $refUnit ||
                !FloatCalc::cmp($element->getQuantity(), $oldQuantity)) {
                continue;
            }

            $element->setQuantity($newQuantity);
            $element->update();

            $affectedElements->add($element);
        }

        return $affectedElements;
    }
}
