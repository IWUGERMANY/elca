<?php


namespace Elca\Controller\Sanity;


use Elca\Controller\AppCtrl;
use Elca\Controller\ExportsCtrl;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaProcessesNavigationView;
use Elca\View\Sanity\ProcessConfigsEolView;

class ProcessConfigsEolCtrl extends AppCtrl
{
    /**
     * Session namespace
     */
    private $namespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if ($this->hasBaseView()) {
            $this->getBaseView()->setContext(ElcaBaseView::CONTEXT_PROCESSES);
        }

        /**
         * Session namespace
         */
        $this->namespace = $this->Session->getNamespace('elca.process_configs.sanity', true);
    }

    protected function defaultAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        /**
         * Show the default overview view
         */
        $view = $this->setView(new ProcessConfigsEolView());
        $view->assign('processDbId', $this->namespace->processDbId);

        $this->Osit->setProcessConfigsEOLScenario();
        $this->addNavigationView();
    }

    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost()) {
            return;
        }

        $processDbId = $this->Request->get('processDbId');

        if (!$processDbId) {
            $this->messages->add(t('Bitte wählen Sie eine Baustoffdatenbank-Version aus', ElcaMessages::TYPE_ERROR));
            $this->defaultAction();
            return;
        }

        $this->namespace->processDbId = $processDbId;

        if ($this->Request->has('onlyC3')) {
            $linkTo = $this->getLinkTo(
                ExportsCtrl::class,
                'processConfigsEol',
                ['processDbId' => $processDbId, 'lc' => ['C3'], 'ex' => ['C4']]
            );
        } elseif ($this->Request->has('onlyC4')) {
            $linkTo = $this->getLinkTo(
                ExportsCtrl::class,
                'processConfigsEol',
                ['processDbId' => $processDbId, 'lc' => ['C4'], 'ex' => ['C3']]
            );
        } elseif ($this->Request->has('c3AndC4')) {
            $linkTo = $this->getLinkTo(
                ExportsCtrl::class,
                'processConfigsEol',
                ['processDbId' => $processDbId, 'lc' => ['C3', 'C4']]
            );
        } else {
            $linkTo = $this->getLinkTo(
                ExportsCtrl::class,
                'processConfigsEol',
                ['processDbId' => $processDbId, null, 'ex' => ['C3', 'C4']]
            );
        }

        if ($linkTo) {
            $this->Response->setHeader('X-Redirect: ' . $linkTo);
        }
    }

    /**
     * Helper to add the navigation to the view stack
     */
    private function addNavigationView()
    {
        /**
         * Add left navigation
         */
        if (!$this->hasViewByName(ElcaProcessesNavigationView::class)) {
            $this->addView(new ElcaProcessesNavigationView());
        }
    }
}