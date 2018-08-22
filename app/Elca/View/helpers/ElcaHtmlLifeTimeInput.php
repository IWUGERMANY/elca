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
namespace Elca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\Blibs\IdFactory;
use Beibob\HtmlTools\ObjectPropertyConverter;
use DOMDocument;
use Elca\Db\ElcaProcessConfig;

/**
 * Numeric input form element
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlLifeTimeInput extends ElcaHtmlNumericInput
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $currentLifeTime = $this->getConvertedTextValue();

        list(, $key) = preg_split('/[\[\]]/', $this->getName());
        $Converter = new ObjectPropertyConverter();
        $processConfigId = $Converter->convertToText(null, $this->getDataObject(), 'processConfigId[' . $key . ']');

        // try via request, if empty
        $Req = $this->getForm()->getRequest();
        $lifeTimeInfos = $Req->getArray('lifeTimeInfo');

        if (!$processConfigId) {
            $processConfigId = $Req->processConfigId[$key];
        }

        /**
         * Build wrapper and overlay
         */
        $DOMFactory = new HtmlDOMFactory($Document);

        $Outer = $DOMFactory->getDiv(['class' => 'life-time-wrapper']);
        $Input = $Outer->appendChild(parent::build($Document));

        if ($processConfigId) {
            $ProcessConfig = ElcaProcessConfig::findById($processConfigId);

            $lifeTimes = [];
            foreach (['min' => t('Min.'), 'avg' => t('Mittlere'), 'max' => t('Max.')] as $type => $caption) {
                $property = $type . 'LifeTime';
                $infoProperty = $property . 'Info';

                if (!$value = $ProcessConfig->$property)
                    continue;

                if (!$info = $ProcessConfig->$infoProperty)
                    $info = $caption . ' ' . t('Nutzungsdauer');

                $lifeTimes[$value] = $info;
            }

            $lifeTimeInfo = $ProcessConfig->getLifeTimeInfo();

            $OverlayContainer = $Outer->appendChild($DOMFactory->getDiv(['class' => 'life-time-info-link']));

            if ($lifeTimeInfo || count($lifeTimes) > 1) {
                $OverlayContainer->appendChild($DOMFactory->getSpan('i', ['class' => 'symbol']));
            }
            $Overlay = $OverlayContainer->appendChild($DOMFactory->getDiv(['class' => 'life-time-info']));

            if (!$lifeTimeInfo)
                $lifeTimeInfo = t('Nutzungsdauern');

            $Overlay->appendChild($DOMFactory->getH3($lifeTimeInfo));

            $hasOwnLifeTime = true;
            $Ul = $Overlay->appendChild($DOMFactory->getUl());
            foreach ($lifeTimes as $value => $info) {
                $Li = $Ul->appendChild($DOMFactory->getLi());
                $Input = $Li->appendChild($DOMFactory->getInput([
                    'id'                  => $eltId = IdFactory::getUniqueXmlId(),
                    'name'                => 'altLifeTime[' . $key . ']',
                    'type'                => 'radio',
                    'value'               => $value,
                    'class'               => 'mark-no-change',
                    'data-has-text-input' => '0'
                ]));

                if ($value == $currentLifeTime) {
                    $Input->setAttribute('checked', 'checked');
                    $hasOwnLifeTime = false;
                }

                $Li->appendChild($DOMFactory->getLabel($info . ': ' . $value . ' ' . t('Jahre'), ['for' => $eltId]));
            }

            $Li = $Ul->appendChild($DOMFactory->getLi());
            $input = $Li->appendChild($DOMFactory->getInput([
                'id'                  => $eltId = IdFactory::getUniqueXmlId(),
                'name'                => 'altLifeTime[' . $key . ']',
                'type'                => 'radio',
                'value'               => $currentLifeTime,
                'class'               => 'mark-no-change',
                'data-has-text-input' => '1'
            ]));

            if ($hasOwnLifeTime) {
                $input->setAttribute('checked', 'checked');
            }

            $Li->appendChild($DOMFactory->getLabel(t('Eigene'), ['for' => $eltId]));
            $Li->appendChild($DOMFactory->getInput([
                'name'  => 'lifeTimeInfo[' . $key . ']',
                'value' => $Converter->convertToText(isset($lifeTimeInfos[$key]) ? $lifeTimeInfos[$key] : null, $this->getDataObject(), 'lifeTimeInfo[' . $key . ']'),
                'class' => 'mark-no-change'
            ]));


            /**
             * Enable hover
             */
            $DOMFactory->addClass($Outer, 'hover');
        }

        return $Outer;
    }
    // End build
}

// End ElcaHtmlLifeTimeInput
