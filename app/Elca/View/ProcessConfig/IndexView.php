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
namespace Elca\View\ProcessConfig;

use Beibob\Blibs\HtmlView;
use Elca\Db\ElcaProcessConfigSet;

/**
 * Builds a list of process config sheets
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class IndexView extends HtmlView
{
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('process_config/index', 'elca');
    }

    protected function beforeRender()
    {
        $content = $this->getElementById('lastModified', true);

        /**
         * Last modified ProcessConfigs
         */
        $processConfigs = ElcaProcessConfigSet::findLastModified();
        if ($processConfigs->count()) {
            $content->appendChild($this->getH3(t('Zuletzt von Ihnen bearbeitete Baustoffkonfigurationen'), ['class' => 'section']));
            $ul = $content->appendChild($this->getUl(['class' => 'last-modified']));
            foreach ($processConfigs as $ProcessConfig) {
                $li         = $ul->appendChild($this->getLi());
                $attributes = ['class' => 'page', 'href' => '/processes/'.$ProcessConfig->getId().'/'];
                $li->appendChild($this->getA($attributes, $ProcessConfig->getName()));
            }
        }
    }
}
