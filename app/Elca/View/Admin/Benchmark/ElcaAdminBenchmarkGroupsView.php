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
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlMultiSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaBenchmarkGroup;
use Elca\Db\ElcaBenchmarkGroupSet;
use Elca\Db\ElcaBenchmarkGroupThreshold;
use Elca\Db\ElcaBenchmarkGroupThresholdSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicatorSet;
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
class ElcaAdminBenchmarkGroupsView extends HtmlView
{
    /**
     * @var int $benchmarkVersionId
     */
    private $benchmarkVersionId;

    /**
     * @var object $data
     */
    private $data;

    private $processDbId;

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
        $this->processDbId = ElcaBenchmarkVersion::findById($this->benchmarkVersionId)->getProcessDbId();

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
        $container = $this->getElementById('tabContent');
        $this->addClass($container, 'benchmark-groups');

        $container->appendChild($this->getP(t('Konfigurieren Sie hier die Bewertungskategorien.')));

        $formId = 'adminBenchmarkGroupsForm';
        $form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveBenchmarkVersionGroups/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes benchmark-groups');

        if ($this->data) {
            $form->setDataObject($this->data);
            $form->add(new HtmlHiddenField('id', $this->benchmarkVersionId));
        }

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
        }
        $form->setRequest(FrontController::getInstance()->getRequest());

        $this->appendGroups($form);
        $form->appendTo($container);
    }

    protected function appendGroups(HtmlForm $Form)
    {
        $group = $Form->add(new HtmlFormGroup(t('Bewertungskategorien')));

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');
        $row->add(new HtmlTag('h5', t('Name'), ['class' => 'hl-name']));
        $row->add(new HtmlTag('h5', t('Indikatoren'), ['class' => 'hl-indicators']));
        $row->add(new HtmlTag('h5', t('Punktwert'), ['class' => 'hl-score']));
        $row->add(new HtmlTag('h5', t('Bewertung'), ['class' => 'hl-caption']));

        $container = $group->add(new HtmlTag('div', null, ['id' => 'lcaGroups']));
        $ul = $container->add(new HtmlTag('ul', null));

        foreach (ElcaBenchmarkGroupSet::findByBenchmarkVersionId($this->benchmarkVersionId, ['id' => 'ASC']) as $benchmarkGroup) {
            $li = $ul->add(new HtmlTag('li', null, ['id' => 'group-'.$benchmarkGroup->getId(), 'class' => 'group']));
            $this->appendGroup($li, $benchmarkGroup);
        }

        if ($this->get('addNewGroup', false)) {
            $li = $ul->add(new HtmlTag('li', null, ['class' => 'group']));
            $this->appendGroup($li);
            $container->addClass('new-group');
        }

        $buttonGroup = $Form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('addGroup', t('Neue Gruppe hinzufügen')));
        $buttonGroup->add(new ElcaHtmlSubmitButton('saveGroups', t('Speichern'), true));
    }
    // End appendBenchmarks


    /**
     * @param HtmlElement          $container
     * @param ElcaBenchmarkGroup $group
     */
    private function appendGroup(HtmlElement $container, ElcaBenchmarkGroup $group = null)
    {
        $key = null !== $group ? $group->getId() : 'new';

        $container->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('name['.$key.']')));
        $container->add(new ElcaHtmlFormElementLabel('', $multiSelect = new HtmlMultiSelectbox('indicators['.$key.']')));

        foreach (ElcaIndicatorSet::findWithPetByProcessDbId($this->processDbId) as $indicator) {
            $multiSelect->add(new HtmlSelectOption($indicator->getName(), $indicator->getId()));
        }

        $this->appendGroupThresholds($container, $key);
    }


    /**
     * @param HtmlElement $container
     * @param mixed       $groupKey
     */
    private function appendGroupThresholds(HtmlElement $container, $groupKey)
    {
        $ul = $container->add(new HtmlTag('ul', null, ['id' => 'group-threshold-'.$groupKey, 'class' => 'group-threshold']));

        if ($this->get('relId')) {
            $ul->setAttribute('data-rel-id', $this->get('relId'));
        }

        if (is_numeric($groupKey)) {
            foreach (ElcaBenchmarkGroupThresholdSet::findByGroupId($groupKey, ['score' => 'DESC']) as $groupThreshold) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'group-threshold']));
                $this->appendGroupThreshold($li, $groupKey, $groupThreshold);
            }

            if ($this->get('addNewGroupThreshold', false) && $this->get('groupId') == $groupKey) {
                $li = $ul->add(new HtmlTag('li', null, ['class' => 'group-threshold new']));
                $this->appendGroupThreshold($li, $groupKey);
            }
            else {
                $benchmarkVersionId = ElcaBenchmarkGroup::findById($groupKey)->getBenchmarkVersionId();

                /**
                 * Add link only on last row
                 */
                $li->add(
                    $Link = new HtmlLink(
                        '+ '.t('Bewertung'),
                        Url::factory(
                            '/admin/benchmarks/addGroupThreshold/',
                            [
                                'id'      => $benchmarkVersionId,
                                'groupId' => $groupKey,
                            ]
                        )
                    )
                )
                   ->addClass('function-link add-link');
                $Link->setAttribute('title', t('Bewertung hinzufügen'));
            }
            $li->addClass('last');
        }
        else {
            $li = $ul->add(new HtmlTag('li', null, ['class' => 'group-threshold new last']));
            $this->appendGroupThreshold($li, $groupKey);
        }
    }
    // End appendTransportMeans


    /**
     * @param HtmlElement              $container
     * @param                          $groupKey
     * @param ElcaBenchmarkGroupThreshold $groupThreshold
     */
    private function appendGroupThreshold(HtmlElement $container, $groupKey, ElcaBenchmarkGroupThreshold $groupThreshold = null)
    {
        $key = null !== $groupThreshold ? $groupKey.'-'.$groupThreshold->getId() : $groupKey.'-new';

        $container->setAttribute('id', 'group-threshold-'.$key);

        $container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('score['.$key.']')));
        $container->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('caption['.$key.']')));

        if ((isset($this->data->score[$key]) && $this->data->score[$key]) ||
            (($validator = $this->get('validator')) && $validator->getValue('thresholds['. $key.']'))) {

            if (is_numeric($groupKey) && $groupThreshold instanceof ElcaBenchmarkGroupThreshold) {
                $container->add(
                    $Link = new HtmlLink(
                        t('Löschen'),
                        Url::factory('/admin/benchmarks/deleteGroupThreshold/', [
                            'id' => $groupThreshold->getGroup()->getBenchmarkVersionId(),
                            'thresholdId' => $groupThreshold->getId()
                        ])
                    )
                )
                          ->addClass('function-link delete-link');
                $Link->setAttribute('title', t('Löschen'));
            }
            else {
                $container->add($Link = new HtmlLink(t('Abbrechen'), Url::factory('/admin/benchmarks/editVersionGroups/', ['id' => $this->benchmarkVersionId])))
                          ->addClass('function-link cancel-link');
                $Link->setAttribute('title', t('Abbrechen'));
            }
        } else {
            $container->add($Link = new HtmlLink(t('Abbrechen'), Url::factory('/admin/benchmarks/editVersionGroups/', ['id' => $this->benchmarkVersionId])))
                      ->addClass('function-link cancel-link');
            $Link->setAttribute('title', t('Abbrechen'));

        }
    }
}
