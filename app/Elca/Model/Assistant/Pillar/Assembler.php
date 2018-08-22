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

namespace Elca\Model\Assistant\Pillar;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Log;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentAttribute;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Elca;
use Elca\Model\Assistant\Material\Material;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\Pillar\PillarAssistant;
use Exception;

class Assembler
{
    const IDENT_PILLAR = 'pillar';

    /**
     * @var Pillar
     */
    private $pillar;

    /**
     * @var null
     */
    private $projectVariantId;

    /**
     * @var Log
     */
    private $log;

    /**
     * @param Pillar $pillar
     * @param null   $projectVariantId
     */
    public function __construct(Pillar $pillar, $projectVariantId = null)
    {
        $this->pillar           = $pillar;
        $this->projectVariantId = $projectVariantId;

        $this->log = Log::getInstance();
    }

    /**
     * @return ElcaElement
     * @throws \Exception
     */
    public function create($elementTypeNodeId)
    {
        $dbh = DbHandle::getInstance();
        try {
            $dbh->begin();

            $element = $this->createPillarElement($elementTypeNodeId);

            $dbh->commit();

        }
        catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $element;
    }

    /**
     * @param ElcaElement $pillarElement
     * @return ElcaElement
     * @throws Exception
     */
    public function update(ElcaElement $pillarElement)
    {
        $dbh = DbHandle::getInstance();
        try {
            $dbh->begin();

            $this->updatePillarElement($pillarElement);

            $dbh->commit();
        }
        catch (\Exception $e) {
            $dbh->rollback();
            throw $e;
        }

        return $pillarElement;
    }

    /**
     * @param $compositeElementId
     * @return ElcaElement
     */
    private function createPillarElement($elementTypeNodeId)
    {
        $pillarElement = ElcaElement::create(
            $elementTypeNodeId,
            $this->pillar->name(),
            '', // description
            false,
            Elca::getInstance()->getProject()->getAccessGroupId(),
            $this->projectVariantId,
            $this->pillar->amount(), // quantity
            $this->pillar->unit(),
            null,
            ElcaAccess::getInstance()->getUserId()
        );

        $this->log->debug(
            'Pillar element `' . $pillarElement->getName() . '\'[' . $pillarElement->getId() . '] created'
        );

        $this->createPillarLayer($pillarElement->getId());

        return $pillarElement;
    }

    /**
     * @param ElcaElement $pillarElement
     * @return ElcaElement
     */
    private function updatePillarElement(ElcaElement $pillarElement)
    {
        $pillarElement->setName($this->pillar->name());
        $pillarElement->setQuantity($this->pillar->amount());
        $pillarElement->setRefUnit($this->pillar->unit());
        $pillarElement->update();

        $this->updatePillarLayer($pillarElement);
    }

    /**
     * @param     $elementId
     * @param int $position
     * @return ElcaElementComponent
     */
    private function createPillarLayer($elementId, $position = 1)
    {
        $material1 = $this->pillar->material1();
        $material2 = $this->pillar->material2();

        $layer1 = $this->createLayer($elementId, $position, $material1);

        if ($material2) {
            $this->addPillarSibling($elementId, $position, $material2, $layer1);
        }
    }

    /**
     * @param ElcaElement $pillarElement
     */
    private function updatePillarLayer(ElcaElement $pillarElement)
    {
        $material1 = $this->pillar->material1();
        $material2 = $this->pillar->material2();

        // find marked components
        $components         = ElcaElementComponentSet::findByElementIdAndAttributeIdent(
            $pillarElement->getId(),
            PillarAssistant::IDENT
        );
        $componentsToDelete = $components->getArrayBy('id', 'id');

        /**
         * @var ElcaElementComponent $component
         */
        $component = $components->search('processConfigId', $this->pillar->material1()->getMaterialId());

        if (null !== $component) {
            $component->setProcessConfigId($material1->getMaterialId());
            $component->setLayerSize($this->pillar->layerHeight());
            $component->setLayerAreaRatio($material1->getShare());
            $component->setLayerLength($this->pillar->length());
            $component->setLayerWidth($this->pillar->width());
            $component->update();

            unset ($componentsToDelete[$component->getId()]);
        }
        else {
            $component = $this->createLayer($pillarElement->getId(), 1, $material1);
        }

        $this->log->debug(
            'Pillar layer `' . $component->getProcessConfig()->getName() . '\'[' . $component->getId() . '] updated'
        );

        /**
         * @var ElcaElementComponent $sibling
         */
        if ($material2) {
            if ($sibling = $components->search('processConfigId', $material2->getMaterialId())) {
                $sibling->setProcessConfigId($material2->getMaterialId());
                $sibling->setLayerSize($this->pillar->layerHeight());
                $sibling->setLayerAreaRatio($material2->getShare());
                $sibling->setLayerLength($this->pillar->length());
                $sibling->setLayerWidth($this->pillar->width());
                $sibling->setLayerSiblingId($component->getId());
                $sibling->update();

                unset ($componentsToDelete[$sibling->getId()]);

                $component->setLayerSiblingId($sibling->getId());
                $component->update();

                $this->log->debug(
                    'Pillar sibling layer `' . $component->getProcessConfig()->getName() . '\'[' . $component->getId(
                    ) . '] updated'
                );
            } else {
                $this->addPillarSibling(
                    $pillarElement->getId(),
                    $component->getLayerPosition(),
                    $material2,
                    $component
                );
            }
        } else {
            $component->setLayerSiblingId(null);
            $component->update();
        }

        foreach ($componentsToDelete as $componentId) {
            $sibling = ElcaElementComponent::findById($componentId);
            $this->log->debug('Pillar sibling layer `' . $sibling->getProcessConfig()->getName() . '\' removed');
            $sibling->delete();
        }


    }

    /**
     * @param $processConfigId
     * @return mixed
     */
    private function getLayerConversionId($processConfigId)
    {
        $conversionSet = ElcaProcessConversionSet::findByProcessConfigIdAndInUnit(
            $processConfigId,
            'm3',
            ['id' => 'ASC'],
            1
        );

        if ($conversionSet->count()) {
            return $conversionSet[0]->getId();
        }

        return null;
    }

    /**
     * @param ElcaElementComponent $component
     * @param string               $ident
     * @return ElcaElementComponent
     */
    private function createComponentAttribute(ElcaElementComponent $component, $ident = PillarAssistant::IDENT)
    {
        ElcaElementComponentAttribute::create($component->getId(), $ident);
    }

    /**
     * @param $elementId
     * @param $position
     * @param $material
     * @param $siblingLayer
     * @return ElcaElementComponent
     */
    private function addPillarSibling($elementId, int $position, Material $material, ElcaElementComponent $siblingLayer)
    {
        $processConfig = ElcaProcessConfig::findById($material->getMaterialId());
        $conversionId  = $this->getLayerConversionId($processConfig->getId());

        $layer = ElcaElementComponent::create(
            $elementId,
            $processConfig->getId(),
            $conversionId,
            $processConfig->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $this->pillar->layerHeight(),
            $siblingLayer->getId(),
            $material->getShare(),
            $this->pillar->length(),
            $this->pillar->width()
        );

        $siblingLayer->setLayerSiblingId($layer->getId());
        $siblingLayer->update();

        $this->createComponentAttribute($layer);

        $this->log->debug(
            'Pillar sibling layer `' . $processConfig->getName() . '\'[' . $layer->getId() . '] created'
        );

        return $layer;
    }

    /**
     * @param $elementId
     * @param $position
     * @param $material1
     * @return ElcaElementComponent
     */
    private function createLayer($elementId, $position, $material1): ElcaElementComponent
    {
        $processConfig1 = ElcaProcessConfig::findById($material1->getMaterialId());
        $conversionId1  = $this->getLayerConversionId($processConfig1->getId());

        $layer1 = ElcaElementComponent::create(
            $elementId,
            $processConfig1->getId(),
            $conversionId1,
            $processConfig1->getDefaultLifeTime(),
            true,
            1,
            true,
            false,
            $position,
            $this->pillar->layerHeight(),
            null,
            $material1->getShare(),
            $this->pillar->length(),
            $this->pillar->width()
        );

        $this->createComponentAttribute($layer1);

        $this->log->debug(
            'Pillar layer `' . $processConfig1->getName() . '\'[' . $layer1->getId() . '] created'
        );

        return $layer1;
    }
}
