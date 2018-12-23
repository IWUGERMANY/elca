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
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlMultiSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Elca\Db\ElcaConstrClassSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * ElcaAdminBenchmarkVersionView
 *
 * @package eLCA
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 */
class ElcaAdminBenchmarkVersionCommonView extends HtmlView
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

        $formId = 'adminBenchmarkVersionCommonForm';
        $form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveBenchmarkVersionCommon/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');

        if ($this->data) {
            $form->setDataObject($this->data);
            $form->add(new HtmlHiddenField('id', $this->benchmarkVersionId));
        }

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
        }
        $form->setRequest(FrontController::getInstance()->getRequest());


        $group = $form->add(new HtmlFormGroup(t('Grundeinstellungen')));
        $group->add(new ElcaHtmlFormElementLabel(t('Bauwerkszuordnung'), $select = new HtmlMultiSelectbox('constrClassIds')));
        $select->add(new HtmlSelectOption('Alle', null));
        foreach (ElcaConstrClassSet::find(null, ['ref_num' => 'ASC']) as $constrClass) {
            $select->add(new HtmlSelectOption($constrClass->getRefNum() .' '.$constrClass->getName(), $constrClass->getId()));
        }

        $group->add(new ElcaHtmlFormElementLabel(t('Nutzungsdauer'), new ElcaHtmlNumericInput('projectLifeTime'), null, t('Jahre')));

        /**
         * Add buttons
         */
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        $form->appendTo($Container);
    }
}
