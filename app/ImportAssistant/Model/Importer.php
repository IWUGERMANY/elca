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
    private $xsdSchemaBasePath;

    /**
     * @var null
     */
    private $processDbId;

    /**
     * @var int
     */
    private $schemaVersion;

    /**
     * Importer constructor.
     *
     * @param MaterialMappingInfoRepository $materialMappingRepository
     * @param string                        $xsdSchemaBasePath
     * @param                               $processDbId
     */
    public function __construct(
        MaterialMappingInfoRepository $materialMappingRepository, string $xsdSchemaBasePath, int $processDbId) {
        $this->materialMappingInfos = $materialMappingRepository->findByProcessDbId($processDbId);
        $this->xsdSchemaBasePath    = $xsdSchemaBasePath;
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
        $this->schemaVersion = $this->validateDocument();

        switch ($this->schemaVersion) {

        }

        $projectNode = $this->xPath->query('/x:elca/x:project')->item(0);

        return $this->importProjectNode($projectNode);
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
     * Validates the document
     *
     * @throws ImportException
     * @return mixed -
     */
    private function validateDocument()
    {
        /**
         * @var \DOMElement $rootNode
         */
        $rootNode = $this->xPath->query('/x:elca')->item(0);
        if (!$rootNode) {
            throw ImportException::documentHasInvalidRootElement();
        }

        if (null !== $this->xsdSchemaBasePath) {
            $schemaVersion = $this->extractSchemaVersion($rootNode);

            $xsdSchema = $this->resolveSchema($schemaVersion);

            if (!$this->document->schemaValidate($xsdSchema)) {
                throw ImportException::documentValidationFailed(basename($xsdSchema));
            }
        }

        return $schemaVersion;
    }


    private function resolveSchema($schemaVersion): string
    {
        $xsdSchema = sprintf(
            '%sexport%s.xsd',
            $this->xsdSchemaBasePath,
            $schemaVersion > 1 ? '-' . $schemaVersion : ''
        );

        return $xsdSchema;
    }

    private function extractSchemaVersion(\DOMElement $rootNode): int
    {
        if ($rootNode->hasAttribute('schemaVersion')) {
            $schemaVersion = (int)$rootNode->getAttribute('schemaVersion');
        }

        if (!$schemaVersion || $schemaVersion < 1) {
            $schemaVersion = 1;
        }

        return $schemaVersion;
    }
}
