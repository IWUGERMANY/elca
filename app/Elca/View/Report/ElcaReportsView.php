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
namespace Elca\View\Report;

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\UserStore;
use Beibob\Blibs\BlibsDateTime;
use DOMElement;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaReportSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Process\Module;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsage;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsages;
use Elca\Model\Project\ProjectId;
use Elca\Service\Project\LifeCycleUsageService;

/**
 * Builds the asset report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
abstract class ElcaReportsView extends HtmlView
{
    /**
     * Print Header and Footer elements
     */
    private $headerFooterView;
    protected $printHeader;
    protected $printFooter;

    /**
     * projectVariantId
     */
    protected $projectVariantId;

    /**
     * @var ElcaProject
     */
    protected $Project;
    protected $ProjectVariant;
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructs the Document
     *
     * @param  string $xmlName
     * @return -
     */
    public function __construct()
    {
        parent::__construct('elca_reports', 'elca');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->projectVariantId = $this->get('projectVariantId', Elca::getInstance()->getProjectVariantId());
        $this->Project = ElcaProjectVariant::findById($this->projectVariantId)->getProject();
        $this->ProjectVariant = ElcaProjectVariant::findById($this->projectVariantId);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getElementById('content');

        $PrintDiv = $Container->appendChild($this->getDiv(['class' => 'button print']));
        $PrintDiv->appendChild($this->getA(['class' => 'no-xhr', 'href' => '#', 'onclick' => 'window.print();return false;'], t('Drucken')));

        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdf', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : 'default']);
        $modalUrl = FrontController::getInstance()->getUrlTo(null, 'pdfModal', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : 'default']);
		$modalUrlDownload = FrontController::getInstance()->getUrlTo(null, 'pdfModalDownload', ['a' => FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : 'default']);
		
		$PrintDiv->appendChild($this->getA(['class' => 'no-xhr', 'rel' => 'open-modal', 'href' => $modalUrl], t('PDF erstellen')));

		// PDF in work / already exists - project_variant, project_id, user_id
		$PDFinfo = ElcaReportSet::findPdfInQueue($this->Project->getId(),$this->projectVariantId, UserStore::getInstance()->getUserId(),FrontController::getInstance()->getUrlTo().(FrontController::getInstance()->getAction() ? FrontController::getInstance()->getAction() : 'default').'/');
		if(!$PDFinfo->isEmpty())
		{
			// PDF ready?
			$infoArrayReady = (array)$PDFinfo[0]->ready;
			if(	!is_null($infoArrayReady[0]) )
			{
				$PDFreadyDate = BlibsDateTime::factory($infoArrayReady[0]);
				$PrintDiv->appendChild($this->getA(['class' => 'no-xhr', 'rel' => 'open-modal','title' => t('Erstellt:').$PDFreadyDate->getDateTimeString(t('DATETIME_FORMAT_DMY') . ' ' . t('DATETIME_FORMAT_HI')), 'href' => $modalUrlDownload], t('PDF anzeigen')));	
				
				$infoArrayKey = (array)$PDFinfo[0]->key;
				if($infoArrayKey[0])
				{	
					
				}
			}		
			else
			{
				$PrintDiv->appendChild($this->getSpan(t('PDF wird erstellt'),['class'=>'pdfcreate']));
			}	
		}	
		
        $ProjectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($this->projectVariantId);

        $lifeTime = $ProjectVariant->getProject()->getLifeTime();

        $Dl = $Container->appendChild($this->getDl(['class' => 'clearfix']));

        $Dl->appendChild($this->getDt(['class' => 'print'], t('Projekt') . ': '));
        $Dl->appendChild($this->getDd(['class' => 'print'], $this->Project->getName()));

        $Dl->appendChild($this->getDt(['class' => 'print'], t('Projektvariante') . ': '));
        $Dl->appendChild($this->getDd(['class' => 'print'], $this->ProjectVariant->getName()));

        if ($this->Project->getProjectNr())
        {
            $Dl->appendChild($this->getDt(['class' => 'print'], t('Projektnummer') . ': '));
            $Dl->appendChild($this->getDd(['class' => 'print'], $this->Project->getProjectNr()));
        }

        if ($this->Project->getDescription())
        {
            $Dl->appendChild($this->getDt(['class' => 'print'], t('Beschreibung') . ': '));
            $Dl->appendChild($this->getDd(['class' => 'print description'], $this->Project->getDescription()));
        }

        $Dl->appendChild($this->getDt(['class' => 'print'], t('Bearbeiter') . ': '));
        $Dl->appendChild($this->getDd(['class' => 'print'], $this->Project->getEditor()? $this->Project->getEditor() : UserStore::getInstance()->getUser()->getFullname()));

        $Dl->appendChild($this->getDt(['class' => 'print'], t('Stand') . ': '));
        $Dl->appendChild($this->getDd(['class' => 'print date'], date('d.m.Y')));



        $Dl->appendChild($this->getDt([], t('Bilanzierungszeitraum') . ': '));
        $Dl->appendChild($this->getDd([], $lifeTime . ' ' . t('Jahre')));

        $this->renderReports($Container, $Dl, $ProjectVariant, $lifeTime);
    }
    // End afterRender


    /**
     * Renders the report
     *
     * @param  DOMElement $Container
     */
    abstract protected function renderReports(DOMElement $Container, DOMElement $InfoDl, ElcaProjectVariant $ProjectVariant, $projectLifeTime);

    /**
     * Appends the print table to the given container and
     * returns the content td to append the main content
     *
     * @param  DOMElement
     * @return DOMElement
     */
    protected function appendPrintTable(DOMElement $Container)
    {
        if (null === $this->headerFooterView) {
            $this->headerFooterView = new ElcaReportsHeaderFooterView();
            $this->headerFooterView->process([], 'elca');

            $this->printHeader = $this->headerFooterView->getElementById('printHeader', true);
            $this->printFooter = $this->headerFooterView->getElementById('printFooter', true);
        }

        $PrintTable = $Container->appendChild($this->getTable(['class' => 'print-table']));

        if (!FrontController::getInstance()->getRequest()->get('pdf'))
        {
            $Header = $PrintTable->appendChild($this->getTHead());
            $Tr = $Header->appendChild($this->getTr());
            $Td = $Tr->appendChild($this->getTd(['class' => 'print-td']));
            $Td->appendChild($this->importNode($this->printHeader, true));

            $Footer = $PrintTable->appendChild($this->getTFoot());
            $Tr = $Footer->appendChild($this->getTr());
            $Td = $Tr->appendChild($this->getTd(['class' => 'print-td']));
            $Td->appendChild($this->importNode($this->printFooter, true));
        }

        $Body = $PrintTable->appendChild($this->getTBody());
        $Tr = $Body->appendChild($this->getTr());
        $TdB = $Tr->appendChild($this->getTd(['class' => 'print-content']));

        return $TdB;
    }
    // End appendPrintTable


    /**
     * @param $infoDl
     */
    protected function appendNonDefaultLifeTimeInfo($infoDl)
    {
        $count = ElcaReportSet::countNonDefaultLifeTimeAssets($this->projectVariantId);
        if ($count) {
            $infoDl->appendChild($this->getDt([], t('Hinweis') . ': '));
            $dd = $infoDl->appendChild($this->getDd([], t('Diese Projektvariante enthält') . ' '));
            $dd->appendChild($this->getSpan($count, ['class' => 'counter non-default-life-time-counter']));
            $dd->appendChild($this->getText(' ' . t('Baustoffe mit einer abweichenden Nutzungsdauer.')));
        }
    }


    /**
     * Appends the container for the element image
     *
     * @param  DOMElement $Container
     * @return -
     */
    protected function appendElementImage(DOMElement $Container, $elementId)
    {
        $attr = array('elementId' => $elementId, 'legend' => '1');
        if (FrontController::getInstance()->getRequest()->has('pdf'))
        {
            $Div = $Container->appendChild($this->getDiv(['class' => 'element-image embedded']));
            $Div->appendChild($this->createElement('include', null, ['name' => 'Elca\Controller\ElementImageCtrl', 'elementId' => $elementId, 'legend' => 1, 'pdf' => 1]));


        }
        else
        {
            $Container->appendChild($this->getDiv(['class' => 'element-image',
                                                   'data-element-id' => $elementId,
                                                   'data-url' => FrontController::getInstance()->getUrlTo('Elca\Controller\ElementImageCtrl', null, $attr)]));
        }
    }
    // End appendElementImage

    /**
     * @return string
     */
    protected function getTotalLifeCycleIdents()
    {
        $en15804Compliant = $this->Project->getProcessDb()->isEn15804Compliant();

        $lcUsages = Environment::getInstance()
                               ->getContainer()
                               ->get(LifeCycleUsageService::class)
                               ->findLifeCycleUsagesForProject(new ProjectId($this->Project->getId()));

        $parts = [];
		// var_dump($lcUsages);
        foreach ($lcUsages->modulesAppliedInTotal() as $module) {
            $parts[$module->value()] = t($module->name());
        }

		

        $parts = $this->cleanupLifeCycleIdents($parts);
		
        sort($parts);
		return implode(', ', $parts);
    }

    /**
     * @return string
     */
    protected function getTotalLifeCycleIdentsReal($reports,$excludesArray)
    {
        $parts = [];
		foreach ($reports as $datasetObj) 
		{
			$dataset = (array)$datasetObj;
			if(!in_array($dataset['category'],$excludesArray) && trim($dataset['category'])!="" )  
			{
				$parts[$dataset['category']] = $dataset['category'];
			}	
		}
		return implode(', ', $parts);
    }


    /**
     * @return string
     */
    protected function getMaintenanceLifeCycleIdents()
    {
        $en15804Compliant = $this->Project->getProcessDb()->isEn15804Compliant();

        $lcUsages = Environment::getInstance()
                               ->getContainer()
                               ->get(LifeCycleUsageService::class)
                               ->findLifeCycleUsagesForProject(new ProjectId($this->Project->getId()));

        $parts = [];
        foreach ($lcUsages->modulesAppliedInMaintenance() as $module) {
            if ($en15804Compliant && $module->isLegacy()) {
                continue;
            }

            if (!$en15804Compliant && !$module->isLegacy()) {
                continue;
            }

            $parts[$module->value()] = t($module->name());
        }

        $parts = $this->cleanupLifeCycleIdents($parts);

        sort($parts);
        return implode(', ', $parts);
    }

    protected static $epdTypeMap = [
        ElcaProcess::EPD_TYPE_GENERIC => 'Generische Datensätze',
        ElcaProcess::EPD_TYPE_AVERAGE => 'Durchschnitt Datensätze',
        ElcaProcess::EPD_TYPE_REPRESENTATIVE => 'Repräsentative Datensätze',
        ElcaProcess::EPD_TYPE_SPECIFIC => 'Spezifische Datensätze',
    ];

    /**
     * @param DOMElement $infoDl
     */
    protected function appendEpdTypeStatistic(DOMElement $infoDl)
    {
        $reportSet = ElcaReportSet::countEpdSubTypes(
            $this->Project->getProcessDbId(),
            $this->projectVariantId,
            [ElcaLifeCycle::PHASE_PROD]
        );

        $epdTypeStatistic = $reportSet->getArrayCopy('epd_type');

        $totalCount         = $reportSet->getSumByKey('count');
        $totalDistinctCount = $reportSet->getSumByKey('distinct_count');

        $infoDl->appendChild($this->getDt(['class' => 'epd-types'], t('Datensätze').': '));

        $dd = $infoDl->appendChild($this->getDd(['class' => 'epd-types'], t('Diese Projektvariante verwendet') . ' '));
        $dd->appendChild($this->getSpan($totalCount, ['class' => 'counter']));
        $dd->appendChild($this->getText(t(' - davon ')));
        $dd->appendChild($this->getSpan($totalDistinctCount, ['class' => 'counter']));
        $dd->appendChild($this->getText(t(' verschiedene - Herstellungsdatensätze, die sich wie folgt gliedern') . ':'));


        $dl = $dd->appendChild($this->getDl(['class' => 'inner clearfix']));

        foreach (ElcaReportSummaryView::$epdTypeMap as $epdType => $caption) {
            if (!isset($epdTypeStatistic[$epdType])) {
                continue;
            }

            $epdTypeDO = $epdTypeStatistic[$epdType];

            if (!$epdTypeDO->count) {
                continue;
            }
            $dl->appendChild($this->getDt([], t($caption).': '));
            $dl->appendChild($this->getDd([], ElcaNumberFormat::toString($epdTypeDO->count)));
        }


    }

    private function cleanupLifeCycleIdents(array $parts)
    {
        if (isset($parts[Module::A13])) {
            unset(
                $parts[Module::A1],
                $parts[Module::A2],
                $parts[Module::A3]
            );
        }

        return $parts;
    }
}
// End ElcaReportsView