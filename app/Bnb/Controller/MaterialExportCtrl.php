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
namespace Bnb\Controller;

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\CsvExporter;
use Beibob\Blibs\StringFactory;
use Beibob\Blibs\TextView;
use Bnb\Model\Export\BnbCsvExporter;
use Bnb\View\BnbCsvExportView;
use Bnb\View\MaterialExportView;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaCacheDataObjectSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;

/**
 * CsvExportCtrl
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class MaterialExportCtrl extends AppCtrl
{
    public function init(array $args = [])
    {
        if ($this->getAction() != 'download')
            parent::init($args);
    }
    // End init


    /**
     * @param bool $addNavigationViews
     * @param null $Validator
     */
    protected function defaultAction($addNavigationViews = true, $Validator = null)
    {
        if (!$this->isAjax())
            return;

        $view = $this->setView(new MaterialExportView());
        $view->assign('projectVariantId', $this->Elca->getProjectVariantId());
        $view->assign('downloadUrl', $this->getActionLink('download', ['id' => $this->Elca->getProjectVariantId()]));

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Stoffstromanalyse'), null, t('Export')));
        }
    }
    // End defaultAction

    /**
     * @param ElcaProjectVariant $ProjectVariant
     *
     * @return mixed|string
     */
    protected function getDownloadFilename(ElcaProjectVariant $ProjectVariant)
    {
        $Project = $ProjectVariant->getProject();
        $filename = \utf8_strtolower($Project->getName() . '_' . $ProjectVariant->getName());
        $filename = \str_replace(' ', 'ABCDEFGHIJKLMNOPQRST', $filename);
        $filename = StringFactory::toAscii($filename, '_');
        $filename = \str_replace('ABCDEFGHIJKLMNOPQRST', '_', $filename);
        $filename = \str_replace('_' . BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString('d.m.Y'), '', $filename);
        $filename .= '_' . BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString('Ymd');
        $filename .= '.csv';

        return $filename;
    }

    protected function downloadAction()
    {
        $projectVariant = ElcaProjectVariant::findById($this->Request->id);
        $project = $projectVariant->getProject();

        if (!$projectVariant->isInitialized()) {
            $this->Response->setHeader('X-Redirect: ' . (string)$this->FrontController->getUrlTo());
            return;
        }


        $view = new TextView();
        $view->setOutputEncoding('ISO-8859-15//TRANSLIT');

        $exporter = new CsvExporter();
        $exporter->setNullExpression('');
        $exporter->setDelimiter(';');

        $data = ElcaCacheDataObjectSet::findProcessConfigMassByProjectVariantId($projectVariant->getId(),
            ['mass' => 'DESC'])->getArrayCopy();

        $headers = [t('Baustoff'), t('Masse') .' (kg)', t('Volumen') . ' (m³)', t('AVV')];
        $columns = ['name', 'mass', 'volume', 'avv'];

        $filename = \trim($project->getName() .'-Stoffstromanalyse-'. date('YmdHis')).".csv";

        $exporter->setHeaders($headers);
        $exporter->setDataObjectlist($data, $columns);
        $view->setContent($exporter->getString());

        /**
         * This is a hack, to solve the problem with different content encodings
         * in text views due to text substitution through the template engine
         */
        $this->Response->setContent((string)$view);

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"".$filename."\"");
        $this->Response->setHeader('Content-Type: text/csv');
    }
    // End downloadAction

}
