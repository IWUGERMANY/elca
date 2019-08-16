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

declare(strict_types=1);

namespace ImportAssistant\Model;

use Beibob\Blibs\File;
use Elca\Db\ElcaProcessConfig;
use ImportAssistant\Model\Import\Project;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfoRepository;
use ImportAssistant\Model\SchemaImporter\SchemaImporterV1;
use ImportAssistant\Model\SchemaImporter\SchemaImporterV2;

class Importer
{
    /**
     * @var MaterialMappingInfo[]
     */
    private $materialMappingInfos;

    /**
     * @var null|string
     */
    private $xsdSchemaBasePath;

    /**
     * @var null
     */
    private $processDbId;


    /**
     * Importer constructor.
     *
     * @param MaterialMappingInfoRepository $materialMappingRepository
     * @param string                        $xsdSchemaBasePath
     * @param                               $processDbId
     */
    public function __construct(
        MaterialMappingInfoRepository $materialMappingRepository,
        string $xsdSchemaBasePath,
        int $processDbId
    ) {
        $this->materialMappingInfos = $materialMappingRepository->findByProcessDbId($processDbId);
        $this->xsdSchemaBasePath    = $xsdSchemaBasePath;
        $this->processDbId          = $processDbId;

    }

    public function fromFile(File $file)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadXML(implode('', $file->getAsArray()));

        $schemaImporterRegistry = [
            new SchemaImporterV1($document),
            new SchemaImporterV2($document),
        ];

        /**
         * @var SchemaImporter $schemaImporter
         * @var SchemaImporter $importer
         */
        $schemaImporter = null;
        foreach ($schemaImporterRegistry as $importer) {
            if (!$importer->assertVersion()) {
                continue;
            }

            $schemaImporter = $importer;
            break;
        }

        if (null === $schemaImporter) {
            throw ImportException::unknownSchemaVersion();
        }

        if (!$schemaImporter->isValid($this->xsdSchemaBasePath)) {
            throw ImportException::documentValidationFailed($schemaImporter->schema());
        }

        return $schemaImporter->importProjectNode($this->materialMappingInfos, $this->processDbId);
    }
}
