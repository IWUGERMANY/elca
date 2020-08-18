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

use Beibob\Blibs\Config;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\IdFactory;
use Beibob\Blibs\Log;
use Beibob\Blibs\Session;
use Beibob\Blibs\Validator;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaConstrClass;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProject;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Quantity\Quantity;
use Elca\Model\Common\Unit;
use Elca\Model\Import\Ifc\Project;
use Elca\Model\Import\Ifc\Validator as IfcImportValidator;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\Service\Import\IfcProjectElementImporter;
use Elca\Service\Import\IfcProjectGenerator;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaModalProcessingView;
use Elca\View\ElcaTemplatePreviewElementSelectorView;
use Elca\View\Import\Ifc\ProjectImportPreviewView;
use Elca\View\Import\Ifc\ProjectImportView;

class ProjectIfcCtrl extends AppCtrl
{
    const UPLOAD_FIELD_NAME = 'importFile';

    /**
     * @var \Beibob\Blibs\SessionNamespace
     */
    private $sessionNamespace;

    /**
     * @var IfcProjectElementImporter
     */
    private $elementImporter;

    /**
     * @var BenchmarkSystemsService
     */
    private $benchmarkSystemService;

    /**
     * @var IfcProjectGenerator
     */
    private $projectGenerator;

    /**
     * @var LifeCycleUsageService
     */
    private $lifeCycleUsageService;

    private $isIfcFile = 0;

    private $deleteKey;

    protected function init(array $args = [])
    {
        parent::init($args);

        if (!$this->isAjax()) {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
        }

        $this->elementImporter        = $this->container->get(IfcProjectElementImporter::class);
        $this->benchmarkSystemService = $this->container->get(BenchmarkSystemsService::class);
        $this->projectGenerator       = $this->container->get(IfcProjectGenerator::class);
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

        $this->Osit->add(new ElcaOsitItem(t('IFC Import')));
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

            // Check file type and use validator
            if (File::uploadFileExists(self::UPLOAD_FIELD_NAME)) {
                if (preg_match('/\.ifc$/iu', (string)$_FILES[self::UPLOAD_FIELD_NAME]['name'])) {
                    $validator       = new IfcImportValidator($this->Request);
                    $this->isIfcFile = 1;
                } else {
                    // not valid file extension - use standard validator
                    $validator = new IfcImportValidator($this->Request);
                }
            } else { // validator or error message?
                $validator = new IfcImportValidator($this->Request);
            }


            // validate project data
            $validator->assertNotEmpty('name', null, t('Bitte wählen Sie einen Projektnamen'));
            $validator->assertNotEmpty('constrMeasure', null, t('Bitte wählen Sie eine Baumaßnahme'));

            if ($validator->assertNotEmpty('postcode', null,
                t('Bitte geben Sie mindestens die 1. Stelle der PLZ ein'))) {
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


                // Ifc file to convert
                if ($this->isIfcFile == 1) {
                    // unique key for project
                    $key = IdFactory::getUniqueId();

                    $environment = Environment::getInstance();
                    $config      = $environment->getConfig();

                    $tmpCacheIFCDir  = $config->toDir('baseDir') . $config->toDir('IfcCreateDir', true,
                            'tmp/ifc-data/');
                    $tmpCacheUserDir = $tmpCacheIFCDir . $key;

                    if (!\is_dir($tmpCacheUserDir)) {
                        if (!mkdir($tmpCacheUserDir, 0777, true) && !is_dir($tmpCacheUserDir)) {
                            throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpCacheUserDir));
                        }
                        chmod($tmpCacheUserDir, 0777);
                    }

                    $tmpCsvFilename = $tmpCacheUserDir . '/' . $config->ifcCsvFilename;

                    // $file   = File::fromUpload(self::UPLOAD_FIELD_NAME, $this->getTempDir());
                    $file = File::fromUpload(self::UPLOAD_FIELD_NAME, $tmpCacheUserDir);

                    $cmdPython       = $config->pythonexecute;
                    $cmdPythonScript = $config->toDir('baseDir') . $config->ifcParserScript;

                    $cmd = sprintf(
                        '%s %s %s %s',
                        $cmdPython,
                        $cmdPythonScript,
                        $file->getFilepath(),
                        $tmpCsvFilename
                    );


                    try {

                        if (!empty($cmd)) {
                            // parse if by python script
                            exec($cmd, $output, $returnvar);
                        }

                    }
                    catch (\Exception $Exception) {
                        Log::getInstance()->debug($cmd);
                        Log::getInstance()->debug($Exception->getMessage());
                        throw new \RuntimeException(sprintf('Python Error cmd: "%s", output: %s, returnvar: %s', $cmd, $output, $returnvar));
                    }


                    // import of generated csv file
                    try {
                        $fileCSV          = new File($tmpCsvFilename);
                        $importedElements = $this->elementImporter->elementsFromIfcFile($fileCSV);
                    }
                    catch (\Exception $Exception) {
                        // Log::getInstance()->debug($cmd);
                        Log::getInstance()->debug($Exception->getMessage());
                        throw new \RuntimeException(sprintf('No "%s" file', $tmpCsvFilename));
                    }


                } else {
                    // ToDo - error message
                    //$importedElements = $this->elementImporter->elementsFromIfcFile($file);
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


                try {
                    $project->setImportElements($importedElements);
                }
                catch (\Exception $Exception) {
                    Log::getInstance()->debug($cmd);
                    Log::getInstance()->debug($Exception->getMessage());
                }


                $this->sessionNamespace->project = $project;

                // session tmp dir, $key, filename ifc, filename csv
                $this->sessionNamespace->ifcData = [
                    'tmpPath'        => $tmpCacheUserDir,
                    'key'            => $key,
                    'ifcFilename'    => $file->getFilename(),
                    'ifcCsvFilename' => $config->ifcCsvFilename,
                ];


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
        $ifcFile = null;

        if (!$this->isAjax()) {
            return;
        }

        /**
         * @var Project $project
         */
        $project = $this->sessionNamespace->project;

        // delete importElement - ToDo: read GET param without $_GET
        if (isset($_GET['delkey'])) {
            $this->deleteKey = $_GET['delkey'];
            $boolwert        = $project->removeElementByUuid($this->deleteKey);
        }


        if (null === $project) {
            $this->importAction();

            return;
        }
        $validator = new IfcImportValidator($this->Request);

        if ($this->Request->isPost() && $this->Request->has('createProject')) {

            $validator->assertValidProject($project);

            if ($validator->isValid()) {
                $elcaProject = $this->projectGenerator->generate($project);
                $this->lifeCycleUsageService->updateForProject($elcaProject);

                $this->messages->add(
                    t('Projekt %name% wurde erfolgreich importiert', null, ['%name%' => $elcaProject->getName()])
                );


                // ifc data
                // -----------------------------------------------------------
                // create user directory
                // move ifc / csv files
                // generate xml, dae, obj files

                $environment = Environment::getInstance();
                $config      = $environment->getConfig();

                // create new directory with userId and projectId storing ifc files
                /*$ifcSaveDir = sprintf(
                        "%s%d/%d",
                        $config->toDir('baseDir') . $config->toDir('ifcSaveDir', true, 'www/ifc-data/'),
                        $this->Access->getUserId(),
                        $elcaProject->getId()
                    );
                */

                // create new directory with projectId storing ifc files
                $ifcSaveDir = $this->createProjectDirectory($config, $elcaProject);

                // move files
                $ifcFile = $this->moveFiles($ifcSaveDir, $config, $elcaProject);

                if (null !== $ifcFile) {
                    $this->convertIfcToXml($config, $ifcFile, $ifcSaveDir, $elcaProject);
                    $this->convertIfcToGlb($config, $ifcFile, $ifcSaveDir, $elcaProject);

                    //$this->convertIfcToDae($config, $ifcFile, $ifcSaveDir, $elcaProject);
                    //$this->convertDaeToGltf($config, $ifcSaveDir, $elcaProject);
                }


                $this->showLcaProcessingModal($elcaProject);

                $this->sessionNamespace->freeData();

                return;
            } else {
                $this->messages->add(t('Bitte mindestens ein Bauteil zuweisen.'), ElcaMessages::TYPE_ERROR);
            }
            /* foreach ($validator->getErrors() as $property => $message) {
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            } */
        } elseif ($this->Request->has('cancel')) {
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
                t('Project IFC Import')
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
        if (isset($this->Request->selectElement)) {
            $selectedElement = ElcaElement::findById($this->Request->id);
            $relId           = $this->Request->relId;

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

    protected function createProjectDirectory(Config $config, ElcaProject $elcaProject): string
    {
        $ifcSaveDir = sprintf(
            "%s%d",
            $config->toDir('baseDir') . $config->toDir('ifcSaveDir', true, 'www/ifc-data/'),
            $elcaProject->getId()
        );

        if (!\is_dir($ifcSaveDir)) {
            if (!mkdir($ifcSaveDir, 0777, true) && !is_dir($ifcSaveDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $ifcSaveDir));
            }
            chmod($ifcSaveDir, 0777);
        }

        return $ifcSaveDir;
    }

    protected function moveFiles(string $ifcSaveDir, Config $config, ElcaProject $elcaProject): string
    {
        $ifcData = $this->sessionNamespace->ifcData;

        if (is_array($ifcData)) {
            try {
                $csvFile = File::move($ifcData['tmpPath'] . '/' . $ifcData['ifcCsvFilename'],
                    $ifcSaveDir . '/' . $ifcData['ifcCsvFilename']
                );
                $ifcFile = File::move($ifcData['tmpPath'] . '/' . $ifcData['ifcFilename'],
                    $ifcSaveDir . '/' . ($config->ifcViewerFilename ?? 'ifc-viewer') . '.' . $config->fileExtLabelIFC
                );
            }
            catch (\Exception $exception) {
                $this->messages->add(
                    t('Warnung Projekt %name%: IFC Dateien nicht kopiert.', null,
                        ['%name%' => $elcaProject->getName()])
                );
                Log::getInstance()->debug($ifcSaveDir);
                Log::getInstance()->debug($exception->getMessage());
                throw new \RuntimeException(sprintf('IFC files not moved: "%s" - Project: "%d"', $ifcSaveDir,
                    $elcaProject->getId()));
            }
        } else {
            $this->messages->add(
                t('Warnung Projekt %name%: IFC Dateien nicht kopiert. Verzeichnis nicht gefunden', null,
                    ['%name%' => $elcaProject->getName()])
            );
            throw new \RuntimeException(sprintf('IFC files not moved: "%s" - Project: "%d"', $ifcSaveDir,
                $elcaProject->getId()));
        }

        return $ifcFile;
    }

    protected function convertIfcToXml(Config $config, string $ifcFile, string $ifcSaveDir, ElcaProject $elcaProject): void
    {
        $cmdXML = sprintf(
            '%s %s %s/%s.%s',
            $config->ifcconvertexecute,
            $ifcFile,
            $ifcSaveDir,
            $config->ifcViewerFilename,
            $config->get('fileExtLabelXML', 'xml')
        );

        try {

            if (!empty($cmdXML)) {
                exec($cmdXML, $output, $returnvar);
            }

        }
        catch (\Exception $Exception) {
            Log::getInstance()->debug($cmdXML);
            Log::getInstance()->debug($Exception->getMessage());
            throw new \RuntimeException(sprintf('XML file not created: "%s" - Project: "%d"', $cmdXML,
                $elcaProject->getId()));
        }
    }

    protected function convertIfcToGlb(Config $config, string $ifcFile, string $ifcSaveDir, ElcaProject $elcaProject): void
    {
        $cmdGlb = sprintf(
            '%s %s %s/%s.%s',
            $config->ifcconvertexecute,
            $ifcFile,
            $ifcSaveDir,
            $config->ifcViewerFilename,
            $config->get('fileExtLabelGLB', 'glb')
        );

        try {

            if (!empty($cmdGlb)) {
                exec($cmdGlb, $output, $returnvar);
            }

        }
        catch (\Exception $Exception) {
            Log::getInstance()->debug($cmdGlb);
            Log::getInstance()->debug($Exception->getMessage());
            throw new \RuntimeException(sprintf('GLB file not created: "%s" - Project: "%d"', $cmdGlb,
                $elcaProject->getId()));
        }
    }


    protected function convertIfcToDae(Config $config, string $ifcFile, string $ifcSaveDir, ElcaProject $elcaProject): void
    {
        $cmdIfcconvert       = $config->ifcconvertexecute . " " . $ifcFile;
        $cmdIfcconvertOutput = $ifcSaveDir . '/' . $config->ifcViewerFilename;

        $cmdDAE = sprintf(
            '%s %s.%s',
            $cmdIfcconvert,
            $cmdIfcconvertOutput,
            $config->fileExtLabelDAE
        );


        try {

            if (!empty($cmdDAE)) {
                exec($cmdDAE, $output, $returnvar);
            }

        }
        catch (\Exception $Exception) {
            Log::getInstance()->debug($cmdDAE);
            Log::getInstance()->debug($Exception->getMessage());
            throw new \RuntimeException(sprintf('IFC file not created: "%s" - Project: "%d"', $cmdDAE,
                $elcaProject->getId()));
        }
    }

    protected function convertDaeToGltf(Config $config, string $ifcSaveDir,
        ElcaProject $elcaProject): void
    {
        // convert dae -> gltf
        $cmdCollada       = $config->colladagltfexecute;
        $cmdColladaInput  = $ifcSaveDir . '/' . $config->ifcViewerFilename . '.' . $config->fileExtLabelDAE;
        $cmdColladaOutput = $ifcSaveDir . '/' . $config->ifcViewerFilename . '.' . $config->fileExtLabelGLTF;

        $cmdGLTF = sprintf(
            '%s -i %s -o %s %s',
            $cmdCollada,
            $cmdColladaInput,
            $cmdColladaOutput,
            ($config->colladagltfexecuteOptions ?? '-V 1.0')
        );

        try {

            if (!empty($cmdGLTF)) {
                exec($cmdGLTF, $output, $returnvar);
            }

        }
        catch (\Exception $Exception) {
            Log::getInstance()->debug($cmdGLTF);
            Log::getInstance()->debug($Exception->getMessage());
            throw new \RuntimeException(sprintf('GLTF file not created: "%s" - Project: "%d"', $cmdGLTF,
                $elcaProject->getId()));
        }
    }

    protected function showLcaProcessingModal(ElcaProject $elcaProject): void
    {
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

        $dinCode2   = $this->Request->getArray('dinCode2');
        $dinCode3   = $this->Request->getArray('dinCode3');
        $quantities = $this->Request->getArray('quantity');
        $units      = $this->Request->getArray('unit');

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
            } else {
                $dinCode             = $element->dinCode();
                $elementType         = ElcaElementType::findByIdent($dinCode);
                $data->dinCode2[$id] = $elementType->isCompositeLevel() ? $elementType->getDinCode()
                    : $elementType->getParent()->getDinCode();
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
            $data->unit[$id]     = null !== $element->quantity() ? $element->quantity()->unit()->value()
                : Unit::SQUARE_METER;

            $selectedElement = ElcaElement::findByUuid($element->tplElementUuid());

            if ($selectedElement->getElementTypeNode()->getDinCode() === $element->dinCode()) {
                $data->tplElementId[$id] = $selectedElement->getId();
            } else {
                $element->changeTplElementUuid(null);
            }

            $data->ifcType[$id]     = $element->ifcType();
            $data->ifcFloor[$id]    = $element->ifcFloor();
            $data->ifcMaterial[$id] = $element->ifcMaterial();
            $data->ifcGUID[$id]     = $element->ifcGUID();

            $data->isModified[$id]         = $element->isModified();
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
        $benchmarkVersion   = ElcaBenchmarkVersion::findById($benchmarkVersionId);

        return $benchmarkVersion->getProcessDbId();
    }

    private function buildProjectFormData(): \stdClass
    {
        $data                = new \stdClass();
        $data->constrMeasure = Elca::CONSTR_MEASURE_PUBLIC;
        $data->constrClassId = ElcaConstrClass::findByRefNum(9890)->getId();

        return $data;
    }
}
