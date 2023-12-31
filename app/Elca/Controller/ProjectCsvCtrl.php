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

use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\Session;
use Beibob\Blibs\Validator;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaConstrClass;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Import\Csv\Project;
use Elca\Model\Import\Csv\Validator as CsvImportValidator;
use Elca\Model\Import\Xls\Validator as XlsImportValidator;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Import\CsvProjectElementImporter;
use Elca\Service\Import\CsvProjectGenerator;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaTemplatePreviewElementSelectorView;
use Elca\View\Import\Csv\ProjectImportPreviewView;
use Elca\View\Import\Csv\ProjectImportView;

class ProjectCsvCtrl extends AppCtrl
{
    const INITIAL_DIN_CODE = 330;
	
	const UPLOAD_FIELD_NAME = 'importFile';

    /**
     * @var \Beibob\Blibs\SessionNamespace
     */
    private $sessionNamespace;

    /**
     * @var CsvProjectElementImporter
     */
    private $elementImporter;

    /**
     * @var BenchmarkSystemsService
     */
    private $benchmarkSystemService;

    /**
     * @var CsvProjectGenerator
     */
    private $projectGenerator;

    /**
     * @var LifeCycleUsageService
     */
    private $lifeCycleUsageService;
	
    /**
     * @var isCsvFile 
     */
    private $isCsvFile = 1;	
	

    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->isAjax()) {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
        }

        $this->elementImporter        = $this->container->get(CsvProjectElementImporter::class);
        $this->benchmarkSystemService = $this->container->get(BenchmarkSystemsService::class);
        $this->projectGenerator       = $this->container->get(CsvProjectGenerator::class);
        $this->lifeCycleUsageService  = $this->container->get(LifeCycleUsageService::class);

        $this->sessionNamespace = $this->Session->getNamespace($this->ident(), Session::SCOPE_PERSISTENT, 3600);
    }

    /**
     * Default action
     */
    protected function importAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $this->addImportView();

        $this->Osit->add(new ElcaOsitItem(t('CSV / XLS Import')));
    }
    // End defaultAction

    /**
     * save action
     */
    protected function validateAction()
    {
        if (!$this->Request->isPost()) {
            return;
        }

        if ($this->Request->cancel) {
            $this->loadHashUrl($this->getLinkTo(ProjectsCtrl::class));
            return;
        }

        $validator = null;
        if ($this->Request->isPost() && $this->Request->has('upload')) {
          
        // Check file type and choose validator / default csv	
		if( File::uploadFileExists(self::UPLOAD_FIELD_NAME) ) {
			if( preg_match('/\.csv$/iu', (string)$_FILES[self::UPLOAD_FIELD_NAME]['name'] ) ) {
				$validator = new CsvImportValidator($this->Request);
			}	
			elseif( preg_match('/\.(xls|xlsx)$/iu', (string)$_FILES[self::UPLOAD_FIELD_NAME]['name'] ) ) {
				$validator = new XlsImportValidator($this->Request);  
				$this->isCsvFile = 0;
			} 
			else {
				// not valid file extension - use standard csv validator
				$validator = new CsvImportValidator($this->Request);
			}
		} 
		else {
			$validator = new CsvImportValidator($this->Request);
		}	
		
        	
            // validate project data
            $validator->assertNotEmpty('name', null, t('Bitte wählen Sie einen Projektnamen'));
            $validator->assertNotEmpty('constrMeasure', null, t('Bitte wählen Sie eine Baumaßnahme'));

            if ($validator->assertNotEmpty('postcode', null, t('Bitte geben Sie mindestens die 1. Stelle der PLZ ein'))) {
                if ($validator->assertMinLength(
                    'postcode',
                    1,
                    null,
                    t('Bitte geben Sie mindestens die 1. Stelle der PLZ ein')
                )) {
                    $validator->assertNumber('postcode', null, t('Die PLZ ist ungültig'));
                }
            }

            $validator->assertNotEmpty('constrClassId', null, t('Wählen Sie bitte eine Bauwerkszuordnung'));
            $validator->assertNotEmpty('benchmarkVersionId', null, t('Wählen Sie bitte ein Benchmarksystem'));

            if ($validator->assertNotEmpty('netFloorSpace', null, t('Geben Sie bitte die NFG an'))) {
                $validator->assertNumber('netFloorSpace', null, t('Die NGF ist ungültig'));
            }

            if ($validator->assertNotEmpty('grossFloorSpace', null, t('Geben Sie bitte die BGF an'))) {
                $validator->assertNumber('grossFloorSpace', null, t('Die BGF ist ungültig'));
            }

            $validator->assertImportFile(self::UPLOAD_FIELD_NAME);

            if ($validator->isValid()) {

                $name               = $this->Request->get('name');
                $constrMeasure      = (int)$this->Request->get('constrMeasure');
                $postcode           = $this->Request->get('postcode');
                $constrClassId      = (int)$this->Request->get('constrClassId');
                $benchmarkVersionId = (int)$this->Request->get('benchmarkVersionId');
                $netFloorSpace      = ElcaNumberFormat::fromString($this->Request->get('netFloorSpace'), 2);
                $grossFloorSpace    = ElcaNumberFormat::fromString($this->Request->get('grossFloorSpace'), 2);

                $file             = File::fromUpload(self::UPLOAD_FIELD_NAME, $this->getTempDir());
				
				
				// XLS file to convert
				if($this->isCsvFile==0) {
					$importedElements = $this->elementImporter->elementsFromXls2CsvFile($file);
				} else {
					$importedElements = $this->elementImporter->elementsFromCsvFile($file);
				}
				
                $project = new Project(
                    $name,
                    $constrMeasure,
                    $postcode,
                    $constrClassId,
                    $benchmarkVersionId,
                    $netFloorSpace,
                    $grossFloorSpace
                );
                $project->setImportElements($importedElements);

                $this->sessionNamespace->project = $project;

                $this->loadHashUrl($this->getActionLink('preview'));

                return;
            }

            if ($validator->hasErrors()) {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        }

        $this->addImportView($validator);
    }

    protected function previewAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        /**
         * @var Project $project
         */
        $project = $this->sessionNamespace->project;

        if (null === $project) {
            $this->importAction();
            return;
        }
        $validator = new CsvImportValidator($this->Request);

        if ($this->Request->isPost() && $this->Request->has('createProject')) {

            $validator->assertValidProject($project);

            if ($validator->isValid()) {
                $elcaProject = $this->projectGenerator->generate($project);
                $this->lifeCycleUsageService->updateForProject($elcaProject);

                $this->messages->add(
                    t('Projekt %name% wurde erfolgreich importiert', null, ['%name%' => $elcaProject->getName()])
                );

                $modalView = $this->addView(new ElcaModalProcessingView());
                $modalView->assign(
                    'action',
                    $this->getLinkTo(
                        ProjectDataCtrl::class,
                        'lcaProcessing',
                        [
                            'id'       => $elcaProject->getId(),
                            'redirect' => $this->getLinkTo(ProjectsCtrl::class, $elcaProject->getId()),
                        ]
                    )
                );
                $modalView->assign('headline', t('Neuberechnung nach Import erforderlich'));

                $this->sessionNamespace->freeData();

                return;
            }
            else {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        }
        elseif ($this->Request->has('cancel')) {
            $this->sessionNamespace->freeData();
            $this->loadHashUrl($this->getLinkTo(ProjectsCtrl::class));
            return;
        }

        $view = $this->setView(new ProjectImportPreviewView());
        $view->assign('data', $this->buildPreviewFormData($project));
        $view->assign('project', $project);
        $view->assign('validator', $validator);

        $this->Osit->add(
            new ElcaOsitItem(
                $project->name(),
                null,
                t('Project CSV Import')
            )
        );
    }

    protected function selectElementAction()
    {
        /**
         * @var Project $project
         */
        $project = $this->getProject();

        /**
         * This selects and assigns an element in composite element context,
         * if a user pressed the select button
         */
        if (isset($this->Request->selectElement))
        {
            $selectedElement = ElcaElement::findById($this->Request->id);
            $relId = $this->Request->relId;

            $importElement = $project->findElementByUuid($relId);

            if (null === $importElement) {
                return false;
            }

            $importElement->changeTplElementUuid($selectedElement->getUuid());
            $project->replaceElement($importElement, $importElement->harmonizeWithTemplateElement($selectedElement));

            $view = $this->setView(new ProjectImportPreviewView());
            $view->assign('data', $this->buildPreviewFormData($project));
            $view->assign('project', $project);

            return true;
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        $view = $this->setView(new ElcaTemplatePreviewElementSelectorView());
        $view->assign('elementId', $this->Request->e);
        $view->assign('currentElementId', $this->Request->id);
        $view->assign('elementTypeNodeId', $this->Request->elementTypeNodeId ?? $this->Request->t);
        $view->assign('searchScope', $this->Request->has('scope') ? $this->Request->get('scope') : 'public');
        $view->assign('url', $this->getActionLink('selectElement'));
        $view->assign('db', $this->getProcessDbIdFromSession($project));
        $view->assign('relId', $this->Request->relId);

        return false;
    }

    protected function getTempDir(): string
    {
        $config = $this->get(Environment::class)->getConfig();
        if (isset($config->tmpDir)) {
            $baseDir = $config->toDir('baseDir');
            $tmpDir  = $baseDir . $config->toDir('tmpDir', true);
        } else {
            $tmpDir = '/tmp';
        }

        return $tmpDir;
    }

    private function addImportView(Validator $validator = null): \Beibob\Blibs\Interfaces\Viewable
    {
        $view = $this->setView(new ProjectImportView());
        $view->assign('benchmarkSystemsService', $this->benchmarkSystemService);
        $view->assign('validator', $validator);
        $view->assign('data', $this->buildProjectFormData());

        return $view;
    }

    private function buildPreviewFormData(Project $project = null)
    {
        $data = new \stdClass();

        if (null === $project) {
            return $data;
        }

        $dinCode2 = $this->Request->getArray('dinCode2');
        $dinCode3 = $this->Request->getArray('dinCode3');
        $quantities  = $this->Request->getArray('quantity');
        $units       = $this->Request->getArray('unit');

        foreach ($project->importElements() as $element) {
            $id              = $element->uuid();
            $data->name[$id] = $element->name();

            if ($this->Request->isPost() && !empty($dinCode2)) {
                if (isset($dinCode3[$id]) && !empty($dinCode3[$id])) {
                    $dinCode = $dinCode3[$id];
                } else {
                    $dinCode = $dinCode2[$id];
                }

                $data->dinCode2[$id] = $dinCode2[$id] ?? null;
                $data->dinCode3[$id] = $dinCode3[$id] ?? null;
            }
            else {
                $dinCode = $element->dinCode();
                $elementType = ElcaElementType::findByIdent($dinCode);
                $data->dinCode2[$id] = $elementType->isCompositeLevel() ? $elementType->getDinCode() : $elementType->getParent()->getDinCode();
                $data->dinCode3[$id] = !$elementType->isCompositeLevel() ? $elementType->getDinCode() : null;
            }

            if (!empty($dinCode) && $dinCode !== $element->dinCode()) {
                $element->changeDinCode((int)$dinCode);
            }

            if (isset($quantities[$id]) && $quantities[$id] && isset($units[$id]) && $units[$id]) {
                $quantity = Quantity::fromValue(
                    ElcaNumberFormat::fromString($quantities[$id]),
                    $units[$id]
                );

                if (null === $element->quantity() || !$quantity->equals($element->quantity())) {
                    $element->changeQuantity($quantity);
                }
            }
            $data->quantity[$id] = null !== $element->quantity() ? $element->quantity()->value() : 1;
            $data->unit[$id]     = null !== $element->quantity() ? $element->quantity()->unit()->value() : Unit::SQUARE_METER;

            $selectedElement = ElcaElement::findByUuid($element->tplElementUuid());

            if ($selectedElement->getElementTypeNode()->getDinCode() === $element->dinCode()) {
                $data->tplElementId[$id] = $selectedElement->getId();
            }
            else {
                $element->changeTplElementUuid(null);
            }

            $data->isModified[$id] = $element->isModified();
            $data->modificationReason[$id] = $element->modificationReason();
        }

        return $data;
    }

    private function getProject()
    {
        return $this->sessionNamespace->project;
    }

    private function getProcessDbIdFromSession(Project $project)
    {
        $benchmarkVersionId = $this->sessionNamespace->project->benchmarkVersionId();
        $benchmarkVersion = ElcaBenchmarkVersion::findById($benchmarkVersionId);

        return $benchmarkVersion->getProcessDbId();
    }

    private function buildProjectFormData(): \stdClass
    {
        $data = new \stdClass();
        $data->constrMeasure = Elca::CONSTR_MEASURE_PUBLIC;
        $data->constrClassId = ElcaConstrClass::findByRefNum(9890)->getId();

        return $data;
    }
}
