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

namespace Elca\Model\Assistant\Window;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Exception;
use Beibob\Blibs\Log;
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
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\Window\DormerAssistant;

/**
 * Assembler
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class DormerAssembler
{
    const DIN_ROOFS = '360';
    const DIN_WINDOWS = '362';
    const DIN_OUTER_WALL_CLADDING = '369';
    const DIN_INNER_WALL_CLADDING = '369';
    const DIN_SUNSCREEN = '369';

    const IDENT_WINDOW = 'dormer';
    const IDENT_INDOOR_SILL = 'indoor_sill';
    const IDENT_OUTDOOR_SILL = 'outdoor_sill';
    const IDENT_INDOOR_SOFFIT = 'indoor_soffit';
    const IDENT_OUTDOOR_SOFFIT= 'outdoor_soffit';
    const IDENT_INDOOR_SUNSCREEN = 'indoor_sunscreen';
    const IDENT_OUTDOOR_SUNSCREEN = 'outdoor_sunscreen';

    const NAME_SILL = '%s / Fensterbank %s';
    const NAME_SOFFIT = '%s / Laibung %s';
    const NAME_INDOOR = 'innen';
    const NAME_OUTDOOR = 'außen';
    const NAME_INDOOR_SUNSCREEN = '%s / Blendschutz';
    const NAME_OUTDOOR_SUNSCREEN = '%s / Sonnenschutz';


    /**
     * @var Window
     */
    private $window;

    /**
     * @var null
     */
    private $projectVariantId;

    /**
     * @var Log
     */
    private $log;

    /**
     * @param Window $window
     * @param null   $projectVariantId
     */
    public function __construct(Window $window, $projectVariantId = null)
    {
        $this->window = $window;
        $this->projectVariantId = $projectVariantId;

        $this->log = Log::getInstance();
    }

    /**
     * @return ElcaElement
     * @throws \Exception
     */
    public function create()
    {
        $dbh = DbHandle::getInstance();
        try
        {
            $dbh->begin();

            $windowElement = $this->createWindowElement();

            if ($this->window->hasSillIndoor())
                $this->createSillElement($windowElement, true);

            if ($this->window->hasSillOutdoor())
                $this->createSillElement($windowElement, false);

            if ($this->window->hasSoffitIndoor())
                $this->createSoffitElement($windowElement, true);

            if ($this->window->hasSoffitOutdoor())
                $this->createSoffitElement($windowElement, false);

            if ($this->window->hasSunscreenIndoor())
                $this->createSunScreenElement($windowElement, true);

            if ($this->window->hasSunscreenOutdoor())
                $this->createSunScreenElement($windowElement, false);

            $dbh->commit();

        } catch(\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $windowElement;
    }

    /**
     * @param ElcaElement $windowElement
     * @return ElcaElement
     * @throws Exception
     */
    public function update(ElcaElement $windowElement)
    {
        $dbh = DbHandle::getInstance();
        try
        {
            $dbh->begin();

            // window
            $this->updateWindowElement($windowElement);

            // indoor sill
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_INDOOR_SILL);
            if ($this->window->hasSillIndoor()) {
                if ($element)
                    $this->updateSillElement($element, true);
                else
                    $this->createSillElement($windowElement, true);
            }
            else {
                if ($element)
                    $element->delete();
            }

            // outdoor sill
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_OUTDOOR_SILL);
            if ($this->window->hasSillOutdoor()) {
                if ($element) {
                    $this->updateSillElement($element, false);
                } else {
                    $this->createSillElement($windowElement, false);
                }
            }
            else {
                if ($element)
                    $element->delete();
            }
            // indoor soffit
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_INDOOR_SOFFIT);
            if ($this->window->hasSoffitIndoor()) {
                if ($element) {
                    $this->updateSoffitElement($element, true);
                } else {
                    $this->createSoffitElement($windowElement, true);
                }
            }
            else {
                if ($element)
                    $element->delete();
            }

            // outdoor soffit
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_OUTDOOR_SOFFIT);
            if ($this->window->hasSoffitOutdoor()) {
                if ($element) {
                    $this->updateSoffitElement($element, false);
                } else {
                    $this->createSoffitElement($windowElement, false);
                }
            }
            else {
                if ($element)
                    $element->delete();
            }

            // indoor sunscreen
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_INDOOR_SUNSCREEN);
            if ($this->window->hasSunscreenIndoor()) {
                if ($element) {
                    $this->updateSunScreenElement($element, true);
                } else {
                    $this->createSunScreenElement($windowElement, true);
                }
            }
            else {
                if ($element)
                    $element->delete();
            }

            // outdoor sunscreen
            $element = $this->findElementByAttribute($windowElement->getId(), self::IDENT_OUTDOOR_SUNSCREEN);
            if ($this->window->hasSunscreenOutdoor()) {
                if ($element) {
                    $this->updateSunScreenElement($element, false);
                } else {
                    $this->createSunScreenElement($windowElement, false);
                }
            }
            else {
                if ($element)
                    $element->delete();
            }

            $dbh->commit();
        } catch(\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $windowElement;
    }

    /**
     * @param $compositeElementId
     * @return ElcaElement
     */
    private function createWindowElement()
    {
        $windowElement = ElcaElement::create(
            ElcaElementType::findByIdent(self::DIN_WINDOWS)->getNodeId(),
            $this->window->getName(),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            1, // quantity
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        $this->log->debug('Window element `'. $windowElement->getName() .'\'['. $windowElement->getId().'] created');

        $this->createSealingLayer($windowElement->getId());
        $this->createFixedFrameComponent($windowElement->getId());
        $this->createSashFrameComponent($windowElement->getId());
        $this->createGlassComponent($windowElement->getId());

        $this->createFittingsComponent($windowElement->getId());
        $this->createHandlesComponent($windowElement->getId());

        return $windowElement;
    }

    /**
     * @param ElcaElement $windowElement
     * @return ElcaElement
     */
    private function updateWindowElement(ElcaElement $windowElement)
    {
        $windowElement->setName($this->window->getName());
        $windowElement->update();

        $this->updateElementComponents(
            $windowElement->getId(),
            function ($windowElementId, ElcaElementComponentSet $components) {

                $updatedComponents = [];

                if ($component = $components->search('processConfigId', $this->window->getSealing()->getMaterialId())) {
                    $this->updateSealingLayer($component);
                } else {
                    $component = $this->createSealingLayer($windowElementId);
                }
                $updatedComponents[] = $component;

                if ($component = $components->search('processConfigId', $this->window->getFixedFrame()->getMaterialId())) {
                    $this->updateFixedFrameComponent($component);
                    $updatedComponents[] = $component;
                } else {
                    $this->createFixedFrameComponent($windowElementId);
                }

                if ($this->window->getFixedFrame()->getSashFrameMaterialId()) {
                    if ($component = $components->search('processConfigId', $this->window->getFixedFrame()->getSashFrameMaterialId())) {
                        $this->updateSashFrameComponent($component);
                    } else {
                        $component = $this->createSashFrameComponent($windowElementId);
                    }
                    $updatedComponents[] = $component;
                }
                if ($this->window->getGlassMaterialId()) {
                    if ($component = $components->search('processConfigId', $this->window->getGlassMaterialId())) {
                        $this->updateGlassComponent($component);
                    } else {
                        $component = $this->createGlassComponent($windowElementId);
                    }
                    $updatedComponents[] = $component;
                }
                if ($fittings = $this->window->getFittings()) {
                    if ($component = $components->search('processConfigId', $fittings->getMaterialId())) {
                        $this->updateFittingsComponent($component);
                    } else {
                        $component = $this->createFittingsComponent($windowElementId);
                    }
                    $updatedComponents[] = $component;
                }
                if ($handles = $this->window->getHandles()) {
                    if ($component = $components->search('processConfigId', $handles->getMaterialId())) {
                        $this->updateHandlesComponent($component);
                    } else {
                        $component = $this->createHandlesComponent($windowElementId);
                    }
                    $updatedComponents[] = $component;
                }

                return $updatedComponents;
            }
        );

        return $windowElement;
    }



    /**
     * @param     $elementId
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createSealingLayer($elementId, $position = 1)
    {
        if (!$sealing = $this->window->getSealing())
            return null;

        $sealingProcessConfig= ElcaProcessConfig::findById($sealing->getMaterialId());
        $conversionId = $this->getLayerConversionId($sealingProcessConfig->getId());


        $sealingLayer = ElcaElementComponent::create(
            $elementId,
            $sealing->getMaterialId(),
            $conversionId,
            $sealingProcessConfig->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $this->window->getSealingSize(),
            null,
            $sealing->getRatio(),
            $sealing->getWidth(),
            $sealing->getHeight()
        );

        $this->log->debug('Sealing layer `'. $sealingLayer->getProcessConfig()->getName() .'\'['. $sealingLayer->getId().'] created');

        return $this->createComponentAttribute($sealingLayer);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateSealingLayer(ElcaElementComponent $component)
    {
        $sealing = $this->window->getSealing();
        $component->setProcessConfigId($sealing->getMaterialId());
        $component->setLayerAreaRatio($sealing->getRatio());
        $component->setLayerLength($sealing->getWidth());
        $component->setLayerWidth($sealing->getHeight());
        $component->update();

        $this->log->debug('Sealing layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }

    /**
     * @param     $elementId
     * @return ElcaElementComponent
     */
    private function createFixedFrameComponent($elementId)
    {
        $fixedFrame = $this->window->getFixedFrame();
        $processConfig = ElcaProcessConfig::findById($fixedFrame->getMaterialId());
        $conversionId = $this->getComponentConversionId($processConfig->getId(), Elca::UNIT_M);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $fixedFrame->getLength()
        );

        $this->log->debug('Fixed frame component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateFixedFrameComponent(ElcaElementComponent $component)
    {
        $fixedFrame = $this->window->getFixedFrame();
        $component->setProcessConfigId($fixedFrame->getMaterialId());
        $component->setQuantity( $fixedFrame->getLength());
        $component->update();

        $this->log->debug('Fixed frame component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }

    /**
     * @param     $elementId
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createSashFrameComponent($elementId)
    {
        if (!$this->window->getFixedFrame()->getSashFrameMaterialId())
            return null;

        $processConfig = ElcaProcessConfig::findById($this->window->getFixedFrame()->getSashFrameMaterialId());
        $conversionId = $this->getComponentConversionId($processConfig->getId(), Elca::UNIT_M);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $this->window->getFixedFrame()->getSashFramesLength()
        );

        $this->log->debug('Sash frame component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateSashFrameComponent(ElcaElementComponent $component)
    {
        $component->setProcessConfigId($this->window->getFixedFrame()->getSashFrameMaterialId());
        $component->setQuantity($this->window->getFixedFrame()->getSashFramesLength());
        $component->update();

        $this->log->debug('Sash frame component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }

    /**
     * @param     $elementId
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createGlassComponent($elementId)
    {
        if (!$this->window->getGlassMaterialId())
            return null;

        $processConfig = ElcaProcessConfig::findById($this->window->getGlassMaterialId());
        $conversionId = $this->getComponentConversionId($processConfig->getId(), Elca::UNIT_M2);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $this->window->getGlassArea()
        );

        $this->log->debug('Glass component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateGlassComponent(ElcaElementComponent $component)
    {
        $component->setProcessConfigId($this->window->getGlassMaterialId());
        $component->setQuantity($this->window->getGlassArea());

        $component->update();

        $this->log->debug('Glass component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }


    /**
     * @param $elementId
     * @return ElcaElementComponent|null
     */
    private function createFittingsComponent($elementId)
    {
        if (!$fittings = $this->window->getFittings())
            return null;

        $processConfig = ElcaProcessConfig::findById($fittings->getMaterialId());
        $conversionId = $this->getComponentConversionId($processConfig->getId(), [Elca::UNIT_STK, 'Stück']);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $fittings->getQuantity()
        );

        $this->log->debug('Fittings component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute(
            $component
        );
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateFittingsComponent(ElcaElementComponent $component)
    {
        $fittings = $this->window->getFittings();
        $component->setProcessConfigId($fittings->getMaterialId());
        $component->setQuantity($fittings->getQuantity());
        $component->update();

        $this->log->debug('Fittings component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }


    /**
     * @param $elementId
     * @return ElcaElementComponent|null
     */
    private function createHandlesComponent($elementId)
    {
        if (!$handles = $this->window->getHandles())
            return null;

        $processConfig = ElcaProcessConfig::findById($handles->getMaterialId());
        $conversionId = $this->getComponentConversionId($processConfig->getId(), [Elca::UNIT_STK, 'Stück']);

        $component = ElcaElementComponent::create(
                $elementId,
                $processConfig->getId(),
                $conversionId,
                $processConfig->getDefaultLifeTime(),
                false,
                $handles->getQuantity()
            );

        $this->log->debug('Handles component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateHandlesComponent(ElcaElementComponent $component)
    {
        $handles = $this->window->getHandles();
        $component->setProcessConfigId($handles->getMaterialId());
        $component->setQuantity($handles->getQuantity());
        $component->update();

        $this->log->debug('Handles component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }

    /**
     * @param ElcaElement $windowElement
     * @param bool        $indoor
     * @return ElcaElement
     */
    private function createSillElement(ElcaElement $windowElement, $indoor = false)
    {
        $element = ElcaElement::create(
            ElcaElementType::findByIdent($indoor? self::DIN_INNER_WALL_CLADDING : self::DIN_OUTER_WALL_CLADDING)
                ->getNodeId(),
            sprintf(self::NAME_SILL, $this->window->getName(), $indoor? self::NAME_INDOOR : self::NAME_OUTDOOR),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $windowElement->getQuantity(),
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        // save attribute
        ElcaElementAttribute::create($element->getId(),
                                     DormerAssistant::IDENT,
                                     DormerAssistant::IDENT,
                                     $windowElement->getId(),
                                     $indoor? self::IDENT_INDOOR_SILL : self::IDENT_OUTDOOR_SILL
        );

        $this->log->debug('Sill element `'. $element->getName() .'\'['. $element->getId().'] created');

        $position = 0;
        $this->createSillLayer($element->getId(), $indoor, ++$position);

        return $element;
    }

    /**
     * @param ElcaElement $element
     * @return ElcaElement
     */
    private function updateSillElement(ElcaElement $element, $indoor = false)
    {
        $element->setName(sprintf(self::NAME_SILL, $this->window->getName(), $indoor? self::NAME_INDOOR : self::NAME_OUTDOOR));
        $element->update();

        $this->log->debug('Sill element `'. $element->getName() .'\'['. $element->getId().'] updated');

        $this->updateElementComponents(
            $element->getId(),
            function ($elementId, ElcaElementComponentSet $components) use($indoor) {
                $sill = $indoor? $this->window->getSillIndoor() : $this->window->getSillOutdoor();

                if ($sillLayer = $components->search('processConfigId', $sill->getMaterialId())) {
                    $this->updateSillLayer($sillLayer, $indoor);
                } else {
                    $sillLayer = $this->createSillLayer($elementId, $indoor);
                }

                return [$sillLayer];
            }
        );

        $components = $element->getComponents();


    }

    /**
     * @param     $elementId
     * @param     $indoor
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createSillLayer($elementId, $indoor = false, $position = 1)
    {
        $sill = $indoor? $this->window->getSillIndoor() : $this->window->getSillOutdoor();
        $processConfig = ElcaProcessConfig::findById($sill->getMaterialId());
        $conversionId = $this->getLayerConversionId($processConfig->getId());

        $component = ElcaElementComponent::create(
            $elementId,
            $sill->getMaterialId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $sill->getSize(),
            null,
            1,
            $sill->getBoundary()->getWidth(),
            $sill->getBoundary()->getHeight()
        );

        $this->log->debug('Sill layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateSillLayer(ElcaElementComponent $component, $indoor = false)
    {
        $sill = $indoor? $this->window->getSillIndoor() : $this->window->getSillOutdoor();

        $component->setProcessConfigId($sill->getMaterialId());
        $component->setLayerSize($sill->getSize());
        $component->setLayerLength($sill->getBoundary()->getWidth());
        $component->setLayerWidth($sill->getBoundary()->getHeight());
        $component->update();

        $this->log->debug('Sill layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }


    /**
     * @param $type
     * @return ElcaElement
     */
    private function createSoffitElement(ElcaElement $windowElement, $indoor = false)
    {
        $element = ElcaElement::create(
            ElcaElementType::findByIdent($indoor? self::DIN_INNER_WALL_CLADDING : self::DIN_OUTER_WALL_CLADDING)
                           ->getNodeId(),
            sprintf(self::NAME_SOFFIT, $this->window->getName(), $indoor? self::NAME_INDOOR : self::NAME_OUTDOOR),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $windowElement->getQuantity(),
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        // save attribute
        ElcaElementAttribute::create($element->getId(),
            DormerAssistant::IDENT,
            DormerAssistant::IDENT,
            $windowElement->getId(),
            $indoor? self::IDENT_INDOOR_SOFFIT : self::IDENT_OUTDOOR_SOFFIT
        );

        $this->log->debug('Soffit element `'. $element->getName() .'\'['. $element->getId().'] created');

        $position = 0;
        $this->createSoffitLayer($element->getId(), $indoor, ++$position);

        return $element;
    }

    /**
     * @param ElcaElement $element
     * @return ElcaElement
     */
    private function updateSoffitElement(ElcaElement $element, $indoor = false)
    {
        $element->setName(sprintf(self::NAME_SOFFIT, $this->window->getName(), $indoor? self::NAME_INDOOR : self::NAME_OUTDOOR));
        $element->update();

        $this->log->debug('Soffit element `'. $element->getName() .'\'['. $element->getId().'] updated');

        $this->updateElementComponents(
            $element->getId(),
            function ($elementId, ElcaElementComponentSet $components) use($indoor) {

                $soffit = $indoor? $this->window->getSoffitIndoor() : $this->window->getSoffitOutdoor();

                if ($soffitLayer = $components->search('processConfigId', $soffit->getMaterialId())) {
                    $this->updateSoffitLayer($soffitLayer, $indoor);
                } else {
                    $soffitLayer = $this->createSoffitLayer($elementId, $indoor);
                }
                return [$soffitLayer];
            }
        );

        return $element;
    }

    /**
     * @param     $elementId
     * @param     $indoor
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createSoffitLayer($elementId, $indoor = false, $position = 1)
    {
        $soffit = $indoor? $this->window->getSoffitIndoor() : $this->window->getSoffitOutdoor();
        $processConfig = ElcaProcessConfig::findById($soffit->getMaterialId());
        $conversionId = $this->getLayerConversionId($processConfig->getId());

        $component = ElcaElementComponent::create(
            $elementId,
            $soffit->getMaterialId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $soffit->getSize(),
            null,
            1,
            $soffit->getBoundary()->getHeight(),
            $soffit->getBoundary()->getWidth()
        );

        $this->log->debug('Soffit layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     */
    private function updateSoffitLayer(ElcaElementComponent $component, $indoor = false)
    {
        $soffit = $indoor? $this->window->getSoffitIndoor() : $this->window->getSoffitOutdoor();

        $component->setProcessConfigId($soffit->getMaterialId());
        $component->setLayerSize($soffit->getSize());
        $component->setLayerLength($soffit->getBoundary()->getHeight());
        $component->setLayerWidth($soffit->getBoundary()->getWidth());
        $component->update();

        $this->log->debug('Soffit layer `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');

        return $component;
    }


    /**
     * @param      $windowElement
     * @param bool $indoor
     * @return ElcaElement
     */
    public function createSunScreenElement($windowElement, $indoor = true)
    {
        $element = ElcaElement::create(
            ElcaElementType::findByIdent(self::DIN_SUNSCREEN)
                           ->getNodeId(),
            sprintf($indoor? self::NAME_INDOOR_SUNSCREEN : self::NAME_OUTDOOR_SUNSCREEN, $this->window->getName()),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $windowElement->getQuantity(),
            Elca::UNIT_STK,
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        // save attribute
        ElcaElementAttribute::create($element->getId(),
                                     DormerAssistant::IDENT,
                                     DormerAssistant::IDENT,
                                     $windowElement->getId(),
                                     $indoor? self::IDENT_INDOOR_SUNSCREEN: self::IDENT_OUTDOOR_SUNSCREEN
        );

        $this->log->debug('Sunscreen element `'. $element->getName() .'\'['. $element->getId().'] created');

        $this->createSunscreenComponent($element->getId(), $indoor);

        return $element;
    }

    /**
     * @param ElcaElement $element
     * @return ElcaElement
     */
    private function updateSunscreenElement(ElcaElement $element, $indoor = false)
    {
        $element->setName(
            sprintf($indoor? self::NAME_INDOOR_SUNSCREEN : self::NAME_OUTDOOR_SUNSCREEN, $this->window->getName())
        );
        $element->update();

        $this->log->debug('Sunscreen element `'. $element->getName() .'\'['. $element->getId().'] updated');

        $this->updateElementComponents(
            $element->getId(),
            function ($elementId, ElcaElementComponentSet $components) use($indoor) {
                $materialId = $indoor? $this->window->getSunscreenIndoorMaterialId() : $this->window->getSunscreenOutdoorMaterialId();

                if ($component = $components->search('processConfigId', $materialId)) {
                    $this->updateSunscreenComponent($component, $indoor);
                } else {
                    $component = $this->createSunscreenComponent($elementId, $indoor);
                }

                return [$component];
            }
        );

        return $element;
    }

    /**
     * @param      $elementId
     * @param bool $indoor
     * @param int  $pos
     */
    private function createSunscreenComponent($elementId, $indoor = false)
    {
        $materialId = $indoor? $this->window->getSunscreenIndoorMaterialId() : $this->window->getSunscreenOutdoorMaterialId();
        $processConfig = ElcaProcessConfig::findById($materialId);
        $conversionId = $this->getComponentConversionId($processConfig->getId(), Elca::UNIT_M2);

        $component = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            false,
            $this->window->getOpeningBoundary()->getArea()
        );

        $this->log->debug('Sunscreen component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] created');

        return $this->createComponentAttribute($component);
    }

    /**
     * @param ElcaElementComponent $component
     * @param bool                 $indoor
     */
    private function updateSunscreenComponent(ElcaElementComponent $component, $indoor = false)
    {
        $materialId = $indoor? $this->window->getSunscreenIndoorMaterialId() : $this->window->getSunscreenOutdoorMaterialId();
        $component->setProcessConfigId($materialId);
        $component->setQuantity($this->window->getOpeningBoundary()->getArea());
        $component->update();

        $this->log->debug('Sunscreen component `'. $component->getProcessConfig()->getName() .'\'['. $component->getId().'] updated');
    }

    /**
     * Helper
     *
     * @param $elementId
     * @param $callback
     */
    private function updateElementComponents($elementId, $callback)
    {
        // find marked components
        $components = ElcaElementComponentSet::findByElementIdAndAttributeIdent($elementId, DormerAssistant::IDENT);
        $componentsToDelete = $components->getArrayBy('id', 'id');

        $updatedComponents = $callback($elementId, $components);

        foreach ($updatedComponents as $component) {
            unset($componentsToDelete[$component->getId()]);
        }

        // delete unseen components
        foreach ($componentsToDelete as $componentId) {
            $component = ElcaElementComponent::findById($componentId);
            $this->log->debug('Component `'. $component->getProcessConfig()->getName() .'\' removed');

            $component->delete();
        }
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
        list($requiredConversions, $availableConversions) = $processConfig->getRequiredConversions();
        $units = array_flip(array_unique($requiredConversions->getArrayBy('inUnit', 'id') + $availableConversions->getArrayBy('inUnit', 'id')));

        if (!is_array($inUnit))
            $inUnit = [$inUnit];

        foreach ($inUnit as $unit) {
            if (isset($units[$unit]))
                return $units[$unit];
        }

        return null;
    }

    /**
     * @param ElcaElementComponent $component
     * @param string               $ident
     * @return ElcaElementComponent
     */
    private function createComponentAttribute(ElcaElementComponent $component, $ident = DormerAssistant::IDENT)
    {
        ElcaElementComponentAttribute::create($component->getId(), $ident);

        return $component;
    }

    /**
     * @param $windowElementId
     * @param $ident
     * @return ElcaElement|null
     */
    private function findElementByAttribute($windowElementId, $ident)
    {
        $attrSet = ElcaElementAttributeSet::find([
            'ident' => DormerAssistant::IDENT,
            'text_value' => $ident,
            'numeric_value' => $windowElementId
        ], ['id' => 'ASC'], 1);

        if (!$attrSet->count())
            return null;

        return $attrSet[0]->getElement();
    }


}
