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
namespace Bnb\Model\Processing;

use Bnb\Db\BnbWater;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaCacheElementType;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Elca;

/**
 * BnbProcessor
 */
class BnbProcessor
{
    /**
     * BNB 4.1.4 calculation methods
     */
    const BNB414_CALC_METHOD_SURFACE = 'surface';
    const BNB414_CALC_METHOD_MASS = 'mass';

    const BNB414_DEFAULT_CALC_METHOD = self::BNB414_CALC_METHOD_MASS;
    const BNB414_DEFAULT_FACTOR = 20;


    /**
     * BNB 4.1.4 ratio EOL:SEPARATION:RECYCLING is 3:3:4
     */
    private static $bnb414Ratio = [
        Elca::ELEMENT_ATTR_EOL => 0.3,
        Elca::ELEMENT_ATTR_SEPARATION => 0.3,
        Elca::ELEMENT_ATTR_RECYCLING => 0.4
    ];

    /**
     * Compute BNB 4.1.4 by surface ratio of each element
     *
     * @param int    $projectVariantId
     * @param string $calcMethod
     * @return array
     */
    public function computeEolSeparationRecycling($projectVariantId, $calcMethod = self::BNB414_CALC_METHOD_SURFACE)
    {
        $results = [];
        $total = 0;

        $RootElementType = ElcaElementType::findByIdent('300');

        foreach (ElcaElementTypeSet::findWithElementsByParentType($RootElementType, $projectVariantId, true, null, true, null, false) as $ElementType) {
            $Elements = ElcaElementSet::findUnassignedByElementTypeNodeId($ElementType->getNodeId(), $projectVariantId);

            $dinCode = $ElementType->getDinCode();

            /** @var ElcaElement $Element */
            foreach ($Elements as $Element) {

                if ($calcMethod == self::BNB414_CALC_METHOD_SURFACE) {
                    $total += $value = $Element->getSurface();
                } elseif ($calcMethod == self::BNB414_CALC_METHOD_MASS) {
                    $value = ElcaCacheElement::findByElementId($Element->getId())->getMass();
                }

                $results['elements'][$Element->getId()] = $DO = (object)['elementId' => $Element->getId(),
                                                                              'elementName' => $Element->getName(),
                                                                              'dinCode' => $dinCode,
                                                                              'value'   => $value
                ];
            }
        }

        if ($calcMethod == self::BNB414_CALC_METHOD_MASS) {
            $total = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId($projectVariantId, $RootElementType->getNodeId())->getMass();
        }

        $results['total'] = $total;
        $results['ratio'] = 0;
        $results['benchmark'] = 0;

        foreach ($results['elements'] as $elementId => $DO) {

            $DO->ratio = $DO->value / $total;
            $results['ratio'] += $DO->ratio;

            $DO->benchmark = null;
            $factor = 0;
            $counter = 0;
            foreach(Elca::$elementBnbAttributes as $ident => $caption)
            {
                $Attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, $ident);
                if (!$Attr->isInitialized() || !$Attr->getNumericValue())
                    continue;

                list(,,$property) = explode('.', $ident);
                $DO->$property = $Attr->getNumericValue();

                $factor += (self::$bnb414Ratio[$ident] * $DO->$property);
                $counter++;
            }

            // skip if attributes are not complete
            if ($counter !== count(Elca::$elementBnbAttributes))
                continue;

            $results['benchmark'] += ($DO->benchmark = $factor * $DO->ratio * self::BNB414_DEFAULT_FACTOR);
        }

        return $results;
    }
    // End computeEolSeparationRecycling


    /**
     * Computes the BNB benchmark according to BNB 1.2.3
     *
     * @param BnbWater $water
     * @param float    $ngf
     * @return array
     */
    public function computeWaterBenchmark(BnbWater $water, $ngf)
    {
        $benchmark = [];

        $benchmark['niederschlagGenutztGesamt'] = $water->getNiederschlagVersickert() + $water->getNiederschlagGenutzt() + $water->getNiederschlagGenutztOhneWandlung() + $water->getNiederschlagKanalisation();

        // sums
        $benchmark['wasserbedarfProPersonTag'] =
            $water->getSanitaerWcSpar()
            + $water->getSanitaerWc()
            + $water->getSanitaerUrinal()
            + 45 * $water->getSanitaerWaschtisch()  // 45 sec/d
            + 30 * $water->getSanitaerDusche()      // 30 sec/d
            + 20 * $water->getSanitaerTeekueche();  // 20 sec/d
        $benchmark['wasserbedarfProJahr'] =  $water->getAnzahlPersonen() * $benchmark['wasserbedarfProPersonTag'] * 210 / 1000
                                             ;

        $benchmark['wasserbedarfBodenreinigung'] = 0.125 / 1000 *
                                                   (250 * $water->getReinigungSanitaer()
                                                    + 250 * $water->getReinigungLobby()
                                                    + 150 * $water->getReinigungVerkehrsflaeche()
                                                    + 100 * $water->getReinigungBuero()
                                                    +  12 * $water->getReinigungKeller()
                                                   );

        $benchmark['niederschlagDaecher'] = 0.001 * $water->getNiederschlagsmenge() *
                                            ($water->getDach1Flaeche() * $water->getDach1Ertragsbeiwert() +
                                             $water->getDach2Flaeche() * $water->getDach2Ertragsbeiwert() +
                                             $water->getDach3Flaeche() * $water->getDach3Ertragsbeiwert() +
                                             $water->getDach4Flaeche() * $water->getDach4Ertragsbeiwert());

        $benchmark['gesamtfrischwasserbedarf'] = $benchmark['wasserbedarfProJahr'] + $benchmark['wasserbedarfBodenreinigung']
                                                 - $water->getNiederschlagGenutzt() - $water->getBrauchwasser();

        $benchmark['gesamtabwasseraufkommen'] = $benchmark['wasserbedarfProJahr'] + $benchmark['wasserbedarfBodenreinigung'] +
                                                $water->getNiederschlagKanalisation() * 0.5
                                                - $water->getBrauchwasser()
                                                - $water->getBrauchwasserGereinigt();

        $benchmark['wassergebrauchskennwert'] = $benchmark['gesamtfrischwasserbedarf'] + $benchmark['gesamtabwasseraufkommen'];

        $benchmark['grenzwertWasserProMitarbeiter'] = $water->getAnzahlPersonen() * ($water->getSanitaerDusche() == 0? 6.8775 : 8.4525);
        $benchmark['grenzwertWasserFussboden'] = ($ngf * 350 / 24000);
        $benchmark['grenzwertNiederschlag'] = 0.001 * 0.8 * 0.5 * $water->getNiederschlagsmenge() * ($water->getDach1Flaeche() + $water->getDach2Flaeche() + $water->getDach3Flaeche() + $water->getDach4Flaeche());

        $benchmark['grenzwertGesamt'] = 2 * $benchmark['grenzwertWasserProMitarbeiter']
                                        + 2 * $benchmark['grenzwertWasserFussboden']
                                        + $benchmark['grenzwertNiederschlag'];

        $benchmark['verhaeltnis'] = $benchmark['wassergebrauchskennwert'] / $benchmark['grenzwertGesamt'];

        $benchmark['punkte'] = $benchmark['verhaeltnis'] < (2/3)? 150 - (150 * $benchmark['verhaeltnis']) : 110 - (90 * $benchmark['verhaeltnis']);

        return $benchmark;
    }
    // End computeBenchmark


    private function computeScore()
    {

    }


}
// End BnbProcessor
