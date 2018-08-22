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

namespace Lcc\Model\Validation;

use Elca\Validator\ElcaValidator;

class LccValidator extends ElcaValidator
{
    public function assertBenchmarkGroups()
    {
        $names = $this->getValue('name');
        $scores = $this->getValue('score');

        $categories = [];

        if (!\is_array($names) || !\is_array($scores)) {
            return true;
        }

        foreach ($names as $key => $foo) {

            $suffix = '[' . $key . ']';

            foreach (['name' => 'den Namen', 'category' => 'die Sonderbedingung'] as $property => $name) {
                $value = $this->getValue($property . $suffix);
                if (empty($value)) {
                    $this->setError($property . $suffix, t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name]));
                }
            }

            $category              = $this->getValue('category'.$suffix);
            if (!isset($categories[$category])) {
                $categories[$category] = true;
            }
            else {
                $this->setError('category'. $suffix, t('Die Sonderbedingung kann nur einmal ausgewählt werden'));
            }

            foreach ($scores as $relId => $score) {
                list($groupKey,) = explode('-', $relId);

                if ($groupKey != $key) {
                    continue;
                }

                $suffix = '[' . $relId . ']';

                foreach (['score' => 'den Punktwert'] as $property => $name) {
                    $value = $this->getValue($property . $suffix);
                    if (empty($value)) {
                        $this->setError($property . $suffix, t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name]));
                    }
                }

                $this->assertNumber('score' . $suffix, $score);
            }
        }

        return true;
    }
}
