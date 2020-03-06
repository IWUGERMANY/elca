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

namespace Elca\Validator;

use Beibob\HtmlTools\HtmlFormValidator;
use Elca\Db\ElcaBenchmarkRefProcessConfig;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaIndicator;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProjectFinalEnergyDemand;
use Elca\ElcaNumberFormat;
use Elca\numeric;

/**
 * Special elca validator
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 * @abstract
 *
 */
class ElcaValidator extends HtmlFormValidator
{
    /**
     * Asserts that the specified values is numeric
     *
     * @param  string $property
     * @param null    $value
     * @param string  $error
     *
     * @return boolean
     */
    public function assertNumber($property, $value = null, $error = self::ERR_NOT_A_NUMBER)
    {
        $value = $this->getValue($property, $value);

        if ($value != '' && !ElcaNumberFormat::isNumeric($value)) {
            return $this->setError($property, $error);
        }

        return $this->setAsserted($property);
    }
    // End assertNumber


    /**
     * @param        $property
     * @param        $repeatProperty
     * @param int    $minLength
     * @param null   $dummyValue
     * @param string $error
     * @return bool
     */
    public function assertProjectPassword(
        $property,
        $repeatProperty,
        $minLength = 8,
        $dummyValue = null,
        $minLengthError,
        $repeatError = self::ERR_INVALID
    ) {
        $value       = $this->getValue($property);
        $valueRepeat = $this->getValue($repeatProperty);

        if (($value === '' && $valueRepeat === '') ||
            ($value === $dummyValue && $valueRepeat === $dummyValue)
        ) {
            return $this->setAsserted($property);
        }

        if (!$this->assertMinLength($property, $minLength, null, $minLengthError)) {
            return false;
        }

        if ($value !== $valueRepeat) {
            $this->setError($property, $repeatError);
            $this->setError($repeatProperty, $repeatError);

            return false;
        }

        $this->setAsserted($property);
        $this->setAsserted($repeatProperty);

        return true;
    }

    /**
     * Asserts that the specified values is within range
     *
     * @param  string  $property
     * @param  numeric $minValue
     * @param  numeric $maxValue
     * @param  string  $error
     *
     * @return boolean
     */
    public function assertNumberRange($property, $minValue = null, $maxValue = null, $error = self::ERR_NOT_A_NUMBER)
    {
        $value = ElcaNumberFormat::toString($this->getValue($property));

        if ($value != '') {
            if (!is_null($minValue) && $value < $minValue) {
                return $this->setError($property, $error);
            }

            if (!is_null($maxValue) && $value > $maxValue) {
                return $this->setError($property, $error);
            }
        }

        return $this->setAsserted($property);
    }
    // End assertNumber


    /**
     * Asserts a layer
     *
     * @return boolean
     */
    public function assertLayers()
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds)) {
            return;
        }

        $siblings = [];
        foreach ($processConfigIds as $key => $processConfigId) {
            $processConfig = ElcaProcessConfig::findById($processConfigId);
            $this->assertLayer($key, $processConfig->getLifeTimes());

            $Component = ElcaElementComponent::findById($key);

            if ($Component->hasLayerSibling() && !isset($siblings[$Component->getLayerSiblingId()])) {
                $siblings[$key] = $Component->getLayerSiblingId();
            }
        }

        /**
         * Validate siblings areaRatio
         */
        foreach ($siblings as $key => $siblingId) {
            $suffix1 = '[' . $key . ']';
            $suffix2 = '[' . $siblingId . ']';

            $ratio1 = ElcaNumberFormat::fromString($this->getValue('areaRatio' . $suffix1));
            $ratio2 = ElcaNumberFormat::fromString($this->getValue('areaRatio' . $suffix2));

            $this->assertTrue(
                'areaRatio' . $suffix1,
                $ratio1 + $ratio2 == 100,
                t('Die Summe der Gefachanteile muss 100% ergeben')
            );
            $this->assertTrue(
                'areaRatio' . $suffix2,
                $ratio1 + $ratio2 == 100,
                ''
            );
        }

        return true;
    }
    // End assertLayers


    /**
     * Asserts a layer
     *
     * @param       $key
     * @param array $lifeTimes
     */
    public function assertLayer($key, array $lifeTimes)
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds) || !isset($processConfigIds[$key])) {
            return;
        }

        $suffix = '[' . $key . ']';
        $this->assertNotEmpty('processConfigId' . $suffix, null, t('Kein Baustoff gewählt'));
        if ($this->isValid()) {
            $this->assertNotEmpty('size' . $suffix, null, t('Keine Dicke angegeben'));
            $this->assertNotEmpty('length' . $suffix, null, t('Keine Länge angegeben'));
            $this->assertNotEmpty('width' . $suffix, null, t('Keine Breite angegeben'));
            $this->assertNotEmpty('lifeTime' . $suffix, null, t('Keine Nutzungsdauer angegeben'));
            $this->assertTrue(
                'lifeTime' . $suffix,
                (int)$this->getValue('lifeTime' . $suffix) > 0,
                t('Keine Nutzungsdauer angegeben')
            );
        }


        if ($this->isValid()) {

            $lifeTime = (int)$this->getValue('lifeTime' . $suffix);
            if (!(isset($lifeTimes[$lifeTime]) || array_key_exists($lifeTime, $lifeTimes))) {
                if (!$this->assertNotEmpty('lifeTimeInfo' . $suffix, null, '')) {
                    $this->setError(
                        'lifeTime' . $suffix,
                        t('Bitte geben Sie eine Begründung für die eigene Nutzungsdauer an')
                    );
                }
            }
            $this->assertNumberRange(
                'lifeTimeDelay' . $suffix,
                0,
                max(0, $this->getValue('lifeTime' . $suffix) - 1),
                t('Restnutzungsdauer muss kleiner als die Nutzungsdauer sein.')
            );
        }
    }
    // End assertLayer


    /**
     * Check condition for extant components:
     */
    public function checkExtantComponents(array $componentPositions, array $extantComponents)
    {
        $extantPositions = [];
        $needExtantFix   = [];

        foreach ($componentPositions as $key => $pos) {
            if (isset($extantComponents[$key])) {
                $extantPositions[$key] = $pos;
            }
        }

        $firstExtantPos = reset($extantPositions);
        $lastExtantPos  = end($extantPositions);
        $keyPositions   = array_flip($componentPositions);

        $previousExtantPosition = null;
        foreach ($keyPositions as $pos => $key) {
            $Layer     = ElcaElementComponent::findById($key);
            $siblingId = $Layer->getLayerSiblingId();

            if (isset($extantComponents[$key]) || isset($extantComponents[$siblingId])) {

                if ($siblingId && $pos > $firstExtantPos && $pos < $lastExtantPos &&
                    (!$Layer->isExtant() || !$Layer->getLayerSibling()->isExtant())
                ) {

                    $needExtantFix[] = $pos;
                }

                if (!is_null($previousExtantPosition) && ($pos - $previousExtantPosition > 1)) {
                    for ($x = 1; $x < ($pos - $previousExtantPosition); $x++) {
                        $needExtantFix[] = $pos - $x;
                    }
                }
                $previousExtantPosition = $pos;
            }
        }

        return $needExtantFix;
    }
    // End checkExtantComponents


    /**
     * Check condition for extant composite elements:
     */
    public function checkExtantElements(array $elementPositions, array $extantElements)
    {
        $extantPositions = [];
        $needExtantFix   = [];

        foreach ($elementPositions as $pos => $elementId) {
            $Element = ElcaElement::findById($elementId);
            if ($Element->isOpaque() === false) {
                unset ($elementPositions[$pos]);
                continue;
            }

            if (isset($extantElements[$pos])) {
                $extantPositions[$elementId] = $pos;
            }

            $elementPositions[$pos] = $Element;
        }

        $firstExtantPos = reset($extantPositions);
        $lastExtantPos  = end($extantPositions);

        $previousExtantPosition = null;
        foreach ($elementPositions as $pos => $Element) {
            if (isset($extantElements[$pos])) {

                if ($pos > $firstExtantPos && $pos < $lastExtantPos &&
                    $Element->isExtant() === false
                ) {
                    $needExtantFix[] = $pos;
                }

                if (!is_null($previousExtantPosition) && ($pos - $previousExtantPosition > 1)) {
                    for ($x = 1; $x < ($pos - $previousExtantPosition); $x++) {
                        $Elt = $elementPositions[$pos - $x];
                        if ($Elt->isExtant() === false) {
                            $needExtantFix[] = $pos - $x;
                        }
                    }
                }
                $previousExtantPosition = $pos;
            }
        }

        return $needExtantFix;
    }
    // End checkExtantComponents

    /**
     * Check condition for extant components:
     */
    public function checkLifeTimeComponents(array $componentPositions, array $lifeTimes)
    {
        $lifeTimePositions = [];
        $needFix           = [];

        foreach ($componentPositions as $key => $pos) {
            $lifeTimePositions[$pos] = $lifeTimes[$key];
        }

        $firstLifeTimePos = reset($componentPositions);
        $lastLifeTimePos  = end($componentPositions);
        $keyPositions     = array_flip($componentPositions);

        $previousLifeTime = null;
        foreach ($keyPositions as $pos => $key) {
            if (!isset($lifeTimes[$key])) {
                continue;
            }

            $lifeTime = $lifeTimes[$key];

            if ($pos > $firstLifeTimePos && $pos < $lastLifeTimePos &&
                null !== $previousLifeTime && $lifeTime < $previousLifeTime) {

                $maxLifeTime = \max(\array_slice($lifeTimePositions, $pos));
                for ($x = $pos; $x < $lastLifeTimePos; $x++) {
                    if ($lifeTimes[$keyPositions[$x] ] >= $maxLifeTime) {
                        break;
                    }
                    $needFix[$x] = $keyPositions[$x];
                }
            }
            $previousLifeTime = $lifeTime;
        }

        return $needFix;
    }

    /**
     * Asserts single components
     *
     * @return boolean
     */
    public function assertSingleComponents()
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds)) {
            return;
        }

        foreach ($processConfigIds as $key => $processConfigId) {
            $processConfig = ElcaProcessConfig::findById($processConfigId);
            $this->assertSingleComponent($key, $processConfig->getLifeTimes());
        }

        return true;
    }
    // End assertSingleComponents


    /**
     * Asserts a single component
     */
    public function assertSingleComponent($key, array $lifeTimes)
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds) || !isset($processConfigIds[$key])) {
            return;
        }

        $suffix = '[' . $key . ']';

        $this->assertNotEmpty('processConfigId' . $suffix, null, t('Kein Baustoff gewählt'));
        if ($this->isValid()) {
            $this->assertNotEmpty('quantity' . $suffix, null, t('Keine Menge angegeben'));
            $this->assertNotEmpty('conversionId' . $suffix, null, t('Keine Mengeneinheit angegeben'));
            $this->assertNotEmpty('lifeTime' . $suffix, null, t('Keine Nutzungsdauer angegeben'));
            $this->assertTrue(
                'lifeTime' . $suffix,
                (int)$this->getValue('lifeTime' . $suffix) > 0,
                t('Keine Nutzungsdauer angegeben')
            );
        }

        if ($this->isValid()) {
            $lifeTime = (int)$this->getValue('lifeTime' . $suffix);
            if (!(isset($lifeTimes[$lifeTime]) || array_key_exists($lifeTime, $lifeTimes))) {
                if (!$this->assertNotEmpty('lifeTimeInfo' . $suffix, null, '')) {
                    $this->setError(
                        'lifeTime' . $suffix,
                        t('Bitte geben Sie eine Begründung für die eigene Nutzungsdauer an')
                    );
                }
            }

            $this->assertNumberRange(
                'lifeTimeDelay' . $suffix,
                0,
                max(0, $this->getValue('lifeTime' . $suffix) - 1),
                t('Restnutzungsdauer muss kleiner als die Nutzungsdauer sein.')
            );
        }

    }
    // End assertSingleComponent


    /**
     * Asserts project final energy demans
     *
     * @return boolean
     */
    public function assertProjectFinalEnergySupplies()
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds)) {
            return;
        }

        foreach ($processConfigIds as $key => $processConfigId) {
            $this->assertNotEmpty(
                'quantity[' . $key . ']',
                null,
                t('Bitte geben Sie einen Wert für die Energiebereitstellung an')
            );
            $this->assertNotEmpty(
                'description[' . $key . ']',
                null,
                t('Bitte geben Sie eine Beschreibung für die Energiebereitstellung an')
            );
        }

        return true;
    }
    // End assertProjectFinalEnergySupplies


    /**
     * Asserts project final energy demans
     *
     * @return boolean
     */
    public function assertProjectFinalEnergyDemands()
    {
        $processConfigIds = $this->getValue('processConfigId');
        $isKwk = $this->getValue('isKwk');
        if (!is_array($processConfigIds)) {
            return true;
        }

        foreach ($processConfigIds as $key => $processConfigId) {
            if ($key === ElcaProjectFinalEnergyDemand::IDENT_PROCESS_ENERGY) {
                continue;
            }

            if (isset($isKwk[$key]) && $isKwk[$key]) {
                continue;
            }

            $this->assertProjectFinalEnergyDemand($key);
        }

        return true;
    }

    /**
     * Asserts project final energy demans
     *
     * @return boolean
     */
    public function assertProjectKwkFinalEnergyDemands()
    {
        $processConfigIds = $this->getValue('processConfigId');
        $isKwk = $this->getValue('isKwk');

        if (!is_array($processConfigIds)) {
            return true;
        }

        $atLeastOneIsset = false;
        foreach (['kwkHeating', 'kwkWater', ] as $property) {
            $value = $this->getValue($property);
            $atLeastOneIsset |= !empty($value);
        }
        if (!$this->assertTrue('atleastonereq', $atLeastOneIsset, t('Mindestens ein Wert muss angegeben sein'))) {
            foreach (['kwkHeating', 'kwkWater'] as $property) {
                $this->setError($property);
            }
        }

        $ratios = $this->getValue('ratio');
        $overallRatio = 0;
        foreach ($processConfigIds as $key => $processConfigId) {
            if (isset($isKwk[$key]) && !$isKwk[$key]) {
                continue;
            }

            $this->assertProjectKwkFinalEnergyDemand($key);
            if (isset($ratios[$key])) {
                $overallRatio += ElcaNumberFormat::fromString($ratios[$key]);
            }
        }

        return $this->assertTrue('ratio['. $key .']', $overallRatio >= 0 && $overallRatio <= 100, t('Der Wert muss zwischen 0 und 100 liegen'));
    }

    /**
     * Asserts a ProjectFinalEnergyDemand row
     */
    public function assertProjectFinalEnergyDemand($key)
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds) || !isset($processConfigIds[$key])) {
            return;
        }

        $isset = false;

        $suffix = '[' . $key . ']';

        foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
            $value = $this->getValue($property . $suffix);
            $isset |= !empty($value);
        }

        if (!$this->assertTrue('atleastonereq', $isset, t('Mindestens ein Wert muss angegeben sein'))) {
            foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
                $this->setError($property . $suffix);
            }
        }
    }

    /**
     * Asserts a ProjectFinalEnergyDemand row
     */
    public function assertProjectKwkFinalEnergyDemand($key)
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds) || !isset($processConfigIds[$key])) {
            return;
        }

        $suffix = '[' . $key . ']';

        $value = $this->getValue('ratio' . $suffix);
        $this->assertNotEmpty('ratio'. $suffix, $value, t('Der Wert darf nicht leer sein'));
    }


    /**
     * Asserts project final energy ref models
     *
     * @return boolean
     */
    public function assertProjectFinalEnergyRefModels()
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds)) {
            return;
        }

        foreach ($processConfigIds as $ident => $processConfigId) {
            if ($processConfigId && $ident !== ElcaBenchmarkRefProcessConfig::IDENT_PROCESS_ENERGY) {
                $this->assertProjectFinalEnergyRefModel($ident);
            }
        }

        return true;
    }
    // End assertProjectFinalEnergyDemands

    /**
     * Asserts a ProjectFinalEnergyRefModel row
     */
    public function assertProjectFinalEnergyRefModel($ident)
    {
        $processConfigIds = $this->getValue('processConfigId');
        if (!is_array($processConfigIds) || !isset($processConfigIds[$ident])) {
            return;
        }

        $isset = false;

        $suffix = '[' . $ident . ']';

        foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
            $value = $this->getValue($property . $suffix);
            $isset |= !empty($value);
        }

        if (!$this->assertTrue('atleastonereq', $isset, t('Mindestens ein Wert muss angegeben sein'))) {
            foreach (['heating', 'water', 'lighting', 'ventilation', 'cooling'] as $property) {
                $this->setError($property . $suffix);
            }
        }
    }
    // End assertProjectFinalEnergyRefModel

    /**
     * Asserts transports
     *
     * @return boolean
     */
    public function assertTransports()
    {
        $matProcessConfigIds = $this->getValue('matProcessConfigId');
        $processConfigIds    = $this->getValue('processConfigId');

        if (!is_array($matProcessConfigIds)) {
            return;
        }

        foreach ($matProcessConfigIds as $key => $foo) {

            $suffix = '[' . $key . ']';

            foreach (['name' => 'den Namen', 'quantity' => 'die Menge'] as $property => $name) {
                $value = $this->getValue($property . $suffix);
                if (empty($value)) {
                    $this->setError(
                        $property . $suffix,
                        t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name])
                    );
                }
            }

            foreach ($processConfigIds as $relId => $processConfigId) {

                list($transportKey,) = explode('-', $relId);

                if ($transportKey != $key) {
                    continue;
                }

                $suffix = '[' . $relId . ']';

                if (!$value = $this->getValue('processConfigId' . $suffix)) {
                    $this->setError('processConfigId' . $suffix, t('Bitte wählen Sie ein Transportmittel'));
                } else {

                    foreach (['distance' => 'die Entfernung', 'efficiency' => 'die Auslastung'] as $property => $name) {
                        $value = $this->getValue($property . $suffix);
                        if (empty($value)) {
                            $this->setError(
                                $property . $suffix,
                                t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name])
                            );
                        }
                    }
                }
            }
        }

        return true;
    }

    public function assertBenchmarkGroups()
    {
        $names  = $this->getValue('name');
        $scores = $this->getValue('score');

        if (!\is_array($names) || !\is_array($scores)) {
            return true;
        }

        $indicators = [];

        foreach ($names as $key => $foo) {

            $suffix = '[' . $key . ']';

            foreach (['name' => 'den Namen', 'indicators' => 'mindestens einen Indikator'] as $property => $name) {
                $value = $this->getValue($property . $suffix);
                if (empty($value)) {
                    $this->setError(
                        $property . $suffix,
                        t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name])
                    );
                }
            }

            $indicatorIds = (array)$this->getValue('indicators' . $suffix);

            foreach ($indicatorIds as $indicatorId) {

                if (!isset($indicators[$indicatorId])) {
                    $indicators[$indicatorId] = true;
                } else {
                    $indicator = ElcaIndicator::findById($indicatorId);
                    $this->setError(
                        'indicators' . $suffix,
                        t(
                            'Der Wirkindikator %name% kann nur einmal für alle Gruppen verwendet werden',
                            null,
                            ['%name%' => $indicator->getName()]
                        )
                    );
                }
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
                        $this->setError(
                            $property . $suffix,
                            t('Bitte geben Sie einen Wert für %name% an', null, ['%name%' => $name])
                        );
                    }
                }

                $this->assertNumber('score' . $suffix, $score);
            }
        }

        return true;
    }
}
// End HtmlFormValidator
