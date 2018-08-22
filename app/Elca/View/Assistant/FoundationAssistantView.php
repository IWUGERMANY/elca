<?php declare(strict_types=1);
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

use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlImage;
use Beibob\HtmlTools\HtmlTag;

class FoundationAssistantView extends PillarAssistantView
{
    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->allowedShapes = ['rectangular'];
    }

    /**
     * @param HtmlForm $form
     */
    protected function appendElementImage(HtmlForm $form)
    {
        $baseUrl = FoundationAssistantImageView::IMG_DIRECTORY;

        $container = $form->add(new HtmlTag('div', null, ['class' => 'type-images']));
        $container->add(new HtmlImage($baseUrl . 'rectangular.png'))->addClass('rectangular');
    }
}
