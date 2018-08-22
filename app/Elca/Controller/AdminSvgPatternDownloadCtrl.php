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

use Beibob\Blibs\AjaxController;
use Beibob\Blibs\MediaView;
use Elca\Db\ElcaSvgPattern;

/**
 * Admin svg patterns
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class AdminSvgPatternDownloadCtrl extends AjaxController
{
    /**
     * default action
     */
    protected function defaultAction()
    {
        if($this->isAjax())
            return;

        $patternId = $this->getAction();

        if (!is_numeric($patternId))
            return;

        $Pattern = ElcaSvgPattern::findById($patternId);
        $Media = $Pattern->getImage();

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader('Expires: ');
        $this->Response->setHeader("Content-Disposition: attachment; filename=\"". $Media->getName() ."\"");
        $this->Response->setHeader('Content-Type: application/octet-stream');

        /**
         * Set the view
         */
        $this->setView(new MediaView($Media));
    }
    // End defaultAction
}
// End AdminSvgPatternDownloadCtrl