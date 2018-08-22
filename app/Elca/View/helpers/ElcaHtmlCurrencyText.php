<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Elca\View\helpers;

use Beibob\Blibs\DbObject;
use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\Interfaces\Converter;
use DOMDocument;
use Elca\ElcaNumberFormat;

/**
 * Numeric input text element in currency style
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fabian@beibob.de>
 *
 */
class ElcaHtmlCurrencyText extends HtmlText
{
    /**
     * @var string
     */
    private $unit;

    /**
     * Creates a text element
     *
     * @param string    $name
     * @param string    $currencySymbol
     * @param Converter $DefaultConverter
     * @param DbObject  $DataObject
     * @param string    $decPoint
     * @param string    $thousendSep
     */
    public function __construct($name, $currencySymbol = '€', Converter $DefaultConverter = null, DbObject $DataObject = null, $decPoint = '?', $thousendSep = '?')
    {
        if(is_null($DefaultConverter))
            $DefaultConverter = new ElcaCurrencyConverter($decPoint, $thousendSep, $currencySymbol);

        parent::__construct($name, $DefaultConverter, $DataObject);
    }
    // End __construct


    /**
     * Builds this element
     *
     * @param DOMDocument $Document
     * @return \DOMNode|\DOMText
     */
    public function build(DOMDocument $Document)
    {
        $factory = HtmlDOMFactory::factory($Document);
        $neg = $this->getConvertedTextValue() < 0 ? 'neg' : 'pos';
        $span = $factory->getSpan($this->getConvertedTextValue(), ['class' => 'value ' . $neg]);

        $this->buildAndSetAttributes($span);

        return $span;
    }
    // End build
}
// End ElcaHtmlCurrencyText
