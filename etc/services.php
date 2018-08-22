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

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\Interfaces\Logger;
use Beibob\Blibs\Log;
use Beibob\Blibs\Session;
use DI\Container;
use Elca\Service\Messages\FlashMessages;

/**
 * @todo split into multiple definition files
 */

return [
    Environment::class => DI\factory(
        function () {
            return Environment::getInstance();
        }
    ),

    DbHandle::class => DI\factory(
        function () {
            return DbHandle::getInstance();
        }
    ),

    Log::class    => DI\factory(
        function () {
            return Log::getInstance();
        }
    ),
    Logger::class => DI\get(Log::class),

    FlashMessages::class    => DI\factory(
        function (Container $container) {
            return new FlashMessages(
                $container->get(Environment::class)
                    ->getSession()
                    ->getNamespace('_flashes', Session::SCOPE_PERSISTENT)
            );
        }
    ),
];
