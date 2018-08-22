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
use Elca\Db\ElcaCacheReferenceProjectSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProjectSet;
use Elca\Db\ElcaReportSet;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;

/**
 * Admin view
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaAdminReferenceProjectsConstructionView extends HtmlView
{
    /**
     * @var object
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

        $this->data = $this->get(
            'data',
            (object)[
                'id' => null,
                'min'                => [],
                'avg'                => [],
                'max'                => [],
            ]
        );
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
        $this->addClass($container, 'admin-reference-projects');

        $formId = 'adminBenchmarksForm';
        $form   = new HtmlForm($formId, '/elca/admin/benchmarks/saveReferenceProjects/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');

        $form->setDataObject($this->data);

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $form->add(new HtmlHiddenField('id'));
        $this->appendIndicators($form);

        $form->appendTo($container);
    }
    // End beforeRender


    /**
     * Appends the benchmarks form
     *
     * @param  HtmlForm $form
     */
    protected function appendIndicators(HtmlForm $form)
    {
        $group = $form->add(
            new HtmlFormGroup(t('Grenz- und Durchschnittswerte der Konstruktion für Referenzprojekte'))
        );

        $refProjectCount = ElcaProjectSet::dbCount(['is_reference' => true, 'benchmark_version_id' => $this->data->id]);
        $refTotalConstrEffects = ElcaReportSet::findRefProjectConstructionEffects($this->data->id);

        if ($refProjectCount === 0 || $refProjectCount > 1) {
            $msg = t(
                'Die unter den Eingabefeldern angezeigten Werte wurden aus %refProjectCount% Projekten ermittelt.',
                null,
                ['%refProjectCount%' => $refProjectCount]
            );
        } else {
            $msg = t('Die unter den Eingabefeldern angezeigten Werte wurden aus einem Projekt ermittelt.');
        }

        $group->add(new HtmlTag('p', $msg));
        $group->add(new HtmlTag('p', 'Ein tiefgestellter und abgesetzter Wert zeigt den Originalwert an, falls die Werte einer Wirkungskategorie angepasst wurden.'));

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');
        $row->add(new HtmlTag('h5', t('Wirkungskategorie'), ['class' => 'hl-indicator']));
        $row->add(new HtmlTag('h5', t('Abweichung % min'), ['class' => 'hl-value min']));
        $row->add(new HtmlTag('h5', t('Abweichung % ∅'), ['class' => 'hl-value avg']));
        $row->add(new HtmlTag('h5', t('Abweichung % max'), ['class' => 'hl-value max']));

        $container    = $group->add(new HtmlTag('div', null, ['id' => 'indicatorBenchmarks']));
        $ul           = $container->add(new HtmlTag('ul', null));
        $indicatorSet = ElcaIndicatorSet::findByProcessDbId(
            ElcaBenchmarkVersion::findById($this->data->id)->getProcessDbId()
        );

        $percentageConverter = new ElcaNumberFormatConverter(null, true);
        foreach ($indicatorSet as $indicator) {
            $key = $indicator->getIdent();

            $li    = $ul->add(new HtmlTag('li', null, ['class' => 'indicator-values']));
            $label = $li->add(
                new ElcaHtmlFormElementLabel(
                    t($indicator->getName()),
                    null,
                    false,
                    t($indicator->getUnit().'/ m²NGF・a'),
                    t($indicator->getDescription())
                )
            );

            foreach (['min', 'avg', 'max'] as $property) {
                $inputContainer = $label->add(new HtmlTag('span', null, ['class' => 'input-container '.$property]));
                $inputContainer->add(
                    new ElcaHtmlNumericInput($property.'['.$key.']', null, false, $percentageConverter)
                );
            }

            $label->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true))->addClass('save-button');

            $referenceProjectSet = ElcaCacheReferenceProjectSet::findByBenchmarkVersionIdAndIndicatorId(
                $this->data->id,
                $indicator->getId(),
                [],
                ['din_code' => 'ASC']
            );

            $refTotalConstrEffect = $refTotalConstrEffects->search('indicator_id', $indicator->getId());

            $dinCodesUl = $li->add(new HtmlTag('ul', null, ['class' => 'din-codes']));

            $level1AvgTotals = [];
            foreach ($referenceProjectSet as $referenceProject) {
                $dinCode = $referenceProject->din_code;

                if ($dinCode % 100 === 0) {
                    $level1AvgTotals[$dinCode] = $referenceProject->avg;
                }

                $dinCodeLi   = $dinCodesUl->add(new HtmlTag('li', null, ['class' => 'din-code-row']));
                $elementType = ElcaElementType::findByNodeId($referenceProject->element_type_node_id);
                $dinCodeLi->add(
                    $dinCodeName = new HtmlTag(
                        'span',
                        $elementType->getDinCode().' '.$elementType->getName(),
                        ['class' => 'din-code']
                    )
                );

                $dinCodeName->add(
                    new HtmlTag(
                        'em',
                        sprintf(
                            '%s / %s',
                            ElcaNumberFormat::formatQuantity(
                                $referenceProject->avg / $level1AvgTotals[(int)($dinCode / 100) * 100],
                                '%',
                                1,
                                true
                            ),
                            ElcaNumberFormat::formatQuantity(
                                $referenceProject->avg / $refTotalConstrEffect->avg,
                                '%',
                                1,
                                true
                            )
                        ),
                        ['class' => 'ratio']
                    )
                );

                foreach (['min', 'avg', 'max'] as $property) {
                    $value = $referenceProject->$property;
                    $modifiedValue = null;
                    $modificator = $this->data->$property[$key] ?? 1;
                    if (1.0 !== (float)$modificator) {
                        $modifiedValue = $value * $modificator;
                    }

                    $span = $dinCodeLi->add(
                        new HtmlTag(
                            'span',
                            ElcaNumberFormat::toString($modifiedValue ?? $value, 2, false, '?', true),
                            [
                                'class' => 'indicator-value din-code-ref-value-'.$property,
                                'title' => $modifiedValue ? t('Angepasster Wert') : t('Berechneter Wert')
                            ]
                        )
                    );

                    if ($modifiedValue) {
                        $span->add(
                            new HtmlTag(
                                'small',
                                ElcaNumberFormat::toString($referenceProject->$property, 2, false, '?', true),
                                ['title' => t('Berechneter Wert')]
                            )
                        );
                    }
                }
            }
        }
    }
}
