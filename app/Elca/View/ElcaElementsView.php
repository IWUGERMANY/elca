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
use Beibob\Blibs\UserStore;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTextInput;
use Beibob\HtmlTools\HtmlUploadInput;
use DOMElement;
use DOMNode;
use Elca\Controller\ElementsCtrl;
use Elca\Db\ElcaConstrCatalog;
use Elca\Db\ElcaConstrCatalogSet;
use Elca\Db\ElcaConstrDesign;
use Elca\Db\ElcaConstrDesignSet;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\Service\Assistant\ElementAssistantRegistry;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds a list of element sheets *
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementsView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_OVERVIEW = 'overview';
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_SELECT = 'select';

    /**
     * Page Limit
     */
    const PAGE_LIMIT = 10;

    /**
     * Buildmode
     */
    protected $buildMode;

    /**
     * Element type node id
     */
    protected $elementTypeNodeId;

    /**
     * Element Type
     */
    protected $elementType;

    /**
     * Context
     */
    protected $context;

    /**
     * Current action
     */
    protected $action;

    /**
     * Filter
     */
    protected $FilterDO;

    /**
     * Current page
     */
    protected $page = 0;

    /**
     * Return only resultList container
     */
    protected $returnResultList = false;

    /**
     * @var ElementAssistantRegistry
     */
    protected $assistantRegistry;

    /**
     * @var bool
     */
    protected $readOnly;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->buildMode         = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->elementTypeNodeId = $this->get('elementTypeNodeId');
        $this->elementType       = ElcaElementType::findByNodeId($this->elementTypeNodeId);

        $this->readOnly         = $this->get('readOnly', false);
        $this->context          = $this->get('context', 'elements');
        $this->action           = $this->get('action');
        $this->FilterDO         = $this->get('FilterDO', new \stdClass());
        $this->returnResultList = $this->get('returnResultList', false);

        $this->assistantRegistry = $this->get('assistantRegistry');

        /**
         * Current page
         */
        $this->page = $this->get('page', 0);

        switch ($this->buildMode) {
            case self::BUILDMODE_OVERVIEW:
                $this->setTplName($this->get('tplName'));
                break;
        }
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
        switch ($this->buildMode) {
            case self::BUILDMODE_DEFAULT:
            case self::BUILDMODE_SELECT:
                $this->buildList('Elca\View\ElcaElementSheetView');
                break;

            default:
                $this->buildOverview();
        }
    }
    // End beforeRender


    /**
     * Builds the overview
     */
    protected function buildOverview()
    {
        $Container = $this->getElementById('content');

        /**
         * Last modified ProcessConfigs
         */
        if ($this->context == ElementsCtrl::CONTEXT) {
            $Elements = ElcaElementSet::findLastModified(null, UserStore::getInstance()->getUserId());
            $caption  = t('Bauteilvorlagen');

            /**
             * Append import form
             */
            $Form = new HtmlForm('elementImportForm', '/elements/import/');
            $Form->addClass('clearfix');

            if ($this->has('Validator')) {
                $Form->setValidator($this->get('Validator'));
                $Form->setRequest(FrontController::getInstance()->getRequest());
            }

            $Group = $Form->add(new HtmlFormGroup(''));
            $Group->add(new ElcaHtmlFormElementLabel(t('Importdatei laden (.xml)'), new HtmlUploadInput('importFile')));

            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('upload', t('Importieren')));

            $ImportContainer = $this->getElementById('importElements');
            $Form->appendTo($ImportContainer);
        } else {
            $Elements = ElcaElementSet::findLastModified(
                Elca::getInstance()->getProjectVariantId(),
                UserStore::getInstance()->getUserId()
            );
            $caption  = t('Bauteile');
        }

        if ($Elements->count()) {

            $lastModifiedContainer = $Container->appendChild($this->getDiv(['class' => 'last-modified']));

            $lastModifiedContainer->appendChild(
                $this->getH3(t('Zuletzt von Ihnen bearbeitete %caption%', null, ['%caption%' => $caption]), ['class' => 'section'])
            );
            $Ul = $lastModifiedContainer->appendChild($this->getUl());
            foreach ($Elements as $Element) {
                $Li         = $Ul->appendChild($this->getLi());
                $attributes = ['class' => 'page', 'href' => '/'.$this->context.'/'.$Element->getId().'/'];
                $Li->appendChild($this->getA($attributes, $Element->getName()));
            }
        }
    }
    // End buildOverview


    /**
     * Builds the element list
     */
    protected function buildList($sheetView)
    {
        /**
         * Prepare result container
         */
        $ResultContainer = $this->getDiv(['id' => 'elcaSheetsContainer']);

        if (!$this->returnResultList && $this->page === 0) {
            $Container = $this->appendChild(
                $this->getDiv(
                    [
                        'id'                     => 'content',
                        'class'                  => 'element-list '.$this->context.' elca-filter-sheets',
                        'data-elementTypeNodeId' => $this->elementTypeNodeId,
                    ]
                )
            );
            $TopRow    = $Container->appendChild($this->getDiv(['class' => 'elements-top clearfix']));

            /**
             * Filter
             */
            $this->appendFilterForm($TopRow);

            /**
             * Button
             */
            $this->appendButtons($TopRow);

            /**
             * Result container
             */
            $Container->appendChild($ResultContainer);
        } elseif ($this->returnResultList) {
            $this->appendChild($ResultContainer);
        } else {
            $ResultContainer = $this;
        }

        /**
         * Elements
         */
        $filterKeywords = preg_split("/[\s,]+/", $this->FilterDO->search, -1, PREG_SPLIT_NO_EMPTY);
        $Elements       = $this->getElementSet($filterKeywords);
        $this->appendFilterInfos($ResultContainer, $filterKeywords);
        $this->appendElementSheets($ResultContainer, $Elements, $sheetView);
    }
    // End buildList


    /**
     * Returns the element set
     *
     * @param  array $filterKeywords
     * @return ElcaElementSet
     */
    protected function getElementSet(array $filterKeywords)
    {
        $Access = ElcaAccess::getInstance();
        $filter = [
            'element_type_node_id' => $this->elementTypeNodeId,
            'project_variant_id'   => null,
        ];

        $processDbId = isset($this->FilterDO->processDbId) && $this->FilterDO->processDbId
            ? $this->FilterDO->processDbId : null;

        if (isset($this->FilterDO->constrDesignId) && $this->FilterDO->constrDesignId) {
            $filter['constr_design_id'] = $this->FilterDO->constrDesignId;
        }

        if (isset($this->FilterDO->constrCatalogId) && $this->FilterDO->constrCatalogId) {
            $filter['constr_catalog_id'] = $this->FilterDO->constrCatalogId;
        }

        if (isset($this->FilterDO->scope)) {
            switch ($this->FilterDO->scope) {
                case 'private':
                    $filter['is_public'] = false;
                    break;
                case 'owned':
                    $filter['access_group_id'] = $Access->getUserGroupId();
                    break;
                case 'public':
                    $filter['is_public'] = true;
                    break;
                case 'reference':
                    $filter['is_reference'] = true;
                    break;
            }
        }

        $elements = ElcaElementSet::searchExtended(
            $filterKeywords,
            $filter,
            $this->elementType->isCompositeLevel(),
            $Access->hasAdminPrivileges(),
            $Access->getUserGroupIds(),
            $processDbId ? $processDbId : null,
            ['name' => 'ASC'],
            self::PAGE_LIMIT + 1,
            $this->page * self::PAGE_LIMIT
        );

        return $elements;
    }
    // End getElementSet


    /**
     * Appends the filter info container
     *
     * @param  -
     * @return -
     */
    protected function appendFilterInfos(DOMNode $container, array $filterKeywords)
    {
        if ($container === $this) {
            return;
        }

        $filterInfo = $container->appendChild($this->getUl(['class' => 'filter-tags clearfix']));

        $filterInfos = $this->getFilterInfos($filterKeywords);
        if (count($filterInfos)) {
            $filterInfo->appendChild($this->getLi(['class' => 'label'], t('Einschränkungen')));

            foreach ($filterInfos as $cssClass => $info) {
                if (is_array($info)) {
                    foreach ($info as $iInfo) {
                        $this->appendFilterInfo($filterInfo, $cssClass, $iInfo);
                    }
                } else {
                    $this->appendFilterInfo($filterInfo, $cssClass, $info);
                }
            }
        } else {
            $filterInfo->appendChild($this->getLi(['class' => 'label'], t('Keine Einschränkungen')));
        }
    }
    // End appendFilterInfos


    /**
     * Returns filter infos
     *
     * @param  -
     * @return array
     */
    protected function getFilterInfos(array $filterKeywords)
    {
        $filterInfo = [];

        if (isset($this->FilterDO->constrDesignId) && $this->FilterDO->constrDesignId) {
            $filterInfo['constrDesignId'] = ElcaConstrDesign::findById($this->FilterDO->constrDesignId)->getName();
        }

        if (isset($this->FilterDO->constrCatalogId) && $this->FilterDO->constrCatalogId) {
            $filterInfo['constrCatalogId'] = ElcaConstrCatalog::findById($this->FilterDO->constrCatalogId)->getName();
        }

        if (isset($this->FilterDO->processDbId) && $this->FilterDO->processDbId) {
            $filterInfo['processDbId'] = ElcaProcessDb::findById($this->FilterDO->processDbId)->getName();
        }

        if (isset($this->FilterDO->scope)) {
            switch ($this->FilterDO->scope) {
                case 'private':
                    $filterInfo['scope'] = t('Privat');
                    break;
                case 'owned':
                    $filterInfo['scope'] = t('Eigene');
                    break;
                case 'public':
                    $filterInfo['scope'] = t('Öffentliche');
                    break;
                case 'reference':
                    $filterInfo['scope'] = t('Referenz');
                    break;
            }
        }

        if (count($filterKeywords)) {
            foreach ($filterKeywords as $keyword) {
                $filterInfo['keyword'][] = $keyword;
            }
        }

        return $filterInfo;
    }
    // End getFilterInfos


    /**
     * Appends the element sheets
     *
     * @param  -
     * @return -
     */
    protected function appendElementSheets(
        DOMNode $Container, ElcaElementSet $Elements, $sheetView = 'Elca\View\ElcaElementSheetView'
    ) {
        $Access = ElcaAccess::getInstance();

        $Ul       = $Container->appendChild(
            $this->getUl(['id' => 'elements-'.$this->page, 'class' => 'elements pageable'])
        );
        $eltCount = $Elements->count();

        if ($eltCount) {

            $hasAssistants = false;
            if ($this->assistantRegistry instanceof ElementAssistantRegistry) {
                if ($this->assistantRegistry->hasAssistantsForElementType($this->elementType, $this->context)) {
                    $hasAssistants = true;
                }
            }

            foreach ($Elements as $index => $Element) {
                if ($index == self::PAGE_LIMIT) {
                    break;
                }

                $Li = $Ul->appendChild($this->getLi());

                $include = $Li->appendChild($this->createElement('include'));
                $include->setAttribute('name', $sheetView);
                $include->setAttribute('itemId', $Element->getId());
                $include->setAttribute('headline', $Element->getName().' ['.$Element->getId().']');
                $include->setAttribute('buildMode', $this->buildMode);
                $include->setAttribute('canEdit', $Access->canEditElement($Element));
                $include->setAttribute('context', $this->context);
                $include->setAttribute('hasAssistants', $hasAssistants);
                $include->setAttribute('readOnly', $this->readOnly);
            }

            /**
             * If there is one more, build next link
             */
            if ($eltCount > self::PAGE_LIMIT && $eltCount % self::PAGE_LIMIT == 1) {
                $Ul->setAttribute('data-next-page-id', 'elements-'.($this->page + 1));
                $Ul->setAttribute('data-next-page-class', 'elements');
                $Ul->setAttribute(
                    'data-next-page-url',
                    $this->action.'?t='.$this->elementTypeNodeId.'&page='.($this->page + 1)
                );

                $Li = $Ul->appendChild($this->getLi(['class' => 'next-page']));
                $Li->appendChild(
                    $this->getA(
                        [
                            'class' => 'next-page',
                            'href'  => $this->action.'?t='.$this->elementTypeNodeId.'&page='.($this->page + 1),
                        ],
                        t('Weitere Einträge laden')
                    )
                );
            }
        } else {
            $Li = $Ul->appendChild($this->getLi());
            $Li->appendChild($this->getText(t('Keine Einträge gefunden')));
        }
    }
    // End appendElementSheets


    /**
     * Append Filter form
     *
     * @param  -
     * @return -
     */
    protected function appendFilterForm(DOMElement $Container)
    {
        $FilterContainer = $Container->appendChild($this->getDiv(['class' => 'sheets-filter']));

        $Form = new HtmlForm('elementFilterForm', $this->action);
        $Form->setAttribute('id', 'elementFilterForm');
        $Form->addClass('filter-form');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->FilterDO);
        $Form->add(new HtmlHiddenField('t', $this->elementTypeNodeId));

        $Filter = $Form->add(new HtmlFormGroup(t('Liste einschränken')));

        $Search = $Filter->add(new ElcaHtmlFormElementLabel(t('Suche'), new HtmlTextInput('search')));
        $Search->addClass('list-search');

        $Select = $Filter->add(new ElcaHtmlFormElementLabel(t('Kataloge'), new HtmlSelectbox('constrCatalogId')));
        $Select->add(new HtmlSelectOption(t('Alle'), ''));
        $ConstrCatalogs = ElcaConstrCatalogSet::find(null, ['name' => 'ASC']);
        foreach ($ConstrCatalogs as $ConstrCatalog) {
            $Select->add(new HtmlSelectOption(t($ConstrCatalog->getName()), $ConstrCatalog->getId()));
        }

        $Select = $Filter->add(new ElcaHtmlFormElementLabel(t('Bauweise'), new HtmlSelectbox('constrDesignId')));
        $Select->add(new HtmlSelectOption(t('Alle'), ''));

        $ConstrDesigns = ElcaConstrDesignSet::find(null, ['name' => 'ASC']);
        foreach ($ConstrDesigns as $ConstrDesign) {
            $Select->add(new HtmlSelectOption(t($ConstrDesign->getName()), $ConstrDesign->getId()));
        }

        $Select = $Filter->add(new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), new HtmlSelectbox('processDbId', null, $this->buildMode === ElcaElementsView::BUILDMODE_SELECT)));
        $Select->add(new HtmlSelectOption(t('Alle'), ''));

        $processDbs = ElcaProcessDbSet::findActive();
        foreach($processDbs as $processDb)
            $Select->add(new HtmlSelectOption(t($processDb->getName()), $processDb->getId()));

        $Radio = $Filter->add(new ElcaHtmlFormElementLabel(t('Typ'), new HtmlRadioGroup('scope')));
        $Radio->add(new HtmlRadiobox(t('Alle'), ''));
        $Radio->add(new HtmlRadiobox(t('Private'), 'private'));

        if (ElcaAccess::getInstance()->hasAdminPrivileges()) {
            $Radio->add(new HtmlRadiobox(t('Eigene'), 'owned'));
        }

        $Radio->add(new HtmlRadiobox(t('Öffentliche'), 'public'));
        $Radio->add(new HtmlRadiobox(t('Referenz'), 'reference'));


        $Filter->add(new ElcaHtmlSubmitButton('refresh', t('Filter aktualisieren'), true));
        $Form->appendTo($FilterContainer);
    }
    // End appendFilter


    /**
     * Appends buttons
     *
     * @param  -
     * @return -
     */
    protected function appendButtons(DOMElement $Container)
    {
        if ($this->buildMode != self::BUILDMODE_DEFAULT) {
            return;
        }

        if ($this->elementType->isCompositeLevel()) {
            $buttonTxt = t('Neue Bauteilvorlage');
        } else {
            $buttonTxt = t('Neue Bauteilkomponentenvorlage');
        }

        $ButtonContainer = $Container->appendChild($this->getDiv(['class' => 'button add']));
        $ButtonContainer->appendChild(
            $this->getA(['href' => '/elements/create/?t='.$this->elementTypeNodeId], '+ '.$buttonTxt)
        );

        if ($this->assistantRegistry->hasAssistantsForElementType($this->elementType, $this->context)) {
            $assistants = $this->assistantRegistry
                ->getAssistantsForElementType($this->elementType, $this->context);

            foreach ($assistants as $assistant) {
                $buttonTxt       = $assistant->getConfiguration()->getCaption();
                $ButtonContainer = $Container->appendChild($this->getDiv(['class' => 'button add assistant']));

                $url = Url::factory(
                    '/elements/create/',
                    [
                        't'         => $this->elementTypeNodeId,
                        'assistant' => $assistant->getConfiguration()->getIdent(),
                    ]
                );
                $ButtonContainer->appendChild($this->getA(['href' => $url], '+ '.$buttonTxt));
            }
        }
    }

    /**
     * @param $filterInfo
     * @param $cssClass
     * @param $iInfo
     */
    protected function appendFilterInfo($filterInfo, $cssClass, $iInfo): void
    {
        $li = $filterInfo->appendChild($this->getLi(['class' => $cssClass]));
        $li->appendChild($this->getSpan($iInfo, ['class' => 'tag-content']));
        $li->appendChild($this->getA(['rel' => 'reset', 'href' => '#', 'class' => 'remove-filter'], 'x'));
    }
    // End appendButtons
}
// End ElcaElementView
