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
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlDataElement;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\ElcaNumberFormat;
use Verraes\ClassFunctions\ClassFunctions;

/**
 * This class implements a label decorator for eLCA form elements
 *
 * The generated HTML looks something like that:
 *
 * <code>
 * <div>
 *   <div class="label-holder">
 *      <label>Label <span class="required">*</span> <strong>unit</strong></label>
 *   </div>
 *   <div class="element-holder">
 *      content
 *   </div>
 * </div>
 * </code>
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlFormElementLabel extends HtmlElement
{
    /**
     * The label content for the element
     */
    private $label;

    /**
     * Marks the element as required
     */
    private $isRequired;

    /**
     * The unit component
     */
    private $unit;

    /**
     * Title of the decorator
     */
    private $title;

    /**
     * Creates a new element
     *
     * @param string $label the label text for the element
     * @param HtmlElement $DataElement the content element
     */
    public function __construct($label, HtmlElement $element = null, $isRequired = null, $unit = null, $title = null)
    {
        $this->label = $label;
        $this->isRequired = $isRequired;
        $this->unit = $unit;
        $this->title = $title;

        if (null !== $element)
            $this->addChild($element);
    }
    // End __construct


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        if(!$Document instanceof HtmlView)
            $Document = HtmlDOMFactory::factory($Document);

        if (!$FirstFormElement = HtmlFormElement::searchFirstFormElementChild($this)) {
            $FirstFormElement = HtmlDataElement::searchFirstDataElementChild($this);
        }

        $DivElt = $Document->getDiv();
        $this->buildAndSetAttributes($DivElt);

        if($FirstFormElement)
        {
            $error = '';
            if ($FirstFormElement instanceof HtmlFormElement && $FirstFormElement->hasError()) {
                $error = ' error';
            }

            $formEltName = $FirstFormElement->getName();

            if($pos = strpos($formEltName, '['))
                $formEltName = \utf8_substr($formEltName, 0, $pos);

            $Document->addClass($DivElt, 'form-section ' . ClassFunctions::short($FirstFormElement) . '-section ' . $formEltName . $error);
        }
        else {
            $Document->addClass($DivElt, 'form-section');
        }

        if($this->label)
        {
            $LabelDiv = $DivElt->appendChild($Document->getDiv(['class' => 'label-holder']));
            $Label = $LabelDiv->appendChild($Document->getLabel());

            /**
             * Connect label and first form element by id
             */
            if($FirstFormElement)
                $Label->setAttribute('for', $FirstFormElement->getId(true));

            $Label->appendChild(
                $Document->getSpan(ucfirst($this->label), ['class' => 'label-content'])
            );

            if($this->isRequired)
                $Label->appendChild($Document->getSpan('*', ['class' => 'required']));

            if(is_string($this->unit))
                $Label->appendChild($Document->getStrong(ElcaNumberFormat::formatUnit($this->unit), ['class' => 'label-unit']));

            if($this->title)
            {
                $Label->setAttribute('title', $this->title);
                $Document->addClass($Label, 'help');
            }
        }

        $EltDiv = $DivElt->appendChild($Document->getDiv(['class' => 'form-elt element-div']));

        foreach($this->getChildren() as $Child)
        {
            $Child->appendTo($EltDiv);
            $Document->addClass($EltDiv, ClassFunctions::short($Child));
        }

        return $DivElt;
    }
    // End build
}
// End ElcaHtmlFormElementLabel
