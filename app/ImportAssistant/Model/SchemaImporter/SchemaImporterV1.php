<?php

namespace ImportAssistant\Model\SchemaImporter;

use ImportAssistant\Model\Import\Component;
use ImportAssistant\Model\Import\Element;
use ImportAssistant\Model\Import\FinalEnergyDemand;
use ImportAssistant\Model\Import\FinalEnergySupply;
use ImportAssistant\Model\Import\LayerComponent;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\Import\ProjectVariant;
use ImportAssistant\Model\Import\RefModel;
use ImportAssistant\Model\Import\SingleComponent;
use ImportAssistant\Model\ImportException;
use Ramsey\Uuid\Uuid;

class SchemaImporterV1 extends AbstractSchemaImporter
{
    const SCHEMA = 'export.xsd';
    const SCHEMA_VERSION = 1;

    public function importProjectNode(array $materialMappingInfos, $processDbId): Project
    {
        $this->setMaterialMappingInfos($materialMappingInfos);
        $this->setProcessDbId($processDbId);

        $projectNode = $this->xPath->query('/x:elca/x:project')->item(0);

        $node = $this->getNode('x:projectInfo', $projectNode);
        $dto  = $this->getObjectProperties($node, ['name', 'description', 'projectNr']);

        if (empty($dto->name)) {
            throw ImportException::projectNameIsInvalid();
        }

        $variants   = $this->importVariantNodes($projectNode);
        $attributes = $this->getAttributes($projectNode);

        return new Project(
            $processDbId,
            $dto->name,
            $variants,
            $attributes,
            $dto->description ?: null,
            $dto->projectNr ?: null
        );
    }

    public function schema(): string
    {
        return self::SCHEMA;
    }

    public function schemaVersion(): int
    {
        return self::SCHEMA_VERSION;
    }

    /**
     * @param \DOMElement $domElement
     * @return array
     */
    private function importVariantNodes(\DOMElement $domElement): array
    {
        $variants     = [];
        $variantNodes = $this->getList('x:projectVariants/x:variant', $domElement);

        foreach ($variantNodes as $variantNode) {
            $dto = $this->getObjectProperties($variantNode, ['name']);

            $locationNode = $this->getNode('x:location', $variantNode);
            $this->getObjectProperties($locationNode, ['street', 'postcode', 'city', 'country'], $dto);

            $constrNode = $this->getNode('x:construction', $variantNode);

            $this->getObjectProperties(
                $constrNode,
                [
                    'grossFloorSpace',
                    'netFloorSpace',
                    'floorSpace',
                    'propertySize',
                ],
                $dto
            );

            $elements     = [];
            $elementNodes = $this->getList('x:elements/x:element', $variantNode);
            foreach ($elementNodes as $elementNode) {
                $elements[] = $this->importElementNode($elementNode);
            }

            $finalEnergyNode = $this->getNode('x:finalEnergy', $variantNode);
            $finalEnergyDto  = $this->getObjectAttributeProperties($finalEnergyNode, ['ngfEnEv', 'enEvVersion']);

            $finalEnergyDemands = [];
            $demandNodes        = $this->getList('x:finalEnergyDemands/x:finalEnergyDemand', $finalEnergyNode);
            foreach ($demandNodes as $demandNode) {
                $finalEnergyDemands[] = $this->importFinalEnergyDemand($demandNode);
            }

            $finalEnergySupplies = [];
            $supplyNodes         = $this->getList('x:finalEnergySupplies/x:finalEnergySupply', $finalEnergyNode);
            foreach ($supplyNodes as $supplyNode) {
                $finalEnergySupplies[] = $this->importFinalEnergySupply($supplyNode);
            }

            $refModels     = [];
            $refModelNodes = $this->getList('x:refFinalEnergyDemands/x:finalEnergyDemand', $finalEnergyNode);
            foreach ($refModelNodes as $refModelNode) {
                $refModels[] = $this->importRefModel($refModelNode);
            }

            $variants[] = new ProjectVariant(
                $dto->name,
                $dto->street,
                $dto->postcode,
                $dto->city,
                $dto->country,
                $dto->grossFloorSpace,
                $dto->netFloorSpace,
                $dto->floorSpace,
                $dto->propertySize,
                $finalEnergyDto->ngfEnEv,
                $finalEnergyDto->enEvVersion,
                $elements,
                $finalEnergyDemands,
                $finalEnergySupplies,
                $refModels
            );
        }

        return $variants;
    }

    /**
     * @param \DOMElement $elementNode
     * @return Element
     */
    private function importElementNode(\DOMElement $elementNode)
    {
        $uuid     = $this->getAttribute($elementNode, 'uuid', (string)Uuid::uuid4(), true);
        $dinCode  = $this->getAttribute($elementNode, 'din276Code');
        $quantity = $this->getAttribute($elementNode, 'quantity', 1);
        $refUnit  = $this->getAttribute($elementNode, 'refUnit');

        $elementInfoNode = $this->getNode('x:elementInfo', $elementNode);
        $dto             = $this->getObjectProperties(
            $elementInfoNode,
            [
                'name',
                'description',
            ]
        );

        $element = new Element(
            $uuid,
            $dinCode,
            $dto->name,
            $this->roundOrDefault($quantity, 8, 1),
            $refUnit,
            $dto->description
        );

        $componentNodes = $this->getList('x:components/x:component', $elementNode, true);
        foreach ($componentNodes as $componentNode) {
            $this->importComponent($componentNode, $element);
        }

        $componentNodes = $this->getList('x:components/x:siblings/x:component', $elementNode, true);
        if ($componentNodes && $componentNodes->length % 2 == 0) {
            for ($index = 0; $index < $componentNodes->length; $index += 2) {

                $this->importLayerSiblings($componentNodes->item($index), $componentNodes->item($index + 1), $element);
            }
        }

        $attributes = $this->getAttributes($elementNode);

        foreach ($attributes as $attribute) {
            $element->addAttribute(
                $attribute
            );
        }

        return $element;
    }

    /**
     * @param \DOMElement $componentNode
     * @param Element     $element
     * @return Component|LayerComponent|SingleComponent
     */
    private function importComponent(\DOMElement $componentNode, Element $element)
    {
        $dto         = $this->getComponentAttributes($componentNode);
        $mappingInfo = $this->getMaterialMappingInfo($dto->processConfigName);

        if (\utf8_strtolower($dto->isLayer) === 'true') {
            $element->addLayerComponent(
                $mappingInfo,
                $dto->layerPosition,
                $this->roundOrDefault($dto->layerSize, 4),
                $this->roundOrDefault($dto->layerLength, 2, 1),
                $this->roundOrDefault($dto->layerWidth, 2, 1),
                $this->roundOrDefault($dto->layerAreaRatio, 3, 1)
            );
        } else {
            $element->addSingleComponent(
                $mappingInfo,
                $this->roundOrDefault($dto->quantity, 8, 1),
                $dto->refUnit,
                $dto->din276Code
            );
        }
    }

    private function importLayerSiblings(\DOMElement $componentNode1, \DOMElement $componentNode2, Element $element)
    {
        $dto1 = $this->getComponentAttributes($componentNode1);
        $dto2 = $this->getComponentAttributes($componentNode2);

        $mappingInfo1 = $this->getMaterialMappingInfo($dto1->processConfigName);
        $mappingInfo2 = $this->getMaterialMappingInfo($dto2->processConfigName);

        $element->addLayerSiblings(
            $mappingInfo1,
            $mappingInfo2,
            $dto1->layerPosition,
            $this->roundOrDefault($dto1->layerSize, 4),
            $this->roundOrDefault($dto2->layerSize, 4),
            $this->roundOrDefault($dto1->layerLength, 2),
            $this->roundOrDefault($dto2->layerLength, 2),
            $this->roundOrDefault($dto1->layerWidth, 2),
            $this->roundOrDefault($dto2->layerWidth, 2),
            $this->roundOrDefault($dto1->layerAreaRatio, 3),
            $this->roundOrDefault($dto2->layerAreaRatio, 3)
        );
    }

    /**
     * @param \DOMElement $componentNode
     * @return object
     */
    private function getComponentAttributes(\DOMElement $componentNode)
    {
        $dto = $this->getObjectAttributeProperties(
            $componentNode,
            [
                'processConfigName',
                'quantity',
                'isLayer',
                'refUnit',
                'layerPosition',
                'layerSize',
                'layerAreaRatio',
                'layerLength',
                'layerWidth',
                'din276Code',
            ]
        );

        return $dto;
    }

    /**
     * @param \DOMElement $node
     * @return FinalEnergyDemand
     */
    private function importFinalEnergyDemand(\DOMElement $node)
    {
        $processConfigName = $this->getAttribute($node, 'processConfigName');
        $dto               = $this->getObjectProperties(
            $node,
            ['heating', 'water', 'lighting', 'ventilation', 'cooling']
        );

        $materialMappingInfo = $this->getMaterialMappingInfo($processConfigName);

        return new FinalEnergyDemand(
            $materialMappingInfo->firstMaterialMapping(),
            $dto->heating, $dto->water, $dto->lighting, $dto->ventilation, $dto->cooling
        );
    }

    private function importFinalEnergySupply(\DOMElement $supplyNode)
    {
        $dto = $this->getObjectAttributeProperties(
            $supplyNode,
            ['processConfigName', 'quantity', 'enevRatio']
        );

        $materialMappingInfo = $this->getMaterialMappingInfo($dto->processConfigName);

        return new FinalEnergySupply(
            $materialMappingInfo->firstMaterialMapping(),
            $dto->quantity, $dto->enevRatio
        );
    }

    private function importRefModel(\DOMElement $refModelNode)
    {
        $ident = $this->getAttribute($refModelNode, 'processConfigName');
        $dto   = $this->getObjectProperties(
            $refModelNode,
            ['heating', 'water', 'lighting', 'ventilation', 'cooling']
        );

        return new RefModel(
            $ident,
            $dto->heating, $dto->water, $dto->lighting, $dto->ventilation, $dto->cooling
        );
    }
}