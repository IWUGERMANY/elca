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
namespace Elca\View\helpers;

use Beibob\Blibs\Environment;
use Beibob\Blibs\HtmlDOMFactory;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Log;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessConfig;
use Elca\Elca;
use Elca\View\ElcaProcessConfigSelectorView;
use Exception;

/**
 * Builds a link to the processConfig selector
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlProcessConfigSelectorLink extends HtmlFormElement
{
    /**
     * Parameters
     */
    private $elementId;

    private $relId;

    private $buildMode;

    private $processCategoryNodeId;

    private $position;

    private $inUnit;

    private $context;

    private $projectVariantId;

    private $filterByProjectVariantId;

    private $processDbId;

    private $data;

    private $caption;

    private $disableDataSheet;

    private $headline;

    private $enableReplaceAll;

    /**
     * Sets the context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    // End setContext


    /**
     * Sets the elementId
     */
    public function setElementId($elementId)
    {
        $this->elementId = $elementId;
    }
    // End setElementId


    /**
     * Sets the relId
     */
    public function setRelId($relId)
    {
        $this->relId = $relId;
    }
    // End setRelId


    /**
     * Sets the relId
     */
    public function setBuildMode($buildMode)
    {
        $this->buildMode = $buildMode;
    }
    // End setBuildMode


    /**
     * Sets the processCategoryNodeId
     */
    public function setProcessCategoryNodeId($nodeId)
    {
        $this->processCategoryNodeId = $nodeId;
    }
    // End setProcessCategoryNodeId


    /**
     * Sets the position
     */
    public function setPosition($pos)
    {
        $this->position = $pos;
    }
    // End setPosition


    /**
     * Sets some data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
    // End setData


    /**
     * Sets the inUnit
     */
    public function setInUnit($inUnit)
    {
        $this->inUnit = $inUnit;
    }
    // End setInUnit


    /**
     * Sets the projectVariantId
     */
    public function setProjectVariantId($projectVariantId)
    {
        $this->projectVariantId = $projectVariantId;
    }

    /**
     * @param mixed $filterByProjectVariantId
     */
    public function setFilterByProjectVariantId($filterByProjectVariantId)
    {
        $this->filterByProjectVariantId = $filterByProjectVariantId;
    }
    // End projectVariantId

    /**
     * @param mixed $processDbId
     */
    public function setProcessDbId($processDbId)
    {
        $this->processDbId = $processDbId;
    }

    /**
     * @return mixed
     */
    public function headline()
    {
        return $this->headline;
    }

    /**
     * @param mixed $headline
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;
    }

    /**
     * @return mixed
     */
    public function enableReplaceAll()
    {
        return $this->enableReplaceAll;
    }

    /**
     * @param mixed $enableReplaceAll
     */
    public function setEnableReplaceAll($enableReplaceAll = true)
    {
        $this->enableReplaceAll = $enableReplaceAll;
    }


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     *
     * @param DOMDocument $Document
     *
     * @return \DOMElement|\DOMNode
     */
    public function build(DOMDocument $Document)
    {
        $procConfigId = $this->getConvertedTextValue();
        if ($procConfigId == 'NULL') {
            $procConfigId = null;
        }

        $processConfig = ElcaProcessConfig::findById($procConfigId);

        if ($procConfigId) {
            $procConfigName              = $processConfig->getName();
            $this->processCategoryNodeId = $processConfig->getProcessCategoryNodeId();
        } else {
            $procConfigName = $this->isReadonly() ? '-' : $this->caption();
        }

        $args      = [];
        $args['p'] = $procConfigId;

        if ($this->relId) {
            $args['relId'] = $this->relId;
        }

        if ($this->elementId) {
            $args['elementId'] = $this->elementId;
        }

        $args['b'] = $this->buildMode;

        if ($this->processCategoryNodeId) {
            $args['c'] = $this->processCategoryNodeId;
        }

        if ($this->position) {
            $args['pos'] = $this->position;
        }

        if ($this->inUnit) {
            $args['u'] = $this->inUnit;
        }

        if ($this->projectVariantId) {
            $args['projectVariantId'] = $this->projectVariantId;
        }

        if ($this->filterByProjectVariantId)
            $args['filterByProjectVariantId'] = $this->filterByProjectVariantId;

        if ($this->processDbId) {
            $args['db'] = $this->processDbId;
        }

        if ($this->data) {
            $args['data'] = $this->data;
        }

        if ($this->headline) {
            $args['headline'] = $this->headline;
        }

        if ($this->enableReplaceAll) {
            $args['replaceAll'] = true;
        }

        $href = Url::factory('/' . $this->context . '/selectProcessConfig/', $args);

        $Factory = new HtmlDOMFactory($Document);

        if ($this->isReadonly()) {
            $A = $Factory->getSpan($procConfigName);

            if (!$this->isDisabled()) {
                $A->appendChild($Factory->getHiddenInput($this->getName(), $procConfigId));
            }
        } else {
            $aAttr = [
                'href'  => $href,
                'title' => $procConfigName,
                'rel'   => 'open-modal'
            ];

            $A = $Factory->getA($aAttr, $procConfigName);
            $A->appendChild($Factory->getHiddenInput($this->getName(), $procConfigId));
        }

        if ($this->hasError()) {
            $this->addClass('error');
        }


        $Div = $Factory->getDiv();
        $Div->appendChild($A);

        /**
         * Set remaining attributes
         */
        $this->addClass('elca-process-config-selector');
        $this->buildAndSetAttributes($Div, $this->getDataObject(), $this->getName());

        foreach ($this->getChildren() as $Child) {
            $Child->appendTo($A);
        }

        if (!$this->disableDataSheet && $processConfig->isInitialized()) {
            $lifeCyclePhase = $this->buildMode == ElcaProcessConfigSelectorView::BUILDMODE_OPERATION ? ElcaLifeCycle::PHASE_OP : ElcaLifeCycle::PHASE_PROD;
            $processDbId    = $this->processDbId ?: Elca::getInstance()->getProject()->getProcessDbId();
            $ProcessSet     = $processConfig->getProcessesByProcessDbId(
                $processDbId
                ,
                ['life_cycle_phase' => $lifeCyclePhase]
            );

            /**
             * @todo this is the case when A1, A2, A3 is given separately
             */
//            if ($ProcessSet->count() > 1) {
//                $this->sendMoreThenOneProcesses($ProcessConfig);
//            }

            /**
             * @var ElcaProcess $Process
             */
            $Process = isset($ProcessSet[0]) ? $ProcessSet[0] : null;
            if ($Process && $Process->isInitialized() && $Process->getProcessDb()->hasSourceUri()) {
                $aAttr = [
                    'class' => 'data-sheet-link icon no-xhr',
                    'href'   => $Process->getDataSheetUrl(),
                    'target' => '_blank'
                ];

                $Div->appendChild($Factory->getA($aAttr, t('Datenblatt')));
                $Div->appendChild($Factory->getDiv(['class' => 'clearfix']));
            }
        }

        return $Div;
    }

    /**
     * @param mixed $caption
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    }

    /**
     * @return mixed
     */
    public function caption()
    {
        return null !== $this->caption ? $this->caption : t('auswählen');
    }

    /**
     * @param mixed $disableDataSheet
     */
    public function setDisableDataSheet($disableDataSheet = true)
    {
        $this->disableDataSheet = $disableDataSheet;
    }
    // End build


    /**
     * @param User $User
     *
     * @return bool
     */
    protected function sendMoreThenOneProcesses($ProcessConfig)
    {
        $MailView = new HtmlView('mail/more_than_one_process');
        $MailView->assign('ProcessConfig', $ProcessConfig);
        $MailView->assign('Project', Elca::getInstance()->getProject());
        $MailView->assign('hostname', Environment::getInstance()->getServerHost());
        $MailView->assign('imageBaseUrl', 'http://' . $MailView->get('hostname') . '/img/elca/');
        $MailView->assign('adminMailAddress', Environment::getInstance()->getConfig()->elca->mailAddress);
        $MailView->assign('version', 'v' . Elca::VERSION_BBSR);

        $Config = Environment::getInstance()->getConfig();
        try {
            $MailView->process();

            /** @var Mailer $Mail */
            $Mail = Environment::getInstance()->getContainer()->get('Elca\Service\Mailer');
            $Mail->setSubject('eLCA | Mehr als ein Processes ...');
            $Mail->setHtmlContent((string)$MailView);
            $Mail->send($Config->postErrorFilter->sendMailTo);

            return true;
        } catch (Exception $Exception) {
            Log::getInstance()->error(__METHOD__ . '() - Exception: ' . $Exception->getMessage());

            return false;
        }

        return false;
    }
    // End sendMoreThenOneProcesses
}

// End ElcaHtmlProcessConfigSelectorLink
