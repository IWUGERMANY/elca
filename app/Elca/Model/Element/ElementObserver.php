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

namespace Elca\Model\Element;

use Elca\Db\ElcaElement;

/**
 * Interface ElementObserver
 *
 * @package Elca\Model\Element
 */
interface ElementObserver
{
    /**
     * @param ElcaElement $elementToDelete
     * @return bool
     */
    public function onElementDelete(ElcaElement $elementToDelete);

    /**
     * @param int $elementId
     * @param     $projectVariantId
     */
    public function afterDeletion($elementId, $projectVariantId = null);

    /**
     * @param ElcaElement $createdElement
     * @return mixed
     */
    public function onElementCreate(ElcaElement $createdElement);

    /**
     * @param ElcaElement $updatedElement
     * @return mixed
     */
    public function onElementUpdate(ElcaElement $updatedElement);

    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
    public function onElementCopy(ElcaElement $element, ElcaElement $copiedElement);

    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
}
