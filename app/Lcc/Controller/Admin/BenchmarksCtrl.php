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

namespace Lcc\Controller\Admin;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Url;
use Elca\Controller\Admin\BenchmarksCtrlTrait;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaBenchmarkGroup;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\ElcaNumberFormat;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Lcc\Db\LccBenchmarkGroup;
use Lcc\Db\LccBenchmarkGroupSet;
use Lcc\Db\LccBenchmarkGroupThreshold;
use Lcc\Db\LccBenchmarkGroupThresholdSet;
use Lcc\Db\LccBenchmarkThreshold;
use Lcc\Db\LccBenchmarkThresholdSet;
use Lcc\Model\Validation\LccValidator;
use Lcc\View\Admin\LccAdminBenchmarkGroupsView;
use Lcc\View\Admin\LccAdminBenchmarkVersionLccView;

class BenchmarksCtrl extends TabsCtrl
{
    use BenchmarksCtrlTrait;

    protected function editVersionLccAction($addNavigationViews = true, ElcaValidator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        $data = new \stdClass();

        $categories = [1, 2];

        foreach ($categories as $category) {
            $property = 'category'.$category;

            $data->$property = [];
            foreach (LccBenchmarkThresholdSet::findByBenchmarkVersionIdAndCategory($benchmarkVersionId, $category, ['score' => 'ASC']) as $threshold) {
                $data->$property[$threshold->getScore()] = $threshold->getValue();
            }
        }

        $view = $this->addView(new LccAdminBenchmarkVersionLccView());
        $view->assign('benchmarkVersionId', $benchmarkVersionId);
        $view->assign('data', $data);

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }

    protected function saveLccAction()
    {
        if (!$this->Request->isPost() || !$this->isAjax()) {
            return;
        }

        if ($this->Request->has('save')) {
            $benchmarkVersionId = $this->Request->id;

            /**
             * Validate inputs
             */
            $validator = new ElcaValidator($this->Request);

            $categories = [1, 2];

            foreach ($categories as $category) {
                $property = 'category' . $category;
                $thresholds = $this->Request->getArray($property);
                $countNotEmpty = $maxValue = 0;

                foreach ($thresholds as $score => $value) {
                    if ($value) {
                        $countNotEmpty++;
                        if ($value > $maxValue) {
                            $maxValue = $value;
                        }
                    }
                }

                // skip if empty
                if (!$countNotEmpty)
                    continue;

                $validator->assertTrue($property, $countNotEmpty > 1,
                    t('Für `%name%\' muss mindestens ein Minimum und ein Maximum spezifiziert werden!', null, ['%name%' => t('Sonderbedingung') . $category]));
            }

            if ($validator->isValid()) {

                foreach ($categories as $category) {
                    $property = 'category' . $category;

                    /** @var array $thresholds */
                    $thresholds = $this->Request->getArray($property);
                    foreach ($thresholds as $score => $value) {
                        $value = ElcaNumberFormat::fromString($value, 2);

                        $threshold = LccBenchmarkThreshold::findByBenchmarkVersionIdAndCategoryAndScore($benchmarkVersionId, $category, $score);

                        if ($threshold->isInitialized()) {

                            if ($value) {
                                $threshold->setValue($value);
                                $threshold->update();
                            } else {
                                $threshold->delete();
                            }
                        } else {
                            LccBenchmarkThreshold::create($benchmarkVersionId, $category, $score, $value);
                        }
                    }
                }
                $this->messages->add(t('Die Daten wurden gespeichert'));
            } else {
                foreach ($validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);

                $this->editVersionLccAction(false, $validator);
                return;
            }
        }

        $this->editVersionLccAction(false);
    }

    protected function editVersionLccGroupsAction($addNavigationViews = true, ElcaValidator $validator = null)
    {
        if (!$this->isAjax() || !$this->Request->id || !\is_numeric($this->Request->id)) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;
        $data = new \stdClass();
        $data->name = [];
        $data->category = [];
        $data->score = [];
        $data->caption = [];

        $groups = LccBenchmarkGroupSet::find(['benchmark_version_id' => $benchmarkVersionId], ['name' => 'ASC']);

        foreach ($groups as $benchmarkGroup) {
            $key = $benchmarkGroup->getId();
            $data->name[$key] = $benchmarkGroup->getName();
            $data->category[$key] = $benchmarkGroup->getCategory();

            /**
             * @var LccBenchmarkGroupThreshold $benchmarkGroupThreshold
             */
            foreach (LccBenchmarkGroupThresholdSet::findByGroupId($key) as $benchmarkGroupThreshold) {
                $thresholdKey = $key . '-' . $benchmarkGroupThreshold->getId();

                $data->score[$thresholdKey] = $benchmarkGroupThreshold->getScore();
                $data->caption[$thresholdKey] = $benchmarkGroupThreshold->getCaption();
            }
        }

        $view = $this->setView(new LccAdminBenchmarkGroupsView());
        $view->assign('benchmarkVersionId', $benchmarkVersionId);
        $view->assign('data', $data);

        if (null !== $validator) {
            $view->assign('validator', $validator);
        }

        /**
         * Add osit scenario
         */
        if ($addNavigationViews) {
            $this->addNavigationView('systems');
            $this->addBenchmarkVersionOsit(ElcaBenchmarkVersion::findById($benchmarkVersionId));
        }
    }

    protected function saveVersionGroupsAction()
    {
        if (!$this->Request->isPost() || !$this->Request->id) {
            return;
        }

        if (!$this->Access->hasAdminPrivileges()) {
            return;
        }

        $benchmarkVersionId = $this->Request->id;

        if ($this->Request->has('saveGroups')) {

            $validator = new LccValidator($this->Request);
            $validator->assertBenchmarkGroups();

            $names = $this->Request->getArray('name');
            $scores    = $this->Request->getArray('score');

            if ($validator->isValid()) {
                $dbHandle = DbHandle::getInstance();
                try {
                    $dbHandle->begin();

                    foreach ($names as $key => $foo) {
                        $benchmarkGroup = $this->saveBenchmarkGroup($benchmarkVersionId, $key);

                        $found = 0;
                        foreach ($scores as $relId => $bar) {
                            list($groupKey, $thresholdKey) = explode('-', $relId);

                            if ($groupKey != $key) {
                                continue;
                            }

                            $this->saveBenchmarkGroupThreshold($benchmarkGroup, $groupKey, $thresholdKey);
                            $found++;
                        }

                        if (!$found) {
                            throw new \Exception('Tried to create transport without transport means');
                        }
                    }

                    $dbHandle->commit();
                }
                catch (\Exception $exception) {
                    $dbHandle->rollback();
                    throw $exception;
                }

                $this->messages->add(t('Die Daten wurden gespeichert'));
                $this->editVersionLccGroupsAction(false);
            } else {
                $addNewThresholdFor = null;
                foreach ($validator->getErrors() as $property => $msg) {
                    $this->messages->add(t($msg), ElcaMessages::TYPE_ERROR);
                }
                $this->editVersionLccGroupsAction(false, $validator);
                $View = $this->getViewByName(LccAdminBenchmarkGroupsView::class);
                $View->assign('addNewGroup', isset($names['new']));
            }
        } elseif ($this->Request->has('addGroup')) {
            $this->editVersionLccGroupsAction(false);
            $View = $this->getViewByName(LccAdminBenchmarkGroupsView::class);
            $View->assign('addNewGroup', true);
        }
    }

    /**
     *
     * @param $benchmarkVersionId
     * @param $key
     *
     * @return LccBenchmarkGroup
     */
    protected function saveBenchmarkGroup($benchmarkVersionId, $key)
    {
        if (!isset($this->Request->name[$key])) {
            return null;
        }

        $name = \trim($this->Request->name[$key]);
        $category = (int)$this->Request->category[$key];

        if (is_numeric($key)) {
            $benchmarkGroup = LccBenchmarkGroup::findById($key);

            $benchmarkGroup->setName($name);
            $benchmarkGroup->setCategory($category);
            $benchmarkGroup->update();

        } else {
            $benchmarkGroup = LccBenchmarkGroup::create(
                $benchmarkVersionId,
                $category,
                $name
            );
        }

        return $benchmarkGroup;
    }

    /**
     * Saves a transport with all its tranport means
     *
     * @param ElcaBenchmarkGroup   $benchmarkGroup
     * @param                      $groupKey
     * @param                      $thresholdKey
     * @return void
     */
    protected function saveBenchmarkGroupThreshold(LccBenchmarkGroup $benchmarkGroup, $groupKey, $thresholdKey)
    {
        $key = $groupKey.'-'.$thresholdKey;

        if (!isset($this->Request->score[$key]) || !$this->Request->score[$key]) {
            return;
        }

        $score  = (int)$this->Request->score[$key];
        $caption = \trim($this->Request->caption[$key]);

        if (is_numeric($thresholdKey)) {
            $groupThreshold = LccBenchmarkGroupThreshold::findById($thresholdKey);

            $groupThreshold->setScore($score);
            $groupThreshold->setCaption($caption);
            $groupThreshold->update();

        } else {
            LccBenchmarkGroupThreshold::create(
                $benchmarkGroup->getId(),
                $score,
                $caption
            );
        }
    }

    protected function addGroupThresholdAction()
    {
        if (!$this->Request->id || !$this->Request->groupId) {
            return;
        }

        $groupId = $this->Request->groupId;

        $this->editVersionLccGroupsAction(false);
        $View = $this->getViewByName(LccAdminBenchmarkGroupsView::class);
        $View->assign('groupId', $groupId);
        $View->assign('addNewGroupThreshold', true);
    }

    protected function deleteGroupThresholdAction()
    {
        if (!$this->Request->id || !$this->Request->thresholdId) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $benchmarkGroupThreshold = LccBenchmarkGroupThreshold::findById($this->Request->thresholdId);
        if (!$benchmarkGroupThreshold->isInitialized()) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            if (LccBenchmarkGroupThresholdSet::dbCount(
                    ['group_id' => $benchmarkGroupThreshold->getGroupId()]
                ) - 1 > 0
            ) {
                $benchmarkGroupThreshold->delete();
            } else {
                $benchmarkGroupThreshold->getGroup()->delete();
            }

            $this->editVersionLccGroupsAction(false);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            if (LccBenchmarkGroupThresholdSet::dbCount(
                    ['group_id' => $benchmarkGroupThreshold->getGroupId()]
                ) - 1 > 0) {
                $msg = t(
                    'Bewertung "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $benchmarkGroupThreshold->getCaption()]
                );
            } else {
                $msg = t(
                    'Gruppe "%name%" wirklich löschen?',
                    null,
                    ['%name%' => $benchmarkGroupThreshold->getGroup()->getName()]
                );
            }

            $this->messages->add(
                $msg,
                ElcaMessages::TYPE_CONFIRM,
                (string)$Url
            );
        }
    }

}