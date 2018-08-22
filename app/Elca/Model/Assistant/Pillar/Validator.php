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

namespace Elca\Model\Assistant\Pillar;

use Assert\Assert;
use Assert\Assertion;
use Assert\AssertionFailedException;
use Assert\LazyAssertionException;
use Beibob\HtmlTools\HtmlFormValidator;
use Elca\Elca;

/**
 * Validator
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Validator extends HtmlFormValidator
{

    /**
     * @param Pillar $pillar
     */
    public function assert(Pillar $pillar)
    {
        try {
            $assert = Assert::lazy();
            $assert
                ->that($pillar->name(), 'name')->notEmpty();
            $assert
                ->that($pillar->height(), 'height')->min(0);
            $assert
                ->that($pillar->unit(), 'unit')->inArray([Elca::UNIT_M, Elca::UNIT_STK]);
            $assert
                ->that($pillar->material1(), 'material1')->notEmpty(
                    t('Bitte spezifizieren Sie das Material der Stütze')
                );

            if ($pillar->shape() === 'cylindric') {
                $assert->that($pillar->width(), 'radius')->notEmpty(t('Bitte geben Sie einen Radius an'));
            } else {
                $assert->that($pillar->width(), 'width')->notEmpty(t('Bitte geben Sie eine Breite an'));
                $assert->that($pillar->length(), 'length')->notEmpty(t('Bitte geben Sie eine Tiefe an'));
            }

            $assert->verifyNow();
        }
        catch (LazyAssertionException $container) {
            $exceptions = $container->getErrorExceptions();

            foreach ($exceptions as $e) {
                if (!$message = $e->getMessage()) {
                    $message = $this->getDefaultMessage($e);
                }

                $this->setError($e->getPropertyPath(), $message);
            }
        }
    }


    /**
     * @param AssertionFailedException $e
     * @return string
     */
    protected function getDefaultMessage(AssertionFailedException $e)
    {
        $message = t('Fehler bei der Eingabe');

        switch ($e->getCode()) {
            case Assertion::INVALID_RANGE;
                $message = t(
                    'Der Wert muss zwischen %min% und %max% liegen',
                    null,
                    ['%min%' => $e->getConstraints()['min'], '%max%' => $e->getConstraints()['max']]
                );
                break;

            case Assertion::INVALID_MAX:
                $message = t('Der Wert darf %max% nicht überschreiten', null, ['%max' => $e->getConstraints()['max']]);
                break;
            case Assertion::INVALID_MIN:
                $message = t(
                    'Der Wert darf %min% nicht unterschreiten',
                    null,
                    ['%min%' => $e->getConstraints()['min']]
                );
                break;
        }

        return $message;
    }
}
