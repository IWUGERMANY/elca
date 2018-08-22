<?php declare(strict_types=1);
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

namespace Elca\Controller\Sanity;

use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSanity;
use Elca\Db\ElcaProcessConfigSanitySet;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaProcessesNavigationView;
use Elca\View\Sanity\ProcessesView;

class ProcessesCtrl extends AppCtrl
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
        $View = $this->setView(new ProcessesView());
        $View->assign('falsePositives', $this->namespace->includeFalsePositives);

        $this->Osit->setProcessSanityScenario();
        $this->addNavigationView();
    }

    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost()) {
            return;
        }

        $falsePositiveValues = $this->Request->getArray('falsePositive');
        $isReferenceValues = $this->Request->getArray('isReference');

        foreach ($falsePositiveValues as $data) {
            $sanity = ElcaProcessConfigSanity::findById($data['id']);

            $value = $data['value'] === 'true';
            if ($sanity->isInitialized() && $sanity->isFalsePositive() !== $value) {
                $sanity->setIsFalsePositive($value);
                $sanity->update();
            }
        }

        foreach ($isReferenceValues as $data) {
            $processConfig = ElcaProcessConfig::findById($data['id']);

            $value = $data['value'] === 'true';
            if ($processConfig->isInitialized() && $processConfig->isReference() !== $value) {
                $processConfig->setIsReference($value);
                $processConfig->update();
            }
        }
    }


    protected function sanitiesJsonAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $dataObjects = ElcaProcessConfigSanitySet::find(true);

        $result = [];
        foreach ($dataObjects as $dataObject) {
            $result[] = (object)[
                'refNum' => $dataObject->ref_num,
                'processConfigId' => $dataObject->process_config_id,
                'processConfigName'=> $dataObject->name,
                'processDbName' => $dataObject->process_db_name,
                'epdTypes' => implode(', ', array_map(function($epdType) { return t($epdType); }, $dataObject->epd_types)),
                'epdModules' => implode(', ', $dataObject->epd_modules),
                'status' => t($dataObject->status),
                'isFalsePositive' => $dataObject->is_false_positive,
                'isReference' => $dataObject->is_reference,
                'id' => $dataObject->id,
            ];
        }

        $this->getView()->assign('processes', $result);
    }

    /**
     * Refresh process config sanity status
     */
    protected function refreshSanitiesAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        ElcaProcessConfigSanity::refreshEntries();

        $this->messages->add(t('Die Liste wurde aktualisiert'));
        $this->defaultAction();
    }

    /**
     * Helper to add the navigation to the view stack
     */
    private function addNavigationView($activeCategoryId = null)
    {
        /**
         * Add left navigation
         */
        if (!$this->hasViewByName('Elca\View\ElcaProcessesNavigationView')) {
            $View = $this->addView(new ElcaProcessesNavigationView());
            $View->assign('activeCategoryId', $activeCategoryId);
        }
    }
}
