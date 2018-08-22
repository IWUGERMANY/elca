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

namespace Elca\Model\Assistant\Stairs;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Log;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementAttributeSet;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentAttribute;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Elca;
use Elca\Model\Assistant\Stairs\Construction\MiddleHolm;
use Elca\Model\Assistant\Stairs\Construction\Solid;
use Elca\Model\Assistant\Stairs\Construction\Stringer;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\Service\Element\ElementService;

/**
 * Assembler
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Assembler
{
    const DIN_CEILINGS = '350';
    const DIN_CONSTRUCTION = '351';
    const DIN_COVER = '352';

    const IDENT_CONSTRUCTION = 'construction';
    const IDENT_COVER_RISER = 'cover-riser';
    const IDENT_COVER = 'cover';
    const IDENT_RISER = 'riser';
    const IDENT_PLATFORM_COVER = 'platform-cover';
    const IDENT_PLATFORM_CONSTRUCTION = 'platform-construction';

    const NAME_SOLID_SLAB = '%s / Laufplatte';
    const NAME_MIDDLE_HOLM = '%s / Mittelholm';
    const NAME_STRINGER = '%s / Wange';
    const NAME_COVER = '%s / Trittstufe';
    const NAME_COVER_AND_RISER = '%s / Tritt- und Setzstufe';
    const NAME_PLATFORM_CONSTRUCTION = '%s / Podest / Konstruktion';
    const NAME_PLATFORM_COVER = '%s / Podest / Belag';

    /**
     * @var Staircase
     */
    private $staircase;

    /**
     * @var null
     */
    private $projectVariantId;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @param ElementService $elementService
     * @param Staircase      $staircase
     * @param null           $projectVariantId
     */
    public function __construct(ElementService $elementService, Staircase $staircase, $projectVariantId = null)
    {
        $this->staircase = $staircase;
        $this->projectVariantId = $projectVariantId;

        $this->log = Log::getInstance();
        $this->elementService = $elementService;
    }

    /**
     * @return ElcaElement
     * @throws \Exception
     */
    public function create($platformConstructionElementId = null, $platformCoverElementId = null)
    {
        $elements = [];
        $dbh = DbHandle::getInstance();

        try
        {
            $dbh->begin();

            $compositeElement = $this->createCompositeElement();
            $elements[self::IDENT_CONSTRUCTION] = $this->createConstructionElement($compositeElement->getId());
            $elements[self::IDENT_COVER_RISER] = $this->createCoverRiserElement($compositeElement->getId());

            if ($this->staircase->hasPlatform()) {
                if ($element = $this->createPlatformConstructionElement($compositeElement->getId(), $platformConstructionElementId))
                    $elements[self::IDENT_PLATFORM_CONSTRUCTION] = $element;
                if ($element = $this->createPlatformCoverElement($compositeElement->getId(), $platformCoverElementId)) {
                    $elements[self::IDENT_PLATFORM_COVER] = $element;
                }
            }

            /**
             * Create new assignments
             */
            $position = 0;
            foreach ($elements as $element) {
                ElcaCompositeElement::create($compositeElement->getId(), ++$position, $element->getId());
            }

            $dbh->commit();

        } catch(\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $compositeElement;
    }

    /**
     * @param ElcaElement $compositeElement
     * @return ElcaElement
     * @throws \Exception
     */
    public function update(ElcaElement $compositeElement, $platformConstructionElementId = null, $platformCoverElementId = null)
    {
        /** @var ElcaElement[] $elements */
        $elements = [];
        $dbh = DbHandle::getInstance();
        try
        {
            $dbh->begin();

            if ($this->staircase->getName() !== $compositeElement->getName()) {
                $compositeElement->setName($this->staircase->getName());
                $compositeElement->update();
            }

            // update
            $compositeElementSet = $compositeElement->getCompositeElements(null, true);

            /** @var ElcaElementAttribute $assignment */
            foreach ($compositeElementSet as $assignment) {
                $attr = $assignment->getElement()->getAttribute(StaircaseAssistant::IDENT);

                if ($attr->isInitialized())
                    $elements[$attr->getTextValue()] = $assignment->getElement();
            }

            if (isset($elements[self::IDENT_CONSTRUCTION]))
                $this->updateConstructionElement($elements[self::IDENT_CONSTRUCTION]);
            else {
                $element = $this->createConstructionElement($compositeElement->getId());
                ElcaCompositeElement::create($compositeElement->getId(), ElcaCompositeElement::getMaxCompositePosition($compositeElement->getId()) + 1, $element->getId());
            }

            if (isset($elements[self::IDENT_COVER_RISER]))
                $this->updateCoverRiserElement($elements[self::IDENT_COVER_RISER]);
            else {
                $element = $this->createCoverRiserElement($compositeElement->getId());
                ElcaCompositeElement::create($compositeElement->getId(), ElcaCompositeElement::getMaxCompositePosition($compositeElement->getId()) + 1, $element->getId());
            }

            if ($this->staircase->hasPlatform()) {
                if (isset($elements[self::IDENT_PLATFORM_CONSTRUCTION]))
                    $this->updatePlatformConstructionElement($elements[self::IDENT_PLATFORM_CONSTRUCTION], $platformConstructionElementId);
                else {
                    if ($element = $this->createPlatformConstructionElement($compositeElement->getId(), $platformConstructionElementId))
                        ElcaCompositeElement::create($compositeElement->getId(), ElcaCompositeElement::getMaxCompositePosition($compositeElement->getId()) + 1, $element->getId());
                }

                if (isset($elements[self::IDENT_PLATFORM_COVER]))
                    $this->updatePlatformCoverElement($elements[self::IDENT_PLATFORM_COVER], $platformCoverElementId);
                else {
                    if ($element = $this->createPlatformCoverElement($compositeElement->getId(), $platformCoverElementId))
                        ElcaCompositeElement::create($compositeElement->getId(), ElcaCompositeElement::getMaxCompositePosition($compositeElement->getId()) + 1, $element->getId());
                }
            } else {
                if (isset($elements[self::IDENT_PLATFORM_CONSTRUCTION])) {
                    $element = $elements[self::IDENT_PLATFORM_CONSTRUCTION];
                    $this->deletePlatformElement($element);
                    $this->log->debug('Platform construction element `' . $element->getName() . ' deleted');
                }

                if (isset($elements[self::IDENT_PLATFORM_COVER])) {
                    $element = $elements[self::IDENT_PLATFORM_COVER];
                    $this->deletePlatformElement($element);
                    $this->log->debug('Platform cover element `' . $element->getName() . ' deleted');
                }
            }

            //$compositeElement->reindexCompositeElements();
            $dbh->commit();
        } catch(\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $compositeElement;
    }

    /**
     * @param $components
     * @param $needle
     * @return mixed
     */
    protected function findComponentByAttributeValue($components, $needle)
    {
        foreach ($components as $component) {
            if ($needle === ElcaElementComponentAttribute::findValue($component->getId(), self::IDENT_COVER_RISER)) {
                return $component;
            }
        }

        return null;
    }

    /**
     * @return ElcaElement
     */
    private function createCompositeElement()
    {
        return ElcaElement::create(
            ElcaElementType::findByIdent(self::DIN_CEILINGS)->getNodeId(),
            $this->staircase->getName(),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            1, // quantity
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );
    }


    /**
     * @param $compositeElementId
     * @return ElcaElement
     */
    private function createConstructionElement($compositeElementId)
    {
        $name = '%s / Konstruktion';
        $compositeElement = ElcaElement::findById($compositeElementId);
        $qty = $compositeElement->getQuantity();
        $unit = Elca::UNIT_STK;

        switch ($this->staircase->getType()) {
            case Staircase::TYPE_SOLID:
                $name = self::NAME_SOLID_SLAB;
                break;
            case Staircase::TYPE_STRINGER:
                $name = self::NAME_STRINGER;
                $qty *= $this->staircase->getConstruction()->getAmount();
                $unit = Elca::UNIT_STK;
                break;
            case Staircase::TYPE_MIDDLE_HOLM:
                $name = self::NAME_MIDDLE_HOLM;
                $unit = Elca::UNIT_M;
                break;
        }

        $element = ElcaElement::create(
            ElcaElementType::findByIdent(self::DIN_CONSTRUCTION)->getNodeId(),
            sprintf($name, $this->staircase->getName()),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $qty, // quantity
            $unit,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        $this->createElementAttribute($element->getId(), self::IDENT_CONSTRUCTION);

        $this->log->debug('Construction element `'. $element->getName() .'\'['. $element->getId().'] created');

        switch ($this->staircase->getType()) {
            case Staircase::TYPE_SOLID:
                /** @var Solid $solid */
                $solid = $this->staircase->getConstruction();
                foreach ($solid->getMaterials() as $material) {
                    $this->createComponent(
                        $element->getId(),
                        $material->getMaterialId(),
                        $solid->getVolume() * $material->getShare(),
                        Elca::UNIT_M3
                    );
                }
                break;

            case Staircase::TYPE_STRINGER:
                /** @var Stringer $stringer */
                $stringer = $this->staircase->getConstruction();
                $this->createComponent(
                    $element->getId(),
                    $stringer->getMaterial()->getMaterialId(),
                    $stringer->getVolume(true),
                    Elca::UNIT_M3
                );
                break;
            case Staircase::TYPE_MIDDLE_HOLM:
                /** @var MiddleHolm $holm */
                $holm = $this->staircase->getConstruction();
                $this->createComponent(
                    $element->getId(),
                    $holm->getMaterial()->getMaterialId(),
                    $holm->getVolume(),
                    Elca::UNIT_M3
                );
                break;
        }

        return $element;
    }

    /**
     * @param $element
     */
    public function updateConstructionElement(ElcaElement $element)
    {
        // find marked components
        $components = ElcaElementComponentSet::findByElementIdAndAttributeIdent($element->getId(), StaircaseAssistant::IDENT);
        $componentsToDelete = $components->getArrayBy('id', 'id');
        $eltName = '%s / Konstruktion';

        $compositeElement = $element->getCompositeElement();
        $eltQuantity = $compositeElement->getQuantity();

        switch ($this->staircase->getType()) {
            case Staircase::TYPE_SOLID:
                $eltName = self::NAME_SOLID_SLAB;
                $solid = $this->staircase->getConstruction();
                foreach ($solid->getMaterials() as $material) {
                    if (!$material->getMaterialId())
                        continue;

                    if ($component = $components->search('processConfigId', $material->getMaterialId())) {
                        $component = $this->updateComponent(
                            $component,
                            $material->getMaterialId(),
                            $solid->getVolume() * $material->getShare()
                        );
                    } else {
                        $component = $this->createComponent(
                            $element->getId(),
                            $material->getMaterialId(),
                            $solid->getVolume() * $material->getShare(),
                            Elca::UNIT_M3
                        );
                    }
                    unset($componentsToDelete[$component->getId()]);
                }
                break;
            case Staircase::TYPE_STRINGER:
                $eltName = self::NAME_STRINGER;
                /** @var Stringer $stringer */
                $stringer = $this->staircase->getConstruction();
                $eltQuantity *= $stringer->getAmount();

                $material = $stringer->getMaterial();
                if ($component = $components->search('processConfigId', $material->getMaterialId())) {
                    $component = $this->updateComponent(
                        $component,
                        $material->getMaterialId(),
                        $stringer->getVolume(true)
                    );
                } else {
                    $component = $this->createComponent(
                        $element->getId(),
                        $material->getMaterialId(),
                        $stringer->getVolume(true),
                        Elca::UNIT_M3
                    );
                }
                unset($componentsToDelete[$component->getId()]);
                break;
            case Staircase::TYPE_MIDDLE_HOLM:
                $eltName = self::NAME_MIDDLE_HOLM;
                /** @var MiddleHolm $holm */
                $holm = $this->staircase->getConstruction();
                $material = $holm->getMaterial();
                if ($component = $components->search('processConfigId', $material->getMaterialId())) {
                    $component = $this->updateComponent(
                        $component,
                        $material->getMaterialId(),
                        $holm->getVolume()
                    );
                } else {
                    $component = $this->createComponent(
                        $element->getId(),
                        $material->getMaterialId(),
                        $holm->getVolume(),
                        Elca::UNIT_M3
                    );
                }
                unset($componentsToDelete[$component->getId()]);
                break;
        }

        // delete unseen components
        foreach ($componentsToDelete as $componentId) {
            $component = ElcaElementComponent::findById($componentId);
            $this->log->debug('Component `'. $component->getProcessConfig()->getName() .'\' removed');

            $component->delete();
        }

        $eltName = sprintf($eltName, $this->staircase->getName());

        if ($element->getName() !== $eltName ||
            $element->getQuantity() !== $eltQuantity) {
            $element->setName($eltName);
            $element->setQuantity($eltQuantity);
            $element->update();
        }
    }

    /**
     * @param $compositeElementId
     * @return ElcaElement
     */
    private function createCoverRiserElement($compositeElementId)
    {
        $name = $this->staircase->getSteps()->getStep()->hasRiser()
            ? self::NAME_COVER_AND_RISER
            : self::NAME_COVER;

        $compositeElement = ElcaElement::findById($compositeElementId);
        $qty = $compositeElement->getQuantity();

        $element = ElcaElement::create(
            ElcaElementType::findByIdent(self::DIN_CONSTRUCTION)->getNodeId(),
            sprintf($name, $this->staircase->getName()),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $qty * $this->staircase->getSteps()->getAmount(), // quantity
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        $this->createElementAttribute($element->getId(), self::IDENT_COVER_RISER);
        $this->log->debug('Cover & Riser element `'. $element->getName() .'\'['. $element->getId().'] created');

        $cover = $this->staircase->getSteps()->getStep()->getCover();
        if ($cover->isTrapezoid()) {
            $this->createComponent(
                $element->getId(),
                $cover->getMaterial()->getMaterialId(),
                $cover->getVolume(),
                Elca::UNIT_M3,
                self::IDENT_COVER
            );
        } else {
            $this->createLayer(
                $element->getId(),
                $cover->getMaterial()->getMaterialId(),
                1,
                $cover->getSize(),
                $cover->getWidth(),
                $cover->getLength1(),
                self::IDENT_COVER
            );
        }
        $riser = $this->staircase->getSteps()->getStep()->getRiser();
        if ($riser && $riser->getMaterial()->getMaterialId()) {
            $this->createLayer(
                $element->getId(),
                $riser->getMaterial()->getMaterialId(),
                1,
                $riser->getSize(),
                $riser->getWidth(),
                $riser->getHeight(),
                self::IDENT_RISER
            );
        }

        return $element;
    }

    /**
     * @param $element
     */
    public function updateCoverRiserElement(ElcaElement $element)
    {
        // find marked components
        $components = ElcaElementComponentSet::findByElementIdAndAttributeIdent($element->getId(), StaircaseAssistant::IDENT);
        $componentsToDelete = $components->getArrayBy('id', 'id');

        $cover = $this->staircase->getSteps()->getStep()->getCover();

        if ($component = $this->findComponentByAttributeValue($components, self::IDENT_COVER)) {
            if ($cover->isTrapezoid()) {
                $component = $this->updateComponent(
                    $component,
                    $cover->getMaterial()->getMaterialId(),
                    $cover->getVolume()
                );
            } else {
                $component = $this->updateLayer(
                    $component,
                    $cover->getMaterial()->getMaterialId(),
                    $cover->getSize(),
                    $cover->getWidth(),
                    $cover->getLength1()
                );
            }
        } else {
            if ($cover->isTrapezoid()) {
                $component = $this->createComponent(
                    $element->getId(),
                    $cover->getMaterial()->getMaterialId(),
                    $cover->getVolume(),
                    Elca::UNIT_M3,
                    self::IDENT_COVER
                );
            } else {
                $component = $this->createLayer(
                    $element->getId(),
                    $cover->getMaterial()->getMaterialId(),
                    1,
                    $cover->getSize(),
                    $cover->getWidth(),
                    $cover->getLength1(),
                    self::IDENT_COVER
                );
            }
        }
        unset($componentsToDelete[$component->getId()]);

        $riser = $this->staircase->getSteps()->getStep()->getRiser();
        if ($riser && $riser->getMaterial()->getMaterialId()) {
            if ($component = $this->findComponentByAttributeValue($components, self::IDENT_RISER)) {
                $component = $this->updateLayer(
                    $component,
                    $riser->getMaterial()->getMaterialId(),
                    $riser->getSize(),
                    $riser->getWidth(),
                    $riser->getHeight()
                );
            } else {
                $component = $this->createLayer(
                    $element->getId(),
                    $riser->getMaterial()->getMaterialId(),
                    1,
                    $riser->getSize(),
                    $riser->getWidth(),
                    $riser->getHeight(),
                    self::IDENT_RISER
                );
            }

            unset($componentsToDelete[$component->getId()]);
        }

        // delete unseen components
        foreach ($componentsToDelete as $componentId) {
            $component = ElcaElementComponent::findById($componentId);
            $this->log->debug('Component `'. $component->getProcessConfig()->getName() .'\' removed');

            $component->delete();
        }

        $eltName = $this->staircase->getSteps()->getStep()->hasRiser()
            ? self::NAME_COVER_AND_RISER
            : self::NAME_COVER;

        $compositeElement = $element->getCompositeElement();
        $qty = $compositeElement->getQuantity() * $this->staircase->getSteps()->getAmount();

        $eltName = sprintf($eltName, $this->staircase->getName());
        if ($element->getName() !== $eltName ||
            $element->getQuantity() !== $qty) {
            $element->setName($eltName);
            $element->setQuantity($qty);
            $element->update();
        }
    }

    /**
     * Despite the name, this method does NOT create a new element. It either copies from a template element
     * or uses a previously selected project element
     *
     * @param $compositeElementId
     * @return ElcaElement
     */
    private function createPlatformConstructionElement($compositeElementId, $platformElementId)
    {
        /**
         * Get the selected element. This can be either a template or a project element. In the
         * latter case it hasn't to be created
         */
        $platformElement = ElcaElement::findById($platformElementId);

        if (!$platformElement->isInitialized())
            return null;

        $elca = Elca::getInstance();
        $description = $platformElement->getName();

        if ($platformElement->isTemplate()) {
            $platformElement = $this->elementService->copyElementFrom(
                $platformElement,
                ElcaAccess::getInstance()->getUserId(),
                $elca->getProjectVariantId(),
                $elca->getProject()->getAccessGroupId(),
                true
            );

            // fix element id
            //$this->staircase->getPlatform()->setConstructionElementId($platformElement->getId());
        }

        $platformElement->setName(
            sprintf(
                self::NAME_PLATFORM_CONSTRUCTION,
                $this->staircase->getName()
            )
        );

        $compositeElement = ElcaElement::findById($compositeElementId);
        $qty = $compositeElement->getQuantity() * $this->staircase->getPlatform()->getAmount() * $this->staircase->getPlatform()->getArea();

        $platformElement->setQuantity($qty);
        $platformElement->setDescription($description);
        $platformElement->update();

        $this->createElementAttribute($platformElement->getId(), self::IDENT_PLATFORM_CONSTRUCTION);
        $this->log->debug('Platform construction element `'. $platformElement->getName() .'\'['. $platformElement->getId().'] created');

        return $platformElement;
    }

    /**
     * @param ElcaElement $element
     */
    private function updatePlatformConstructionElement(ElcaElement $platformElement, $platformElementId)
    {
        if (!$this->staircase->hasPlatform() || !$platformElementId) {
            $this->deletePlatformElement($platformElement);
            return;
        }

        /**
         * check if the element has been replaced
         */
        if ($platformElementId !== $platformElement->getId()) {
            $compositeElementId = $platformElement->getCompositeElement()->getId();
            $this->deletePlatformElement($platformElement);

            $element = $this->createPlatformConstructionElement($compositeElementId, $platformElementId);
            ElcaCompositeElement::create($compositeElementId, ElcaCompositeElement::getMaxCompositePosition($compositeElementId) + 1, $element->getId());
            return;
        }

        $platformElement->setName(
            sprintf(
                self::NAME_PLATFORM_CONSTRUCTION,
                $this->staircase->getName()
            )
        );

        $compositeElement = $platformElement->getCompositeElement();
        $qty = $compositeElement->getQuantity() * $this->staircase->getPlatform()->getAmount() * $this->staircase->getPlatform()->getArea();

        $platformElement->setQuantity($qty);
        $platformElement->update();

        $this->log->debug('Platform construction element `'. $platformElement->getName() .'\'['. $platformElement->getId().'] updated');
    }



    /**
     * Despite the name, this method does NOT create a new element. It either copies from a template element
     * or uses a previously selected project element
     *
     * @param $compositeElementId
     * @return ElcaElement|null
     * @throws \Exception
     */
    private function createPlatformCoverElement($compositeElementId, $platformElementId)
    {
        /**
         * Get the selected element. This can be either a template or a project element. In the
         * latter case it hasn't to be created
         */
        $platformElement = ElcaElement::findById($platformElementId);

        if (!$platformElement->isInitialized())
            return null;

        $elca = Elca::getInstance();
        $description = $platformElement->getName();

        if ($platformElement->isTemplate()) {
            $platformElement = $this->elementService->copyElementFrom(
                $platformElement,
                ElcaAccess::getInstance()->getUserId(),
                $elca->getProjectVariantId(),
                $elca->getProject()->getAccessGroupId(),
                true
            );

            // fix element id
            //$this->staircase->getPlatform()->setCoverElementId($platformElement->getId());
        }

        $platformElement->setName(
            sprintf(
                self::NAME_PLATFORM_COVER,
                $this->staircase->getName()
            )
        );
        $compositeElement = ElcaElement::findById($compositeElementId);
        $qty = $compositeElement->getQuantity() * $this->staircase->getPlatform()->getAmount() * $this->staircase->getPlatform()->getArea();

        $platformElement->setQuantity($qty);
        $platformElement->setDescription($description);
        $platformElement->update();

        $this->createElementAttribute($platformElement->getId(), self::IDENT_PLATFORM_COVER);
        $this->log->debug('Platform cover element `'. $platformElement->getName() .'\'['. $platformElement->getId().'] created');

        return $platformElement;
    }

    /**
     * @param ElcaElement $platformElement
     */
    private function updatePlatformCoverElement(ElcaElement $platformElement, $platformElementId)
    {
        if (!$this->staircase->hasPlatform() || !$platformElementId) {
            $this->deletePlatformElement($platformElement);
            return;
        }

        /**
         * check if the element has been replaced
         */
        if ($platformElementId !== $platformElement->getId()) {
            $compositeElementId = $platformElement->getCompositeElement()->getId();
            $this->deletePlatformElement($platformElement);
            $element = $this->createPlatformCoverElement($compositeElementId, $platformElementId);
            ElcaCompositeElement::create($compositeElementId, ElcaCompositeElement::getMaxCompositePosition($compositeElementId) + 1, $element->getId());
            return;
        }

        $platformElement->setName(
            sprintf(
                self::NAME_PLATFORM_COVER,
                $this->staircase->getName()
            )
        );
        $compositeElement = $platformElement->getCompositeElement();
        $qty = $compositeElement->getQuantity() * $this->staircase->getPlatform()->getAmount() * $this->staircase->getPlatform()->getArea();

        $platformElement->setQuantity($qty);
        $platformElement->update();

        $this->log->debug('Platform cover element `'. $platformElement->getName() .'\'['. $platformElement->getId().'] updated');
    }

    /**
     * Checks if the element is a template element and deletes it if not
     *
     * @param ElcaElement $platformElement
     */
    private function deletePlatformElement(ElcaElement $platformElement)
    {
        if (!$platformElement->isTemplate()) {
            $platformElement->delete();

            $this->log->debug('Platform element `'. $platformElement->getName() .' deleted');
        } else {
            $this->log->debug('Platform element `'. $platformElement->getName() .' NOT deleted, because it is a template element');
        }
    }

    /**
     * @param      $elementId
     * @param      $materialId
     * @param      $amount
     * @param      $unit
     * @param null $componentIdentValue
     * @return ElcaElementComponent
     */
    private function createComponent($elementId, $materialId, $amount, $unit, $componentIdentValue = null)
    {
        $processConfig = ElcaProcessConfig::findById($materialId);
        $conversionId = $this->getComponentConversionId($processConfig->getId(), $unit);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $amount
        );

        $this->createComponentAttribute($component, StaircaseAssistant::IDENT, $componentIdentValue);

        $this->log->debug('Component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $component;
    }

    /**
     * @param $componentId
     * @param $materialId
     * @param $amount
     */
    private function updateComponent(ElcaElementComponent $component, $materialId, $amount)
    {
        $processConfig = ElcaProcessConfig::findById($materialId);

        $component->setProcessConfigId($materialId);
        $component->setLifeTime($processConfig->getDefaultLifeTime());
        $component->setQuantity($amount);
        $component->update();

        $this->log->debug('Component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');

        return $component;
    }

    /**
     * @param     $elementId
     * @param     $materialId
     * @param     $position
     * @param     $size
     * @param int $length
     * @param int $width
     * @return ElcaElementComponent
     */
    private function createLayer($elementId, $materialId, $position, $size, $length = 1, $width = 1, $componentIdentValue = null)
    {
        $processConfig = ElcaProcessConfig::findById($materialId);
        $conversionId = $this->getLayerConversionId($processConfig->getId());

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $size,
            null,
            1,
            $length,
            $width
        );

        $this->createComponentAttribute($component, StaircaseAssistant::IDENT, $componentIdentValue);

        $this->log->debug('Layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $component;
    }

    /**
     * @param ElcaElementComponent $component
     * @param                      $materialId
     * @param                      $size
     * @param int                  $length
     * @param int                  $width
     * @return ElcaElementComponent
     */
    private function updateLayer(ElcaElementComponent $component, $materialId, $size, $length = 1, $width = 1)
    {
        $processConfig = ElcaProcessConfig::findById($materialId);

        $component->setProcessConfigId($materialId);
        $component->setLifeTime($processConfig->getDefaultLifeTime());
        $component->setLayerSize($size);
        $component->setLayerLength($length);
        $component->setLayerWidth($width);
        $component->update();

        $this->log->debug('Layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');

        return $component;
    }


    /**
     * @param $processConfigId
     * @return mixed
     */
    private function getLayerConversionId($processConfigId)
    {
        $conversionSet = ElcaProcessConversionSet::findByProcessConfigIdAndInUnit($processConfigId, 'm3', ['id' => 'ASC'], 1);

        if ($conversionSet->count())
            return $conversionSet[0]->getId();

        return null;
    }

    /**
     * @param $processConfigId
     * @return mixed
     */
    private function getComponentConversionId($processConfigId, $inUnit)
    {
        $processConfig = ElcaProcessConfig::findById($processConfigId);

        if ($processConfig->isInitialized()) {
            list($requiredConversions, $availableConversions) = $processConfig->getRequiredConversions();
            $units = array_flip(array_unique($requiredConversions->getArrayBy('inUnit', 'id') + $availableConversions->getArrayBy('inUnit', 'id')));

            if (!is_array($inUnit))
                $inUnit = [$inUnit];

            foreach ($inUnit as $unit) {
                if (isset($units[$unit]))
                    return $units[$unit];
            }
        }
        return null;
    }

    /**
     * @param $elementId
     * @param $ident
     */
    private function createElementAttribute($elementId, $ident)
    {
        // save attribute
        ElcaElementAttribute::create($elementId,
                                     StaircaseAssistant::IDENT,
                                     StaircaseAssistant::IDENT,
                                     null,
                                     $ident
        );
    }


    /**
     * @param ElcaElementComponent $component
     * @param string               $ident
     * @param null                 $additionalValue
     * @return ElcaElementComponent
     */
    private function createComponentAttribute(ElcaElementComponent $component, $ident = StaircaseAssistant::IDENT, $additionalValue = null)
    {
        ElcaElementComponentAttribute::create($component->getId(), $ident, null, $additionalValue);

        return $component;
    }

    /**
     * @param $staircaseElementId
     * @param $ident
     * @return ElcaElement|null
     */
    private function findElementByAttribute($staircaseElementId, $ident)
    {
        $attrSet = ElcaElementAttributeSet::find([
            'ident' => StaircaseAssistant::IDENT,
            'text_value' => $ident,
            'numeric_value' => $staircaseElementId
        ], ['id' => 'ASC'], 1);

        if (!$attrSet->count())
            return null;

        return $attrSet[0]->getElement();
    }
}
