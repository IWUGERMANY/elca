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
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlDataElement;
use DOMDocument;
use Elca\Db\ElcaProcessDb;
use Elca\Elca;

/**
 * Formats fields for a ElcaHtmlProjectElementSanity
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlProcessWithDatasheetLink extends HtmlDataElement
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
        $value = $DataObject->$name;

        $Elt = $Factory->getDiv(['class' => 'process-selector-link']);

        $Elt->appendChild($Factory->getText($this->getConvertedTextValue()));


        $ProcessDb = ElcaProcessDb::findById($DataObject->processDbId);
        if (!$ProcessDb->isInitialized())
            return $Elt;

        if (!$ProcessDb->hasSourceUri())
            return $Elt;

        $aAttr = [
            'class'    => 'data-sheet-link icon no-xhr'
            , 'target' => '_blank'
            , 'href'   => Url::factory(
                $ProcessDb->getSourceUri() . '/../../processes/' . $DataObject->uuid,  [
                    'lang' => Elca::getInstance()->getLocale(),
                    'version' => $DataObject->version
                ]
            )
        ];

        $Elt->appendChild($Factory->getA($aAttr, t('Datenblatt')));

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Elt, $DataObject, $name);

        return $Elt;
    }
    // End build

}
