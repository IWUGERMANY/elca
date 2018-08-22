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
namespace Elca\View\ProcessConfig;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTextInput;
use DOMNode;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Security\ElcaAccess;
use Elca\View\ElcaProcessConfigSheetView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds a list of process config sheets
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class SearchView extends HtmlView
{
    /**
     * Page Limit
     */
    const PAGE_LIMIT = 10;

    /**
     * categoryId
     */
    protected $categoryId;

    /**
     * Action url
     */
    protected $action;

    /**
     * Current page
     */
    protected $page = 0;

    /**
     * Filter
     */
    protected $filterDO;

    /**
     * Return only resultList container
     */
    protected $returnResultList = false;

    private $allowEmptySearch;


    /**
     * Init
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->categoryId       = $this->get('categoryId') ?: null;
        $this->filterDO         = $this->get('filterDO', (object)['search' => null]);
        $this->returnResultList = $this->get('returnResultList', false);
        $this->page             = $this->get('page', 0);
        $this->action           = $this->get('action');
        $this->allowEmptySearch = $this->get('allowEmptySearch', true);
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
        $resultContainer = $this->getDiv(['id' => 'elcaSheetsContainer']);

        if (!$this->returnResultList && $this->page === 0) {
            $container = $this->appendChild($this->getDiv(['id' => 'processConfigList', 'class' => 'process-configs']));
            $topRow = $container->appendChild($this->getDiv(['class' => 'sheet-list-top clearfix']));

            /**
             * Filter
             */
            $this->appendFilterForm($topRow);

            /**
             * Buttons
             */
            if ($this->categoryId && ElcaAccess::getInstance()->hasAdminPrivileges()) {
                $buttonContainer = $topRow->appendChild($this->getDiv(['class' => 'button add']));
                $buttonContainer->appendChild($this->getA(['class' => 'no-history', 'href' => '/processes/create/?c=' . $this->categoryId], '+ ' . t('Neue Baustoffkonfiguration erstellen')));
            }

            $container->appendChild($resultContainer);
        } elseif ($this->returnResultList) {
            $this->appendChild($resultContainer);
        }
        else {
            $resultContainer = $this;
        }

        $filterKeywords = preg_split("/[\s,]+/", $this->filterDO->search, -1, PREG_SPLIT_NO_EMPTY);

        $processConfigs = $this->getProcessConfigSet($filterKeywords);

        $this->appendFilterInfos($resultContainer, $filterKeywords);

        $ul = $resultContainer->appendChild($this->getUl(['id' => 'process-configs-' . $this->page, 'class' => 'process-configs pageable']));
        $pCount = $processConfigs->count();

        if ($pCount) {
            /**
             * @var ElcaProcessConfig $processConfig
             */
            foreach ($processConfigs as $index => $processConfig) {
                if ($index == self::PAGE_LIMIT)
                    break;

                $li = $ul->appendChild($this->getLi(['id' => 'process-config-' . $processConfig->getId()]));

                $include = $li->appendChild($this->createElement('include'));
                $include->setAttribute('name', ElcaProcessConfigSheetView::class);
                $include->setAttribute('itemId', $processConfig->getId());
                $include->setAttribute('headline', $processConfig->getName());
                $include->setAttribute('readOnly', $this->get('readOnly'));
                $include->setAttribute('isReference', $processConfig->isReference());
                $include->setAttribute('backReference', null === $this->categoryId ? 'search' : '');

                if (null === $this->categoryId) {
                    $elcaProcessCategory = $processConfig->getProcessCategory();
                    $include->setAttribute('subheadline', $elcaProcessCategory->getRefNum() .' '. $elcaProcessCategory->getName());
                }
            }

            /**
             * If there is one more, build next link
             */
            if ($pCount > self::PAGE_LIMIT && $pCount % self::PAGE_LIMIT == 1) {
                $ul->setAttribute('data-next-page-id', 'process-configs-' . ($this->page + 1));
                $ul->setAttribute('data-next-page-class', 'process-configs');
                $ul->setAttribute('data-next-page-url', $this->action . '?c=' . $this->categoryId . '&page=' . ($this->page + 1));

                $li = $ul->appendChild($this->getLi(['class' => 'next-page']));
                $li->appendChild($this->getA(['class' => 'next-page', 'href' => $this->action . '?c=' . $this->categoryId . '&page=' . ($this->page + 1)], t('Weitere Einträge laden')));
            }
        } elseif ($this->allowEmptySearch) {
            $li = $ul->appendChild($this->getLi());
            $li->appendChild($this->getText(t('Keine Einträge gefunden')));
        }
    }
    // End beforeRender


    /**
     * Appends the filter info container
     *
     * @param  -
     *
     * @return -
     */
    protected function appendFilterInfos(DOMNode $container, array $filterKeywords)
    {
        if ($container === $this)
            return;

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
        } else
            $filterInfo->appendChild($this->getLi(['class' => 'label'], t('Keine Einschränkungen')));
    }
    // End appendFilterInfos


    /**
     * Returns filter infos
     *
     * @param  -
     *
     * @return array
     */
    protected function getFilterInfos(array $filterKeywords)
    {
        $filterInfo = [];

        if (count($filterKeywords)) {
            foreach ($filterKeywords as $keyword)
                $filterInfo['keyword'][] = $keyword;
        }

        return $filterInfo;
    }
    // End getFilterInfos


    /**
     * Append Filter form
     *
     * @param  -
     *
     * @return -
     */
    protected function appendFilterForm(DOMNode $container)
    {
        $filterContainer = $container->appendChild($this->getDiv(['class' => 'sheets-filter']));

        $form = new HtmlForm('processConfigFilterForm', $this->action);
        $form->setAttribute('id', 'processConfigFilterForm');
        $form->addClass('filter-form');
        $form->setRequest(FrontController::getInstance()->getRequest());
        $form->setDataObject($this->filterDO);
        $form->add(new HtmlHiddenField('c', $this->categoryId));

        $filter = $form->add(new HtmlFormGroup(
            t($this->allowEmptySearch ? 'Liste einschränken' : 'Baustoffe finden' )
        ));

        $search = $filter->add(new ElcaHtmlFormElementLabel(t('Stichwörter'), new HtmlTextInput('search')));
        $search->addClass('list-search');

        $filter->add(new ElcaHtmlSubmitButton('refresh', t('Filter aktualisieren'), true));
        $form->appendTo($filterContainer);
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

    /**
     * @param $filterKeywords
     * @return \Elca\Db\ElcaElementSet|ElcaProcessConfigSet
     */
    private function getProcessConfigSet($filterKeywords)
    {
        $filter = ['is_stale' => false];

        if ($this->categoryId) {
            $filter['process_category_node_id'] = $this->categoryId;
        }

        if (count($filterKeywords)) {
            $processConfigSet = ElcaProcessConfigSet::searchExtended(
                $filterKeywords,
                $filter,
                ['name' => 'ASC'],
                self::PAGE_LIMIT + 1,
                $this->page * self::PAGE_LIMIT
            );
        } elseif ($this->allowEmptySearch) {
            $processConfigSet = ElcaProcessConfigSet::find(
                $filter,
                array('name' => 'ASC'),
                self::PAGE_LIMIT + 1,
                $this->page * self::PAGE_LIMIT
            );
        }
        else {
            $processConfigSet = new ElcaProcessConfigSet();
        }

        return $processConfigSet;
    }
    // End appendFilterForm
}
// End ElcaProcessConfigView
