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
namespace Elca\View\Admin\Benchmark;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Elca\Db\ElcaBenchmarkLifeCycleUsageSpecification;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaLifeCycleSet;
use Elca\Model\Process\Module;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * ElcaAdminBenchmarkVersionView
 *
 * @package eLCA
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 */
class ElcaAdminBenchmarkVersionComputationView extends HtmlView
{
    /**
     * @var int $benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * @var object $data
     */
    private $data;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        $this->setTplName('elca_admin_benchmark_version');

        parent::init($args);

        $this->benchmarkVersionId = $this->get('benchmarkVersionId');

        $this->data = $this->get('data', new \stdClass());
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        /**
         * Add Container
         */
        $Container = $this->getElementById('tabContent');

        $Container->appendChild($this->getP(t('Konfigurieren Sie, welche Lebenszyklusmodule in die Berechnung und Bewertung einfließen.')));

        $formId = 'adminBenchmarkVersionForm';
        $Form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveBenchmarkVersionComputation/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes life-cycle-usages');

        if ($this->data) {
            $Form->setDataObject($this->data);
            $Form->add(new HtmlHiddenField('id', $this->benchmarkVersionId));
        }

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
        }
        $Form->setRequest(FrontController::getInstance()->getRequest());

        $construction = array_merge(
            ElcaBenchmarkLifeCycleUsageSpecification::$constrDefaults,
            ElcaBenchmarkLifeCycleUsageSpecification::$energyDefaults
        );

        $maintenance = ElcaBenchmarkLifeCycleUsageSpecification::$maintDefaults;

        $constrGroup = $Form->add(new HtmlFormGroup(t('Konstruktion und Endenergiebilanz') ));
        $maintenanceGroup = $Form->add(new HtmlFormGroup(t('Instandhaltung')));

        $version    = ElcaBenchmarkVersion::findById($this->benchmarkVersionId);
        $lifeCycles = ElcaLifeCycleSet::findByProcessDbId($version->getProcessDbId(), ['p_order' => 'ASC']);

        // iterate over lifecycles to keep order
        foreach ($lifeCycles as $lifeCycle) {
            $ident = $lifeCycle->getIdent();

            if (\in_array($ident, [Module::A1, Module::A2, Module::A3], true)) {
                continue;
            }

            if (isset($construction[$ident])) {
                $constrGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t($lifeCycle->getName()),
                        new HtmlCheckbox('construction['.$lifeCycle->getIdent().']')
                    )
                );
            }
            if (isset($maintenance[$lifeCycle->getIdent()])) {
                $maintenanceGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t($lifeCycle->getName()),
                        new HtmlCheckbox('maintenance['.$lifeCycle->getIdent().']')
                    )
                );
            }
        }

        /**
         * Add buttons
         */
        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        $Form->appendTo($Container);
    }
}
