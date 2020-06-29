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
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\SessionNamespace;
use Beibob\Blibs\Url;
use Elca\Db\ElcaCacheIndicatorSet;
use Elca\Db\ElcaCacheReferenceProjectSet;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectIFCSet;
use Elca\Elca;
use Elca\Model\Import\Xml\Importer;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Project\ProjectId;
use Elca\Model\User\UserId;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\AccessToken\ProjectAccessTokenService;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Service\Project\ProjectService;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;
use Elca\Service\ProjectAccess;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaContentHeadView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProjectDataGeneralView;
use Elca\View\ElcaProjectImportView;
use Elca\View\ElcaProjectNavigationLeftView;
use Elca\View\ElcaProjectsView;
use Elca\View\ElcaProjectView;
use Elca\View\Modal\ModalProjectAccess;
use Exception;

/**
 * Projects controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ProjectsCtrl extends AppCtrl
{
    /**
     * Validator
     */
    private $Validator;

    /**
     * Session namespace
     * @var SessionNamespace
     */
    private $namespace;

	/**
	 * 	checkAccess - with remove of shared project
	 */ 
	const REMOVEPROJECT = true;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        if(isset($args['Validator']))
            $this->Validator = $args['Validator'];

        if($this->hasBaseView())
            $this->getBaseView()->setContext(ElcaBaseView::CONTEXT_PROJECTS);

        /**
         * Session namespace
         */
        $this->namespace = $this->Session->getNamespace('elca.projects', true);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Default action
     */
    protected function defaultAction($projectId = null)
    {
        $projectId = $projectId? $projectId : $this->getAction();

        if($this->isAjax())
        {
            if(is_numeric($projectId))
            {
                //project details
                $project = ElcaProject::findById((int)$projectId);

                if($this->checkProjectAccess($project))
                {
                    $View = $this->setView(new ElcaProjectView());
                    $View->assign('firstStep', $this->Request->has('first-step'));
                    $View->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));
                    $View->setProject($project);

                    $View = $this->addView(new ElcaProjectNavigationLeftView);
                    $View->assign('Project', $project);

                    $this->Elca->setProjectId($project->getId());

                    /**
                     * Check new a1-3 aggregation model
                     */
                    if (ElcaCacheIndicatorSet::countA1A2OrA3Totals($project->getId())) {
                        $this->messages->add(
                            t('Das Projekt muss neu berechnet werden. Wollen Sie damit fortfahren?'),
                            ElcaMessages::TYPE_CONFIRM,
                            $this->getActionLink('recompute', ['id' => $project->getId(), 'confirmed' => true])
                        );
                    }
                    return;
                }
				

            }
            else
            {
                // project list
                $view = $this->setView(new ElcaProjectsView());
                $view->assign('filterDO', $this->getFilterDO('projectlist', [
                    'search' => null,
                    'scope'  => 'private',
                ]));
            }
        }
        else
        {
            if(is_numeric($projectId))
            {
                // project details
                $project = ElcaProject::findById((int)$projectId);

                if($this->checkProjectAccess($project))
                {
                    $this->getBaseView()->setProject($project);
                    $this->Elca->setProjectId($project->getId());
                    return;
                }
            }
            else
            {
                //project list
                $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
            }
        }

        $this->Elca->unsetProjectId();
    }
    // End defaultAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * creates a new empty project and names it with 'leeres Projekt <Datum>'
     *
     */
    protected function createAction()
    {
        if(!$this->isAjax())
        {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
            return;
        }

        $View = $this->setView(new ElcaProjectDataGeneralView());
        $View->assign('ElcaProcessDbSet',ElcaProcessDbSet::find(['is_active' => true], ['version' => 'desc'], null));

        // set default values for new project
        $DataObject = $View->assign('DataObject', new \stdClass());
        $DataObject->lifeTime = Elca::DEFAULT_LIFE_TIME;
        $DataObject->constrMeasure = Elca::CONSTR_MEASURE_PRIVATE;
        $DataObject->benchmarkVersionId = null;

        $View->assign('formAction', '/project-data/save/');
        $View->assign('buildMode', ElcaProjectDataGeneralView::BUILDMODE_CREATE);
        $View->assign('benchmarkSystemsService', $this->container->get(BenchmarkSystemsService::class));

        if (isset($this->Validator))
            $View->assign('Validator', $this->Validator);

        $this->Osit->add(new ElcaOsitItem(t('Projekt anlegen')));
    }
    // End createAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     *
     */
    protected function editAction()
    {
        if (!$this->Request->id) {
            return;
        }

        //project details
        $project = ElcaProject::findById((int)$this->Request->id);

        if (!$this->checkProjectAccess($project)) {
            return;
        }

		// Check, if IFC based project
		$ifcProject = ElcaProjectIFCSet::findIFCprojectById($project->getId());
		if(is_array($ifcProject)) {
			$this->namespace->ifcData = $ifcProject;
		}

        $this->Response->setHeader(
            'X-Redirect: '. $this->getActionLink($this->Request->id)
        );
    }

    /**
     *
     */
    protected function exportAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (!$this->Request->id) {
            return;
        }

        //project
        $project = ElcaProject::findById((int)$this->Request->id);

        if (!$this->checkProjectAccess($project) || !$this->Access->isProjectOwnerOrAdmin($project)) {
            return;
        }

        $this->Response->setHeader(
            'X-Redirect: '. $this->getLinkTo(ExportsCtrl::class, 'project', ['id' => $project->getId()])
        );
    }


    /**
     * Loads the import view
     */
    protected function importAction()
    {
        if(!$this->isAjax())
        {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
            return;
        }

        if($this->Request->isPost() && $this->Request->has('upload'))
        {
            $Validator = new ElcaValidator($this->Request);
            $Validator->assertTrue(
                'projects',
                $this->Access->canCreateProject(),
                t('Sie können nur %limit% Projekte anlegen', null, ['%limit%' => $this->Elca->getProjectLimit()])
            );

            if ($Validator->isValid()) {
                $Validator->assertTrue('importFile', File::uploadFileExists('importFile'), t('Bitte geben Sie eine Datei für den Import an!'));

                if(isset($_FILES['importFile']))
                    $Validator->assertTrue('importFile', preg_match('/\.xml$/iu', (string)$_FILES['importFile']['name']), t('Bitte nur XML Dateien importieren'));
            }


            if($Validator->isValid())
            {
                $Config = Environment::getInstance()->getConfig();
                if(isset($Config->tmpDir))
                {
                    $baseDir = $Config->toDir('baseDir');
                    $tmpDir  = $baseDir . $Config->toDir('tmpDir', true);
                }
                else
                    $tmpDir = '/tmp';

                $file = File::fromUpload('importFile', $tmpDir);

                $importer = $this->container->get(Importer::class);
                $Dbh = $this->container->get(DbHandle::class);

                try
                {
                    $Dbh->begin();
                    if(!$Project = $importer->importProject($file))
                        throw new Exception(t('Kein Projekt in Importdatei vorhanden'));

                    $this->container->get(LifeCycleUsageService::class)
                        ->updateForProject($Project);

                    $this->messages->add(t('Projekt %name% wurde erfolgreich importiert', null, ['%name%' => $Project->getName()]));

                    $View = $this->addView(new ElcaModalProcessingView());
                    $View->assign('action', $this->getLinkTo(
                        ProjectDataCtrl::class, 'lcaProcessing', [
                            'id' => $Project->getId(),
                            'redirect' => $this->getLinkTo(self::class, $Project->getId()),
                    ]));
                    $View->assign('headline', t('Neuberechnung nach Import erforderlich'));

                    $this->defaultAction();

                    $Dbh->commit();
                }
                catch(Exception $Exception)
                {
                    $Dbh->rollback();
                    $this->messages->add($Exception->getMessage(), ElcaMessages::TYPE_ERROR);
                }
            }
            else
            {
                foreach($Validator->getErrors() as $property => $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }
        }
        elseif($this->Request->isPost() && $this->Request->has('cancel'))
        {
            $this->defaultAction();
        }
        else
        {
            $View = $this->setView(new ElcaProjectImportView());
            $this->Osit->add(new ElcaOsitItem(t('Projekt importieren')));
        }
    }
    // End importAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     *
     */
    protected function passwordPromptAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return $this->redirect(self::class);
        }

        $projectId = (int)$this->Request->id;
        $project = ElcaProject::findById($projectId);

        if (!$project->isInitialized()) {
            return $this->redirect(self::class);
        }

        $view = $this->addView(new ModalProjectAccess());
        $view->assign('projectId', $projectId);
        $view->assign('origin', $this->Request->has('origin') ? $this->Request->origin : null);
        $view->assign('originCtrl', $this->Request->get('originCtrl'));
        $view->assign('originAction', $this->Request->get('originAction'));
        $view->assign('originArgs', $this->Request->get('originArgs'));

        if ($this->Request->isPost()) {

            $plainPw = $this->Request->pwProject;

            $validator = new ElcaValidator();
            $validator->assertTrue(
                'pwProject',
                $project->getPassword()->isValid($plainPw),
                t('Das Passwort ist falsch')
            );

            if ($validator->isValid()) {
                $projectAccess = $this->container->get(ProjectAccess::class);
                $projectAccess->updateEncryptedPasswordInSessionForProject($project);

                $originUrl = $this->Request->has('origin') ? $this->Request->origin : $this->getActionLink((int)$projectId);

                if ($this->Request->has('originCtrl')) {
                    $args = $this->Request->originArgs ? (array)json_decode($this->Request->originArgs) : null;
                    $this->forwardReset($this->Request->originCtrl, $this->Request->originAction, $args);
                }
                else {
                    $this->Response->setHeader('X-Redirect: '. $originUrl);
                }

            } else {
                foreach($validator->getErrors() as $property => $message) {
                    $this->messages->add(t($message), ElcaMessages::TYPE_ERROR);
                }

                $view->assign('validator', $validator);
            }
        }
    }

    /**
     *
     */
    protected function removeProjectAccessAction()
    {
		
        if (!$projectId = $this->Request->getString('id')) {
            return;
        }

        $project = ElcaProject::findById($projectId);

        if (!$this->checkProjectAccess($project,self::REMOVEPROJECT)) {
            return;
        }

		
        if ($this->Request->has('confirmed')) {
			
            $this->get(ProjectAccessTokenService::class)
                 ->removeAccessTokenForProjectAndUser(
                     new ProjectId($projectId),
                     new UserId($this->Access->getUserId())
                 );

            $this->messages->add(t('Die Freigabe wurde entfernt'), ElcaMessages::TYPE_NOTICE);

            $this->defaultAction();

        } else {
            $url = Url::parse($this->Request->getURI());
            $url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t(
                    'Soll die Freigabe für das Projekt :projectName: wirklich entfernt werden?',
                    null,
                    [':projectName:' => $project->getName()]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$url
            );
        }
    }


    /**
     * Changes the phase
     */
    protected function changePhaseAction()
    {
        if (!is_numeric($this->Request->id) or !is_numeric($this->Request->phase) )
            return false;

        $project = ElcaProject::findById($this->Request->id);
        if (!$project->isInitialized())
            return false;

        if (!$this->checkProjectAccess($project))
            return;

        $phase = ElcaProjectPhase::findById($this->Request->phase);
        if (!$phase->isInitialized()) return false;


        // change variant to project->currentVariant if we're in currentVariant phase
        $projectVariant = $project->getCurrentVariant()->getPhaseId() === $phase->getId()
            ? ElcaProjectVariant::findById($project->getCurrentVariantId())
            : ElcaProjectVariant::findByProjectIdAndPhaseId($project->getId(), $phase->getId());

        $this->Elca->setProjectVariantId($projectVariant->getId());

        $View = $this->addView(new ElcaContentHeadView());
        $View->assign('Project', $project);

        /**
         * Reload hash url and set event
         */
        if(!$this->notifyHashUrlWithEvent('variant-change'))
        {
            /**
             * Special case, if no controller could be notified
             * add left navigation view to apply navigation changes
             */
            $View = $this->addView(new ElcaProjectNavigationLeftView());
            $View->assign('Project', $project);
        }
    }
    // End changePhaseAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Changes the project variant
     */
    protected function changeVariantAction()
    {
        if (!$this->Request->isPost() || !$this->Request->has('projectVariant') ) {
            return;
        }

        $Variant = ElcaProjectVariant::findById($this->Request->projectVariant);
        if (!$Variant->isInitialized()) return false;

        $Project = $Variant->getProject();

        if(!$this->checkProjectAccess($Project))
            return;

        $this->Elca->setProjectVariantId($this->Request->projectVariant);

        /**
         * Reload hash url and set event
         */
        $this->notifyHashUrlWithEvent('variant-change');
    }
    // End changeVariantAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * new Phase action -> creates new phase
     * if new phase < actual phase:  takes last phase that exists and is smaller than phase to be created
     *                               and takes last created/modified variant from it
     * if new phase > actual phase: takes next phase and actual variant
     */
    protected function newPhaseAction()
    {
        if(!is_numeric($this->Request->phase))
            return false;
        $project = ElcaProject::findById($this->Request->id);
        if (!$project->isInitialized())
            return false;

        if(!$this->checkProjectAccess($project) || !$this->Access->isProjectOwnerOrAdmin($project))
            return;

        $NewPhase = ElcaProjectPhase::findById($this->Request->phase);
        if (!$NewPhase->isInitialized())
            return false;

        $ActPhase = ElcaProjectPhase::findById($this->Elca->getProjectVariant()->getPhaseId());
        if (!$ActPhase->isInitialized())
            return false;

        $ProjectVariant = false;
        // new phase is later phase
        if ($NewPhase->getStep() > $ActPhase->getStep())
            $ProjectVariant = $this->Elca->getProjectVariant();

        else if ($NewPhase->getStep() < $ActPhase->getStep() )
            // get last Variant for phase
            $ProjectVariant = ElcaProjectVariant::findLastModifiedCreated($project->getId(), $NewPhase->getId() );

        if (!$ProjectVariant)
            return false;

        if($this->Request->has('confirmed'))
        {
            // build new phase
            $NewProjectVariant = $this
                ->container->get(ProjectVariantService::class)
                           ->copy($ProjectVariant, $project->getId(), $NewPhase->getId(), true)
            ;

            //$NewProjectVariant = $ProjectVariant->copy($Project->getId(), $NewPhase->getId(), true);
            $NewProjectVariant->setName($NewPhase->getName());
            $NewProjectVariant->update();
            
            $project->setCurrentVariantId($NewProjectVariant->getId());
            $project->update();
            $this->Elca->setProjectVariantId($NewProjectVariant->getId());

            $View = $this->addView(new ElcaContentHeadView());
            $View->assign('Project', $project);

	        $this->addView(new ElcaProjectNavigationLeftView());

            /**
             * Reload hash url and set event
             */
            $this->notifyHashUrlWithEvent('variant-change');
        }
        else
        {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(t('Soll für das Project die Phase "%project%" aus der Variante "%variant%" erstellt werden?', null, ['%project%' => $NewPhase->getName(), '%variant%' => $ProjectVariant->getName()]),
                                 ElcaMessages::TYPE_CONFIRM,
                                 (string)$Url);
        }

        return false;
    }
    // End newPhaseAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a project
     */
    protected function deleteAction()
    {
        if(!is_numeric($this->Request->id))
            return;

        $project = ElcaProject::findById($this->Request->id);
        if(!$project->isInitialized())
            return;

        if(!$this->checkProjectAccess($project) || !$this->Access->isProjectOwnerOrAdmin($project))
            return;

        /**
         * If deletion has confirmed, do the action
         */
        if($this->Request->has('confirmed'))
        {
            $project = ElcaProject::findById($this->Request->id);
            if($project->isInitialized())
            {
                $project->delete();

                $this->messages->add(t('Projekt wurde gelöscht'));
            }

            $this->defaultAction();
        }
        else
        {
            /**
             * Build confirm url by adding the confirmed argument to the current request
             */
            $Url = Url::parse($this->getActionLink('delete', ['id' => $this->Request->id]));
            $Url->addParameter(['confirmed' => null]);

            /**
             * Show confirm message
             */
            $this->messages->add(t('Soll das Projekt wirklich gelöscht werden?'),
                                 ElcaMessages::TYPE_CONFIRM,
                                 (string)$Url);
        }
    }
    // End deleteAction

    /**
     * Recomputes the lca for all project variants
     */
    public function recomputeAction()
    {
        if (!is_numeric($this->Request->id))
            return;

        $project = ElcaProject::findById($this->Request->id);
        if (!$project->isInitialized())
            return;

        if( !$this->checkProjectAccess($project) || !$this->Access->canEditProject($project))
            return;

        /**
         * If deletion has confirmed, do the action
         */
        if ($this->Request->has('confirmed'))
        {
            $View = $this->addView(new ElcaModalProcessingView());
            $View->assign('action', $this->FrontController->getUrlTo('Elca\Controller\ProjectDataCtrl', 'lcaProcessing', ['id' => $this->Request->id]));
            $View->assign('headline', t('Neuberechnung'));
            $View->assign('description', t('Das Projekt "%project%" wird neu berechnet.', null, ['%project%' => $project->getName()]));
        }
        else
        {
            /**
             * Build confirm url by adding the confirmed argument to the current request
             */
            $Url = Url::parse($this->getActionLink('recompute', ['id' => $this->Request->id]));
            $Url->addParameter(['confirmed' => null]);

            /**
             * Show confirm message
             */
            $this->messages->add(t('Soll das Projekt wirklich neu berechnet werden? Dies kann je nach Projektumfang einige Minuten Zeit beanspruchen!'),
                                 ElcaMessages::TYPE_CONFIRM,
                                 (string)$Url);
        }
    }
    // End recomputeAction


    /**
     * Creates a copy from a project
     *
     */
    public function copyAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if(!is_numeric($this->Request->id))
            return;

        $project = ElcaProject::findById($this->Request->id);
        if(!$project->isInitialized())
            return;

        if(!$this->checkProjectAccess($project) || !$this->Access->isProjectOwnerOrAdmin($project))
            return;

        $validator = new ElcaValidator($this->Request);
        $validator->assertTrue(
            'projects',
            $this->Access->canCreateProject(),
            t('Sie können nur %limit% Projekte anlegen', null, ['%limit%' => $this->Elca->getProjectLimit()])
        );

        if ($validator->isValid()) {

            /**
             * @var ProjectService $projectsService
             */
            $projectsService = $this->container->get(ProjectService::class);

            $copy = $projectsService->createCopyFromProject($project);

            if ($copy->isInitialized()) {
                $this->messages->add(t('Projekt wurde kopiert'));
            }

            $this->defaultAction();
        }
        else {
            foreach($validator->getErrors() as $property => $message)
                $this->messages->add(t($message), ElcaMessages::TYPE_ERROR);
        }
    }
    // End copyAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a copy from a project
     *
     */
    public function markAsReferenceAction()
    {
        if (!is_numeric($this->Request->id)) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $project = ElcaProject::findById($this->Request->id);
        if (!$project->isInitialized()) {
            return;
        }

        if ($this->Request->has('unset')) {
            $project->setIsReference(false);
            $this->messages->add(
                t(
                    'Das Projekt "%project%" wird nicht mehr als Referenzprojekt gewertet.',
                    null,
                    ['%project%' => $project->getName()]
                )
            );
        } else {
            $project->setIsReference(true);
            $this->messages->add(
                t(
                    'Das Projekt "%project%" wird nun als Referenzprojekt gewertet.',
                    null,
                    ['%project%' => $project->getName()]
                )
            );
        }
        $project->update();

        ElcaCacheReferenceProjectSet::refreshMaterializedView();

        $this->defaultAction();
    }
    // End copyAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a filter data object form request or session
     *
     * @param  string $key
     * @param array   $defaults
     * @return object
     */
    protected function getFilterDO($key, array $defaults = [])
    {
        if(!$filterDOs = $this->namespace->filterDOs)
            $filterDOs = [];

        $filterDO = isset($filterDOs[$key])? $filterDOs[$key] : new \stdClass();

        foreach($defaults as $name => $defaultValue)
            $filterDO->$name = $this->Request->has($name)? $this->Request->get($name) : (isset($filterDO->$name)? $filterDO->$name : $defaultValue);

        $filterDOs[$key] = $filterDO;

        $this->namespace->filterDOs = $filterDOs;

        return $filterDO;
    }
}
// End ElcaProjectsCtrl
