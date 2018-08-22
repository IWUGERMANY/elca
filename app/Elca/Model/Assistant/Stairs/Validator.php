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

namespace Elca\Model\Assistant\Stairs;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Assert\LazyAssertionException;
use Beibob\HtmlTools\HtmlFormValidator;
use Elca\Model\Assistant\Stairs\Construction\MiddleHolm;
use Elca\Model\Assistant\Stairs\Construction\Solid;
use Elca\Model\Assistant\Stairs\Construction\Stringer;

/**
 * Validator
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Validator extends HtmlFormValidator
{

    /**
     * @param Staircase $staircase
     */
    public function assert(Staircase $staircase)
    {
        try
        {
            $assert = \Assert\lazy();
            $assert
                ->that($staircase->getName(), 'name')->notBlank(t('Bitte vergeben Sie einen Namen'))
                ->that($staircase->getSteps()->getAmount(), 'numberOfSteps')->min(1, t('Bitte spezifizieren Sie die Anzahl Stufen'))
                ->that($staircase->getSteps()->getStep()->getWidth(), 'width')->notEmpty(t('Bitte geben Sie die Breite der Treppe an'))
                ->that($staircase->getSteps()->getStep()->getDepth(), 'stepDepth')->notEmpty(t('Bitte geben Sie die Tiefe einer Stufe an'))
                ->that($staircase->getSteps()->getStep()->getHeight(), 'stepHeight')->notEmpty(t('Bitte geben Sie die Höhe einer Stufe an'))
            ;

            $cover = $staircase->getSteps()->getStep()->getCover();
            $assert
                ->that($cover->getLength1(), 'coverLength1')->notEmpty(t('Bitte geben Sie die Tiefe der Trittfläche an'))
                ->that($cover->getLength1(), 'coverLength1')->min($staircase->getSteps()->getStep()->getDepth(), t('Die Trittfläche muss mindestens so tief wie die Stufe sein'))
                ->that($cover->getSize(), 'coverSize')->notEmpty(t('Bitte geben Sie die Dicke der Trittfläche an'))
                ->that($cover->getMaterial()->getMaterialId(), 'materialId[cover]')->notEmpty(t('Bitte spezifizieren Sie das Material der Trittfläche'))
            ;

            if ($cover->isTrapezoid()) {
                $assert
                    ->that($cover->getLength2(), 'coverLength2')->notEmpty(t('Bitte geben Sie eine 2. Tiefe der Trapez-Trittfläche an'))
                    ->that($cover->getLength2(), 'coverLength2')->min($staircase->getSteps()->getStep()->getDepth(), t('Die 2. Tiefe der Trapez-Trittfläche muss mindestens so tief wie die Stufe sein'))
                ;
            }

            if ($riser = $staircase->getSteps()->getStep()->getRiser()) {
                $assert
                    ->that($riser->getSize(), 'riserSize')->notEmpty(t('Bitte geben Sie die Dicke der Setzstufe an'))
                    ->that($riser->getHeight(), 'riserHeight')->notEmpty(t('Die Höhe der Setzstufe muss spezifiziert werden'))
                    ->that($riser->getHeight(), 'riserHeight')->max($staircase->getSteps()->getStep()->getHeight(), t('Die Höhe der Setzstufe darf nicht höher als die Stufe sein'))
                    ->that($riser->getMaterial()->getMaterialId(), 'materialId[riser]')->notEmpty(t('Bitte spezifizieren Sie das Material der Setzstufe'))
                ;
            }

            switch ($staircase->getType()) {

                case Staircase::TYPE_SOLID:
                    /** @var Solid $solid */
                    $solid = $staircase->getConstruction();
                    $assert
                        ->that($solid->getHeight(), 'solidSlabHeight')->notEmpty(t('Bitte geben Sie die Dicke der Laufplatte an'))
                        ->that($solid->getMaterial(0)->getMaterialId(), 'materialId[solid1]')->notEmpty(t('Bitte spezifizieren Sie das Material der Laufplatte'))
                        ->that($solid->getMaterial(0)->getShare(), 'solidMaterial1Share')->notEmpty(t('Bitte geben Sie den Gefachanteil der Laufplatte an'))
                    ;
                    $material2 = $solid->getMaterial(1);
                    if ($material2 && $material2->getMaterialId()) {
                        $assert
                            ->that($material2->getShare(), 'solidMaterial2Share')->notEmpty(t('Bitte geben Sie den Anteil des 2. Materials der Laufplatte an'))
                            ->that($material2->getShare() + $solid->getMaterial(0)->getShare(), 'solidMaterial2Share')->eq(1, t('Die Summe der beiden Anteile muss 100% ergeben'))
                        ;

                    } elseif ($material2 && $material2->getShare()) {
                        $assert
                            ->that($material2->getMaterialId(), 'materialId[solid2]')->notEmpty(t('Bitte spezifizieren Sie das 2. Material der Laufplatte'))
                        ;
                    }
                    break;

                case Staircase::TYPE_MIDDLE_HOLM:
                    /** @var MiddleHolm $holm */
                    $holm = $staircase->getConstruction();

                    $assert
                        ->that($holm->getOrientation(), 'holmOrientation')->inArray([MiddleHolm::ORIENTATION_ASCENDING, MiddleHolm::ORIENTATION_VERTICAL], t('Bitte wählen Sie den Verlauf des Holms'))
                        ->that($holm->getShape(), 'holmShape')->inArray([MiddleHolm::SHAPE_ELLIPSOID, MiddleHolm::SHAPE_RECTANGLE], t('Bitte wählen Sie eine Form des Holms'))
                        ->that($holm->getWidth(), 'holmWidth')->notEmpty(t('Bitte geben Sie die Breite des Holms an'))
                        ->that($holm->getHeight(), 'holmHeight')->notEmpty(t('Bitte geben Sie die Höhe des Holms an'))
                        ->that($holm->getMaterial(0)->getMaterialId(), 'materialId[holm]')->notEmpty(t('Bitte spezifizieren Sie das Material des Mittelholms'))
                    ;
                    break;

                case Staircase::TYPE_STRINGER:
                    /** @var Stringer $stringer */
                    $stringer = $staircase->getConstruction();
                    $assert
                        ->that($stringer->getWidth(), 'stringerWidth')->notEmpty(t('Bitte geben Sie die Dicke einer Wange an'))
                        ->that($stringer->getHeight(), 'stringerHeight')->notEmpty(t('Bitte geben Sie die Höhe einer Wange an'))
                        ->that($stringer->getMaterial(0)->getMaterialId(), 'materialId[stringer]')->notEmpty(t('Bitte spezifizieren Sie das Material der Wangen'))

                    ;
                    break;
            }

            // platform

            if ($staircase->hasPlatform()) {
                $platform = $staircase->getPlatform();

                $assert
                    ->that($platform->getWidth(), 'platformWidth')->notEmpty(t('Bitte geben Sie die Breite des Podests an'))
                    ->that($platform->getHeight(), 'platformHeight')->notEmpty(t('Bitte geben Sie die Länge des Podests an'))
                ;
            }

            $assert->verifyNow();
        }
        catch(LazyAssertionException $container)
        {
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

        switch($e->getCode())
        {
            case Assertion::INVALID_RANGE;
                $message = t('Der Wert muss zwischen %min% und %max% liegen', null, ['%min%' => $e->getConstraints()['min'], '%max%' => $e->getConstraints()['max']]);
                break;

            case Assertion::INVALID_MAX:
                $message = t('Der Wert darf %max% nicht überschreiten', null, ['%max%' => $e->getConstraints()['max']]);
                break;
            case Assertion::INVALID_MIN:
                $message = t('Der Wert darf %min% nicht unterschreiten', null, ['%min%' => $e->getConstraints()['min']]);
                break;
        }

        return $message;
    }
}
