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

use Beibob\Blibs\BlibsDateTime;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Url;
use Beibob\Blibs\UserStore;
use Beibob\Blibs\Validator;
use Beibob\Blibs\Environment;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaBenchmarkRefProcessConfigSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaConstrCatalogSet;
use Elca\Db\ElcaConstrDesignSet;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectConstruction;
use Elca\Db\ElcaProjectEnEv;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\Db\ElcaProjectFinalEnergyDemandSet;
use Elca\Db\ElcaProjectFinalEnergyRefModel;
use Elca\Db\ElcaProjectFinalEnergyRefModelSet;
use Elca\Db\ElcaProjectFinalEnergySupply;
use Elca\Db\ElcaProjectFinalEnergySupplySet;
use Elca\Db\ElcaProjectIndicatorBenchmark;
use Elca\Db\ElcaProjectIndicatorBenchmarkSet;
use Elca\Db\ElcaProjectKwk;
use Elca\Db\ElcaProjectKwkSet;
use Elca\Db\ElcaProjectLocation;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectPhaseSet;
use Elca\Db\ElcaProjectTransport;
use Elca\Db\ElcaProjectTransportMean;
use Elca\Db\ElcaProjectTransportMeanSet;
use Elca\Db\ElcaProjectTransportSet;
use Elca\Db\ElcaProjectVariant;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Db\ElcaSearchAndReplaceResultSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Element\SearchAndReplaceObserver;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Security\EncryptedPassword;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;
use Elca\Service\ProjectAccess;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaContentHeadView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\ElcaProjectDataBenchmarksView;
use Elca\View\ElcaProjectDataEnEvView;
use Elca\View\ElcaProjectDataGeneralView;
use Elca\View\ElcaProjectDataTransportsView;
use Elca\View\ElcaProjectNavigationLeftView;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\ElcaProjectSearchAndReplaceProcessesView;
use Elca\View\ElcaProjectVariantsView;
use Elca\View\ProjectData\ReplaceOverviewView;
use Elca\Security\ElcaAccess;
use Exception;

/**
 * Project data controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ProjectDataCtrl extends AppCtrl
{
    const CONTEXT = 'project-data';
    const DUMMY_PASSWORD = '*****************';
    const PROJECT_PASSWORD_LENGTH = 6;
    const PROCESS_CATEGORY_DEFAULT_REF = '8.06';
    const PROCESS_CATEGORY_KWK_DEFAULT_REF = '8.06';


    /**
     * Default action
     */
    protected function defaultAction()
    {
        $this->generalAction();
    }
    // End defaultAction


    /**
     * General action
     */
    protected function generalAction(
        ElcaValidator $Validator = null,
        $addNavigationViews = true,
        $ignorePermissionCheck = false
    ) {
        /**
         * Check permissions
         */
        if (!$ignorePermissionCheck && !$this->checkProjectAccess()) {
            return;
        }

        $ProjectVariant = $this->Elca->getProjectVariant();
        $View           = $this->setView(new ElcaProjectDataGeneralView());
        $View->assign('projectVariantId', $ProjectVariant->getId());
        
        // ON! 26.04.2023 ------------------------------
        // nicht aktive Test-DB für Testgruppe abfragen
        // ---------------------------------------------
        $environment = Environment::getInstance();
        $config      = $environment->getConfig();
        $access = ElcaAccess::getInstance();
        
        if ($access->hasAdminPrivileges() || $access->hasRole(Elca::ELCA_ROLE_TESTING)) 
        {
            if(isset($config->databasetest->processdbid))
            {
               // $databasetestgroup = $config->databasetest->group;
               $databasetestprocessdbid = (int)$config->databasetest->processdbid;
            }
            $View->assign('ElcaProcessDbSet',ElcaProcessDbSet::findActiveOrById($databasetestprocessdbid, ['version' => 'desc'], null)); 
            
        }
        else 
        {        
            // normale Abfrage Datenbanken aktiv
            $View->assign('ElcaProcessDbSet',ElcaProcessDbSet::find(['is_active' => true], ['version' => 'desc'], null));
        }            
        
        
        $View->assign('ElcaConstrCatalogSet', ElcaConstrCatalogSet::find(null, ['ident' => 'desc'], null));
        $View->assign('ElcaConstrDesignSet', ElcaConstrDesignSet::find(null, ['ident' => 'desc'], null));
        $View->assign('benchmarkSystemsService', $this->container->get(BenchmarkSystemsService::class));

        $View->assign('formAction', '/project-data/save/');
        if ($Validator) {
            $View->assign('Validator', $Validator);
        }

        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        // project head
        $dataObject                     = $View->assign('DataObject', new \stdClass());
        $Project                        = $this->Elca->getProject();
        $dataObject->name               = $Project->getName();
        $dataObject->projectNr          = $Project->getProjectNr();
        $dataObject->lifeTime           = $Project->getLifeTime();
        $dataObject->constrMeasure      = $Project->getConstrMeasure();
        $dataObject->constrClassId      = $Project->getConstrClassId();
        $dataObject->description        = $Project->getDescription();
        $dataObject->processDbId        = $Project->getProcessDbId();
        $dataObject->benchmarkVersionId = $Project->getBenchmarkVersionId();
        $dataObject->currentVariantId   = $Project->getCurrentVariantId();
        $dataObject->editor             = $Project->getEditor();

        // geo stuff
        $Location                = $ProjectVariant->getProjectLocation();
        $dataObject->street      = $Location->getStreet();
        $dataObject->postcode    = $Location->getPostcode();
        $dataObject->city        = $Location->getCity();
        $dataObject->country     = $Location->getCountry();
        $dataObject->geoLocation = $Location->getGeoLocation();

        // area stuff
        $projectConstruction = $ProjectVariant->getProjectConstruction();
        if ($projectConstruction->isInitialized()) {
            $dataObject->floorSpace       = $projectConstruction->getFloorSpace();
            $dataObject->grossFloorSpace  = $projectConstruction->getGrossFloorSpace();
            $dataObject->netFloorSpace    = $projectConstruction->getNetFloorSpace();
            $dataObject->propertySize     = $projectConstruction->getPropertySize();
            $dataObject->livingSpace      = $projectConstruction->getLivingSpace();
            $dataObject->constrCatalogId  = $projectConstruction->getConstrCatalogId();
            $dataObject->constrDesignId   = $projectConstruction->getConstrDesignId();
            $dataObject->isExtantBuilding = $projectConstruction->isExtantBuilding();
        }

        if ($Project->hasPassword()) {
            $dataObject->pw       = self::DUMMY_PASSWORD;
            $dataObject->pwRepeat = self::DUMMY_PASSWORD;
        }

        // attributes
        $ProjectAttribute     = ElcaProjectAttribute::findByProjectIdAndIdent(
            $Project->getId(),
            ElcaProjectAttribute::IDENT_IS_LISTED
        );
        $dataObject->isListed = (bool)$ProjectAttribute->getNumericValue();

        $ProjectAttribute  = ElcaProjectAttribute::findByProjectIdAndIdent(
            $Project->getId(),
            ElcaProjectAttribute::IDENT_BNB_NR
        );
        $dataObject->bnbNr = $ProjectAttribute->getTextValue();

        $ProjectAttribute   = ElcaProjectAttribute::findByProjectIdAndIdent(
            $Project->getId(),
            ElcaProjectAttribute::IDENT_EGIS_NR
        );
        $dataObject->eGisNr = $ProjectAttribute->getTextValue();

        $this->Osit->add(new ElcaOsitItem(t('Allgemein'), null, t('Stammdaten')));

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $View = $this->addView(new ElcaProjectNavigationView());
            $View->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
        }
    }
    // End generalAction


    /**
     * save action
     */
    protected function saveAction()
    {
        if (!$this->Request->isPost()) {
            return;
        }

        if ($this->Request->cancel) {
            return $this->forward(ProjectsCtrl::class);
        }

        /**
         * Check permissions
         */
        if (!$this->Request->has('create') &&
            !$this->checkProjectAccess() &&
            !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())
        ) {
            return;
        }

        $needLcaProcessing          = false;
        $processDbHasChanged        = false;
        $benchmarkVersionHasChanged = false;
        $benchmarkVersion = ElcaBenchmarkVersion::findById($this->Request->benchmarkVersionId);
        $projectLifeTimeIsReadOnly = null !== $benchmarkVersion->getProjectLifeTime();

        $validator = new ElcaValidator($this->Request);
        $validator->assertTrue(
            'projects',
            $this->Access->canCreateProject(),
            t('Sie können nur %limit% Projekte anlegen', null, ['%limit%' => $this->Elca->getProjectLimit()])
        );

        if ($validator->isValid()) {
            $validator->assertNotEmpty('name', null, t('Bitte geben Sie einen Namen ein'));

            if (!$this->Request->benchmarkVersionId) {
                $validator->assertNotEmpty(
                    'processDbId',
                    null,
                    t('Bitte wählen sie eine Baustoff Datenbank oder ein Benchmarksystem aus')
                );
            }
            $validator->assertNumber('processDbId', null, t('Bitte wählen sie eine Baustoff Datenbank aus'));

            if (!$projectLifeTimeIsReadOnly) {
                $validator->assertNotEmpty(
                    'lifeTime',
                    null,
                    t('Bitte geben Sie eine Gebäude Nutzungsdauer in Jahren ein')
                );
                $validator->assertNumber(
                    'lifeTime',
                    null,
                    t('Bitte geben Sie eine Gebäude Nutzungsdauer in Jahren ein')
                );
            }
            if ($this->Request->has('create')) {
                $validator->assertNotEmpty('constrMeasure', null, t('Bitte wählen Sie eine Baumaßnahme aus'));
            }
            else {
                $validator->assertNotEmpty('currentVariantId', null, t('Bitte wählen Sie eine aktive Projektvariante'));
            }

            $validator->assertNotEmpty('constrClassId', null, t('Bitte wählen Sie eine Bauwerkszuordnung aus'));

            $validator->assertNotEmpty('netFloorSpace', null, t('Bitte geben Sie eine NGF an'));
            $validator->assertNumber('netFloorSpace', null, t('Bitte geben Sie eine NGF an'));

            $validator->assertNotEmpty('grossFloorSpace', null, t('Bitte geben Sie eine BGF an'));
            $validator->assertNumber('grossFloorSpace', null, t('Bitte geben Sie eine BGF an'));

            $benchmarkSystemModel = $this->container->get(BenchmarkSystemsService::class)->benchmarkSystemModelByVersionId($benchmarkVersion->getId());
            if ($benchmarkVersion->isInitialized() && $benchmarkSystemModel && $benchmarkSystemModel->displayLivingSpace()) {
                $validator->assertNotEmpty('livingSpace', null, t('Bitte geben Sie eine Wohnfläche an'));
                $validator->assertNumber('livingSpace', null, t('Bitte geben Sie eine Wohnfläche an'));
            }

            $validator->assertNotEmpty(
                'postcode',
                null,
                t('Geben Sie bitte mindestens die erste Stelle der Postleitzahl an')
            );
            $validator->assertLength(
                'postcode',
                6,
                1,
                null,
                t('Geben Sie bitte mindestens die erste Stelle der Postleitzahl an')
            );
            $validator->assertNumber('postcode', null, t('Die Postleitzahl muss numerisch sein'));

            $validator->assertProjectPassword(
                'pw',
                'pwRepeat',
                self::PROJECT_PASSWORD_LENGTH,
                self::DUMMY_PASSWORD,
                t(
                    'Das Passwort muss mindenstens :count: Zeichen lang sein',
                    null,
                    [':count:' => self::PROJECT_PASSWORD_LENGTH]
                ),
                t('Die beiden Passwörter stimmen nicht überein')
            );
        }

        if ($validator->isValid()) {

            $lifeCycleUsages = $this->container->get(LifeCycleUsageService::class);
            $projectLifeTime = $this->Request->lifeTime;

            if ($benchmarkVersion->isInitialized()) {
                $benchmarkVersionId = $benchmarkVersion->getId();
                $processDbId        = $benchmarkVersion->getProcessDbId();

                if ($benchmarkVersion->getProjectLifeTime()) {
                    $projectLifeTime = $benchmarkVersion->getProjectLifeTime();
                }
            } else {
                $benchmarkVersionId = null;
                $processDbId        = $this->Request->processDbId;
            }

            $passwordPlain = $this->Request->pw;
            $passwordHasChanged = false;

            if ($this->Request->has('create')) {
                // save project
                $project = ElcaProject::create(
                    $processDbId,
                    UserStore::getInstance()->getUser()->getId(),
                    // ownerId
                    UserStore::getInstance()->getUser()->getGroupId(),
                    // access_group_id
                    $this->Request->name,
                    $projectLifeTime,
                    null,
                    strlen($this->Request->description) ? $this->Request->description : null,
                    // description = null
                    strlen($this->Request->projectNr) ? $this->Request->projectNr : null,
                    // project_nr = null
                    $this->Request->constrMeasure,
                    // constr_measure = 1 or 2
                    $this->Request->constrClassId ? $this->Request->constrClassId : null,
                    $this->Request->editor ? \trim($this->Request->editor) : null,
                    false,
                    // isReference
                    $benchmarkVersionId,
                    $passwordPlain ? (string)EncryptedPassword::fromPlainPassword($passwordPlain) : null
                );

                $passwordHasChanged = (bool)$passwordPlain;

                // project variant
                $ProjectPhase   = ElcaProjectPhase::findMinIdByConstrMeasure(
                    $project->getConstrMeasure(),
                    $this->Request->has('startWithProjection') ? 0 : 1
                );
                $ProjectVariant = ElcaProjectVariant::create(
                    $project->getId(),
                    $ProjectPhase->getId(),
                    $ProjectPhase->getName()
                );

                $project->setCurrentVariantId($ProjectVariant->getId());
                $project->update();

                $lifeCycleUsages->updateForProject($project);

            } // update project
            else {
                $project        = $this->Elca->getProject();
                $ProjectVariant = $this->Elca->getProjectVariant();

                if ($project->getName() != \trim($this->Request->name)) {
                    $project->setName(trim($this->Request->name));

                    /**
                     * update content head view
                     */
                    $ContentHead = $this->addView(new ElcaContentHeadView());
                    $ContentHead->assign('Project', $project);
                }
                $project->setProjectNr(\trim($this->Request->projectNr));
                $project->setDescription(\trim($this->Request->description));
                if (in_array(
                    $this->Request->constrMeasure,
                    [Elca::CONSTR_MEASURE_PRIVATE, Elca::CONSTR_MEASURE_PUBLIC]
                )) {
                    $project->setConstrMeasure($this->Request->constrMeasure);
                }

                $project->setConstrClassId($this->Request->constrClassId ? $this->Request->constrClassId : null);

                /**
                 * Check for changes, require lca re-computation
                 */
                if ($projectLifeTime != $project->getLifeTime()) {
                    $needLcaProcessing = true;
                }

                if ($processDbId != $project->getProcessDbId()) {
                    $needLcaProcessing   = true;
                    $processDbHasChanged = true;

                    $ProcessDb = ElcaProcessDb::findById($processDbId);
                    if (!$ProcessDb->isEn15804Compliant()) {
                        foreach (
                            ElcaProjectTransportSet::findByProjectVariantId(
                                $ProjectVariant->getId()
                            ) as $Transport
                        ) {
                            $Transport->setCalcLca(false);
                            $Transport->update();
                        }
                    }
                }

                $oldBenchmarkVersion = $project->getBenchmarkVersion();

                if ($oldBenchmarkVersion->getId() !== $benchmarkVersionId) {
                    $benchmarkVersionHasChanged = true;
                }

                $project->setLifeTime($projectLifeTime);
                $project->setBenchmarkVersionId($benchmarkVersionId);
                $project->setProcessDbId($processDbId);
                $project->setEditor(\trim($this->Request->editor));
                $project->setCurrentVariantId($this->Request->currentVariantId ?: $project->getCurrentVariantId());

                $encryptedPassword = EncryptedPassword::fromPlainPassword($passwordPlain);
                if ($passwordPlain !== self::DUMMY_PASSWORD &&
                    !$encryptedPassword->equals($project->getPassword())
                ) {
                    $project->setPassword(
                        $passwordPlain ? (string)$encryptedPassword : null
                    );

                    $projectAccess = $this->container->get(ProjectAccess::class);
                    $projectAccess->updateEncryptedPasswordInSessionForProject($project);
                    $passwordHasChanged = true;
                }

                $project->update();

                /**
                 * Remove process energy demand, if reference model is not used
                 */
                if (!$benchmarkVersion->getUseReferenceModel()) {
                    $demand = ElcaProjectFinalEnergyDemand::findByProjectVariantIdAndIdent(
                        $ProjectVariant->getId(),
                        ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY
                    );
                    if ($demand->isInitialized()) {
                        $demand->delete();
                        $needLcaProcessing = true;
                    }
                }

                /**
                 * Re-process lca if reference model usage has changed
                 */
                if ($oldBenchmarkVersion->getUseReferenceModel() !== $benchmarkVersion->getUseReferenceModel()) {
                    $needLcaProcessing = true;
                }

                if ($processDbHasChanged || $benchmarkVersionHasChanged) {
                    $lifeCycleUsages->updateForProject($project);
                }
            }

            if ($passwordHasChanged) {
                ElcaProjectAttribute::updateValue(
                    $project->getId(),
                    ElcaProjectAttribute::IDENT_PW_DATE,
                    date('Y-m-d'), true,
                    "Passwort gültig seit"
                );
            }

            /**
             * Location
             */
            $street   = \trim($this->Request->street);
            $postCode = \trim($this->Request->postcode);
            $city     = \trim($this->Request->city);
            $country  = \trim($this->Request->country);

            $Location = $ProjectVariant->getProjectLocation();
            if ($Location->isInitialized()) {
                $Location->setStreet($street);
                $Location->setPostcode($postCode);
                $Location->setCity($city);
                $Location->setCountry($country);
                $Location->update();
            } else {
                ElcaProjectLocation::create(
                    $ProjectVariant->getId(),
                    $street,
                    $postCode,
                    $city,
                    $country
                );
            }

            /**
             * Construction
             */
            $floorSpace          = ElcaNumberFormat::fromString($this->Request->floorSpace, 2);
            $grossFloorSpace     = ElcaNumberFormat::fromString($this->Request->grossFloorSpace, 2);
            $netFloorSpace       = ElcaNumberFormat::fromString($this->Request->netFloorSpace, 2);
            $propertySize        = ElcaNumberFormat::fromString($this->Request->propertySize, 2);
            $livingSpace         = $this->Request->livingSpace ? ElcaNumberFormat::fromString($this->Request->livingSpace, 2) : null;
            $isExtantBuilding    = $this->Request->has('isExtantBuilding');
            $resetExtantElements = false;

            $projectConstruction = $ProjectVariant->getProjectConstruction();
            if ($projectConstruction->isInitialized()) {
                if ($netFloorSpace != $projectConstruction->getNetFloorSpace()) {
                    $needLcaProcessing = true;
                }

                $projectConstruction->setFloorSpace($floorSpace);
                $projectConstruction->setGrossFloorSpace($grossFloorSpace);
                $projectConstruction->setNetFloorSpace($netFloorSpace);
                $projectConstruction->setPropertySize($propertySize);
                $projectConstruction->setLivingSpace($livingSpace);
                $projectConstruction->setConstrDesignId(
                    strlen($this->Request->constrDesignId) ? $this->Request->constrDesignId : null
                );
                $projectConstruction->setConstrCatalogId(
                    strlen($this->Request->constrCatalogId) ? $this->Request->constrCatalogId : null
                );

                if ($isExtantBuilding != $projectConstruction->isExtantBuilding() && !$isExtantBuilding) {
                    $resetExtantElements = true;
                }

                $projectConstruction->setIsExtantBuilding($isExtantBuilding);
                $projectConstruction->update();
            } else {
                ElcaProjectConstruction::create(
                    $ProjectVariant->getId(),
                    null, // constrCatalogId
                    null, // constrDesignId
                    $grossFloorSpace,
                    $netFloorSpace,
                    $floorSpace,
                    $propertySize,
                    $livingSpace,
                    $isExtantBuilding
                );
            }

            /**
             * If extant building property has changed to false,
             * reset all isExtant and lifeTimeDelay properties
             */
            if ($resetExtantElements) {
                /** @var ElcaElementComponent $Component */
                foreach (ElcaElementComponentSet::findByProjectVariantId($ProjectVariant->getId()) as $Component) {
                    if (!$Component->isExtant()) {
                        continue;
                    }

                    $Component->setIsExtant(false);
                    $Component->setLifeTimeDelay(0);
                    $Component->update();

                    $needLcaProcessing = true;
                }
            }

            /**
             * Attributes
             */
            ElcaProjectAttribute::updateValue(
                $project->getId(),
                ElcaProjectAttribute::IDENT_IS_LISTED,
                (int)$this->Request->has('isListed')
            );
            ElcaProjectAttribute::updateValue(
                $project->getId(),
                ElcaProjectAttribute::IDENT_BNB_NR,
                $this->Request->get('bnbNr')
            );
            ElcaProjectAttribute::updateValue(
                $project->getId(),
                ElcaProjectAttribute::IDENT_EGIS_NR,
                $this->Request->get('eGisNr')
            );

            /**
             * Check if lca re-computation is required
             */
            if ($needLcaProcessing) {
                $View = $this->addView(new ElcaModalProcessingView());
                $View->assign('action', $this->getActionLink('lcaProcessing', ['id' => $project->getId()]));
                $View->assign('headline', t('Neuberechnung erforderlich'));
                $View->assign('reload', true);
                $View->assign(
                    'description',
                    t(
                        'Sie haben Änderungen vorgenommen, die eine Neuberechnung des gesamten Projekts erforderlich machen.'
                    )
                );

                return;
            } else {
                $this->messages->add(t('Ihre Daten wurden übernommen.'));
            }

            if ($this->Request->has('create')) {
                return $this->Response->setHeader('X-Redirect: /projects/' . $project->getId() . '/');
            }

            $this->generalAction();
        } else {
            foreach ($validator->getErrors() as $property => $message) {
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->generalAction($validator, true, true);
        }

        if ($this->Request->has('create')) {
            $this->forward(ProjectsCtrl::class, 'create', null, ['Validator' => $validator]);
        }
    }
    // End saveAction

    /**
     * Calculates the lca for the complete project
     */
    protected function lcaProcessingAction()
    {
        if (!$this->Request->id) {
            return;
        }

        $Project = ElcaProject::findById((int)$this->Request->id);

        if (!$Project->isInitialized()) {
            return;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess($Project)) {
            return;
        }

        $LcaProcessor = $this->container->get(ElcaLcaProcessor::class);
        $lifeTime     = $Project->getLifeTime();
        $processDbId  = new ProcessDbId($Project->getProcessDbId());

        $Dbh = DbHandle::getInstance();

        try {
            $Dbh->begin();

            /**
             * Recompute all elements and final energy demands of all variants
             */
            $Variants = ElcaProjectVariantSet::findByProjectId($Project->getId());
            foreach ($Variants as $Variant) {
                $LcaProcessor->computeProjectVariant($Variant, $processDbId, $lifeTime);
            }

            $LcaProcessor->updateCache($Project->getId());
            $Dbh->commit();
        }
        catch (Exception $Exception) {
            $Dbh->rollback();
            throw $Exception;
        }

        if ($this->Request->has('reload')) {
            $this->Response->setHeader('X-Reload-Hash: true');

        } elseif ($this->Request->has('redirect')) {
            $this->Response->setHeader('X-Redirect: ' . $this->Request->get('redirect'));

        } elseif (!$this->Request->has('stay') && $this->Elca->hasProjectId()) {
            $this->generalAction(null, false);
        }
    }
    // End lcaProcessingAction

    /**
     *
     */
    protected function replaceProcessesAction()
    {
        /**
         * Check permissions
         */
        $project = $this->Elca->getProject();
        if (!$this->checkProjectAccess($project)) {
            return;
        }

        if ($this->Request->projectVariantId) {
            $projectVariantId = $this->Request->projectVariantId;
        } elseif ($this->Request->relId) {
            $projectVariantId = $this->Request->relId;
        } else {
            $projectVariantId = $this->Elca->getProjectVariantId();
        }

        $projectVariant = ElcaProjectVariant::findById($projectVariantId);

        $this->Osit->add(new ElcaOsitItem(t('Projektvarianten'), '/project-data/variants/', t('Stammdaten')));
        $this->Osit->add(new ElcaOsitItem($projectVariant->getName(),  null, t('Baustoffe Suchen & Ersetzen')));

        $Namespace = $this->Session->getNamespace('searchAndReplace', true);
        if (!isset($Namespace->FormData)) {
            $Namespace->FormData = (object)null;
        }

        if ($this->Request->has('init')) {
            $Namespace->FormData = (object)null;
            $url                 = Url::parse($this->Request->getURI());
            $url->removeParameter('init');
            $this->updateHashUrl((string)$url);

            if ((int)$projectVariantId !== (int)$this->Elca->getProjectVariantId()) {
                $this->Elca->setProjectVariantId($projectVariantId);

                $contentHeadView = $this->addView(new ElcaContentHeadView());
                $contentHeadView->assign('Project', $this->Elca->getProject());
            }
        }


        if ($this->Request->isPost() && $this->Request->has('cancel')) {
            if (isset($Namespace->FormData->searchForId) || isset($Namespace->FormData->replaceWithId)) {
                $this->updateHashUrl('/project-data/replaceProcesses/');
                $Namespace->FormData = (object)null;
                $View                = $this->setView(new ElcaProjectSearchAndReplaceProcessesView());
                $View->assign('Project', $project);
                $View->assign('ProjectVariant', $projectVariant);

                return;
            } else {
                $this->updateHashUrl('/project-data/variants/');

                return $this->variantsAction(false);
            }
        }


        if (($this->Request->has('openpcs') || $this->Request->has('processCategoryNodeId')) && !$this->Request->has(
                'select'
            )
        ) {
            $View = $this->setView(new ElcaProcessConfigSelectorView());
            $View->assign('buildMode', ElcaProcessConfigSelectorView::BUILDMODE_DEFAULT);
            $View->assign('context', 'project-data');
            $View->assign('submitAction', 'replaceProcesses');

            $what = $this->Request->has('f') ? $this->Request->f : $this->Request->data;
            $View->assign('data', $what);
            if ($what == 'search') {
                $View->assign('inUnit', null);
                $View->assign('filterByProjectVariantId', $projectVariantId);
            } else {
                if (isset($Namespace->FormData->searchForId)) {
                    $SearchFor = ElcaProcessConfig::findById($Namespace->FormData->searchForId);
                    list($RequiredConversions, $AvailableConversions) = $SearchFor->getRequiredConversions();

                    $availableUnits = array_unique(
                        $RequiredConversions->getArrayBy('inUnit', 'id') + $AvailableConversions->getArrayBy(
                            'inUnit',
                            'id'
                        )
                    );
                    $View->assign('inUnit', join(',', $availableUnits));
                }
            }

            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $View->assign(
                'processConfigId',
                $this->Request->sp ? $this->Request->sp : ($this->Request->id ? $this->Request->id : $this->Request->p)
            );

            $View->assign('epdSubType', $this->Request->epdSubType);

        } elseif ($this->Request->has('doReplace')) {
            $View = $this->setView(new ElcaProjectSearchAndReplaceProcessesView());
            $View->assign('Project', $project);
            $View->assign('ProjectVariant', $projectVariant);

            $FormData = $Namespace->FormData;
            $View->assign('FormData', $FormData);

            $Replace = ElcaProcessConfig::findById($FormData->replaceWithId);
            list($RequiredConversions, $AvailableConversions) = $Replace->getRequiredConversions();
            $availableUnits = array_flip(
                array_unique(
                    $RequiredConversions->getArrayBy('inUnit', 'id') + $AvailableConversions->getArrayBy('inUnit', 'id')
                )
            );

            $Search = ElcaProcessConfig::findById($FormData->searchForId);

            $ResultSet = ElcaSearchAndReplaceResultSet::findByProjectVariantIdAndProcessConfigId(
                $projectVariant->getId(),
                $Namespace->FormData->searchForId
            );

            $quantity = $conversionId = null;
            $updated  = 0;
            foreach ($ResultSet as $ResultItem) {
                if ($ResultItem->process_config_id != $Search->getId()) {
                    continue;
                }

                if (!isset($this->Request->replaceConfirmed[$ResultItem->id])) {
                    continue;
                }

                $ProcessConfig = ElcaProcessConfig::findById($ResultItem->process_config_id);
                $usedUnit      = $ResultItem->is_layer ? Elca::UNIT_M3 : $ResultItem->component_unit;
                if (!isset($availableUnits[$usedUnit])) {
                    if ($ResultItem->is_layer) {
                        continue;
                    } // Should not happen
                    else {
                        $outUnit = null;
                        $matrix = $ProcessConfig->getConversionMatrix($this->Elca->getProject()->getProcessDbId());
                        foreach ($matrix[$usedUnit] as $unit => $factor) {
                            if (isset($availableUnits[$unit])) {
                                $outUnit = $unit;
                                break;
                            }
                        }

                        if ($outUnit) {
                            $quantity     = $matrix[$usedUnit][$outUnit] * $ResultItem->quantity;
                            $conversionId = $availableUnits[$outUnit];
                        }
                        else {
                            $conversionId = null;
                        }
                    }
                } else {
                    $conversionId = $availableUnits[$usedUnit];
                }

                $ElementComponent = ElcaElementComponent::findById($ResultItem->id);
                $ElementComponent->setProcessConfigId($Replace->getId());

                if ($quantity) {
                    $ElementComponent->setQuantity($quantity);
                }

                if ($conversionId) {
                    $ElementComponent->setProcessConversionId($conversionId);
                }

                if (isset($this->Request->newLifetime[$ResultItem->id]) && $this->Request->newLifetime[$ResultItem->id]) {
                    $ElementComponent->setLifeTime($this->Request->newLifetime[$ResultItem->id]);
                }

                if ($ElementComponent->isLayer() &&
                    isset($this->Request->newLayerSize[$ResultItem->id]) && $this->Request->newLayerSize[$ResultItem->id]
                ) {
                    $ElementComponent->setLayerSize(
                        ElcaNumberFormat::fromString($this->Request->newLayerSize[$ResultItem->id]) / 1000
                    );
                }

                $ElementComponent->update();

                /**
                 * @var SearchAndReplaceObserver $observer
                 */
                foreach ($this->container->get('elca.search_and_replace_observers') as $observer) {
                    $observer->onElementComponenentSearchAndReplace(
                        $ElementComponent,
                        $Search->getId(),
                        $Replace->getId()
                    );
                }

                $this->container->get(ElcaLcaProcessor::class)->computeElementComponent($ElementComponent);
                $updated++;

                $quantity = $conversionId = null;
            }

            if ($updated > 0) {
                $this->container->get(ElcaLcaProcessor::class)->updateCache($project->getId());
                $this->messages->add(
                    t('Der Baustoff wurde %count% mal ersetzt.', null, ['%count%' => $updated]),
                    ElcaMessages::TYPE_INFO
                );
            } else {
                $this->messages->add(t('Der Baustoff wurde %count% mal ersetzt.', null, ['%count%' => $updated]));
            }


            if (isset($Namespace->FormData->searchForId)) {
                $ResultSet = ElcaSearchAndReplaceResultSet::findByProjectVariantIdAndProcessConfigId(
                    $projectVariantId,
                    $Namespace->FormData->searchForId,
                    true
                );
                $View->assign('ResultSet', $ResultSet);
            }

        } else {
            $View = $this->setView(new ElcaProjectSearchAndReplaceProcessesView());
            $View->assign('Project', $project);
            $View->assign('ProjectVariant', $projectVariant);

            $FormData = $Namespace->FormData;
            $View->assign('FormData', $FormData);

            if ($this->Request->has('select') && $this->Request->has('data') && in_array(
                    $this->Request->data,
                    ['search', 'replace']
                )
            ) {
                $ProcessConfig = ElcaProcessConfig::findById($this->Request->id);
                if ($this->Request->data == 'search') {
                    $FormData->searchForName = $ProcessConfig->getName();
                    $FormData->searchForId   = $ProcessConfig->getId();
                } else {
                    $FormData->replaceWithName = $ProcessConfig->getName();
                    $FormData->replaceWithId   = $ProcessConfig->getId();
                }
            }

            if (isset($Namespace->FormData->searchForId)) {
                $ResultSet = ElcaSearchAndReplaceResultSet::findByProjectVariantIdAndProcessConfigId(
                    $projectVariantId,
                    $Namespace->FormData->searchForId
                );
                $View->assign('ResultSet', $ResultSet);
            }
        }


        if ($this->isBaseRequest()) {
            $View = $this->addView(new ElcaProjectNavigationView());
            $View->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
        }
    }
    // End variantsAction

    /**
     * variants action
     *
     * lists all variants for the actual project for the actual project phase
     */
    protected function variantsAction($addNavigationViews = true)
    {
        $Project = $this->Elca->getProject();

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess($Project)) {
            return;
        }

        $ActProjectVariant        = $this->Elca->getProjectVariant();
        $conditions['project_id'] = $Project->getId();
        $conditions['phase_id']   = $ActProjectVariant->getPhaseId();

        $ProjectVariants = ElcaProjectVariantSet::find($conditions, ['created' => 'ASC']);
        $PhaseSet        = ElcaProjectPhaseSet::find();
        $phaseHash       = $PhaseSet->getArrayBy('name', 'id');

        $DO = new \stdClass;
        foreach ($ProjectVariants as $ProjectVariant) {
            $key                 = $ProjectVariant->getId();
            $DO->name[$key]      = $ProjectVariant->getName();
            $DO->phaseName[$key] = $phaseHash[$ProjectVariant->getPhaseId()];
            $DO->created[$key]   = BlibsDateTime::factory($ProjectVariant->getCreated())->getDateTimeString(
                    'd.m.Y, H:i'
                ) . ' ' . t('Uhr');
        }

        $View = $this->setView(new ElcaProjectVariantsView());
        $View->assign('Data', $DO);
        $View->assign('Project', $Project);
        $View->assign('phaseName', $ActProjectVariant->getPhase()->getName());
        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $View = $this->addView(new ElcaProjectNavigationView());
            $View->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
        }

        $this->Osit->add(new ElcaOsitItem(t('Projektvarianten'), null, t('Stammdaten')));
    }
    // End replaceProcessesAction

    /**
     * copyVariantAction - copies a variant and all data that is connected to it
     */
    protected function copyVariantAction()
    {
        if (!is_numeric($this->Request->id)) {
            return false;
        }

        $ProjectVariant = ElcaProjectVariant::findById($this->Request->id);

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess($ProjectVariant->getProject())) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($ProjectVariant->getProject())) {
            return;
        }

        // build new variant
        if ($this->Request->has('confirmed')) {
            $Project = $this->Elca->getProject();

            $NewProjectVariant = $this
                ->container->get(ProjectVariantService::class)
                           ->copy($ProjectVariant, $Project->getId());
            $NewProjectVariant->update();

            // update project current variant to new variant if actual phase is last phase from project
            if ($Project->getCurrentVariant()->getPhaseId() == $NewProjectVariant->getPhaseId()) {
                $Project->setCurrentVariantId($NewProjectVariant->getId());
                $Project->update();
            }

            $this->variantsAction(false);
            $ContentHead = $this->addView(new ElcaContentHeadView());
            $ContentHead->assign('Project', $Project);
        } // confirm box
        else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t(
                    'Soll für das Projekt in der Phase "%phaseName%" eine neue Variante erstellt werden?',
                    null,
                    ['%phaseName%' => $ProjectVariant->getPhase()->getName()]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End copyVariantAction


    /**
     * deleteVariantAction - deletes a variant and all data that is connected to it
     */
    protected function deleteVariantAction()
    {
        if (!is_numeric($this->Request->id)) {
            return false;
        }

        $ProjectVariant = ElcaProjectVariant::findById($this->Request->id);

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess($ProjectVariant->getProject())) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($ProjectVariant->getProject())) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            /**
             * Check for the current variant in the session
             */
            if ($this->Elca->getProjectVariantId() == $ProjectVariant->getId()) {
                $this->messages->add(t('Die aktive Variante kann nicht gelöscht werden!'), ElcaMessages::TYPE_ERROR);

                return;
            }

            /**
             * Check for the current variant on the project and update the project with
             * the current session variant id
             */
            $Project = $ProjectVariant->getProject();
            if ($Project->getCurrentVariantId() == $ProjectVariant->getId()) {
                $Project->setCurrentVariantId($this->Elca->getProjectVariantId());
                $Project->update();
            }

            // delete project variant
            $ProjectVariant->delete();

            // update content head view
            $ContentHead = $this->addView(new ElcaContentHeadView());
            $ContentHead->assign('Project', $Project);

            $this->variantsAction(false);
        } else {
            $ProjectVariant = ElcaProjectVariant::findById($this->Request->id);
            if ($this->Elca->getProjectVariantId() == $ProjectVariant->getId()) {
                $this->messages->add(
                    t('Die aktive Variante kann nicht gelöscht werden! Wechseln Sie zunächst die aktive Variante.'),
                    ElcaMessages::TYPE_INFO
                );

                return;
            }

            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t(
                    'Soll die Variante "%variantName%" wirklich gelöscht werden?',
                    null,
                    ['%variantName%' => $ProjectVariant->getName()]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End deleteVariantAction


    /**
     * saveVariantsAction - saves the new name of all variants deliverd in $this->Request->name
     * and updated the ElcaContentHeadView
     */
    protected function saveVariantsAction()
    {
        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if (!is_array($this->Request->name)) {
            $this->variantsAction();
        }

        $hasMessage = false;
        foreach ($this->Request->name as $variantId => $name) {
            $Variant = ElcaProjectVariant::findById($variantId);
            if (!$Variant->isInitialized()) {
                continue;
            }

            if ($Variant->getName() != $name) {
                $Variant->setName($name);
                $Variant->update();
                if (!$hasMessage) {
                    $this->messages->add(t('Die Änderungen wurden erfolgreich übernommen.'));
                    $hasMessage = true;

                    // update content head view
                    $ContentHead = $this->addView(new ElcaContentHeadView());
                    $ContentHead->assign('Project', $Variant->getProject());
                    $this->variantsAction(false);
                }
            }
        }
    }
    // End saveVariantAction

    /**
     * Saves ProjectFinalEnergyDemand
     */
    protected function saveEnEvAction()
    {
        if (!$this->Request->isPost()) {
            return false;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $addNewDemand     = isset($this->Request->addDemand) ? (bool)$this->Request->addDemand : false;
        $addNewKwkDemand  = isset($this->Request->addKwkDemand) ? (bool)$this->Request->addKwkDemand : false;
        $addNewSupply     = isset($this->Request->addSupply) ? (bool)$this->Request->addSupply : false;
        $addKwkInit       = isset($this->Request->addKwkInit) ? (bool)$this->Request->addKwkInit : false;
        $projectVariantId = $this->Request->projectVariantId;
        $validator        = new ElcaValidator($this->Request);
        $modified         = false;

        /**
         * Set view
         */
        $view = $this->setView(new ElcaProjectDataEnEvView());
        $view->assign('projectVariantId', $projectVariantId);
        $view->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        /**
         * Ngf
         */
        $ngf = ElcaProjectEnEv::findByProjectVariantId($projectVariantId);

        if ($this->Request->has('ngf')) {
            $validator->assertNotEmpty('ngf', null, t('Keine NGF-EnEv angegeben'));

            if ($validator->isValid()) {
                $ngfEnEv     = ElcaNumberFormat::fromString($this->Request->ngf);
                $enEvVersion = ElcaNumberFormat::fromString($this->Request->enEvVersion, 0);

                if ($ngf->isInitialized()) {
                    $ngfChanged = $ngf->getNgf() != $ngfEnEv;

                    $ngf->setProjectVariantId($projectVariantId);
                    $ngf->setNgf($ngfEnEv);
                    $ngf->setVersion($enEvVersion);
                    $ngf->update();
                    $modified = true;
                } else {
                    $ngf        = ElcaProjectEnEv::create($projectVariantId, $ngfEnEv, $enEvVersion);
                    $ngfChanged = true;
                }

                $ProjectConstruction = Elca::getInstance()->getProjectVariant()->getProjectConstruction();
                if ($ngfChanged && $ProjectConstruction->getNetFloorSpace() < $ngfEnEv) {
                    $this->messages->add(
                        t('Bezugsfläche NGF-EnEV ist größer als die Bezugsfläche NGF'),
                        ElcaMessages::TYPE_INFO
                    );
                }
            }
        }

        if (isset($this->Request->saveEnergyRefModel)) {
            $validator->assertProjectFinalEnergyRefModels();

            if ($validator->isValid() && is_array($this->Request->processConfigId)) {
                foreach ($this->Request->processConfigId as $ident => $processConfigId) {
                    if ($processConfigId) {
                        $modified |= $this->saveEnergyRefModel($ident);
                    }
                }

                $this->messages->add(t('Der Energiebedarf für das Referenzgebäude wurde gespeichert'));
            }
        } elseif (isset($this->Request->saveEnergyDemand)) {
            $projectKwk = ElcaProjectKwk::findByProjectVariantId($projectVariantId);

            $validator->assertProjectFinalEnergyDemands();

            if ($projectKwk->isInitialized()) {
                if (!$validator->assertProjectKwkFinalEnergyDemands()) {
                    $addNewKwkDemand = true;
                    $addKwkInit = true;
                }
            }

            if ($validator->isValid()) {
                $isKwk = $this->Request->isKwk;

                if ($this->Request->has('kwkName')) {
                    $name    = $this->Request->get('kwkName');
                    $heating = ElcaNumberFormat::fromString($this->Request->get('kwkHeating'));
                    $water   = ElcaNumberFormat::fromString($this->Request->get('kwkWater'));

                    if (!$projectKwk->isInitialized()) {
                        $projectKwk = ElcaProjectKwk::create($projectVariantId, $name, $heating, $water);
                    } else {
                        if (empty($name) && empty($heating) && (empty($water))) {
                            $projectKwk->delete();
                            $projectKwk = null;
                        } else {
                            $projectKwk->setName($name);
                            $projectKwk->setHeating($heating);
                            $projectKwk->setWater($water);
                            $projectKwk->update();
                        }
                    }
                }

                if (is_array($this->Request->processConfigId)) {
                    foreach ($this->Request->processConfigId as $key => $processConfigId) {
                        if ($projectKwk->isInitialized() && $isKwk[$key]) {
                            $modified |= $this->saveKwkEnergyDemand($projectKwk, $key);
                        } else {
                            $modified |= $this->saveEnergyDemand($key);
                        }
                    }

                    $this->messages->add(t('Der Energiebedarf wurde gespeichert'));
                }
            }
        } elseif (isset($this->Request->addEnergyDemand)) {
            $key = 'newDemand';

            $validator->assertProjectFinalEnergyDemand($key);

            if ($validator->isValid()) {
                /**
                 * Save previously added energy-carrier
                 */
                $modified = $this->saveEnergyDemand($key);
                $this->Request->__set('b', ElcaProcessConfigSelectorView::BUILDMODE_OPERATION);
                $this->Request->__set('processCategoryNodeId',
                    ElcaProcessCategory::findByRefNum(self::PROCESS_CATEGORY_DEFAULT_REF)->getNodeId());
                $this->selectProcessConfigAction($key);
            } else {
                $addNewDemand = true;
            }
        } elseif (isset($this->Request->addKwk)) {
                $projectKwk = ElcaProjectKwk::findByProjectVariantId($projectVariantId);

                if (!$projectKwk->isInitialized()) {
                    ElcaProjectKwk::create($projectVariantId, t('Fernwärme Mix'));
                    $addNewKwkDemand = true;
                }

                $addKwkInit = true;
        } elseif (isset($this->Request->addKwkEnergyDemand)) {
            $key = 'newKwkDemand';

            $validator->assertProjectFinalEnergyDemand($key);

            if ($validator->isValid()) {
                /**
                 * Save previously added energy-carrier
                 */
                $projectKwk = ElcaProjectKwk::findByProjectVariantId($projectVariantId);

                $modified = $this->saveKwkEnergyDemand($projectKwk, $key);
                $this->Request->__set('b', ElcaProcessConfigSelectorView::BUILDMODE_KWK);
                $this->Request->__set('processCategoryNodeId',
                    ElcaProcessCategory::findByRefNum(self::PROCESS_CATEGORY_KWK_DEFAULT_REF)->getNodeId());
                $this->selectProcessConfigAction($key);
            } else {
                $addNewKwkDemand = true;
            }
        } elseif (isset($this->Request->saveEnergySupply)) {
            if ($this->Access->canEditFinalEnergySupplies()) {
                $validator->assertProjectFinalEnergySupplies();
                if ($validator->isValid() && is_array($this->Request->processConfigId)) {
                    foreach ($this->Request->processConfigId as $key => $processConfigId) {
                        $modified |= $this->saveEnergySupply($key);
                    }

                    $this->messages->add(t('Die Energiebereitstellung wurde gespeichert'));
                }
            }
        } elseif (isset($this->Request->addEnergySupply)) {
            if ($this->Access->canEditFinalEnergySupplies()) {

                $key = 'newSupply';
                if ($validator->isValid()) {
                    /**
                     * Save previously added energy-carrier
                     */
                    $modified = $this->saveEnergySupply($key);
                    $this->Request->__set('b', ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY);
                    $this->Request->__set('processCategoryNodeId',
                        ElcaProcessCategory::findByRefNum(self::PROCESS_CATEGORY_DEFAULT_REF)->getNodeId());
                    $this->selectProcessConfigAction($key);
                } else {
                    $addNewSupply = true;
                }
            }
        }

        if ($validator->isValid()) {
            $addNewDemand = $addNewSupply = $addNewKwkDemand = false;
        } else {
            foreach ($validator->getErrors() as $property => $message) {
                if ($message != Validator::ERR_INVALID) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }

            $view->assign('Validator', $validator);
        }

        /**
         * Assign addNewProjectFinalEnergyDemand to keep new ProjectFinalEnergyDemand at view
         */
        $view->assign('addNewProjectFinalEnergyDemand', $addNewDemand);
        $view->assign('addNewProjectKwkFinalEnergyDemand', $addNewKwkDemand);
        $view->assign('addNewProjectFinalEnergySupply', $addNewSupply);
        $view->assign('addKwkInit', $addKwkInit);
        $view->assign('ngf', $ngf->getNgf());
        $view->assign('enEvVersion', $ngf->getVersion());

        $view->assign('Data', $this->getFinalEnergyDataObject($projectVariantId));

        if ($modified) {
            $projectVariant = ElcaProjectVariant::findById($projectVariantId);
            $this->container->get(ElcaLcaProcessor::class)
                            ->computeFinalEnergy($projectVariant)
                            ->updateCache($projectVariant->getProjectId());
        }

        return $modified;
    }
    // End enEvAction

    /**
     * Saves a energy supply
     *
     * @param  String $ident
     *
     * @return bool  $modified
     */
    protected function saveEnergyRefModel($ident)
    {
        $projectVariantId = $this->Request->projectVariantId;
        $heating          = $this->Request->heating[$ident] ? ElcaNumberFormat::fromString(
            $this->Request->heating[$ident],
            2
        ) : null;
        $water            = $this->Request->water[$ident] ? ElcaNumberFormat::fromString(
            $this->Request->water[$ident],
            2
        ) : null;
        $lighting         = $this->Request->lighting[$ident] ? ElcaNumberFormat::fromString(
            $this->Request->lighting[$ident],
            2
        ) : null;
        $ventilation      = $this->Request->ventilation[$ident] ? ElcaNumberFormat::fromString(
            $this->Request->ventilation[$ident],
            2
        ) : null;
        $cooling          = $this->Request->cooling[$ident] ? ElcaNumberFormat::fromString(
            $this->Request->cooling[$ident],
            2
        ) : null;

        $modified = false;

        $RefModel = ElcaProjectFinalEnergyRefModel::findByProjectVariantIdAndIdent($projectVariantId, $ident);
        $ngf      = ElcaProjectVariant::findById($this->Request->projectVariantId)->getProjectConstruction(
        )->getNetFloorSpace();

        if ($ident === ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY) {
            /**
             * Process energy is given by kWh per year.
             * Need to be computed into kWh per year and ngf
             */
            if ($heating) {
                $heating /= $ngf;
            }
            if ($water) {
                $water /= $ngf;
            }
            if ($lighting) {
                $lighting /= $ngf;
            }
            if ($ventilation) {
                $ventilation /= $ngf;
            }
            if ($cooling) {
                $cooling /= $ngf;
            }
        }


        if ($RefModel->isInitialized()) {

            if ($RefModel->getHeating() != $heating) {
                $RefModel->setHeating($heating);
                $modified = true;
            }
            if ($RefModel->getWater() != $water) {
                $RefModel->setWater($water);
                $modified = true;
            }
            if ($RefModel->getLighting() != $lighting) {
                $RefModel->setLighting($lighting);
                $modified = true;
            }
            if ($RefModel->getVentilation() != $ventilation) {
                $RefModel->setVentilation($ventilation);
                $modified = true;
            }
            if ($RefModel->getCooling() != $cooling) {
                $RefModel->setCooling($cooling);
                $modified = true;
            }

            if ($modified) {
                $RefModel->update();
            }
        } else {
            $RefModel = ElcaProjectFinalEnergyRefModel::create(
                $this->Request->projectVariantId,
                $ident,
                $heating,
                $water,
                $lighting,
                $ventilation,
                $cooling
            );
            $modified = true;
        }


        return $modified;
    }
    // End selectProcessConfigAction

    /**
     * Saves a energy demand
     *
     * @param  String $key
     *
     * @return bool  $modified
     */
    protected function saveEnergyDemand($key)
    {
        if (!isset($this->Request->processConfigId[$key])) {
            return false;
        }

        if (isset($this->Request->isKwk) && $this->Request->isKwk[$key]) {
            return false;
        }

        $processConfigId = $this->Request->processConfigId[$key];
        $heating         = $this->Request->heating[$key] ? ElcaNumberFormat::fromString(
            $this->Request->heating[$key],
            2
        ) : null;
        $water           = $this->Request->water[$key] ? ElcaNumberFormat::fromString($this->Request->water[$key], 2)
            : null;
        $lighting        = $this->Request->lighting[$key] ? ElcaNumberFormat::fromString(
            $this->Request->lighting[$key],
            2
        ) : null;
        $ventilation     = $this->Request->ventilation[$key] ? ElcaNumberFormat::fromString(
            $this->Request->ventilation[$key],
            2
        ) : null;
        $cooling         = $this->Request->cooling[$key] ? ElcaNumberFormat::fromString(
            $this->Request->cooling[$key],
            2
        ) : null;

        $modified = false;

        if (is_numeric($key)) {
            $energyDemand = ElcaProjectFinalEnergyDemand::findById($key);
        } elseif ($key === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
            $energyDemand = ElcaProjectFinalEnergyDemand::findByProjectVariantIdAndIdent(
                $this->Request->projectVariantId,
                $key
            );
            $ngf          = ElcaProjectVariant::findById($this->Request->projectVariantId)->getProjectConstruction(
            )->getNetFloorSpace();

            /**
             * Process energy is given by kWh per year.
             * Need to be computed into kWh per year and ngf
             */
            if ($heating) {
                $heating /= $ngf;
            }
            if ($water) {
                $water /= $ngf;
            }
            if ($lighting) {
                $lighting /= $ngf;
            }
            if ($ventilation) {
                $ventilation /= $ngf;
            }
            if ($cooling) {
                $cooling /= $ngf;
            }

        } else {
            $energyDemand = ElcaProjectFinalEnergyDemand::findById(null);
        }

        if ($energyDemand->isInitialized()) {
            if ($energyDemand->getProcessConfigId() != $processConfigId) {
                $energyDemand->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if ($energyDemand->getHeating() != $heating) {
                $energyDemand->setHeating($heating);
                $modified = true;
            }
            if ($energyDemand->getWater() != $water) {
                $energyDemand->setWater($water);
                $modified = true;
            }
            if ($energyDemand->getLighting() != $lighting) {
                $energyDemand->setLighting($lighting);
                $modified = true;
            }
            if ($energyDemand->getVentilation() != $ventilation) {
                $energyDemand->setVentilation($ventilation);
                $modified = true;
            }
            if ($energyDemand->getCooling() != $cooling) {
                $energyDemand->setCooling($cooling);
                $modified = true;
            }

            if ($modified) {
                $energyDemand->update();
            }
        } else {
            ElcaProjectFinalEnergyDemand::create(
                $this->Request->projectVariantId,
                $processConfigId,
                $heating,
                $water,
                $lighting,
                $ventilation,
                $cooling,
                $key === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY
                    ? ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY : null
            );
            $modified = true;
        }

        return $modified;
    }

    /**
     * Saves a energy demand
     *
     * @param  String $key
     *
     * @return bool  $modified
     */
    protected function saveKwkEnergyDemand(ElcaProjectKwk $projectKwk, $key)
    {
        if (!isset($this->Request->processConfigId[$key])) {
            return false;
        }

        if (isset($this->Request->isKwk) && !$this->Request->isKwk[$key]) {
            return false;
        }

        $processConfigId = $this->Request->processConfigId[$key];
        $heating         = $projectKwk->getHeating();
        $water           = $projectKwk->getWater();
        $ratio           = ElcaNumberFormat::fromString($this->Request->ratio[$key], 4, true);

        $modified = false;

        if (is_numeric($key)) {
            $energyDemand = ElcaProjectFinalEnergyDemand::findById($key);
        } else {
            $energyDemand = ElcaProjectFinalEnergyDemand::findById(null);
        }

        if ($energyDemand->isInitialized()) {
            if ($energyDemand->getProcessConfigId() != $processConfigId) {
                $energyDemand->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if ($energyDemand->getHeating() != $heating) {
                $energyDemand->setHeating($heating);
                $modified = true;
            }
            if ($energyDemand->getWater() != $water) {
                $energyDemand->setWater($water);
                $modified = true;
            }

            if ($energyDemand->getRatio() != $ratio) {
                $energyDemand->setRatio($ratio);
                $modified = true;
            }

            if ($modified) {
                $energyDemand->update();
            }
        } else {
            ElcaProjectFinalEnergyDemand::create(
                $this->Request->projectVariantId,
                $processConfigId,
                $heating,
                $water,
                null,
                null,
                null,
                null,
                $ratio,
                $projectKwk->getId()
            );
            $modified = true;
        }

        return $modified;
    }

    /**
     * Action selectProcessConfig
     *
     * @param null $key
     */
    protected function selectProcessConfigAction($key = null)
    {
        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        /**
         * If a term was send, autocomplete term
         */
        if (isset($this->Request->term)) {
            $keywords                 = explode(' ', \trim((string)$this->Request->term));
            $inUnit                   = $this->Request->has('u') ? $this->Request->get('u') : null;
            $filterByProjectVariantId = $this->Request->has('filterByProjectVariantId') ? $this->Request->get(
                'filterByProjectVariantId'
            ) : null;

            /**
             * @todo: modify for buildmode operation
             */
            switch ($this->Request->b) {
                case ElcaProcessConfigSelectorView::BUILDMODE_KWK:
                    $Results = ElcaProcessConfigSearchSet::findKwkByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        $this->Elca->getProject()->getProcessDbId()
                    );
                    break;

                case ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY:
                    $Results = ElcaProcessConfigSearchSet::findFinalEnergySuppliesByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        $this->Elca->getProject()->getProcessDbId()
                    );
                    break;

                default:
                    $Results = ElcaProcessConfigSearchSet::findByKeywords(
                        $keywords,
                        $this->Elca->getLocale(),
                        $inUnit,
                        !$this->Access->hasAdminPrivileges(),
                        [$this->Elca->getProject()->getProcessDbId()],
                        $filterByProjectVariantId,
                        $this->Request->epdSubType
                    );
            }
            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name . ' > ' . $Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        } /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!isset($this->Request->select)) {

            $View = $this->setView(new ElcaProcessConfigSelectorView());
            $View->assign(
                'processConfigId',
                $this->Request->sp ? $this->Request->sp : ($this->Request->id ? $this->Request->id : $this->Request->p)
            );
            $View->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $View->assign('projectVariantId', $this->Request->projectVariantId);
            $View->assign('buildMode', $this->Request->b);
            $View->assign('context', self::CONTEXT);
            $View->assign('relId', $this->Request->relId ? $this->Request->relId : $key);

            if ($this->Request->b == ElcaProcessConfigSelectorView::BUILDMODE_OPERATION ||
                $this->Request->b == ElcaProcessConfigSelectorView::BUILDMODE_KWK ||
                $this->Request->b == ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY
            ) {
                if (isset($this->Request->ngf)) {
                    $ngf         = $this->Request->ngf;
                    $enEvVersion = $this->Request->enEvVersion;
                } else {
                    $Ngf         = ElcaProjectEnEv::findByProjectVariantId($this->Request->projectVariantId);
                    $ngf         = $Ngf->getNgf();
                    $enEvVersion = $Ngf->getVersion();
                }
                $View->assign('ngf', $ngf);
                $View->assign('enEvVersion', $enEvVersion);
            }
        } /**
         * If user pressed select button, assign the new process
         */
        elseif (isset($this->Request->select)) {
            /**
             * Set view
             */
            switch ($this->Request->b) {
                case ElcaProcessConfigSelectorView::BUILDMODE_OPERATION:
                case ElcaProcessConfigSelectorView::BUILDMODE_KWK:
                case ElcaProcessConfigSelectorView::BUILDMODE_FINAL_ENERGY_SUPPLY:
                    $this->selectFinalEnergyProcessConfig();
                    break;

                case ElcaProcessConfigSelectorView::BUILDMODE_TRANSPORTS:
                    $this->selectTransportProcessConfig();
                    break;
            }
        }
    }

    /**
     * @param int $projectVariantId
     *
     * @return \StdClass
     */
    protected function getFinalEnergyDataObject($projectVariantId, $addSupply = false)
    {
        $data           = new \stdClass();
        $data->Demand   = new \stdClass();
        $data->Kwk      = (object)['id' => null, 'name' => t('KWK / Fernwärme'), 'heating' => null, 'water' => null, 'overall' => 0];
        $data->Supply   = new \stdClass();
        $data->RefModel = new \stdClass();

        $ngf = ElcaProjectVariant::findById($projectVariantId)->getProjectConstruction()->getNetFloorSpace();

        $kwk = ElcaProjectKwkSet::findByProjectVariantId($projectVariantId)->current();

        if ($kwk) {
            $data->Kwk->id = $kwk->getId();
            $data->Kwk->name = $kwk->getName();
            $data->Kwk->heating = $kwk->getHeating();
            $data->Kwk->water = $kwk->getWater();
        }

        $projectFinalEnergyDemandSet = ElcaProjectFinalEnergyDemandSet::find(
            ['project_variant_id' => $projectVariantId],
            ['id' => 'ASC']
        );
        $kwkProjectFinalEnergyDemands = [];
        foreach ($projectFinalEnergyDemandSet as $projectFinalEnergyDemand) {
            if ($projectFinalEnergyDemand->isKwk()) {
                $kwkProjectFinalEnergyDemands[] = $projectFinalEnergyDemand;
                continue;
            }

            $overall = 0;

            if ($projectFinalEnergyDemand->getIdent() === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
                $key    = ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY;
                $factor = $ngf;
            } else {
                $key    = $projectFinalEnergyDemand->getId();
                $factor = 1;
            }

            $data->Demand->processConfigId[$key] = $projectFinalEnergyDemand->getProcessConfigId();
            $overall                             += $data->Demand->heating[$key] = ($projectFinalEnergyDemand->getHeating(
            ) ? $projectFinalEnergyDemand->getHeating() * $factor : null);
            $overall                             += $data->Demand->water[$key] = ($projectFinalEnergyDemand->getWater()
                ? $projectFinalEnergyDemand->getWater() * $factor : null);
            $overall                             += $data->Demand->lighting[$key] = ($projectFinalEnergyDemand->getLighting(
            ) ? $projectFinalEnergyDemand->getLighting() * $factor : null);
            $overall                             += $data->Demand->ventilation[$key] = ($projectFinalEnergyDemand->getVentilation(
            ) ? $projectFinalEnergyDemand->getVentilation() * $factor : null);
            $overall                             += $data->Demand->cooling[$key] = ($projectFinalEnergyDemand->getCooling(
            ) ? $projectFinalEnergyDemand->getCooling() * $factor : null);
            $data->Demand->overall[$key]         = $overall / $factor;
            $data->Demand->toggle[$key]          = 0;
            $data->Demand->isKwk[$key]           = false;
        }

        foreach ($kwkProjectFinalEnergyDemands as $kwkProjectFinalEnergyDemand) {
            $overall = 0;

            $key   = $kwkProjectFinalEnergyDemand->getId();
            $ratio = $kwkProjectFinalEnergyDemand->getRatio();

            $data->Demand->processConfigId[$key] = $kwkProjectFinalEnergyDemand->getProcessConfigId();
            $data->Demand->ratio[$key] = $ratio;

            $overall                             += $data->Demand->heating[$key] = $kwkProjectFinalEnergyDemand->getHeating()
                ? $kwkProjectFinalEnergyDemand->getHeating() * $ratio
                : 0;
            $overall                             += $data->Demand->water[$key] = $kwkProjectFinalEnergyDemand->getWater()
                ? $kwkProjectFinalEnergyDemand->getWater() * $ratio
                : 0;

            $data->Demand->overall[$key]         = $overall;
            $data->Demand->toggle[$key]          = 0;
            $data->Demand->isKwk[$key]           = true;

            $data->Kwk->overall += $data->Demand->overall[$key];
        }

        $ProjectFinalEnergySupplySet = ElcaProjectFinalEnergySupplySet::find(
            ['project_variant_id' => $projectVariantId],
            ['id' => 'ASC']
        );
        foreach ($ProjectFinalEnergySupplySet as $ProjectFinalEnergySupply) {
            $key                                 = $ProjectFinalEnergySupply->getId();
            $data->Supply->processConfigId[$key] = $ProjectFinalEnergySupply->getProcessConfigId();
            $data->Supply->description[$key]     = $ProjectFinalEnergySupply->getDescription();
            $data->Supply->enEvRatio[$key]       = $ProjectFinalEnergySupply->getEnEvRatio();
            $data->Supply->quantity[$key]        = $ProjectFinalEnergySupply->getQuantity();
            $data->Supply->toggle[$key]          = 0;
            $data->Supply->overall[$key]         = $ProjectFinalEnergySupply->getQuantity(
                ) * (1 - $ProjectFinalEnergySupply->getEnEvRatio());
        }

        if ($addSupply) {
            $data->Supply->enEvRatio['newSupply'] = 0;
        }

        $Project = ElcaProjectVariant::findById($projectVariantId)->getProject();
        if ($Project->getBenchmarkVersionId()) {
            $refProcessConfigs = ElcaBenchmarkRefProcessConfigSet::find(
                ['benchmark_version_id' => $Project->getBenchmarkVersionId()]
            )->getArrayBy('processConfigId', 'ident');
            $projectRefModels  = ElcaProjectFinalEnergyRefModelSet::find(
                ['project_variant_id' => $projectVariantId]
            )->getArrayCopy('ident');
            foreach (
                [
                    ElcaBenchmarkRefProcessConfig::IDENT_HEATING,
                    ElcaBenchmarkRefProcessConfig::IDENT_ELECTRICITY,
                    ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY,
                ] as $ident
            ) {
                /** @var ElcaProjectFinalEnergyRefModel $RefModel */
                $RefModel = isset($projectRefModels[$ident]) ? $projectRefModels[$ident] : null;
                $overall  = 0;

                if ($ident === ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY) {
                    $factor = $ngf;
                } else {
                    $factor = 1;
                }
                $data->RefModel->processConfigId[$ident] = isset($refProcessConfigs[$ident])
                    ? $refProcessConfigs[$ident] : null;
                $overall                                 += $data->RefModel->heating[$ident] = $RefModel && $RefModel->getHeating(
                ) ? $RefModel->getHeating() * $factor : null;
                $overall                                 += $data->RefModel->water[$ident] = $RefModel && $RefModel->getWater(
                ) ? $RefModel->getWater() * $factor : null;
                $overall                                 += $data->RefModel->lighting[$ident] = $RefModel && $RefModel->getLighting(
                ) ? $RefModel->getLighting() * $factor : null;
                $overall                                 += $data->RefModel->ventilation[$ident] = $RefModel && $RefModel->getVentilation(
                ) ? $RefModel->getVentilation() * $factor : null;
                $overall                                 += $data->RefModel->cooling[$ident] = $RefModel && $RefModel->getCooling(
                ) ? $RefModel->getCooling() * $factor : null;
                $data->RefModel->overall[$ident]         = $overall / $factor;
                $data->RefModel->toggle[$ident]          = 0;
            }
        }

        return $data;
    }
    // End saveEnergyDemand

    /**
     * Saves a energy supply
     *
     * @param  String $key
     *
     * @return bool  $modified
     */
    protected function saveEnergySupply($key)
    {
        if (!isset($this->Request->processConfigId[$key])) {
            return false;
        }

        $processConfigId = $this->Request->processConfigId[$key];
        $description     = \trim((string)($this->Request->description[$key]));
        $enEvRatio       = $this->Request->enEvRatio[$key] ? ElcaNumberFormat::fromString(
            $this->Request->enEvRatio[$key],
            2,
            true
        ) : 0;
        $quantity        = $this->Request->quantity[$key] ? ElcaNumberFormat::fromString(
            $this->Request->quantity[$key],
            2
        ) : 0;

        $modified = false;

        if (is_numeric($key)) {
            $EnergySupply = ElcaProjectFinalEnergySupply::findById($key);

            if ($EnergySupply->getProcessConfigId() != $processConfigId) {
                $EnergySupply->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if ($EnergySupply->getDescription() != $description) {
                $EnergySupply->setDescription($description);
                $modified = true;
            }
            if ($EnergySupply->getEnEvRatio() != $enEvRatio) {
                $EnergySupply->setEnEvRatio($enEvRatio);
                $modified = true;
            }
            if ($EnergySupply->getQuantity() != $quantity) {
                $EnergySupply->setQuantity($quantity);
                $modified = true;
            }

            if ($modified) {
                $EnergySupply->update();
            }
        } else {
            ElcaProjectFinalEnergySupply::create(
                $this->Request->projectVariantId,
                $processConfigId,
                $quantity,
                $description,
                $enEvRatio
            );
            $modified = true;
        }

        return $modified;
    }
    // End saveEnergySupply

    /**
     * Removes a Project Final Energy Demand
     */
    protected function deleteFinalEnergyDemandAction()
    {
        if (!is_numeric($this->Request->id)) {
            return false;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            $ProjectFinalEnergyDemand = ElcaProjectFinalEnergyDemand::findById($this->Request->id);

            $Dbh = DbHandle::getInstance();
            if ($ProjectFinalEnergyDemand->isInitialized()) {
                $projectVariantId = $ProjectFinalEnergyDemand->getProjectVariantId();
                $Dbh->begin();

                /**
                 * Delete component
                 */
                $ProjectFinalEnergyDemand->delete();

                /**
                 * Compute lca
                 */
                $projectVariant = ElcaProjectVariant::findById($projectVariantId);
                $this->container->get(ElcaLcaProcessor::class)
                                ->computeFinalEnergy($projectVariant)
                                ->updateCache($projectVariant->getProjectId(), $projectVariantId);

                $Dbh->commit();

                /**
                 * Refresh view
                 */
                $this->enEvAction($projectVariantId);
                $this->messages->add(t('Der Energieträger wurde gelöscht.'));

                return true;
            }
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t('Soll der Energieträger wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }

        return false;
    }
    // End saveEnergyRefModel

    /**
     * enEv Action
     */
    protected function enEvAction()
    {
        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        $ProjectVariant = $this->Elca->getProjectVariant();

        $Ngf = ElcaProjectEnEv::findByProjectVariantId($ProjectVariant->getId());

        $View = $this->setView(new ElcaProjectDataEnEvView());
        $View->assign('projectVariantId', $ProjectVariant->getId());
        $View->assign('Data', $this->getFinalEnergyDataObject($ProjectVariant->getId()));
        $View->assign('ngf', $Ngf->getNgf());
        $View->assign('enEvVersion', $Ngf->getVersion());
        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        $this->Osit->add(
            new ElcaOsitItem(
                $ProjectVariant->getPhase()->getStep() ? t('Endenergiebilanz') : t('Prognose'),
                null,
                t('Stammdaten')
            )
        );

        /**
         * Render complete navigation on reload
         */
        $View = $this->addView(new ElcaProjectNavigationView());
        $View->assign('activeCtrlName', get_class());

        $this->addView(new ElcaProjectNavigationLeftView());
    }
    // End deleteFinalEnergyDemandAction

    /**
     * Removes a Project Final Energy Supply
     */
    protected function deleteFinalEnergySupplyAction()
    {
        if (!is_numeric($this->Request->id)) {
            return false;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            $ProjectFinalEnergySupply = ElcaProjectFinalEnergySupply::findById($this->Request->id);

            $Dbh = DbHandle::getInstance();
            if ($ProjectFinalEnergySupply->isInitialized()) {
                $projectVariantId = $ProjectFinalEnergySupply->getProjectVariantId();
                $Dbh->begin();

                /**
                 * Delete component
                 */
                $ProjectFinalEnergySupply->delete();

                /**
                 * Compute lca
                 */
                $projectVariant = ElcaProjectVariant::findById($projectVariantId);
                $this->container->get(ElcaLcaProcessor::class)
                                ->computeFinalEnergy($projectVariant)
                                ->updateCache($projectVariant->getProjectId(), $projectVariantId);

                $Dbh->commit();

                /**
                 * Refresh view
                 */
                $this->enEvAction($projectVariantId);
                $this->messages->add(t('Der Energieträger wurde gelöscht.'));

                return true;
            }
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t('Soll der Energieträger wirklich gelöscht werden?'),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }

        return false;
    }
    // End deleteFinalEnergyDemandAction

    /**
     * Save benchmarks action
     *
     * @return void -
     */
    protected function saveBenchmarksAction()
    {
        if (!$this->Request->isPost() || !$this->Request->projectVariantId) {
            return false;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $Validator = new ElcaValidator($this->Request);

        $benchmarks       = $this->Request->getArray('benchmark');
        $projectVariantId = $this->Request->projectVariantId;

        foreach ($benchmarks as $key => $value) {
            $value = ElcaNumberFormat::fromString($value, 0);

            $Indicator = ElcaIndicator::findById($key);
            $maxRate   = $Indicator->getIdent() == ElcaIndicator::IDENT_PE_EM ? 50 : 100;

            if (!$Validator->assertTrue(
                'benchmark[' . $key . ']',
                $value >= 0 && $value <= $maxRate,
                t('Der Zielwert muss zwischen 0 und %maxRate% liegen', null, ['%maxRate%' => $maxRate])
            )
            ) {
                continue;
            }

            $Benchmark = ElcaProjectIndicatorBenchmark::findByPk($projectVariantId, $key);
            if ($Benchmark->isInitialized()) {
                if ($value) {
                    $Benchmark->setBenchmark($value);
                    $Benchmark->update();
                } else {
                    $Benchmark->delete();
                }
            } elseif ($value) {
                ElcaProjectIndicatorBenchmark::create($projectVariantId, $key, $value);
            }
        }

        if ($Validator->isValid()) {
            $Validator = null;
        } else {
            foreach ($Validator->getErrors() as $property => $message) {
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }
        }

        $this->benchmarksAction(false, $Validator);
    }
    // End getFinalEnergyDataObject

    /**
     * Benchmark action
     *
     * @param bool      $addNavigationViews
     * @param Validator $Validator
     *
     * @return void -
     */
    protected function benchmarksAction($addNavigationViews = true, Validator $Validator = null)
    {
        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        $projectVariantId = $this->Elca->getProjectVariantId();

        $Data            = new \stdClass();
        $Data->benchmark = [];

        $Benchmarks = ElcaProjectIndicatorBenchmarkSet::find(
            ['project_variant_id' => $projectVariantId],
            ['indicator_id' => 'ASC']
        );
        foreach ($Benchmarks as $IndicatorBenchmark) {
            $key                   = $IndicatorBenchmark->getIndicatorId();
            $Data->benchmark[$key] = $IndicatorBenchmark->getBenchmark();
        }

        $View = $this->setView(new ElcaProjectDataBenchmarksView());
        $View->assign('projectVariantId', $projectVariantId);
        $View->assign('Data', $Data);
        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));


        if ($Validator) {
            $View->assign('Validator', $Validator);
        }

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $View = $this->addView(new ElcaProjectNavigationView());
            $View->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Zielwerte'), null, t('Stammdaten')));
        }
    }
    // End benchmarksAction

    /**
     *
     */
    protected function addTransportMeanAction()
    {
        if (!$this->Request->id) {
            return;
        }

        $transportId = $this->Request->id;

        $this->transportsAction(false);
        $View = $this->getViewByName(ElcaProjectDataTransportsView::class);
        $View->assign('transportId', $transportId);
        $View->assign('addNewTransportMean', true);
    }
    // End saveBenchmarksAction

    /**
     * Benchmark action
     *
     * @param bool      $addNavigationViews
     * @param Validator $Validator
     *
     * @return void -
     */
    protected function transportsAction($addNavigationViews = true, Validator $Validator = null)
    {
        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        $projectVariantId = $this->Elca->getProjectVariantId();

        $Data = new \stdClass();
        foreach (
            ElcaProjectTransportSet::findByProjectVariantId(
                $projectVariantId,
                ['id' => 'ASC']
            ) as $Transport
        ) {
            $key                = $Transport->getId();
            $Data->includeInLca = $Transport->getCalcLca();

            $Data->matProcessConfigId[$key] = $Transport->getProcessConfigId();
            $Data->name[$key]               = $Transport->getName();
            $Data->quantity[$key]           = $Transport->getQuantity();

            foreach (ElcaProjectTransportMeanSet::findByProjectTransportId($key) as $TransportMean) {
                $meanKey = $key . '-' . $TransportMean->getId();

                $Data->processConfigId[$meanKey] = $processConfigId = $TransportMean->getProcessConfigId();
                $Data->distance[$meanKey]        = $TransportMean->getDistance();
                $Data->efficiency[$meanKey]      = $TransportMean->getEfficiency();
            }
        }

        $View = $this->setView(new ElcaProjectDataTransportsView());
        $View->assign('projectVariantId', $projectVariantId);
        $View->assign('Data', $Data);
        $View->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        if ($Validator) {
            $View->assign('Validator', $Validator);
        }

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $View = $this->addView(new ElcaProjectNavigationView());
            $View->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
            $this->Osit->add(new ElcaOsitItem(t('Transportrechner'), null, t('Transporte')));
        }
    }
    // End transportsAction

    /**
     *
     */
    protected function deleteTransportMeanAction()
    {
        if (!$this->Request->id) {
            return;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $TransportMean = ElcaProjectTransportMean::findById($this->Request->id);
        if (!$TransportMean->isInitialized()) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            $projectVariantId = $TransportMean->getProjectTransport()->getProjectVariantId();

            if (ElcaProjectTransportMeanSet::dbCount(
                    ['project_transport_id' => $TransportMean->getProjectTransportId()]
                ) - 1 > 0
            ) {
                $TransportMean->delete();
            } else {
                $TransportMean->getProjectTransport()->delete();
            }

            $projectVariant = ElcaProjectVariant::findById($projectVariantId);
            $this->container->get(ElcaLcaProcessor::class)
                            ->computeTransports($projectVariant)
                            ->updateCache($projectVariant->getProjectId());

            $this->transportsAction(false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            if (ElcaProjectTransportMeanSet::dbCount(
                    ['project_transport_id' => $TransportMean->getProjectTransportId()]
                ) - 1 > 0
            ) {
                $msg = t(
                    'Transportmittel "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $TransportMean->getProcessConfig()->getName()]
                );
            } else {
                $msg = t(
                    'Kompletten Transport "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $TransportMean->getProcessConfig()->getName()]
                );
            }

            $this->messages->add(
                $msg,
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }
    // End addTransportMeanAction

    /**
     *
     */
    protected function saveTransportsAction()
    {
        if (!$this->Request->isPost() || !$this->Request->projectVariantId) {
            return;
        }

        /**
         * Check permissions
         */
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $projectVariantId = $this->Request->projectVariantId;

        if ($this->Request->has('saveTransports')) {

            $Validator = new ElcaValidator($this->Request);
            $Validator->assertTransports();

            $matProcessConfigIds = $this->Request->getArray('matProcessConfigId');
            $processConfigIds    = $this->Request->getArray('processConfigId');

            if ($Validator->isValid()) {
                $needLcaComputation = false;

                $Dbh = DbHandle::getInstance();
                try {
                    $Dbh->begin();

                    foreach ($matProcessConfigIds as $key => $foo) {
                        list($Transport, $modified) = $this->saveProjectTransport($projectVariantId, $key);

                        if ($modified) {
                            $needLcaComputation = true;
                        }

                        $found = 0;
                        foreach ($processConfigIds as $transportMeanKey => $bar) {
                            list($transportKey, $meanKey) = explode('-', $transportMeanKey);

                            if ($transportKey != $key) {
                                continue;
                            }

                            if ($modified = $this->saveProjectTransportMean($Transport, $transportKey, $meanKey)) {
                                $needLcaComputation = true;
                            }

                            if (!is_null($modified)) {
                                $found++;
                            }
                        }

                        if (!$found) {
                            throw new Exception('Tried to create transport without transport means');
                        }
                    }

                    $Dbh->commit();
                }
                catch (Exception $Exception) {
                    $Dbh->rollback();
                    throw $Exception;
                }

                /**
                 * Compute LCA
                 */
                if ($needLcaComputation) {
                    $projectVariant = ElcaProjectVariant::findById($projectVariantId);
                    $this->container->get(ElcaLcaProcessor::class)
                                    ->computeTransports($projectVariant)
                                    ->updateCache($projectVariant->getProjectId());
                }

                $this->messages->add(t('Die Daten wurden gespeichert'));
                $this->transportsAction(false);
            } else {
                $addNewTransportMeanFor = null;
                foreach ($Validator->getErrors() as $property => $msg) {
                    $this->messages->add(t($msg), ElcaMessages::TYPE_ERROR);
                }
                $this->transportsAction(false, $Validator);
                $View = $this->getViewByName('Elca\View\ElcaProjectDataTransportsView');
                $View->assign('addNewTransport', isset($matProcessConfigIds['new']));
            }
        } elseif ($this->Request->has('addTransport')) {
            $this->transportsAction(false);
            $View = $this->getViewByName('Elca\View\ElcaProjectDataTransportsView');
            $View->assign('addNewTransport', true);
        }
    }
    // End deleteTransportMeanAction

    /**
     * Saves a transport with all its tranport means
     *
     * @param $projectVariantId
     * @param $key
     *
     * @return array(ElcaProjectTransport, boolean)
     */
    protected function saveProjectTransport($projectVariantId, $key)
    {
        if (!isset($this->Request->matProcessConfigId[$key])) {
            return null;
        }

        $processConfigId = $this->Request->matProcessConfigId[$key] ? $this->Request->matProcessConfigId[$key] : null;
        $name            = \trim($this->Request->name[$key]);
        $quantity        = $this->Request->quantity[$key] ? ElcaNumberFormat::fromString(
            $this->Request->quantity[$key],
            3
        ) : 0;
        $calcLca         = $this->Request->has('includeInLca');

        $modified = false;
        if (is_numeric($key)) {
            $Transport = ElcaProjectTransport::findById($key);

            if ($Transport->getProcessConfigId() != $processConfigId) {
                $Transport->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if ($Transport->getName() != $name) {
                $Transport->setName($name);
                $modified = true;
            }
            if ($Transport->getQuantity() != $quantity) {
                $Transport->setQuantity($quantity);
                $modified = true;
            }
            if ($Transport->getCalcLca() != $calcLca) {
                $Transport->setCalcLca($calcLca);
                $modified = true;
            }
            if ($modified) {
                $Transport->update();
            }
        } else {
            $Transport = ElcaProjectTransport::create(
                $projectVariantId,
                $name,
                $quantity,
                $processConfigId,
                $calcLca
            );
        }

        return [$Transport, $modified];
    }
    // End saveTransportsAction

    /**
     * Saves a transport with all its tranport means
     *
     * @param ElcaProjectTransport $Transport
     * @param                      $transportKey
     * @param                      $meanKey
     *
     * @internal param string $key
     * @internal param int $transportKey
     * @return bool|null  $modified
     */
    protected function saveProjectTransportMean(ElcaProjectTransport $Transport, $transportKey, $meanKey)
    {
        $key = $transportKey . '-' . $meanKey;

        if (!isset($this->Request->processConfigId[$key]) || !$this->Request->processConfigId[$key]) {
            return null;
        }

        $processConfigId = $this->Request->processConfigId[$key];
        $distance        = isset($this->Request->distance[$key]) ? ElcaNumberFormat::fromString(
            $this->Request->distance[$key],
            3
        ) : 0;
        $efficiency      = isset($this->Request->efficiency[$key]) ? ElcaNumberFormat::fromString(
            $this->Request->efficiency[$key],
            3,
            true
        ) : 1;

        $modified = false;

        if (is_numeric($meanKey)) {
            $TransportMean = ElcaProjectTransportMean::findById($meanKey);

            if ($TransportMean->getProcessConfigId() != $processConfigId) {
                $TransportMean->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if ($TransportMean->getDistance() != $distance) {
                $TransportMean->setDistance($distance);
                $modified = true;
            }
            if ($TransportMean->getEfficiency() != $efficiency) {
                $TransportMean->setEfficiency($efficiency);
                $modified = true;
            }

            if ($modified) {
                $TransportMean->update();
            }
        } else {
            ElcaProjectTransportMean::create(
                $Transport->getId(),
                $processConfigId,
                $distance,
                $efficiency
            );

            $modified = true;
        }

        return $modified;
    }
    // End selectTransportProcessConfig

    /**
     *
     */
    private function selectFinalEnergyProcessConfig()
    {
        $view = $this->setView(new ElcaProjectDataEnEvView());
        $view->assign('projectVariantId', $this->Request->projectVariantId);
        $view->assign('ngf', $this->Request->ngf);
        $view->assign('enEvVersion', $this->Request->enEvVersion);
        $view->assign('readOnly', !$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject()));

        $projectVariantId = $this->Request->projectVariantId;

        $relId = $this->Request->relId;

        /**
         * Build data
         */
        if (!is_numeric($relId)) {
            switch ($relId) {
                case 'newDemand': $view->assign("addNewProjectFinalEnergyDemand", true); break;
                case 'newSupply': $view->assign("addNewProjectFinalEnergySupply", true); break;
                case 'newKwkDemand':
                    $view->assign("addNewProjectKwkFinalEnergyDemand", true);
                    $view->assign("addKwkInit", true);
                    break;
            }
        }

        if ($this->Request->id != $this->Request->p) {
            $view->assign('changedElements', ['processConfigId[' . $relId . ']' => true]);
        }

        $this->Request->processConfigId = [$relId => $this->Request->id];

        $view->assign('Data', $this->getFinalEnergyDataObject($projectVariantId, $relId == 'newSupply'));
    }
    // End saveProjectTransport

    /**
     *
     */
    private function selectTransportProcessConfig()
    {
        $relId = $this->Request->relId;
        list($transportId, $transportMeanId) = explode('-', $relId);

        $View = $this->setView(new ElcaProjectDataTransportsView());
        $View->assign('buildMode', ElcaProjectDataTransportsView::BUILDMODE_TRANSPORT_MEANS);
        $View->assign('projectVariantId', $this->Request->projectVariantId);
        $View->assign('transportId', $transportId);
        $Data = $View->assign('Data', (object)null);

        if (is_numeric($transportId)) {
            foreach (ElcaProjectTransportMeanSet::findByProjectTransportId($transportId) as $TransportMean) {
                $key = $transportId . '-' . $TransportMean->getId();

                $Data->processConfigId[$key] = $TransportMean->getProcessConfigId();
                $Data->rounds[$key]          = $TransportMean->getRounds();
                $Data->distance[$key]        = $TransportMean->getDistance();
                $Data->totalDistance[$key]   = $TransportMean->getTotalDistance();
                $Data->efficiency[$key]      = $TransportMean->getEfficiency();
            }
        }

        if (!is_numeric($transportMeanId)) {
            $Data->processConfigId[$relId] = $this->Request->id;
            $Data->efficiency[$relId]      = 1;
            $Data->distance[$relId]        = null;

            $View->assign('addNewTransportMean', true);
        }

        if ($this->Request->id != $this->Request->p) {
            $View->assign('changedElements', ['processConfigId[' . $relId . ']' => true]);
            $this->Request->processConfigId = [$relId => $this->Request->id];
        }

        /**
         * Used to focus the distance field
         */
        $View->assign('relId', $this->Request->relId);
    }
    // End saveProjectTransportMean
}

// End ProjectDataCtrl
