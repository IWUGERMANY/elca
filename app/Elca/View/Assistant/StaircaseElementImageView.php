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
namespace Elca\View\Assistant;

use Beibob\Blibs\Environment;
use Elca\Db\ElcaElement;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\View\DefaultElementImageView;

/**
 * ElementImage View
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class StaircaseElementImageView extends DefaultElementImageView
{
    const IMG_DIRECTORY = '/img/elca/assistant/stairs/';


    /**
     * Callback triggered after rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $element = ElcaElement::findById($this->elementId);

        if ($element->isComposite()) {
            /**
             * @var StaircaseAssistant $assistant
             */
            $assistant = Environment::getInstance()->getContainer()->get('Elca\Service\Assistant\Stairs\StaircaseAssistant');
            $staircase = $assistant->getStaircaseFromElement($this->elementId);

            $v = 1.1;
            $svg = $this->appendChild($this->getSvg(['height' => '100%',
                                                     'width' => '100%',
                                                     'viewBox' => '-10 -10 '. ($this->canvasWidth - 10) * $v .' '. ($this->canvasHeight - 10) * $v
            ]));



            $type = $staircase->getType();
            $url = self::IMG_DIRECTORY . $type .'.png';
            $svg->appendChild($this->getImage(0, 0, $this->canvasWidth, $this->canvasHeight, $url));

        } else {
            parent::beforeRender();
        }
    }
}
// End ElcaElementImageView
