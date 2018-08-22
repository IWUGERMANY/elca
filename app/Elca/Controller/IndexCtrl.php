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
namespace Elca\Controller;

use Beibob\Blibs\Log;
use Beibob\Blibs\UserStore;
use Elca\Elca;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaIndexView;
use Elca\View\ElcaVersionsView;

/**
 * Main index controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class IndexCtrl extends AppCtrl
{
    /**
     *
     */
    protected function defaultAction()
    {
        if($this->hasBaseView())
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);

        if($this->isAjax())
            return;

        $View = $this->setView(new ElcaIndexView());
        $View->assign('username', UserStore::getInstance()->getUser()->getIdentifier());

        $Config = $this->FrontController->getConfig();
        $baseDir = $Config->toDir('baseDir');
        $notesFilepath = $baseDir . Elca::DOCS_FILEPATH . sprintf(Elca::MD_NOTES_FILENAME_PATTERN, Elca::getInstance()->getLocale());

        if(file_exists($notesFilepath)) {
            $View->assign('notes', join("\n", file($notesFilepath)));
        }
        else
            Log::getInstance()->debug('"' . $notesFilepath . '" not found');
    }
    // End defaultAction


    /**
     * History
     */
    protected function versionsAction()
    {
        if($this->isAjax())
            return;

        $View = $this->setView(new ElcaVersionsView());
        $Config = $this->FrontController->getConfig();
        $baseDir = $Config->toDir('baseDir');
        $filePath = $baseDir . Elca::MD_HISTORY_FILEPATH . Elca::MD_HISTORY_FILENAME;

        if(file_exists($filePath)) {
            $View->assign('history', join("\n", file($filePath)));
        }
    }
    // End versionsAction

}
// End IndexCtrl
