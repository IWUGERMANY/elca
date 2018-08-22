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
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOptGroup;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Controller\ProjectDataCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessConfigVariantSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the process config selector
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigSelectorView extends HtmlView
{

    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_OPERATION = 'operation';
    const BUILDMODE_FINAL_ENERGY_SUPPLY = 'finalEnergySupply';
    const BUILDMODE_TRANSPORTS = 'transports';

    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * Context
     */
    private $context;

    private $filterByProjectVariantId;

    private $enableReplaceAll;

    private $headline;

    /**
     * @var ElcaElement
     */
    private $element;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    public function init(array $args = [])
    {
        $this->setTplName('elca_process_config_selector', 'elca');
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->context   = $this->get('context');

        $this->filterByProjectVariantId = $this->get('filterByProjectVariantId');
        $this->enableReplaceAll         = $this->get('enableReplaceAll');
        $this->element                  = ElcaElement::findById($this->get('elementId'));

        $this->assign('headline', $this->get('headline', t('Baustoff suchen und wählen')));
    }
    // End init

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     *
     * @return -
     */
    protected function beforeRender()
    {
        $request           = FrontController::getInstance()->getRequest();
        $Container         = $this->getElementById('elca-process-config-selector-form-holder');
        $access            = ElcaAccess::getInstance();
        $activeProcessDbId = $this->get('db') ?: null;

        if ($this->context == ProjectElementsCtrl::CONTEXT || $this->context == ProjectDataCtrl::CONTEXT) {
            $activeProcessDbId = Elca::getInstance()->getProject()->getProcessDbId();
        }

        $compatibleProcessDbs = new ElcaProcessDbSet();
        if ($this->element->isTemplate()) {
            $compatibleProcessDbs = ElcaProcessDbSet::findElementCompatibles($this->element);

            if (0 === $compatibleProcessDbs->count()) {
                $compatibleProcessDbs = ElcaProcessDbSet::findActive();
            }
        } elseif ($activeProcessDbId) {
            $compatibleProcessDbs->add(ElcaProcessDb::findById($activeProcessDbId));
        }

        $filterByProcessDbId = $activeProcessDbId ? [$activeProcessDbId] : $compatibleProcessDbs->getArrayBy('id');

        $inUnit = $this->retrieveInUnit();

        // init variables
        $activeProcessConfig = ElcaProcessConfig::findById(
            $this->get('processConfigId') !== 'NULL' ? $this->get('processConfigId') : null
        );
        $categoryId          = $this->get('processCategoryNodeId', $activeProcessConfig->getProcessCategoryNodeId());
        $epdSubType          = $this->get('epdSubType');

        $request->processCategoryNodeId = $categoryId;
        $submitAction                   = $this->get('submitAction') ? $this->get('submitAction')
            : 'selectProcessConfig';
        $form                           = new HtmlForm(
            'processConfigSelectorForm',
            '/'.$this->context.'/'.$submitAction.'/'
        );
        $form->setAttribute('id', 'processConfigSelectorForm');
        $form->setAttribute('class', 'clearfix modal-selector-form');
        $form->setRequest($request);

        $this->appendHiddenfields($form, $activeProcessConfig, $activeProcessDbId, $epdSubType, $inUnit);

        if ($activeProcessConfig->isInitialized()) {
            $form->setDataObject($activeProcessConfig);
        }

        if ($this->element->isTemplate()) {
            $this->appendProcessDbFilter($form, $compatibleProcessDbs, $activeProcessDbId);
        }

        if ($this->hasEn15804CompliantProcessDb($compatibleProcessDbs, $activeProcessDbId)) {
            $this->appendEpdSubTypeFilter($form, $epdSubType);
        }

        $this->appendSearch($form, $categoryId, $inUnit, $filterByProcessDbId, $activeProcessDbId, $epdSubType);

        $categoryId = $this->appendCategorySearch(
            $form,
            $inUnit,
            $access,
            $activeProcessDbId,
            $filterByProcessDbId,
            $epdSubType,
            $categoryId
        );

        if ($categoryId) {
            $this->appendProcessConfigSearch(
                $form,
                $categoryId,
                $inUnit,
                $access,
                $activeProcessDbId,
                $filterByProcessDbId,
                $epdSubType,
                $activeProcessConfig
            );
        }

        $this->appendButtons($form);

        $form->appendTo($Container);
    }

    /**
     * @param $form
     * @param $epdSubType
     */
    private function appendEpdSubTypeFilter($form, $epdSubType)
    {
        if (false === $this->isDefaultBuildmode()) {
            return;
        }

        $epdSubTypeFilter = $form->add(
            new ElcaHtmlFormElementLabel(t('EPD Subtyp'), new HtmlSelectbox('epdSubType'))
        );
        foreach (
            [
                'Alle'                               => null,
                ElcaProcess::EPD_TYPE_GENERIC        => ElcaProcess::EPD_TYPE_GENERIC,
                ElcaProcess::EPD_TYPE_AVERAGE        => ElcaProcess::EPD_TYPE_AVERAGE,
                ElcaProcess::EPD_TYPE_REPRESENTATIVE => ElcaProcess::EPD_TYPE_REPRESENTATIVE,
                ElcaProcess::EPD_TYPE_SPECIFIC       => ElcaProcess::EPD_TYPE_SPECIFIC,
            ] as $caption => $value
        ) {
            $opt = $epdSubTypeFilter->add(
                new HtmlSelectOption(t($caption), $value)
            );

            if ($epdSubType === $value) {
                $opt->setAttribute('selected', 'selected');
            }
        }

        $epdSubTypeFilter->setAttribute('onchange', '$(this.form).submit();');
    }

    /**
     * @param $form
     * @param $categoryId
     * @param $inUnit
     * @param $activeProcessDbId
     * @param $epdSubType
     */
    private function appendSearch(HtmlForm $form, $categoryId, $inUnit, $compatibleProcessDbIds, $activeProcessDbId, $epdSubType)
    {
        $Search = $form->add(new ElcaHtmlFormElementLabel(t('Suche nach Stichwörtern'), new HtmlTextInput('search')));
        $Search->setAttribute('id', 'elca-process-config-search');
        $Search->setAttribute('data-url', '/'.$this->context.'/selectProcessConfig/');
        $Search->setAttribute('data-process-category-node-id', $categoryId);
        $Search->setAttribute('data-in-unit', $inUnit);
        $Search->setAttribute('data-build-mode', $this->buildMode);
        $Search->setAttribute('data-db', $activeProcessDbId);
        $Search->setAttribute('data-compatdbs', \json_encode($compatibleProcessDbIds));


        if ($this->filterByProjectVariantId) {
            $Search->setAttribute('data-filter-project-variant-id', $this->filterByProjectVariantId);
        }

        if ($epdSubType) {
            $Search->setAttribute('data-epd-sub-type', $epdSubType);
        }
    }

    /**
     * @param HtmlForm         $form
     * @param ElcaProcessDbSet $compatibleProcessDbs
     * @param null             $activeProcessDbId
     */
    private function appendProcessDbFilter(
        HtmlForm $form, ElcaProcessDbSet $compatibleProcessDbs, $activeProcessDbId = null
    ) {
        if (false === $this->isDefaultBuildmode()) {
            return;
        }

        $allActiveProcessDbs = ElcaProcessDbSet::findActive();
        $incompatibleDbs = array_filter(
            $allActiveProcessDbs->getArrayCopy(),
            function (ElcaProcessDb $processDb) use ($compatibleProcessDbs) {
                return null === $compatibleProcessDbs->search('id', $processDb->getId());
            }
        );

        $processDbFilter = $form->add(
            new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), new HtmlSelectbox('db'))
        );
        $processDbFilter->setAttribute('onchange', '$(this.form).submit();');

        $compatibleGroup = $processDbFilter->add(new HtmlSelectOptGroup('Verfügbar in'));

        if ($compatibleProcessDbs->count() > 1) {
            $compatibleGroup->add(
                new HtmlSelectOption(t('Alle verfügbaren'), null)
            );
        }

        foreach ($compatibleProcessDbs as $processDb) {
            $option = $compatibleGroup->add(
                new HtmlSelectOption($processDb->getName(), $processDb->getId())
            );

            if ($activeProcessDbId === $processDb->getId()) {
                $option->setAttribute('selected', 'selected');
            }
        }


        if ($incompatibleDbs) {
            $incompatibleGroup = $processDbFilter->add(new HtmlSelectOptGroup('Sonstige'));

            foreach ($incompatibleDbs as $processDb) {
                $option = $incompatibleGroup->add(
                    new HtmlSelectOption($processDb->getName(), $processDb->getId())
                );

                if ($activeProcessDbId === $processDb->getId()) {
                    $option->setAttribute('selected', 'selected');
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isDefaultBuildmode(): bool
    {
        return $this->buildMode === ElcaElementComponentsView::BUILDMODE_LAYERS ||
               $this->buildMode === ElcaElementComponentsView::BUILDMODE_COMPONENTS ||
               $this->buildMode === self::BUILDMODE_DEFAULT;
    }

    /**
     * @param $form
     * @param $activeProcessConfig
     * @param $activeProcessDbId
     * @param $epdSubType
     * @param $inUnit
     */
    private function appendHiddenfields(
        HtmlForm $form, ElcaProcessConfig $activeProcessConfig, $activeProcessDbId, $epdSubType, $inUnit
    ) {
        if ($this->buildMode == self::BUILDMODE_OPERATION ||
            $this->buildMode == self::BUILDMODE_FINAL_ENERGY_SUPPLY
        ) {
            $form->add(new HtmlHiddenField('relId', $this->get('relId')));
            $form->add(new HtmlHiddenField('projectVariantId', $this->get('v')));
            $form->add(new HtmlHiddenField('ngf', $this->get('ngf')));
            $form->add(new HtmlHiddenField('enEvVersion', $this->get('enEvVersion')));
        } else {
            $form->add(new HtmlHiddenField('elementId', $this->get('elementId')));
            $form->add(new HtmlHiddenField('relId', $this->get('relId')));
        }

        if ($this->get('data')) {
            $form->add(new HtmlHiddenField('data', $this->get('data')));
        }

        if ($this->get('headline')) {
            $form->add(new HtmlHiddenField('headline', $this->get('headline')));
        }

        if ($this->get('enableReplaceAll')) {
            $form->add(new HtmlHiddenField('replaceAll', $this->get('enableReplaceAll')));
        }

        $form->add(new HtmlHiddenField('p', $activeProcessConfig->getId()));
        $form->add(new HtmlHiddenField('sp', ''));
        $form->add(new HtmlHiddenField('db', $activeProcessDbId));
        $form->add(new HtmlHiddenField('filterByProjectVariantId', $this->filterByProjectVariantId));

        if ($epdSubType && ElcaProcessDb::findById($activeProcessDbId)->isEn15804Compliant()) {
            $form->add(new HtmlHiddenField('epdSubType', $epdSubType));
        }

        $form->add(new HtmlHiddenField('b', $this->buildMode));

        if ($inUnit) {
            $form->add(new HtmlHiddenField('u', $inUnit));
        }
    }

    /**
     * @param $form
     * @param $inUnit
     * @param $access
     * @param $activeProcessDbId
     * @param $filterByProcessDbId
     * @param $epdSubType
     * @param $categoryId
     * @return array
     */
    private function appendCategorySearch(
        HtmlForm $form, $inUnit, ElcaAccess $access, $activeProcessDbId, $filterByProcessDbId, $epdSubType, $categoryId
    ) {
        $select = $form->add(
            new ElcaHtmlFormElementLabel(t('Suche über Kategorie'), new HtmlSelectbox('processCategoryNodeId'), true)
        );
        $select->add(
            new HtmlSelectOption(
                $this->get('allowDeselection') ? ('-- '.t('Keine Auswahl').' --') : ('-- '.t('Bitte wählen').' --'),
                $this->get('allowDeselection') ? 'NULL' : ''
            )
        );

        $select->setAttribute('onchange', '$(this.form).submit();');

        switch ($this->buildMode) {
            case self::BUILDMODE_OPERATION:
                $CategorySet = ElcaProcessCategorySet::findOperationCategories(
                    $inUnit,
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            case self::BUILDMODE_FINAL_ENERGY_SUPPLY:
                $CategorySet = ElcaProcessCategorySet::findFinalEnergySupplyCategories(
                    $inUnit,
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            case self::BUILDMODE_TRANSPORTS:
                $CategorySet = ElcaProcessCategorySet::findTransportCategories(
                    $inUnit,
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            default:
                $CategorySet = ElcaProcessCategorySet::findExtended(
                    $inUnit,
                    !$access->hasAdminPrivileges(),
                    $filterByProcessDbId,
                    $this->filterByProjectVariantId,
                    $epdSubType ?: null
                );
        }


        $lastParentNodeName = null;
        foreach ($CategorySet as $Category) {
            if ($lastParentNodeName != $Category->getParentNodeName()) {
                $optGroup = $select->add(
                    new HtmlSelectOptGroup(t($lastParentNodeName = $Category->getParentNodeName()))
                );
            }

            $optGroup->add(
                new HtmlSelectOption($Category->getRefNum().' '.t($Category->getName()), $Category->getNodeId())
            );
        }

        if ($categoryId && null === $CategorySet->searchKey('nodeId', $categoryId)) {
            $category = ElcaProcessCategory::findByNodeId($categoryId);

            $optGroup = $select->add(
                new HtmlSelectOptGroup(t('Keine Treffer in Kategorie'))
            );

            $optGroup->add(
                new HtmlSelectOption($category->getRefNum().' '.t($category->getName()), $category->getNodeId())
            );

            $categoryId = null;
        }

        return $categoryId;
    }

    private function hasEn15804CompliantProcessDb(ElcaProcessDbSet $compatibleProcessDbs, $activeProcessDbId = null)
    {
        if (null !== $activeProcessDbId) {
            return ElcaProcessDb::findById($activeProcessDbId)->isEn15804Compliant();
        }

        foreach ($compatibleProcessDbs as $processDb) {
            if ($processDb->isEn15804Compliant()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed|null|string
     */
    private function retrieveInUnit()
    {
        $inUnit = null;
        switch ($this->buildMode) {
            case self::BUILDMODE_TRANSPORTS:
                $inUnit = Elca::UNIT_TKM;
                $Info   = $this->getElementById('layer-info');
                $Info->parentNode->removeChild($Info);
                break;

            case self::BUILDMODE_OPERATION:
            case self::BUILDMODE_FINAL_ENERGY_SUPPLY:
                $inUnit = Elca::UNIT_KWH;
                $Info   = $this->getElementById('layer-info');
                $Info->parentNode->removeChild($Info);
                break;

            case ElcaElementComponentsView::BUILDMODE_COMPONENTS:
                if ($this->has('inUnit')) {
                    $inUnit = $this->get('inUnit');
                }
                $Info = $this->getElementById('layer-info');
                $Info->parentNode->removeChild($Info);
                break;

            case ElcaElementComponentsView::BUILDMODE_LAYERS:
                $inUnit = Elca::UNIT_M3;
                break;

            default:
                if ($this->has('inUnit')) {
                    $inUnit = $this->get('inUnit');
                }

                $Info = $this->getElementById('layer-info');
                $Info->parentNode->removeChild($Info);

                if ($this->has('inUnit')) {
                    $this->removeChildNodes($Info);
                    $parts = explode(',', $this->get('inUnit'));

                    if (count($parts) < 2) {
                        $inUnitFormated = $this->get('inUnit');
                    } elseif (count($parts) == 2) {
                        $inUnitFormated = $parts[0].' '.t('oder').' '.$parts[1];
                    } else {
                        $last = array_pop($parts);

                        $inUnitFormated = join(', ', $parts);
                        $inUnitFormated .= ' '.t('oder').' '.$last;
                    }
                    $inUnitFormated = '<strong>'.$inUnitFormated.'</strong>';
                    if (count($parts) > 1) {
                        $Info->appendChild(
                            $this->getText(
                                t(
                                    'Es werden nur Baustoffe zur Auswahl gestellt, die sich in eine der Einheiten %units% umrechnen lassen.',
                                    null,
                                    ['%units%' => $inUnitFormated]
                                )
                            )
                        );
                    } else {
                        $Info->appendChild(
                            $this->getText(
                                t(
                                    'Es werden nur Baustoffe zur Auswahl gestellt, die sich in %unit% umrechnen lassen.',
                                    null,
                                    ['%unit%' => $inUnitFormated]
                                )
                            )
                        );
                    }
                }

                break;
        }

        return $inUnit;
    }

    /**
     * @param $form
     * @param $categoryId
     * @param $inUnit
     * @param $access
     * @param $activeProcessDbId
     * @param $filterByProcessDbId
     * @param $epdSubType
     * @param $activeProcessConfig
     */
    private function appendProcessConfigSearch(
        HtmlForm $form, $categoryId, $inUnit, ElcaAccess $access, $activeProcessDbId, $filterByProcessDbId, $epdSubType,
        ElcaProcessConfig $activeProcessConfig
    ) {
        $select = $form->add(
            $processSelectboxLabel = new ElcaHtmlFormElementLabel(t('Baustoff'), new HtmlSelectbox('id'), true)
        );
        $select->setAttribute('onchange', '$(this.form).submit();');
        $select->add(
            new HtmlSelectOption(
                $this->get('allowDeselection')
                    ? ('-- '.t('Keine Auswahl').' --')
                    : ('-- '.t(
                        'Bitte wählen'
                    ).' --'), $this->get('allowDeselection') ? 'NULL' : ''
            )
        );

        switch ($this->buildMode) {
            case  self::BUILDMODE_OPERATION:
                $processConfigSet = ElcaProcessConfigSet::findOperationsByProcessCategoryNodeId(
                    $categoryId,
                    $inUnit,
                    array('name' => 'ASC'),
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            case  self::BUILDMODE_FINAL_ENERGY_SUPPLY:
                $processConfigSet = ElcaProcessConfigSet::findFinalEnergySuppliesByProcessCategoryNodeId(
                    $categoryId,
                    $inUnit,
                    array('name' => 'ASC'),
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            case  self::BUILDMODE_TRANSPORTS:
                $processConfigSet = ElcaProcessConfigSet::findTransportsByProcessCategoryNodeId(
                    $categoryId,
                    $inUnit,
                    array('name' => 'ASC'),
                    !$access->hasAdminPrivileges(),
                    $activeProcessDbId
                );
                break;

            default:
                $processConfigSet = ElcaProcessConfigSet::findByProcessCategoryNodeId(
                    $categoryId,
                    $this->buildMode == ElcaElementComponentsView::BUILDMODE_LAYERS ? 'm3' : $inUnit,
                    ['name' => 'ASC'],
                    !$access->hasAdminPrivileges(),
                    $filterByProcessDbId,
                    false,
                    $this->filterByProjectVariantId,
                    $epdSubType ?: null
                );
        }

        foreach ($processConfigSet as $processConfig) {
            $caption = $processConfig->getName();

            $Opt = $select->add(new HtmlSelectOption($caption, $processConfig->getId()));

            if ($processConfig->getId() == $activeProcessConfig->getId()) {
                $Opt->setAttribute('selected', 'selected');
            }
        }

        if ($activeProcessConfig->getId()) {
            $processConfigVariantSet = ElcaProcessConfigVariantSet::find(
                ['process_config_id' => $activeProcessConfig->getId()],
                ['name' => 'ASC']
            );

            if ($processConfigVariantSet->count()) {
                $select = $form->add(
                    new ElcaHtmlFormElementLabel(t('Varianten'), new HtmlSelectbox('processConfigVariantUuid'))
                );
                $select->setAttribute('onchange', '$(this.form).submit();');
                $select->add(new HtmlSelectOption('-- '.t('Eigene Angabe').' --', ''));

                foreach ($processConfigVariantSet as $ProcessConfigVariant) {
                    $name = sprintf(
                        "%s [%s %s]",
                        $ProcessConfigVariant->getName(),
                        ElcaNumberFormat::toString($ProcessConfigVariant->getRefValue()),
                        ElcaNumberFormat::formatUnit($ProcessConfigVariant->getRefUnit())
                    );

                    $select->add(new HtmlSelectOption($name, $ProcessConfigVariant->getUuid()));
                }
            }

            /**
             * Build data sheet link if available
             */
            $lifeCyclePhase = $this->buildMode == ElcaProcessConfigSelectorView::BUILDMODE_OPERATION
                ? ElcaLifeCycle::PHASE_OP : ElcaLifeCycle::PHASE_PROD;
            $processSet     = $activeProcessConfig->getProcessesByProcessDbId(
                Elca::getInstance()->getProject()->getProcessDbId()
                ,
                ['life_cycle_phase' => $lifeCyclePhase]
            );

            /**
             * @var ElcaProcess $process
             */
            $process = isset($processSet[0]) ? $processSet[0] : null;
            if ($process && $process->isInitialized() && $process->getProcessDb()->hasSourceUri()) {
                $aAttr = [
                    'class'  => 'data-sheet-link no-xhr right',
                    'href'   => $process->getDataSheetUrl(),
                    'target' => '_blank',
                ];
                $processSelectboxLabel->add(new HtmlTag('a', t('Datenblatt anzeigen'), $aAttr));
            }
        }
    }

    /**
     * @param $form
     */
    private function appendButtons(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('buttons');

        if ($this->enableReplaceAll) {
            $group->add(new ElcaHtmlSubmitButton('applyAll', t('Für alle übernehmen')));
        }

        $group->add(new ElcaHtmlSubmitButton('select', t('Übernehmen')));
    }

    /**
     * Returns an assigned substitution variable
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        $value = parent::get($key, $defaultValue);

        if ('NULL' === $value) {
            $value = null;
        }

        return $value;
    }
}
// End ElcaProcessSelectorView
