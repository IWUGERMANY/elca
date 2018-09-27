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
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOptGroup;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaProcess;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessSet;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the process selector
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessSelectorView extends HtmlView
{
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructs the Document
     *
     * @param  string $xmlName
     * @return -
     */
    public function __construct()
    {
        parent::__construct('elca_process_selector');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Request = FrontController::getInstance()->getRequest();
        $Container = $this->getElementById('elca-process-selector-form-holder');

        // init variables
        $ActiveProcess = ElcaProcess::findById($this->get('processId'));
        $ProcessDb = ElcaProcessDb::findById($this->get('processDbId'));
        $lifeCycle = $this->get('lifeCycleIdent', $ActiveProcess->getLifeCycleIdent());
        $categoryId = $this->get('processCategoryNodeId', $ActiveProcess->getProcessCategoryNodeId());

        $this->assign('processDb', $ProcessDb->getName());

        $Form = new HtmlForm('processSelectorForm', '/processes/selectProcess/');
        $Form->setAttribute('id', 'processSelectorForm');
        $Form->setAttribute('class', 'clearfix modal-selector-form');
        $Form->setRequest($Request);

        $Form->add(new HtmlHiddenField('processConfigId', $this->get('processConfigId')));
        $Form->add(new HtmlHiddenField('p', $ActiveProcess->getId()));
        $Form->add(new HtmlHiddenField('processDbId', $this->get('processDbId')));
        $Form->add(new HtmlHiddenField('lc', $lifeCycle));
        $Form->add(new HtmlHiddenField('plcaId', $this->get('processLifeCycleAssignmentId')));

        if($ActiveProcess->isInitialized())
            $Form->setDataObject($ActiveProcess);

        $Search = $Form->add(new ElcaHtmlFormElementLabel(t('Suche'), new HtmlTextInput('search')));
        $Search->setAttribute('id', 'elca-process-search');
        $Search->setAttribute('data-url', '/processes/selectProcess/');
        $Search->setAttribute('data-life-cycle', $lifeCycle);
        $Search->setAttribute('data-process-db-id', $this->get('processDbId'));

        $Select = $Form->add(new ElcaHtmlFormElementLabel(t('Kategorie'), new HtmlSelectbox('processCategoryNodeId'), true));
        $Select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));

        $Select->setAttribute('onchange', '$(this.form).submit();');

        $CategorySet = ElcaProcessCategorySet::findByLifeCycleIdent($lifeCycle);
        $lastParentNodeName = null;
        foreach($CategorySet as $Category)
        {
            $parentNodeName = $Category->getParentNodeRefNum().' '. t($Category->getParentNodeName());

            if($lastParentNodeName != $parentNodeName)
                $OptGroup = $Select->add(new HtmlSelectOptGroup($lastParentNodeName = $parentNodeName));

            $optionElt = $OptGroup->add(new HtmlSelectOption($Category->getRefNum().' '. t($Category->getName()), $Category->getNodeId()));

            if ($categoryId == $Category->getNodeId()) {
                $optionElt->setAttribute('selected', 'selected');
            }
        }


        // unset categoryId if id is not in CategorySet
        if($categoryId && !$CategorySet->search('nodeId', $categoryId))
            $categoryId = null;

        if($categoryId)
        {
            $Select = $Form->add($SelectLabel = new ElcaHtmlFormElementLabel(t('Prozess'), new HtmlSelectbox('id'), true));
            $Select->setAttribute('onchange', '$(this.form).submit();');
            $Select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));

            $ProcessSet = ElcaProcessSet::findExtended(['process_category_node_id' => $categoryId,
                                                             'life_cycle_ident'  => $lifeCycle,
                                                             'process_db_id' => $this->get('processDbId')], ['name' => 'ASC']);

            /**
             * @var ElcaProcess $Process
             */
            foreach($ProcessSet as $Process)
            {
                $caption = \processName($Process->getId());

                if ($Process->getGeographicalRepresentativeness()) {
                    $caption .= ' ['. $Process->getGeographicalRepresentativeness().']';
                }

                if ($Process->getEpdType()) {
                    $caption .= ' ['. $Process->getEpdType().']';
                }

                if($Process->getScenarioId())
                    $caption .= ' ['. $Process->getScenario()->getDescription().']';

                $Opt = $Select->add(new HtmlSelectOption($caption, $Process->getId()));

                if($Process->getId() == $ActiveProcess->getId())
                    $Opt->setAttribute('selected', 'selected');
            }

            if ($ProcessSet->count() === 1) {
                $Opt->setAttribute('selected', 'selected');
            }

            /**
             * Build data sheet link if available
             */
            if ($ActiveProcess && $ActiveProcess->isInitialized() && $ActiveProcess->getProcessDb()->getSourceUri())
            {
                $aAttr = [
                    'class'    => 'data-sheet-link no-xhr right'
                    , 'href'   => Url::factory(
                        $ActiveProcess->getProcessDb()->getSourceUri() . '/../../processes/' . $ActiveProcess->getUuid(),  [
                            'lang' => Elca::getInstance()->getLocale()
                        ]
                    )
                    , 'target' => '_blank'
                ];
                $SelectLabel->add(new HtmlTag('a', t('Datenblatt anzeigen'), $aAttr));
            }
        }

        $Form->add(new ElcaHtmlSubmitButton('select', t('Übernehmen')));
        $Form->appendTo($Container);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaProcessSelectorView
