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

namespace Elca\Repositories\Process;

use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Model\Process\ProcessDb;
use Elca\Model\Process\ProcessDbId;
use Elca\Model\Process\ProcessDbRepository;
use Utils\Model\FactoryHelper;

class DbProcessDbRepository implements ProcessDbRepository
{
    /**
     * @return ProcessDb[]
     */
    public function findAll(): array
    {
        $result = [];
        foreach (ElcaProcessDbSet::find() as $elcaProcessDb) {
            $result[] = $this->build($elcaProcessDb);
        }

        return $result;
    }

    public function findById(ProcessDbId $processDbId): ?ProcessDb
    {
        $processDb = ElcaProcessDb::findById($processDbId->value());

        if (!$processDb->isInitialized()) {
            return null;
        }

        return $this->build($processDb);
    }

    private function build(ElcaProcessDb $elcaProcessDb)
    {
        return FactoryHelper::createInstanceWithoutConstructor(
            ProcessDb::class,
            [
                'id'                 => new ProcessDbId($elcaProcessDb->getId()),
                'name'               => $elcaProcessDb->getName(),
                'version'            => $elcaProcessDb->getVersion(),
                'uuid'               => $elcaProcessDb->getUuid(),
                'sourceUri'          => $elcaProcessDb->getSourceUri() ?: null,
                'isActive'           => $elcaProcessDb->isActive(),
                'isEn15804Compliant' => $elcaProcessDb->isEn15804Compliant(),
            ]
        );
    }
}
