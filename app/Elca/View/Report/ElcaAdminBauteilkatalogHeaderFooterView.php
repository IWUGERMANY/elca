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
namespace Elca\View\Report;

use Beibob\Blibs\HtmlView;
use Beibob\Blibs\UserStore;
use DOMElement;
use Elca\Elca;

/**
 * Builds the asset report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaAdminBauteilkatalogHeaderFooterView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_BOTH = 'both';
    const BUILDMODE_HEADER  = 'header';
    const BUILDMODE_FOOTER  = 'footer';

    /**
     * Buildmode
     */
    private $buildMode;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('elca_admin_bauteilkatalog_header_footer', 'elca');
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_BOTH);

        $FooterInfo = $this->assign('Footer', new \stdClass());
        $FooterInfo->elcaVersion = Elca::VERSION_BBSR;
        $FooterInfo->date = date('d.m.Y');
        $this->assign('date', date('d.m.Y'));
        $FooterInfo->editor = "BBSR";

    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement $Container
     */
    protected function beforeRender()
    {
        parent::beforeRender();

        switch($this->buildMode) {
            case self::BUILDMODE_HEADER:
                $header = $this->getElementById('printHeader', true);
                $header->parentNode->removeChild($header);
                break;

            case self::BUILDMODE_FOOTER:
                $footer = $this->getElementById('printFooter', true);
                $footer->parentNode->removeChild($footer);
                break;

        }
    }
}
// End ElcaReportsHeaderFooterView