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

namespace Elca\View\Modal;

use Beibob\Blibs\HtmlView;
use Beibob\Blibs\XmlDocument;

/**
 * ModalElementImage
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ModalElementImage extends HtmlView
{
    /**
     * @var XmlDocument
     */
    private $view;

    /**
     * @param XmlDocument $view
     */
    public function setElementImageView(XmlDocument $view)
    {
        $this->view = $view;
    }
    // End setElementImageView

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('elca_modal_element_image');
    }
    // End init

    /**
     *
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('elca-modal-content');

        if (!$this->view) {
            $includeElt = $container->appendChild($this->createElement('include'));
            $includeElt->setAttribute('name', 'Elca\View\ElcaElementImageView');
            $includeElt->setAttribute('elementId', '$$elementId$$');
            $includeElt->setAttribute('width', '$$width$$');
            $includeElt->setAttribute('height', '$$height$$');
            $includeElt->setAttribute('showTotalSize', '$$showTotalSize$$');
        } else {

            $this->view->assign('elementId', $this->get('elementId'));
            $this->view->assign('width', $this->get('width'));
            $this->view->assign('height', $this->get('height'));
            $this->view->assign('showTotalSize', $this->get('showTotalSize', false));
            $this->view->process();

            $container->appendChild($this->importNode($this->view->getContentNode(), true));
        }
    }
    // End beforeRender
}
// End ModalElementImage
