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

namespace Elca\Repositories\Indicator;

use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaIndicatorSet;
use Elca\Model\Indicator\Indicator;
use Elca\Model\Indicator\IndicatorId;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorRepository;
use Elca\Model\Process\ProcessDbId;
use Utils\Model\FactoryHelper;

class DbIndicatorRepository implements IndicatorRepository
{
    public function findById(IndicatorId $id): ?Indicator
    {
        return $this->build(ElcaIndicator::findById($id->value()));
    }

    public function findByIdent(IndicatorIdent $ident): ?Indicator
    {
        return $this->build(ElcaIndicator::findByIdent($ident->value()));
    }

    /**
     * @return Indicator[]
     */
    public function findAllByProcessDbId(ProcessDbId $processDbId): array
    {
        $indicatorSet = ElcaIndicatorSet::findByProcessDbId($processDbId->value(), true, true);

        return $indicatorSet->map(function (ElcaIndicator $indicator) {
            return $this->build($indicator);
        });
    }

    /**
     * @return Indicator[]
     */
    public function findForProcessingByProcessDbId(ProcessDbId $processDbId): array
    {
        $indicatorSet = ElcaIndicatorSet::findWithPetByProcessDbId($processDbId->value(), false, true);

        return $indicatorSet->map(function (ElcaIndicator $indicator) {
            return $this->build($indicator);
        });
    }

    /**
     * @return Indicator[]
     */
    public function findForDisplayByProcessDbId(ProcessDbId $processDbId): array
    {
        $indicatorSet = ElcaIndicatorSet::findByProcessDbId($processDbId->value(), false, false);

        return $indicatorSet->map(function (ElcaIndicator $indicator) {
            return $this->build($indicator);
        });
    }

    private function build(ElcaIndicator $indicator) : Indicator
    {
        if (!$indicator->isInitialized()) {
            return null;
        }

        return FactoryHelper::createInstanceWithoutConstructor(
            Indicator::class,
            [
                'id' => new IndicatorId($indicator->getId()),
                'name' => $indicator->getName(),
                'ident' => new IndicatorIdent($indicator->getIdent()),
                'unit' => $indicator->getUnit(),
                'isEn15804Compliant' => $indicator->isEn15804Compliant(),
            ]
        );
    }
}
