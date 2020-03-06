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
namespace Elca\Controller;

use Beibob\Blibs\AjaxController;
use Beibob\Blibs\CsvExporter;
use Beibob\Blibs\File;
use Beibob\Blibs\FileView;
use Beibob\Blibs\MimeType;
use Beibob\Blibs\TextView;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcessConfigViewSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessViewSet;
use Elca\Db\ElcaProject;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Export\Xml\Exporter;
use Elca\Security\ElcaAccess;
use Elca\Service\ProjectAccess;

/**
 * Handles various exports
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class ExportsCtrl extends AjaxController
{
    /**
     * Exports a project
     */
    protected function projectAction()
    {
        if (!is_numeric($this->Request->id))
            return;

        $Project = ElcaProject::findById($this->Request->id);
        if(!$Project->isInitialized())
            return;

        if (!ElcaAccess::getInstance()->isProjectOwnerOrAdmin($Project)) {
            return;
        }

        $projectAccess = $this->container->get(ProjectAccess::class);

        if(!ElcaAccess::getInstance()->canAccessProject(
            $Project,
            $projectAccess->retrieveEncryptedPasswordFromSessionForProject($Project)
        )) {
            return;
        }

        $filename = \trim($Project->getName() .'-'. date('YmdHis')).".xml";

        $Exporter = Exporter::getInstance();
        $Xml = $Exporter->exportProject($Project);

        $View = new TextView();
        $View->setContent($Xml->saveXml());
        $this->Response->setContent((string)$View);
        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: application/xml');
    }
    // End projectAction


    /**
     * Exports an element
     */
    protected function elementAction()
    {
        if(!is_numeric($this->Request->id))
            return;

        $Element = ElcaElement::findById($this->Request->id);
        if(!$Element->isInitialized())
            return;

        if(!ElcaAccess::getInstance()->canAccessElement($Element))
            return;

        $filename = \trim($Element->getName() .'-'. date('YmdHis')).".xml";

        $exporter = Exporter::getInstance();
        $Xml = $exporter->exportElement($Element);

        $View = new TextView();
        $View->setContent($Xml->saveXml());
        $this->Response->setContent((string)$View);
        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: application/xml');
    }
    // End elementAction


    /**
     * Exports processes action
     */
    protected function processesAction()
    {
        if(!$this->Request->id || !is_numeric($this->Request->id))
           return;

        $View = new TextView();
        $View->setOutputEncoding('ISO-8859-15//TRANSLIT');

        $Exporter = new CsvExporter();
        $Exporter->setNullExpression('');
        $Exporter->setDelimiter(';');

        $ProcessData = ElcaProcessViewSet::findWithIndicators($this->Request->id);

        $columns = ['uuid', 'nameOrig', 'category', 'refValue', 'refUnit', 'lifeCycleName'];
        $headers = ['UUID', 'Name', 'Kategorie', 'Bezugsgröße', 'Bezugseinheit', 'EPD Modul'];

        $IndicatorSet = ElcaIndicatorSet::findByProcessDbId($this->Request->id, false, false, ['p_order' => 'ASC']);
        foreach($IndicatorSet AS $Indicator)
        {
            $columns[] = $Indicator->ident;
            $headers[] = $Indicator->name;
        }

        $Exporter->setHeaders($headers);
        $Exporter->setDataObjectlist($ProcessData->getArrayCopy(), $columns);
        $View->setContent($Exporter->getString());

        /**
         * This is a hack, to solve the problem with different content encodings
         * in text views due to text substitution through the template engine
         */
        $this->Response->setContent((string)$View);

        $ProcessDatabase = ElcaProcessDb::findById($this->Request->id);
        $filename = \trim($ProcessDatabase->name . $ProcessDatabase->version).".csv";

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: text/csv');
    }
    // End defaultAction


    /**
     * Export configs action
     */
    protected function configsAction()
    {
        if(!$this->Request->id || !is_numeric($this->Request->id))
            return;

        $ProcessDb = ElcaProcessDb::findById($this->Request->id);

        $View = new TextView();
        $View->setOutputEncoding('ISO-8859-15//TRANSLIT');

        $Exporter = new CsvExporter();
        $Exporter->setNullExpression('');
        $Exporter->setDelimiter(';');

        $ProcessData = ElcaProcessViewSet::findProcessAssignmentsByProcessDbId($ProcessDb);
        $data = $ProcessData->getArrayCopy();

        foreach($data as $Obj)
        {
            $conversions = explode('; ', $Obj->conversions);
            foreach($conversions as $index => $conv)
                $conversions[$index] = ElcaNumberFormat::toString($conv);

            $Obj->conversions = join('; ', $conversions);
        }

        $columns = ['id', 'name', 'density', 'life_time_info', 'min_life_time', 'min_life_time_info', 'avg_life_time', 'avg_life_time_info', 'max_life_time', 'max_life_time_info', 'f_hs_hi'];
        $headers = ['ID', 'Baustoffkonfiguration', 'Rohdichte', 'Nutzungsdauer Info', 'Min. Nutzungsdauer', 'Min. Nutzungsdauer Info', 'Mittlere Nutzungsdauer', 'Mittlere Nutzungsdauer Info', 'Max. Nutzungsdauer', 'Max. Nutzungsdauer Info', 'Faktor Hs/Hi'];

        if ($ProcessDb->isEn15804Compliant()) {
            $lcIdents = [ElcaLifeCycle::IDENT_A13, ElcaLifeCycle::IDENT_A4, ElcaLifeCycle::IDENT_B6,
                              ElcaLifeCycle::IDENT_C3, ElcaLifeCycle::IDENT_C4, ElcaLifeCycle::IDENT_D];
        } else {
            $lcIdents = [ElcaLifeCycle::PHASE_PROD, ElcaLifeCycle::PHASE_OP, ElcaLifeCycle::PHASE_EOL];
        }

        foreach ($lcIdents as $index => $lcIdent) {
            $tblIdent = str_replace('-', '', \utf8_strtolower($lcIdent));

            foreach (['uuid' => 'UUID', 'name_orig' => 'Prozess', 'ref_value' => 'Bezugsgröße', 'ref_unit' => 'Einheit'] as $colName => $caption) {
                $headers[] = $caption .' ['. $lcIdent.']';
                $columns[] = $tblIdent. '_'. $colName;
            }
        }

        $columns[] = 'conversions';
        $headers[] = 'Umrechnungsfaktoren';

        $Exporter->setHeaders($headers);
        $Exporter->setDataObjectlist($data, $columns);
        $View->setContent($Exporter->getString());

        /**
         * This is a hack, to solve the problem with different content encodings
         * in text views due to text substitution through the template engine
         */
        $this->Response->setContent((string)$View);

        $filename = 'Baustoffkonfigurationen_'.$ProcessDb->getName().'.csv';

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: text/csv');
    }
    // End configsAction

    /**
     * Download action for the NOTES.md content
     */
    protected function downloadNotesFileAction()
    {
        $Config = $this->FrontController->getConfig();
        $baseDir = $Config->toDir('baseDir');
        $notesFilepath = $baseDir . Elca::DOCS_FILEPATH . sprintf(Elca::MD_NOTES_FILENAME_PATTERN, Elca::getInstance()->getLocale());

        if (!file_exists($notesFilepath))
            throw new \Exception('Huups! The handbook file "' . $notesFilepath . '" is missing!');

        $View = new TextView();
        $View->setContent(join("\n", file($notesFilepath)));
        $this->Response->setContent((string)$View);
        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"" . basename($notesFilepath) . "\"");
        $this->Response->setHeader('Content-Type: text/plain');
    }
    // End downloadNotesFileAction


    /**
     * Download action for the handbook
     */
    protected function handbookAction()
    {
        $filePath = Elca::getInstance()->getHandbookFilepath();

        if(!file_exists($filePath))
            throw new \Exception('Huups! The handbook file "' . $filePath . '" is missing!');

        $View = $this->addView(new FileView());
        $View->setFilePath($filePath);

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: inline; filename=\"". basename($filePath) ."\"");
        $this->Response->setHeader('Content-Type: '. MimeType::getByFilepath($filePath));
        $this->Response->setHeader('Content-Length: '. File::getFilesize($filePath));
    }

    protected function processConfigsEolAction()
    {
        if (!$this->Request->processDbId) {
            return;
        }

        $processDb = ElcaProcessDb::findById($this->Request->processDbId);
        $lcIdents = $this->Request->getArray('lc');
        $lcExcluded = $this->Request->getArray('ex');
        $processDbName     = $processDb->getName();

        $view = new TextView();
        //$View->setOutputEncoding('ISO-8859-15//TRANSLIT');

        $exporter = new CsvExporter();
        $exporter->setNullExpression('');
        $exporter->setDelimiter(';');

        $processData = ElcaProcessConfigViewSet::findProcessConfigsByDbAndLcIdents($processDb->getId(), $lcIdents, $lcExcluded);
        $data = $processData->getArrayCopy();

        foreach($data as $obj) {
            $obj->process_db = $processDbName;
            $obj->life_cycle_idents = trim($obj->life_cycle_idents, '{}');
            $obj->process_names = trim($obj->process_names, '{}');
            $obj->epd_types = trim($obj->epd_types, '{}');
        }

        $columns = ['process_config_id', 'name', 'process_category', 'process_category_name', 'life_cycle_idents', 'process_names', 'epd_types', 'process_db'];
        $headers = ['ID', 'Baustoffkonfiguration', 'Kategorie', 'Kategoriename', 'Module', 'Zugeordnete EOL Prozesse', 'EPD Typen', 'Baustoff-DB'];

        $exporter->setHeaders($headers);
        $exporter->setDataObjectlist($data, $columns);

        $view->setContent($exporter->getString());

        /**
         * This is a hack, to solve the problem with different content encodings
         * in text views due to text substitution through the template engine
         */
        $this->Response->setContent((string)$view);

        $filename = 'Baustoffkonfigurationen_EOL_' . $processDbName . '.csv';

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: text/csv');
    }


    /**
     * @return bool
     */
    public static function isPublic()
    {
        return false;
    }
    // End isPublic
}
// End ElcaExportCtrl
