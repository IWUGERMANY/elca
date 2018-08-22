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

namespace Elca\View\ProjectData;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\IdFactory;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectLifeCycleUsage;
use Elca\Model\Process\Module;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

class ProjectLifeCycleUsageView extends HtmlView
{
    private $data;

    private $projectId;

    private $readOnly;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        $this->setTplName('elca_project_life_cycle_usage');

        parent::init($args);

        $this->projectId = $this->get('projectId');
        $this->data = $this->get('data', new \stdClass);
        $this->readOnly = $this->get('readOnly');
    }

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        /**
         * Add Container
         */
        $container = $this->getElementById('content');
        $container->appendChild($this->getP(t('Die folgenden Lebenszyklusmodule werden in die jeweilige Berechnung und Bewertung einbezogen. Diese Einstellung wird für alle Varianten im Projekt angewandt.')));
        $notice = $container->appendChild($this->getP(t('Die Verrechnung von Modul D ist nicht Normkonform!'), ['id' => 'nonComplianceNotice', 'class' => 'warning']));

        if (!$this->get('hasRecPot', false)) {
            $this->addClass($notice, 'hidden');
        }

        $project = ElcaProject::findById($this->projectId);

        $formId = 'projectLifeCycleUsageForm';
        $form   = new HtmlForm($formId, '/elca/project-data/life-cycle-usage/save');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes life-cycle-usages');

        $form->setReadonly($this->readOnly);
        $form->add(new HtmlHiddenField('id', $this->projectId));


        if ($this->data) {
            $form->setDataObject($this->data);
        }
        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
        }

        if (null !== $project->getBenchmarkVersionId()) {
            $form->setReadonly();
        }

        $form->setRequest(FrontController::getInstance()->getRequest());

        $construction = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults
        );

        $maintenance = ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults;

        $constrGroup = $form->add(new HtmlFormGroup(t('Konstruktion und Endenergiebilanz') ));
        $maintenanceGroup = $form->add(new HtmlFormGroup(t('Instandhaltung')));

        $lifeCycles = ElcaLifeCycleSet::findByProcessDbId($project->getProcessDbId(), ['p_order' => 'ASC']);
        $allLcIdents = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults
        );

        // iterate over lifecycles to keep order
        foreach ($lifeCycles as $lifeCycle) {
            $ident = $lifeCycle->getIdent();

            if (!isset($allLcIdents[$ident]) ||
                \in_array($ident, [Module::A1, Module::A2, Module::A3], true)) {
                continue;
            }

            if (isset($construction[$ident])) {
                $label = $constrGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t($lifeCycle->getName())
                    )
                );
                $label->addClass($ident);

                $label->add(
                    new HtmlCheckbox(
                        'construction['.$ident.']',
                        null, '',
                        $this->isReadOnly($ident, $this->data->construction)
                    )
                );
            }
            if (isset($maintenance[$ident])) {
                $label = $maintenanceGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t($lifeCycle->getName())
                    )
                );
                $label->addClass($ident);

                $label->add(
                    new HtmlCheckbox('maintenance['.$ident.']', null, '', $this->isReadOnly($ident, $this->data->maintenance))
                );
            }
        }

        /**
         * Add buttons
         */
        if (!$this->readOnly && null === $project->getBenchmarkVersionId()) {
            $buttonGroup = $form->add(new HtmlFormGroup(''));
            $buttonGroup->addClass('buttons');
            $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern und neu berechnen'), false));
        }

        $form->appendTo($container);
    }

    private function isReadOnly($ident, $settings)
    {
        switch ($ident) {
            case ElcaLifeCycle::IDENT_A13:
                if ($settings[ElcaLifeCycle::IDENT_A13] ||
                    ($settings[ElcaLifeCycle::IDENT_A1] && $settings[ElcaLifeCycle::IDENT_A2] && $settings[ElcaLifeCycle::IDENT_A3]) ||
                    (!$settings[ElcaLifeCycle::IDENT_A1] && !$settings[ElcaLifeCycle::IDENT_A2] && !$settings[ElcaLifeCycle::IDENT_A3])) {
                    return false;
                }
                return true;

            case ElcaLifeCycle::IDENT_A1:
            case ElcaLifeCycle::IDENT_A2:
            case ElcaLifeCycle::IDENT_A3:
                return $settings[ElcaLifeCycle::IDENT_A13];
        }

        return false;
    }
}
