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

use Beibob\Blibs\Environment;
use Beibob\Blibs\HtmlView;
use Elca\Db\ElcaProject;

/**
 * Elca base view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 */
class ElcaBaseView extends HtmlView
{
    /**
     * sidebars
     */
    const SIDEBAR_LEFT = 1;

    /**
     * Contexts
     */
    const CONTEXT_PROJECTS  = 'projects';
    const CONTEXT_ELEMENTS  = 'elements';
    const CONTEXT_PROCESSES = 'processes';
    const CONTEXT_USERS     = 'users';

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * flag for disabling sidebars
     */
    private $disabledSidebars = [];

    /**
     * Context
     */
    private $context;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Constructs the Document
     *
     * @param  string $xmlName
     * @return -
     */
    public function __construct()
    {
        parent::__construct('elca_base', 'elca');
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Sets the current context, which is used as css class on the main div
     *
     * @param  string $context
     * @return -
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
    // End setContext

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * sets the flag to disable a specific sidebar
     *
     * @param  string $side
     * @return -
     */
    public function disableSidebar($sidebar = null)
    {
        switch($sidebar)
        {
            default:
            case self::SIDEBAR_LEFT:
                $this->disabledSidebars[] = self::SIDEBAR_LEFT;
                break;
        }
    }
    // End disableSidebar

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * sets the headline (above the content)
     *
     * @param  string $headline
     * @return -
     */
    public function setProject(ElcaProject $Project)
    {
        $this->assign('Project', $Project);
    }
    // End setProject

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * callback before rendering the view
     *
     * @param  string $side
     * @return -
     */
    public function beforeRender()
    {
        /**
         * sidebar management
         */
        foreach ($this->disabledSidebars as $sidebar)
        {
            switch($sidebar)
            {
                case self::SIDEBAR_LEFT:
                    $Sidebar = $this->getElementById('navLeft');
                    $this->getElementById('main')->setAttribute('class', 'full');
                    break;
                default:
                    $Sidebar = null;
                    break;
            }
            if (!is_null($Sidebar))
                $Sidebar->parentNode->removeChild($Sidebar);
        }

        $this->assign('context', $this->context);

        /** Enable or disable the higlighting of missing translations **/
        $this->assign('highlightMissingTranslations', '');
        if (Environment::getInstance()->getConfig()->translate->highlightMissingTranslations)
            $this->assign('highlightMissingTranslations', 'hl-mi-tr');
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaBaseView
