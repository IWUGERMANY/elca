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
use Elca\Db\ElcaProcessConfigSanity;

/**
 * Formats fields for a ProcessConfigSanity
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlProcessConfigSanity extends HtmlDataElement implements Formatter
{
    /**
     * Sanity status map
     *
     * @translate array Elca\View\helpers\ElcaHtmlProcessConfigSanity::$sanityStatusMap
     */
    public static $sanityStatusMap = [
        ElcaProcessConfigSanity::STATUS_MISSING_PRODUCTION => 'Kein Herstellungsprozess',
        ElcaProcessConfigSanity::STATUS_MISSING_EOL => 'Kein Entsorgungsprozess',
        ElcaProcessConfigSanity::STATUS_MISSING_CONVERSIONS => 'Fehlender Umrechnungsfaktor',
        ElcaProcessConfigSanity::STATUS_MISSING_LIFE_TIME => 'Fehlende Nutzungsdauer',
        ElcaProcessConfigSanity::STATUS_MISSING_DENSITY => 'Fehlende Rohdichte',
    ];


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $factory = new HtmlDOMFactory($Document);

        $dataObject = $this->getDataObject();
        $name       = $this->getName();
        $value      = $dataObject->$name;
        $backReference = 'ref'.$dataObject->id;

        switch ($name) {
            case 'name':
                $element = $factory->getA(
                    ['href' => '/processes/'.$dataObject->process_config_id.'/?back='.$backReference, 'class' => 'page'],
                    $value
                );
                break;

            case 'status':
                $element = $factory->getSpan(
                    isset(self::$sanityStatusMap[$value]) ? t(self::$sanityStatusMap[$value]) : $value
                );
                break;

            case 'is_false_positive':
                $args = [];

                if ($value) {
                    $args['href'] = '/sanity/processes/sanityFalsePositive/?unset&id='.$dataObject->id;
                } else {
                    $args['href'] = '/sanity/processes/sanityFalsePositive/?id='.$dataObject->id;
                }

                $element = $factory->getA($args, $value ? t('zurücksetzen') : t('ignorieren'));
                break;

            default:
                return $factory->getText($value);
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($element, $dataObject, $name);

        return $element;
    }
    // End build

    /**
     * Formats the object
     *
     * @see Formatter::format()
     */
    public function format(HtmlElement $obj, $DataObject = null, $property = null)
    {
        if (null === $DataObject) {
            return;
        }

        $obj->setId('ref'. $DataObject->id);

        if (!$DataObject->is_reference) {
            $obj->addClass('inactive');
        }
    }
}
// End ElcaHtmlProcessConfigSanity
