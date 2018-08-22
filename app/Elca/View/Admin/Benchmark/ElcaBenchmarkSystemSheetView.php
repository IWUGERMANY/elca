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

use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\View\ElcaSheetView;

/**
 * Builds a benchmark system sheet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaBenchmarkSystemSheetView extends ElcaSheetView
{
    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-benchmark-system-sheet');

        $this->addFunction('edit', '/elca/admin/benchmarks/systems/?id=$$itemId$$', t('Bearbeiten'), 'default page');
        $this->addFunction('copy', '/elca/admin/benchmarks/copySystem/?id=$$itemId$$', t('Kopieren'));
        $this->addFunction('delete', '/elca/admin/benchmarks/deleteSystem/?id=$$itemId$$', t('Löschen'));

        /**
         * Append individual content
         */
        $this->addDescription($this->get('description'));

        /**
         * Add version info
         */
        $versions = ElcaBenchmarkVersionSet::find(['benchmark_system_id' => $this->get('itemId')])->getArrayBy('name');
        $this->addInfo(join(', ', $versions), t('Versionen'));
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaBenchmarkSystemSheetView
