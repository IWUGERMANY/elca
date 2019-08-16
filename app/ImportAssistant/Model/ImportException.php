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

namespace ImportAssistant\Model;

use Elca\Model\Exception\AbstractException;

class ImportException extends AbstractException
{
    /**
     * @translate const ImportAssistant\Model\ImportException::MISSING_ATTRIBUTE
     */
    const MISSING_ATTRIBUTE = 'Element `:elementName:\' has no attribute `:attributeName:\' on :nodePath:';
    /**
     * @translate const ImportAssistant\Model\ImportException::XPATH_NOT_FOUND_IN_CONTEXT
     */
    const XPATH_NOT_FOUND_IN_CONTEXT = 'Path `:xpath:\' not found in context `:context:\'';

    /**
     * @translate const ImportAssistant\Model\ImportException::PROJECT_NAME_IS_EMPTY
     */
    const PROJECT_NAME_IS_EMPTY = 'Project name is empty';

    /**
     * @translate const ImportAssistant\Model\ImportException::DOCUMENT_HAS_INVALID_ROOT_ELEMENT
     */
    const DOCUMENT_HAS_INVALID_ROOT_ELEMENT = 'Document has invalid root element';

    /**
     * @translate const ImportAssistant\Model\ImportException::DOCUMENT_VALIDATION_FAILED
     */
    const DOCUMENT_VALIDATION_FAILED = 'Document validation (:schema:) failed';

    /**
     * @translate const ImportAssistant\Model\ImportException::UNKNOWN_SCHEMA_VERSION
     */
    const UNKNOWN_SCHEMA_VERSION = 'Unknown schema version';


    public static function projectNameIsInvalid()
    {
        return new self(self::PROJECT_NAME_IS_EMPTY);
    }

    public static function documentHasInvalidRootElement()
    {
        return new self(self::DOCUMENT_HAS_INVALID_ROOT_ELEMENT);
    }

    public static function documentValidationFailed(string $schemaName)
    {
        return new self(self::DOCUMENT_VALIDATION_FAILED, [':schema:' => $schemaName]);
    }

    public static function unknownSchemaVersion()
    {
        return new self(self::UNKNOWN_SCHEMA_VERSION);
    }


    public static function pathNotFound(string $xpath, string $context = null)
    {
        return new self(
            self::XPATH_NOT_FOUND_IN_CONTEXT, [
            ':xpath:' => $xpath,
            ':context:' => $context ?: '/'
        ]);
    }

    public static function missingAttribute($elementName, $attributeName, $nodePath)
    {
        return new self(
            self::MISSING_ATTRIBUTE, [
           'elementName' => $elementName,
           'attributeName' => $attributeName,
           'nodePath' => $nodePath,
        ]);
    }
}
