<?php


namespace ImportAssistant\Model\SchemaImporter;


use Elca\Db\ElcaProcessConfig;
use ImportAssistant\Model\Import\Attribute;
use ImportAssistant\Model\Importer;
use ImportAssistant\Model\ImportException;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;
use ImportAssistant\Model\SchemaImporter;

abstract class AbstractSchemaImporter implements SchemaImporter
{
    /**
     * @var \DOMXPath
     */
    protected $xPath;

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var string
     */
    private $xsdBasePath;

    /**
     * @var Importer
     */
    private $importer;

    /**
     * @var MaterialMappingInfo[]
     */
    protected $materialMappingInfos;

    /**
     * @var int
     */
    protected $processDbId;

    public function __construct(\DOMDocument $document)
    {
        $this->document = $document;

        $this->xPath = new \DOMXPath($document);
        $this->xPath->registerNamespace('x', SchemaImporter::NAMESPACE);
    }

    public function assertVersion(): bool
    {
        return $this->schemaVersion() === $this->extractSchemaVersion();
    }

    public function isValid(string $xsdBasePath): bool
    {
        return $this->document->schemaValidate($this->resolveSchema($xsdBasePath));
    }

    protected function setMaterialMappingInfos(array $materialMappingInfos)
    {
        $this->materialMappingInfos = $materialMappingInfos;
    }

    protected function setProcessDbId(int $processDbId)
    {
        $this->processDbId = $processDbId;
    }

    /**
     * @param $processConfigName
     * @return MaterialMappingInfo
     */
    protected function getMaterialMappingInfo($processConfigName): MaterialMappingInfo
    {
        $processConfigNameCI = \utf8_strtolower($processConfigName);
        if (isset($this->materialMappingInfos[$processConfigNameCI])) {
            return $this->materialMappingInfos[$processConfigNameCI];
        }

        if ($this->processDbId) {
            $processConfig = ElcaProcessConfig::findCaseInsensitiveByProcessNameAndProcessDbId($processConfigName, $this->processDbId);

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

    protected function rootNode(): \DOMElement
    {
        /**
         * @var \DOMElement $rootNode
         */
        $rootNode = $this->xPath->query('/x:elca')->item(0);

        if (!$rootNode) {
            throw ImportException::documentHasInvalidRootElement();
        }

        return $rootNode;
    }

    /**
     * Returns object properties by the given simple elements
     * This method is the oposite of Exporter::appendObjectProperties
     *
     * @param \DOMElement $domElement
     * @param array       $properties
     * @param null|object $dto
     * @return object
     */
    protected function getObjectProperties(\DOMElement $domElement, array $properties, $dto = null)
    {
        $dto = $dto ?? new \stdClass();

        foreach ($properties as $property) {
            $value    = null;
            $nodeList = $this->xPath->query('./x:' . $property . '/text()', $domElement);

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
     * @param \DOMElement $domElement
     * @param array       $attributes
     * @param null|object $dto
     * @return object
     */
    protected function getObjectAttributeProperties(\DOMElement $domElement, array $attributes, $dto = null)
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
     * @param string      $xpath
     * @param \DOMElement $context
     * @param bool        $isOptional
     * @return \DOMElement
     * @throws ImportException
     */
    protected function getNode($xpath, \DOMElement $context = null, $isOptional = false)
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
     * Returns an attribute value.
     *
     * @param \DOMElement $domElement
     * @param string      $name
     * @param mixed       $defaultValue
     * @param bool        $isOptional
     * @return string
     * @throws ImportException
     */
    protected function getAttribute(\DOMElement $domElement, $name, $defaultValue = null, $isOptional = false)
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
     * @param \DOMElement $domElement
     * @return array
     */
    protected function getAttributes(\DOMElement $domElement): array
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
     * Returns a DOMNodelist
     *
     * @param string      $xpath
     * @param \DOMElement $context
     * @param bool        $isOptional
     * @return \DOMNodelist
     * @throws ImportException
     */
    protected function getList($xpath, \DOMElement $context = null, $isOptional = false)
    {
        if ($result = $this->xPath->query($xpath, $context)) {
            return $result;
        }

        if ($isOptional) {
            return new \DOMNodelist();
        }

        throw ImportException::pathNotFound($xpath, $context ? $context->getNodePath() : '/');
    }

    protected function roundOrDefault($value, $precision, $defaultValue = null)
    {
        if (null === $value || '' === $value) {
            return $defaultValue;
        }

        return round($value, $precision);
    }


    private function extractSchemaVersion(): int
    {
        $rootNode = $this->rootNode();

        if ($rootNode->hasAttribute('schemaVersion')) {
            $schemaVersion = (int)$rootNode->getAttribute('schemaVersion');
        }

        if (!$schemaVersion || $schemaVersion < 1) {
            $schemaVersion = 1;
        }

        return $schemaVersion;
    }

    private function resolveSchema(string $xsdBasePath): string
    {
        return sprintf('%s%s', $xsdBasePath, $this->schema());
    }
}