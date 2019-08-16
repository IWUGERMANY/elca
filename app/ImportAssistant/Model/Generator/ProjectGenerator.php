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

declare(strict_types=1);

namespace ImportAssistant\Model\Generator;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\UserStore;
use ImportAssistant\Model\Import\Component;
use ImportAssistant\Model\Import\Element;
use ImportAssistant\Model\Import\FinalEnergyDemand;
use ImportAssistant\Model\Import\FinalEnergySupply;
use ImportAssistant\Model\Import\LayerComponent;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\Import\ProjectVariant;
use ImportAssistant\Model\Import\RefModel;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaConstrClass;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentAttribute;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergyRefModel;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Security\ElcaAccess;

class ProjectGenerator
{
    const CONSTR_CLASS_REF_NUM = 9890;

    /**
     * @var ElcaAccess
     */
    private $elcaAccess;

    /**
     * @var DbHandle
     */
    private $dbh;

    /**
     * ProjectGenerator constructor.
     *
     * @param DbHandle  $dbHandle
     * @param UserStore $elcaAccess
     */
    public function __construct(DbHandle $dbHandle, ElcaAccess $elcaAccess)
    {
        $this->elcaAccess = $elcaAccess;
        $this->dbh        = $dbHandle;
    }

    /**
     * @param Project $project
     * @return ElcaProject
     */
    public function generate(Project $project)
    {
        $elcaProject = $this->dbh->atomic(
            function () use ($project) {
                $elcaProject = ElcaProject::create(
                    $project->processDbId(),
                    $this->elcaAccess->getUserId(),
                    $this->elcaAccess->getUserGroupId(),
                    $project->name(),
                    Elca::DEFAULT_LIFE_TIME,
                    null,
                    $project->description(),
                    $project->projectNr(),
                    Elca::CONSTR_MEASURE_PRIVATE,
                    ElcaConstrClass::findByRefNum(self::CONSTR_CLASS_REF_NUM)->getId()
                );

                foreach ($project->attributes() as $attribute) {
                    ElcaProjectAttribute::create(
                        $elcaProject->getId(),
                        $attribute->ident(),
                        $attribute->caption(),
                        $attribute->numericValue(),
                        $attribute->textValue()
                    );
                }

                foreach ($project->variants() as $variant) {
                    $this->generateVariant($variant, $elcaProject);
                }

                return $elcaProject;
            }
        );

        return $elcaProject;
    }

    /**
     * @param ProjectVariant $variant
     * @param ElcaProject    $elcaProject
     */
    private function generateVariant(ProjectVariant $variant, ElcaProject $elcaProject)
    {
        $elcaProjectPhase   = ElcaProjectPhase::findByConstrMeasureAndIdent(
            $elcaProject->getConstrMeasure(),
            ElcaProjectPhase::IDENT_VORPL
        );
        $elcaProjectVariant = ElcaProjectVariant::create(
            $elcaProject->getId(),
            $elcaProjectPhase->getId(),
            $variant->name() ?? $elcaProjectPhase->getName()
        );

        $elcaProject->setCurrentVariantId($elcaProjectVariant->getId());
        $elcaProject->update();

        $location = ElcaProjectLocation::create(
            $elcaProjectVariant->getId(),
            $variant->street(),
            $variant->postcode(),
            $variant->city(),
            $variant->country()
        );

        if (!$location->isInitialized()) {
            throw new \RuntimeException(implode(', ', $location->getValidator()->getErrors()));
        }

        $construction = ElcaProjectConstruction::create(
            $elcaProjectVariant->getId(),
            null,
            null,
            $variant->grossFloorSpace() > 0 ? $variant->grossFloorSpace() : $variant->netFloorSpace(),
            $variant->netFloorSpace(),
            $variant->floorSpace(),
            $variant->propertySize()
        );

        if (!$construction->isInitialized()) {
            throw new \RuntimeException(implode(', ', $construction->getValidator()->getErrors()));
        }

        foreach ($variant->elements() as $element) {
            $this->generateElement($element, $elcaProjectVariant);
        }

        if ($variant->ngfEnEv()) {
            ElcaProjectEnEv::create(
                $elcaProjectVariant->getId(),
                $variant->ngfEnEv(),
                (int)$variant->enEvVersion()
            );

            foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
                $this->generateFinalEnergyDemand($finalEnergyDemand, $elcaProjectVariant);
            }

            foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
                $this->generateFinalEnergySupply($finalEnergySupply, $elcaProjectVariant);
            }

            foreach ($variant->refModels() as $refModel) {
                $this->generateRefFinalEnergyDemand($refModel, $elcaProjectVariant);
            }
        }
    }

    /**
     * @param Element            $element
     * @param ElcaProjectVariant $elcaProjectVariant
     */
    private function generateElement(Element $element, ElcaProjectVariant $elcaProjectVariant)
    {
        $elcaElement = ElcaElement::create(
            ElcaElementType::findByIdent($element->dinCode())->getNodeId(),
            $element->name(),
            $element->description(),
            false,
            $this->elcaAccess->getUserGroupId(),
            $elcaProjectVariant->getId(),
            $element->quantity(),
            $element->refUnit(),
            null,
            $this->elcaAccess->getUserId()
        );

        if (!$elcaElement->isInitialized()) {
            throw new \RuntimeException(implode(', ', $elcaElement->getValidator()->getErrors()));
        }

        foreach ($element->attributes() as $attribute) {
            ElcaElementAttribute::create(
                $elcaElement->getId(),
                $attribute->ident(),
                $attribute->caption(),
                $attribute->numericValue(),
                $attribute->textValue()
            );
        }

        $components = $element->allComponents();

        /**
         * @var Component[][] $dinCodeComponents
         */
        $dinCodeComponents = [];

        array_walk(
            $components,
            function (Component $component) use (&$dinCodeComponents) {
                if (!isset($dinCodeComponents[$component->dinCode()])) {
                    $dinCodeComponents[$component->dinCode()] = [];
                }

                $dinCodeComponents[$component->dinCode()][] = $component;
            }
        );

        $position = 1;
        foreach ($dinCodeComponents as $dinCode => $components) {
            $componentElement = $this->generateComponentElement(
                $element,
                $elcaElement,
                $dinCode,
                $components
            );

            $composite = ElcaCompositeElement::create($elcaElement->getId(), $position++, $componentElement->getId());

            if (!$composite->isInitialized()) {
                throw new \RuntimeException(implode(', ', $composite->getValidator()->getErrors()));
            }
        }
    }

    /**
     * @param Element            $element
     * @param                    $dinCode
     * @param Component[]        $components
     * @return ElcaElement
     */
    private function generateComponentElement(
        Element $element,
        ElcaElement $elcaElement,
        $dinCode,
        $components
    ) {
        $elcaElementType  = ElcaElementType::findByIdent($dinCode);
        $componentElement = ElcaElement::create(
            $elcaElementType->getNodeId(),
            $element->name() . ' - ' . $elcaElementType->getName(),
            null,
            false,
            $this->elcaAccess->getUserGroupId(),
            $elcaElement->getProjectVariantId(),
            $element->quantity(),
            $element->refUnit(),
            null,
            $this->elcaAccess->getUserId()
        );


        if (!$componentElement->isInitialized()) {
            throw new \RuntimeException(implode(', ', $componentElement->getValidator()->getErrors()));
        }

        /**
         * @var ElcaElementComponent[] $elcaComponents
         */
        $elcaComponents = [];
        foreach ($components as $component) {

            $processConfigId = $component->materialMapping()->mapsToProcessConfigId();
            $processConfig   = $processConfigId ? ElcaProcessConfig::findById($processConfigId)
                : ElcaProcessConfig::findUnknown();

            $conversions = ElcaProcessConversionSet::findByProcessConfigIdAndUnit(
                $processConfig->getId(),
                $component->refUnit(),
                ['in_unit <> out_unit' => 'ASC'],
                1
            );

            $conversion = $conversions->current();

            if (false === $conversion) {
                continue;
            }

            $elcaComponents[$component->uuid()] = $elcaElementComponent = ElcaElementComponent::create(
                $componentElement->getId(),
                $processConfig->getId(),
                $conversion->getId(),
                $processConfig->getDefaultLifeTime(),
                $component->isLayer(),
                $component->quantity(),
                true,
                false,
                $component->layerPosition(),
                $component->layerSize(),
                null,
                $component->layerAreaRatio(),
                $component->layerLength(),
                $component->layerWidth()
            );

            if (!$elcaElementComponent->isInitialized()) {
                throw new \RuntimeException(
                    implode(', ', $elcaElementComponent->getValidator()->getErrors())
                );
            }

            if ($processConfig->isUnknown()) {
                ElcaElementComponentAttribute::create(
                    $elcaElementComponent->getId(),
                    Elca::ELEMENT_COMPONENT_ATTR_UNKNOWN,
                    null,
                    $component->materialMapping()->materialName()
                );
            }

            if ($component instanceof LayerComponent && $component->isSibling()) {
                if (isset($elcaComponents[$component->isSiblingOf()->uuid()])) {

                    $elcaComponents[$component->uuid()]->setLayerSiblingId(
                        $elcaComponents[$component->isSiblingOf()->uuid()]->getId()
                    );

                    $elcaComponents[$component->isSiblingOf()->uuid()]->setLayerSiblingId(
                        $elcaComponents[$component->uuid()]->getId()
                    );

                    $elcaComponents[$component->uuid()]->update();
                    $elcaComponents[$component->isSiblingOf()->uuid()]->update();
                }
            }
        }

        return $componentElement;
    }

    private function generateFinalEnergyDemand(
        FinalEnergyDemand $finalEnergyDemand,
        ElcaProjectVariant $elcaProjectVariant
    ) {
        ElcaProjectFinalEnergyDemand::create(
            $elcaProjectVariant->getId(),
            $finalEnergyDemand->materialMapping()->mapsToProcessConfigId(),
            $finalEnergyDemand->heating(),
            $finalEnergyDemand->water(),
            $finalEnergyDemand->lighting(),
            $finalEnergyDemand->ventilation(),
            $finalEnergyDemand->cooling()
        );
    }

    private function generateFinalEnergySupply(
        FinalEnergySupply $finalEnergySupply,
        ElcaProjectVariant $elcaProjectVariant
    ) {
        ElcaProjectFinalEnergySupply::create(
            $elcaProjectVariant->getId(),
            $finalEnergySupply->materialMapping()->mapsToProcessConfigId(),
            $finalEnergySupply->quantity(),
            $finalEnergySupply->materialMapping()->materialName(),
            $finalEnergySupply->enEvRatio()
        );
    }

    private function generateRefFinalEnergyDemand(RefModel $refModel, ElcaProjectVariant $elcaProjectVariant)
    {
        ElcaProjectFinalEnergyRefModel::create(
            $elcaProjectVariant->getId(),
            $refModel->ident(),
            $refModel->heating(),
            $refModel->water(),
            $refModel->lighting(),
            $refModel->ventilation(),
            $refModel->cooling()
        );
    }
}
