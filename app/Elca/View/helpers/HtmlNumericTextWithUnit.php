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
 * Numeric input text element
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class HtmlNumericTextWithUnit extends HtmlText
{
    /**
     * @var string
     */
    private $unit;

    private $nullDisplayValue;

    /**
     * Creates a text element
     *
     * @param string $name
     * @param string $unit
     * @param Converter $DefaultConverter
     * @param DbObject $DataObject
     * @param null $precision
     * @param bool $isPercentage
     * @param string $decPoint
     * @param bool $inScientific
     */
    public function __construct(
        $name,
        $unit,
        Converter $DefaultConverter = null,
        DbObject $DataObject = null,
        $precision = null,
        $isPercentage = false,
        $decPoint = ',',
        $inScientific = false
    ) {
        if (is_null($DefaultConverter)) {
            $DefaultConverter = new ElcaNumberFormatConverter($precision, $isPercentage, $decPoint, $inScientific);
        }

        $this->unit = $unit;

        parent::__construct($name, $DefaultConverter, $DataObject);
    }

    public function setNullDisplayValue(?string $nullDisplayValue): void
    {
        $this->nullDisplayValue = $nullDisplayValue;
    }

    /**
     * Builds this element
     *
     * @param DOMDocument $Document
     * @return \DOMNode|\DOMText
     */
    public function build(DOMDocument $Document)
    {
        $factory = HtmlDOMFactory::factory($Document);
        $span    = $factory->getSpan();

        $convertedTextValue = $this->getConvertedTextValue();

        if (empty($convertedTextValue)) {
            $convertedTextValue = $this->nullDisplayValue;
        }

        $span->appendChild($factory->getSpan($convertedTextValue, ['class' => 'value']));

        if ($this->unit) {
            $span->appendChild($factory->getText(' '));
            $span->appendChild(
                $factory->getSpan(t(ElcaNumberFormat::formatUnit($this->unit)), ['class' => 'unit'])
            );
        }

        $this->buildAndSetAttributes($span);

        return $span;
    }
}
