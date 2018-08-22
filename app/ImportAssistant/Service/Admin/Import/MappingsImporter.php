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

namespace ImportAssistant\Service\Admin\Import;

use Beibob\Blibs\File;
use ImportAssistant\Model\MaterialMapping\MaterialMapping;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfo;
use ImportAssistant\Model\MaterialMapping\MaterialMappingInfoRepository;

class MappingsImporter
{
    /**
     * @var MaterialMappingInfoRepository
     */
    private $mappingInfoRepository;

    /**
     * CsvMappingsImporter constructor.
     *
     * @param MaterialMappingInfoRepository $mappingInfoRepository
     * @internal param DbHandle $dbh
     */
    public function __construct(MaterialMappingInfoRepository $mappingInfoRepository)
    {
        $this->mappingInfoRepository = $mappingInfoRepository;
    }

    public function fromCsvFile(File $file, int $processDbId, bool $removeAllMappingsBeforeCopy = false) : int
    {
        // first line is headline
        $header = $file->getCsv();

        $this->guardColumnCount($header);

        $mappingInfos = [];
        while ($csv = $file->getCsv()) {

            $materialName                = trim($csv[0]);
            $processConfigId             = (int)$csv[1];
            $requiresSibling             = (bool)$csv[2];
            $siblingRatio                = $csv[3] ? ((int)$csv[3]) / 100 : null;
            $requiresAdditionalComponent = (bool)$csv[4];

            if (!isset($mappingInfos[$materialName])) {
                $mappingInfos[$materialName] = (object)[
                    'mappings'                    => [],
                    'requiresSibling'             => $requiresSibling,
                    'requiresAdditionalComponent' => $requiresAdditionalComponent,
                ];
            }

            $mappingInfos[$materialName]->mappings[] = new MaterialMapping($materialName, $processConfigId, $siblingRatio);
        }

        if ($removeAllMappingsBeforeCopy) {
            $this->mappingInfoRepository->removeByProcessDbId($processDbId);
        }

        foreach ($mappingInfos as $materialName => $dataObject) {
            $mappingInfo = new MaterialMappingInfo(
                $materialName,
                $processDbId,
                $dataObject->mappings,
                $dataObject->requiresSibling,
                $dataObject->requiresAdditionalComponent
            );

            $this->mappingInfoRepository->add($mappingInfo);
        }

        return count($mappingInfos);
    }

    public function copyFromProcessDbMappings(int $fromProcessDbId, int $toProcessDbId, bool $removeAllMappingsBeforeCopy = false) : int
    {
        if ($removeAllMappingsBeforeCopy) {
            $this->mappingInfoRepository->removeByProcessDbId($toProcessDbId);
        }

        return $this->mappingInfoRepository->copy($fromProcessDbId, $toProcessDbId);
    }

    /**
     * @param $header
     */
    protected function guardColumnCount($header)
    {
        if (count($header) !== 5) {
            throw new \UnexpectedValueException('Wrong format: column count does not match');
        }
    }
}
