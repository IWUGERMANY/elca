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

namespace Elca\Model\Export\Xml;

use Beibob\Blibs\DbObject;
use DOMDocument;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProcessConversionVersion;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectIndicatorBenchmarkSet;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Service\ElcaElementImageCache;

/**
 * Xml Exporter
 *
 * @package   elca
 * @author    Tobias Lode <tobias@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Exporter
{
    /**
     * XSD file name
     */
    const XSD_FILE_NAME = 'elca_export.xsd';
    const XSD_DIR = 'docs';
    const SCHEMA_NAMESPACE = 'https://www.bauteileditor.de';
    const SCHEMA_VERSION = '1.3';
    const SCHEMA_VERSION_LEGACY = '1.2';

    /**
     * Singleton instance
     */
    private static $instance;

    /**
     * @var DOMDocument
     */
    private $document;


    /**
     * Returns the singelton
     *
     * @return Exporter
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Exporter();
        }

        return self::$instance;
    }
    // End getInstance

    /**
     * Constructor
     *
     * @return Exporter
     */
    private function __construct()
    {
        $this->document                   = new DOMDocument('1.0', 'UTF-8');
        $this->document->validateOnParse  = false;
        $this->document->resolveExternals = false;
        $this->document->formatOutput     = true;
    }
    // End exportProjectVariant

    /**
     * Exports a projectVariant
     *
     * @param ElcaProject $project
     * @return DOMDocument
     */
    public function exportProject(ElcaProject $project)
    {
        $processDbId = $project->getProcessDbId();

        /**
         * Create project container inside elca root element
         */
        $projectNode =
            $this->createRootNode()
                 ->appendChild(
                     $this->get(
                         'project',
                         [
                             'processDbUuid'     => $project->getProcessDb()->getUuid(),
                             'lifeTime'          => $project->getLifeTime(),
                             'constrMeasure'     => $project->getConstrMeasure(),
                             'constrClassRefNum' => $project->getConstrClass()->getRefNum(),
                         ]
                     )
                 );

        /**
         * Create project info
         */
        $projectInfoNode = $projectNode->appendChild($this->get('projectInfo'));
        $this->appendObjectProperties(
            $projectInfoNode,
            $project,
            [
                'name',
                'description',
                'projectNr',
            ]
        );

        /**
         * Add project attributes
         */
        $projectAttribute = ElcaProjectAttribute::findByProjectIdAndIdent(
            $project->getId(),
            ElcaProjectAttribute::IDENT_IS_LISTED
        );

        if ($projectAttribute->getNumericValue() > 0) {
            $projectInfoNode->appendChild($this->get('isListed'));
        }

        /**
         * Variants
         */
        $variantsNode = $projectNode->appendChild($this->get('projectVariants'));

        foreach (ElcaProjectVariantSet::findByProjectId($project->getId(), [], ['id' => 'ASC']) as $variant) {
            $attributes = ['phaseIdent' => $variant->getPhase()->getIdent()];

            if ($project->getCurrentVariantId() == $variant->getId()) {
                $attributes['isCurrent'] = 'true';
            }

            $variantNode = $variantsNode->appendChild($this->get('variant', $attributes));

            $this->appendObjectProperties($variantNode, $variant, ['name']);

            /**
             * location
             */
            $projectLocation = ElcaProjectLocation::findByProjectVariantId($variant->getId());
            $locationNode    = $variantNode->appendChild($this->get('location'));
            $this->appendObjectProperties(
                $locationNode,
                $projectLocation,
                [
                    'street',
                    'postcode',
                    'city',
                    'country',
                ]
            );

            /**
             * construction
             */
            $projectConstr = ElcaProjectConstruction::findByProjectVariantId($variant->getId());
            $constrNode    = $variantNode->appendChild($this->get('construction'));
            $constrNode->setAttribute('isExtantBuilding', $projectConstr->isExtantBuilding() ? 'true' : 'false');

            $this->appendObjectProperties(
                $constrNode,
                $projectConstr,
                [
                    'grossFloorSpace',
                    'netFloorSpace',
                    'floorSpace',
                    'propertySize',
                    'livingSpace',
                ]
            );

            /**
             * constr catalog and design
             */
            $catalog = $projectConstr->getConstrCatalog();
            if ($catalog->isInitialized()) {
                $constrNode->appendChild($this->get('constrCatalog', ['ident' => $catalog->getIdent()]));
            }

            $design = $projectConstr->getConstrDesign();
            if ($design->isInitialized()) {
                $constrNode->appendChild($this->get('constrDesign', ['ident' => $design->getIdent()]));
            }


            /**
             * Export construction elements
             */
            $elementsNode = $variantNode->appendChild($this->get('elements'));
            foreach (ElcaElementSet::findByProjectVariantId($variant->getId()) as $Element) {
                $this->appendElement($elementsNode, $Element, $processDbId);
            }

            /**
             * Export final energy demands
             */
            $projectEnEv = ElcaProjectEnEv::findByProjectVariantId($variant->getId());

            if ($projectEnEv->isInitialized()) {
                $containerNode = $variantNode->appendChild(
                    $this->get(
                        'finalEnergyDemands',
                        ['ngfEnEv' => $projectEnEv->getNgf(), 'enEvVersion' => (int)$projectEnEv->getVersion()]
                    )
                );
                foreach (
                    ElcaProjectFinalEnergyDemandSet::findByProjectVariantId(
                        $variant->getId()
                    ) as $finalEnergyDemand
                ) {
                    $fedNode = $containerNode->appendChild(
                        $this->get(
                            'finalEnergyDemand',
                            [
                                'processConfigUuid' => $finalEnergyDemand->getProcessConfig()->getUuid(),
                                'processConfigName' => $finalEnergyDemand->getProcessConfig()->getName(),
                            ]
                        )
                    );
                    $this->appendObjectProperties(
                        $fedNode,
                        $finalEnergyDemand,
                        ['heating', 'water', 'lighting', 'ventilation', 'cooling'],
                        true
                    );
                }

                $elcaProjectFinalEnergySupplySet = ElcaProjectFinalEnergySupplySet::findByProjectVariantId(
                    $variant->getId()
                );

                if ($elcaProjectFinalEnergySupplySet->count()) {
                    $containerNode = $variantNode->appendChild(
                        $this->get(
                            'finalEnergySupplies',
                            ['ngfEnEv' => $projectEnEv->getNgf(), 'enEvVersion' => (int)$projectEnEv->getVersion()]
                        )
                    );
                    /**
                     * @var ElcaProjectFinalEnergySupply $finalEnergySupply
                     */
                    foreach ($elcaProjectFinalEnergySupplySet as $finalEnergySupply) {
                        $fedNode = $containerNode->appendChild(
                            $this->get(
                                'finalEnergySupply',
                                [
                                    'processConfigUuid' => $finalEnergySupply->getProcessConfig()->getUuid(),
                                    'processConfigName' => $finalEnergySupply->getProcessConfig()->getName(),
                                    'quantity'          => $finalEnergySupply->getQuantity(),
                                    'enevRatio'         => $finalEnergySupply->getEnEvRatio(),
                                ]
                            )
                        );
                        $this->appendObjectProperties($fedNode, $finalEnergySupply, ['description'], true);
                    }
                }
            }

            /**
             * Export indicator benchmarks
             */
            $benchmarks = ElcaProjectIndicatorBenchmarkSet::find(['project_variant_id' => $variant->getId()]);
            if ($benchmarks->count()) {
                $benchmarkNodes = $variantNode->appendChild($this->get('indicatorBenchmarks'));
                foreach ($benchmarks as $benchmark) {
                    $benchmarkNodes->appendChild(
                        $this->get(
                            'benchmark',
                            [
                                'indicatorIdent' => $benchmark->getIndicator()->getIdent(),
                                'value'          => $benchmark->getBenchmark(),
                            ]
                        )
                    );
                }
            }

            $this->appendAttributesNode(
                $variantNode,
                $variant->getAttributes()->getArrayCopy()
            );
        }

        /**
         * Append attributes
         */
        $this->appendAttributesNode(
            $projectNode,
            $project->getAttributes()->getArrayCopy()
        );

        return $this->document;
    }


    /**
     * Exports an element
     *
     * @param  ElcaElement $element
     * @return DOMDocument
     */
    public function exportElement(ElcaElement $element)
    {
        /**
         * Create root element
         */
        $root = $this->createRootNode();

        /**
         * Append referenced elements first
         */
        if ($element->isComposite()) {
            foreach ($element->getCompositeElements() as $assignment) {
                $this->appendElement($root, $assignment->getElement());
            }
        }

        /**
         * Append requested element
         */
        $this->appendElement($root, $element);

        return $this->document;
    }
    // End appendElement

    /**
     * Appends an element
     *
     * @param  DOMElement  $container
     * @param  ElcaElement $element
     * @return void
     */
    protected function appendElement(DOMElement $container, ElcaElement $element, $processDbId = null)
    {
        /**
         * @var DOMElement $elementNode
         */
        $elementNode = $container->appendChild($this->get($element->isComposite() ? 'composite' : 'element'));

        $elementNode->setAttribute('uuid', $element->getUuid());
        $elementNode->setAttribute('din276Code', $element->getElementTypeNode()->getDinCode());
        $elementNode->setAttribute('quantity', $element->getQuantity());
        $elementNode->setAttribute('refUnit', $element->getRefUnit());

        $elementInfo = $elementNode->appendChild($this->get('elementInfo'));
        $this->appendObjectProperties($elementInfo, $element, ['name', 'description']);

        if ($element->isComposite()) {
            $elementsNode = $elementNode->appendChild($this->get('elements'));
            foreach ($element->getCompositeElements() as $assignedElement) {
                $elementsNode->appendChild(
                    $this->get(
                        'referenceToElement',
                        [
                            'position' => $assignedElement->getPosition(),
                            'uuid'     => $assignedElement->getElement()->getUuid(),
                        ]
                    )
                );
            }
        } else {
            $this->appendElementComponents($elementNode, $element, $processDbId);
        }

        /**
         * Append catalogs
         */
        $catalogNode = $elementNode->appendChild($this->get('constrCatalogs'));
        foreach ($element->getConstrCatalogs() as $constrCatalog) {
            $catalogNode->appendChild($this->get('item', ['ident' => $constrCatalog->getIdent()]));
        }

        $catalogNode = $elementNode->appendChild($this->get('constrDesigns'));
        foreach ($element->getConstrDesigns() as $constrDesign) {
            $catalogNode->appendChild($this->get('item', ['ident' => $constrDesign->getIdent()]));
        }

        /**
         * Append attributes
         */
        $this->appendAttributesNode(
            $elementNode,
            $element->getAttributes()->getArrayCopy(),
            function (ElcaElementAttribute $attribute) {
                /**
                 * Don't export image cache attribute
                 */
                return \utf8_strpos(
                           $attribute->getIdent(),
                           ElcaElementImageCache::SVG_IMAGE_CACHE_ATTRIBUTE_IDENT
                       ) === false;
            }
        );
    }
    // End appendElementComponents

    /**
     * Appends element components
     *
     * @param  DOMElement  $container
     * @param  ElcaElement $element
     * @return DOMElement
     */
    protected function appendElementComponents(DOMElement $container, ElcaElement $element, $processDbId)
    {
        $componentsNode = $container->appendChild($this->get('components'));

        $siblingNodes = [];

        /**
         * @var ElcaElementComponent $component
         */
        foreach (
            ElcaElementComponentSet::findByElementId(
                $element->getId(),
                [],
                ['is_layer' => 'ASC', 'layer_position' => 'ASC', 'id' => 'ASC']
            ) as $component
        ) {
            $containerNode = $componentsNode;
            $componentNode = $this->get(
                'component',
                [
                    'isLayer'           => $component->isLayer() ? 'true' : 'false',
                    'processConfigUuid' => $component->getProcessConfig()->getUuid(),
                    'processConfigName' => $component->getProcessConfig()->getName(),
                    'lifeTime'          => $component->getLifeTime(),
                    'lifeTimeDelay'     => $component->getLifeTimeDelay(),
                    'calcLca'           => $component->getCalcLca() ? 'true' : 'false',
                    'isExtant'          => $component->isExtant() ? 'true' : 'false',
                ]
            );

            if ($component->isLayer()) {
                if ($siblingId = $component->getLayerSiblingId()) {
                    if (isset($siblingNodes[$siblingId])) {
                        $containerNode = $siblingNodes[$siblingId];
                    } else {
                        $containerNode = $siblingNodes[$component->getId()] = $componentsNode->appendChild(
                            $this->get('siblings')
                        );
                    }
                }

                $this->addAttributes(
                    $componentNode,
                    [
                        'layerPosition'  => $component->getLayerPosition(),
                        'layerSize'      => $component->getLayerSize(),
                        'layerAreaRatio' => $component->getLayerAreaRatio(),
                        'layerLength'    => $component->getLayerLength(),
                        'layerWidth'     => $component->getLayerWidth(),
                    ]
                );
            } else {
                $conversion = $component->getProcessConversion();

                // conversion factor is unknown for template element exports
                $versionedConversionFactor = null;
                if (!$element->isTemplate()) {
                    $conversionVersion         = ElcaProcessConversionVersion::findByPK($conversion->getId(),
                        $processDbId);
                    $versionedConversionFactor = $conversionVersion->getFactor();
                }

                $this->addAttributes(
                    $componentNode,
                    [
                        'quantity'          => $component->getQuantity(),
                        'conversionInUnit'  => $conversion->getInUnit(),
                        'conversionOutUnit' => $conversion->getOutUnit(),
                        'conversionFactor'  => $versionedConversionFactor,
                    ]
                );
            }

            $containerNode->appendChild($componentNode);

            /**
             * Append attributes
             */
            $this->appendAttributesNode(
                $componentNode,
                $component->getAttributes()->getArrayCopy()
            );
        }
    }

    /**
     * Returns the root element
     *
     * @param  -
     * @return DOMElement
     */
    protected function createRootNode()
    {
        /**
         * @var DOMElement $root
         */
        $root = $this->document->appendChild(
            $this->document->createElementNS(self::SCHEMA_NAMESPACE, 'elca')
        );

        $root->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'schemaLocation',
            sprintf(
                '%s https://www.bauteileditor.de/%s/%s/%s',
                self::SCHEMA_NAMESPACE,
                Exporter::XSD_DIR,
                self::SCHEMA_VERSION,
                Exporter::XSD_FILE_NAME
            )
        );

        $root->setAttribute('schemaVersion', self::SCHEMA_VERSION);

        return $root;
    }

    /**
     * Appends the given properties as simple elements
     *
     * @param  DOMElement $container
     * @param  DbObject   $dbObject
     * @param  array      $properties
     * @param bool        $omitIfEmpty
     * @return DOMElement
     */
    protected function appendObjectProperties(
        DOMNode $container,
        DbObject $dbObject,
        array $properties,
        $omitIfEmpty = false
    ) {
        foreach ($properties as $property) {
            if (!isset($dbObject->$property)) {
                continue;
            }

            $value = $dbObject->$property;
            if (!$value && $omitIfEmpty) {
                continue;
            }

            $container->appendChild($this->get($property, null, $value));
        }

        return $container;
    }

    /**
     * Creates an element
     *
     * @param  string $name
     * @param  array  $attributes
     * @param         string value
     * @return DOMElement
     */
    protected function get($name, array $attributes = null, $content = null)
    {
        $element = $this->document->createElement($name);

        if (null !== $attributes) {
            $this->addAttributes($element, $attributes);
        }

        if (null !== $content) {
            if (is_numeric($content)) {
                $element->appendChild($this->document->createTextNode($content));
            } else {
                $element->appendChild($this->document->createCDATASection($content));
            }
        }

        return $element;
    }


    /**
     * Adds attributes to a DOMElement
     *
     * @param  DOMElement $element
     * @param  array      $attributes
     * @return DOMElement
     */
    protected function addAttributes(DOMNode $element, array $attributes)
    {
        foreach ($attributes as $attr => $value) {
            $element->setAttribute((string)$attr, (string)$value);
        }
    }

    private function appendAttributesNode(
        DOMNode $parentNode,
        array $variantAttributes,
        \Closure $filterCallback = null
    ) {
        $attributesNode = $parentNode->appendChild($this->get('attributes'));
        foreach ($variantAttributes as $attribute) {
            if (null !== $filterCallback && false === $filterCallback($attribute)) {
                continue;
            }

            $hasNumVal = $attribute->getNumericValue();
            $hasTxtVal = $attribute->getTextValue();

            $attrNode = $attributesNode->appendChild($this->get('attr', ['ident' => $attribute->getIdent()]));

            $properties = ['caption'];
            if ($hasNumVal) {
                $properties[] = 'numericValue';
            }

            if ($hasTxtVal) {
                $properties[] = 'textValue';
            }

            $this->appendObjectProperties($attrNode, $attribute, $properties);
        }
    }
}
