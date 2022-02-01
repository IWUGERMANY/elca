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

use Beibob\Blibs\CsvExporter;
use Bnb\Db\BnbExportSet;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;

class BnbCsvExporter
{
    /**
     * @var ElcaProjectVariant $ProjectVariant
     */
    protected $ProjectVariant;

    /**
     * @translate array Bnb\Model\Export\BnbCsvExporter::$columns
     */
    public static $columns = ['export_type'        => 'Typ',
                               'composite_name'     => 'Name Bauteil',
                               'composite_din_code' => 'Kostengruppe Bauteil',
                               'name'               => 'Name Bauteilkomponente',
                               'din_code'           => 'Kostengruppe Bauteilkomponente',
                               'layer_position'     => 'Schichtposition',
                               'process_config'     => 'Baustoff',
                               'layer_area_ratio'   => 'Gefach Anteil [%]',
                               'element_quantity'   => 'Verbaute Menge',
                               'element_ref_unit'   => 'Verbaute Menge Einheit',
                               'layer_size'         => 'Schichtdicke [m]',
                               'layer_volume'       => 'Volumen [m3]',
                               'density'            => 'Rohdichte [kg/m3]]',
                               'component_quantity' => 'Bezugsmenge ÖkoBau.dat',
                               'component_ref_unit' => 'Einheit Bezugsmenge',
                               'mass'               => 'Masse [kg]',
                               'num_replacements'   => 'Anzahl Instandhaltungszyklen'
    ];


    /**
     * Constructor
     *
     * @param ElcaProjectVariant $ProjectVariant
     */
    public function __construct(ElcaProjectVariant $ProjectVariant)
    {
        $this->ProjectVariant = $ProjectVariant;
    }
    // End __construct


    /**
     * @return string
     */
    public function getCsv()
    {
        /**
         * Header
         */
        $Project = $this->ProjectVariant->getProject();
        $ProcessDb = $this->ProjectVariant->getProject()->getProcessDb();
        $Location = $this->ProjectVariant->getProjectLocation();
        $Constr = $this->ProjectVariant->getProjectConstruction();

        $headerData = [];
        $headerData[] = (object)['caption' => t('Projektname'), 'value' => $Project->getName()];
        $headerData[] = (object)['caption' => t('Projektnummer'), 'value' => $Project->getProjectNr()];
        $headerData[] = (object)['caption' => t('Beschreibung'), 'value' => $Project->getDescription()];
        $headerData[] = (object)['caption' => t('Straße'), 'value' => $Location->getStreet()];
        $headerData[] = (object)['caption' => t('PLZ'), 'value' => $Location->getPostcode()];
        $headerData[] = (object)['caption' => t('Ort'), 'value' => $Location->getCity()];
        $headerData[] = (object)['caption' => t('Baumaßnahme'), 'value' => Elca::$constrMeasures[$Project->getConstrMeasure()]];
        $headerData[] = (object)['caption' => t('Nutzungsdauer'), 'value' => $Project->getLifeTime()];
        $headerData[] = (object)['caption' => t('Bauwerkszuordnung'), 'value' => $Project->getConstrClass()->getRefNum() . ' - ' . $Project->getConstrClass()->getName()];
        $headerData[] = (object)['caption' => t('Baustoffdatenbank'), 'value' => $ProcessDb->getName()];
        $headerData[] = (object)['caption' => t('Bauteilkatalog'), 'value' => $Constr->getConstrCatalog()->getName()];
        $headerData[] = (object)['caption' => t('Bevorzugte Bauweise'), 'value' => $Constr->getConstrDesign()->getName()];
        $headerData[] = (object)['caption' => t('Nettogrundfläche'), 'value' => $Constr->getNetFloorSpace()];
        $headerData[] = (object)['caption' => t('Bruttogrundfläche'), 'value' => $Constr->getGrossFloorSpace()];
        $headerData[] = (object)['caption' => t('Nutzfläche'), 'value' => $Constr->getFloorSpace()];
        $headerData[] = (object)['caption' => t('Grundstücksfläche'), 'value' => $Constr->getPropertySize()];
        $headerData[] = (object)['caption' => '', 'value' => ''];

        /**
         * CSV
         */
        $Indicators = ElcaIndicatorSet::findByProcessDbId($ProcessDb->getId());

        $columns = array_keys(self::$columns);
        $headers = array_values(self::$columns);

        if ($ProcessDb->isEn15804Compliant()) {
            $lcIdents = [ElcaLifeCycle::IDENT_A13, ElcaLifeCycle::PHASE_MAINT, ElcaLifeCycle::IDENT_B6,
                         ElcaLifeCycle::IDENT_C3, ElcaLifeCycle::IDENT_C4, ElcaLifeCycle::IDENT_D];
        } else {
            $lcIdents = [ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_MAINT, ElcaLifeCycle::PHASE_OP, ElcaLifeCycle::PHASE_EOL];
        }

        foreach ($lcIdents as $index => $lcIdent) {
            if ($lcIdent == ElcaLifeCycle::PHASE_MAINT)
                continue;

            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            $columns[] = 'p_' . $tblIdent;
            $headers[] = t('Datensatz') . ' ' . \utf8_strtoupper($lcIdent);
        }
        foreach ($lcIdents as $index => $lcIdent) {
            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            /** @var ElcaIndicator $Indicator */
            foreach ($Indicators as $Indicator) {
                $headers[] = t($Indicator->getName()) . ' ' . \utf8_strtoupper($lcIdent) . ' [' . $Indicator->getUnit() . ']';
                $columns[] = 'i_' . $tblIdent . '_' . \utf8_strtolower($Indicator->getIdent());
            }
        }

        $Exporter = new CsvExporter();
        $Exporter->setNullExpression('NULL');
        $Exporter->setDelimiter(';');
        $Exporter->setLineFeed("\n");
        $Exporter->setDataObjectlist($headerData, ['caption', 'value']);
        $export = $Exporter->getString();

        $Exporter->setHeaders($headers);
        $Exporter->setDataObjectlist(BnbExportSet::findElementsForCsvExport($this->ProjectVariant)->getArrayCopy(), $columns);
        $export .= $Exporter->getString();

        $Exporter->setHeaders(null);
        $Exporter->setDataObjectlist(BnbExportSet::findEnergyDemandsForCsvExport($this->ProjectVariant)->getArrayCopy(), $columns);
        $export .= $Exporter->getString();

        return $export;
    }
    // End getCsv
}
// End BnbCsvExporter