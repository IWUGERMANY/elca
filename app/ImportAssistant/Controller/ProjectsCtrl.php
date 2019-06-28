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

declare(strict_types=1);

namespace ImportAssistant\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\SessionNamespace;
use Elca\Controller\AppCtrl;
use Elca\Controller\ProjectDataCtrl;
use Elca\Controller\ProjectsCtrl as ElcaProjectsCtrl;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProcessConfigSelectorView;
use ImportAssistant\Model\Generator\ProjectGenerator;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\Importer;
use ImportAssistant\Model\ImportException;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfoRepository;
use ImportAssistant\Model\Validator;
use ImportAssistant\View\ProjectImportView;
use ImportAssistant\View\ProjectPreviewView;

class ProjectsCtrl extends AppCtrl
{
    const CONTEXT = 'importAssistant/projects';

    /**
     * @var SessionNamespace
     */
    private $sessionNamespace;

    private $activeTab;

    /**
     *
     */
    public function importAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $processDbId = (int) ($this->Request->get('processDbId') ?? ElcaProcessDb::findMostRecentVersion(true)->getId());

        $validator = null;
        if ($this->Request->isPost() && $this->Request->has('upload')) {
            $this->sessionNamespace->freeData();

            $validator = new ElcaValidator($this->Request);
            $validator->assertTrue(
                'projects',
                $this->Access->canCreateProject(),
                t('Sie können nur %limit% Projekte anlegen', null, ['%limit%' => $this->Elca->getProjectLimit()])
            );

            if ($validator->isValid()) {
                $validator->assertTrue(
                    'importFile',
                    File::uploadFileExists('importFile'),
                    t('Bitte geben Sie eine Datei für den Import an!')
                );

                if (isset($_FILES['importFile'])) {
                    $validator->assertTrue(
                        'importFile',
                        preg_match('/\.xml$/iu', (string)$_FILES['importFile']['name']),
                        t('Bitte nur XML Dateien importieren')
                    );
                }
            }

            if ($validator->isValid()) {
                $config = $this->get(Environment::class)->getConfig();
                if (isset($config->tmpDir)) {
                    $baseDir = $config->toDir('baseDir');
                    $tmpDir  = $baseDir.$config->toDir('tmpDir', true);
                } else {
                    $tmpDir = '/tmp';
                }

                $file = File::fromUpload('importFile', $tmpDir);

                $docRootPath = $config->toDir('docRoot');
                $xsdPath     = $docRootPath.'docs/EnEV/2017/';
                $importer    = new ImporterV1(new MaterialMappingInfoRepository(), $xsdPath, $processDbId);

                try {
                    if (!$project = $importer->fromFile($file)) {
                        throw new \RuntimeException(t('Kein Projekt in Importdatei vorhanden'));
                    }

                    $this->sessionNamespace->project = $project;

                    $this->loadHashUrl($this->getActionLink('preview'));

                    return;
                }
                catch (ImportException $exception) {
                    $this->messages->add(t($exception->messageTemplate(), null, $exception->parameters()), ElcaMessages::TYPE_ERROR);
                }
                catch (\Exception $exception) {
                    $this->messages->add($exception->getMessage(), ElcaMessages::TYPE_ERROR);
                }


            } else {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        } elseif ($this->Request->isPost() && $this->Request->has('cancel')) {
            $this->sessionNamespace->freeData();

            $this->redirect(ElcaProjectsCtrl::class);
            return;
        }

        $view = $this->setView(new ProjectImportView());
        $view->assign('context', self::CONTEXT);

        if (null !== $validator) {
            $view->assign('Validator', $validator);
        }

        $this->Osit->add(new ElcaOsitItem(t('Projektimport Assistent')));
    }

    /**
     *
     */
    public function previewAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (!($project = $this->sessionNamespace->project) ||
            ($this->Request->isPost() && $this->Request->has('cancel'))
        ) {
            $this->Response->setHeader('X-Redirect: '.$this->getActionLink('import'));

            return;
        }

        $view = $this->setView(new ProjectPreviewView());
        $view->assign('context', self::CONTEXT);
        $view->assign('project', $project);
        $view->assign('data', $this->getFormDataForProject($project));
        $view->assign('activeTab', $this->activeTab);

        if ($this->Request->isPost() && $this->Request->has('createProject')) {
            $validator = new Validator($this->Request);

            if ($validator->isValid()) {

                $generator   = new ProjectGenerator($this->get(DbHandle::class), $this->Access);
                $elcaProject = $generator->generate($project);

                $this->container->get(LifeCycleUsageService::class)
                                ->updateForProject($elcaProject);


                $this->messages->add(
                    t('Projekt %name% wurde erfolgreich importiert', null, ['%name%' => $elcaProject->getName()])
                );

                $View = $this->addView(new ElcaModalProcessingView());
                $View->assign(
                    'action',
                    $this->getLinkTo(
                        ProjectDataCtrl::class,
                        'lcaProcessing',
                        [
                            'id' => $elcaProject->getId(),
                            'redirect' => $this->getLinkTo(ElcaProjectsCtrl::class, $elcaProject->getId()),
                        ]
                    )
                );
                $View->assign('headline', t('Neuberechnung nach Import erforderlich'));

                $this->sessionNamespace->freeData();
                //$this->Response->setHeader('X-Redirect: '.$this->getLinkTo(ElcaProjectsCtrl::class));

                return;

            } else {
                $this->messages->add(
                    t('Für die gelb markierten Zeilen wurde noch kein eLCA Baustoff gewählt'),
                    ElcaMessages::TYPE_ERROR
                );
            }
        }

        $this->Osit->add(new ElcaOsitItem(t('Projektimport Assistent'), '/importAssistant/projects/import/', null, null, false));
        $this->Osit->add(new ElcaOsitItem($project->name(), null, 'Projektimport Assistent'));
    }

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->isAjax()) {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
        }

        $this->sessionNamespace = $this->Session->getNamespace($this->ident(), true, 3600);

        if ($this->Request->has('activeTab')) {
            $this->sessionNamespace->activeTab = $this->Request->activeTab;
        }

        $this->activeTab = $this->sessionNamespace->activeTab;
    }

    /**
     * Action selectProcessConfig
     */
    protected function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        $applyToAll = isset($this->Request->applyAll);
        $apply      = isset($this->Request->select);

        if (isset($this->Request->term)) {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $inUnit   = $this->Request->has('u') ? $this->Request->get('u') : null;

            /**
             * @todo: modify for buildmode operation
             */
            switch ($this->Request->b) {
                case ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY:
                    $Results = ElcaProcessConfigSearchSet::findFinalEnergySuppliesByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        $this->Request->db
                    );
                    break;

                default:
                    $Results = ElcaProcessConfigSearchSet::findByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        [$this->Request->db],
                        null,
                        $this->Request->epdSubType
                    );
            }

            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name.' > '.$Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        } /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!$apply && !$applyToAll) {
            $view = $this->setView(new ElcaProcessConfigSelectorView());
            $view->assign('db', $this->Request->db);
            $view->assign(
                'processConfigId',
                $this->Request->sp ? $this->Request->sp : ($this->Request->id ? $this->Request->id : $this->Request->p)
            );
            $view->assign('elementId', $this->Request->elementId);
            $view->assign('relId', $this->Request->relId);
            $view->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('context', self::CONTEXT);
            $view->assign('inUnit', $this->Request->u);
            $view->assign('data', $this->Request->data);
            $view->assign('headline', $this->Request->headline);
            $view->assign('enableReplaceAll', $this->Request->replaceAll);
            $view->assign('epdSubType', $this->Request->epdSubType);
        } /**
         * If user pressed select button, assign the new process
         */
        elseif ($apply || $applyToAll) {
            /**
             * @var Project $project
             */
            $project = $this->sessionNamespace->project;

            // relId is the component uuid
            $relId = $this->Request->relId;

            // in id is the newProcessConfigId, in p the old
            $newProcessConfigId = (int)$this->Request->id;

            if ($applyToAll) {
                $project->replaceAllMappedProcessConfigIds($relId, $newProcessConfigId);
            } else {
                $project->replaceMappedProcessConfigIdForUuid($relId, $newProcessConfigId);
            }

            $view = $this->setView(new ProjectPreviewView());
            $view->assign('context', self::CONTEXT);
            $view->assign('project', $project);
            $view->assign('data', $this->getFormDataForProject($project));
            $view->assign('activeTab', $this->Request->has('data') ? $this->Request->get('data') : $this->activeTab);

            if ($this->Request->has('data')) {
                $this->activeTab = $this->sessionNamespace->activeTab = $this->Request->data;
            }
        }
    }

    /**
     * @param Project $project
     * @return \stdClass
     */
    private function getFormDataForProject(Project $project)
    {
        $processDbs = ElcaProcessDbSet::find(['is_active' => true], ['created' => 'desc'], 1);

        $data              = new \stdClass();
        $data->processDbId = $processDbs->current()->getId();

        $elementDinCodes   = $this->Request->getArray('elementDinCodes');
        $componentDinCodes = $this->Request->getArray('dinCodes');

        foreach ($project->variants() as $variant) {
            foreach ($variant->elements() as $element) {
                $resetComponentDinCodes = false;
                $elementUuid            = $element->uuid();

                if (isset($elementDinCodes[$elementUuid])) {
                    $data->elementDinCodes[$elementUuid] = $elementDinCodes[$elementUuid];
                    if ($elementDinCodes[$elementUuid] !== (string)$element->dinCode()) {
                        $element->changeDinCode($elementDinCodes[$elementUuid]);
                        $resetComponentDinCodes = true;
                    }

                } else {
                    $data->elementDinCodes[$elementUuid] = \utf8_substr($element->dinCode(), 0, 2).'0';
                }

                foreach ($element->allComponents() as $index => $component) {
                    $key = $component->uuid();

                    if (!$resetComponentDinCodes && isset($componentDinCodes[$key])) {
                        $data->dinCodes[$key] = $componentDinCodes[$key];
                    } else {
                        $data->dinCodes[$key] = !$resetComponentDinCodes && $component->hasDin276Code()
                            ? $component->dinCode()
                            : \utf8_substr($data->elementDinCodes[$elementUuid], 0, 2) . '9';
                    }
                    $component->setDinCode($data->dinCodes[$key]);

                    $data->processConfigIds[$key] = $component->materialMapping()->mapsToProcessConfigId();
                }
            }

            foreach ($variant->finalEnergyDemands() as $finalEnergyDemand) {
                $data->processConfigIds[$finalEnergyDemand->uuid()] = $finalEnergyDemand->materialMapping(
                )->mapsToProcessConfigId();
            }

            foreach ($variant->finalEnergySupplies() as $finalEnergySupply) {
                $data->processConfigIds[$finalEnergySupply->uuid()] = $finalEnergySupply->materialMapping(
                )->mapsToProcessConfigId();
            }
        }

        return $data;
    }
    // End selectProcessConfigAction

}
