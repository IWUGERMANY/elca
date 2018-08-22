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
use Beibob\HtmlTools\HtmlDataElement;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\Interfaces\Formatter;
use DOMDocument;
use Elca\Elca;
use RunTimeException;

/**
 * Formats fields for a ElcaHtmlProjectElementSanity
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlEpdTypeFormatter extends HtmlDataElement implements Formatter
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $Factory = new HtmlDOMFactory($Document);

        $DataObject = $this->getDataObject();
        $name = $this->getName();

        $value = isset($DataObject->$name) ? $DataObject->$name : null;

        switch ($name)
        {
            case 'lifeCyclePhase':
                $elt = $Factory->getText(
                    isset(Elca::$lcPhases[$value])
                        ? Elca::$lcPhases[$value]
                        : ''
                );
                break;

            case 'lifeCycleIdent':
                if ($value === 'total') {
                    $caption = t('Gesamt');
                } elseif ($value === 'prodtotal') {
                    $caption = t('Summe') .' '. t('Herstellung');
                } elseif ($value === 'mainttotal') {
                    $caption = t('Summe') .' '. t('Instandhaltung');
                }
                else {
                    $caption = $value;
                }

                $elt = $Factory->getText($caption);
                break;

            default:
                throw new RuntimeException('Unknown property: '. $name);
        }

        return $elt;
    }
    // End build

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Processes the given values and either sets some properties on the $obj
     * or returns an array with attribute => value pairs
     *
     * @param  mixed $value
     */
    public function format(HtmlElement $obj, $dataObject = null, $property = null)
    {
       if (!$dataObject) {
           return;
       }

       if (!isset($dataObject->lifeCycleIdent)) {
           return;
       }

        if (in_array($dataObject->lifeCycleIdent, ['total', 'prodtotal', 'mainttotal'], true)) {
            $obj->setAttribute('class', $dataObject->lifeCycleIdent);
        }
    }
}
