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

namespace Elca\Model\Processing;

use Elca\Model\Exception\AbstractException;
use Elca\Model\Process\ProcessId;
use Elca\Model\ProcessConfig\ProcessConfigId;

class ProcessNotFoundInLifeCycleException extends AbstractException
{
    /**
     * @var ProcessId
     */
    private $processId;

    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    public function __construct(ProcessId $processId, ProcessConfigId $processConfigId)
    {
        parent::__construct('Process with id :id: not found in life cycle configuration for processConfigId :processConfigId:', [
            ':id:' => (string)$processId,
            ':processConfigId:' => (string)$processConfigId,
        ]);

        $this->processId = $processId;
        $this->processConfigId = $processConfigId;
    }

    public function processId(): ProcessId
    {
        return $this->processId;
    }

    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }
}
