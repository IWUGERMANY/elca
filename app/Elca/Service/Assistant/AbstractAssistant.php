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

namespace Elca\Service\Assistant;

use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementType;
use Elca\Model\Assistant\Configuration;

/**
 * WindowAssistant
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
abstract class AbstractAssistant implements ElementAssistant
{
    /** @var Configuration  */
    private $configuration;

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param ElcaElementType $elementType
     * @param string          $context
     * @return bool
     */
    public function provideForElementType(ElcaElementType $elementType, $context)
    {
        return in_array($elementType->getDinCode(), $this->configuration->getDinCodes());
    }

    /**
     * @param ElcaElement $element
     * @return bool
     */
    public function provideForElement(ElcaElement $element)
    {
        if (!$element->isInitialized()) {
            return false;
        }

        $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), $this->configuration->getIdent());

        if ($attr->isInitialized())
            return true;

        return false;
    }

    /**
     * @param Configuration $configuration
     */
    protected function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }
}
