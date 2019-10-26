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
namespace Soda4Lca\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Log;
use Beibob\Blibs\StringFactory;
use Beibob\Blibs\Url;
use DateTime;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProcessConfigSanity;
use Elca\Db\ElcaProcessConfigSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProjectSet;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\ProcessConfig\Conversions;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaModalProcessingView;
use Exception;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaProcessSet;
use Soda4Lca\Model\Import\Soda4LcaImporter;
use Soda4Lca\Model\Import\Soda4LcaParser;
use Soda4Lca\View\Soda4LcaDatabasesView;
use Soda4Lca\View\Soda4LcaDatabaseView;

/**
 * Soda4Lca databases admin section
 *
 * @package    soda4lca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class DatabasesCtrl extends AppCtrl
{
    /**
     * Namespace
     */
    private $Namespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Session namespace
         */
        $this->Namespace = $this->Session->getNamespace('soda4lca.'. $this->ident(), true);
    }
    // End init


    /**
     * Default action
     *
     * @param -
     * @return -
     */
    protected function defaultAction($importId = null, $addNavigationViews = true)
    {
        if(!$this->isAjax())
            return;

        if(!$this->Access->hasAdminPrivileges())
            return;

        $importId = $importId? $importId : $this->getAction();

        if(is_numeric($importId))
        {
            $Import = Soda4LcaImport::findById($importId);
            $ProcessDb = $Import->getProcessDb();

            $Data = new \stdClass();
            foreach(['id', 'status', 'dateOfImport', 'dataStock'] as $property)
                $Data->$property = $Import->$property;

            if($DateOfImport = $Data->dateOfImport? new DateTime($Data->dateOfImport) : null)
                $Data->dateOfImport = $DateOfImport->format('d.m.Y, H:i');

            foreach(['name', 'version', 'uuid', 'sourceUri', 'isActive'] as $property)
                $Data->$property = $ProcessDb->$property;

            $Data->sourceUriShort = StringFactory::stringMidCut($Data->sourceUri, 50, '...', 20);

            if($Import->getStatus() == Soda4LcaImport::STATUS_INIT)
            {
                $DataStockDO = Soda4LcaParser::getInstance()->getDataStock($ProcessDb->getUuid());
                $Data->numberOfProcesses = $DataStockDO->totalSize;
            }
            else
                $Data->numberOfProcesses = Soda4LcaProcessSet::dbCount(['import_id' => $importId]);

            $Data->numberOfImportedProcesses = Soda4LcaProcessSet::dbCountImported($importId);
            $Data->numberOfProjects  = ElcaProjectSet::dbCount(['process_db_id' => $ProcessDb->getId()]);

            $View = $this->addView(new Soda4LcaDatabaseView());
            $View->assign('Data', $Data);
            $View->assign('FilterDO', $this->getFilterDO('reportFilter', ['status' => '']));

            /**
             * Add navigation
             */
            if($addNavigationViews)
            {
                $this->Osit->add(new ElcaOsitItem(t('Datenbanken'), '/soda4Lca/databases/', t('Ökobau.dat')));
                $this->Osit->add(new ElcaOsitItem($ProcessDb->getName(), null, t('Ökobau.dat')));
                // set active controller in navigation
                $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', $this->ident());
            }
        }
        else
        {
            $this->setView(new Soda4LcaDatabasesView());

            /**
             * Add navigation
             */
            if($addNavigationViews)
            {
                $this->Osit->add(new ElcaOsitItem(t('Datenbanken'), null, t('Ökobau.dat')));
                // set active controller in navigation
                $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', $this->ident());
            }
        }

    }
    // End defaultAction


    /**
     * Create action
     *
     * @param -
     * @return -
     */
    protected function createAction()
    {
        if(!$this->isAjax())
            return;

        $View = $this->addView(new Soda4LcaDatabaseView());
        $View->assign('buildMode', Soda4LcaDatabaseView::BUILDMODE_NEW);

        $this->Osit->add(new ElcaOsitItem(t('Datenbanken'), '/soda4Lca/databases/', t('Soda4LCA')));
        $this->Osit->add(new ElcaOsitItem(t('Neue Datenbank importieren'), null, t('Soda4LCA')));
    }
    // End createAction


    /**
     * Save action
     *
     * @param -
     * @return -
     */
    protected function saveAction()
    {
        if(!$this->Request->isPost())
            return;

        /**
         * Check if user is allowed to save
         */
        if(!$this->Access->hasAdminPrivileges())
            return;

        if($this->Request->has('save'))
        {
            $Import = Soda4LcaImport::findById($this->Request->importId);

            $View = $this->addView(new Soda4LcaDatabaseView());
            $View->assign('buildMode', Soda4LcaDatabaseView::BUILDMODE_NEW);
            $View->assign('Validator', $Validator = new ElcaValidator($this->Request));

            if($Import->isInitialized()) {
                $Validator->assertNotEmpty('name', null, t('Bitte geben Sie einen Namen ein'));
                $Validator->assertNotEmpty('version', null, t('Bitte geben Sie eine kurze Versionsbezeichnung ein'));
                $Validator->assertMaxLength('version', 50, null, t('Die Versionsbezeichnung darf nicht länger als 50 Zeichen sein'));
            }

            if($Validator->isValid())
            {
                $ProcessDb = $Import->getProcessDb();

                try
                {
                    $Dbh = DbHandle::getInstance();
                    $Dbh->begin();
                    if($Import->isInitialized())
                    {
                        $ProcessDb->setName($this->Request->name);
                        $ProcessDb->setVersion($this->Request->version);
                        $ProcessDb->setIsActive($this->Request->has('isActive'));
                        $ProcessDb->update();
                    }
                    else
                    {
                        if($DataStockDO = Soda4LcaParser::getInstance()->getDataStock($this->Request->uuid? $this->Request->uuid : null))
                        {
                            $ProcessDb = ElcaProcessDb::create($DataStockDO->name? $DataStockDO->name : $DataStockDO->shortName,
                                                               null, // version, not available
                                                               $DataStockDO->uuid,
                                                               $DataStockDO->sourceUri,
                                                               false, // is active
                                                               true // is always EN 15804 compliant
                                                              );

                            $Import = Soda4LcaImport::create($ProcessDb->getId(), Soda4LcaImport::STATUS_INIT, null, $DataStockDO->name? $DataStockDO->name : $DataStockDO->shortName);

                            $this->Response->setHeader('X-Update-Hash: /soda4Lca/databases/'. $Import->getId() .'/');
                        }
                    }

                    $Validator = null;
                    $Dbh->commit();

                    $this->defaultAction($Import->getId());
                }
                catch(Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }
            }
            else
                foreach($Validator->getErrors() as $property => $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
        }
    }
    // End saveAction


    /**
     * Import action
     *
     * @param -
     * @return -
     */
    protected function importAction()
    {
        if(!is_numeric($this->Request->importId))
            return;

        /**
         * Check if user is allowed to import
         */
        if(!$this->Access->hasAdminPrivileges())
            return;

        $Import = Soda4LcaImport::findById($this->Request->importId);
        $ProcessDb = $Import->getProcessDb();

        if($this->Request->isPost() && $this->Request->has('import'))
        {
            $View = $this->addView(new ElcaModalProcessingView());
            $View->assign('action', '/soda4Lca/databases/import/?importId='. $Import->getId());
            $View->assign('reload', true);
            $View->assign('headline', t('Datenimport'));
            $View->assign('description', t('Es werden die Datensätze von "%sourceUri%" in die lokale Datenbank "%processName%" importiert.', null, ['%sourceUri%' => $ProcessDb->getSourceUri(), '%processName%' => $ProcessDb->getName()]));
        }
        elseif($this->Request->isPost() && $this->Request->has('retrySkipped'))
        {
            $View = $this->addView(new ElcaModalProcessingView());
            $View->assign('action', '/soda4Lca/databases/import/?importId='. $Import->getId());
            $View->assign('reload', true);
            $View->assign('headline', t('Datenimport'));
            $View->assign('description', t('Es wird versucht, die nicht importierten Datensätze von "%sourceUri%" in die lokale Datenbank "%processName%" zu importieren.', null, ['%sourceUri%' => $ProcessDb->getSourceUri(), '%processName%' => $ProcessDb->getName()]));
        }
        elseif ($this->Request->isGet() && $this->Request->has('checkVersions'))
        {
            $View = $this->addView(new ElcaModalProcessingView());
            $url = Url::factory('/soda4Lca/databases/import/', ['importId' => $Import->getId(), 'check' => true]);
            $View->assign('action', (string) $url);
            $View->assign('reload', true);
            $View->assign('headline', t('Suche nach neuen Versionen'));
            $View->assign('description', t('Es wird auf %sourceUri% nach neuen Versionen der Datensätze gesucht.', null, ['%sourceUri%' => $ProcessDb->getSourceUri()]));
        }
        else
        {
            try
            {
                $Importer = new Soda4LcaImporter($Import, $this->get(Conversions::class));
                $totalSize = null;

                switch($Import->getStatus())
                {
                    case Soda4LcaImport::STATUS_DONE:
                        if ($this->Request->has('check'))  {
                            $Importer->checkProcessesVersions($Import->getProcessDb()->getUuid());
                        }
                        else
                            $Importer->retrySkippedProcesses();
                        break;
                    case Soda4LcaImport::STATUS_INIT:
                        $Importer->importProcesses($ProcessDb->getUuid());
                        break;
                }

                /**
                 * Refresh sanity check of process configs
                 */
                ElcaProcessConfigSanity::refreshEntries();
            }
            catch(Exception $Exception)
            {
                Log::getInstance()->error($Exception->getMessage(), __METHOD__);
                throw $Exception;
            }
        }
    }
    // End importAction

    
    /**
     * Deletes an imported database
     *
     * @return
     */
    protected function deleteAction()
    {
        if(!is_numeric($this->Request->id))
            return false;

        /**
         * Check if user is allowed to delete
         */
        if(!$this->Access->hasAdminPrivileges())
            return false;

        $Import = Soda4LcaImport::findById($this->Request->id);

        $projectCount = ElcaProjectSet::dbCount(['process_db_id' => $Import->getProcessDbId()]);

        if($projectCount)
        {
            $projectCount = $projectCount == 1? t('einem') : $projectCount;
            $projects = $projectCount > 1 ? t('Projekten') : t('Projekt');
            $msg = t("Die Datenbank wird in %count% %projects% verwendet und kann nicht gelöscht werden.", null, ['%count%' => $projectCount, '%projects%' => $projects]);
            $this->messages->add($msg, ElcaMessages::TYPE_INFO);
            return false;
        }

        if($this->Request->has('confirmed'))
        {
            if($Import->isInitialized())
            {
                try
                {
                    $Dbh = DbHandle::getInstance();
                    $Dbh->begin();
                    $ProcessDb = $Import->getProcessDb();

                    Log::getInstance()->notice('Deleting ProcessDb '. $ProcessDb->getName() .' ['. $ProcessDb->getUuid().']', __METHOD__);
                    $ProcessDb->delete();

                    /**
                     * Delete stale process configs
                     */
                    foreach(ElcaProcessConfigSet::findStaleWithoutAssignments() as $ProcessConfig)
                    {
                        Log::getInstance()->notice('Deleting stale ProcessConfig '. $ProcessConfig->getName(), __METHOD__);
                        $ProcessConfig->delete();
                    }

                    $Dbh->commit();
                    $this->messages->add(t('Der Datensatz wurde gelöscht'));
                    $this->defaultAction();
                }
                catch(Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }

                return true;
            }
        }
        else
        {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $confirmMsg = t('Soll die Datenbank :dbName: wirklich gelöscht werden?', null, [':dbName:' => $Import->getProcessDb()->getName()]);
            $this->messages->add($confirmMsg, ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }

        return false;
    }
    // End deleteAction


    /**
     * Import action
     *
     * @param -
     * @return -
     */
    protected function updateEpdTypeAction()
    {
        if (!is_numeric($this->Request->importId)) {
            return;
        }

        /**
         * Check if user is allowed to import
         */
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $Import = Soda4LcaImport::findById($this->Request->importId);

        try
        {
            $Importer = new Soda4LcaImporter($Import, $this->get(Conversions::class));
            $Importer->updateProcessEpdSubType();
        }
        catch(Exception $Exception)
        {
            Log::getInstance()->error($Exception->getMessage(), __METHOD__);
            throw $Exception;
        }
    }

    protected function updateGeographicalRepresentativenessAction()
    {
        if (!is_numeric($this->Request->importId)) {
            return;
        }

        /**
         * Check if user is allowed to import
         */
        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $Import = Soda4LcaImport::findById($this->Request->importId);

        try
        {
            $Importer = new Soda4LcaImporter($Import, $this->get(Conversions::class));
            $Importer->updateProcessGeographicalRepresentativeness();
        }
        catch(Exception $Exception)
        {
            Log::getInstance()->error($Exception->getMessage(), __METHOD__);
            throw $Exception;
        }
    }

    /**
     * Default action
     *
     * @param -
     * @return -
     */
    protected function filterAction()
    {
        if(!$this->Request->isPost() || !$this->Request->has('importId'))
            return;

        $Data = new \stdClass();
        $Data->id = $this->Request->importId;

        $View = $this->addView(new Soda4LcaDatabaseView());
        $View->assign('buildMode', Soda4LcaDatabaseView::BUILDMODE_REPORT);
        $View->assign('Data', $Data);
        $View->assign('FilterDO', $this->getFilterDO('reportFilter', ['status' => '']));

    }
    // End filterAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a filter data object form request or session
     *
     * @param  string $key
     * @return object
     */
    protected function getFilterDO($key, array $defaults = [])
    {
        if(!$filterDOs = $this->Namespace->filterDOs)
            $filterDOs = [];

        $FilterDO = isset($filterDOs[$key])? $filterDOs[$key] : new \stdClass();

        foreach($defaults as $name => $defaultValue)
            $FilterDO->$name = $this->Request->has($name)? $this->Request->get($name) : (isset($FilterDO->$name)? $FilterDO->$name : $defaultValue);

        $filterDOs[$key] = $FilterDO;

        $this->Namespace->filterDOs = $filterDOs;

        return $FilterDO;
    }
    // End getFilterDO

}
// End DatabasesCtrl
