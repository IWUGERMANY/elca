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

namespace Elca\Service\Project;

use Beibob\Blibs\DbHandle;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Security\ElcaAccess;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;
use Exception;

class ProjectService
{
    /**
     * @var DbHandle
     */
    private $dbHandle;

    /**
     * @var ProjectVariantService
     */
    private $projectVariants;

    /**
     * @var LifeCycleUsageService
     */
    private $lifeCycleUsages;

    /**
     * @param DbHandle              $dbHandle
     * @param ProjectVariantService $projectVariants
     * @param LifeCycleUsageService $lifeCycleUsages
     */
    public function __construct(DbHandle $dbHandle, ProjectVariantService $projectVariants, LifeCycleUsageService $lifeCycleUsages)
    {
        $this->dbHandle = $dbHandle;
        $this->projectVariants = $projectVariants;
        $this->lifeCycleUsages = $lifeCycleUsages;
    }

    /**
     * @param ElcaProject $project
     * @param bool        $useSameProjectName
     * @return ElcaProject
     * @throws \Exception
     */
    public function createCopyFromProject(ElcaProject $project, $useSameProjectName = false)
    {
        if(!$project->isInitialized()) {
            throw new \RuntimeException('Project to copy from is not initialized');
        }

        try
        {
            $this->dbHandle->begin();

            $access = ElcaAccess::getInstance();
            $copy   = ElcaProject::create($project->getProcessDbId(),
                $access->getUserId(),
                $access->getUserGroupId(),
                $useSameProjectName? $project->getName() : t('Kopie von').' '.$project->getName(),
                $project->getLifeTime(),
                null, // currentVariantId
                $project->getDescription(),
                $project->getProjectNr(),
                $project->getConstrMeasure(),
                $project->getConstrClassId(),
                $project->getEditor(),
                false, // dont pass isReference flag
                $project->getBenchmarkVersionId()
            );

            /**
             * Copy variants
             */
            if($copy->isInitialized())
            {
                /**
                 * Copy lifeCycle usages
                 */
                $this->lifeCycleUsages->copyFromProject($project->getId(), $copy->getId());

                $projectVariantSet = ElcaProjectVariantSet::find(array('project_id' => $project->getId()), array('id' => 'ASC'));
                $CopyVariants = new ElcaProjectVariantSet();

                $copiedCurrentVariantId = null;
                foreach($projectVariantSet as $projectVariant) {

                    $variantCopy = $this->projectVariants->copy($projectVariant, $copy->getId(), null, true, $copy->getAccessGroupId());
                    $CopyVariants->add($variantCopy);

                    if ($projectVariant->getId() === $project->getCurrentVariantId()) {
                        $copiedCurrentVariantId = $variantCopy->getId();
                    }
                }


                if ($copiedCurrentVariantId) {
                    $copy->setCurrentVariantId($copiedCurrentVariantId);
                } else {
                    /**
                     * Set currentVariantId to first variant
                     */
                    $copy->setCurrentVariantId($CopyVariants[0]->getId());
                }
                $copy->update();
            }

            $this->dbHandle->commit();
        }
        catch(Exception $Exception)
        {
            $this->dbHandle->rollback();
            throw $Exception;
        }

        return $copy;
    }
}
