<?php declare(strict_types=1);
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

namespace Elca\Controller\Admin;

use Elca\Db\ElcaBenchmarkVersion;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaAdminNavigationLeftView;

trait BenchmarksCtrlTrait
{
    /**
     * @param ElcaBenchmarkVersion $BenchmarkVersion
     */
    private function addBenchmarkVersionOsit(ElcaBenchmarkVersion $BenchmarkVersion)
    {
        $this->Osit->add(new ElcaOsitItem(t('Systeme'), '/elca/admin/benchmarks/systems/', t('Benchmarks')));
        $this->Osit->add(
            new ElcaOsitItem(
                $BenchmarkVersion->getBenchmarkSystem()->getName(),
                '/elca/admin/benchmarks/systems/?id='.$BenchmarkVersion->getBenchmarkSystemId(),
                t('Benchmarksystem')
            )
        );
        $this->Osit->add(new ElcaOsitItem($BenchmarkVersion->getName(), null, t('Benchmarkversion')));
    }

    /**
     * Adds the navigation view
     *
     * @return void
     */
    private function addNavigationView($action = null)
    {
        // set active controller in navigation
        $NavView = $this->addView(new ElcaAdminNavigationLeftView());
        $NavView->assign('activeCtrlName', $this->ident());
        $NavView->assign('activeCtrlAction', $action ? $action : $this->getAction());
    }
}
