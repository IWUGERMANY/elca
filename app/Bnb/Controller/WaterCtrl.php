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

use Bnb\Db\BnbWater;
use Bnb\Model\Event\WaterUpdated;
use Bnb\Model\Processing\BnbProcessor;
use Bnb\View\BnbWaterView;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectConstruction;
use Elca\ElcaNumberFormat;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Event\EventPublisher;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;

/**
 * Water controller
 *
 * @package bnb
 * @author  Tobias Lode <tobias@beibob.de>
 */
class WaterCtrl extends AppCtrl
{
    /**
     * Default action
     *
     * @param  -
     *
     * @return -
     */
    protected function defaultAction($addNavigationViews = true, $Validator = null)
    {
        if (!$this->isAjax())
            return;

        $Data = new \stdClass();

        $ProjectVariant = $this->Elca->getProjectVariant();
        $ProjectConstruction = ElcaProjectConstruction::findByProjectVariantId($ProjectVariant->getId());
        $BnbWater = BnbWater::findByProjectId($ProjectVariant->getProjectId());
        if (!$BnbWater->isInitialized())
            $BnbWater = BnbWater::create($ProjectVariant->getProjectId());

        // parameters
        $Data->ngf = max(1, $ProjectConstruction->getNetFloorSpace());

        foreach (BnbWater::getColumnTypes() as $property => $type)
            $Data->$property = $BnbWater->$property;

        // Set some defaults
        foreach (
            [
                'sanitaerWaschtisch' => 0.15,
                'sanitaerWcSpar'     => 9,
                'sanitaerWc'         => 9,
                'sanitaerUrinal'     => 3,
                'sanitaerDusche'     => 0,
                'sanitaerTeekueche'  => 0.25,
            ] as $property => $default
        ) {
            if (null === $Data->$property) {
                $Data->$property = $default;
            }
        }

        // compute BNB Benchmark according to 1.2.3
        $bnbProcessor = new BnbProcessor();
        $Data->sum = $bnbProcessor->computeWaterBenchmark($BnbWater, $Data->ngf);

        $View = $this->addView(new BnbWaterView());
        $View->assign('Data', $Data);
        $View->assign('toggleStates', $this->Request->getArray('toggleStates'));
        $View->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));

        if ($Validator)
            $View->assign('Validator', $Validator);

        /**
         * Add navigation
         */
        if ($addNavigationViews) {
            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Rechenhilfe Trinkwasser (1.2.3)'), null, t('Ökologische Qualität')));
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

        if ($this->Request->has('save')) {
            $Project = ElcaProject::findById($this->Request->projectId);
            if (!$Project->isInitialized())
                $Project = $this->Elca->getProject();

            $Validator = new ElcaValidator($this->Request);

            $BnbWater = BnbWater::findByProjectId($Project->getId());

            foreach (BnbWater::getColumnTypes() as $property => $type) {
                $setter = 'set' . ucfirst($property);
                $value = ElcaNumberFormat::fromString($this->Request->$property);

                switch ($property) {
                    case 'projectId':
                        break;

                    case 'niederschlagsmenge':
                        if ($Validator->assertNotEmpty('niederschlagsmenge', null, t('Bitte geben Sie eine Niederschlagsmenge an')))
                            $BnbWater->setNiederschlagsmenge(ElcaNumberFormat::fromString($this->Request->getNumeric('niederschlagsmenge')));
                        break;

                    case 'anzahlPersonen':
                        if ($Validator->assertNotEmpty('anzahlPersonen', null, t('Bitte geben Sie die Anzahl Mitarbeiter an')))
                            $BnbWater->setAnzahlPersonen($this->Request->getNumeric('anzahlPersonen'));
                        break;

                    default:
                        $BnbWater->$setter($value);
                        break;
                }
            }

            $BnbWater->update();

            /**
             * raise update event
             */
            $this->container->get(EventPublisher::class)
                ->publish(
                    new WaterUpdated(
                        $this->Elca->getProjectVariantId()
                    )
                );

            /**
             * Check validator and add error messages
             */
            if (!$Validator->isValid()) {
                foreach (array_unique($Validator->getErrors()) as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->defaultAction(false, !$Validator->isValid() ? $Validator : null);
        } elseif ($this->Request->has('cancel')) {
            $this->defaultAction(false);
        }
    }
    // End saveAction

}
// End WaterCtrl