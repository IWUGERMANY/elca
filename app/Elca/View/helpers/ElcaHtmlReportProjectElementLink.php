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
use DOMDocument;

/**
 * Formats fields for a ElcaHtmlProjectElementSanity
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlReportProjectElementLink extends HtmlDataElement
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $factory = new HtmlDOMFactory($Document);
        $dataObject = $this->getDataObject();

        $property = $this->getName();
        $elementName = $dataObject->$property;

        switch($property)
        {
            case 'element_name':
                $elt = $this->buildLink($factory, $dataObject->element_id, $elementName);
                break;

            case 'composite_element_name':
                $elt = $this->buildLink($factory, $dataObject->composite_element_id, $elementName);
                break;

            default:
                return $factory->getText($elementName);
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($elt, $dataObject, $property);

        return $elt;
    }

    /**
     * @param $DataObject
     * @param $value
     * @param $factory
     * @return mixed
     */
    private function buildLink(HtmlDOMFactory $factory, $elementId, $elementName)
    {
        if (!$elementId) {
            return $factory->getText($elementName);
        }

        $url   = '/project-elements/' . $elementId . '/';
        $value = $elementName . ' [' . $elementId. ']';
        return $factory->getA(array('href' => $url, 'class' => 'page'), $value);
    }
    // End build

    //////////////////////////////////////////////////////////////////////////////////////
}
