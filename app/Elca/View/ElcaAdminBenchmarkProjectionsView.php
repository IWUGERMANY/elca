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

namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProjectSet;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Admin view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaAdminBenchmarkProjectionsView extends HtmlView
{
    /**
     * Data
     */
    private $data;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_admin_benchmark_version');

        $this->data = $this->get('data', (object)['min' => [], 'avg' => [], 'max' => []]);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('tabContent');
        $this->addClass($container, 'admin-benchmark-constructions');

        $formId = 'adminBenchmarksForm';
        $form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveProjections/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');

        $form->setDataObject($this->data);

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $form->add(new HtmlHiddenField('id'));
        $this->appendBenchmarks($form);

        $form->appendTo($container);
    }
    // End beforeRender


    /**
     * Appends the benchmarks form
     *
     * @param  HtmlForm $form
     */
    protected function appendBenchmarks(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Durchschnittswerte aus Kostengruppe 300, 400 und 500')));

        $refConstrEffects = ElcaReportSet::findRefProjectConstructionEffects($this->data->id);

        $refProjectCount = ElcaProjectSet::dbCount(['is_reference' => true, 'benchmark_version_id' => $this->data->id]);

        if ($refProjectCount === 0 || $refProjectCount > 1) {
            $msg = t(
                'Die unter den Eingabefeldern angezeigten Werte wurden aus %refProjectCount% Projekten ermittelt',
                null,
                ['%refProjectCount%' => $refProjectCount]
            );
        } else {
            $msg = t('Die unter den Eingabefeldern angezeigten Werte wurden aus einem Projekt ermittelt');
        }

        $group->add(new HtmlTag('p', $msg));

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');
        $row->add(new HtmlTag('h5', t('Wirkungskategorie'), ['class' => 'hl-indicator']));
        $row->add(new HtmlTag('h5', t('Geringster Wert'), ['class' => 'hl-value min']));
        $row->add(new HtmlTag('h5', t('Mittlerer Wert'), ['class' => 'hl-value avg']));
        $row->add(new HtmlTag('h5', t('Höchster Wert'), ['class' => 'hl-value max']));

        $container = $group->add(new HtmlTag('div', null, ['id' => 'indicatorBenchmarks']));
        $ul        = $container->add(new HtmlTag('ul', null));

        $indicatorSet = ElcaIndicatorSet::findByProcessDbId(
            ElcaBenchmarkVersion::findById($this->data->id)->getProcessDbId()
        );

        foreach ($indicatorSet as $indicator) {
            $refConstrEffect = $refConstrEffects->search('indicator_id', $indicator->getId());

            if (!\is_object($refConstrEffect)) {
                $refConstrEffect = new \stdClass();
            }

            $key = $indicator->getIdent();

            $li = $ul->add(new HtmlTag('li', null, ['class' => 'indicator-values']));
            $label = $li->add(
                new ElcaHtmlFormElementLabel(
                    t($indicator->getName()),
                    null,
                    false,
                    null,
                    t($indicator->getDescription())
                )
            );

            foreach (['min', 'avg', 'max'] as $property) {
                $inputContainer = $label->add(new HtmlTag('span', null, ['class' => 'input-container']));
                $input          = $inputContainer->add(
                    new ElcaHtmlNumericInput($property.'['.$key.']')
                ); //, null, false, $ScientificNumberFormatConverter));
                $inputContainer->add(
                    new HtmlTag(
                        'span',
                        '',
                        ['class' => 'use-computed-value', 'title' => t('Berechneten Wert übernehmen')]
                    )
                );

                if (!isset($refConstrEffect->$property)) {
                    $refConstrEffect->$property = 0;
                }

                if ($refConstrEffect->$property) {
                    $precision = round(log10($refConstrEffect->$property));
                    $precision = $precision < 0 ? abs($precision) + 2 : 2;
                } else {
                    $precision = 0;
                }

                $input->setAttribute(
                    'data-computed-value',
                    ElcaNumberFormat::toString($refConstrEffect->$property, $precision)
                );
                $inputContainer->add(
                    new HtmlTag(
                        'span',
                        ElcaNumberFormat::toString($refConstrEffect->$property, 2, false, ',', true),
                        ['class' => 'computed-value']
                    )
                );
            }

            $label->add(new HtmlTag('span', t($indicator->getUnit()), ['class' => 'unit']));
        }

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern')));
    }
}
