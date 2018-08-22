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

namespace Soda4Lca\Model\Import;

use Beibob\Blibs\Log;
use DOMNode;
use DOMXPath;
use Elca\Db\ElcaProcessConversion;
use Elca\Model\Common\Unit;
use Elca\Model\ProcessConfig\Conversion\ConversionType;

/**
 * Parses xml data from soda4lca service
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class Soda4LcaParser
{
    /**
     * Classification name
     */
    const CLASSIFICATION_OEKOBAUDAT = 'OEKOBAU.DAT';

    /**
     * MatML Property names
     *
     * gross density = Rohdichte
     * grammage = Flächengewicht
     * bulk density = Schüttdichte
     * layer thickness = Schichtdicke
     * productiveness = Ergiebigkeit
     * linear density = Längengewicht
     */
    const MATML_PROP_NAME_GROSS_DENSITY = 'gross density';
    const MATML_PROP_NAME_GRAMMAGE = 'grammage';
    const MATML_PROP_NAME_BULK_DENSITY = 'bulk density';
    const MATML_PROP_NAME_LAYER_THICKNESS = 'layer thickness';
    const MATML_PROP_NAME_PRODUCTIVENESS = 'productiveness';
    const MATML_PROP_NAME_LINEAR_DENSITY = 'linear density';
    const MATML_PROP_NAME_CONVERSION_TO_MASS = 'conversion factor to 1 kg';

    /** deprecated */
    const MATML_PROP_NAME_AVG_MPUA = 'average mass per unit area';
    const MATML_PROP_NAME_RAW_DENSITY = 'raw density';


    /**
     * Instance
     */
    private static $Instance;

    /**
     * MatML Property names
     */
    private static $matMLPropertyNames = [
        self::MATML_PROP_NAME_AVG_MPUA    => ElcaProcessConversion::IDENT_AVG_MPUA,
        self:: MATML_PROP_NAME_GRAMMAGE   => ElcaProcessConversion::IDENT_AVG_MPUA,
        self::MATML_PROP_NAME_RAW_DENSITY      => ElcaProcessConversion::IDENT_GROSS_DENSITY,
        self:: MATML_PROP_NAME_GROSS_DENSITY   => ElcaProcessConversion::IDENT_GROSS_DENSITY,
        self:: MATML_PROP_NAME_BULK_DENSITY    => ElcaProcessConversion::IDENT_BULK_DENSITY,
        self:: MATML_PROP_NAME_LAYER_THICKNESS => ElcaProcessConversion::IDENT_LAYER_THICKNESS,
        self:: MATML_PROP_NAME_PRODUCTIVENESS  => ElcaProcessConversion::IDENT_PRODUCTIVENESS,
        self:: MATML_PROP_NAME_LINEAR_DENSITY  => ElcaProcessConversion::IDENT_LINEAR_DENSITY
    ];

    /**
     * Connector
     */
    private $Connector;

    /**
     * Log
     */
    private $Log;



    /**
     * Returns the singleton instance
     *
     * @return Soda4LcaParser
     */
    public static function getInstance()
    {
        if(!self::$Instance)
            self::$Instance = new Soda4LcaParser();

        return self::$Instance;
    }
    // End getInstance



    /**
     * DataStocks
     *
     * @return array of DataStockDOs
     */
    public function getDataStocks()
    {
        $results = [];

        $this->Log->debug('Fetching datastocks', __METHOD__);

        $XPath = $this->Connector->getDataStocks();
        $sourceUri = $this->Connector->getSourceUri();

        $XPath->registerNamespace('sapi', 'http://www.ilcd-network.org/ILCD/ServiceAPI');

        $DataStocks = $XPath->query('//sapi:dataStockList/sapi:dataStock');
        foreach($DataStocks as $DataStock)
        {
            $DO = new \stdClass;
            $DO->uuid = $XPath->query('./sapi:uuid/text()', $DataStock)->item(0)->textContent;
            $DO->sourceUri = $sourceUri .'/'. $DO->uuid;
            $DO->isDefault = $DataStock->hasAttribute('sapi:root') && $DataStock->getAttribute('sapi:root') == 'true';
            $DO->shortName = $XPath->query('./sapi:shortName/text()', $DataStock)->item(0)->textContent;

            $DO->name = $this->getTextContentByPreferredLang(['de', 'en'], $XPath, './sapi:name', $DataStock);
            $DO->description = $this->getTextContentByPreferredLang(['de', 'en'], $XPath, './sapi:description', $DataStock);

            $totalSize = 0;
            $this->getProcesses($DO->uuid, 0, 1, $totalSize);
            $DO->totalSize = $totalSize;

            $results[$DO->uuid] = $DO;
        }

        return $results;
    }
    // End getDataStocks



    /**
     * DataStock
     *
     * @param string $uuid
     * @return object
     */
    public function getDataStock($uuid = '')
    {
        $dataStocks = $this->getDataStocks();

        if(!isset($dataStocks[$uuid]))
            return null;

        return $dataStocks[$uuid];
    }
    // End getDataStock



    /**
     * Returns a list of processes for the specified dataStock UUID
     *
     * @param  string $dataStockUuid
     * @param  int    $startIndex
     * @param  int    $pageSize
     * @param  &int   $totalSize
     * @return array of stdClass objects
     */
    public function getProcesses($dataStockUuid = null, $startIndex = 0, $pageSize = 10, &$totalSize)
    {
        $results = [];
        $this->Log->debug('Fetching processes from '. $dataStockUuid. ' [startIndex='. $startIndex .'] [pageSize='. $pageSize .']', __METHOD__);

        $XPath = $this->Connector->getProcesses($dataStockUuid, $startIndex, $pageSize);
        $XPath->registerNamespace('sapi', 'http://www.ilcd-network.org/ILCD/ServiceAPI');
        $XPath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        $XPath->registerNamespace('p', 'http://www.ilcd-network.org/ILCD/ServiceAPI/Process');

        $totalSize = $XPath->query('//sapi:dataSetList')->item(0)->getAttribute('sapi:totalSize');

        $Processes = $XPath->query('//sapi:dataSetList/p:process');
        foreach($Processes as $Process)
        {
            $DO = $results[] = new \stdClass;
            $DO->uri = $Process->getAttribute('xlink:href');
            $DO->uuid = $XPath->query('./sapi:uuid/text()', $Process)->item(0)->textContent;
            $DO->name = $XPath->query('./sapi:name/text()', $Process)->item(0)->textContent;
            $DO->version = $XPath->query('./sapi:dataSetVersion/text()', $Process)->item(0)->textContent;

            $ClassificationNodes = $XPath->query('./sapi:classification[@name="'. \utf8_strtoupper(self::CLASSIFICATION_OEKOBAUDAT) .'" or @name="'. \utf8_strtolower(self::CLASSIFICATION_OEKOBAUDAT) .'"]/sapi:class[@level="1"]', $Process);
            if ($ClassificationNodes->length)
                $DO->classId = $ClassificationNodes->item(0)->getAttribute('classId');
            else {
                $this->Log->warning('Node `'. $DO->name .'\' defines no classification in dataSetList', __METHOD__);
            }
        }

        return $results;
    }
    // End getProcesses


    /**
     * Retrieves informations about a ProcessDataSet
     *
     * @param  string $uuid
     * @throws Soda4LcaException
     * @return \stdClass
     */
    public function getProcessDataSet($uuid)
    {
        $DO = new \stdClass();

        $this->Log->debug('Fetching process ['. $uuid. ']', __METHOD__);

        $XPath = $this->Connector->getProcess($uuid, Soda4LcaConnector::FORMAT_XML);
        $XPath->registerNamespace('p', 'http://lca.jrc.it/ILCD/Process');
        $XPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');
        $XPath->registerNamespace('epd', 'http://www.iai.kit.edu/EPD/2013');

        $DO->uuid = $XPath->query('//p:processDataSet/p:processInformation/p:dataSetInformation/common:UUID')->item(0)->textContent;
        $DO->nameOrig = $this->getTextContentByPreferredLang(['de', 'en'], $XPath, '//p:processDataSet/p:processInformation/p:dataSetInformation/p:name/p:baseName');

        $Descriptions = $XPath->query('//p:processDataSet/p:processInformation/p:dataSetInformation/common:generalComment');
        $DO->description = $Descriptions->length? (string)$Descriptions->item(0)->textContent : '';

        $CatNodes = $XPath->query('//p:processDataSet/p:processInformation/p:dataSetInformation/p:classificationInformation/common:classification[@name="'. \utf8_strtoupper(self::CLASSIFICATION_OEKOBAUDAT) .'" or @name="'. \utf8_strtolower(self::CLASSIFICATION_OEKOBAUDAT) .'"]/common:class[@level="1"]');

        if ($CatNodes->length) {
            $CatNode = $CatNodes->item(0);
            $DO->classId = $CatNode->getAttribute('classId');
            $DO->className = $CatNode->textContent;
        } else {
            throw new Soda4LcaException('No Ökobau.dat classification found for `'. $DO->nameOrig .'\' ['. $DO->uuid .']', Soda4LcaException::MISSING_PROCESS_CATEGORY);
        }

        $DO->version = $XPath->query('//p:processDataSet/p:administrativeInformation/p:publicationAndOwnership/common:dataSetVersion')->item(0)->textContent;
        $dateOfLastRevision = $XPath->query('//p:processDataSet/p:administrativeInformation/p:publicationAndOwnership/common:dateOfLastRevision');

        if ($dateOfLastRevision->length) {
            $DO->dateOfLastRevision = $dateOfLastRevision->item(0)->textContent;
        } else {
            $DO->dateOfLastRevision = '';
        }

        $EpdSubTypes = $XPath->query('//p:processDataSet/p:modellingAndValidation/p:LCIMethodAndAllocation/common:other/epd:subType');
        if($EpdSubTypes->length)
            $DO->epdSubType = $EpdSubTypes->item(0)->textContent;

        $locationOfOperationSupplyOrProductionNodes = $XPath->query('//p:processDataSet/p:processInformation/p:geography/p:locationOfOperationSupplyOrProduction');
        if($locationOfOperationSupplyOrProductionNodes->length) {
            $locationOfOperationSupplyOrProductionNode = $locationOfOperationSupplyOrProductionNodes->item(0);
            if ($locationOfOperationSupplyOrProductionNode->hasAttribute('location')) {
                $DO->geographicalRepresentativeness = $locationOfOperationSupplyOrProductionNode->getAttribute('location');
            }
        }

        /**
         * Flow
         */
        $internalDataSetId = null;
        $RefToRefFlow = $XPath->query('//p:processDataSet/p:processInformation/p:quantitativeReference/p:referenceToReferenceFlow');
        if($RefToRefFlow->length)
        {
            /**
             * Ref unit and value
             */
            $internalDataSetId = $RefToRefFlow->item(0)->textContent;
            $flowUuid = $XPath->query('//p:processDataSet/p:exchanges/p:exchange[@dataSetInternalID="'. $internalDataSetId .'"]/p:referenceToFlowDataSet')->item(0)->getAttribute('refObjectId');
            $DO->refValue = $XPath->query('//p:processDataSet/p:exchanges/p:exchange[@dataSetInternalID="'. $internalDataSetId .'"]/p:meanAmount')->item(0)->textContent;
            $DO->refUnit = $this->getUnit($flowUuid);

            /**
             * Flow descendants
             */
            $DO->flowDescendants = $this->getFlowDescendants($flowUuid);

            /**
             * Material Properties
             */
            $DO->MatProperties = $this->getMatProperties($flowUuid, $DO->refUnit);
        }

        /**
         * Scenarios
         */
        $DO->scenarios = [];

        $Scenarios = $XPath->query('//p:processDataSet/p:processInformation/p:dataSetInformation/common:other/epd:scenarios/epd:scenario');
        if($Scenarios->length)
        {
            foreach($Scenarios as $Scenario)
            {
                $ident = $Scenario->getAttribute('epd:name');

                $ScenarioDO = $DO->scenarios[$ident] = new \stdClass();
                $ScenarioDO->ident = $ident;
                $ScenarioDO->group = $Scenario->hasAttribute('epd:group')? $Scenario->getAttribute('epd:group') : null;
                $ScenarioDO->description = $this->getTextContentByPreferredLang(['de', 'en'], $XPath, './epd:description', $Scenario);
                $ScenarioDO->isDefault = $Scenario->getAttribute('epd:default') == 'true';
            }
            $this->Log->debug('Scenarios found:  ['. join('], [', array_keys($DO->scenarios)) .']', __METHOD__);
        }

        /**
         * indicators
         */
        $DO->epdModules = [];

        $Exchanges = $XPath->query('//p:processDataSet/p:exchanges/p:exchange');
        foreach($Exchanges as $Exchange)
        {
            if($internalDataSetId && $Exchange->getAttribute('dataSetInternalID') == $internalDataSetId)
                continue;

            if(!$indicatorUuid = $XPath->query('./p:referenceToFlowDataSet', $Exchange)->item(0)->getAttribute('refObjectId'))
                continue;

            $Amounts = $XPath->query('./common:other/epd:amount', $Exchange);
            foreach($Amounts as $Amount)
            {
                $epdModule = $Amount->getAttribute('epd:module');
                $scenarioIdent = $Amount->getAttribute('epd:scenario')? $Amount->getAttribute('epd:scenario') : null;

                $DO->epdModules[$epdModule][$scenarioIdent][$indicatorUuid] = is_numeric($Amount->textContent)? $Amount->textContent : null;
            }
        }

        $LCIAResults = $XPath->query('//p:processDataSet/p:LCIAResults/p:LCIAResult');
        foreach($LCIAResults as $LCIAResult)
        {
            if(!$indicatorUuid = $XPath->query('./p:referenceToLCIAMethodDataSet', $LCIAResult)->item(0)->getAttribute('refObjectId'))
                continue;

            $Amounts = $XPath->query('./common:other/epd:amount', $LCIAResult);
            foreach($Amounts as $Amount)
            {
                $epdModule = $Amount->getAttribute('epd:module');
                $scenarioIdent = $Amount->getAttribute('epd:scenario')? $Amount->getAttribute('epd:scenario') : null;
                $DO->epdModules[$epdModule][$scenarioIdent][$indicatorUuid] = is_numeric($Amount->textContent)? $Amount->textContent : null;
            }
        }

        return $DO;
    }
    // End getProcessDataSet


    /**
     * Resolves the descendants of the flowDataSet
     *
     * @param  string $flowDataSetUuid
     * @return array
     */
    private function getFlowDescendants($flowDataSetUuid)
    {
        $descendants = [];

        /**
         * Get the flow descendants
         */
        $XPath = $this->Connector->getFlowDescendants($flowDataSetUuid);
        $XPath->registerNamespace('f', 'http://lca.jrc.it/ILCD/Flow');
        $XPath->registerNamespace('sapi', 'http://www.ilcd-network.org/ILCD/ServiceAPI');

        $Descendants = $XPath->query('//sapi:dataSetList/f:flow/sapi:uuid');
        if($Descendants->length)
        {
            $this->Log->debug('Fetching descendants for flowDataSet ['. $flowDataSetUuid. ']', __METHOD__);

            foreach($Descendants as $Descendant)
            {
                if(!$descUuid = $Descendant->textContent)
                    continue;

                $descendants[] = $DO = new \stdClass();

                $DescXPath = $this->Connector->getFlow($descUuid);
                $DescXPath->registerNamespace('f', 'http://lca.jrc.it/ILCD/Flow');
                $DescXPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');

                $DO->uuid = $descUuid;
                $DO->name = $this->getTextContentByPreferredLang(['de', 'en'], $DescXPath, '//f:flowDataSet/f:flowInformation/f:dataSetInformation/f:name/f:baseName');

                $internalFlowDataSetId = $DescXPath->query('//f:flowDataSet/f:flowInformation/f:quantitativeReference/f:referenceToReferenceFlowProperty')->item(0)->textContent;
                $DO->refValue = $DescXPath->query('//f:flowDataSet/f:flowProperties/f:flowProperty[@dataSetInternalID="'. $internalFlowDataSetId .'"]/f:meanValue')->item(0)->textContent;
                $DO->refUnit = $this->getUnit($descUuid);

                $VendorSpecificProducts = $DescXPath->query('//f:flowDataSet/f:modellingAndValidation/f:LCIMethod/common:other/epd:vendorSpecificProduct');
                $DO->isVendorSpecific = $VendorSpecificProducts->length && $VendorSpecificProducts->item(0)->textContent == 'true';

                $this->Log->debug('Found '. ($DO->isVendorSpecific? 'vendor specific' : '') .' descendant for flowDataSet ['. $flowDataSetUuid. ']: '. $DO->name.' ['. $DO->uuid.']', __METHOD__);
            }
        }

        return $descendants;
    }
    // End getFlowDescendants



    /**
     * Resolves the material properties of the flowDataSet
     *
     * @param  string $flowDataSetUuid
     * @return object
     */
    private function getMatProperties($flowDataSetUuid, $refUnit = null)
    {
        $matProperties = new \stdClass();
        $matProperties->conversions = [];

        /**
         * Get flow
         */
        $XPath = $this->Connector->getFlow($flowDataSetUuid);
        $XPath->registerNamespace('f', 'http://lca.jrc.it/ILCD/Flow');
        $XPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');
        $XPath->registerNamespace('matml', 'http://www.matml.org/');

        $PropertyDetails = $XPath->query('//f:flowDataSet/f:flowInformation/f:dataSetInformation/common:other/matml:MatML_Doc/matml:Metadata/matml:PropertyDetails');
        foreach($PropertyDetails as $PropertyDetail)
        {
            $name = \utf8_strtolower(\trim($XPath->query('./matml:Name', $PropertyDetail)->item(0)->textContent));
            $propertyId = $PropertyDetail->getAttribute('id');

            $convDO = null;

            switch($name)
            {
                case self::MATML_PROP_NAME_AVG_MPUA:
                case self::MATML_PROP_NAME_GRAMMAGE:
                case self::MATML_PROP_NAME_PRODUCTIVENESS:
                    $convDO = $matProperties->conversions[$name] = new \stdClass();
                    $convDO->inUnit = 'm2';
                    $convDO->outUnit = 'kg';
                    $convDO->factor = $XPath->query('../../matml:Material/matml:BulkDetails/matml:PropertyData[@property="'.$propertyId.'"]/matml:Data', $PropertyDetail)->item(0)->textContent;
                    $convDO->ident = self::$matMLPropertyNames[$name];
                    break;

                case self::MATML_PROP_NAME_RAW_DENSITY:
                case self::MATML_PROP_NAME_GROSS_DENSITY:
                    $convDO = $matProperties->conversions[$name] = new \stdClass();
                    $convDO->inUnit = 'm3';
                    $convDO->outUnit = 'kg';
                    $convDO->factor = $XPath->query('../../matml:Material/matml:BulkDetails/matml:PropertyData[@property="'.$propertyId.'"]/matml:Data', $PropertyDetail)->item(0)->textContent;
                    $convDO->ident = self::$matMLPropertyNames[$name];

                    $matProperties->density = $convDO->factor;
                    break;

                case self::MATML_PROP_NAME_LINEAR_DENSITY:
                    $convDO = $matProperties->conversions[$name] = new \stdClass();
                    $convDO->inUnit = 'm';
                    $convDO->outUnit = 'kg';
                    $convDO->factor = $XPath->query('../../matml:Material/matml:BulkDetails/matml:PropertyData[@property="'.$propertyId.'"]/matml:Data', $PropertyDetail)->item(0)->textContent;
                    $convDO->ident = self::$matMLPropertyNames[$name];
                    break;

                case self::MATML_PROP_NAME_CONVERSION_TO_MASS:
                    if (!$refUnit) {
                        $this->Log->warning('Can not apply material property `'. $name .'\': missing the reference quantity.', __METHOD__);
                        break;
                    }

                    if ($refUnit === Unit::KILOGRAMM) {
                        $this->Log->debug('Will not apply material property `'. $name .'\': kg to kg conversion.', __METHOD__);
                        break;
                    }

                    $conversionType = ConversionType::guessForUnits(new Unit($refUnit), Unit::kg());
                    $factor = $XPath->query('../../matml:Material/matml:BulkDetails/matml:PropertyData[@property="'.$propertyId.'"]/matml:Data', $PropertyDetail)->item(0)->textContent;

                    if (!\is_numeric($factor) || empty($factor) || $factor === 0) {
                        $this->Log->debug('Can not apply material property `'. $name .'\': factor `'. $factor .'\' is invalid.', __METHOD__);
                        break;
                    }

                    $convDO          = $matProperties->conversions[$name] = new \stdClass();
                    $convDO->inUnit  = $refUnit;
                    $convDO->outUnit = 'kg';
                    $convDO->factor  = 1 / $factor;
                    $convDO->ident   = $conversionType->isKnown() ? $conversionType->value() : ConversionType::CONVERSION_TO_MASS;
                    break;

                    /**
                     * Log unknown properties
                     */
                default:
                    $this->Log->warning('Don\'t know nothing about material property `'. $name.'\'', __METHOD__);
                    break;
            }

            if ($convDO) {
                $this->Log->debug(
                    sprintf(
                        'Found material property %s [in=%s,out=%s,f=%f]',
                        $convDO->ident ? $name .' ('. $convDO->ident .')' : $name,
                        $convDO->inUnit,
                        $convDO->outUnit,
                        $convDO->factor
                        )
                    ,
                    __METHOD__
                );
            }
        }

        return $matProperties;
    }
    // End getMatProperties



    /**
     * Resolves the unit of a flow dataSets uuid
     *
     * @param  string $flowDataSetUuid
     * @return string
     */
    private function getUnit($flowDataSetUuid)
    {
        $this->Log->debug('Fetching unit for flowDataSet ['. $flowDataSetUuid. ']', __METHOD__);

        /**
         * Get flow
         */
        $FlowXPath = $this->Connector->getFlow($flowDataSetUuid);
        $FlowXPath->registerNamespace('f', 'http://lca.jrc.it/ILCD/Flow');
        $FlowXPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');

        $refToRefFlowProperty = $FlowXPath->query('//f:flowDataSet/f:flowInformation/f:quantitativeReference/f:referenceToReferenceFlowProperty')->item(0);
        $internalFlowDataSetId = null !== $refToRefFlowProperty ? $refToRefFlowProperty->textContent : null;

        $refUnitFlowPropertyUuids = $FlowXPath->query('//f:flowDataSet/f:flowProperties/f:flowProperty[@dataSetInternalID="'. $internalFlowDataSetId .'"]/f:referenceToFlowPropertyDataSet');

        if ($refUnitFlowPropertyUuids->length) {
            $refUnitFlowPropertyUuid = $refUnitFlowPropertyUuids->item(0)->getAttribute('refObjectId');
        } else {
            throw new Soda4LcaException('No refUnit flow property found for flow data set `'. $flowDataSetUuid .'\'', Soda4LcaException::MISSING_REF_UNIT);
        }

        /**
         * Get FlowProperty
         */
        $FlowPropXPath = $this->Connector->getFlowProperty($refUnitFlowPropertyUuid);
        $FlowPropXPath->registerNamespace('fp', 'http://lca.jrc.it/ILCD/FlowProperty');
        $FlowPropXPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');
        $refUnitUnitGroupUuid = $FlowPropXPath->query('//fp:flowPropertyDataSet/fp:flowPropertiesInformation/fp:quantitativeReference/fp:referenceToReferenceUnitGroup')->item(0)->getAttribute('refObjectId');

        /**
         * Get UnitGroup
         */
        $UnitGroupXPath = $this->Connector->getUnitGroup($refUnitUnitGroupUuid);
        $UnitGroupXPath->registerNamespace('ug', 'http://lca.jrc.it/ILCD/UnitGroup');
        $UnitGroupXPath->registerNamespace('common', 'http://lca.jrc.it/ILCD/Common');
        $internalUnitGroupDataSetId = $UnitGroupXPath->query('//ug:unitGroupDataSet/ug:unitGroupInformation/ug:quantitativeReference/ug:referenceToReferenceUnit')->item(0)->textContent;

        /**
         * Finaly find unit
         */
        $unit = $UnitGroupXPath->query('//ug:unitGroupDataSet/ug:units/ug:unit[@dataSetInternalID="'. $internalUnitGroupDataSetId .'"]/ug:name')->item(0)->textContent;

        /**
         * Normalize unit names
         */
        return UnitNameMapper::unitByName($unit)->value();
    }
    // End getUnit



    /**
     * Returns the text contents preferred for the given languageIdent
     *
     * @param  array
     * @param  DOMXPath $XPath
     * @param  string $query
     * @param  DOMNode $ContextNode
     * @return array
     */
    private function getTextContentByPreferredLang(array $preferredLangIdents, DOMXPath $XPath, $query, $ContextNode = null)
    {
        $contents = [];
        $Nodes = $XPath->query($query, $ContextNode);
        foreach($Nodes as $Node)
        {
            $lang = null;
            if($Node->hasAttribute('xml:lang'))
                $lang = \trim($Node->getAttribute('xml:lang'));

            if(!is_null($lang))
                $contents[$lang] = \trim($Node->textContent);
            else
                $contents[] = \trim($Node->textContent);
        }

        foreach($preferredLangIdents as $lang)
            if(isset($contents[$lang]))
                return $contents[$lang];

        return array_shift($contents);
    }
    // End getTextContentByPreferredLang



    /**
     * Constructor
     *
     * @return Soda4LcaParser -
     */
    private function __construct()
    {
        $this->Connector = Soda4LcaConnector::getInstance();
        $this->Log = Log::getInstance();
    }
    // End __construct


}
// End Soda4LcaParser