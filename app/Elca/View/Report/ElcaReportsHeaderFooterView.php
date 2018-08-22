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
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;

/**
 * Builds the asset report for construction elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaReportsHeaderFooterView extends HtmlView
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

        $this->setTplName('elca_reports_header_footer', 'elca');

        $projectVariantId = $this->get('projectVariantId', Elca::getInstance()->getProjectVariantId());

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_BOTH);

        $projectVariant = ElcaProjectVariant::findById($projectVariantId);
        $project = $projectVariant->getProject();

        $this->Project = $this->assign('Project', $project);
        $this->ProjectVariant = $this->assign('ProjectVariant', $projectVariant);

        $FooterInfo = $this->assign('Footer', new \stdClass());
        $FooterInfo->elcaVersion = Elca::VERSION_BBSR;
        $FooterInfo->processDb = $project->getProcessDb()->getName();
        $FooterInfo->date = date('d.m.Y');
        $this->assign('date', date('d.m.Y'));
        $FooterInfo->editor = $project->getEditor()? $project->getEditor() : UserStore::getInstance()->getUser()->getFullname();
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
                $footer = $this->getElementById('printFooter', true);
                $footer->parentNode->removeChild($footer);
                break;

            case self::BUILDMODE_FOOTER:
                $header = $this->getElementById('printHeader', true);
                $header->parentNode->removeChild($header);
                break;

        }
    }
}
// End ElcaReportsHeaderFooterView