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

namespace ImportAssistant\Model;

use Beibob\Blibs\File;
use Elca\Db\ElcaProcessConfig;
use ImportAssistant\Model\Import\Attribute;
use ImportAssistant\Model\Import\Component;
use ImportAssistant\Model\Import\Element;
use ImportAssistant\Model\Import\FinalEnergyDemand;
use ImportAssistant\Model\Import\FinalEnergySupply;
use ImportAssistant\Model\Import\LayerComponent;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\Import\ProjectVariant;
use ImportAssistant\Model\Import\RefModel;
use ImportAssistant\Model\Import\SingleComponent;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfoRepository;
use Ramsey\Uuid\Uuid;

class Importer
{
    /**
     * @var \DOMDocument
     */
    private $document;

    /**
     * @var \DOMXPath
     */
    private $xPath;

    /**
     * @var MaterialMappingInfo[]
     */
    private $materialMappingInfos;

    /**
     * @var null|string
     */
    private $xsdSchemaPath;

    /**
     * @var null
     */
    private $processDbId;

    /**
     * Importer constructor.
     *
     * @param MaterialMappingInfoRepository $materialMappingRepository
     * @param string                   $xsdSchemaPath
     * @param                               $processDbId
     */
    public function __construct(
        MaterialMappingInfoRepository $materialMappingRepository, string $xsdSchemaPath, int $processDbId) {
        $this->materialMappingInfos = $materialMappingRepository->findByProcessDbId($processDbId);
        $this->xsdSchemaPath        = $xsdSchemaPath;
        $this->processDbId          = $processDbId;
    }

    /**
     * @param File $file
     * @return Project
     */
    public function fromFile(File $file)
    {
        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->loadXML(implode('', $file->getAsArray()));

        $this->xPath = new \DOMXPath($this->document);
        $this->xPath->registerNamespace('x', 'https://www.bauteileditor.de/EnEV/2017');

        /**
         * validate document
         */
        $this->validateDocument();

        $projectNode = $this->xPath->query('/x:elca/x:project')->item(0);

        return $this->importProjectNode($projectNode);
    }


    /**
     * @param \DOMElement $domElement
     * @return Project
     */
    private function importProjectNode(\DOMElement $domElement)
    {
        $node = $this->getNode('x:projectInfo', $domElement);
        $dto  = $this->getObjectProperties($node, ['name', 'description', 'projectNr']);

        if (empty($dto->name)) {
            throw ImportException::projectNameIsInvalid();
        }

        $variants   = $this->importVariantNodes($domElement);
        $attributes = $this->getAttributes($domElement);

        return new Project(
            $this->processDbId,
            $dto->name,
            $variants,
            $attributes,
            $dto->description ?: null,
            $dto->projectNr ?: null
        );
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
     * Returns object properties by the given simple elements
     * This method is the oposite of Exporter::appendObjectProperties
     *
     * @param  \DOMElement $domElement
     * @param  array       $properties
     * @param null|object  $dto
     * @return object
     */
    private function getObjectProperties(\DOMElement $domElement, array $properties, $dto = null)
    {
        $dto = $dto ?? new \stdClass();

        foreach ($properties as $property) {
            $value    = null;
            $nodeList = $this->xPath->query('./x:'.$property.'/text()', $domElement);

            if ($nodeList && $nodeList->length > 0) {
                $value = '';
                foreach ($nodeList as $node) {
                    $value .= $node->textContent;
                }

                $value = trim($value);
            }

            $dto->$property = $value;
        }

        return $dto;
    }

    /**
     * Returns object properties by the given simple elements
     * This method is the oposite of Exporter::appendObjectProperties
     *
     * @param  \DOMElement $domElement
     * @param  array       $attributes
     * @param null|object  $dto
     * @return object
     */
    private function getObjectAttributeProperties(\DOMElement $domElement, array $attributes, $dto = null)
    {
        $dto = $dto ?? new \stdClass();

        foreach ($attributes as $attribute) {
            $dto->$attribute = $this->getAttribute($domElement, $attribute, null, true);
        }

        return $dto;
    }

    /**
     * Returns either a single DOMElement
     *
     * @param  string      $xpath
     * @param  \DOMElement $context
     * @param bool         $isOptional
     * @throws ImportException
     * @return \DOMElement
     */
    private function getNode($xpath, \DOMElement $context = null, $isOptional = false)
    {
        if ($result = $this->xPath->query($xpath, $context)) {
            if ($result->length > 0) {
                return $result->item(0);
            }
        }

        if ($isOptional) {
            return null;
        }

        throw ImportException::pathNotFound($xpath, $context ? $context->getNodePath() : '/');
    }


    /**
     * Returns a DOMNodelist
     *
     * @param  string      $xpath
     * @param  \DOMElement $context
     * @param bool         $isOptional
     * @throws ImportException
     * @return \DOMNodelist
     */
    private function getList($xpath, \DOMElement $context = null, $isOptional = false)
    {
        if ($result = $this->xPath->query($xpath, $context)) {
            return $result;
        }

        if ($isOptional) {
            return new \DOMNodelist();
        }

        throw ImportException::pathNotFound($xpath, $context ? $context->getNodePath() : '/');
    }


    /**
     * Returns an attribute value.
     *
     * @param  \DOMElement $domElement
     * @param  string      $name
     * @param  mixed       $defaultValue
     * @param bool         $isOptional
     * @throws ImportException
     * @return string
     */
    private function getAttribute(\DOMElement $domElement, $name, $defaultValue = null, $isOptional = false)
    {
        if (!$domElement->hasAttribute($name)) {
            if ($defaultValue || $isOptional) {
                return $defaultValue;
            }

            throw ImportException::missingAttribute($domElement->nodeName, $name, $domElement->getNodePath());
        }

        return $domElement->getAttribute($name);
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
     * @param $processConfigName
     * @return MaterialMappingInfo
     */
    private function getMaterialMappingInfo($processConfigName): MaterialMappingInfo
    {
        if (isset($this->materialMappingInfos[$processConfigName])) {
            return $this->materialMappingInfos[$processConfigName];
        }

        if ($this->processDbId) {
            $processConfig = ElcaProcessConfig::findByProcessNameAndProcessDbId($processConfigName, $this->processDbId);

            $units = array_keys($processConfig->getRequiredUnits());

            return new MaterialMappingInfo(
                $processConfigName,
                $this->processDbId,
                [new MaterialMapping($processConfigName, $processConfig->getId(), null, $processConfigName, $units)]
            );
        }

        return new MaterialMappingInfo(
            $processConfigName,
            $this->processDbId
        );
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

    /**
     * @param \DOMElement $domElement
     * @return array
     */
    private function getAttributes(\DOMElement $domElement): array
    {
        $attributes = [];

        $attributeNodes = $this->getList('x:attributes/x:attr', $domElement, true);
        foreach ($attributeNodes as $AttrNode) {
            if (!$ident = $this->getAttribute($AttrNode, 'ident')) {
                continue;
            }

            $attrDto      = $this->getObjectProperties($AttrNode, ['caption', 'numericValue', 'textValue']);
            $attributes[] = new Attribute($ident, $attrDto->caption, $attrDto->numericValue, $attrDto->textValue);
        }

        return $attributes;
    }

    /**
     * Validates the document
     *
     * @throws ImportException
     * @return mixed -
     */
    private function validateDocument()
    {
        $rootNode = $this->xPath->query('/x:elca')->item(0);
        if (!$rootNode) {
            throw ImportException::documentHasInvalidRootElement();
        }

        if (null !== $this->xsdSchemaPath) {
            if (!$this->document->schemaValidate($this->xsdSchemaPath)) {
                throw ImportException::documentValidationFailed(basename($this->xsdSchemaPath));
            }
        }

        return $rootNode;
    }

    // End validateDocument


    private function roundOrDefault($value, $precision, $defaultValue = null)
    {
        if (null === $value || '' === $value) {
            return $defaultValue;
        }

        return round($value, $precision);
    }
}
