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

use Beibob\Blibs\Config;
use Beibob\Blibs\CssLoader;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\FileView;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\IdFactory;
use Beibob\Blibs\JsLoader;
use Beibob\Blibs\Log;
use Beibob\Blibs\SessionNamespace;
use Beibob\Blibs\Url;
use Elca\Db\ElcaReportSet;
use Elca\Service\ElcaSessionRecovery;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\ProjectAccess;
use Elca\View\Report\ElcaReportsHeaderFooterView;
use Elca\View\ReportsPdfModalDownloadView;
use Elca\View\ReportsPdfModalView;

/**
 * BaseReportsCtrl
 */
abstract class BaseReportsCtrl extends AppCtrl
{
    const DEFAULT_PATH_WKHTMLTOPDF = "/usr/bin/wkhtmltopdf";
    const MODAL_CLOSE_TIMEOUT = 2000;

    /**
     * @return SessionNamespace
     */
    abstract protected function getSessionNamespace();

    /**
     * Open modal and start pdf generation
     */
    protected function pdfModalAction()
    {
		// elca.js Row:2158 preparePdf: function ($context) 
		// 
        $view      = $this->addView(new ReportsPdfModalView());
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
        $V  = $this->addView(new ReportsPdfModalDownloadView());
        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdfDownload', ['a' => $this->Request->a]);
        $V->assign('action', $pdfUrl);
    }

    /**
     * generate pdf
     */
    protected function pdfAction()
    {
		$key = IdFactory::getUniqueId();

		$PDFinfo = ElcaReportSet::findPdfInQueue(
			$this->Elca->getProjectId(), 
			$this->Elca->getProjectVariantId(),
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
		$tempTitle = date('Ymd') . '_' . $this->buildFilename($this->Elca->getProject()->getName()) . '.pdf';		
		
		if (!\is_dir($tmpCacheDir)) {
            if (!mkdir($tmpCacheDir, 0777, true) && !is_dir($tmpCacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpCacheDir));
            }
			chmod($tmpCacheDir,0777);
		}	

		$pdf = new File($tmpCacheDir.'/tempPDF.pdf');

		// -----------------------------------------
        $namespace = $this->getSessionNamespace();
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
        $SessionRecovery->storeNamespace($this->Session->getNamespace(ProjectAccess::NAMESPACE_NAME));
		
		// $tmpCacheDir = $config->toDir('baseDir') . $config->toDir('cacheDir', true, 'tmp/cache');
		// -----------------------------------------

        $projectsUrl = new Url(
            FrontController::getInstance()->getUrlTo('Elca\Controller\ProjectsCtrl', $this->Elca->getProjectId())
            , ['app_token' => $this->Access->getUser()->getCryptId('modified'), 'pdf' => 1]
            , '!' . FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a, ['pdf' => 1])
            , Environment::getServerHostName()
            , $_SERVER['SERVER_PORT']
            , Environment::sslActive() ? 'https' : 'http'
        );

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

        Log::getInstance()->debug($cmd);
		// exec($cmd);

        // delete tmp header and footer files
        // $tmpHeaderFile->delete();
        // $tmpFooterFile->delete();

		// saving / placing PDF report data in queue
		// :user_id, :projects_id, :projects_name, :current_variant_id, :pdf_cmd, :key
		$queue_values = [
				'user_id' => $this->Session->getNamespace('blibs.userStore')->__get('userId'), 
				'projects_id' => $this->Elca->getProjectId(), 
				'report_name' => FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a), 
				'projects_filename' => $tempTitle,
				'current_variant_id' => $this->Elca->getProjectVariantId(), 
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
     * show pdf for downloading
     */
    protected function pdfDownloadAction()
    {
       $PDFinfo = ElcaReportSet::findPdfInQueue(
			$this->Elca->getProjectId(), 
			$this->Elca->getProjectVariantId(),
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

    /**
     * Check for generated pdf to deliver
     */
    protected function checkPdfAction()
    {
		$reportsPDF = ElcaReportSet::findPdfInQueue(
			$this->Elca->getProjectId(), 
			$this->Elca->getProjectVariantId(),
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
            $this->messages->add('Die angeforderte Datei ist nicht verfügbar!', ElcaMessages::TYPE_ERROR);
            return;
        }
		*/
		$PDFinfo = ElcaReportSet::findPdfInQueueByHash(
			$this->Elca->getProjectId(), 
			$this->Elca->getProjectVariantId(),
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
			$this->messages->add('Die angeforderte Datei ist nicht verfügbar!', ElcaMessages::TYPE_ERROR);
            return;
		}		
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
     * Renders only the header for pdf version of report
     */
    protected function pdfHeaderAction()
    {
        if ($this->isAjax()) {
            return;
        }

        if (!$this instanceof AppCtrl)
            throw new \Exception('Elca\Controller\Traits\ProjectPdfReportsTrait can only used by Elca\Controller\AppCtrl');

        JsLoader::getInstance()->unregisterAll();
        CssLoader::getInstance()->unregisterAll();

        $BaseView = $this->setBaseView(new HtmlView('elca_report_header_footer_base', 'elca'));
        $BaseView->assign('action', $this->getAction());
        $View = $this->setView(new ElcaReportsHeaderFooterView());
        $View->assign('buildMode', ElcaReportsHeaderFooterView::BUILDMODE_HEADER);
    }
    // End pdfHeaderAction


    /**
     * Renders only the footer for pdf version of report
     */
    protected function pdfFooterAction()
    {
        if ($this->isAjax()) {
            return;
        }

        if (!$this instanceof AppCtrl)
            throw new \Exception('Elca\Controller\Traits\ProjectPdfReportsTrait can only used by Elca\Controller\AppCtrl');

        JsLoader::getInstance()->unregisterAll();
        CssLoader::getInstance()->unregisterAll();

        $BaseView = $this->setBaseView(new HtmlView('elca_report_header_footer_base', 'elca'));
        $BaseView->assign('action', $this->getAction());
        $View = $this->setView(new ElcaReportsHeaderFooterView());
        $View->assign('buildMode', ElcaReportsHeaderFooterView::BUILDMODE_FOOTER);
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
}