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

use Elca\Model\Indicator\IndicatorRepository;
use Elca\Model\Process\ProcessDbRepository;
use Elca\Model\ProcessConfig\Conversion\ProcessConversionsRepository;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigRepository;
use Elca\Model\Processing\LifeCycleUsage\LifeCycleUsageRepository;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Repositories\Indicator\DbIndicatorRepository;
use Elca\Repositories\Process\DbProcessDbRepository;
use Elca\Repositories\ProcessConfig\DbProcessConfigRepository;
use Elca\Repositories\ProcessConfig\DbProcessConversionsRepository;
use Elca\Repositories\ProcessConfig\DbProcessLifeCycleRepository;
use Elca\Repositories\Processing\DbLifeCycleUsageRepository;
use Elca\Repositories\Project\DbProjectAccessTokenRepository;

return [
    IndicatorRepository::class          => DI\get(DbIndicatorRepository::class),
    ProjectAccessTokenRepository::class => DI\get(DbProjectAccessTokenRepository::class),
    ProcessConfigRepository::class      => DI\get(DbProcessConfigRepository::class),
    ProcessLifeCycleRepository::class   => DI\get(DbProcessLifeCycleRepository::class),
    ProcessDbRepository::class          => DI\get(DbProcessDbRepository::class),
    LifeCycleUsageRepository::class     => DI\get(DbLifeCycleUsageRepository::class),
    ProcessConversionsRepository::class => DI\get(DbProcessConversionsRepository::class),
];

