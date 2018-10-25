<?php
/**
 * This file is part of blibs - mvc development framework
 * 
 * Copyright (c) 2013 Tobias Lode <tobias@beibob.de>
 *                    Fabian Möller <fab@beibob.de>
 *                    BEIBOB Medienfreunde GbR - http://beibob.de/
 * 
 * blibs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * blibs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with blibs. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Elca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlMultiSelectbox;
use DOMDocument;

/**
 * Implements a HTML select box container.
 *
 * Usage:
 *
 * <code>
 *   $SelectBox = $Form->addChild(new HtmlFormElementLabel('Label', new HtmlSelectBox('property')));
 *   $SelectBox->add(new HtmlSelectOption('name', 'value'));
 *   $SelectBox->add(new HtmlSelectOption('name2', 'value2'));
 * </code>
 *
 * @package blibs
 * @author Thorsten Mürell <thorsten.muerell@beibob.net>
 *
 */
class Selectbox extends \Beibob\HtmlTools\HtmlSelectbox
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $name = $this->getName();

        $factory = new HtmlDOMFactory($Document);
        $container = $factory->getDiv(['class' => 'selectbox-holder '. $this->getAttribute('class') .'-select']);
        $selectElt = $container->appendChild($Document->createElement('select'));

        if ($this instanceof HtmlMultiSelectbox) {
            $name .= '[]';
        }

        $selectElt->setAttribute('name', $name);

        if ($this->isReadonly() || $this->isDisabled()) {
            $selectElt->setAttribute('disabled', 'disabled');
            $container->appendChild($factory->getHiddenInput($this->getName(), $this->getConvertedTextValue()));
        }

        foreach ($this->getChildren() as $Child)
            $Child->appendTo($selectElt);

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($selectElt, $this->getDataObject(), $this->getName());

        return $container;
    }
}
