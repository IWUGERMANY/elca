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

namespace Elca\Service\Element;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaAssistantSubElement;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaCompositeElementSet;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Model\Common\Unit;
use Elca\Model\Element\ElementObserver;
use Exception;

class ElementService
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
     * @param array    $elementObservers
     * @param DbHandle $dbh
     */
    public function __construct(array $elementObservers, DbHandle $dbh)
    {
        $this->elementObservers = $elementObservers;
        $this->dbh              = $dbh;
    }

    public function addElementObserver(ElementObserver $elementObserver) {
        $this->elementObservers[] = $elementObserver;
    }

    /**
     * @param ElcaElement $element
     * @param null|int    $ownerId
     * @param null        $projectVariantId
     * @param null        $accessGroupId
     * @param bool        $copyName
     * @param bool        $copyCacheItems
     * @param null        $compositeElementId
     * @param null        $compositePosition
     * @return null|ElcaElement
     * @throws \Exception
     */
    public function copyElementFrom(
        ElcaElement $element, $ownerId = null, $projectVariantId = null, $accessGroupId = null, $copyName = false,
        $copyCacheItems = false, $compositeElementId = null, $compositePosition = null, bool $doNotTriggerObservers = false
    ) {
        if (!$element->isInitialized()) {
            return null;
        }

        $assistantElement = ElcaAssistantElement::findByMainElementId($element->getId());

        $ownerId = $ownerId ?? UserStore::getInstance()->getUser()->getId();

        $copy = $this->copyElement(
            $element,
            $ownerId,
            $projectVariantId,
            $accessGroupId,
            $copyName,
            $copyCacheItems,
            $compositeElementId,
            $compositePosition,
            $assistantElement
        );

        // trigger observers
        if (false === $doNotTriggerObservers) {
            foreach ($this->elementObservers as $observer) {
                if (!$observer instanceof ElementObserver) {
                    continue;
                }

                $observer->onElementCopy($element, $copy);
            }
        }

        return $copy;
    }

    public function deleteElement(ElcaElement $element, $recursive = false): bool {
        if (!$element->isInitialized()) {
            return false;
        }

        /**
         * Observer may abort this action
         */
        foreach ($this->elementObservers as $observer) {
            if ($observer->onElementDelete($element) === false)
                return false;
        }

        if ($element->isComposite() && $recursive) {
            foreach ($element->getCompositeElements() as $assignment) {
                $assignment->getElement()->delete();
            }
        }

        $assistantElement = ElcaAssistantElement::findByMainElementId($element->getId());
        if ($assistantElement->isInitialized() && $assistantElement->getMainElementId() === $element->getId()) {
            foreach ($assistantElement->getSubElements() as $assistantSubElement) {
                if ($assistantSubElement->getElementId() !== $assistantElement->getMainElementId()) {
                    $this->deleteElement($assistantSubElement->getElement(), $recursive);
                }
            }
        }

        $elementId = $element->getId();
        $projectVariantId = $element->getProjectVariantId();
        $element->delete();

        foreach ($this->elementObservers as $observer) {
            $observer->afterDeletion($elementId, $projectVariantId);
        }

        return true;
    }


    /**
     * Assigns an element to a composite element
     *
     * @param  ElcaElement $compositeElement - composite element
     * @param  ElcaElement $element          - element to assign
     * @param null         $position
     * @throws Exception
     * @return boolean
     */
    public function addToCompositeElement(ElcaElement $compositeElement, ElcaElement $element, $position = null): bool
    {
        /**
         * Skip if one of the elements is not initialized
         */
        if (!$compositeElement->isInitialized() ||
            !$element->isInitialized()) {
            return false;
        }

        try {
            $elementType = $element->getElementTypeNode();

            $this->dbh->begin();

            /**
             * Assign it to the composite element
             * Synchronize quantity and refUnit
             */
            if (null === $position) {
                if ($element->getElementTypeNode()->isOpaque() === false) {
                    // transparent elements at the end
                    $position = ElcaCompositeElement::getMaxCompositePosition($compositeElement->getId()) + 1;
                } // opaque elements *before* transparent elements
                else {
                    $position = ElcaCompositeElement::getMaxOpaquePosition($compositeElement->getId()) + 1;

                    $compositeElements = ElcaCompositeElementSet::findNonOpaqueByCompositeElementId(
                        $compositeElement->getId(),
                        [],
                        ['position' => 'ASC']
                    );

                    // reverse list order to avoid duplicate keys
                    $compositeElements->reverse();
                    foreach ($compositeElements as $assignment) {
                        $assignment->setPosition($assignment->getPosition() + 1);
                        $assignment->update();
                    }
                }
            }

            /**
             * Create new assignment
             */
            ElcaCompositeElement::create($compositeElement->getId(), $position, $element->getId());

            if ($elementType->isOpaque() !== false) {
                if ($compositeElement->getRefUnit() === Unit::SQUARE_METER) {
                    $element->setQuantity(round($compositeElement->getOpaqueArea(true), 3));
                } else {
                    $element->setQuantity($compositeElement->getQuantity());
                }

                $element->setRefUnit($compositeElement->getRefUnit());
            }

            /**
             * Public state of composite elements overwrite element state
             */
            if ($compositeElement->isPublic()) {
                $element->setIsPublic(true);
            }

            if ($compositeElement->isReference()) {
                $element->setIsReference(true);
            }

            $element->update();
            $this->dbh->commit();
        } catch (Exception $exception) {
            $this->dbh->rollback();
            throw $exception;
        }

        return true;
    }

    /**
     * Unassigns or deletes a element from a composite element
     *
     * @param  ElcaElement $compositeElement - composite element
     * @param  int         $position
     * @param  ElcaElement $element          - element to unassign or delete
     * @param  boolean     $deleteElement    - if true, the element will be deleted instead of unassigned
     * @throws Exception
     * @return boolean
     */
    public function removeFromCompositeElement(
        ElcaElement $compositeElement, $position, ElcaElement $element, $deleteElement = false
    ): bool {
        if (!$element->isInitialized() || !$compositeElement->isInitialized()) {
            return false;
        }

        try {
            $this->dbh->begin();

            if ($deleteElement) {
                foreach ($this->elementObservers as $observer) {
                    if (!$observer instanceof ElementObserver) {
                        continue;
                    }

                    if ($observer->onElementDelete($element) === false) {
                        return false;
                    }
                }

                /**
                 * Assignment will be implicit deleted by constraint
                 */
                $elementId        = $element->getId();
                $projectVariantId = $element->getProjectVariantId();

                $element->delete();

                foreach ($this->elementObservers as $observer) {
                    if (!$observer instanceof ElementObserver) {
                        continue;
                    }

                    $observer->afterDeletion($elementId, $projectVariantId);
                }
            } else {
                /**
                 * Explicit remove assignment
                 */
                $assignment = ElcaCompositeElement::findByPk($compositeElement->getId(), $position);
                $assignment->delete();
            }

            $this->dbh->commit();
        } catch (Exception $exception) {
            $this->dbh->rollback();
            throw $exception;
        }

        return true;
    }

    /**
     * @param ElcaElement               $element
     * @param                           $ownerId
     * @param                           $projectVariantId
     * @param                           $accessGroupId
     * @param                           $copyName
     * @param                           $copyCacheItems
     * @param                           $compositeElementId
     * @param                           $compositePosition
     * @param ElcaAssistantElement|null $assistantElement
     * @return ElcaElement
     * @throws Exception
     */
    private function copyElement(
        ElcaElement $element, $ownerId, $projectVariantId, $accessGroupId, $copyName, $copyCacheItems,
        $compositeElementId, $compositePosition, ElcaAssistantElement $assistantElement
    ): ElcaElement {
        try {
            $this->dbh->begin();

            /**
             * Quantity depends on...
             *
             * the copy will be a project element
             */
            if ($projectVariantId) {
                // and is copied from template
                // and is within composite elements
                // and is transparent
                if (!$element->getProjectVariantId() &&
                    $compositeElementId &&
                    $element->getElementTypeNode()->isOpaque() === false) {
                    $quantity = 0;
                } else {
                    $quantity = $element->getQuantity();
                }
            } // or a template elements,
            else {
                $quantity = 1;
            }

            $copy = ElcaElement::create(
                $element->getElementTypeNodeId(),
                $copyName ? $element->getName() : ElcaElement::findNewUniqueName($element),
                $element->getDescription(),
                false, // copy is never a reference element
                $accessGroupId ? $accessGroupId : $element->getAccessGroupId(),
                $projectVariantId,
                $quantity,
                $element->getRefUnit(),
                $projectVariantId ? $element->getId() : null, // copyOfElementId only on project elements
                $ownerId
            );

            /**
             * Copy components
             */
            if ($copy->isInitialized()) {
                if ($copyCacheItems) {
                    /**
                     * Retrieve the cached composite element itemId for the given compositeElementId
                     * Then copy the cached element and assign this itemId
                     */
                    $compositeItemId = null;

                    if ($compositeElementId) {
                        $compositeItemId = ElcaCacheElement::findByElementId($compositeElementId)->getItemId();
                    }

                    ElcaCacheElement::findByElementId($element->getId())->copy($copy->getId(), $compositeItemId);
                }

                if ($element->isComposite()) {
                    if ($assistantElement->isInitialized() && $assistantElement->isMainElement($element->getId())) {
                        $assistantSubElement = ElcaAssistantSubElement::findByPk($assistantElement->getId(), $element->getId());

                        $newAssistantElement = $assistantElement->copy($copy->getId(), $copy->getProjectVariantId(), $ownerId, $accessGroupId);
                        $assistantSubElement->copy($newAssistantElement->getId(), $copy->getId());
                    }

                    foreach ($element->getCompositeElements(null, true) as $assignment) {
                        if ($assistantElement->isInitialized()) {
                            $copiedSubElement = $this->copyElement(
                                $assignment->getElement(),
                                $ownerId,
                                $projectVariantId,
                                $accessGroupId,
                                true,
                                $copyCacheItems,
                                $copy->getId(),
                                $assignment->getPosition(),
                                $assistantElement
                            );

                            $assistantSubElement = ElcaAssistantSubElement::findByPk($assistantElement->getId(),
                                $assignment->getElementId());
                            $assistantSubElement->copy($newAssistantElement->getId(), $copiedSubElement->getId());
                        }
                        else {
                            $this->copyElement(
                                $assignment->getElement(),
                                $ownerId,
                                $projectVariantId,
                                $accessGroupId,
                                true,
                                $copyCacheItems,
                                $copy->getId(),
                                $assignment->getPosition(),
                                ElcaAssistantElement::findByMainElementId($assignment->getElementId())
                            );
                        }
                    }
                } else {
                    /**
                     * Assign element to compositeElementId
                     */
                    if ($compositeElementId) {
                        $position = $compositePosition ? $compositePosition
                            : ElcaCompositeElement::getMaxCompositePosition($compositeElementId) + 1;
                        ElcaCompositeElement::create($compositeElementId, $position, $copy->getId());
                    }

                    /**
                     * Clone all components
                     *
                     * @var ElcaElementComponent $component
                     */
                    $componentSet = ElcaElementComponentSet::findByElementId(
                        $element->getId(),
                        [],
                        ['layer_position' => 'ASC', 'id' => 'ASC']
                    );

                    $siblings = [];
                    foreach ($componentSet as $component) {
                        if ($component->hasLayerSibling()) {
                            if (!isset($siblings[$component->getId()])) {
                                $copyComponent = $component->copy($copy->getId(), null, $copyCacheItems);
                                $copySibling   = $component->getLayerSibling()->copy(
                                    $copy->getId(),
                                    $copyComponent->getId(),
                                    $copyCacheItems
                                );

                                $copyComponent->setLayerSiblingId($copySibling->getId());
                                $copyComponent->update();

                                $siblings[$component->getLayerSiblingId()] = true;
                            }
                        } else {
                            $component->copy($copy->getId(), null, $copyCacheItems);
                        }
                    }

                    /**
                     * Copy assistant and sub elements if this element is a assistant main element
                     */
                    if ($assistantElement->isInitialized() && $assistantElement->isMainElement($element->getId())) {
                        $newAssistantElement = $assistantElement->copy($copy->getId(), $copy->getProjectVariantId(), $ownerId, $accessGroupId);

                        foreach ($assistantElement->getSubElements() as $assistantSubElement) {
                            if ($assistantSubElement->getElementId() === $assistantElement->getMainElementId()) {
                                $assistantSubElement->copy($newAssistantElement->getId(), $copy->getId());
                            }
                            else {
                                $copiedSubElement = $this->copyElement($assistantSubElement->getElement(), $ownerId,
                                    $projectVariantId, $accessGroupId, $copyName, $copyCacheItems, null,
                                    null, $newAssistantElement);

                                $assistantSubElement->copy($newAssistantElement->getId(), $copiedSubElement->getId());
                            }
                        }
                    }
                }

                // if this a template element, copy constrDesigns and catalogs
                if ($copy->isTemplate()) {
                    foreach ($element->getConstrCatalogs() as $constrCatalog) {
                        $copy->assignConstrCatalogId($constrCatalog->getId());
                    }

                    foreach ($element->getConstrDesigns() as $constrDesign) {
                        $copy->assignConstrDesignId($constrDesign->getId());
                    }
                }

                /**
                 * Copy element attributes
                 */
                foreach ($element->getAttributes() as $attribute) {
                    ElcaElementAttribute::create(
                        $copy->getId(),
                        $attribute->getIdent(),
                        $attribute->getCaption(),
                        $attribute->getNumericValue(),
                        $attribute->getTextValue()
                    );
                }
            }
            $this->dbh->commit();
        } catch (Exception $exception) {
            $this->dbh->rollback();
            throw $exception;
        }

        return $copy;
    }
}
