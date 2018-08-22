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
namespace Elca\View;

use Beibob\Blibs\HtmlView;
use Elca\Model\Navigation\ElcaOsit;

/**
 * Builds the osit view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaOsitView extends HtmlView
{
    /**
     * Builds the content navigation to the left
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'osit']));

        $Osit = ElcaOsit::getInstance();

        $items = $Osit->getItems();

        if(!$items)
            return;

        $Ul = $Container->appendChild($this->getUl());

        $numItems = count($items);
        $index = 1;
        foreach($items as $url => $Item)
        {
            $Li = $Ul->appendChild($this->getLi(['class' => $Item->getCssClass()]));

            if($numItems === $index)
            {
                $Li->setAttribute('class', trim($Li->getAttribute('class').' active'));
                $Span = $Li->appendChild($this->getSpan($Item->getCaption()));

                if($context = $Item->getContext())
                    $Span->appendChild($this->getSpan($context, ['class' => 'osit-context']));
            }
            else
            {
                $linkAttr = ['href' => $url,
                                  'class' => 'page'];

                if($Item->hasNoPageLink())
                    unset($linkAttr['class']);

                $A = $Li->appendChild($this->getA($linkAttr, $Item->getCaption()));

                if($context = $Item->getContext())
                    $A->appendChild($this->getSpan($context, ['class' => 'osit-context']));

                $A->appendChild($this->getSpan(t('zurück'), ['class' => 'back-hint']));

            }
            $index++;
        }
    }
    // End beforeRender
}
// End EcoOsitView
