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

use Beibob\Blibs\Environment;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Elca;

/**
 * ElementAssistantRegistry
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElementAssistantRegistry
{
    /**
     * @var ElementAssistant[]
     */
    private $assistants;

    /**
     * Construct
     *
     * @param array $elementAssistants
     */
    public function __construct(array $elementAssistants)
    {
        $this->assistants = [];

        foreach ($elementAssistants as $assistant) {
            $this->register($assistant);
        }
    }

    /**
     * @param ElementAssistant $assistant
     */
    public function register(ElementAssistant $assistant)
    {
        $this->assistants[$assistant->getConfiguration()->getIdent()] = $assistant;
    }


    /**
     * @param string $ident
     * @return bool
     */
    public function hasAssistantByIdent($ident)
    {
        return isset($this->assistants[$ident]);
    }

    /**
     * @param string $ident
     * @return bool
     */
    public function getAssistantByIdent($ident)
    {
        return $this->assistants[$ident];
    }

    /**
     * @param ElcaElementType $elementType
     * @return bool
     */
    public function hasAssistantsForElementType(ElcaElementType $elementType, $context)
    {
        return count($this->getAssistantsForElementType($elementType, $context)) > 0;
    }

    /**
     * @param ElcaElementType $elementType
     * @return ElementAssistant[]
     */
    public function getAssistantsForElementType(ElcaElementType $elementType, $context)
    {
        $assistants = [];

        /**
         * @var  string $ident
         * @var  ElementAssistant $assistant
         */
        foreach ($this->assistants as $ident => $assistant)
        {
            if ($assistant->provideForElementType($elementType, $context)) {
                $assistants[] = $assistant;
            }
        }

        return $assistants;
    }

    /**
     * @param ElcaElement $element
     * @return bool
     */
    public function hasAssistantForElement(ElcaElement $element)
    {
        return $this->getAssistantForElement($element) !== null;
    }

    /**
     * @param ElcaElement $element
     * @return ElementAssistant|null
     */
    public function getAssistantForElement(ElcaElement $element)
    {
        /**
         * @var  string $ident
         * @var  ElementAssistant $assistant
         */
        foreach ($this->assistants as $ident => $assistant)
        {
            if ($assistant->provideForElement($element)) {
                return $assistant;
            }
        }

        return null;
    }
}
