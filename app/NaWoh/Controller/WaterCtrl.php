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

namespace NaWoh\Controller;

use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProject;
use Elca\ElcaNumberFormat;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;
use NaWoh\Db\NawohWater;
use NaWoh\Service\WaterService;
use NaWoh\View\NaWohWaterView;

class WaterCtrl extends AppCtrl
{
    protected function defaultAction($addNavigationViews = true, $validator = null)
    {
        if (!$this->isAjax())
            return;


        /**
         * @var WaterService $service
         */
        $service = $this->get(WaterService::class);

        $projectVariant = $this->Elca->getProjectVariant();

        $naWohWater = $service->findConsumptionForProject($projectVariant->getProjectId());
        $naWohWaterVersion = $service->findVersionForConsumption($naWohWater);

        $data = $this->buildDataObject($naWohWater, $naWohWaterVersion);
        foreach ($service->computeConsumptionsFor($naWohWater, $naWohWaterVersion) as $property => $value) {
            $resultProperty = 'result_' . $property;
            $data->$resultProperty = $value;
        }

        $view = $this->addView(new NaWohWaterView());
        $view->assign('data', $data);
        $view->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));

        if ($validator) {
            $view->assign('validator', $validator);
        }

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Rechenhilfe Trinkwasser'), null, t('NaWoh')));
        }
    }
    // End defaultAction


    /**
     * Default action
     *
     * @param  -
     *
     * @return -
     */
    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost())
            return;

        if (!$this->checkProjectAccess() || !$this->Access->canEditProject($this->Elca->getProject())) {
            return;
        }

        $project = ElcaProject::findById($this->Request->projectId);
        if (!$project->isInitialized())
            $project = $this->Elca->getProject();

        $validator = new ElcaValidator($this->Request);

        if ($this->Request->has('save')) {
            $nawohWater = NawohWater::findByProjectId($project->getId());

            foreach (NawohWater::getColumnTypes() as $property => $type) {
                $setter = 'set' . ucfirst($property);
                $value = ElcaNumberFormat::fromString($this->Request->$property);

                switch ($property) {
                    case 'projectId':
                        break;

                    default:
                        $nawohWater->$setter($value);
                        break;
                }
            }

            $nawohWater->update();

            /**
             * Check validator and add error messages
             */
            if (!$validator->isValid()) {
                foreach (array_unique($validator->getErrors()) as $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        } else {
            $nawohWater = NawohWater::findByProjectId($project->getId());

            if ($nawohWater->getMitBadewanne() !== (bool)$this->Request->mitBadewanne) {
                $nawohWater->setMitBadewanne((bool)$this->Request->mitBadewanne);
                $nawohWater->update();
            }
        }

        $this->defaultAction(false, !$validator->isValid() ? $validator : null);
    }

    protected function buildDataObject($naWohWater, $naWohWaterVersion): \stdClass
    {
        $data = new \stdClass();
        foreach (NawohWater::getColumnTypes() as $property => $type) {
            if ($property === 'projectId') {
                continue;
            }

            $data->$property = $this->Request->has($property) ? $this->Request->get($property) : $naWohWater->$property;

            $baseValueProperty        = 'version_'.$property;
            $data->$baseValueProperty = $naWohWaterVersion->$property;

        }

        // Set some defaults
        foreach (['waschmaschine' => 40, 'geschirrspueler' => 15] as $property => $default) {
            if (null === $data->$property) {
                $data->$property = $default;
            }
        }

        return $data;
    }
}
