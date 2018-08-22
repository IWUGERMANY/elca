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

namespace Elca\Model\Processing;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Interfaces\Logger;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaCacheElementComponent;
use Elca\Db\ElcaCacheElementType;
use Elca\Db\ElcaCacheFinalEnergyDemand;
use Elca\Db\ElcaCacheFinalEnergyDemandSet;
use Elca\Db\ElcaCacheFinalEnergyRefModel;
use Elca\Db\ElcaCacheFinalEnergySupply;
use Elca\Db\ElcaCacheFinalEnergySupplySet;
use Elca\Db\ElcaCacheIndicator;
use Elca\Db\ElcaCacheItem;
use Elca\Db\ElcaCacheProjectVariant;
use Elca\Db\ElcaCacheTransportMean;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaProjectFinalEnergyRefModelSet;
use Elca\Db\ElcaProjectTransportMean;
use Elca\Db\ElcaProjectTransportMeanSet;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Model\Common\Quantity\Quantity;
use Exception;

/**
 * ElcaCache is a singleton class to cache lca processing results
 * within the result cache
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaCache
{
    /**
     * Stored procedures
     */
    const PROC_ELCA_CACHE_UPDATE_CACHE = 'elca_cache.update_cache';
    const PROC_ELCA_CACHE_UPDATE_TREE = 'elca_cache.update_element_type_tree(%d)';
    const PROC_ELCA_CACHE_UPDATE_PROJECT_VARIANT = 'elca_cache.update_project_variant(%d)';

    /**
     * Singleton instance
     */
    private static $Instance;

    /**
     * DbHandle
     */
    private $dbh;

    /**
     * Log
     */
    private $log;

    /**
     * Constructor
     *
     * @param DbHandle $dbHandle
     * @param Logger   $logger
     */
    public function __construct(DbHandle $dbHandle, Logger $logger)
    {
        $this->dbh = $dbHandle;
        $this->log = $logger;
    }
    // End __construct


    /**
     * Updates the cache by accomplishing the following steps
     *
     * 1. Checks for outdated elements
     *    and aggregates all associated elements
     * 2. Checks for outdated final energy demands
     * 3. Updates the sum over all indicator values for each outdated element
     * 4. Updates the indicators values along the element type tree
     * 5. Updates the project variant root cache item
     */
    public function update($projectId)
    {
        try {
            $this->dbh->begin();
            $sql = sprintf('SELECT %s(%d)', self::PROC_ELCA_CACHE_UPDATE_CACHE, $projectId);
            $this->dbh->query($sql);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End update


    /**
     * Aggregates the cache tree for element types in the given project variant.
     * This is necessary after deleting a project element to keep the tree
     * up-to-date
     *
     * @param int $projectVariantId
     * @param     $elementTypeNodeId
     */
    public function updateElementTypeTree($projectVariantId, $elementTypeNodeId)
    {
        $CEType = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId(
            $projectVariantId,
            $elementTypeNodeId
        );

        if (!$CEType->isInitialized()) {
            return;
        }

        /**
         * Update cache values for all project elements
         */
        $this->dbh->query(sprintf('SELECT '.self::PROC_ELCA_CACHE_UPDATE_TREE, $CEType->getItemId()));
    }
    // End updateElementTypeTree


    /**
     * Aggregates the cache for the given project variant.
     * This is necessary after deleting final energy demands or other cache items beneath
     * the project variant cache item.
     *
     * @param int $projectVariantId
     */
    public function updateProjectVariant($projectVariantId)
    {
        $CacheVariant = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId);

        if (!$CacheVariant->isInitialized()) {
            return;
        }

        /**
         * Update cache values for all project elements
         */
        $this->dbh->query(sprintf('SELECT '.self::PROC_ELCA_CACHE_UPDATE_PROJECT_VARIANT, $CacheVariant->getItemId()));
    }
    // End updateProjectVariant

    /**
     * Caches the results for an ElcaElement
     *
     * @param  ElcaElement $Element
     * @param  number      $mass
     * @param  number      $quantity
     * @param  string      $refUnit
     * @param null         $compositeItemId
     * @throws Exception
     * @return ElcaCacheItem
     */
    public function storeElement(
        ElcaElement $Element, $mass = null, $quantity = null, $refUnit = null, $compositeItemId = null
    ) {
        try {
            $this->dbh->begin();

            $CacheElement = ElcaCacheElement::findByElementId($Element->getId());

            if ($CacheElement->isInitialized()) {
                if ($Element->hasCompositeElement(true)) {
                    if ($compositeItemId) {
                        $CacheElement->setCompositeItemId($compositeItemId);
                    }
                } else {
                    $CacheElement->setCompositeItemId(null);
                }

                $CacheElement->setMass($mass);
                $CacheElement->setQuantity($quantity);
                $CacheElement->setRefUnit($refUnit);
                $CacheElement->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheElement->setIsOutdated();
            } else {
                $CacheElement = ElcaCacheElement::create(
                    $Element->getId(),
                    $mass,
                    $quantity,
                    $refUnit,
                    $compositeItemId
                );
            }

//            $this->log->debug($Element->getName().' itemId='.$CacheElement->getItemId().', compositeItemId='.$compositeItemId.': mass='.$mass, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheElement;
    }
    // End storeElement


    /**
     * Caches the results for an ElcaElementComponent
     *
     * @throws Exception
     * @param  int     $elementComponentId
     * @param Quantity $quantity
     * @param  number  $mass
     * @param  int     $numReplacements
     * @return ElcaCacheItem
     */
    public function storeElementComponent(
        $elementComponentId, Quantity $quantity, $mass = null, $numReplacements = null
    ) {
        try {
            $this->dbh->begin();

            $CacheElementComponent = ElcaCacheElementComponent::findByElementComponentId($elementComponentId);

            if ($CacheElementComponent->isInitialized()) {
                $CacheElementComponent->setQuantity($quantity->value());
                $CacheElementComponent->setRefUnit($quantity->unit()->value());
                $CacheElementComponent->setMass($mass);
                $CacheElementComponent->setNumReplacements($numReplacements);
                $CacheElementComponent->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheElementComponent->setIsOutdated();
            } else {
                $CacheElementComponent = ElcaCacheElementComponent::create(
                    $elementComponentId,
                    $mass,
                    $quantity->value(),
                    $quantity->unit()->value(),
                    $numReplacements
                );
            }

            $this->log->debug(ElcaElementComponent::findById($elementComponentId)->getProcessConfig()->getName().' itemId='.$CacheElementComponent->getItemId().': quantity='.$quantity.' mass='.$mass.' numReplacements='.$numReplacements, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheElementComponent;
    }
    // End storeElementComponent


    /**
     * Removes caches results for an ElcaElementComponent
     *
     * @param  int $componentId
     * @throws Exception
     */
    public function removeElementComponent($componentId)
    {
        try {
            $this->dbh->begin();

            $CacheElementComponent = ElcaCacheElementComponent::findByElementComponentId($componentId);
            if ($CacheElementComponent->isInitialized()) {
                $ParentItem = $CacheElementComponent->getItem()->getParent();
                $CacheElementComponent->delete();

                /**
                 * Set parent item (cache element) outdated
                 */
                $ParentItem->setIsOutdated(true);
                $ParentItem->update();
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End storeElementComponent


    /**
     * Caches the results for a final energy demand
     *
     * @param  int    $finalEnergyDemandId
     * @param  number $quantity
     * @param  string $refUnit
     * @throws Exception
     * @return ElcaCacheItem
     */
    public function storeFinalEnergyDemand($finalEnergyDemandId, $quantity = null, $refUnit = null)
    {
        try {
            $this->dbh->begin();

            $CacheDemand = ElcaCacheFinalEnergyDemand::findByFinalEnergyDemandId($finalEnergyDemandId);

            if ($CacheDemand->isInitialized()) {
                $CacheDemand->setQuantity($quantity);
                $CacheDemand->setRefUnit($refUnit);
                $CacheDemand->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheDemand->setIsOutdated();
            } else {
                $CacheDemand = ElcaCacheFinalEnergyDemand::create($finalEnergyDemandId, $quantity, $refUnit);
            }

            //$this->log->debug(ElcaProjectFinalEnergyDemand::findById($finalEnergyDemandId)->getProcessConfig()->getName().' itemId='.$CacheDemand->getItemId().': qE='.$quantity, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheDemand;
    }
    // End storeFinalEnergyDemand


    /**
     * Removes all cached final energy demand results for an project variant
     *
     * @todo: stored procedure would work faster
     *
     * @param  int $projectVariantId
     * @throws Exception
     */
    public function removeFinalEnergyDemands($projectVariantId)
    {
        $CacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId);

        try {
            $this->dbh->begin();

            foreach (ElcaCacheFinalEnergyDemandSet::findByParentItemId($CacheRoot->getItemId()) as $CacheDemand) {
                $CacheDemand->delete();
                //$this->log->debug('CacheFinalEnergyDemand itemId='.$CacheDemand->getItemId().' deleted', __METHOD__);
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End removeFinalEnergyDemands


    /**
     * Caches the results for a final energy Supply
     *
     * @param  int    $finalEnergySupplyId
     * @param  number $quantity
     * @param  string $refUnit
     * @throws Exception
     * @return ElcaCacheItem
     */
    public function storeFinalEnergySupply($finalEnergySupplyId, $quantity = null, $refUnit = null)
    {
        try {
            $this->dbh->begin();

            $CacheSupply = ElcaCacheFinalEnergySupply::findByFinalEnergySupplyId($finalEnergySupplyId);

            if ($CacheSupply->isInitialized()) {
                $CacheSupply->setQuantity($quantity);
                $CacheSupply->setRefUnit($refUnit);
                $CacheSupply->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheSupply->setIsOutdated();
            } else {
                $CacheSupply = ElcaCacheFinalEnergySupply::create($finalEnergySupplyId, $quantity, $refUnit);
            }

            //$this->log->debug(ElcaProjectFinalEnergySupply::findById($finalEnergySupplyId)->getProcessConfig()->getName().' itemId='.$CacheSupply->getItemId().': qE='.$quantity, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheSupply;
    }
    // End storeFinalEnergySupply


    /**
     * Removes all cached final energy supplies results for an project variant
     *
     * @todo: stored procedure would work faster
     *
     * @param  int $projectVariantId
     * @throws Exception
     */
    public function removeFinalEnergySupplies($projectVariantId)
    {
        $CacheRoot = ElcaCacheProjectVariant::findByProjectVariantId($projectVariantId);

        try {
            $this->dbh->begin();

            foreach (ElcaCacheFinalEnergySupplySet::findByParentItemId($CacheRoot->getItemId()) as $CacheSupply) {
                $CacheSupply->delete();
                //$this->log->debug('CacheFinalEnergySupply itemId='.$CacheSupply->getItemId().' deleted', __METHOD__);
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End removeFinalEnergySupplies


    /**
     * Caches the results for a final energy ref model
     *
     * @param  int    $finalEnergyRefModelId
     * @param  float  $quantity
     * @param  string $refUnit
     *
     * @throws Exception
     * @return ElcaCacheItem
     */
    public function storeFinalEnergyRefModel($finalEnergyRefModelId, $quantity = null, $refUnit = null)
    {
        try {
            $this->dbh->begin();

            $CacheRefModel = ElcaCacheFinalEnergyRefModel::findByFinalEnergyRefModelId($finalEnergyRefModelId);

            if ($CacheRefModel->isInitialized()) {
                $CacheRefModel->setQuantity($quantity);
                $CacheRefModel->setRefUnit($refUnit);
                $CacheRefModel->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheRefModel->setIsOutdated();
            } else {
                $CacheRefModel = ElcaCacheFinalEnergyRefModel::create($finalEnergyRefModelId, $quantity, $refUnit);
            }

            //$this->log->debug(ElcaProjectFinalEnergyRefModel::findById($finalEnergyRefModelId)->getIdent().' itemId='.$CacheRefModel->getItemId().': qE='.$quantity, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheRefModel;
    }
    // End storeFinalEnergyRefModel


    /**
     * Removes all cached final energy supplies results for an project variant
     *
     * @todo: stored procedure would work faster
     *
     * @param  int $projectVariantId
     * @throws Exception
     */
    public function removeFinalEnergyRefModels($projectVariantId)
    {
        try {
            $this->dbh->begin();

            foreach (
                ElcaProjectFinalEnergyRefModelSet::find(
                    ['project_variant_id' => $projectVariantId]
                ) as $RefModel
            ) {

                $CacheRefModel = ElcaCacheFinalEnergyRefModel::findByFinalEnergyRefModelId($RefModel->getId());
                $CacheRefModel->delete();
                //$this->log->debug('CacheFinalEnergyRefModel '.$RefModel->getIdent().' itemId='.$CacheRefModel->getItemId().' deleted', __METHOD__);
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End removeFinalEnergyRefModels


    /**
     * Caches the results for a transport mean
     *
     * @param         $transportMeanId
     * @param  float  $quantity
     * @param  string $refUnit
     * @param bool    $includeInLca
     * @throws Exception
     * @return ElcaCacheTransportMean
     */
    public function storeTransportMean($transportMeanId, $quantity = null, $refUnit = null, $includeInLca = false)
    {
        try {
            $this->dbh->begin();

            $CacheTransportMean = ElcaCacheTransportMean::findByTransportMeanId($transportMeanId);

            $ProjectTransportMean = ElcaProjectTransportMean::findById($transportMeanId);
            $CacheRoot            = ElcaCacheProjectVariant::findByProjectVariantId(
                $ProjectTransportMean->getProjectTransport()->getProjectVariantId()
            );

            if ($CacheTransportMean->isInitialized()) {
                $CacheTransportMean->setQuantity($quantity);
                $CacheTransportMean->setRefUnit($refUnit);
                $CacheTransportMean->update();

                /**
                 * Sets the cache item outdated
                 */
                $CacheTransportMean->setIsOutdated();

                $Item = $CacheTransportMean->getItem();
                $Item->setIsVirtual(!$includeInLca);
                $Item->update();
            } else {

                $CacheTransportMean = ElcaCacheTransportMean::create(
                    $transportMeanId,
                    $quantity,
                    $refUnit,
                    !$includeInLca
                );
            }

            //$this->log->debug(ElcaProjectTransportMean::findById($transportMeanId)->getProcessConfig()->getName().' itemId='.$CacheTransportMean->getItemId().': quantity='.$quantity, __METHOD__);
            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }

        return $CacheTransportMean;
    }
    // End storeTransportMean


    /**
     * Removes all cached transport mean results for an project variant
     *
     * @param  int $projectVariantId
     * @throws Exception
     */
    public function removeTransportMeans($projectVariantId)
    {
        try {
            $this->dbh->begin();

            foreach (ElcaProjectTransportSet::findByProjectVariantId($projectVariantId) as $Transport) {

                foreach (ElcaProjectTransportMeanSet::findByProjectTransportId($Transport->getId()) as $TransortMean) {
                    $CacheTransportMean = ElcaCacheTransportMean::findByTransportMeanId($TransortMean->getId());
                    $CacheTransportMean->delete();
                    //$this->log->debug('CacheTransportMean itemId='.$CacheTransportMean->getItemId().' deleted', __METHOD__);
                }
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
    // End removeTransportMeans


    /**
     * Caches indicator values for cache items
     *
     * @throws Exception
     * @param  ElcaCacheItem   $CacheItem
     * @param IndicatorResults $indicatorResults
     * @param bool             $zeroValues - overwrite values with zero (but keep the indicators)
     */
    public function storeIndicators(
        ElcaCacheItem $CacheItem, IndicatorResults $indicatorResults, bool $zeroValues = false, bool $isPartial = false
    ) {
        try {
            $this->dbh->begin();

            /**
             * @var IndicatorResult $indicatorResult
             */
            foreach ($indicatorResults as $indicatorResult) {
                /**
                 * @todo: Including the processId in the unique key constraint is a problem, when
                 *        the processId has changed
                 *        The workaround for this is currently to remove a component or energy demand item from
                 *        the cache when changing the associated process config
                 *
                 * @see ElementsCtrl::saveComponent and saveLayer
                 * @see ProjectDataCtrl::saveEnergyDemand
                 */
                $indicator = ElcaCacheIndicator::findByPk(
                    $CacheItem->getId(),
                    $indicatorResults->module()->value(),
                    $indicatorResult->indicatorId()->value(),
                    $indicatorResults->hasProcessId() ? $indicatorResults->processId()->value() : null
                );

                if ($indicator->isInitialized()) {
                    $indicator->setRatio($indicatorResults->moduleRatio());
                    $indicator->setValue($zeroValues ? 0 : $indicatorResult->value());
                    $indicator->setIsPartial($isPartial);
                    $indicator->update();
                } else {
                    ElcaCacheIndicator::create(
                        $CacheItem->getId(),
                        $indicatorResults->module()->value(),
                        $indicatorResult->indicatorId()->value(),
                        $zeroValues ? 0 : $indicatorResult->value(),
                        $indicatorResults->hasProcessId() ? $indicatorResults->processId()->value() : null,
                        $indicatorResults->moduleRatio(),
                        $isPartial
                    );
                }
            }

            /**
             * Update CacheItem if necessary
             */
            if (!$CacheItem->isOutdated()) {
                $CacheItem->setIsOutdated();
                $CacheItem->update();
            }

            $this->dbh->commit();
        } catch (Exception $Exception) {
            $this->dbh->rollback();
            throw $Exception;
        }
    }
}
