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
namespace Bnb\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTag;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\HtmlNumericTextWithUnit;

/**
 * Rechenhilfe 1.2.3 Wasser
 *
 * @package   bnb
 * @author    Tobias Lode <tobias@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class BnbWaterView extends HtmlView
{
    /**
     * Data
     */
    private $Data;

    /**
     * toggle states
     */
    private $toggleStates = [];

    private $readOnly;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->Data = $this->get('Data', new \stdClass());

        /**
         * initial toggle states
         */
        $this->toggleStates = $this->get('toggleStates', []);

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
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'bnb-water']));
        $projectId = Elca::getInstance()->getProjectId();

        $Form = new HtmlForm('bnbWater', '/bnb/water/save/');
        $Form->addClass('clearfix highlight-changes');
        $Form->setReadonly($this->readOnly);

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        $Form->setDataObject($this->Data);
        $Form->add(new HtmlHiddenField('projectId', Elca::getInstance()->getProjectId()));

        $this->appendGroup($Form, t('Allgemein'), [$this, 'appendParameters']);
        $this->appendGroup($Form, t('Sanitärobjekte'), [$this, 'appendSanitary']);
        $this->appendGroup($Form, t('Reinigung Böden'), [$this, 'appendCleaning']);
        $this->appendGroup($Form, t('Niederschlag'), [$this, 'appendPercipitation']);
        $this->appendGroup($Form, t('Bewertung'), [$this, 'appendResults'], false);

        $Form->appendTo($Container);
    }
    // End afterRender


    /**
     * Appends a section
     *
     * @param HtmlElement $group
     * @param HtmlElement $Buttons
     *
     * @return HtmlElement
     */
    protected function appendParameters(HtmlElement $group, HtmlElement $Buttons)
    {
        $group->addClass('building');
        $group->removeClass('collapsible');

        $label = $group->add(new ElcaHtmlFormElementLabel(t('NGF'), null, false, 'm2'));
        $span = $label->add(new HtmlTag('span', null, ['class' => 'input-column']));
        $span->add(new ElcaHtmlNumericText('ngf', 2));


        $this->appendInput($group, 'niederschlagsmenge', t('Jährliche Niederschlagsmenge am Standort'), true, 'mm');
        $this->appendInput($group, 'anzahlPersonen', t('Anzahl Mitarbeiter'), true);
    }
    // End appendBuilding


    /**
     * Appends a section
     *
     * @param HtmlElement $group
     * @param HtmlElement $Buttons
     *
     * @return HtmlElement
     */
    protected function appendSanitary(HtmlElement $group, HtmlElement $Buttons)
    {
        $group->addClass('sanitary');

        $this->appendInput($group, 'sanitaerWaschtisch', t('Waschtischarmatur'), false, 'Liter', '45 sec/d');
        $this->appendInput($group, 'sanitaerWcSpar', t('WC-Spartaste'), false, 'Liter', '1 Spülung/d');
        $this->appendInput($group, 'sanitaerWc', t('WC'), false, 'Liter', '1 Spülung/d');
        $this->appendInput($group, 'sanitaerUrinal', t('Urinal'), false, 'Liter', '1 Spülung/d');
        $this->appendInput($group, 'sanitaerDusche', t('Armatur Dusche'), false, 'Liter', '30 sec/d');
        $this->appendInput($group, 'sanitaerTeekueche', t('Armatur Teeküche'), false, 'Liter', '20 sec/d');

        // append totals for this group

        $totals = $group->add(new HtmlTag('div', null, ['class' => 'totals']));

        $this->appendResult($totals, t('Summe rechn. Wasserbedarf je Mitarbeiter pro Tag'), 'sum[wasserbedarfProPersonTag]', 'Liter', 2);
        $this->appendResult($totals, t('Rechn. Wasserbedarf aller Mitarbeiter pro Jahr'), 'sum[wasserbedarfProJahr]', 'm3', 2);
    }
    // End appendSanitary


    /**
     * Appends a section
     *
     * @param HtmlElement $group
     * @param HtmlElement $Buttons
     *
     * @return HtmlElement
     */
    protected function appendCleaning(HtmlElement $group, HtmlElement $Buttons)
    {
        $group->addClass('cleaning');

        $this->appendInput($group, 'reinigungSanitaer', t('Sanitärbereiche'), false, 'm2', '250 '.t('Reinigungen pro Jahr'));
        $this->appendInput($group, 'reinigungLobby', t('Lobby'), false, 'm2', '250 '.t('Reinigungen pro Jahr'));
        $this->appendInput($group, 'reinigungVerkehrsflaeche', t('Verkehrsfläche'), false, 'm2', '150 '.t('Reinigungen pro Jahr'));
        $this->appendInput($group, 'reinigungBuero', t('Büros'), false, 'm2', '100 '.t('Reinigungen pro Jahr'));
        $this->appendInput($group, 'reinigungKeller', t('Keller, Nebenräume'), false, 'm2', '12 '.t('Reinigungen pro Jahr'));

        // append totals for this group
        $totals = $group->add(new HtmlTag('div', null, ['class' => 'totals']));
        $this->appendResult($totals,t('Summe Wasserbedarf zur Bodenreinigung'), 'sum[wasserbedarfBodenreinigung]', 'm3', 2);
    }
    // End appendCleaning


    /**
     * Appends a section
     *
     * @param HtmlElement $group
     * @param HtmlElement $Buttons
     *
     * @return HtmlElement
     */
    protected function appendPercipitation(HtmlElement $group, HtmlElement $Buttons)
    {
        $group->addClass('cleaning');

        $group->add(new HtmlTag('h4', t('Ermittlung der zu berücksichtigen Niederschlagsmenge')));
        for ($i = 1; $i <= 4; $i++) {
            $this->appendInput($group, 'dach'.$i.'Flaeche', t('Fläche Dach').' '.$i, false, 'm2');
            $this->appendInput($group, 'dach'.$i.'Ertragsbeiwert', t('Ertragsbeiwert Dach').' '.$i, false);
        }

        // append totals for this group
        $this->appendResult($group,t('Anfallendes Niederschlagswasser Dächer'), 'sum[niederschlagDaecher]', 'm3', 2);

        $group->add(new HtmlTag('h4', t('Niederschlags- und Brauchwasserbehandlung')));
        $this->appendInput($group, 'niederschlagVersickert', t('Menge des auf dem Grundstück versickerten Niederschlagswassers'), false, 'm3');
        $this->appendInput($group, 'niederschlagGenutzt', t('Menge des genutzten Niederschlagswassers (mit Wandlung in Abwasser, z.B. Substitution Wischwasser)'), false, 'm3');
        $this->appendInput($group, 'niederschlagGenutztOhneWandlung', t('Menge des genutzten Niederschlagswassers (ohne Wandlung in Abwasser, z.B. adiabate Kühlung)'), false, 'm3');
        $this->appendInput($group, 'niederschlagKanalisation', t('Menge des in die Kanalisation direkt abgeführten Niederschlagwassers (direkte Einspeisung z.B. Überschuss'), false, 'm3');
        $check = $this->appendResult($group, t('Kontrolle: Anfallendes = genutztes Niederschlagwasser'), 'sum[niederschlagGenutztGesamt]', 'm3', null, 2, 'sum[niederschlagDaecher]');

        if ((float)$this->Data->sum['niederschlagGenutztGesamt'] !== (float)$this->Data->sum['niederschlagDaecher']) {
            $check->addClass('not-equal');
        }

        $this->appendInput($group, 'brauchwasser', t('Menge des genutzten Brauchwassers'), false, 'm3');
        $this->appendInput($group, 'brauchwasserGereinigt', t('Menge des auf dem Grundstück greinigten Brauchwassers'), false, 'm3');
    }
    // End appendCleaning


    /**
     * Appends a section
     *
     * @param HtmlElement $Group
     * @param HtmlElement $Buttons
     *
     * @return HtmlElement
     */
    protected function appendResults(HtmlElement $Group, HtmlElement $Buttons)
    {
        $Group->addClass('results');
        $Group->removeClass('collapsible');

        $Group->add(new HtmlTag('h4', t('Frischwasserbedarf pro Jahr')));
        $this->appendResult($Group, t('Frischwasserbedarf Mitarbeiter'), 'sum[wasserbedarfProJahr]', 'm3');
        $this->appendResult($Group, t('Frischwasserbedarf Fussbodenreinigung'), 'sum[wasserbedarfBodenreinigung]', 'm3');
        $this->appendResult($Group, t('Menge des genutzten Niederschlagswassers'), 'niederschlagGenutztNeg', 'm3', $this->Data->niederschlagGenutzt * -1);
        $this->appendResult($Group, t('Menge des genutzten Brauchwassers'), 'brauchwasserNeg', 'm3', $this->Data->brauchwasser * -1);
        $this->appendResult($Group, t('Gesamtfrischwasserbedarf'), 'sum[gesamtfrischwasserbedarf]', 'm3')->addClass('sum');

        $Group->add(new HtmlTag('h4', t('Abwasseraufkommen pro Jahr')));
        $this->appendResult($Group, t('Abwasseraufkommen Mitarbeiter'), 'sum[wasserbedarfProJahr]', 'm3');
        $this->appendResult($Group, t('Abwasseraufkommen Fussbodenreinigung'), 'sum[wasserbedarfBodenreinigung]', 'm3');
        $this->appendResult($Group, t('Menge des in die Kanalisation direkt abgeführten Niederschlagwassers'), 'niederschlagKanalisation', 'm3');
        $this->appendResult($Group, t('Menge des genutzten Brauchwassers'), 'brauchwasserNeg', 'm3', $this->Data->brauchwasser * -1);
        $this->appendResult($Group, t('Menge des auf dem Grundstück gereinigten Brauchwassers'), 'brauchwasserGereinigtNeg', 'm3', $this->Data->brauchwasserGereinigt * -1);
        $this->appendResult($Group, t('Gesamtabwasseraufkommen'), 'sum[gesamtabwasseraufkommen]', 'm3')->addClass('sum');

        $Group->add(new HtmlTag('h4', t('Wassergebrauchskennwert')));
        $this->appendResult($Group, t('Wassergebrauchskennwert'), 'sum[wassergebrauchskennwert]', 'm3')->addClass('sum');


        $Group->add(new HtmlTag('h4', t('Grenzwerte')));
        $this->appendResult($Group, t('Wasserbedarf Mitarbeiter'), 'sum[grenzwertWasserProMitarbeiter]', 'm3');
        $this->appendResult($Group, t('Abwasseraufkommen Mitarbeiter'), 'sum[grenzwertWasserProMitarbeiter]', 'm3');
        $this->appendResult($Group, t('Wasserbedarf Fussbodenreinigung'), 'sum[grenzwertWasserFussboden]', 'm3');
        $this->appendResult($Group, t('Abwasseraufkommen Fussbodenreinigung'), 'sum[grenzwertWasserFussboden]', 'm3');
        $this->appendResult($Group, t('Abwasseraufkommen anfallendes Niederschlagswassers'), 'sum[grenzwertNiederschlag]', 'm3');
        $this->appendResult($Group, t('Grenzwert Gesamt'), 'sum[grenzwertGesamt]', 'm3')->addClass('sum');

        $Totals = $Group->add(new HtmlTag('div', null, ['class' => 'totals']));
        $this->appendResult($Totals, t('Verhältnis Wassergebrauchskennwert / Grenzwert'), 'sum[verhaeltnis]', null, null, 5);
        $this->appendResult($Totals, t('Punkte Kriterium 1.2.3'), 'sum[punkte]', null, null, 0)->addClass('rating');
    }
    // End appendBuilding


    /**
     * Appends a expand/collapsable group to the form
     *
     * @param  HtmlForm $Form
     * @param  string   $title
     * @param null      $callback
     * @param bool      $addButton
     *
     * @return HtmlElement
     */
    protected function appendGroup(HtmlForm $Form, $title, $callback = null, $addButton = true)
    {
        $grouping = md5($title);
        $Group = $Form->add(new HtmlFormGroup($title));
        $Group->addClass('bnb-section clearfix collapsible');
        $Hidden = $Group->add(new HtmlHiddenField('toggleStates[' . $grouping . ']', 0));
        $Hidden->addClass('toggle-state');

        if (isset($this->toggleStates[$grouping]) && $this->toggleStates[$grouping])
            $Group->addClass('close');


            $Buttons = new HtmlTag('div', null, ['class' => 'buttons']);

            if (is_callable($callback)) {
                call_user_func($callback, $Group, $Buttons);
            }

        if (!$this->readOnly) {
            if ($addButton) {
                $Group->add($Buttons);
                $Buttons->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
            }
        }
        return $Group;
    }
    // End appendGroup


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
    protected function appendResult(HtmlElement $container, $title, $property, $refUnit = null, $value = null, $precision = 2, $controlProperty = null)
    {
        if (null !== $value)
            $this->Data->$property = $value;

        $label = $container->add(new ElcaHtmlFormElementLabel($title));

        if (null !== $controlProperty) {
            $controlElt = $label->add(new HtmlNumericTextWithUnit($controlProperty, $refUnit, null, null, $precision));
            $controlElt->addClass('frequency-column');
        }

        $elt = $label->add(new HtmlNumericTextWithUnit($property, $refUnit, null, null, $precision));
        $elt->addClass('input-column');

        return $label;
    }

    private function appendInput(HtmlElement $container, $property, $caption, $isRequired, $unit = null, $frequency = null)
    {
        $label = $container->add(
            new ElcaHtmlFormElementLabel($caption, null, $isRequired, $unit)
        );

//        if (null !== $frequency) {
//            $label->add(new HtmlTag('span', $frequency, ['class' => 'frequency-column']));
//        }

        $label->add($elt = new ElcaHtmlNumericInput($property))
            ->addClass('input-column');

        if ($frequency) {
            $elt->setAttribute('title', $frequency);
        }
    }
    // End appendResult
}
// End BnbWaterView