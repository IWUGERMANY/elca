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

declare(strict_types = 1);
namespace Elca\Service;

use Beibob\Blibs\Environment;
use Beibob\Blibs\Url;

class UrlGenerator
{
    /**
     * @var \Beibob\Blibs\Interfaces\Router
     */
    private $router;

    /**
     * ProjectAccessTokenMailer constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $actionControllerName
     * @param null   $action
     * @param array  $query
     * @return Url
     */
    public function urlTo($actionControllerName, $action = null, array $query = null)
    {
        return Url::parse($this->router->getUrlTo($actionControllerName, $action, $query));
    }

    /**
     * @param string $actionControllerName
     * @param null   $action
     * @param array  $query
     * @return Url
     */
    public function absoluteUrlTo($actionControllerName, $action = null, array $query = null)
    {
        $url = Url::parse($this->router->getUrlTo($actionControllerName, $action, $query));
        $url->setHostname(Environment::getServerHostName());
        $url->setProtocol(Environment::sslActive() ? 'https' : 'http');

        return $url;
    }
}
