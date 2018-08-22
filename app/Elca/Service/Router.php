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
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HttpRouter;
use Beibob\Blibs\Interfaces\Request;

class Router implements \Beibob\Blibs\Interfaces\Router
{
    /**
     * @var Router
     */
    private $router;

    /**
     * Router constructor.
     *
     * @param FrontController $frontController
     * @param Environment     $environment
     */
    public function __construct(FrontController $frontController, Environment $environment)
    {
        $config = $environment->getConfig();

        $routerName = isset($config->mvc->router)? $config->mvc->router : HttpRouter::class;

        $this->router = new $routerName($frontController);
    }

    /**
     * Resolves and returns the name of the invoked ActionController,
     * the action and the query string by the given Request
     *
     * @param Request $Request
     * @return array(string, string, array)
     */
    public function resolve(Request $Request)
    {
        return $this->router->resolve($Request);
    }

    /**
     * Build the url to the given name of an ActionController,
     * an optional action and optional a query string
     *
     * @param string $actionControllerName
     * @param string $action
     * @param array  $query
     * @return string
     */
    public function getUrlTo($actionControllerName, $action = null, array $query = null)
    {
        return $this->router->getUrlTo($actionControllerName, $action, $query);
    }

    /**
     * Returns the last resolved name of an action controller
     *
     * @return string
     */
    public function getActionController()
    {
        return $this->router->getActionController();
    }

    /**
     * Returns the last resolved action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->router->getAction();

    }

    /**
     * Returns the last resolved request query
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->router->getQuery();
    }
}
