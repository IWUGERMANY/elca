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

namespace Lcc\View\Admin;

use Beibob\Blibs\Config;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlTag;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

class LccAdminBenchmarkVersionLccView extends HtmlView
{
    private $benchmarkVersionId;

    private $data;


    protected function init(array $args = [])
    {
        parent::init($args);

        $this->benchmarkVersionId = $this->get('benchmarkVersionId');
        $this->data = $this->get('data', new \stdClass());
    }

    protected function beforeRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'elca-admin-benchmark-version tab-lcc active']));

        $formId = 'adminBenchmarkVersionLccForm';
        $form   = new HtmlForm($formId, '/lcc/admin/benchmarks/saveLcc/');
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

        $this->appendThresholds($form);

        /**
         * Add buttons
         */
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        $form->appendTo($container);
    }

    private function appendThresholds(HtmlForm $form)
    {
        $h3 = $form->add(
            new HtmlTag(
                'h3',
                t('Schwellenwerte')
            )
        );

        $link = $h3->add(new HtmlLink(t('Alle Eingabefelder leeren')));
        $link->addClass('clear-fields no-xhr');
        $link->setAttribute('rel', 'clearFields');

        $form->add(
            new HtmlTag(
                'p',
                t(
                    'Es müssen mindestens zwei Schwellenwerte - das Maximum und das Minimum - pro Zeile spezifiziert werden. Zwischen den Werten wird interpoliert.'
                )
            )
        );

        $this->appendThresholdRows($form, 1, 100, 10);
        $this->appendThresholdRows($form, 2, 100, 10);
    }


    private function appendThresholdRows(
        HtmlForm $form, $category, $scoreMax = 100, $scoreDecrement = 10)
    {
        if (!$this->data || !isset($this->benchmarkVersionId)) {
            return;
        }

        $property = 'category'. $category;

        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('benchmark-thresholds-group');

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');

        for ($score = $scoreMax; $score > 0; $score -= $scoreDecrement) {
            $row->add(new HtmlTag('h5', $score, ['class' => 'hl-benchmark']));
        }
        $fifthScoreDecrement = $scoreDecrement / 5;
        for ($score = $scoreDecrement - $fifthScoreDecrement; $score > 0; $score -= $fifthScoreDecrement) {
            $row->add(new HtmlTag('h5', $score, ['class' => 'hl-benchmark']));
        }

        $ul = $group->add(new HtmlTag('ul', null));

        $validator = $this->get('validator');

        $li    = $ul->add(new HtmlTag('li', null, ['class' => 'indicator-values']));
        $label = $li->add(
            new ElcaHtmlFormElementLabel(
                $category === 1 ? t('Ohne Sonderbed.') : t('Mit Sonderbed.'),
                null,
                false,
                '€ / m²BGF'
            )
        );

        if ($validator && $validator->hasError($property)) {
            $label->addClass('error');
        }

        for ($score = $scoreMax; $score > 0; $score -= $scoreDecrement) {
            $this->appendInputField($label, $property, $score);
        }
        for ($score = $scoreDecrement - $fifthScoreDecrement; $score > 0; $score -= $fifthScoreDecrement) {
            $this->appendInputField($label, $property, $score);
        }
    }

    private function appendInputField(HtmlElement $label, $property, $score, Config $bnbDefaults = null)
    {
        $input = new ElcaHtmlNumericInput($property.'['.$score.']');
        $input->setPrecision(2);

        $label->add($input);

        if (null === $bnbDefaults) {
            return;
        }

        if (isset($bnbDefaults->$property, $bnbDefaults->$property->$score)) {
            $input->setAttribute('data-default', ElcaNumberFormat::toString($bnbDefaults->$property->$score));
        }
    }
}
