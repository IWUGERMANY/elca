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
class ElcaHtmlTemplateElementSelectorLink extends HtmlFormElement
{
    /**
     * Parameters
     */
    private $projectVariantId;
    private $elementTypeNodeId;
    private $url;
    private $relId;

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sets the relId
     */
    public function setProjectVariantId($projectVariantId)
    {
        $this->projectVariantId = $projectVariantId;
    }

    /**
     * Sets the processCategoryNodeId
     */
    public function setElementTypeNodeId($nodeId)
    {
        $this->elementTypeNodeId = $nodeId;
    }

    public function setRelId($relId): void
    {
        $this->relId = $relId;
    }

    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        if ($elementId = $this->getConvertedTextValue()) {
            $element = ElcaElement::findById($elementId);
            $elementName = $element->getName();
            $this->elementTypeNodeId = $element->getElementTypeNodeId();
        } else
            $elementName = $this->isReadonly() ? '-' : t('auswählen');

        $args = [];
        $args['e'] = $elementId;

        if ($this->projectVariantId)
            $args['projectVariantId'] = $this->projectVariantId;

        if ($this->elementTypeNodeId)
            $args['t'] = $this->elementTypeNodeId;

        if ($this->relId)
            $args['relId'] = $this->relId;

        $href = Url::factory($this->url, $args);

        $factory = new HtmlDOMFactory($Document);

        if ($this->isReadonly()) {
            $aElt = $factory->getSpan($elementName);

            if (!$this->isDisabled()) {
                $aElt->appendChild($factory->getHiddenInput($this->getName(), $elementId));
            }
        } else {
            $aAttr = ['href'  => $href,
                      'title' => $elementName,
                      'rel'   => 'open-modal'];

            $aElt = $factory->getA($aAttr, $elementName);
            $aElt->appendChild($factory->getHiddenInput($this->getName(), $elementId));
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($aElt, $this->getDataObject(), $this->getName());

        foreach ($this->getChildren() as $child)
            $child->appendTo($aElt);

        return $aElt;
    }
}
