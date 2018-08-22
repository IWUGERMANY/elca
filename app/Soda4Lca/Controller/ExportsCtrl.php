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
namespace Soda4Lca\Controller;

use Beibob\Blibs\AjaxController;
use Beibob\Blibs\CsvExporter;
use Beibob\Blibs\TextView;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaReportSet;

/**
 * Handles various exports
 *
 * @package    soda4lca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class ExportsCtrl extends AjaxController
{
    /**
     * Exports process report action
     */
    protected function processesAction()
    {
        if(!$this->Request->importId || !is_numeric($this->Request->importId))
           return;

        $importId = $this->Request->importId;

        $View = new TextView();
        //$View->setOutputEncoding('ISO-8859-15');

        $Exporter = new CsvExporter();
        $Exporter->setNullExpression('');
        $Exporter->setDelimiter(';');

        $ProcessSet = Soda4LcaReportSet::findImportedProcesses($importId, [], true);

        $columns = ['status', 'uuid', 'name', 'version', 'modules', 'epd_types', 'details', 'process_configs'];
        $headers = [t('Status'), t('UUID'), t('Name'), t('Version'), t('EPD Module'), t('EPD Subtype'), t('Information'), t('Baustoffkonfigurationen')];

        $Exporter->setHeaders($headers);
        $Exporter->setDataObjectlist($ProcessSet->getArrayCopy(), $columns);
        $View->setContent($Exporter->getString());

        /**
         * This is a hack, to solve the problem with different content encodings
         * in text views due to text substitution through the template engine
         */
        $this->Response->setContent((string)$View);

        $Import = Soda4LcaImport::findById($importId);
        $ProcessDb = $Import->getProcessDb();
        $filename = \trim(t('Importprotokoll') . '_' . $ProcessDb->name .".csv");

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: text/csv');
    }
    // End processExportAction
}
// End ExportsCtrl