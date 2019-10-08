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
use Beibob\Blibs\File;
use Beibob\Blibs\FileView;
use Beibob\Blibs\MimeType;
use Elca\Elca;

/**
 * Handles handbook export
 *
 * @package    elca
 * @author     Online Now! <boeneke@online-now.de>
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class HandbookCtrl extends AjaxController
{

    /**
     * Download action for the handbook
     */
    protected function defaultAction()
    {
        $filePath = Elca::getInstance()->getHandbookFilepath();

        if(!file_exists($filePath))
            throw new \Exception('Huups! The handbook file "' . $filePath . '" is missing!');

        $View = $this->addView(new FileView());
        $View->setFilePath($filePath);

        $this->Response->setHeader('Pragma: ');
        $this->Response->setHeader("Content-Disposition: inline; filename=\"". basename($filePath) ."\"");
        $this->Response->setHeader('Content-Type: '. MimeType::getByFilepath($filePath));
        $this->Response->setHeader('Content-Length: '. File::getFilesize($filePath));
    }

    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End isPublic
}
// End HandbookCtrl
