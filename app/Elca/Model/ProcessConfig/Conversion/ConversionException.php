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

namespace Elca\Model\ProcessConfig\Conversion;

use Elca\Db\ElcaProcessConfig;
use Elca\Model\Common\Unit;
use Elca\Model\Exception\AbstractException;
use Elca\Model\ProcessConfig\ProcessConfigId;

class ConversionException extends AbstractException
{
    private const ERROR_MISSING_CONVERSION = 1;

    /**
     * @translate value 'Cannot convert from %fromUnit% to %toUnit%. No such conversion available'
     */
    private const MESSAGE_TEMPLATE = 'Cannot convert from %fromUnit% to %toUnit%. No such conversion available for processConfigId = %processConfigId%';

    /**
     * @var Unit
     */
    private $fromUnit;

    /**
     * @var Unit
     */
    private $toUnit;

    /**
     * @var ProcessConfigId
     */
    private $processConfigId;

    public function __construct(ProcessConfigId $processConfigId, Unit $fromUnit, Unit $toUnit)
    {
        parent::__construct(
            self::MESSAGE_TEMPLATE,
            [
                '%processConfigId%' => (string)$processConfigId,
                '%fromUnit%' => (string)$fromUnit,
                '%toUnit%' => (string)$toUnit,
            ],
            self::ERROR_MISSING_CONVERSION
        );

        $this->processConfigId = $processConfigId;

        $this->fromUnit = $fromUnit;
        $this->toUnit   = $toUnit;
    }

    /**
     * @return Unit
     */
    public function fromUnit()
    {
        return $this->fromUnit;
    }

    /**
     * @return Unit
     */
    public function toUnit()
    {
        return $this->toUnit;
    }

    /**
     * @return ElcaProcessConfig
     */
    public function processConfigId(): ProcessConfigId
    {
        return $this->processConfigId;
    }
}
