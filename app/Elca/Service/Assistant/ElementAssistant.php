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
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Model\Assistant\Configuration;

/**
 * Interface ElementAssistantDefinition
 *
 * @package Elca\Model\ElementAssistant
 */
interface ElementAssistant
{
    const PROPERTY_NAME = 'name';
    const PROPERTY_REF_UNIT = 'refUnit';
    const PROPERTY_QUANTITY = 'quantity';
    const PROPERTY_COMPONENT_LAYER_SIZE = 'component/layerSize';
    const PROPERTY_COMPONENT_LAYER_AREA_RATIO = 'component/layerAreaRatio';
    const PROPERTY_COMPONENT_LAYER_LENGTH = 'component/layerLength';
    const PROPERTY_COMPONENT_LAYER_WIDTH = 'component/layerWidth';
    const PROPERTY_COMPONENT_PROCESS_CONFIG_ID = 'component/processConfigId';
    const PROPERTY_COMPONENT_QUANTITY = 'component/quantity';
    const PROPERTY_COMPONENT_CONVERSION_ID = 'component/conversionId';
    const PROPERTY_COMPONENT_LIFE_TIME = 'component/lifeTime';
    const PROPERTY_ELEMENTS_ELEMENT_ID = 'elements/elementId';

    const FUNCTION_COMPONENT_DELETE = 'component/delete';
    const FUNCTION_COMPONENT_DELETE_COMPONENT = 'component/deleteComponent';
    const FUNCTION_COMPONENT_ADD_COMPONENT = 'component/addComponent';
    const FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING = 'component/addComponentSibling';
    const FUNCTION_COMPONENT_ADD_LAYER = 'component/addLayer';
    const FUNCTION_ELEMENT_COPY = 'element/copy';
    const FUNCTION_ELEMENT_MOVE = 'element/move';
    const FUNCTION_ELEMENT_DELETE = 'element/delete';
    const FUNCTION_ELEMENT_DELETE_RECURSIVE = 'element/deleteRecursive';
    const FUNCTION_ELEMENT_ADD_ELEMENT = 'element/addElement';
    const FUNCTION_ELEMENT_DELETE_ELEMENT = 'element/deleteElement';
    const FUNCTION_ELEMENT_ASSIGN_ELEMENT = 'element/assignElement';
    const FUNCTION_ELEMENT_ASSIGN_TO_ELEMENT = 'element/assignToElement';
    const FUNCTION_ELEMENT_UNASSIGN_ELEMENT = 'element/unassignElement';

    /**
     * @return string
     */
    public function getIdent();

    /**
     * @return Configuration
     */
    public function getConfiguration();

    /**
     * @param ElcaElementType $elementType
     * @param string          $context
     * @return bool
     */
    public function provideForElementType(ElcaElementType $elementType, $context);

    /**
     * @param ElcaElement $element
     * @return bool
     */
    public function provideForElement(ElcaElement $element);

    /**
     * @param             $property
     * @param ElcaElement $element
     * @return bool
     */
    public function isLockedProperty($property, ElcaElement $element = null);

    /**
     * @param                           $name
     * @param ElcaElement               $element
     * @param ElcaElementComponent|null $component
     * @return bool
     */
    public function isLockedFunction($name, ElcaElement $element = null, ElcaElementComponent $component = null);
}
