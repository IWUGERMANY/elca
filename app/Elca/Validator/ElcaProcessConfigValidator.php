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

namespace Elca\Validator;

use Elca\ElcaNumberFormat;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;

class ElcaProcessConfigValidator extends ElcaValidator
{
    public function assertConversion(Conversion $conversion, $factorPrefix, $inUnitPrefix, $outUnitPrefix)
    {
        $id = $conversion instanceof LinearConversion && $conversion->hasSurrogateId()
            ? $conversion->surrogateId()
            : 'new_' . $conversion->fromUnit() . '_' . $conversion->toUnit();

        $factorProperty = $factorPrefix . $id;
        $inUnitPrefixProperty = $inUnitPrefix . $id;
        $outUnitPrefixProperty = $outUnitPrefix . $id;

        $this->assertNotEmpty(
            $factorProperty,
            null,
            t(
                'Bitte geben Sie einen Faktor für die Umrechnung von %inUnit% nach %outUnit% ein',
                null,
                [
                    '%inUnit%'  => ElcaNumberFormat::formatUnit((string)$conversion->fromUnit()),
                    '%outUnit%' => ElcaNumberFormat::formatUnit((string)$conversion->toUnit()),
                ]
            )
        );

        $this->assertNumber($factorProperty, null, t('Es sind nur numerische Werte erlaubt'));

        $this->assertNotEmpty(
            $inUnitPrefixProperty,
            null,
            t(
                'Bitte geben Sie eine Eingabeeinheit für die Umrechnung nach %outUnit% ein',
                null,
                ['%outUnit%' => ElcaNumberFormat::formatUnit((string)$conversion->toUnit())]
            )
        );
        $this->assertNotEmpty(
            $outUnitPrefixProperty,
            null,
            t(
                'Bitte geben Sie eine Ausgabeeinheit für die Umrechnung von %inUnit%  ein',
                null,
                ['%inUnit%' => ElcaNumberFormat::formatUnit((string)$conversion->fromUnit())]
            )
        );

        return $this->hasErrors();
    }

    public function assertLifeTime($minLifeTimeProperty, $avgLifeTimeProperty, $maxLifeTimeProperty)
    {
        $minLifeTime = $this->getValue($minLifeTimeProperty);
        $avgLifeTime = $this->getValue($avgLifeTimeProperty);
        $maxLifeTime = $this->getValue($maxLifeTimeProperty);

        $this->assertTrue(
            $minLifeTimeProperty,
            $minLifeTime || $avgLifeTime || $maxLifeTime,
            t('Es muss mindestens eine Nutzungsdauer spezifiziert werden.')
        );

        if ($minLifeTime && $avgLifeTime) {
            $this->assertTrue(
                $minLifeTimeProperty,
                $minLifeTime < $avgLifeTime,
                t('Min. muss kleiner der mittleren Nutzungsdauer sein.')
            );
        }

        if ($avgLifeTime && $maxLifeTime) {
            $this->assertTrue(
                $avgLifeTimeProperty,
                $avgLifeTime < $maxLifeTime,
                t('Die mittlere muss kleiner der maximalen Nutzungsdauer sein.')
            );
        }

        return $this->hasErrors();
    }
}
