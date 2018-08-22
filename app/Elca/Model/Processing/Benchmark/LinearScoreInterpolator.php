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

namespace Elca\Model\Processing\Benchmark;

use Beibob\Blibs\FloatCalc;
use Elca\Model\Indicator\IndicatorIdent;

class LinearScoreInterpolator
{
    /**
     * @var NamedScoreThresholds
     */
    private $namedScoreThresholds;

    /**
     * IndicatorBenchmark constructor.
     *
     * @param NamedScoreThresholds $indicatorThresholds
     */
    public function __construct(NamedScoreThresholds $indicatorThresholds)
    {
        $this->namedScoreThresholds = $indicatorThresholds;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->namedScoreThresholds->name();
    }

    /**
     * @param $value
     * @return float|null
     */
    public function computeScore($value)
    {
        if (!count($this->namedScoreThresholds->thresholds())) {
            return null;
        }

        if (FloatCalc::lt($value, $this->namedScoreThresholds->minScoreValue())) {
            return $this->namedScoreThresholds->minScore();
        }

        if (FloatCalc::gt($value, $this->namedScoreThresholds->maxScoreValue())) {
            return $this->namedScoreThresholds->maxScore();
        }

        $lastScore = $result = null;
        $lastThreshold = 0;
        foreach ($this->namedScoreThresholds->thresholds() as $score => $threshold) {
            if (FloatCalc::gt($value, $lastThreshold) && FloatCalc::le($value, $threshold)) {
                $result = $lastThreshold ? FloatCalc::linInterpol($value, $lastScore, $score, $lastThreshold, $threshold)
                    : $score;
                break;
            }

            $lastThreshold = $threshold;
            $lastScore = $score;
        }

        return $result ?? $lastScore;
    }
}
