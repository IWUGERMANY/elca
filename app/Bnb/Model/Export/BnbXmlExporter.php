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
namespace Bnb\Model\Export;

use Beibob\Blibs\Environment;
use Bnb\Db\BnbExportSet;
use Bnb\Db\BnbWater;
use Bnb\Model\Processing\BnbProcessor;
use DateTime;
use DOMDocument;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Export\Xml\Exporter;
use Lcc\LccModule;
use Lcc\Db\LccProjectTotalSet;
use Lcc\Db\LccProjectVersion;
use Lcc\Service\BenchmarkService;

class BnbXmlExporter
{
    /**
     * XSD file name
     */
    const XSD_FILE_NAME = 'elca_ebnb_export.xsd';

    /**
     * @var ElcaProjectVariant $ProjectVariant
     */
    protected $ProjectVariant;


    /**
     * Constructor
     *
     * @param ElcaProjectVariant $ProjectVariant
     */
    public function __construct(ElcaProjectVariant $ProjectVariant)
    {
        $this->ProjectVariant = $ProjectVariant;

        $this->Doc = new DOMDocument('1.0', 'UTF-8');
        $this->Doc->validateOnParse  = false;
        $this->Doc->resolveExternals = false;
        $this->Doc->formatOutput = true;
    }
    // End __construct


    /**
     * @return string
     */
    public function getXml()
    {
        $RootNode = $this->createRootNode();
        $this->appendProjectInformation($RootNode);
        $this->appendAdministrativeInformation($RootNode);
        $this->appendOutputs($RootNode);

        return $this->Doc->saveXML();
    }
    // End getXml

    /**
     * Returns the root element
     *
     * @param  -
     * @return DOMElement
     */
    protected function createRootNode()
    {
        $Root = $this->Doc->createElementNS('', 'projectDataSet');
        $this->Doc->appendChild($Root);
        $Root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $Root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'noNamespaceSchemaLocation', '/'. Exporter::XSD_DIR .'/1.2/'. BnbXmlExporter::XSD_FILE_NAME);

        return $Root;
    }
    // End createRootNode


    /**
     * @param DOMNode $Container
     */
    protected function appendProjectInformation(DOMNode $Container)
    {
        $Project = $this->ProjectVariant->getProject();
        $ProjectInfoNode = $Container->appendChild($this->get('projectInformation'));

        // common
        $CommonNode = $ProjectInfoNode->appendChild($this->get('common'));

        $this->appendObjectProperties($CommonNode,
                                      $Project,
                                      ['name', 'projectNr', 'description', 'lifeTime', 'editor']);

        $ConstrClass = $Project->getConstrClass();
        $CommonNode->appendChild($this->get('constrClass', ['refNum' => $ConstrClass->getRefNum()], $ConstrClass->getName()));
        $CommonNode->appendChild($this->get('projectType', null, Elca::$constrMeasures[$Project->getConstrMeasure()]));
        $CommonNode->appendChild($this->get('bnbNr', null, ElcaProjectAttribute::findValue($Project->getId(), ElcaProjectAttribute::IDENT_BNB_NR)));
        $CommonNode->appendChild($this->get('eGisNr', null, ElcaProjectAttribute::findValue($Project->getId(), ElcaProjectAttribute::IDENT_EGIS_NR)));

        // projectVariant
        $ProjectInfoNode->appendChild($this->get('projectVariant',
                                           ['phaseIdent' => $this->ProjectVariant->getPhase()->getIdent(),
                                                 'refId' => $this->ProjectVariant->getId()],
                                           $this->ProjectVariant->getName()
                                ));

        // benchmark system
        $BenchmarkSystemNode = $ProjectInfoNode->appendChild($this->get('benchmarkSystem'));
        $BenchmarkSystemNode->appendChild($this->get('name', null, $Project->getBenchmarkVersion()->getBenchmarkSystem()->getName()));
        $BenchmarkSystemNode->appendChild($this->get('version', null, $Project->getBenchmarkVersion()->getName()));

        // process database
        $ProcessDb = $Project->getProcessDb();
        $ProcessDatabaseNode = $ProjectInfoNode->appendChild($this->get('processDatabase',
                                                                  ['sourceUri' => $ProcessDb->getSourceUri()]
                                                       ));
        $ProcessDatabaseNode->appendChild($this->get('name', null, $ProcessDb->getName()));
        $ProcessDatabaseNode->appendChild($this->get('uuid', null, $ProcessDb->getUuid()));

        // location
        $LocationNode = $ProjectInfoNode->appendChild($this->get('location'));
        $this->appendObjectProperties($LocationNode, $this->ProjectVariant->getProjectLocation(),
                                      ['street', 'postcode', 'city', 'country']
        );

        // construction
        $ConstructionNode = $ProjectInfoNode->appendChild($this->get('construction'));
        $this->appendObjectProperties($ConstructionNode, $this->ProjectVariant->getProjectConstruction(),
                                      ['grossFloorSpace', 'netFloorSpace', 'floorSpace', 'propertySize']
        );
        $ProjectEnEv = $this->ProjectVariant->getProjectEnEv();
        $ConstructionNode->appendChild($this->get('netFloorSpaceEnEv', ['version' => $ProjectEnEv->getVersion()], $ProjectEnEv->getNgf()));
    }
    // End addProjectInformation


    /**
     * @param DOMNode $Container
     */
    protected function appendAdministrativeInformation(DOMNode $Container)
    {
        $AdminInfoNode = $Container->appendChild($this->get('administrativeInformation'));
        $AdminInfoNode->appendChild($this->get('timestamp', null, date(DateTime::W3C), true));
        $AdminInfoNode->appendChild($this->get('system',
                                               ['version' => Elca::VERSION,
                                                     'host'    => (Environment::sslActive()? 'https://' : 'http://') . Environment::getServerHostName()
                                               ],
                                               Elca::NAME, true
                                    ));

    }
    // End appendAdministrativeInformation


    /**
     * @param DOMNode $Container
     */
    protected function appendOutputs(DOMNode $Container)
    {
        $OutputsNode = $Container->appendChild($this->get('outputs'));

        $m2a = max(1, $this->ProjectVariant->getProject()->getLifeTime() * $this->ProjectVariant->getProjectConstruction()->getNetFloorSpace());

        $this->appendLcaSummary($OutputsNode, $m2a);
        $this->appendLcaDin276($OutputsNode, $m2a);
        $this->appendLcc($OutputsNode);
        $this->appendFreshWater($OutputsNode);
    }
    // End appendOutputs


    /**
     * Summary LCA
     *
     * @param DOMNode $Container
     * @param float   $m2a
     */
    protected function appendLcaSummary(DOMNode $Container, $m2a)
    {
        $LcaNode = $Container->appendChild($this->get('lca', ['type' => 'summary']));
        $ResultSetNode = $LcaNode->appendChild($this->get('resultSet', ['ident' => 'summary']));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Gesamtbilanz')));

        $TotalEffects = BnbExportSet::findTotalEffects($this->ProjectVariant->getId());

        foreach ($TotalEffects as $IndicatorDO) {

            $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => $IndicatorDO->ident]));
            $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
            $ResultInfoNode->appendChild($this->get('name', null, t($IndicatorDO->name)));
            $ResultInfoNode->appendChild($this->get('unit', null, $IndicatorDO->unit));

            $value = $IndicatorDO->value / $m2a;
            $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $value));

            /**
             * append subSet
             */
            $TotalLcEffects = BnbExportSet::findEffectsPerLifeCycleByItemId(
                                          $IndicatorDO->item_id,
                                          $IndicatorDO->indicator_id
            );

            $SubSetNode = $ResultNode->appendChild($this->get('subSet'));
            foreach ($TotalLcEffects as $LcDO) {
                $value = $LcDO->value / $m2a;
                $SubSetNode->appendChild($this->get('amount', ['module' => $LcDO->life_cycle_ident], $value));
            }
        }
    }
    // End appendLcaSummary


    /**
     * DIN276 LCA
     *
     * @param DOMNode $Container
     * @param float   $m2a
     */
    protected function appendLcaDin276(DOMNode $Container, $m2a)
    {
        $LcaNode = $Container->appendChild($this->get('lca', ['type' => 'DIN_276']));

        $Effects = BnbExportSet::findTotalEffectsPerElementType($this->ProjectVariant->getId());

        $data = [];
        foreach ($Effects as $DO) {
            $data[$DO->din_code.'|'.$DO->element_type_name][] = $DO;
        }

        foreach ($data as $dinCodeName => $effects) {

            list($dinCode, $elementTypeName) = explode('|', $dinCodeName);

            $ResultSetNode = $LcaNode->appendChild($this->get('resultSet', ['ident' => $dinCode]));
            $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
            $ResultSetInfoNode->appendChild($this->get('name', null, $elementTypeName));

            foreach ($effects as $IndicatorDO) {

                $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => $IndicatorDO->ident]));
                $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
                $ResultInfoNode->appendChild($this->get('name', null, t($IndicatorDO->name)));
                $ResultInfoNode->appendChild($this->get('unit', null, $IndicatorDO->unit));

                $value = $IndicatorDO->value / $m2a;
                $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $value));

                /**
                 * append subSet
                 */
                $TotalLcEffects = BnbExportSet::findEffectsPerLifeCycleByItemId(
                                              $IndicatorDO->item_id,
                                              $IndicatorDO->indicator_id
                );

                $SubSetNode = $ResultNode->appendChild($this->get('subSet'));
                foreach ($TotalLcEffects as $LcDO) {
                    $value = $LcDO->value / $m2a;
                    $SubSetNode->appendChild($this->get('amount', ['module' => $LcDO->life_cycle_ident], $value));
                }
            }
        }
    }
    // End appendLcaDin276


    /**
     * LCC
     *
     * @param DOMNode $Container
     */
    protected function appendLcc(DOMNode $Container)
    {
        $LccNode = $Container->appendChild($this->get('lcc'));

        // skip if no lcc data exists
        if(!LccProjectTotalSet::dbCount(['project_variant_id' => $this->ProjectVariant->getId()]))
            return;

        $LccProjectVersion = LccProjectVersion::findByPK($this->ProjectVariant->getId(), LccModule::CALC_METHOD_GENERAL);

        $LccInfoNode = $LccNode->appendChild($this->get('lccInformation'));
        $LccInfoNode->appendChild($this->get('name', null, LccModule::MODULE_NAME));
        $LccVersionNode = $LccInfoNode->appendChild($this->get('version'));
        $LccVersionNode->appendChild($this->get('name', null, $LccProjectVersion->getVersion()->getName()));
        $LccInfoNode->appendChild($this->get('category', ['ident' => t('Sonderbedingungen')], $LccProjectVersion->getCategory()));

        $ResultSetNode = $LccNode->appendChild($this->get('resultSet', ['ident' => 'Aufteilung LCC Kosten']));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Aufteilung LCC Kosten')));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Barwert Gesamt')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Barwert Gesamt')));
        $ResultInfoNode->appendChild($this->get('unit', null, '€'));

        $benchmarkService = Environment::getInstance()->getContainer()->get(BenchmarkService::class);
        $summary = $benchmarkService->summary($LccProjectVersion);

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['total'][0]->costs));

        $SubSetNode = $ResultNode->appendChild($this->get('subSet'));
        foreach (['costs', 'irregular', 'service', 'regular'] as $module) {
            foreach ($summary[$module] as $DO) {
                $SubSetNode->appendChild($this->get('amount', ['module' => $DO->name], $DO->costs));
            }
        }

        foreach ($summary['rating'] as $DO) {

            $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => $DO->name]));
            $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
            $ResultInfoNode->appendChild($this->get('name', null, $DO->name));
            $ResultInfoNode->appendChild($this->get('unit', null, $DO->unit? '€' : ''));
            $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $DO->costs));
        }
    }
    // End appendLcc


    /**
     * Fresh water
     *
     * @param DOMNode $Container
     */
    protected function appendFreshWater(DOMNode $Container)
    {
        $WaterNode = $Container->appendChild($this->get('freshWater'));

        $BnbWater = BnbWater::findByProjectId($this->ProjectVariant->getProjectId());

        // skip if this project has no bnb water data
        if(!$BnbWater->isInitialized())
            return;

        // compute BNB Benchmark according to 1.2.3
        $bnbProcessor = new BnbProcessor();
        $summary = $bnbProcessor->computeWaterBenchmark($BnbWater, $this->ProjectVariant->getProjectConstruction()->getNetFloorSpace());

        $WaterInfoNode = $WaterNode->appendChild($this->get('freshWaterInformation'));
        $WaterInfoNode->appendChild($this->get('name', null, t('Trinkwasser 1.2.3')));

        // frischwasser bedarf
        $ResultSetNode = $WaterNode->appendChild($this->get('resultSet', ['ident' => t('Frischwasserbedarf pro Jahr')]));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Frischwasserbedarf pro Jahr')));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Gesamtfrischwasserbedarf')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Gesamtfrischwasserbedarf')));
        $ResultInfoNode->appendChild($this->get('unit', null, 'm3'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['gesamtfrischwasserbedarf']));

        $SubSetNode = $ResultNode->appendChild($this->get('subSet'));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Frischwasserbedarf pro Mitarbeiter')], $summary['wasserbedarfProJahr']));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Frischwasserbedarf Fussbodenreinigung')], $summary['wasserbedarfBodenreinigung']));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Menge des genutzten Niederschlagswassers')], $BnbWater->getNiederschlagGenutzt() * -1));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Menge des genutzten Brauchwassers')], $BnbWater->getBrauchwasser() * -1));

        // abwasseraufkommen
        $ResultSetNode = $WaterNode->appendChild($this->get('resultSet', ['ident' => t('Abwasseraufkommen pro Jahr')]));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Abwasseraufkommen pro Jahr')));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Gesamtabwasseraufkommen')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Gesamtabwasseraufkommen')));
        $ResultInfoNode->appendChild($this->get('unit', null, 'm3'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['gesamtfrischwasserbedarf']));

        $SubSetNode = $ResultNode->appendChild($this->get('subSet'));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Abwasseraufkommen pro Mitarbeiter')], $summary['wasserbedarfProJahr']));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Abwasseraufkommen Fussbodenreinigung')], $summary['gesamtfrischwasserbedarf']));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Anfallendes Niederschlagswassers')], $summary['niederschlagDaecher']));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Menge des auf dem Grundstück versickerten Regenwassers')], $BnbWater->getNiederschlagVersickert() * -1));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Menge des genutzten Brauchwassers')], $BnbWater->getBrauchwasser() * -1));
        $SubSetNode->appendChild($this->get('amount', ['module' => t('Menge des auf dem Grundstück gereinigten Brauchwassers')], $BnbWater->getBrauchwasserGereinigt() * -1));

        // Wassergebrauchskennwert
        $ResultSetNode = $WaterNode->appendChild($this->get('resultSet', ['ident' => t('Wassergebrauchskennwert')]));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Wassergebrauchskennwert')));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Wassergebrauchskennwert')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Wassergebrauchskennwert')));
        $ResultInfoNode->appendChild($this->get('unit', null, 'm3'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['wassergebrauchskennwert']));

        // grenzwerte
        $ResultSetNode = $WaterNode->appendChild($this->get('resultSet', ['ident' => t('Grenzwerte')]));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, t('Grenzwerte')));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Grenzwert Gesamt')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Grenzwert Gesamt')));
        $ResultInfoNode->appendChild($this->get('unit', null, 'm3'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['grenzwertGesamt']));

        $SubSetNode = $ResultNode->appendChild($this->get('subSet'));

        foreach ([t('Wasserbedarf pro Mitarbeiter')                       => 'grenzwertWasserProMitarbeiter',
                       t('Abwasseraufkommen pro Mitarbeiter')                  => 'grenzwertWasserProMitarbeiter',
                       t('Wasserbedarf Fussbodenreinigung')                    => 'grenzwertWasserFussboden',
                       t('Abwasseraufkommen Fussbodenreinigung')               => 'grenzwertWasserFussboden',
                       t('Abwasseraufkommen anfallendes Niederschlagswassers') =>'grenzwertNiederschlag'] as $caption => $module) {
            $SubSetNode->appendChild($this->get('amount', ['module' => $caption], $summary[$module]));
        }

        // ergebnis
        $ResultSetNode = $WaterNode->appendChild($this->get('resultSet', ['ident' => t('Ergebnis')]));
        $ResultSetInfoNode = $ResultSetNode->appendChild($this->get('resultSetInformation'));
        $ResultSetInfoNode->appendChild($this->get('name', null, 'Ergebnis'));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Verhältnis Wassergebrauchskennwert / Grenzwert')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Verhältnis Wassergebrauchskennwert / Grenzwert')));
        $ResultInfoNode->appendChild($this->get('unit'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['verhaeltnis']));

        $ResultNode = $ResultSetNode->appendChild($this->get('result', ['ident' => t('Punkte Kriterium 1.2.3')]));
        $ResultInfoNode = $ResultNode->appendChild($this->get('resultInformation'));
        $ResultInfoNode->appendChild($this->get('name', null, t('Punkte Kriterium 1.2.3')));
        $ResultInfoNode->appendChild($this->get('unit'));

        $ResultNode->appendChild($this->get('amount', ['module' => 'total'], $summary['punkte']));
    }
    // End appendFreshWater



    /**
     * Appends the given properties as simple elements
     *
     * @param  DOMElement      $Container
     * @param  object $DbObject $DbObject
     * @param  array           $properties
     * @param bool             $omitIfEmpty
     * @return DOMElement
     */
    protected function appendObjectProperties(DOMElement $Container, $DbObject, array $properties, $omitIfEmpty = false)
    {
        foreach($properties as $property)
        {
            $value = $DbObject->$property;
            if(!$value && $omitIfEmpty)
                continue;

            $Container->appendChild($this->get($property, null, $value));
        }

        return $Container;
    }
    // End appendObjectProperties


    /**
     * Creates an element
     *
     * @param  string $name
     * @param  array  $attributes
     * @param null    $content
     * @param bool    $omitCDATA
     * @return DOMElement
     */
    protected function get($name, array $attributes = null, $content = null, $omitCDATA = false)
    {
        $Element = $this->Doc->createElement($name);

        if(!is_null($attributes))
            $this->addAttributes($Element, $attributes);

        if(!is_null($content))
        {
            if($omitCDATA || is_numeric($content))
                $Element->appendChild($this->Doc->createTextNode($content));
            else
                $Element->appendChild($this->Doc->createCDATASection($content));
        }

        return $Element;
    }
    // End get


    /**
     * Adds attributes to a DOMElement
     *
     * @param  DOMElement $Element
     * @param  array $attributes
     * @return DOMElement
     */
    protected function addAttributes(DOMElement $Element, array $attributes)
    {
        foreach($attributes as $attr => $value)
            $Element->setAttribute((string)$attr, (string)$value);
    }
    // End addAttributes
}
// End BnbXmlExporter