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

namespace NaWoh\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlTag;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;

class NaWohWaterView extends HtmlView
{
    /**
     * Data
     */
    private $data;

    private $readOnly;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('water', 'naWoh');

        /**
         * Init arguments and options
         */
        $this->data = $this->get('data', new \stdClass());

        $this->readOnly = $this->get('readOnly');
    }
    // End init


    /**
     * Callback triggered after rendering the template
     *
     * @return void -
     */
    protected function afterRender()
    {
        $container = $this->getElementById('content');
        $formHolder = $this->getElementById('formHolder', true);

        $form = new HtmlForm('nawohWater', '/naWoh/water/save/');
        $form->addClass('clearfix highlight-changes');
        $form->setReadonly($this->readOnly);

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $form->setDataObject($this->data);
        $form->add(new HtmlHiddenField('projectId', Elca::getInstance()->getProjectId()));

        $this->appendInputs($form);

        if (!$this->readOnly) {
            $buttons = $form->add(new HtmlFormGroup(''));
            $buttons->addClass('buttons');
            $buttons->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        }

        $form->appendTo($formHolder);
    }

    // End afterRender


    private function appendInputs($form): void
    {
        $group = $form->add(new HtmlFormGroup('Wasserverbrauchsangaben'));
        $group->add(new HtmlTag('h5', t('Ihre Angaben'), ['class' => 'column inputs']));
        $group->add(new HtmlTag('h5', t('Nutzungen / Minuten pro Tag'), ['class' => 'column version']));
        $group->add(new HtmlTag('h5', t('Liter / Kopf / Tag'), ['class' => 'column result']));

        $radioGroup = $group->add(
            new ElcaHtmlFormElementLabel(t('Badewanne vorhanden'), new HtmlRadioGroup('mitBadewanne'))
        );
        $radioGroup->add(new HtmlRadiobox(t('nein'), false));
        $radioGroup->add(new HtmlRadiobox(t('ja'), true));

        $this->appendInput($group, t('Toilette voll'), 'toiletteVoll', 'Liter pro Nutzung');
        $this->appendInput($group, t('Toilette Spartaste'), 'toiletteSpartaste', 'Liter pro  Nutzung');
        $this->appendInput($group, t('Dusche'), 'dusche', 'Liter pro  Minute');

        $this->appendInput($group, t('Badewanne Gesamtfüllvolumen'), 'badewanneGesamt', 'Liter pro Nutzung', 'tub-input ' .($this->data->mitBadewanne ? '' : 'hidden'));

        $this->appendInput($group, t('Wasserhähne Bad'), 'wasserhaehneBad', 'Liter pro Minute');
        $this->appendInput($group, t('Wasserhähne Küche'), 'wasserhaehneKueche', 'Liter pro Minute');
        $this->appendInput($group, t('Waschmaschine'), 'waschmaschine', 'Liter pro Nutzung', null, true);
        $this->appendInput($group, t('Geschirrspüler'), 'geschirrspueler', 'Liter pro Nutzung', 'last', true);

        $this->appendResult($group, t('Summe'), 'result_total', 'Liter / Kopf / Tag');
    }

    private function appendInput(HtmlElement $container, $caption, $property, $unit, $cssClass = null, $readOnly = false)
    {
        $label = $container->add(
            new ElcaHtmlFormElementLabel(
                $caption,
                null,
                false,
                $unit
            )
        );

        if ($cssClass) {
            $label->addClass($cssClass);
        }

        $label->add(
            $input = new ElcaHtmlNumericInput($property)
        );
        $input->addClass('consumption');

        if ($readOnly) {
            $input->setReadonly(true, false);
        }

        $label->add(
            new HtmlTag('span', 'x', ['class' => 'column product'])
        );

        $baseValue = $label->add(
            new HtmlTag('span', null, ['class' => 'column version_base'])
        );
        $baseValue->add(new ElcaHtmlNumericText('version_'.$property));

        $label->add(
            new HtmlTag('span', '=', ['class' => 'column equals'])
        );

        $result = $label->add(
            new HtmlTag('span', null, ['class' => 'column result'])
        );
        $result->add(new ElcaHtmlNumericText('result_'.$property));
    }

    /**
     * Appends a row with text value
     *
     * @param HtmlElement $container
     * @param  string     $title
     * @param             $property
     * @param null        $refUnit
     * @param null        $value
     * @param int         $precision
     *
     * @return HtmlElement
     */
    private function appendResult(
        HtmlElement $container, $title, $property, $refUnit = null, $value = null, $precision = 2
    ) {
        $formSection = $container->add(
            new ElcaHtmlFormElementLabel($title, null, null, $refUnit)
        );
        $formSection->addClass('totals');
        $span = $formSection->add(new HtmlTag('span', null, ['class' => 'column result']));
        $span->add(new ElcaHtmlNumericText($property, $precision));
    }
}
