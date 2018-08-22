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
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\Db\ElcaElement;

/**
 * Builds a link to the element selector
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaHtmlElementSelectorLink extends HtmlFormElement
{
    /**
     * Parameters
     */
    private $relId;
    private $elementTypeNodeId;
    private $position;
    private $context;
    private $refUnit;
    private $buildMode;
    private $url;


    /**
     * Sets the context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    // End setContent

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    // End setContext


    /**
     * Sets the buildMode
     */
    public function setBuildMode($buildMode)
    {
        $this->buildMode = $buildMode;
    }
    // End setBuildMode


    /**
     * Sets the relId
     */
    public function setRelId($relId)
    {
        $this->relId = $relId;
    }
    // End setRelId


    /**
     * Sets the processCategoryNodeId
     */
    public function setElementTypeNodeId($nodeId)
    {
        $this->elementTypeNodeId = $nodeId;
    }
    // End setElementTypeNodeId


    /**
     * Sets the position
     */
    public function setPosition($pos)
    {
        $this->position = $pos;
    }


    /**
     * @param mixed $refUnit
     */
    public function setRefUnit($refUnit)
    {
        $this->refUnit = $refUnit;
    }
    // End setPosition


    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        if ($elementId = $this->getConvertedTextValue()) {
            $Element = ElcaElement::findById($elementId);
            $elementName = $Element->getName();
            $this->elementTypeNodeId = $Element->getElementTypeNodeId();
        } else
            $elementName = $this->isReadonly() ? '-' : t('auswählen');

        $args = [];
        $args['e'] = $elementId;

        if ($this->relId)
            $args['relId'] = $this->relId;

        if ($this->elementTypeNodeId)
            $args['t'] = $this->elementTypeNodeId;

        if ($this->position)
            $args['pos'] = $this->position;

        if ($this->buildMode)
            $args['b'] = $this->buildMode;

        if ($this->refUnit)
            $args['u'] = $this->refUnit;

        if ($this->url) {
            $args['context'] = $this->context;
            $href = Url::factory($this->url, $args);
        } else
            $href = Url::factory('/' . $this->context . '/selectElement/', $args);

        $Factory = new HtmlDOMFactory($Document);

        if ($this->isReadonly()) {
            $A = $Factory->getSpan($elementName);

            if (!$this->isDisabled()) {
                $A->appendChild($Factory->getHiddenInput($this->getName(), $elementId));
            }
        } else {
            $aAttr = ['href'  => $href,
                      'title' => $elementName,
                      'rel'   => 'open-modal'];

            $A = $Factory->getA($aAttr, $elementName);
            $A->appendChild($Factory->getHiddenInput($this->getName(), $elementId));
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($A, $this->getDataObject(), $this->getName());

        foreach ($this->getChildren() as $Child)
            $Child->appendTo($A);

        return $A;
    }
    // End build
}
// End ElcaHtmlElementSelectorLink
