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

namespace Elca\Model\Import\Xml;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\Log;
use Beibob\Blibs\UserStore;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaAssistantSubElement;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaConstrCatalog;
use Elca\Db\ElcaConstrClass;
use Elca\Db\ElcaConstrDesign;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentAttribute;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectIndicatorBenchmark;
use Elca\Db\ElcaProjectKwk;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantAttribute;
use Elca\Elca;
use Elca\Model\Export\Xml\Exporter;
use Elca\Model\Import\ImportObserver;
use Elca\Service\ElcaElementImageCache;
use Exception;

/**
 * Helps importing xml documents which were generated with Exporter
 *
 * @package   elca
 * @author    Tobias Lode <tobias@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Importer
{
    /**
     * Errors
     */
    const ERR_INVALID_DOCUMENT = 1;
    const ERR_ELEMENT_MISSING = 2;
    const ERR_ATTRIBUTE_MISSING = 3;
    const ERR_PROCESS_DB_UNKNOWN = 4;
    const ERR_VERSION_MISSMATCH = 5;
    const ERR_MISSING_CONVERSION = 6;

    /**
     * Singleton instance
     */
    private static $Instance;

    /**
     * @var DOMDocument
     */
    private $document;

    /**
     * @var DOMXPath
     */
    private $xPath;

    /**
     * Logger
     */
    private $log;

    /**
     * Current user
     */
    private $user;

    /**
     * element uuid cache
     */
    private $elementUuids = [];

    private $assistantUuids = [];

    /**
     * @var ImportObserver[]
     */
    private $importObservers;

    /**
     * @var string
     */
    private $defaultPrefix;

    /**
     * @var string
     */
    private $schemaVersion;

    /**
     * Returns the singelton
     *
     * @return Importer
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new Importer();
        }

        return self::$Instance;
    }
    // End getInstance

    /**
     * @return string
     *
     * @translate value 'Importiert'
     */
    public static function getNameSuffix()
    {
        return ' (' . t('Importiert') . ')';
    }

    /**
     * Constructor
     *
     * @throws Exception
     * @return Importer
     */
    private function __construct()
    {
        $this->user = UserStore::getInstance()->getUser();

        if (!$this->user->isInitialized()) {
            throw new Exception('No access');
        }

        $this->log = Log::getInstance();

        $this->importObservers = [];
    }

    /**
     * @param array $observers
     */
    public function registerObservers(array $observers)
    {
        $this->importObservers = $observers;
    }
    // End importProject

    /**
     * Imports a project
     *
     * @param File $file
     * @return ElcaProject
     */
    public function importProject(File $file)
    {
        $this->initDOMDocumentFromFile($file);

        /**
         * validate document
         */
        $this->validateDocument();

        /**
         * Import projects
         */
        $project  = null;
        $projects = $this->query('/{p:}elca/{p:}project');

        foreach ($projects as $node) {
            $project = $this->importProjectNode($node);

            foreach ($this->importObservers as $observer) {
                $observer->onProjectImport($project);
            }
        }

        return $project;
    }

    /**
     * Imports elements
     *
     * @param DOMDocument
     * @return ElcaElement
     */
    public function importElement(File $file)
    {
        $this->initDOMDocumentFromFile($file);

        /**
         * validate document
         */
        $this->validateDocument();

        /**
         * Import template elements
         */
        $element  = null;
        $elements = $this->query('/{p:}elca/{p:}element');
        foreach ($elements as $node) {
            $element = $this->importElementNode($node);

            foreach ($this->importObservers as $observer) {
                $observer->onElementImport($element);
            }
        }

        $composites = $this->query('/{p:}elca/{p:}composite');
        foreach ($composites as $node) {
            $element = $this->importElementNode($node, null, true);

            foreach ($this->importObservers as $observer) {
                $observer->onElementImport($element);
            }
        }

        return $element;
    }
    // End getProject

    /**
     * Imports a project
     *
     * @param  DOMElement $projectNode
     * @throws Exception
     * @return ElcaProject
     */
    protected function importProjectNode(DOMElement $projectNode)
    {
        $processDb = ElcaProcessDb::findByUuid($processDbUuid = $this->getAttribute($projectNode, 'processDbUuid'));

        if (!$processDb->isInitialized()) {
            throw new Exception(
                'ProcessDb with UUID `' . $processDbUuid . '\' unknown in current installation',
                self::ERR_PROCESS_DB_UNKNOWN
            );
        }

        $lifeTime          = $this->getAttribute($projectNode, 'lifeTime', 50);
        $constrMeasure     = $this->getAttribute($projectNode, 'constrMeasure', Elca::CONSTR_MEASURE_PRIVATE);
        $constrClassRefNum = $this->getAttribute($projectNode, 'constrClassRefNum', null, true);

        /**
         * projectInfos
         */
        $node = $this->getNode('{p:}projectInfo', $projectNode);
        $dataObject   = $this->getObjectProperties($node, ['name', 'description', 'projectNr']);

        $project = ElcaProject::create(
            $processDb->getId(),
            $this->user->getId(),
            $this->user->getGroupId(),
            $dataObject->name . self::getNameSuffix(),
            $lifeTime,
            null,
            $dataObject->description,
            $dataObject->projectNr,
            $constrMeasure,
            $constrClassRefNum ? ElcaConstrClass::findByRefNum($constrClassRefNum)->getId() : null
        );

        if (!$project->isInitialized()) {
            $this->log->error($project->getValidator()->getErrors(), __METHOD__);
            throw new Exception(
                'Project is invalid on line '.$projectNode->getNodePath(), self::ERR_INVALID_DOCUMENT
            );
        }

        $this->log->notice('Created Project `'.$dataObject->name.'` ('.$project->getId().')', __METHOD__);

        /**
         * Project attributes
         */

        /**
         * Variants
         */
        $currentVariantId = null;
        $variantNodes     = $this->getList('{p:}projectVariants/{p:}variant', $projectNode);
        foreach ($variantNodes as $variantNode) {
            $phaseIdent = $this->getAttribute($variantNode, 'phaseIdent');
            $dataObject = $this->getObjectProperties($variantNode, ['name']);

            $variant = ElcaProjectVariant::create(
                $project->getId(),
                ElcaProjectPhase::findByConstrMeasureAndIdent($project->getConstrMeasure(), $phaseIdent)->getId(),
                $dataObject->name
            );

            if (!$variant->isInitialized()) {
                $this->log->error($variant->getValidator()->getErrors(), __METHOD__);
                throw new Exception(
                    'variant is invalid on line ' . $variantNode->getNodePath(),
                    self::ERR_INVALID_DOCUMENT
                );
            }

            $this->log->notice('Created ProjectVariant `'.$dataObject->name.'` ('.$variant->getId().')', __METHOD__);

            if ($this->getAttribute($variantNode, 'isCurrent', '', true) === 'true') {
                $currentVariantId = $variant->getId();
            }

            /**
             * ProjectLocation
             */
            $node = $this->getNode('{p:}location', $variantNode);
            $dataObject   = $this->getObjectProperties($node, ['street', 'postcode', 'city', 'country']);

            $location = ElcaProjectLocation::create(
                $variant->getId(),
                $dataObject->street,
                $dataObject->postcode,
                $dataObject->city,
                $dataObject->country
            );

            if (!$location->isInitialized()) {
                throw new Exception('location is invalid on line ' . $node->getNodePath(), self::ERR_INVALID_DOCUMENT);
            }

            $this->log->notice('Created ProjectLocation', __METHOD__);

            /**
             * ProjectConstruction
             */
            $node             = $this->getNode('{p:}construction', $variantNode);
            $isExtantBuilding = $this->getAttribute($node, 'isExtantBuilding') === 'true' ? true : false;

            $dataObject = $this->getObjectProperties(
                $node,
                [
                    'grossFloorSpace',
                    'netFloorSpace',
                    'floorSpace',
                    'propertySize',
                    'livingSpace'
                ]
            );

            if ($catNode = $this->getNode('{p:}construction/{p:}constrCatalog', $variantNode, true)) {
                $dataObject->constrCatalogIdent = $this->getAttribute($catNode, 'ident');
            }

            if ($desNode = $this->getNode('{p:}construction/{p:}constrDesign', $variantNode, true)) {
                $dataObject->constrDesignIdent = $this->getAttribute($desNode, 'ident');
            }

            $construction = ElcaProjectConstruction::create(
                $variant->getId(),
                isset($dataObject->constrCatalogIdent) ? ElcaConstrCatalog::findByIdent($dataObject->constrCatalogIdent)->getId()
                    : null,
                isset($dataObject->constrDesignIdent) ? ElcaConstrDesign::findByIdent($dataObject->constrDesignIdent)->getId() : null,
                $dataObject->grossFloorSpace,
                $dataObject->netFloorSpace,
                $dataObject->floorSpace,
                $dataObject->propertySize,
                $dataObject->livingSpace,
                $isExtantBuilding
            );

            if (!$construction->isInitialized()) {
                throw new Exception('construction is invalid on ' . $node->getNodePath(), self::ERR_INVALID_DOCUMENT);
            }

            $this->log->notice('Created ProjectConstruction', __METHOD__);

            /**
             * construction elements
             */
            $elementNodes = $this->getList('{p:}elements/{p:}element', $variantNode);
            foreach ($elementNodes as $ElementNode) {
                $this->importElementNode($ElementNode, $variant);
            }

            $compositeNodes = $this->getList('{p:}elements/{p:}composite', $variantNode);
            foreach ($compositeNodes as $compositeNode) {
                $this->importElementNode($compositeNode, $variant, true);
            }

            /**
             * FinalEnergyDemands
             */
            if ($node = $this->getNode('{p:}finalEnergyDemands', $variantNode, true)) {
                if ($ngfEnEv = $this->getAttribute($node, 'ngfEnEv', true)) {
                    $enEvVersion = (int)$this->getAttribute($node, 'enEvVersion', true);
                    $projectEnEv = ElcaProjectEnEv::create($variant->getId(), $ngfEnEv, $enEvVersion);

                    if (!$projectEnEv->isInitialized()) {
                        throw new Exception(
                            'NGF EnEv is invalid on ' . $node->getNodePath(), self::ERR_INVALID_DOCUMENT
                        );
                    }

                    $nodes = $this->getList('{p:}finalEnergyDemands/{p:}finalEnergyDemand', $variantNode);
                    foreach ($nodes as $node) {
                        $processConfigUuid = $this->getAttribute($node, 'processConfigUuid');
                        $kwkName           = $this->getAttribute($node, 'kwk', null, true);
                        $fedRatio          = $this->getAttribute($node, 'ratio', 1, true);
                        $processConfig     = ElcaProcessConfig::findByUuid($processConfigUuid);
                        $dataObject        = $this->getObjectProperties(
                            $node,
                            ['heating', 'water', 'lighting', 'ventilation', 'cooling']
                        );

                        $kwkId = null;
                        if ($kwkName) {
                            $kwkId = $this->createOrFindProjectKwkId($variant, $kwkName, $dataObject);
                        }

                        $fed = ElcaProjectFinalEnergyDemand::create(
                            $variant->getId(),
                            $processConfig->getId(),
                            $dataObject->heating,
                            $dataObject->water,
                            $dataObject->lighting,
                            $dataObject->ventilation,
                            $dataObject->cooling,
                            null,
                            $fedRatio,
                            $kwkId
                        );

                        if (!$fed->isInitialized()) {
                            throw new Exception(
                                'FinalEnergyDemand is invalid on ' . $node->getNodePath(),
                                self::ERR_INVALID_DOCUMENT
                            );
                        }

                        $this->log->notice(
                            'Created FinalEnergyDemand `' . $processConfig->getName() . '\' (' . $fed->getId() . ')',
                            __METHOD__
                        );
                    }
                }
            }

            /**
             * FinalEnergySupply
             */
            if ($node = $this->getNode('{p:}finalEnergySupplies', $variantNode, true)) {
                if ($ngfEnEv = $this->getAttribute($node, 'ngfEnEv', true)) {
                    $enEvVersion = (int)$this->getAttribute($node, 'enEvVersion', true);

                    if ($projectEnEv = ElcaProjectEnEv::findByProjectVariantId($variant->getId())) {
                        $projectEnEv->setNgf($ngfEnEv);
                        $projectEnEv->setVersion($enEvVersion);
                        $projectEnEv->update();
                    } else {
                        $projectEnEv = ElcaProjectEnEv::create($variant->getId(), $ngfEnEv, $enEvVersion);
                    }

                    if (!$projectEnEv->isInitialized()) {
                        throw new Exception(
                            'NGF EnEv is invalid on ' . $node->getNodePath(), self::ERR_INVALID_DOCUMENT
                        );
                    }

                    $nodes = $this->getList('{p:}finalEnergySupplies/{p:}finalEnergySupply', $variantNode);
                    foreach ($nodes as $node) {
                        $processConfigUuid = $this->getAttribute($node, 'processConfigUuid');
                        $processConfig     = ElcaProcessConfig::findByUuid($processConfigUuid);
                        $quantity          = $this->getAttribute($node, 'quantity');
                        $enevRatio         = $this->getAttribute($node, 'enevRatio');
                        $dataObject                = $this->getObjectProperties($node, ['description']);
                        $description       = $dataObject ? $dataObject->description : null;

                        $fes = ElcaProjectFinalEnergySupply::create(
                            $variant->getId(),
                            $processConfig->getId(),
                            $quantity,
                            $description,
                            $enevRatio
                        );

                        if (!$fes->isInitialized()) {
                            throw new Exception(
                                'FinalEnergySupply is invalid on ' . $node->getNodePath(),
                                self::ERR_INVALID_DOCUMENT
                            );
                        }

                        $this->log->notice(
                            'Created FinalEnergySupply `' . $processConfig->getName() . '\' (' . $fes->getId() . ')',
                            __METHOD__
                        );
                    }
                }
            }


            /**
             * Indicator benchmarks
             */
            $benchmarkNodes = $this->getList('{p:}indicatorBenchmarks/{p:}benchmark', $variantNode, true);
            foreach ($benchmarkNodes as $benchmarkNode) {
                try {
                    ElcaProjectIndicatorBenchmark::create(
                        $variant->getId(),
                        ElcaIndicator::findByIdent($this->getAttribute($benchmarkNode, 'indicatorIdent'))->getId(),
                        $this->getAttribute($benchmarkNode, 'value')
                    );
                }
                catch (Exception $Exception) {
                }
            }

            /**
             * Project variant attributes
             */
            $attributeNodes = $this->getList('{p:}attributes/{p:}attr', $variantNode, true);
            foreach ($attributeNodes as $attrNode) {
                if (!$ident = $this->getAttribute($attrNode, 'ident')) {
                    continue;
                }

                $dataObject          = $this->getObjectProperties($attrNode, ['caption', 'numericValue', 'textValue']);
                $projectAttr = ElcaProjectVariantAttribute::create(
                    $variant->getId(),
                    $ident,
                    $dataObject->caption,
                    $dataObject->numericValue,
                    $dataObject->textValue
                );

                if (!$projectAttr->isInitialized()) {
                    throw new Exception(
                        'ProjectVariantAttribute `' . $ident . '\' is invalid in ' . $attrNode->getNodePath(),
                        self::ERR_INVALID_DOCUMENT
                    );
                }
            }
        }

        /**
         * Set current variant as the last variant
         */
        if (!$currentVariantId) {
            $currentVariantId = $variant->getId();
        }

        $project->setCurrentVariantId($currentVariantId);
        $project->update();

        /**
         * Project attributes
         */
        $attributeNodes = $this->getList('{p:}attributes/{p:}attr', $projectNode, true);
        foreach ($attributeNodes as $attrNode) {
            if (!$ident = $this->getAttribute($attrNode, 'ident')) {
                continue;
            }

            $dataObject          = $this->getObjectProperties($attrNode, ['caption', 'numericValue', 'textValue']);
            $projectAttr = ElcaProjectAttribute::create(
                $project->getId(),
                $ident,
                $dataObject->caption,
                $dataObject->numericValue,
                $dataObject->textValue
            );

            if (!$projectAttr->isInitialized()) {
                throw new Exception(
                    'ProjectAttribute `' . $ident . '\' is invalid in ' . $attrNode->getNodePath(),
                    self::ERR_INVALID_DOCUMENT
                );
            }
        }

        return $project;
    }
    // End importElement

    /**
     * Imports an element
     *
     * @param  DOMElement         $elementNode
     * @param  ElcaProjectVariant $variant
     * @param bool                $isComposite
     * @throws Exception
     * @return ElcaElement
     */
    protected function importElementNode(
        DOMElement $elementNode,
        ElcaProjectVariant $variant = null,
        $isComposite = false
    ) {
        $projectVariantId = $variant ? $variant->getId() : null;
        $uuid             = $this->getAttribute($elementNode, 'uuid', null, true);
        $dinCode          = $this->getAttribute($elementNode, 'din276Code');
        $quantity         = $this->getAttribute($elementNode, 'quantity', 1);
        $refUnit          = $this->getAttribute($elementNode, 'refUnit');

        // backward compatibility - old files use unit `Stk' instead of `Stück'
        if ($refUnit === 'Stk') {
            $refUnit = Elca::UNIT_STK;
        }

        $node = $this->getNode('{p:}elementInfo', $elementNode);
        $dataObject   = $this->getObjectProperties(
            $node,
            [
                'name',
                'description',
            ]
        );

        $elementType = ElcaElementType::findByIdent($dinCode);

        $element = ElcaElement::findByUuid($uuid);
        if (!$projectVariantId && $element->isInitialized() && $element->isPublic()) {
            $this->log->warning('Skipping existing public element `'.$element->getUuid().'\'', __METHOD__);
            // associate uuid with the elements id to keep the reference
            $this->elementUuids[$uuid] = $element->getId();

            return;
        }

        $element = ElcaElement::create(
            $elementType->getNodeId(),
            $projectVariantId ? $dataObject->name : $dataObject->name . self::getNameSuffix(), // append suffix only on element import
            $dataObject->description,
            false, // isPublic
            $this->user->getGroupId(),
            $projectVariantId,
            $quantity,
            $refUnit,
            null, // copyOfElementId
            $this->user->getId(),
            (!$projectVariantId && $element->isPublic()) ? $uuid : null // keep project elements unique
        );

        // associate old uuid with new Elements to keep references
        $this->elementUuids[$uuid] = $element->getId();

        if (!$element->isInitialized()) {
            throw new Exception(
                'Element `' . $uuid . '\' is invalid on line '.$elementNode->getNodePath(),
                self::ERR_INVALID_DOCUMENT
            );
        }

        $this->log->notice('Created element `'.$element->getUuid().'\'', __METHOD__);

        if ($isComposite) {
            $refElementNodes = $this->getList('{p:}elements/{p:}referenceToElement', $elementNode);
            foreach ($refElementNodes as $RefElement) {
                $pos         = $this->getAttribute($RefElement, 'position');
                $elementUuid = $this->getAttribute($RefElement, 'uuid');
                if (!isset($this->elementUuids[$elementUuid])) {
                    throw new Exception(
                        'Could not find referenced element `' . $elementUuid . '\'',
                        self::ERR_ELEMENT_MISSING
                    );
                }

                $elementId = $this->elementUuids[$elementUuid];

                $compositeElement = ElcaCompositeElement::create($element->getId(), $pos, $elementId);

                if (!$compositeElement->isInitialized()) {
                    throw new Exception(
                        'CompositeElement (`' . $uuid . '\', ' . $pos . ') is invalid in ' . $RefElement->getNodePath(),
                        self::ERR_INVALID_DOCUMENT
                    );
                }
            }
        } else {
            $componentNodes = $this->getList('{p:}components/{p:}component', $elementNode, true);
            foreach ($componentNodes as $componentNode) {
                $this->importComponent($componentNode, $element);
            }

            $componentNodes = $this->getList('{p:}components/{p:}siblings/{p:}component', $elementNode, true);
            if ($componentNodes && $componentNodes->length % 2 == 0) {
                for ($index = 0; $index < $componentNodes->length; $index += 2) {
                    $component = $this->importComponent($componentNodes->item($index), $element);
                    $sibling   = $this->importComponent(
                        $componentNodes->item($index + 1),
                        $element,
                        $component->getId()
                    );
                    $component->setLayerSiblingId($sibling->getId());
                    $component->update();
                }
            }
        }

        /**
         * Catalogs
         */
        $catalogNodes = $this->getList('{p:}constrCatalogs/{p:}item', $elementNode, true);
        foreach ($catalogNodes as $node) {
            $catalog = ElcaConstrCatalog::findByIdent($this->getAttribute($node, 'ident'));
            if ($catalog->isInitialized()) {
                $element->assignConstrCatalogId($catalog->getId());
            }
        }

        $catalogNodes = $this->getList('{p:}constrDesigns/{p:}item', $elementNode, true);
        foreach ($catalogNodes as $node) {
            $catalog = ElcaConstrDesign::findByIdent($this->getAttribute($node, 'ident'));
            if ($catalog->isInitialized()) {
                $element->assignConstrDesignId($catalog->getId());
            }
        }

        /**
         * Element attributes
         */
        $attributeNodes = $this->getList('{p:}attributes/{p:}attr', $elementNode, true);
        foreach ($attributeNodes as $AttrNode) {
            if (!$ident = $this->getAttribute($AttrNode, 'ident')) {
                continue;
            }

            /**
             * Don't import image cache attribute
             */
            if (\utf8_strpos($ident, ElcaElementImageCache::SVG_IMAGE_CACHE_ATTRIBUTE_IDENT) !== false) {
                continue;
            }

            $dataObject          = $this->getObjectProperties($AttrNode, ['caption', 'numericValue', 'textValue']);
            $elementAttr = ElcaElementAttribute::create(
                $element->getId(),
                $ident,
                $dataObject->caption,
                $dataObject->numericValue,
                $dataObject->textValue
            );

            if (!$elementAttr->isInitialized()) {
                throw new Exception(
                    'ElementAttribute `' . $ident . '\' is invalid in ' . $AttrNode->getNodePath(),
                    self::ERR_INVALID_DOCUMENT
                );
            }
        }

        /**
         * AssistantElement
         */

        $assistantNode = $this->getNode('{p:}assistant', $elementNode, true);

        if (null !== $assistantNode) {
            $assistantIdent = $this->getAttribute($assistantNode, 'ident');
            $assistantUuid  = $this->getAttribute($assistantNode, 'uuid');
            $assistantDO    = $this->getObjectProperties(
                $assistantNode,
                [
                    'config',
                    'element'
                ]
            );

            $assistantElement = $this->assistantUuids[$assistantUuid] ?? ElcaAssistantElement::create(
                $element->getId(),
                $assistantIdent,
                $element->getProjectVariantId(),
                $assistantDO->config ?? null,
                $element->isReference(),
                $element->isPublic(),
                $element->getOwnerId(),
                $element->getAccessGroupId()
            );
            $this->assistantUuids[$assistantUuid] = $assistantElement;

            // update main element, since subelements may have arised earlier and already triggered the creation
            // of the assistant element
            if (null !== $assistantDO->config && null === $assistantElement->getConfig()) {
                $assistantElement->setMainElementId($element->getId());
                $assistantElement->setIsPublic($element->isPublic());
                $assistantElement->setIsReference($element->isReference());
                $assistantElement->setOwnerId($element->getOwnerId());
                $assistantElement->setAccessGroupId($element->getAccessGroupId());
                $assistantElement->setConfig($assistantDO->config);
                $assistantElement->update();
            }

            ElcaAssistantSubElement::create($assistantElement->getId(), $element->getId(), $assistantDO->element);
        }

        return $element;
    }
    // End importComponent

    /**
     * Imports a component
     *
     * @param DOMElement  $componentNode
     * @param ElcaElement $element
     * @param null        $layerSiblingId
     * @throws Exception
     * @return ElcaElementComponent -
     */
    protected function importComponent(DOMElement $componentNode, ElcaElement $element, $layerSiblingId = null)
    {
        $processConfigUuid = $this->getAttribute($componentNode, 'processConfigUuid');
        $processConfig     = ElcaProcessConfig::findByUuid($processConfigUuid);

        if (false === $processConfig->isInitialized()) {
            $processConfig = ElcaProcessConfig::findUnknown();
        }

        $isLayer           = \utf8_strtolower($this->getAttribute($componentNode, 'isLayer')) === 'true' ? true : false;
        $lifeTime          = $this->getAttribute($componentNode, 'lifeTime', $processConfig->getMinLifeTime());
        $lifeTimeDelay     = $this->getAttribute($componentNode, 'lifeTimeDelay', 0, true);
        $quantity          = $this->getAttribute($componentNode, 'quantity', 1);
        $calcLca           = $this->getAttribute($componentNode, 'calcLca', true) === 'true' ? true : false;
        $isExtant          = $this->getAttribute($componentNode, 'isExtant', false, true) === 'true' ? true : false;

        $layerPosition  = $layerSize = null;
        $layerAreaRatio = $layerLength = $layerWidth = 1;

        if ($isLayer) {
            $conversionSet = ElcaProcessConversionSet::findByProcessConfigIdAndInUnit(
                $processConfig->getId(),
                'm3',
                ['id' => 'ASC'],
                1
            );
            if (!$conversionSet->count()) {
                throw new Exception(
                    'No conversion from m³ configured for ProcessConfig `' . $processConfig->getProcessCategory(
                    )->getRefNum() . ' ' . $processConfig->getName() . '\'', self::ERR_MISSING_CONVERSION
                );
            }

            $processConversionId = $conversionSet[0]->getId();

            $layerPosition  = $this->getAttribute($componentNode, 'layerPosition');
            $layerSize      = $this->getAttribute($componentNode, 'layerSize');
            $layerAreaRatio = $this->getAttribute($componentNode, 'layerAreaRatio', 1);
            $layerLength    = $this->getAttribute($componentNode, 'layerLength', 1);
            $layerWidth     = $this->getAttribute($componentNode, 'layerWidth', 1);
        } else {
            $inUnit  = $this->getAttribute($componentNode, 'conversionInUnit');
            $outUnit = $this->getAttribute($componentNode, 'conversionOutUnit');

            $conversion = ElcaProcessConversion::findByProcessConfigIdAndInOut($processConfig->getId(),
                $inUnit, $outUnit);

            $processConversionId = $conversion->getId();
        }

        $component = ElcaElementComponent::create(
            $element->getId(),
            $processConfig->getId(),
            $processConversionId,
            $lifeTime,
            $isLayer,
            $quantity,
            $calcLca,
            $isExtant,
            $layerPosition,
            $layerSize,
            $layerSiblingId,
            $layerAreaRatio,
            $layerLength,
            $layerWidth,
            $lifeTimeDelay
        );

        if (!$component->isInitialized()) {
            throw new Exception(
                'ElementComponent `' . $processConfig->getUuid(
                ) . '\' is invalid on line '.$componentNode->getNodePath(), self::ERR_INVALID_DOCUMENT
            );
        }

        if ($layerSiblingId) {
            $this->log->notice(
                'Created element component sibling with processConfigUUid `' . $processConfig->getUuid() . '\'',
                __METHOD__
            );
        } else {
            $this->log->notice(
                'Created element component with processConfigUUid `' . $processConfig->getUuid() . '\'',
                __METHOD__
            );
        }

        /**
         * ElementComponent attributes
         */
        $attributeNodes = $this->getList('{p:}attributes/{p:}attr', $componentNode, true);
        foreach ($attributeNodes as $AttrNode) {
            if (!$ident = $this->getAttribute($AttrNode, 'ident')) {
                continue;
            }

            $dataObject                   = $this->getObjectProperties($AttrNode, ['numericValue', 'textValue']);
            $elementComponentAttr = ElcaElementComponentAttribute::create(
                $component->getId(),
                $ident,
                $dataObject->numericValue,
                $dataObject->textValue
            );

            if (!$elementComponentAttr->isInitialized()) {
                throw new Exception(
                    'ElementComponentAttribute `' . $ident . '\' is invalid in ' . $AttrNode->getNodePath(),
                    self::ERR_INVALID_DOCUMENT
                );
            }
        }

        if ($processConfig->isUnknown()) {
            $processConfigName = $this->getAttribute($componentNode, 'processConfigName', null, true);

            if ($processConfigName) {
                ElcaElementComponentAttribute::create(
                    $component->getId(),
                    Elca::ELEMENT_COMPONENT_ATTR_UNKNOWN,
                    null,
                    $processConfigName
                );
            }
        }

        return $component;
    }
    // End getObjectProperties

    /**
     * Returns object properties by the given simple elements
     * This method is the oposite of Exporter::appendObjectProperties
     *
     * @see ElcaXmlExporter::appendObjectProperties
     * @param  DOMElement $container
     * @param  array      $properties
     * @param DbObject    $dbObject
     * @return object
     */
    protected function getObjectProperties(DOMElement $container, array $properties, DbObject $dbObject = null)
    {
        $dataObject = $dbObject instanceOf DbObject ? $dbObject : new \stdClass();

        foreach ($properties as $property) {
            $value    = null;
            $nodelist = $this->query('./{p:}'.$property.'/text()', $container);

            if ($nodelist && $nodelist->length > 0) {
                for ($i = 0; $i < $nodelist->length; $i++) {
                    $value .= $nodelist->item($i)->textContent;
                }
            }

            $dataObject->$property = $nodelist->length > 1 ? trim($value) : $value;
        }

        return $dataObject;
    }
    // End get

    /**
     * Returns either a single DOMElement
     *
     * @param  string     $xpath
     * @param  DOMElement $context
     * @param bool        $isOptional
     * @throws Exception
     * @return DOMElement
     */
    protected function getNode($xpath, DOMElement $context = null, $isOptional = false)
    {
        if ($result = $this->query($xpath, $context)) {
            if ($result->length > 0) {
                return $result->item(0);
            }
        }

        if ($isOptional) {
            return null;
        }

        throw new Exception(
            'Path `' . $xpath . '\' not found in context `' . ($context ? $context->getNodePath() : '/'),
            self::ERR_ELEMENT_MISSING
        );
    }
    // End getList

    /**
     * Returns a DOMNodelist
     *
     * @param  string     $xpath
     * @param  DOMElement $context
     * @param bool        $isOptional
     * @throws Exception
     * @return DOMNodelist
     */
    protected function getList($xpath, DOMElement $context = null, $isOptional = false)
    {
        if ($result = $this->query($xpath, $context)) {
            return $result;
        }

        if ($isOptional) {
            return new DOMNodelist();
        }

        throw new Exception(
            'Path `' . $xpath . '\' not found in context `' . ($context ? $context->getNodePath() : '/'),
            self::ERR_ELEMENT_MISSING
        );
    }
    // End getAttribute

    /**
     * Returns an attribute value.
     *
     * @param  DOMElement $element
     * @param  string     $name
     * @param  mixed      $defaultValue
     * @param bool        $isOptional
     * @throws Exception
     * @return string
     */
    protected function getAttribute(DOMElement $element, $name, $defaultValue = null, $isOptional = false)
    {
        if (!$element->hasAttribute($name)) {
            if ($defaultValue || $isOptional) {
                return $defaultValue;
            }

            throw new Exception(
                'Element `'.$element->nodeName.'\' has no attribute `'.$name.'\' on '.$element->getNodePath(),
                self::ERR_ATTRIBUTE_MISSING
            );
        }

        return $element->getAttribute($name);
    }
    // End validateDocument


    // private
    protected function createOrFindProjectKwkId(ElcaProjectVariant $variant, string $kwkName, $dataObject): int
    {
        $projectKwk = ElcaProjectKwk::findByProjectVariantId($variant->getId());

        if ($projectKwk->isInitialized()) {
            return $projectKwk->getId();
        }

        return ElcaProjectKwk::create($variant->getId(), $kwkName, $dataObject->heating,
            $dataObject->water)->getId();
    }

    /**
     * Validates the document
     *
     * @throws Exception
     * @return mixed -
     */
    private function validateDocument()
    {
        $xsdPath  = $this->getXsdPath($this->schemaVersion);
        $config      = Environment::getInstance()->getConfig();
        $docRootPath = $config->toDir('docRoot');

        libxml_use_internal_errors(true);
        if (!$this->document->schemaValidate($docRootPath . $xsdPath)) {
            throw new Exception(
                sprintf(
                    'Document fails to validate against `%s\': %s',
                    $xsdPath,
                    \libxml_get_last_error() instanceof \LibXMLError ? libxml_get_last_error()->message : ''
                ),
                self::ERR_INVALID_DOCUMENT
            );
        }
    }

    private function getXsdPath(string $schemaVersion): string
    {
        $xsdPath = sprintf(
            '%s/%s/%s',
            Exporter::XSD_DIR,
            $schemaVersion,
            Exporter::XSD_FILE_NAME
        );

        return $xsdPath;
    }

    private function findSchemaVersion(DOMElement $rootNode): string
    {
        if ($rootNode->hasAttribute('schemaVersion')) {
            return $rootNode->getAttribute('schemaVersion');
        }

        // use last legacy version
        return Exporter::SCHEMA_VERSION_LEGACY;
    }

    private function initDOMDocumentFromFile(File $file): void
    {
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->loadXML(implode('', $file->getAsArray()));

        $this->schemaVersion = $this->findSchemaVersion($this->findRootNode());
        $this->defaultPrefix = '';

        $this->xPath = new DOMXPath($this->document);

        if ($this->schemaVersion !== Exporter::SCHEMA_VERSION_LEGACY) {
            $prefix = 'e';
            $this->xPath->registerNamespace($prefix, Exporter::SCHEMA_NAMESPACE);
            $this->defaultPrefix = $prefix . ':';
        }
    }

    private function query(string $xPath, \DOMNode $context = null): DOMNodeList
    {
        $cleanedPath = strtr($xPath, ['{p:}' => $this->defaultPrefix ]);

        $nodeList = $this->xPath->query($cleanedPath, $context);

        if (false === $nodeList) {
            throw new \RuntimeException('XPath query `'. $cleanedPath .'\' is malformed');
        }

        return $nodeList;
    }

    private function findRootNode(): DOMElement
    {
        $rootNodes = $this->document->getElementsByTagName('elca');

        if (0 === $rootNodes->length) {
            throw new Exception('Invalid document', self::ERR_INVALID_DOCUMENT);
        }

        return $rootNodes->item(0);
    }
}
