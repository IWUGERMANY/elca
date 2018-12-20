<?php declare(strict_types=1);
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

namespace ImportAssistant\Controller\Admin;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlFormValidator;
use Elca\Controller\AppCtrl;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\ElcaNumberFormat;
use Elca\Model\Exception\AbstractException;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaProcessConfigSelectorView;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfoRepository;
use ImportAssistant\Service\Admin\Import\MappingsImporter;
use ImportAssistant\View\Admin\MappingEditView;
use ImportAssistant\View\Admin\MappingsImportView;
use ImportAssistant\View\Admin\MappingsView;

class MappingsCtrl extends AppCtrl
{
    const CONTEXT = 'importAssistant/admin/mappings';

    const MAPPING_TYPE_SINGLE = 0;
    const MAPPING_TYPE_SIBLINGS = 1;
    const MAPPING_TYPE_MULTIPLE = 2;

    /**
     * @var array
     * @translate array ImportAssistant\Controller\Admin\MappingsCtrl::$mappingModeMap
     */
    public static $mappingModeMap = [
        self::MAPPING_TYPE_SINGLE   => '1:1',
        self::MAPPING_TYPE_SIBLINGS => 'Gefach',
        self::MAPPING_TYPE_MULTIPLE => 'Split',
    ];

    private $sessionNamespace;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->sessionNamespace = $this->Session->getNamespace(get_class(), true);
    }

    protected function mappingsAction()
    {
        $view = $this->setView(new MappingsView());
        $view->assign('processDbId', $this->getActiveProcessDbId());

        $this->addNavigationView('mappings');

        $this->Osit->add(new ElcaOsitItem(
            t('Materialmapping [:dbVersion:]', null, [':dbVersion:' => ElcaProcessDb::findById($this->getActiveProcessDbId())->getVersion()]),
            null,
            t('ImportAssistent')
        ));
    }

    protected function setProcessDbAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost() || !$this->Request->get('processDbId')) {
            return;
        }

        $this->sessionNamespace->processDbId = (int)$this->Request->get('processDbId');

        $this->reloadHashUrl();
    }

    protected function addAction()
    {
        $view = $this->setView(new MappingEditView());
        $view->assign('data', $this->buildData());
        $view->assign('context', self::CONTEXT);

        $this->addNavigationView('mappings');

        $caption = t('Materialmapping [:dbVersion:]', null, [':dbVersion:' => ElcaProcessDb::findById($this->getActiveProcessDbId())->getVersion()]);
        $this->Osit->add(new ElcaOsitItem($caption, $this->getActionLink('mappings'), t('ImportAssistent')));
        $this->Osit->add(new ElcaOsitItem(t('Neues Mapping hinzufügen'), null, $caption));
    }


    protected function editAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $mappingRepository   = $this->get(MaterialMappingInfoRepository::class);
        $materialMappingInfo = $mappingRepository->findById((int)$this->Request->id);

        if (null === $materialMappingInfo) {
            return;
        }

        $view = $this->setView(new MappingEditView());
        $view->assign('data', $this->buildData($materialMappingInfo));
        $view->assign('context', self::CONTEXT);

        $this->addNavigationView('mappings');
        $this->addOsitEditItems($materialMappingInfo);
    }

    protected function saveAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        if (false === $this->Access->hasAdminPrivileges()) {
            return;
        }

        if ($this->Request->has('cancel')) {
            $this->mappingsAction();

            return;
        }

        $processDbId      = $this->getActiveProcessDbId();
        $mappingRepository   = $this->get(MaterialMappingInfoRepository::class);
        $materialMappingInfo = is_numeric($this->Request->id)
            ? $mappingRepository->findById((int)$this->Request->id)
            : null;

        $processConfigIds = $this->Request->getArray('processConfigId');
        $mappingIds       = $this->Request->getArray('mappingId');
        $siblingRatios    = $this->Request->getArray('siblingRatio');
        $mode             = (int)$this->Request->mode;
        $materialName     = trim($this->Request->materialName);

        foreach ($siblingRatios as $index => $ratio) {
            $siblingRatios[$index] = ElcaNumberFormat::fromString($ratio, 2, true);
        }

        $validator = new HtmlFormValidator($this->Request);
        $validator->assertNotEmpty(
            'materialName',
            null,
            t('Bitte geben Sie den Namen des Materials an, für die eine Zuordnung gespeichert werden soll.')
        );
        $validator->assertTrue(
            'processConfigId[0]',
            $processConfigIds[0] ?? false,
            t('Bitte wählen Sie eine Baustoffkonfiguration.')
        );

        if ($validator->isValid() && $mode > 0) {
            $validator->assertTrue(
                'processConfigId[1]',
                $processConfigIds[1] ?? false,
                t('Bitte wählen Sie eine zweite Baustoffkonfiguration.')
            );

            if ($mode === MappingsCtrl::MAPPING_TYPE_SIBLINGS) {
                $validator->assertTrue(
                    'siblingRatio[0]',
                    is_numeric($siblingRatios[0] ?? null),
                    t('Geben Sie einen Wert für den Anteil an.')
                );
                $validator->assertTrue(
                    'siblingRatio[1]',
                    is_numeric($siblingRatios[1] ?? null),
                    t('Geben Sie einen Wert für den zweiten Anteil an.')
                );

                if ($validator->isValid()) {
                    $validator->assertTrue(
                        'siblingRatio[0]',
                        ($siblingRatios[0] + $siblingRatios[1] == 1),
                        t('Die Summe der Anteile muss 100 % ergeben.')
                    );
                }
            }
        }

        // check uniqueness
        if ($validator->isValid()) {
            $foundMappingInfo = $mappingRepository->findByMaterialName($materialName, $processDbId);

            $validator->assertTrue(
                '',
                null === $foundMappingInfo || (null !== $materialMappingInfo && $foundMappingInfo->equals(
                        $materialMappingInfo
                    )),
                t(
                    'Eine Zuordnung für :materialName: existiert bereits',
                    null,
                    [
                        ':materialName:' => $materialName,
                    ]
                )
            );
        }

        if ($validator->isValid()) {
            /**
             * @var MaterialMapping[] $mappings
             */
            $mappings = [];
            $mapping  = $mappings[] = new MaterialMapping(
                $materialName,
                (int)$processConfigIds[0],
                $mode === self::MAPPING_TYPE_SIBLINGS ? (float)$siblingRatios[0] : null
            );
            if (null !== $materialMappingInfo) {
                $mapping->setSurrogateId($mappingIds[0] ? (int)$mappingIds[0] : null);
            }

            if ($mode > 0) {
                $mapping = $mappings[] = new MaterialMapping(
                    $materialName,
                    (int)$processConfigIds[1],
                    $mode === self::MAPPING_TYPE_SIBLINGS ? (float)$siblingRatios[1] : null
                );
                if (null !== $materialMappingInfo && isset($mappingIds[1]) && $mappingIds[1]) {
                    $mapping->setSurrogateId((int)$mappingIds[1]);
                }

                if ($mode === self::MAPPING_TYPE_MULTIPLE && isset($processConfigIds[2]) && $processConfigIds[2]) {
                    $mapping = $mappings[] = new MaterialMapping(
                        $materialName,
                        (int)$processConfigIds[2]
                    );
                    if (null !== $materialMappingInfo && isset($mappingIds[2]) && $mappingIds[2]) {
                        $mapping->setSurrogateId((int)$mappingIds[1]);
                    }
                }
            }

            $updatedMappingInfo = new MaterialMappingInfo(
                $materialName,
                $processDbId,
                $mappings,
                $mode === self::MAPPING_TYPE_SIBLINGS,
                $mode === self::MAPPING_TYPE_MULTIPLE
            );

            if (null !== $materialMappingInfo) {
                $mappingRepository->save($updatedMappingInfo);
            } else {
                $mappingRepository->add($updatedMappingInfo);
            }

            if (null === $materialMappingInfo) {
                $this->loadHashUrl(
                    $this->getActionLink(
                        'edit',
                        ['id' => $updatedMappingInfo->firstMaterialMapping()->surrogateId()]
                    )
                );

                $this->messages->add(t('Die Zuordnung wurde erstellt.'));
            } else {
                $this->messages->add(t('Die Zuordnung wurde gespeichert.'));
            }
        } else {
            foreach ($validator->getErrors() as $error) {
                $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }

    }

    protected function deleteAction()
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $mappingRepository   = $this->get(MaterialMappingInfoRepository::class);
        $materialMappingInfo = $mappingRepository->findById((int)$this->Request->id);

        if (null === $materialMappingInfo) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            $mappingRepository->remove($materialMappingInfo);

            $this->mappingsAction();
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t(
                    'Soll das Mapping `:name:\' wirklich gelöscht werden?',
                    null,
                    [':name:' => $materialMappingInfo->materialName()]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }

    protected function deleteMultipleAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $session           = $this->Session->getNamespace(__METHOD__, true, 60);
        $mappingRepository = $this->get(MaterialMappingInfoRepository::class);


        if ($this->Request->has('confirmed')) {

            if ($session->ids && is_array($session->ids)) {
                foreach ($session->ids as $mappingId) {
                    if ($materialMappingInfo = $mappingRepository->findById((int)$mappingId)) {
                        $mappingRepository->remove($materialMappingInfo);
                    }
                }

                $this->messages->add(
                    t(':count: Datensätze wurden gelöscht', null, [':count:' => count($session->ids)])
                );
            }

            $session->freeData();
            $this->mappingsAction();
        } elseif ($this->Request->isPost() && $this->Request->ids && is_array($this->Request->ids)) {
            $session->freeData();
            $session->ids = $this->Request->getArray('ids');

            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(
                t(
                    'Sind Sie sicher, dass :count: Datensätze gelöscht werden sollen?',
                    null,
                    [':count:' => count($session->ids)]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }

    protected function jsonAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $mappingRepository = $this->get(MaterialMappingInfoRepository::class);
        $processDbNames    = ElcaProcessDbSet::findEn15804Compatibles()->getArrayBy('version', 'id');

        $result = [];
        foreach ($mappingRepository->findByProcessDbId($this->getActiveProcessDbId()) as $mappingInfo) {
            $mode        = $this->getMappingMode($mappingInfo);
            $epdSubTypes = $processDbs = [];

            if ($mode === self::MAPPING_TYPE_SINGLE) {
                $processConfigNames = $mappingInfo->firstMaterialMapping()->processConfigName();
                foreach ($mappingInfo->firstMaterialMapping()->epdSubTypes() as $epdSubType) {
                    $epdSubTypes[] = t($epdSubType);
                }
                foreach ($mappingInfo->firstMaterialMapping()->processDbIds() as $processDbId) {
                    if (isset($processDbNames[$processDbId])) {
                        $processDbs[] = $processDbNames[$processDbId];
                    }
                }
            } else {
                $processConfigNames = implode(
                    '; ',
                    array_map(
                        function (MaterialMapping $mapping) {
                            return $mapping->processConfigName();
                        },
                        $mappingInfo->materialMappings()
                    )
                );

                foreach ($mappingInfo->materialMappings() as $mapping) {
                    foreach ($mapping->epdSubTypes() as $epdSubType) {
                        $epdSubTypes[] = t($epdSubType);
                    }
                    foreach ($mapping->processDbIds() as $processDbId) {
                        if (isset($processDbNames[$processDbId])) {
                            $processDbs[] = $processDbNames[$processDbId];
                        }
                    }
                }
            }

            //\ksort($processDbs, SORT_NUMERIC);

            $result[] = [
                //'select' => null,
                'materialName'  => $mappingInfo->materialName(),
                'processConfig' => $processConfigNames,
                'epdSubTypes'   => implode('; ', $epdSubTypes),
                'processDbs'    => implode('; ', $processDbs),
                'mappingMode'   => t(self::$mappingModeMap[$mode]),
                'id'            => $mappingInfo->firstMaterialMapping()->surrogateId(),
            ];
        }

        $this->getView()->assign('mappings', $result);
    }

    protected function importAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $activeProcessDbId = $this->getActiveProcessDbId();

        $mappingRepository = $this->get(MaterialMappingInfoRepository::class);
        $importer = new MappingsImporter($mappingRepository);

        $importedMappingCount = null;

        if ($this->Request->isPost() && $this->Request->has('upload') && File::uploadFileExists('importFile')) {
            $validator = new ElcaValidator($this->Request);

            if ($validator->isValid()) {
                $validator->assertTrue(
                    'importFile',
                    File::uploadFileExists('importFile'),
                    t('Bitte geben Sie eine Datei für den Import an!')
                );

                if (isset($_FILES['importFile'])) {
                    $validator->assertTrue(
                        'importFile',
                        preg_match('/\.csv/iu', (string)$_FILES['importFile']['name']),
                        t('Bitte nur CSV Dateien importieren')
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

                try {
                    DbHandle::getInstance()->begin();
                    $importedMappingCount = $importer->fromCsvFile($file, $activeProcessDbId, $this->Request->has('removeMappings'));

                    $this->flashMessages->add(
                        t(':count: Datensätze wurden importiert', null, [':count:' => $importedMappingCount])
                    );
                    $this->reloadHashUrl();
                    DbHandle::getInstance()->commit();

                    return;
                } catch (\Exception $exception) {
                    DbHandle::getInstance()->rollback();
                    $this->messages->add($exception->getMessage(), ElcaMessages::TYPE_ERROR);
                }
            } else {
                foreach ($validator->getErrors() as $property => $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        } elseif ($this->Request->isPost() && $this->Request->get('copyFromProcessDbId')) {
            try {
                $importedMappingCount = $importer->copyFromProcessDbMappings(
                    (int)$this->Request->get('copyFromProcessDbId'),
                    $activeProcessDbId,
                    $this->Request->has('removeMappings')
                );

                $this->flashMessages->add(
                    t(':count: Datensätze wurden importiert', null, [':count:' => $importedMappingCount])
                );

                $this->reloadHashUrl();

            } catch (AbstractException $exception) {
                $this->messages->add(t($exception->messageTemplate(), null, $exception->parameters()), ElcaMessages::TYPE_ERROR);
            }
            catch (\Exception $exception) {
                $this->messages->add($exception->getMessage(), ElcaMessages::TYPE_ERROR);
            }
        }
        elseif ($this->Request->isPost() && $this->Request->has('removeMappings')) {
            $mappingRepository->removeByProcessDbId($activeProcessDbId);
        }
        elseif ($this->Request->isPost() && $this->Request->has('cancel')) {
            $this->reloadHashUrl();
            return;
        }

        $view = $this->setView(new MappingsImportView());
        $view->assign('processDbId', $activeProcessDbId);

        $caption = t('Materialmapping [:dbVersion:]', null, [':dbVersion:' => ElcaProcessDb::findById($activeProcessDbId)->getVersion()]);
        $this->Osit->add(new ElcaOsitItem($caption, $this->getActionLink('mappings'), t('ImportAssistent')));
        $this->Osit->add(new ElcaOsitItem(t('CSV Import'), null, $caption));
    }

    /**
     * Action selectProcessConfig
     */
    protected function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if (isset($this->Request->term)) {
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $inUnit   = $this->Request->has('u') ? $this->Request->get('u') : null;
            $Results  = ElcaProcessConfigSearchSet::findByKeywords(
                $keywords,
                $this->Elca->getLocale(),
                $inUnit,
                !$this->Access->hasAdminPrivileges(),
                [$this->Request->db],
                null,
                $this->Request->epdSubType
            );

            $returnValues = [];
            foreach ($Results as $Result) {
                $DO           = $returnValues[] = new \stdClass();
                $DO->id       = $Result->id;
                $DO->catId    = $Result->process_category_node_id;
                $DO->label    = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name.' > '.$Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        elseif (!isset($this->Request->select)) {
            $index = (int)($this->Request->data ?? 0);

            $view = $this->setView(new ElcaProcessConfigSelectorView());
            $view->assign('db', $this->Request->db);
            $view->assign(
                'processConfigId',
                $this->Request->sp ? $this->Request->sp : ($this->Request->id ? $this->Request->id : $this->Request->p)
            );
            $view->assign('relId', $this->Request->relId);
            $view->assign('data', $this->Request->data);
            $view->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('context', self::CONTEXT);
            $view->assign('epdSubType', $this->Request->epdSubType);
            $view->assign('allowDeselection', $index === self::MAPPING_TYPE_MULTIPLE);
        } /**
         * If user pressed select button, assign the new process
         */
        elseif (isset($this->Request->select)) {

            $mappingRepository = $this->get(MaterialMappingInfoRepository::class);

            $firstMappingId      = $this->Request->relId ?: null;
            $index               = (int)($this->Request->data ?? 0);
            $processConfigId     = $this->Request->id ?: $this->Request->p;
            $materialMappingInfo = $firstMappingId ? $mappingRepository->findById((int)$firstMappingId) : null;

            $data = $this->buildData($materialMappingInfo);

            $data->processConfigId[$index] = $processConfigId;

            $view = $this->setView(new MappingEditView());
            $view->assign('buildMode', MappingEditView::BUILDMODE_SELECT);
            $view->assign('data', $data);
            $view->assign('context', self::CONTEXT);
            $view->assign('changedMapping', $index);
        }
    }

    /**
     * @param $materialMappingInfo
     */
    protected function addOsitEditItems(MaterialMappingInfo $materialMappingInfo)
    {
        $caption = t('Materialmapping [:dbVersion:]', null, [':dbVersion:' => ElcaProcessDb::findById($materialMappingInfo->processDbId())->getVersion()]);
        $this->Osit->add(new ElcaOsitItem($caption, $this->getActionLink('mappings'), t('ImportAssistent')));
        $this->Osit->add(new ElcaOsitItem($materialMappingInfo->materialName(), null, $caption));
    }

    /**
     * @return mixed
     */
    private function getActiveProcessDbId()
    {
        if (!isset($this->sessionNamespace->processDbId)) {
            $this->sessionNamespace->processDbId = ElcaProcessDb::findMostRecentVersion()->getId();
        }

        return $this->sessionNamespace->processDbId;
    }

    private function buildData(MaterialMappingInfo $mappingInfo = null)
    {
        // defaults
        $processConfigIds = $mappingIds = [0 => null, 1 => null];
        $ratios           = [0 => 1, 1 => 0];

        if (null === $mappingInfo) {
            return (object)[
                'id'              => null,
                'mode'            => self::MAPPING_TYPE_SINGLE,
                'processDbId'     => $this->getActiveProcessDbId(),
                'mappingId'       => $mappingIds,
                'processConfigId' => $processConfigIds,
                'siblingRatio'    => $ratios,
            ];
        }

        foreach ($mappingInfo->materialMappings() as $index => $mapping) {
            $mappingIds[$index]       = $mapping->surrogateId();
            $processConfigIds[$index] = $mapping->mapsToProcessConfigId();

            if (null !== $mapping->ratio()) {
                $ratios[$index] = $mapping->ratio();
            }
        }

        return (object)[
            'id'              => $mappingInfo->firstMaterialMapping()->surrogateId(),
            'materialName'    => $mappingInfo->materialName(),
            'processDbId'     => $mappingInfo->processDbId(),
            'mode'            => $this->getMappingMode($mappingInfo),
            'mappingId'       => $mappingIds,
            'processConfigId' => $processConfigIds,
            'siblingRatio'    => $ratios,
        ];
    }


    private function getMappingMode(MaterialMappingInfo $mappingInfo): int
    {
        if ($mappingInfo->requiresSibling()) {
            return self::MAPPING_TYPE_SIBLINGS;
        }

        if ($mappingInfo->requiresAdditionalComponent()) {
            return self::MAPPING_TYPE_MULTIPLE;
        }

        return self::MAPPING_TYPE_SINGLE;
    }

    /**
     * Adds the navigation view
     *
     * @return void
     */
    private function addNavigationView($action = null)
    {
        // set active controller in navigation
        $NavView = $this->addView(new ElcaAdminNavigationLeftView());
        $NavView->assign('activeCtrlName', $this->ident());
        $NavView->assign('activeCtrlAction', $action ? $action : $this->getAction());
    }
}
