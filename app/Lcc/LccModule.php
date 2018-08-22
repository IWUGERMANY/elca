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
namespace Lcc;

use Beibob\Blibs\CssLoader;
use Beibob\Blibs\Environment;
use Beibob\Blibs\JsLoader;
use Elca\Elca;
use Elca\Model\ElcaModuleInterface;
use Elca\Security\ElcaAccess;
use Elca\Service\Event\EventPublisher;
use Lcc\Model\Navigation\LccNavigation;
use Lcc\Model\Processing\LccProcessingObserver;

/**
 * LccModule
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccModule implements ElcaModuleInterface
{
    /**
     * Module name
     */
    const MODULE_NAME = 'LCC';

    /**
     * Role ident
     */
    const ROLE = 'LCC';

    /**
     * Calculation method
     */
    const CALC_METHOD_GENERAL = 0;
    const CALC_METHOD_DETAILED = 1;
    const ATTRIBUTE_IDENT_LCC_BENCHMARK_COMMENT = 'lcc.benchmark.comment';


    /**
     * Module init method
     *
     * Will be called on startup
     */
    public function init()
    {
        /**
         * Register additional navigations
         */
        Elca::getInstance()->registerAdditionalNavigation(new LccNavigation(ElcaAccess::getInstance()));

        /**
         * Register event handler for domestic water updates
         */
        $container = Environment::getInstance()->getContainer();
        $container->get(EventPublisher::class)
                  ->subscribe(
                       $container->get(LccProcessingObserver::class)
                   );
        
        /**
         * Load additional css and js files
         */
        CssLoader::getInstance()->register('lcc', 'lcc.css?'.Elca::VERSION);
        JsLoader::getInstance()->register('lcc', 'lcc.min.js?'.Elca::VERSION);
    }
    // End init


    /**
    * Should return a short descriptive name for the module
    */
    public function getName()
    {
        return self::MODULE_NAME;
    }
    // End getName

}
// End LccModule
