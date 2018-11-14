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

use Beibob\Blibs\HtmlView;
use Elca\View\ElcaBaseView;

/**
 * Privacy controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 *
 */
class PrivacyCtrl extends AppCtrl
{
    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->Elca->unsetProjectId();

        if ($this->isAjax()) {
            $this->setView(new HtmlView('i18n/privacy_' . $this->Elca->getLocale(), 'elca'));
        }

        if($this->hasBaseView()) {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
        }
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End isPublic
}
