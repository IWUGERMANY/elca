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

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\HtmlView;
use Elca\Db\ElcaCacheIndicatorSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaWindowAssistantProblemProjectView;
use Elca\Elca;

/**
 *
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectView extends HtmlView
{

    /**
     * Active project
     */
    private $project;


    /**
     * Constructs the Document
     *
     * @param  string $xmlName
     *
     * @return -
     */
    public function __construct()
    {
        parent::__construct('elca_project');
    }
    // End __construct


    /**
     * Sets the active project
     *
     * @param  ElcaProject $project
     *
     * @return -
     */
    public function setProject(ElcaProject $project)
    {
        $this->project = $project;
    }
    // End setProject


    /**
     * Init
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if ($this->get('firstStep')) {
            $this->setTplName('elca_project_first_step');
        } else {
            $this->setTplName('elca_project');
        }
    }
    // End init

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $warningsElt = $this->getElementById('cacheWarning', true);
        if (!ElcaCacheIndicatorSet::countDuplicateTotals($this->project->getId())) {
            $warningsElt->parentNode->removeChild($warningsElt);
        }

        $windowAssistantProblemDiv = $this->getElementById('windowAssistantProblem', true);
        if (!$this->checkWindowAssistantProblem()) {
            $windowAssistantProblemDiv->parentNode->removeChild($windowAssistantProblemDiv);
        }
    }

    private function checkWindowAssistantProblem()
    {
        return ElcaWindowAssistantProblemProjectView::checkForProjectId(Elca::getInstance()->getProjectId());
    }
}
// End ElcaProjectView
