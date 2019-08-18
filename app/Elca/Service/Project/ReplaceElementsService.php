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
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Service\ElcaElementImageCache;

class ReplaceElementsService
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

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    public function __construct(
        DbHandle $dbHandle,
        ProjectElementService $projectElementService,
        ElcaLcaProcessor $lcaProcessor,
        ElcaElementImageCache $elementImageCache
    ) {
        $this->dbHandle              = $dbHandle;
        $this->projectElementService = $projectElementService;
        $this->elementImageCache     = $elementImageCache;
        $this->lcaProcessor          = $lcaProcessor;
    }

    /**
     * @param array $replaceElementIds [ elementId => compositeElementId ]
     * @param int   $tplElementId
     * @param array $layerSizes
     * @param array $lifeTimes
     * @throws \Exception
     */
    public function replaceCompositeElements(array $replaceElementIds, int $tplElementId)
    {
        $elements = [];
        $projectVariantId = null;

        try {
            $this->dbHandle->begin();

            foreach ($replaceElementIds as $compositeElementId) {
                $compositeElement = ElcaElement::findById($compositeElementId);

                $quantity         = Quantity::fromValue($compositeElement->getQuantity(), $compositeElement->getRefUnit());
                $projectVariantId = $compositeElement->getProjectVariantId();
                $ownerId          = $compositeElement->getOwnerId();
                $accessGroupId    = $compositeElement->getAccessGroupId();

                $this->projectElementService->deleteElement($compositeElement, true);

                $newElement = $this->importNewCompositeFrom(
                    $quantity,
                    $tplElementId,
                    $projectVariantId,
                    $ownerId,
                    $accessGroupId
                );

                $elements[] = $newElement;
            }

            $this->dbHandle->commit();
        }
        catch (\Exception $exception) {
            $this->dbHandle->rollback();
            throw $exception;
        }

        foreach ($elements as $element) {
            $this->lcaProcessor
                ->computeElement($element);
        }

        if ($projectVariantId) {
            $projectVariant = ElcaProjectVariant::findById($projectVariantId);

            $this->lcaProcessor->updateCache(
                $projectVariant->getProjectId(),
                $projectVariant->getId()
            );
        }
    }

    private function importNewCompositeFrom(
        Quantity $quantity,
        int $tplElementId,
        int $projectVariantId,
        int $ownerId,
        int $accessGroupId
    ): ElcaElement {
        $tplElement = ElcaElement::findById($tplElementId);

        $newElement = $this->projectElementService->copyElementFrom(
            $tplElement,
            $ownerId,
            $projectVariantId,
            $accessGroupId,
            true,
            false
        );

        $oldQuantity = $newElement->getQuantity();

        if (null === $newElement) {
            throw new \RuntimeException('Copy from template element failed');
        }

        $newElement->setQuantity($quantity->value());
        $newElement->setRefUnit($quantity->unit()->value());
        $newElement->update();

        $this->projectElementService->updateQuantityOfAffectedElements($newElement, $oldQuantity);

        return $newElement;
    }

}
