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
namespace Elca\Security;

use Beibob\Blibs\Auth;
use Beibob\Blibs\Environment;
use Beibob\Blibs\ForwardException;
use Beibob\Blibs\HttpRouter;
use Beibob\Blibs\Interfaces\Filter;
use Beibob\Blibs\Interfaces\Request;
use Beibob\Blibs\Interfaces\Response;
use Beibob\Blibs\RequestBase;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Elca\Controller\AppCtrl;
use Elca\Elca;
use Elca\Service\ElcaSessionRecovery;

/**
 * Filter for handling authentication
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaAuthFilter implements Filter
{
    /**
     *  The filter function, that sets the content to the login form
     *
     *  @param Request $Request
     *  @param Response $Response
     */
    public function filter(Request $Request, Response $Response, HttpRouter $Router = null)
    {
        if (!is_null($Router) &&
            !UserStore::getInstance()->hasUser())
        {
            if (!call_user_func([$Router->getInvokedControllerName(), AppCtrl::IS_PUBLIC])
                && !$this->checkCredentials($Request)
                )
            {
                /**
                 * forward to LoginCtrl if
                 * - it's not a public page and
                 * - user is not valid
                 */
                throw new ForwardException('Login needed', 0, 'Elca\Controller\LoginCtrl', 'default');
            }
        }
    }
    // End filter

    /**
     * checks if access is granted by an app_token
     *
     * @param RequestBase $Request
     *
     * @return bool
     */
    protected function checkAppToken(RequestBase $Request)
    {
        if (!$Request->has('app_token'))
            return false;

        $token = $Request->get('app_token');
        $parts = explode(".", $token);
        if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]))
        {
            $User = User::findByCryptId($token, 'modified');
            if (!$User->isInitialized())
                return false;

            if($User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN))
                return $this->recoverSession($User);

            $Config = Environment::getInstance()->getConfig();
            if (!$User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD))
                return false;

            if (!$Config->isBeta)
                return $this->recoverSession($User);

            if ($User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_BETA))
                return $this->recoverSession($User);

            return false;
        }

        return false;
    }
    // End checkAppToken


    /**
     *  Checking the credentials for authentication
     *
     *  @return boolean
     */
    protected function checkCredentials(RequestBase $Request)
    {
        if ($this->checkAppToken($Request))
            return true;

        $username = $Request->authName;
        $password = $Request->authKey;

        $User = User::findByAuthName($username);
        if (!$User->isInitialized())
            $User = User::findExtendedByEmail($username);

        if ($User->isInitialized() && !$User->isLocked())
        {
            $Auth = new Auth($User->getAuthMethod(), 'authName', 'authKey');
            $Auth->authName = $username;
            $Auth->authKey = $password;

            if(!$User->authenticate($Auth) && Environment::getInstance()->getConfig()->elca->auth->uniqueEmail)
            {
                $Auth = new Auth($User->getAuthMethod(), 'email', 'authKey');
                $Auth->authName = \utf8_strtolower($username);
                $Auth->authKey = $password;
            }

            if($User->authenticate($Auth))
            {
                if($User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN))
                    return $this->checkAuthStatus($User);

                $Config = Environment::getInstance()->getConfig();
                if (!$User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD))
                    return false;

                if (!$Config->isBeta)
                    return $this->checkAuthStatus($User);

                if ($User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_BETA))
                    return $this->checkAuthStatus($User);

                return false;
            }
        }

        return false;
    }
    // End checkCredentials


    protected function checkAuthStatus($User)
    {
        if ($User->getStatus() == User::STATUS_CONFIRMED)
            return (bool) UserStore::getInstance()->setUser($User);

        if ($User->getStatus() == User::STATUS_REQUESTED)
            return false;

        throw new ForwardException('Account information need update', 0, 'Elca\Controller\UpdateProfileCtrl', 'default', ['userId' => $User->getCryptId()]);
    }

    /**
     * Sets the given user and try to recover a existing session
     *
     * @param $user
     *
     * @return bool
     * @throws \DI\NotFoundException
     */
    protected function recoverSession($user)
    {
        UserStore::getInstance()->setUser($user);

        /**
         * @var ElcaSessionRecovery $sessionRecovery
         */
        $sessionRecovery = Environment::getInstance()->getContainer()->get(ElcaSessionRecovery::class);
        $sessionRecovery->recover();

        ElcaAccess::getInstance()->setUser($user);

        return true;
    }
    // End recoverSession
}
// End ElcaAuthFilter
