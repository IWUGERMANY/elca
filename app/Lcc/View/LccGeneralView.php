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
namespace Lcc\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaNumberFormatConverter;
use Lcc\Db\LccCost;
use Lcc\Db\LccCostSet;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccVersionSet;
use Lcc\LccModule;

/**
 *
 *
 * @package   lcc
 * @author    Tobias Lode <tobias@beibob.de>
 * @copyright 2013 BEIBOB GbR
 */
class LccGeneralView extends HtmlView
{
    /**
     * Data
     */
    private $Data;

    /**
     * toggle states
     */
    private $toggleStates = [];

    /**
     * Admin mode
     */
    private $isAdminMode = false;

    /**
     * Converter
     */
    private $PercConverter;
    private $EurConverter;

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

        /**
         * Set admin mode
         */
        $this->isAdminMode = $this->get('adminMode', $this->isAdminMode);

        /**
         * Converter
         */
        $this->PercConverter = new ElcaNumberFormatConverter(2, true);
        $this->EurConverter = new ElcaNumberFormatConverter(2);
        $this->readOnly = $this->get('readOnly');
    }
    // End init


    /**
     * Callback triggered after rendering the template
     *
     * @param  -
     *
     * @return -
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'lcc general']));
        $projectId = Elca::getInstance()->getProjectId();

        $versionId = isset($this->Data->oldVersionId) && $this->Data->versionId !== $this->Data->oldVersionId ? $this->Data->oldVersionId : $this->Data->versionId;

        $Form = new HtmlForm('lccProjectData', '/lcc/general/save/');
        $Form->addClass('clearfix highlight-changes');
        $Form->setReadonly($this->readOnly);

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }
        $Form->setDataObject($this->Data);

        $Form->add(new HtmlHiddenField('projectVariantId', Elca::getInstance()->getProjectVariantId()));
        $Form->add(new HtmlHiddenField('isAdminMode', (int)$this->isAdminMode));

        $this->appendGroup($Form, '', [$this, 'appendParameters'], null, 'version');

        if ($this->Data->isInitialized) {
            $this->appendGroup($Form, 'A.1 ' . t('Regelmässige Zahlungen') . ' - ' . t('Wasser / Abwasser'), [$this, 'appendRegularCosts'], LccCostSet::findRegular($versionId, LccCost::GROUPING_WATER), LccCost::GROUPING_WATER);
            $this->appendGroup($Form, 'A.1 ' . t('Regelmässige Zahlungen') . ' - ' . t('Energie'), [$this, 'appendRegularCosts'], LccCostSet::findRegular($versionId, LccCost::GROUPING_ENERGY), LccCost::GROUPING_ENERGY);
            $this->appendGroup($Form, 'A.2 ' . t('Regelmässige Zahlungen') . ' - ' . t('Reinigung'), [$this, 'appendRegularCosts'], LccCostSet::findRegular($versionId, LccCost::GROUPING_CLEANING), LccCost::GROUPING_CLEANING);

            $this->appendGroup($Form, 'B ' . t('Regelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 300', [$this, 'appendRegularServiceCosts'], LccCostSet::findRegularService($versionId, 300, $projectId), LccCost::GROUPING_KGR . '300');
            $this->appendGroup($Form, 'C ' . t('Regelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 400', [$this, 'appendRegularServiceCosts'], LccCostSet::findRegularService($versionId, 400, $projectId), LccCost::GROUPING_KGR . '400');
            $this->appendGroup($Form, 'C ' . t('Regelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 500', [$this, 'appendRegularServiceCosts'], LccCostSet::findRegularService($versionId, 500, $projectId), LccCost::GROUPING_KGR . '500');

            $this->appendGroup($Form, 'D ' . t('Unregelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 300', [$this, 'appendIrregularCosts'], LccCostSet::findIrregular($versionId, 300, $projectId), LccCost::GROUPING_KGU . '300');
            $this->appendGroup($Form, 'E ' . t('Unregelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 400', [$this, 'appendIrregularCosts'], LccCostSet::findIrregular($versionId, 400, $projectId), LccCost::GROUPING_KGU . '400');
            $this->appendGroup($Form, 'E ' . t('Unregelmässige Zahlungen') . ' - ' . t('Kostengruppe') . ' 500', [$this, 'appendIrregularCosts'], LccCostSet::findIrregular($versionId, 500, $projectId), LccCost::GROUPING_KGU . '500');
        }

        $Form->appendTo($Container);
    }
    // End afterRender


    /**
     * Appends the parameter sectiona
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendParameters(HtmlElement $Group, HtmlElement $Buttons)
    {
        $Group->addClass('parameters');
        $Group->removeClass('collapsible');

        $ParamGroup = $Group->add(new HtmlTag('div', null, ['class' => 'parameter-group']));

        $Select = new HtmlSelectbox('versionId');
        $Select->setAttribute('onchange', '$(this.form).submit()');
        foreach(LccVersionSet::find(['calc_method' => LccModule::CALC_METHOD_GENERAL], ['name' => 'ASC']) as $Version)
            $Select->add(new HtmlSelectOption($Version->getName(), $Version->getId()));

        if (isset($this->Data->oldVersionId) && $this->Data->versionId !== $this->Data->oldVersionId) {
            $Select->addClass('changed');
        }

        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Preisstand'), $Select));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Betrachtungszeitraum'), new ElcaHtmlNumericInput('projectLifeTime', null, true), false, t('Jahre')));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Bezugsgröße BGF'), new ElcaHtmlNumericInput('bgf', null, (bool)$this->Data->bgf), !(bool)$this->Data->bgf, 'm²'));

        $RadioGroup = new HtmlRadioGroup('category');
        $RadioGroup->add(new HtmlRadiobox('1', 1));
        $RadioGroup->add(new HtmlRadiobox('2', 2));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Sonderbedingungen'), $RadioGroup, true));

        $ParamGroup = $Group->add(new HtmlTag('div', null, ['class' => 'parameter-group']));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Zinssatz'), new ElcaHtmlNumericInput('rate', null, !$this->isAdminMode, $this->PercConverter), null, '%'));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Allg. Preissteigerung (z.B. Baukosten)'), new ElcaHtmlNumericInput('commonPriceInc', null, !$this->isAdminMode, $this->PercConverter), null, '%'));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Preissteigerung Energiekosten'), new ElcaHtmlNumericInput('energyPriceInc', null, !$this->isAdminMode, $this->PercConverter), null, '%'));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Preissteigerung Wasser-/Abwasserkosten'), new ElcaHtmlNumericInput('waterPriceInc', null, !$this->isAdminMode, $this->PercConverter), null, '%'));
        $ParamGroup->add(new ElcaHtmlFormElementLabel(t('Preissteigerung Dienstleistung Reinigung'), new ElcaHtmlNumericInput('cleaningPriceInc', null, !$this->isAdminMode, $this->PercConverter), null, '%'));

        $Group->add(new HtmlTag('h4', t('Kosten gemäß Entscheidungsunterlage Bau')));
        $Group->add(new ElcaHtmlFormElementLabel(t('KG300 Bauwerk- Baukonstruktion'), new ElcaHtmlNumericInput('costs300'), true, '€'));
        $Group->add(new ElcaHtmlFormElementLabel(t('KG400 Bauwerk- Technische Anlagen'), new ElcaHtmlNumericInput('costs400'), true, '€'));
        $Group->add(new ElcaHtmlFormElementLabel(t('KG500 Technische Anlagen in Außenanlagen'), new ElcaHtmlNumericInput('costs500'), true, '€'));

        if (ElcaAccess::getInstance()->hasAdminPrivileges()) {
            if ($this->isAdminMode)
                $Buttons->add(new ElcaHtmlSubmitButton('cancel', t('Editieren beenden')));
            else
                $Buttons->add(new ElcaHtmlSubmitButton('setAdminMode', t('Parameter editieren')));
        }
    }
    // End appendParameters


    /**
     * Appends the media group section
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendRegularCosts(HtmlElement $Group, HtmlElement $Buttons, LccCostSet $LccCostSet)
    {
        $Group->addClass('media-cleaning collapsable');

        $Hl = $Group->add(new HtmlTag('div', null, ['class' => 'headline']));
        $Hl->add(new HtmlTag('h3', t('phys. ME / Jahr'), ['class' => 'hl-me-per-a']));
        $Hl->add(new HtmlTag('h3', t('€ / ME'), ['class' => 'hl-eur-per-me']));

        $lastHeadline = null;
        $allValuesSet = true;
        $grouping = null;
        $FormSection = null;
        foreach ($LccCostSet as $LccCost) {
            $costId = $LccCost->getId();
            if ($lastHeadline != t($LccCost->getHeadline()))
                $Group->add(new HtmlTag('h4', t($LccCost->getHeadline())));

            $refValue = $LccCost->getRefValue();
            $allValuesSet &= isset($this->Data->quantity[$costId]) && !is_null($this->Data->quantity[$costId]);

            $Elts = new HtmlTag('div');
            $Elts->add(new ElcaHtmlNumericInput('quantity[' . $costId . ']'));

            $AdminElt = $Elts->add(new ElcaHtmlNumericInput('refValue[' . $costId . ']', $refValue, !($this->isAdminMode || is_null($refValue))));
            $AdminElt->addClass('refValue');

            if (!$this->isAdminMode && !is_null($refValue))
                $AdminElt->setAttribute('disabled', 'disabled');

            $Elts->add(new HtmlTag('span', $LccCost->getRefUnit(), ['class' => 'ref-unit']));

            $FormSection = $Group->add(new ElcaHtmlFormElementLabel($LccCost->getDin276Code() . ' ' . t($LccCost->getLabel())));
            $FormSection->addClass('row-highlight');
            $FormSection->add($Elts);

            $lastHeadline = t($LccCost->getHeadline());
            $grouping = $LccCost->getGrouping();
        }
        if ($FormSection)
            $FormSection->addClass('last');

        if ($allValuesSet)
            $Group->addClass('close');

        // append totals for this group
        $Totals = $Group->add(new HtmlTag('div', null, ['class' => 'totals']));
        $FormSection = $Totals->add(new ElcaHtmlFormElementLabel(t('Barwert'), new ElcaHtmlNumericText('sum[' . $grouping . ']', 2)));
        $FormSection->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));
    }
    // End appendMediaCosts


    /**
     * Appends the service group section
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendRegularServiceCosts(HtmlElement $Group, HtmlElement $Buttons, LccCostSet $LccCostSet, $grouping)
    {
        $Group->addClass('service');

        $Hl = $Group->add(new HtmlTag('div', null, ['class' => 'headline']));
        $Hl->add(new HtmlTag('h3', t('Instandsetzung in %'), ['class' => 'hl-maintenance-perc']));
        $Hl->add(new HtmlTag('h3', t('Wartung und Inspektion in %'), ['class' => 'hl-service-perc']));
        $Hl->add(new HtmlTag('h3', t('Herstellungskosten'), ['class' => 'hl-costs']));

        $FormSection = null;
        $allValuesSet = true;
        foreach ($LccCostSet as $LccCost) {
            $costId = $LccCost->getId();
            $this->appendRegularServiceCostsRow($Group, $grouping, $LccCost);
            $allValuesSet &= isset($this->Data->quantity[$costId]) && !is_null($this->Data->quantity[$costId]);
        }

        $FormSection = $this->appendRegularServiceCostsRow($Group, $grouping, null);

        if ($FormSection)
            $FormSection->addClass('last');

        if ($allValuesSet)
            $Group->addClass('close');

        // append totals for this group
        if (!isset($this->Data->sum[$grouping]))
            $this->Data->sum[$grouping] = 0;

        $Totals = $Group->add(new HtmlTag('div', null, ['class' => 'totals']));
        $FormSection = $Totals->add(new ElcaHtmlFormElementLabel(t('Barwert'), new ElcaHtmlNumericText('sum[' . $grouping . ']', 2)));
        $FormSection->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));

    }
    // End appendRegularServiceCosts


    /**
     * Appends a regular service cost row
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendRegularServiceCostsRow($Group, $grouping, LccCost $LccCost = null)
    {
        $costId = $LccCost ? $LccCost->getId() : $grouping . '_new';

        $maintenancePerc = $LccCost ? $LccCost->getMaintenancePerc() : null;
        $servicePerc = $LccCost ? $LccCost->getServicePerc() : null;

        $Elts = new HtmlTag('div');

        if (!$LccCost) {
            $Din = $Elts->add(new ElcaHtmlNumericInput('din276Code[' . $costId . ']'));
            $Din->addClass('din276Code');
            $Din->setAttribute('maxlength', 3);
            $Elts->add(new HtmlTextInput('label[' . $costId . ']'))->addClass('label');
        }

        $readonly = !($this->isAdminMode || is_null($LccCost));

        $AdminElt = $Elts->add(new ElcaHtmlNumericInput('maintenancePerc[' . $costId . ']', $maintenancePerc, $readonly, $this->PercConverter));
        $AdminElt->addClass('maintenancePerc');
        if ($readonly)
            $AdminElt->setAttribute('disabled', 'disabled');

        $AdminElt = $Elts->add(new ElcaHtmlNumericInput('servicePerc[' . $costId . ']', $servicePerc, $readonly, $this->PercConverter));
        $AdminElt->addClass('servicePerc');
        if ($readonly)
            $AdminElt->setAttribute('disabled', 'disabled');

        $Elts->add(new ElcaHtmlNumericInput('quantity[' . $costId . ']', null, false, $this->EurConverter))->addClass('costs');
        $Elts->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));

        if($LccCost)
        {
            if($LccCost->getProjectId())
                $Elts->add(new HtmlTag('a', 'löschen', ['class' => 'delete-cost', 'href' => '/lcc/general/delete/?id='.$costId]));

            $FormSection = $Group->add(new ElcaHtmlFormElementLabel($LccCost->getDin276Code() . ' ' . t($LccCost->getLabel())));
            $FormSection->addClass('row-highlight');
            $FormSection->add($Elts);
        } else {
            $FormSection = $Group->add(new ElcaHtmlFormElementLabel(''));
            $FormSection->addClass('new');
            $FormSection->add($Elts);
        }

        return $FormSection;
    }
    // End appendRegularServiceCostsRow


    /**
     * Appends the irregular costs group section
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendIrregularCosts(HtmlElement $Group, HtmlElement $Buttons, LccCostSet $LccCostSet, $grouping)
    {
        $Group->addClass('irregular');

        if ($grouping == LccCost::GROUPING_KGU . '300') {
            $RadioGroup = new HtmlRadioGroup('kguAlt[' . $grouping . ']', null, $this->readOnly);
            $RadioGroup->add(new HtmlRadiobox(t('Einzelauflistung'), ''));
            $RadioGroup->add(new HtmlRadiobox(ElcaNumberFormat::toString(LccProjectVersion::KGU_ALTERNATIVE_PERCENTAGE) . t('% der Herstellungskosten KG300 pro Jahr ansetzen'), (string)LccProjectVersion::KGU_ALTERNATIVE_PERCENTAGE));
            $Group->add(new ElcaHtmlFormElementLabel(t('Berechnungsmethode'), $RadioGroup));

            $AltGroup = $Group->add(new HtmlTag('div', null, ['class' => 'alt-group']));
            $Section = $AltGroup->add(new ElcaHtmlFormElementLabel(ElcaNumberFormat::toString(LccProjectVersion::KGU_ALTERNATIVE_PERCENTAGE) . t('% der Herstellungskosten KG300 pro Jahr'), new ElcaHtmlNumericText('kguAltPerc[' . $grouping . ']', 2)));
            $Section->addClass('kguAltPerc');
            $Section->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));

            $ListGroup = $Group->add(new HtmlTag('div', null, ['class' => 'list-group']));

            if (isset($this->Data->kguAlt[$grouping]) && $this->Data->kguAlt[$grouping] > 0) {
                $ListGroup->setAttribute('style', 'display:none');
                $Group->removeClass('collapsible');
            } else
                $AltGroup->setAttribute('style', 'display:none');
        } else
            $ListGroup = $Group;


        $Hl = $ListGroup->add(new HtmlTag('div', null, ['class' => 'headline']));
        $Hl->add(new HtmlTag('h3', t('Nutzungsdauer in Jahren'), ['class' => 'hl-life-time']));
        $Hl->add(new HtmlTag('h3', t('Ersatzhäufigkeit'), ['class' => 'hl-replacements']));
        $Hl->add(new HtmlTag('h3', t('Herstellungskosten'), ['class' => 'hl-costs']));

        $projectLifeTime = Elca::getInstance()->getProject()->getLifeTime();

        $allValuesSet = true;
        $FormSection = null;
        foreach ($LccCostSet as $LccCost) {
            $costId = $LccCost->getId();
            $this->appendIrregularCostsRow($ListGroup, $grouping, $projectLifeTime, $LccCost);
            $allValuesSet &= isset($this->Data->quantity[$costId]) && !is_null($this->Data->quantity[$costId]);
        }

        $FormSection = $this->appendIrregularCostsRow($ListGroup, $grouping, $projectLifeTime);

        if ($FormSection)
            $FormSection->addClass('last');

        if ($allValuesSet)
            $Group->addClass('close');

        // append totals for this group
        if (!isset($this->Data->sum[$grouping]))
            $this->Data->sum[$grouping] = 0;

        $Totals = $Group->add(new HtmlTag('div', null, ['class' => 'totals']));
        $FormSection = $Totals->add(new ElcaHtmlFormElementLabel(t('Barwert'), new ElcaHtmlNumericText('sum[' . $grouping . ']', 2)));
        $FormSection->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));
    }
    // End appendMediaCosts


    /**
     * Appends a irregular costs group row
     *
     * @param  HtmlForm $Form
     *
     * @return HtmlElement
     */
    protected function appendIrregularCostsRow($Group, $grouping, $projectLifeTime, LccCost $LccCost = null)
    {
        $costId = $LccCost ? $LccCost->getId() : $grouping . '_new';

        $lifeTime = $LccCost ? $LccCost->getLifeTime() : null;
        $replacements = $lifeTime ? ($projectLifeTime % $lifeTime == 0 ? $projectLifeTime / $lifeTime - 1 : floor($projectLifeTime / $lifeTime)) : null;

        $Elts = new HtmlTag('div');

        if (!$LccCost) {
            $Din = $Elts->add(new ElcaHtmlNumericInput('din276Code[' . $costId . ']'));
            $Din->addClass('din276Code');
            $Din->setAttribute('maxlength', 3);
            $Elts->add(new HtmlTextInput('label[' . $costId . ']'))->addClass('label');
        }

        $readonly = !($this->isAdminMode || is_null($LccCost));

        $AdminElt = $Elts->add(new ElcaHtmlNumericInput('lifeTime[' . $costId . ']', $lifeTime, $readonly));
        $AdminElt->addClass('lifeTime');
        if ($readonly)
            $AdminElt->setAttribute('disabled', 'disabled');

        $Elts->add(new HtmlTag('span', $replacements, ['class' => 'replacements']));
        $Elts->add(new ElcaHtmlNumericInput('quantity[' . $costId . ']', null, false))->addClass('costs');
        $Elts->add(new HtmlTag('span', '€', ['class' => 'ref-unit']));

        if($LccCost)
        {
            if($LccCost->getProjectId())
                $Elts->add(new HtmlTag('a', t('löschen'), ['class' => 'delete-cost', 'href' => '/lcc/general/delete/?id='.$costId]));

            $FormSection = $Group->add(new ElcaHtmlFormElementLabel($LccCost->getDin276Code() . ' ' . t($LccCost->getLabel())));
            $FormSection->addClass('row-highlight');
            $FormSection->add($Elts);
        } else {
            $FormSection = $Group->add(new ElcaHtmlFormElementLabel(''));
            $FormSection->addClass('new');
            $FormSection->add($Elts);
        }

        return $FormSection;
    }
    // End appendIrregularCostsRow


    /**
     * Appends a expand/collapsable group to the form
     *
     * @param  HtmlForm $Form
     * @param  string   $title
     *
     * @return HtmlElement
     */
    protected function appendGroup(HtmlForm $Form, $title, $callback = null, LccCostSet $CostSet = null, $grouping = null)
    {
        $Group = $Form->add(new HtmlFormGroup($title));
        $Group->addClass('lcc-section clearfix collapsible');
        $Hidden = $Group->add(new HtmlHiddenField('toggleStates[' . $grouping . ']', 0));
        $Hidden->addClass('toggle-state');

        if (isset($this->toggleStates[$grouping]) && $this->toggleStates[$grouping])
            $Group->addClass('close');

            $Buttons = new HtmlTag('div', null, ['class' => 'buttons']);

        if (is_callable($callback))
            call_user_func($callback, $Group, $Buttons, $CostSet, $grouping);

        $Group->add($Buttons);

        if (!$this->readOnly) {
            $Buttons->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        }

        return $Group;
    }
    // End appendGroup
}
// End LccGeneralView
