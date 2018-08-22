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
namespace Elca\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\File;
use Beibob\Blibs\Session;
use Beibob\Blibs\SessionNamespace;
use Beibob\Blibs\Url;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessCategorySet;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaSvgPattern;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaAdminSvgPatternAssignmentsView;
use Elca\View\ElcaAdminSvgPatternsView;
use Elca\View\ElcaAdminSvgPatternView;
use Exception;

/**
 * Admin svg patterns
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class AdminSvgPatternsCtrl extends AppCtrl
{
    /** @var SessionNamespace $Namespace */
    protected $Namespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->Access->hasAdminPrivileges())
            $this->noAccessRedirect('/');

        $this->Namespace = $this->Session->getNamespace('elca.admin-svg-patterns', Session::SCOPE_PERSISTENT);
    }
    // End init


    /**
     * default action
     */
    protected function defaultAction()
    {
        $this->listAction(true);
    }
    // End defaultAction


    /**
     * list action
     */
    protected function listAction($addNavigationViews = true)
    {
        if (!$this->isAjax())
            return;

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->addNavigationView();
            $this->Osit->add(new ElcaOsitItem(t('Alle Schraffuren'), null, t('Verwaltung')));
        }

        $View = $this->addView(new ElcaAdminSvgPatternsView());
    }
    // End listAction


    /**
     * Edits a svg pattern
     */
    protected function editAction($addNavigationViews = true, $svgPatternId = null, Validator $Validator = null)
    {
        if (!$this->isAjax())
            return;



        $View = $this->setView(new ElcaAdminSvgPatternView());
        $View->assign('Validator', $Validator);
        $View->assign('Data', $SvgPattern = ElcaSvgPattern::findById($svgPatternId ? $svgPatternId : $this->Request->id));

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->addNavigationView('list');
            $this->Osit->add(new ElcaOsitItem(t('Alle Schraffuren'), '/elca/admin-svg-patterns/list/', t('Verwaltung')));
            $this->Osit->add(new ElcaOsitItem($SvgPattern->getName(), null, t('Schraffur')));
        }
    }
    // End editAction


    /**
     * save action
     */
    protected function saveAction()
    {
        if (!$this->isAjax())
            return;

        if ($this->Request->has('save') || $this->Request->has('insert')) {
            $Validator = new ElcaValidator($this->Request);
            $Validator->assertNotEmpty('name', null, t('Dieses Feld ist ein Pflichtfeld'));

            if (File::uploadFileExists('uploadFile'))
                $Validator->assertTrue('uploadFile', in_array($_FILES['uploadFile']['type'], ElcaSvgPattern::$validMimetypes), t('Bitte wählen Sie eine Grafikdatei im PNG-, GIF-, JPEG- oder SVG-Format'));

            if ($Validator->isValid()) {
                $nameChanged = false;

                if ($this->Request->has('save'))
                {
                    $SvgPattern = ElcaSvgPattern::findById($this->Request->id);

                    $nameChanged = $SvgPattern->getName() != $this->Request->name;

                    $SvgPattern->setName($this->Request->name);
                    $SvgPattern->setDescription($this->Request->description);
                    $SvgPattern->update();
                    $this->messages->add(t('Die Schraffur wurde gespeichert.'), ElcaMessages::TYPE_NOTICE);
                }
                else
                {
                    $SvgPattern = ElcaSvgPattern::create($this->Request->name, 0, 0, null, $this->Request->description);
                    $this->messages->add(t('Die Schraffur wurde erstellt.'), ElcaMessages::TYPE_NOTICE);
                }

                if (File::uploadFileExists('uploadFile'))
                {
                    if ($this->Request->has('save'))
                        ElcaElementSet::clearSvgPatternCacheByPatternId($SvgPattern->getId());

                    $SvgPattern->setImageByUpload('uploadFile');
                }

                /**
                 * Update action and osit view
                 */
                $this->Response->setHeader('X-Update-Hash: /elca/admin-svg-patterns/edit/?id=' . $SvgPattern->getId());
                $Validator = null;

                $this->editAction($nameChanged, $SvgPattern->getId());
            } else {
                foreach ($Validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }
        elseif ($this->Request->has('cancel')) {
            $this->Response->setHeader('X-Update-Hash: /elca/admin-svg-patterns/list/');
            $this->listAction();
        }
    }
    // End saveAction



    /**
     * Delete
     */
    protected function deleteAction()
    {
        if (!is_numeric($this->Request->id))
            return;

        $SvgPattern = ElcaSvgPattern::findById($this->Request->id);

        if ($this->Request->has('confirmed')) {

            /**
             * Clear SVG-Cache
             */
            ElcaElementSet::clearSvgPatternCacheByPatternId($SvgPattern->getId());

            /** Delete pattern */
            $SvgPattern->delete();

            /**
             * Forward to list
             */
            $this->listAction(null, false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(t('Soll das Pattern wirklich gelöscht werden?'), ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteSystemAction


    /**
     * assignments action
     */
    protected function assignmentsAction($addNavigationViews = true)
    {
        if (!$this->isAjax())
            return;

        $openedNodes = isset($this->Namespace->openedNodes)? $this->Namespace->openedNodes : [];

        $View = $this->setView(new ElcaAdminSvgPatternAssignmentsView());
        $Data = $View->assign('Data', new \stdClass());
        $View->assign('openedNodes', $openedNodes);

        if ($this->Request->has('nodeId')) {
            $nodeId = $this->Request->nodeId;
            $nodeChangeToPatternId = $this->Request->get('nodeChangedToPatternId');

            $View->assign('processCategoryNodeId', $nodeId);
            $View->assign('buildMode', ElcaAdminSvgPatternAssignmentsView::BUILDMODE_PROCESS_CONFIGS);
            $View->assign('nodeChangeToPatternId', $nodeChangeToPatternId);

            $openedNodes[$nodeId] = true;
            $this->Namespace->openedNodes = $openedNodes;

            $Data->configPatternId = [];

            /** @var ElcaProcessConfig $ProcessConfig */
            foreach (ElcaProcessConfigSet::find(['process_category_node_id' => $nodeId], ['name' => 'ASC']) as $ProcessConfig) {
                $Data->configPatternId[$ProcessConfig->getId()] = $ProcessConfig->getSvgPatternId() ?? $nodeChangeToPatternId;
            }
            $addNavigationViews = false;
        } else {
            $Data->categoryPatternId = $Data->configPatternId = [];
            $Categories = ElcaProcessCategorySet::findByParent(ElcaProcessCategory::findRoot());
            /** @var ElcaProcessCategory $Category */
            foreach ($Categories as $Category) {
                foreach (ElcaProcessCategorySet::findByParent($Category) as $SubCategory) {
                    $Data->categoryPatternId[$nodeId = $SubCategory->getNodeId()] = $SubCategory->getSvgPatternId();

                    /** add process configs for opened nodes */
                    if (isset($openedNodes[$nodeId]) && $openedNodes[$nodeId]) {
                        /** @var ElcaProcessConfig $ProcessConfig */
                        foreach (ElcaProcessConfigSet::find(['process_category_node_id' => $nodeId], ['name' => 'ASC']) as $ProcessConfig) {
                            $Data->configPatternId[$ProcessConfig->getId()] = $ProcessConfig->getSvgPatternId();
                        }
                    }
                }
            }
        }

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $this->addNavigationView('assignments');
            $this->Osit->add(new ElcaOsitItem(t('Zuordnungen'), null, t('Schraffuren')));
        }
    }
    // End assignmentsAction


    /**
     * toggleCategory action
     */
    protected function toggleCategoryAction()
    {
        if (!$this->isAjax() || !$this->Request->has('nodeId'))
            return;

        $nodeId = $this->Request->nodeId;
        $openedNodes = isset($this->Namespace->openedNodes)? $this->Namespace->openedNodes : [];
        $openedNodes[$nodeId] = $this->Request->get('state', false);

        if (!$openedNodes[$nodeId]) {
            foreach (ElcaProcessCategorySet::findByParent(ElcaProcessCategory::findByNodeId($nodeId)) as $Category) {
                unset($openedNodes[$Category->getNodeId()]);
            }
        }

        $this->Namespace->openedNodes = $openedNodes;
    }
    // End toggleCategoryAction


    /**
     * Saves assignments
     */
    protected function saveAssignmentsAction()
    {
        if(!$this->isAjax())
            return;
        if ($this->Request->has('save')) {
            $categoryPatternIds = $this->Request->getArray('categoryPatternId');
            $configPatternIds = $this->Request->getArray('configPatternId');

            $Dbh = DbHandle::getInstance();

            $updatedProcessCategoryIds = [];
            $updatedProcessConfigIds = [];
            try {
                $Dbh->begin();

                foreach ($categoryPatternIds as $categoryNodeId => $svgPatternId) {
                    $ProcessCategory = ElcaProcessCategory::findByNodeId($categoryNodeId);

                    if ($ProcessCategory->getSvgPatternId() != $svgPatternId)
                    {
                        $ProcessCategory->setSvgPatternId($svgPatternId);
                        $ProcessCategory->update();
                        $updatedProcessCategoryIds[$ProcessCategory->getNodeId()] = true;
                    }
                }

                foreach ($configPatternIds as $processConfigId => $svgPatternId) {
                    $ProcessConfig = ElcaProcessConfig::findById($processConfigId);
                    $categorySvgPatternId = $ProcessConfig->getProcessCategory()->getSvgPatternId();

                    if ($ProcessConfig->getSvgPatternId() != $svgPatternId)
                    {
                        $ProcessConfig->setSvgPatternId(!$svgPatternId || $svgPatternId == $categorySvgPatternId? null : $svgPatternId);
                        $ProcessConfig->update();
                        $updatedProcessConfigIds[$ProcessConfig->getId()] = true;
                    }
                }

                $Dbh->commit();

                /**
                 * Clear svg-cache
                 */
                if (count($updatedProcessConfigIds) + count($updatedProcessCategoryIds) > 0)
                {
                    if (count($updatedProcessCategoryIds))
                    {
                        foreach ($updatedProcessCategoryIds as $id => $foo)
                        {
                            foreach (ElcaProcessConfigSet::findByProcessCategoryNodeId($id) as $ProcessConfig)
                                $updatedProcessConfigIds[$ProcessConfig->getId()] = true;
                        }
                    }

                    if (count($updatedProcessConfigIds))
                        ElcaElementSet::clearSvgPatternCacheByProcessConfigIds(array_keys($updatedProcessConfigIds));
                }


                $this->messages->add(t('Die Zuordnungen wurden gespeichert.'));

            } catch(Exception $Exception) {
                fb($Exception);
                $Dbh->rollback();
                throw $Exception;
            }
        }

        $this->assignmentsAction(false);
    }
    // End saveAssignmentsAction

    /**
     *
     */
    protected function createAction()
    {
        if (!$this->isAjax())
            return;

        $this->setView(new ElcaAdminSvgPatternView());
    }
    // End createAction



    /**
     * Adds the navigation view
     *
     * @param null $action
     * @return void
     */
    private function addNavigationView($action = null)
    {
        // set active controller in navigation
        $NavView = $this->addView(new ElcaAdminNavigationLeftView());
        $NavView->assign('activeCtrlName', $this->ident());
        $NavView->assign('activeCtrlAction', $action ? $action : $this->getAction());
    }
    // End addNavigationView

}
// End AdminSvgPatternsCtrl
