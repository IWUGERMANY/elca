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

use Elca\Db\ElcaSvgPattern;

/**
 * Builds a svg pattern sheet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaSvgPatternSheetView extends ElcaSheetView
{
    const PREVIEW_WIDTH = 60;
    const PREVIEW_HEIGHT = 60;

    /**
     * @var ElcaSvgPattern $SvgPattern
     */
    private $SvgPattern;

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        // itemId
        $this->SvgPattern = ElcaSvgPattern::findById($this->get('itemId'));
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-svg-pattern-sheet');

        $this->addFunction('edit', '/elca/admin-svg-patterns/edit/?id=$$itemId$$', t('Bearbeiten'), 'default page');
        $this->addFunction('delete', '/elca/admin-svg-patterns/delete/?id=$$itemId$$', t('Löschen'));

        /**
         * Append individual content
         */
        $this->addDescription($this->SvgPattern->getDescription(), true);

        $this->addInfo($this->SvgPattern->getWidth() . ' x ' . $this->SvgPattern->getHeight(), t('Format'), 'px', true);

        $usageCount = $this->SvgPattern->getUsageCount();
        $this->addInfo($usageCount > 1? $usageCount : 'einer', 'Verwendung in', $usageCount < 2? 'Kategorie oder Baustoffkonfiguration' : 'Kategorien und Baustoffkonfigurationen', false);

        $this->addInfo(
            $usageCount > 1? $usageCount : 'einer',
            t('Verwendung in'),
            $usageCount < 2? t('Kategorie oder Baustoffkonfiguration') : t('Kategorien und Baustoffkonfigurationen'), false);


        //$contain = $this->SvgPattern->getWidth() > self::PREVIEW_WIDTH || $this->SvgPattern->getHeight() > self::PREVIEW_HEIGHT ? ' contain' : 'size';
        $contain = $this->SvgPattern->getHeight() > $this->SvgPattern->getWidth()? 'x-size' : 'y-size';

        $src = $this->SvgPattern->hasImage() ? $this->SvgPattern->getImageUrl() : '/img/elca/blank.gif';
        $Preview = $this->getContentContainer()->appendChild($this->getDiv(['class' => 'pattern-preview '. $contain]));
        $Preview->setAttribute('style', 'background-image:url(' . $src . ')');
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaSvgPatternSheetView
