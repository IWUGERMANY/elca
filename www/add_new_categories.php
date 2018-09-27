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
$loader = require_once '../vendor/autoload.php';

use Beibob\Blibs\Bootstrap;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\NestedNode;
use Elca\Db\ElcaProcessCategory;

/**
 * Bootstrap the environment
 */
$baseDir = dirname( __DIR__);
$bootstrap = new Bootstrap($baseDir);

/**
 * Init elca modules
 */
$elca = $bootstrap->getEnvironment()->getContainer()->get('\Elca\Elca');
$elca->initModules();

$Dbh = DbHandle::getInstance();

try
{
    $Dbh->begin();

    $Node = NestedNode::createAsChildOf(ElcaProcessCategory::findRoot()->getNode(), '10');
    ElcaProcessCategory::create($Node->getId(), 'Komposite', '10');

    $Node = NestedNode::createAsChildOf(ElcaProcessCategory::findRoot()->getNode(), '100');
    ElcaProcessCategory::create($Node->getId(), 'End of Life', '100');

    $Dbh->commit();
}
catch(Exception $Exception)
{
    $Dbh->rollback();
    throw $Exception;
}