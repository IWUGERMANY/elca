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
namespace Elca\View\Admin\Benchmark;

use Beibob\Blibs\HtmlView;
use Elca\Db\ElcaBenchmarkSystem;
use Elca\Db\ElcaBenchmarkSystemSet;

/**
 * Admin view
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaAdminBenchmarkSystemsView extends HtmlView
{
    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_admin_benchmark_systems', 'elca');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @internal param $ -
     * @return void -
     */
    protected function beforeRender()
    {
        $Systems = ElcaBenchmarkSystemSet::find(null, ['name' => 'ASC']);

        if(!count($Systems))
            return;

        $NoEntriesElt = $this->getElementById('no-entries');
        $NoEntriesElt->parentNode->removeChild($NoEntriesElt);

        $Ul = $this->getElementById('elcaBenchmarkSystems')->appendChild($this->getUl());
        /** @var ElcaBenchmarkSystem $System */
        foreach($Systems as $System)
        {
            $Li = $Ul->appendChild($this->getLi(['id' => 'system-' . $System->getId() ]));

            $Include = $Li->appendChild($this->createElement('include'));
            $Include->setAttribute('name', 'Elca\View\Admin\Benchmark\ElcaBenchmarkSystemSheetView');
            $Include->setAttribute('itemId', $System->getId());
            $Include->setAttribute('headline', $System->getName());
            $Include->setAttribute('description', $System->getDescription());
        }

    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaAdminBenchmarkSystemsView
