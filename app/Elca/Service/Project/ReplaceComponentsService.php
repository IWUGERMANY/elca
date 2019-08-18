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

use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Service\ElcaElementImageCache;

class ReplaceComponentsService
{
    /**
     * @var DbHandle
     */
    private $dbHandle;

    /**
     * @var ProjectElementService
     */
    private $projectElementService;

    /**
     * @var ElcaElementImageCache
     */
    private $elementImageCache;

    public function __construct(DbHandle $dbHandle, ProjectElementService $projectElementService, ElcaElementImageCache $elementImageCache)
    {
        $this->dbHandle = $dbHandle;
        $this->projectElementService = $projectElementService;
        $this->elementImageCache = $elementImageCache;
    }

    /**
     * @throws \Exception
     * @param array $replaceElementIds [ elementId => compositeElementId ]
     * @param int   $tplElementId
     * @param array $layerSizes
     * @param array $lifeTimes
     */
    public function replaceCompositeComponents(array $replaceElementIds, int $tplElementId, array $layerSizes, array $lifeTimes)
    {
        try {
            $this->dbHandle->begin();


            foreach ($replaceElementIds as $replaceElementId => $compositeElementId) {
                $assignment = ElcaCompositeElement::findProjectCompositeByIdAndElementId($compositeElementId, $replaceElementId);

                $replaceElement = $assignment->getElement();
                $compositeElement = $assignment->getCompositeElement();

                if (!$compositeElement->isInitialized()) {
                    continue;
                }

                $newElement = $this->importNewElementFrom(
                    $replaceElement->getQuantity(),
                    $tplElementId,
                    $layerSizes,
                    $lifeTimes,
                    $compositeElement->getProjectVariantId(),
                    $compositeElement->getOwnerId(),
                    $compositeElement->getAccessGroupId()
                );

                $this->projectElementService->removeFromCompositeElement($compositeElement, $assignment->getPosition(), $replaceElement, true);
                $this->projectElementService->addToCompositeElement($compositeElement, $newElement, $assignment->getPosition());

                $compositeElement->reindexCompositeElements();

                $this->elementImageCache->clear($newElement->getId());
            }

            $this->dbHandle->commit();
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }
    }

    public function deleteCompositeElements(array $deleteElementIds)
    {
        try {
            $this->dbHandle->begin();

            foreach ($deleteElementIds as $replaceElementId => $compositeElementId) {
                $assignment = ElcaCompositeElement::findProjectCompositeByIdAndElementId(
                    $compositeElementId,
                    $replaceElementId
                );

                $replaceElement   = $assignment->getElement();
                $compositeElement = $assignment->getCompositeElement();

                if (!$compositeElement->isInitialized()) {
                    continue;
                }

                $this->projectElementService->removeFromCompositeElement($compositeElement, $assignment->getPosition(), $replaceElement, true);
                $compositeElement->reindexCompositeElements();
            }

            $this->dbHandle->commit();
        }
        catch(\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }
    }


    private function importNewElementFrom($oldQuantity, int $tplElementId, array $layerSizes, array $lifeTimes, int $projectVariantId, int $ownerId, int $accessGroupId): ElcaElement
    {
        $tplElement = ElcaElement::findById($tplElementId);

        $newElement = $this->projectElementService->copyElementFrom(
            $tplElement,
            $ownerId,
            $projectVariantId,
            $accessGroupId,
            true,
            false
        );

        if (null === $newElement) {
            throw new \RuntimeException('Copy from template element failed');
        }

        if ($oldQuantity !== $newElement->getQuantity()) {
           $newElement->setQuantity($oldQuantity);
           $newElement->update();
        }

        $tplComponents = $tplElement->getComponents();

        foreach ($tplComponents as $tplComponent) {
            $componentId = $tplComponent->getId();
            if (isset($layerSizes[$componentId]) || isset($lifeTimes[$componentId])) {
                $this->findAndUpdateComponent($newElement, $tplComponent, $layerSizes[$componentId], $lifeTimes[$componentId]);
            }
        }

        return $newElement;
    }

    private function findAndUpdateComponent(ElcaElement $newElement, ElcaElementComponent $tplComponent, $layerSize = null, $lifeTime = null)
    {
        $components = $newElement->getComponents();

        /**
         * @var ElcaElementComponent $component
         */
        foreach ($components as $component) {
            if ($component->getProcessConfigId() === $tplComponent->getProcessConfigId() &&
                $component->getProcessConversionId() === $tplComponent->getProcessConversionId() &&
                $component->getQuantity() === $tplComponent->getQuantity() &&
                $component->getLifeTime() === $tplComponent->getLifeTime() &&
                $component->isLayer() === $tplComponent->isLayer() &&
                $component->getLayerSize() === $tplComponent->getLayerSize() &&
                $component->getLayerPosition() === $tplComponent->getLayerPosition() &&
                $component->getLayerAreaRatio() === $tplComponent->getLayerAreaRatio()) {

                if ($layerSize) {
                    $component->setLayerSize($layerSize);
                }
                if ($lifeTime) {
                    $component->setLifeTime($lifeTime);
                }
                $component->update();
            }
        }
    }

}
