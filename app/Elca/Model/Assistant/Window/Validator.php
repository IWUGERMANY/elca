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

namespace Elca\Model\Assistant\Window;

use Assert\Assertion;
use Assert\AssertionFailedException;
use Assert\LazyAssertionException;
use Beibob\Blibs\FloatCalc;
use Beibob\HtmlTools\HtmlFormValidator;

/**
 * Validator
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class Validator extends HtmlFormValidator
{

    /**
     * @param Window $window
     */
    public function assert(Window $window)
    {
        try
        {
            $assert = \Assert\lazy();
            $assert
                ->that($window->getBoundary()->getWidth(), 'width')->min(0)
                ->that($window->getBoundary()->getHeight(), 'height')->min(0)
                ->that($window->getSealingWidth() > 0, 'sealingWidth')->true(t('Der Wert für das Stopfmaß muss größer 0 sein'))
                ->that($window->getFixedFrame()->getNumberOfMullions(), 'numberOfMullions')->min(0)
                ->that($window->getFixedFrame()->getNumberOfTransoms(), 'numberOfTransoms')->min(0)
            ;

            $fixedFrame = $window->getFixedFrame();
            $property = $fixedFrame->getFrameWidth() >= $fixedFrame->getSashFrameWidth()? 'fixedFrameWidth' : 'sashFrameWidth';
            $assert->that(
                $fixedFrame->getRatio() * 100,
                $property
            )->max(100, t('Der Rahmenanteil läge bei den aktuellen Angaben bei %s%%. Bitte passen Sie die Rahmenparameter entsprechend an.'))
             ->that(
                 $fixedFrame->getRatio() * 100,
                 $property
             )->min(0, t('Der Rahmenanteil läge bei den aktuellen Angaben bei %s%%. Bitte passen Sie die Rahmenparameter entsprechend an.'));

            $assert->that($fixedFrame->getMaterialId(), 'processConfigId[fixedFrame]')->notNull(t('Bitte spezifizieren Sie das Material für den Blendrahmen'))
                   ->that($window->getSealing()->getMaterialId(), 'processConfigId[sealing]')->notNull(t('Bitte spezifizieren Sie das Material für die Anschlussfuge'))
                   ->that($window->getGlassMaterialId(), 'processConfigId[glass]')->notNull(t('Bitte spezifizieren Sie das Material für die Fensterschreibe'))
            ;

            if ($fixedFrame->hasTopLight()) {

                $minHeight = $fixedFrame->getTopLightMinHeight();
                $maxHeight = $fixedFrame->getTopLightMaxHeight();

                $assert
                    ->that($fixedFrame->getTopLightHeight(), 'topLightHeight')
                    ->min(
                        $minHeight,
                        t('Die Höhe des Oberlichtes muss mindestens %minHeight% m betragen', null, ['%minHeight%' => $minHeight])
                    );

                $assert
                    ->that($fixedFrame->getTopLightHeight(), 'topLightHeight')
                    ->max(
                        $maxHeight,
                        t('Die Höhe des Oberlichtes kann %maxHeight% m nicht überschreiten', null, ['%maxHeight%' => $maxHeight])
                    );
            }

            if ($fixedFrame->getSashFrameWidth()) {
                $assert
                    ->that($fixedFrame->getSashFrameWidth(), 'sashFrameWidth')
                    ->numeric(t('Bitte geben Sie eine Breite für den Flügelrahmen an'))
                    ->that(FloatCalc::gt($fixedFrame->getSashFrameWidth(), 0), 'sashFrameWidth')
                    ->true(t('Die Rahmenbreite des Flügelrahmens muss größer 0 sein'));
            }

            if ($handles = $window->getHandles())
                $assert
                    ->that($handles->getQuantity(), 'handles')
                    ->integerish(t('Bitte geben Sie eine Anzahl für Fenstergriffe ein'))
                    ->that($handles->getQuantity() > 0, 'handles')
                    ->true(t('Die Anzahl Fenstergriffe muss größer 0 sein'))
                    ->that($handles->getMaterialId(), 'processConfigId[handles]')
                    ->notNull(t('Bitte wählen Sie ein Material für die Fenstergriffe'));

            if ($fittings = $window->getFittings())
                $assert
                    ->that($fittings->getQuantity(), 'fittings')
                    ->integerish(t('Bitte geben Sie eine Anzahl für Beschläge ein'))
                    ->that($fittings->getQuantity() > 0, 'fittings')
                    ->true(t('Die Anzahl Beschläge muss größer 0 sein'))
                    ->that($fittings->getMaterialId(), 'processConfigId[fittings]')
                    ->notNull(t('Bitte wählen Sie ein Material für die Beschläge'));


            if ($sill = $window->getSillIndoor())
                $assert
                    ->that(FloatCalc::gt($sill->getDepth(), 0), 'sillIndoorDepth')
                    ->true(t('Bitte geben Sie eine Tiefe für die Fensterbank an'))
                    ->that(FloatCalc::gt($sill->getSize(), 0), 'sillIndoorSize')
                    ->true(t('Bitte geben Sie eine Dicke für die Fensterbank an'))
                    ->that($sill->getMaterialId(), 'processConfigId[sillIndoor]')
                    ->notNull(t('Bitte spezifizieren Sie ein Material für die Fensterbank'));

            if ($sill = $window->getSillOutdoor())
                $assert
                    ->that(FloatCalc::gt($sill->getDepth(), 0), 'sillOutdoorDepth')
                    ->true(t('Bitte geben Sie eine Tiefe für die Fensterbank an'))
                    ->that(FloatCalc::gt($sill->getSize(), 0), 'sillOutdoorSize')
                    ->true(t('Bitte geben Sie eine Dicke für die Fensterbank an'))
                    ->that($sill->getMaterialId(), 'processConfigId[sillOutdoor]')
                    ->notNull(t('Bitte spezifizieren Sie ein Material für die Fensterbank'));

            if ($soffit = $window->getSoffitIndoor())
                $assert
                    ->that(FloatCalc::gt($soffit->getDepth(), 0), 'soffitIndoorDepth')
                    ->true(t('Bitte geben Sie eine Tiefe für die Laibung an'))
                    ->that(FloatCalc::gt($soffit->getSize(), 0), 'soffitIndoorSize')
                    ->true(t('Bitte geben Sie eine Dicke für die Laibung an'))
                    ->that($soffit->getMaterialId(), 'processConfigId[soffitIndoor]')
                    ->notNull(t('Bitte spezifizieren Sie ein Material für die Laibung'));

            if ($soffit = $window->getSoffitOutdoor())
                $assert
                    ->that(FloatCalc::gt($soffit->getDepth(), 0), 'soffitOutdoorDepth')
                    ->true(t('Bitte geben Sie eine Tiefe für die Laibung an'))
                    ->that(FloatCalc::gt($soffit->getSize(), 0), 'soffitOutdoorSize')
                    ->true(t('Bitte geben Sie eine Dicke für die Laibung an'))
                    ->that($soffit->getMaterialId(), 'processConfigId[soffitOutdoor]')
                    ->notNull(t('Bitte spezifizieren Sie ein Material für die Laibung'));

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
                $message = t('Der Wert darf %max% nicht überschreiten', null, ['%max' => $e->getConstraints()['max']]);
                break;
            case Assertion::INVALID_MIN:
                $message = t('Der Wert darf %min% nicht unterschreiten', null, ['%min%' => $e->getConstraints()['min']]);
                break;
        }

        return $message;
    }
}
