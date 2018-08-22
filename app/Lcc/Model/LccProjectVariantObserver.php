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
namespace Lcc\Model;

use Elca\Db\ElcaProjectVariant;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Lcc\Db\LccProjectVersion;
use Lcc\LccModule;

/**
 * Project variant observer
 *
 * @package lcc
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccProjectVariantObserver implements ElcaProjectVariantObserver
{
    /**
     * Called on copy of project variants
     *
     * @param ElcaProjectVariant $OldVariant
     * @param ElcaProjectVariant $NewVariant
     */
    public function onProjectVariantCopy(ElcaProjectVariant $OldVariant, ElcaProjectVariant $NewVariant)
    {
        /**
         * Copy both versions
         */
        $this->copyVersion($OldVariant->getId(), $NewVariant->getId(), LccModule::CALC_METHOD_GENERAL);
        $this->copyVersion($OldVariant->getId(), $NewVariant->getId(), LccModule::CALC_METHOD_DETAILED);
    }
    // End onCopy


    /**
     * @param $oldVariantId
     * @param $newVariantId
     * @param $calcMethod
     * @throws \Exception
     */
    private function copyVersion($oldVariantId, $newVariantId, $calcMethod)
    {
        $projectVersion = LccProjectVersion::findByPK($newVariantId, $calcMethod);
        if ($projectVersion->isInitialized())
            return;

        $projectVersion = LccProjectVersion::findByPK($oldVariantId, $calcMethod);
        if (!$projectVersion->isInitialized())
            return;

        $projectVersion->copy($newVariantId);
    }

}
// End LccProjectVariantObserver
