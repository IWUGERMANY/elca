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
namespace Soda4Lca\View\helpers;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\Environment;
use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlDataElement;
use Beibob\HtmlTools\Interfaces\Converter;
use DOMDocument;
use Soda4Lca\Model\Import\Soda4LcaConnector;

/**
 * Builds a report status element
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soda4LcaHtmlReportProcessLink extends HtmlDataElement
{
    /**
     * Base url
     */
    private $baseUrl;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a new HtmlDataObject
     *
     * If no default converter is given or null, a standard ObjectPropertyConverter is added
     * to the chain.
     *
     * @param string $name                   the name of the object (also known as property)
     * @param Converter $defaultConverter    the default converter to be used
     * @param DbObject $DataObject           the data object to use
     */
    public function __construct($name, Converter $defaultConverter = null, DbObject $DataObject = null)
    {
        parent::__construct($name, $defaultConverter, $DataObject);

        $Config = Environment::getInstance()->getConfig();
        $this->baseUrl = $Config->elca->soda4Lca->toDir('baseUrl');
        $this->baseUrl .= Soda4LcaConnector::PROCESSES .'/';
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $DataObject = $this->getDataObject();
        $name = $this->getName();
        $value = $DataObject->$name;
        
        $uuid = $DataObject->uuid;
        $url = $this->baseUrl . $DataObject->uuid;

        $Factory = new HtmlDOMFactory($Document);
        $A = $Factory->getA(['href' => $url, 'target' => '_blank', 'class' => 'no-xhr'], $value);

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($A, $DataObject, $this->getName());

        return $A;
    }
    // End build

    //////////////////////////////////////////////////////////////////////////////////////
}
// End Soda4LcaHtmlReportStatus
