<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2020 m boeneke <boeneke@online-now.de>
 *               Online Now! GmbH
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

namespace Elca\Controller\Admin;

use Beibob\Blibs\Config;
use Beibob\Blibs\CssLoader;
use Beibob\Blibs\JsLoader;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Url;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Validator;
use Beibob\Blibs\File;
use Beibob\Blibs\FileView;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\Environment;
use Beibob\Blibs\IdFactory;
use Beibob\Blibs\Log;
use Beibob\Blibs\SessionNamespace;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaSetting;
use Elca\ElcaNumberFormat;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Admin\LifeCycleUsageSpecificationService;
use Elca\Db\ElcaReportSet;
use Elca\Service\ElcaSessionRecovery;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\Processing\BenchmarkService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\Admin\Bauteilkatalog\ElcaAdminBauteilkatalogView;
use Elca\View\Admin\Bauteilkatalog\ElcaAdminBauteilkatalogCreatePDFView;
use Elca\View\Admin\Bauteilkatalog\ElcaAdminBauteilkatalogHeaderFooterView;
use Elca\View\Admin\Bauteilkatalog\ElcaAdminBauteilkatalogPdfModalDownloadView;
use Elca\View\Admin\Bauteilkatalog\ElcaAdminBauteilkatalogPdfModalView;
use Exception;

/**
 * Admin Bauteilkatalog
 *
 * @package    elca
 * @author     m boeneke <boeneke@online-now.de>
 * @copyright  Online Now! GmbH - BEIBOB medienfreunde GbR
 */
class BauteilkatalogCtrl extends TabsCtrl
{
    
    /**
     * Section name
     */
    const SETTING_SECTION = 'elca.admin.bauteilkatalog';
    
    const CATALOGACTION = 'catalog';
    const DEFAULT_PATH_WKHTMLTOPDF = "/usr/bin/wkhtmltopdf";
    const MODAL_CLOSE_TIMEOUT = 6000;

    /**
     * Context
     */
    const CONTEXT = 'admin-bauteilkatalog';
    const SETTING_SECTION_PROJECTIONS = 'elca.admin.benchmark.projections';
    const SETTING_SECTION_DIN_CODES = 'elca.admin.reference-projects';
    
    const BUILDMODE_SCREEN = 'screen';
    const BUILDMODE_PDF = 'pdf';

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->Access->hasAdminPrivileges()) {
            $this->noAccessRedirect('/');
        }
    }
    // End init


    /**
     * default action
     */
    protected function defaultAction()
    {
        /* 
        if (FrontController::getInstance()->getRequest()->has('pdf'))
        {
            JsLoader::getInstance()->unregisterAll();
            CssLoader::getInstance()->unregisterAll();
            var_dump("O.K. PDF");
        }
        else 
        */
        $this->systemsAction();
    }
    // End defaultAction


    /**
     * default action
     */
    protected function systemsAction($systemId = null, $addNavigationViews = true, Validator $Validator = null)
    {
        if (!$this->isAjax()) {
            return;
        }
        
        $this->setView(new ElcaAdminBauteilkatalogView()); 

        // $View = $this->setView(new ElcaReportEffectsView());

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->Osit->add(new ElcaOsitItem(t('PDF Erstellung'), null, t('Bauteilkatalog')));
        }
        $this->addView(new ElcaAdminNavigationLeftView());
    }
    // End systemsAction


    /**
     * create Bauteilkatalog action
     */
    protected function createBauteilkatalogAction(Validator $Validator = null)
    {
        if (!$this->isAjax()) {
            return;
        }

        $View = $this->setView(new ElcaAdminBauteilkatalogCreatePDFView());
        $View->assign('Validator', $Validator);
    }
    // End createSystemAction

     /**
     * Open modal and start pdf generation
     */
    protected function pdfModalAction()
    {
		// elca.js Row:2158 prepareCatalogPdf: function ($context) 
		// 
        $view      = $this->addView(new ElcaAdminBauteilkatalogPdfModalView());
        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdf', ['a' => $this->Request->a]);
        $view->assign('action', $pdfUrl);
        $view->assign('closeAfterTimeInMs', self::MODAL_CLOSE_TIMEOUT);
    }

    /**
     * Open modal to start download pdf if generation has been completed
     */
    protected function pdfModalDownloadAction()
    {
		// elca.js Row:2158 preparePdf: function ($context) 
		// 
        $V  = $this->addView(new ElcaAdminBauteilkatalogPdfModalDownloadView());
        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdfDownload', ['a' => $this->Request->a]);
		$pdfTimeCreated = $this->Request->t;
        $V->assign('action', $pdfUrl);
		$V->assign('timecreated', $pdfTimeCreated);
    }

    /**
     * generate pdf
     */
    protected function pdfAction()
    {
        $key = IdFactory::getUniqueId();

		$PDFinfo = ElcaReportSet::findPdfInQueue(
			0, 
			0,
			$this->Session->getNamespace('blibs.userStore')->__get('userId'), 
			FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a)
		);
		
		if( !$PDFinfo->isEmpty() )
		{
			$infoArrayKey = (array)$PDFinfo[0]->key;
			$key = $infoArrayKey[0];
		}	
		
		$environment = Environment::getInstance();
		$config = $environment->getConfig();
		
        $tmpCacheDir = $config->toDir('baseDir') . $config->toDir('pdfCreateDir', true, 'tmp/pdf-data').$key;	
		$tempTitle = date('Ymd') . '_' . $this->buildFilename(t('eLCA-Bauteilkatalog')) . '.pdf';		
		
		if (!\is_dir($tmpCacheDir)) {
            if (!mkdir($tmpCacheDir, 0777, true) && !is_dir($tmpCacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpCacheDir));
            }
			chmod($tmpCacheDir,0777);
		}	

		$pdf = new File($tmpCacheDir.'/tempPDF.pdf');

		// -----------------------------------------
        $namespace = $this->getSessionNamespace($environment);
        $keys      = $namespace->pdfKeys;
        if (!is_array($keys)) {
            $keys = [];
        }
        $keys[$key] = $pdf->getFilepath();

        $namespace->pdfKeys = $keys;

        /**
         * @var ElcaSessionRecovery
         */
        $SessionRecovery = $this->container->get('Elca\Service\ElcaSessionRecovery');
        $SessionRecovery->storeNamespace($namespace);
        $SessionRecovery->storeNamespace($this->Session->getNamespace('blibs.userStore'));
        $SessionRecovery->storeNamespace($this->Session->getNamespace('elca'));
        $SessionRecovery->storeNamespace($this->Session->getNamespace('elca.locale'));
        // $SessionRecovery->storeNamespace($this->Session->getNamespace(ProjectAccess::NAMESPACE_NAME));
        $SessionRecovery->storeNamespace($this->Session->getNamespace(self::SETTING_SECTION));
        
		
		// $tmpCacheDir = $config->toDir('baseDir') . $config->toDir('cacheDir', true, 'tmp/cache');
		// -----------------------------------------

        /*
        $projectsUrl = new Url(
            FrontController::getInstance()->getUrlTo('Elca\Controller\Admin\BauteilkatalogCtrl')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified'), 'pdf' => 1]
            , '!' . FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a, ['pdf' => 1])
            , Environment::getServerHostName()
            , $_SERVER['SERVER_PORT']
            , Environment::sslActive() ? 'https' : 'http'
        );
        */
        
        $catalogUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'pdfCatalog')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified')]
            , null
            , Environment::getServerHostName()
            , $_SERVER['SERVER_PORT']
            , Environment::sslActive() ? 'https' : 'http'
        );
        
        $tmpCatalogFile = new File();
        $tmpCatalogFile->createTemporaryFile($tmpCacheDir);
        $tmpCatalogFile->write(file_get_contents((string)$catalogUrl));
        $tmpCatalogFile->close();
        
		File::move($tmpCatalogFile->getFilepath(), $tmpCatalogFile->getFilepath() .'.html');
        $tmpCatalogFile = new File($tmpCatalogFile->getFilepath() .'.html');        
       
        $headerUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'pdfHeader')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified')]
            , null
            , Environment::getServerHostName()
            , $_SERVER['SERVER_PORT']
            , Environment::sslActive() ? 'https' : 'http'
        );
        
        $tmpHeaderFile = new File();
        $tmpHeaderFile->createTemporaryFile($tmpCacheDir);
        $tmpHeaderFile->write(file_get_contents((string)$headerUrl));
        $tmpHeaderFile->close();
		
        
		File::move($tmpHeaderFile->getFilepath(), $tmpHeaderFile->getFilepath() .'.html');
        $tmpHeaderFile = new File($tmpHeaderFile->getFilepath() .'.html');
        
        
        $footerUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'pdfFooter')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified')]
            , null
            , Environment::getServerHostName()
            , $_SERVER['SERVER_PORT']
            , Environment::sslActive() ? 'https' : 'http'
        );

        $tmpFooterFile = new File();
        $tmpFooterFile->createTemporaryFile($tmpCacheDir);
        $tmpFooterFile->write(file_get_contents((string)$footerUrl));
        $tmpFooterFile->close();
        File::move($tmpFooterFile->getFilepath(), $tmpFooterFile->getFilepath() .'.html');
        $tmpFooterFile = new File($tmpFooterFile->getFilepath() .'.html');
 

        
        $wkhtmltopdfCmd = $this->resolveWkhtmltopdfCmd($config);
        
        /*
        $cmd = sprintf(
            '%s --quiet --window-status ready_to_print --cache-dir %s --title %s -s A4 --margin-top 55 --margin-bottom 30 --margin-right 10 --margin-left 25 --print-media-type --no-stop-slow-scripts --javascript-delay %d --header-html %s --header-spacing 15 --footer-html %s %s %s',
            $wkhtmltopdfCmd,
            escapeshellarg($tmpCacheDir),
            escapeshellarg($tempTitle),
            1000, // javascript-delay
            escapeshellarg((string)$tmpHeaderFile->getFilepath()),
            escapeshellarg((string)$tmpFooterFile->getFilepath()),
            escapeshellarg((string)$projectsUrl),
            $pdf->getFilepath()
        );
        */
        // --window-status ready_to_print : deactivated ON! 11.12.2020
        $cmd = sprintf(
            '%s --quiet  --cache-dir %s --title %s -s A4 --margin-top 55 --margin-bottom 30 --margin-right 10 --margin-left 25 --print-media-type --no-stop-slow-scripts --javascript-delay %d --header-html %s --header-spacing 15 --footer-html %s %s %s',
            $wkhtmltopdfCmd,
            escapeshellarg($tmpCacheDir),
            escapeshellarg($tempTitle),
            1000, // javascript-delay
            escapeshellarg((string)$tmpHeaderFile->getFilepath()),
            escapeshellarg((string)$tmpFooterFile->getFilepath()),
            escapeshellarg((string)$tmpCatalogFile->getFilepath()),
            $pdf->getFilepath()
        );
        

        Log::getInstance()->debug($cmd);
		// exec($cmd);

        // delete tmp header and footer files
        // $tmpHeaderFile->delete();
        // $tmpFooterFile->delete();

		// saving / placing PDF report data in queue
		// :user_id, :projects_id, :projects_name, :current_variant_id, :pdf_cmd, :key
		$queue_values = [
				'user_id' => $this->Session->getNamespace('blibs.userStore')->__get('userId'), 
				'projects_id' => 0, 
				'report_name' => FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a), 
				'projects_filename' => $tempTitle,
				'current_variant_id' => 0, 
				'pdf_cmd' => $cmd, 
				'key' => $key
				];
		$testPDF = ElcaReportSet::setPdfInQueue($queue_values);


		/*
        $View = $this->addView(new HtmlView());
        $View->appendChild($View->getDiv(['id' => 'download-pdf'], $P = $View->getP('')));

        $downloadUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'downloadPdf'),
            ['key' => $key]
        );
        $P->appendChild(
            $View->getA(
                ['class' => 'no-xhr', 'href' => $downloadUrl],
                $this->buildFilename($this->Elca->getProject()->getName()) . '.pdf'
            )
        );
        */

		$this->reloadHashUrl();
    }    
    
    /**
     * Check for generated pdf to deliver
     */
    protected function checkPdfAction()
    {
		$reportsPDF = ElcaReportSet::findPdfInQueue(
			0, 
			0,
			$this->Session->getNamespace('blibs.userStore')->__get('userId'), 
			FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a)
		);	
		
		return $reportsPDF;
	}    
    
    /**
     * Delivers generated pdf
     */
    protected function downloadPdfAction()
    {
        /*
		$namespace = $this->getSessionNamespace();
        $pdf = File::factory($namespace->pdfKeys[$this->Request->key]);

         
		if (!$pdf->exists())
        {
            $this->messages->add('Die angeforderte Datei ist nicht verfÃ¼gbar!', ElcaMessages::TYPE_ERROR);
            return;
        }
		*/
		$PDFinfo = ElcaReportSet::findPdfInQueueByHash(
			0, 
			0,
			$this->Session->getNamespace('blibs.userStore')->__get('userId'), 
			$this->Request->key
			// FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->key)
		);

		if( !$PDFinfo->isEmpty() )
		{
			$infoArray = (array)$PDFinfo[0];
			
			$environment = Environment::getInstance();
			$config = $environment->getConfig();
			$tmpCacheDir = $config->toDir('baseDir') . $config->toDir('pdfCreateDir', true, 'tmp/pdf-data').$infoArray["key"];	

			$pdf = new File($tmpCacheDir."/tempPDF.pdf");
			$this->Response->setHeader('Content-Type: application/pdf');
			$this->Response->setHeader('Content-Disposition: attachment; filename=' . urlencode($infoArray["projects_filename"]));
			$this->setBaseView(new FileView($pdf->getFilepath()));
		}
		else
		{
			$this->messages->add('Die angeforderte Datei ist nicht verfÃ¼gbar!', ElcaMessages::TYPE_ERROR);
            return;
		}		
    }    
    
    
    /**
     * Renders only the content for pdf version of bauteilkatalog
     */
    protected function pdfCatalogAction()
    {
        if ($this->isAjax()) {
            return;
        }

        if (!$this instanceof TabsCtrl)
            throw new \Exception('Elca\Controller\Traits\ProjectPdfReportsTrait can only used by Elca\Controller\TabsCtrl');

        JsLoader::getInstance()->unregisterAll();
        CssLoader::getInstance()->unregisterAll();

        $BaseView = $this->setBaseView(new HtmlView('elca_admin_bauteilkatalog_header_footer_base', 'elca'));
        $BaseView->assign('action', $this->getAction());
        
        $View = $this->setView(new ElcaAdminBauteilkatalogView()); 
        $View->assign('buildMode', ElcaAdminBauteilkatalogView::BUILDMODE_PDF);
    }
    // End pdfHeaderAction    
    
    /**
     * Renders only the header for pdf version of bauteilkatalog
     */
    protected function pdfHeaderAction()
    {
        if ($this->isAjax()) {
            return;
        }

        if (!$this instanceof TabsCtrl)
            throw new \Exception('Elca\Controller\Traits\ProjectPdfReportsTrait can only used by Elca\Controller\TabsCtrl');

        JsLoader::getInstance()->unregisterAll();
        CssLoader::getInstance()->unregisterAll();

        $BaseView = $this->setBaseView(new HtmlView('elca_admin_bauteilkatalog_header_footer_base', 'elca'));
        $BaseView->assign('action', $this->getAction());
        $View = $this->setView(new ElcaAdminBauteilkatalogHeaderFooterView());
        $View->assign('buildMode', ElcaAdminBauteilkatalogHeaderFooterView::BUILDMODE_HEADER);
    }
    // End pdfHeaderAction


    /**
     * Renders only the footer for pdf version of bauteilkatalog
     */
    protected function pdfFooterAction()
    {
        if ($this->isAjax()) {
            return;
        }

        if (!$this instanceof TabsCtrl)
            throw new \Exception('Elca\Controller\Traits\ProjectPdfReportsTrait can only used by Elca\Controller\TabsCtrl');

        JsLoader::getInstance()->unregisterAll();
        CssLoader::getInstance()->unregisterAll();

        $BaseView = $this->setBaseView(new HtmlView('elca_admin_bauteilkatalog_header_footer_base', 'elca'));
        $BaseView->assign('action', $this->getAction());
        $View = $this->setView(new ElcaAdminBauteilkatalogHeaderFooterView());
        $View->assign('buildMode', ElcaAdminBauteilkatalogHeaderFooterView::BUILDMODE_FOOTER);
    }    
    
    
   /**
     * Generates a handy filename for pdf file
     *
     * @param $projektName
     *
     * @return string
     */
    protected function buildFilename($projektName)
    {
        $clean = str_replace(' ', '_', \trim($projektName));

        $clean = str_replace(' ', '-', $clean); // Replaces all spaces with hyphens.
        $clean = preg_replace('/[^A-Za-z0-9\_]/', '', $clean); // Removes special chars.

        $clean = preg_replace('/_+/', '_', $clean);

        return \utf8_strtolower($clean);

    }
    
    
    /**
     * show pdf for downloading
     */
    protected function pdfDownloadAction()
    {
       $PDFinfo = ElcaReportSet::findPdfInQueue(
			0, 
			0,
			$this->Session->getNamespace('blibs.userStore')->__get('userId'), 
			FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a)
		);
	  
	   if( !$PDFinfo->isEmpty() )
		{
			$infoArray = (array)$PDFinfo[0];
			
			if(!is_null($infoArray["ready"]))
			{
				$environment = Environment::getInstance();
				$config = $environment->getConfig();
				
				$View = $this->addView(new HtmlView());
				$View->appendChild($View->getDiv(['id' => 'download-pdf'], $P = $View->getP('')));
				
				$tmpCacheDir = $config->toDir('baseDir') . $config->toDir('pdfCreateDir', true, 'tmp/pdf-data'). $infoArray["key"];
				$downloadUrl = new Url(
					FrontController::getInstance()->getUrlTo(get_class($this), 'downloadPdf'),
					['key' => $infoArray["key"]]
				);
				$P->appendChild(
					$View->getA(
						['class' => 'no-xhr', 'href' => $downloadUrl],$infoArray["projects_filename"]
						//$this->buildFilename($infoArray["projects_filename"]) 
					)
				);
			}	
		}	
    }
    
    private function resolveWkhtmltopdfCmd(Config $config): string
    {
        if (!isset($config->elca->wkhtmltopdf)) {
            $this->Log->warning("Path to wkhtmltopdf is not configured. Assuming the default location is `".
                                self::DEFAULT_PATH_WKHTMLTOPDF ."'",__FUNCTION__);
        }

        return $config->elca->wkhtmltopdf ?? self::DEFAULT_PATH_WKHTMLTOPDF;
    }

    /**
     * Execute a command and return it's output. Either wait until the command exits or the timeout has expired.
     *
     * @param string $cmd     Command to execute.
     * @param number $timeout Timeout in seconds.
     * @return string Output of the command.
     * @throws \Exception
     */
    private function exec_timeout($cmd, $timeout) {
        // File descriptors passed to the process.
        $descriptors = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w')   // stderr
        );

        // Start the process.
        $process = proc_open('exec ' . $cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \Exception('Could not execute process');
        }

        // Set the stdout stream to none-blocking.
        stream_set_blocking($pipes[1], 0);

        // Turn the timeout into microseconds.
        $timeout = $timeout * 1000000;

        // Output buffer.
        $buffer = '';

        // While we have time to wait.
        while ($timeout > 0) {
            $start = microtime(true);

            // Wait until we have output or the timer expired.
            $read  = array($pipes[1]);
            $other = array();
            stream_select($read, $other, $other, 0, $timeout);

            // Get the status of the process.
            // Do this before we read from the stream,
            // this way we can't lose the last bit of output if the process dies between these     functions.
            $status = proc_get_status($process);

            // Read the contents from the buffer.
            // This function will always return immediately as the stream is none-blocking.
            $buffer .= stream_get_contents($pipes[1]);

            if (!$status['running']) {
                // Break from this loop if the process exited before the timeout.
                break;
            }

            // Subtract the number of microseconds that we waited.
            $timeout -= (microtime(true) - $start) * 1000000;
        }

        // Check if there were any errors.
        $errors = stream_get_contents($pipes[2]);

        if (!empty($errors)) {
            throw new \Exception($errors);
        }

        // Kill the process in case the timeout expired and it's still running.
        // If the process already exited this won't do anything.
        proc_terminate($process, 9);

        // Close all streams.
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return $buffer;
    }    
    
     /**
     * @return SessionNamespace
     */
    protected function getSessionNamespace(Environment $environment)
    {
        return $environment->getSession()->getNamespace(self::SETTING_SECTION, true);
    }
    
    
    // End getSessionNamespace
    
}
// End AdminBauteilkatalogCtrl
