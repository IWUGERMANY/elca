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
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\ElcaSessionRecovery;
use Elca\Service\ProjectAccess;
use Elca\View\Report\ElcaReportsHeaderFooterView;
use Elca\View\ReportsPdfModalView;

/**
 * BaseReportsCtrl
 */
abstract class BaseReportsCtrl extends AppCtrl
{
    /**
     * @return SessionNamespace
     */
    abstract protected function getSessionNamespace();

    /**
     * Open modal and start pdf generation
     */
    protected function pdfModalAction()
    {
        $V      = $this->addView(new ReportsPdfModalView());
        $pdfUrl = FrontController::getInstance()->getUrlTo(null, 'pdf', ['a' => $this->Request->a]);
        $V->assign('action', $pdfUrl);
    }

    /**
     * generate pdf
     */
    protected function pdfAction()
    {
        $pdf = new File();
        $pdf->createTemporaryFile();

        $namespace = $this->getSessionNamespace();
        $keys      = $namespace->pdfKeys;
        if (!is_array($keys)) {
            $keys = [];
        }

        $key        = IdFactory::getUniqueId();
        $keys[$key] = $pdf->getFilepath();

        $namespace->pdfKeys = $keys;

        /**
         * @var ElcaSessionRecovery
         */
        $SessionRecovery = $this->container->get('Elca\Service\ElcaSessionRecovery');
        $SessionRecovery->storeNamespace($namespace);
        $SessionRecovery->storeNamespace($this->Session->getNamespace('elca'));
        $SessionRecovery->storeNamespace($this->Session->getNamespace('elca.locale'));
        $SessionRecovery->storeNamespace($this->Session->getNamespace(ProjectAccess::NAMESPACE_NAME));

        $environment = Environment::getInstance();

        $projectsUrl = new Url(
            FrontController::getInstance()->getUrlTo('Elca\Controller\ProjectsCtrl', $this->Elca->getProjectId())
            , ['app_token' => $this->Access->getUser()->getCryptId('modified'), 'pdf' => 1]
            , '!' . FrontController::getInstance()->getUrlTo(get_class($this), $this->Request->a, ['pdf' => 1])
            , Environment::getServerHostName()
            , null
            , Environment::sslActive() ? 'https' : 'http'
        );

        $headerUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'pdfHeader')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified')]
            , null
            , Environment::getServerHostName()
            , null
            , Environment::sslActive() ? 'https' : 'http'
        );

        $tmpHeaderFile = new File();
        $tmpHeaderFile->createTemporaryFile();
        $tmpHeaderFile->write(file_get_contents($headerUrl));
        $tmpHeaderFile->close();
        File::move($tmpHeaderFile->getFilepath(), $tmpHeaderFile->getFilepath() .'.html');
        $tmpHeaderFile = new File($tmpHeaderFile->getFilepath() .'.html');

        $footerUrl = new Url(
            FrontController::getInstance()->getUrlTo(get_class($this), 'pdfFooter')
            , ['app_token' => $this->Access->getUser()->getCryptId('modified')]
            , null
            , Environment::getServerHostName()
            , null
            , Environment::sslActive() ? 'https' : 'http'
        );

        $tmpFooterFile = new File();
        $tmpFooterFile->createTemporaryFile();
        $tmpFooterFile->write(file_get_contents($footerUrl));
        $tmpFooterFile->close();
        File::move($tmpFooterFile->getFilepath(), $tmpFooterFile->getFilepath() .'.html');
        $tmpFooterFile = new File($tmpFooterFile->getFilepath() .'.html');

        $config = $environment->getConfig();
        $tmpCacheDir = $config->toDir('baseDir') . $config->toDir('cacheDir', true, 'tmp/cache');
        
        $cmd = sprintf(
            '/usr/local/bin/wkhtmltopdf --quiet --window-status ready_to_print --cache-dir %s --title %s -s A4 --margin-top 55 --margin-bottom 30 --margin-right 10 --margin-left 25 --print-media-type --no-stop-slow-scripts --javascript-delay %d --header-html %s --header-spacing 15 --footer-html %s %s %s',
            escapeshellarg($tmpCacheDir),
            escapeshellarg(date('Ymd') . '_' . $this->buildFilename($this->Elca->getProject()->getName()) . '.pdf'),
            1000, // javascript-delay
            escapeshellarg((string)$tmpHeaderFile->getFilepath()),
            escapeshellarg((string)$tmpFooterFile->getFilepath()),
            escapeshellarg((string)$projectsUrl),
            $pdf->getFilepath()
        );

        Log::getInstance()->debug($cmd);
        exec($cmd);

        // delete tmp header and footer files
        $tmpHeaderFile->delete();
        $tmpFooterFile->delete();

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
    }

    /**
     * Delivers generated pdf
     */
    protected function downloadPdfAction()
    {
        $namespace = $this->getSessionNamespace();
        $pdf = File::factory($namespace->pdfKeys[$this->Request->key]);

        if (!$pdf->exists())
        {
            $this->messages->add('Die angeforderte Datei ist nicht verfügbar!', ElcaMessages::TYPE_ERROR);
            return;
        }

        $this->Response->setHeader('Content-Type: application/pdf');
        $this->Response->setHeader('Content-Disposition: attachment; filename=' . urlencode(date('Ymd') . '_' . $this->buildFilename($this->Elca->getProject()->getName()) . '.pdf'));

        $this->setBaseView(new FileView($pdf->getFilepath()));
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
    // End pdfFooterAction

}