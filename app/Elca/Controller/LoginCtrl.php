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
namespace Elca\Controller;

use Beibob\Blibs\Auth;
use Beibob\Blibs\Environment;
use Beibob\Blibs\ForwardException;
use Beibob\Blibs\Url;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Beibob\Blibs\Validator;
use Elca\Elca;
use Elca\Service\Messages\ElcaMessages;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaLoginView;

/**
 * Login controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 *
 */
class LoginCtrl extends AppCtrl
{
    const NAMESPACE_NAME = 'elca.login';

    /**
     * Origin url
     */
    protected $OriginUrl;

    /**
     * Namespace
     */
    protected $Namespace;


    /**
     * Will be called on initialization.
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if ($this->hasBaseView())
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);

        $this->Namespace = $this->Session->getNamespace(self::NAMESPACE_NAME, true);
    }
    // End init


    /**
     * Default action
     */
    protected function defaultAction()
    {
        if (!$this->isAjax()) {
            /**
             * Keep origin url
             */
            $this->Namespace->baseUrl = $this->Request->has('origin') ? $this->Request->origin : $this->Request->getURI();
            return;
        }

        if (UserStore::getInstance()->hasUser()) {
            $this->Response->setHeader('X-Redirect: /');
            return;
        }

        /**
         * Keep origin url
         */
        if ($this->Request->has('origin')) {
            $this->OriginUrl = Url::parse($this->Request->origin);
        } else {
            $HashUrl = Url::parse($this->Request->getURI());
            $HashUrl->setHostname(); // unset hostname
            $HashUrl->removeParameter('_isBaseReq'); // unset isBaseReq

            if ($this->Namespace->baseUrl && $this->Namespace->baseUrl != (string)$HashUrl)
                $this->OriginUrl = Url::parse($this->Namespace->baseUrl . '#!' . (string)$HashUrl);
            else
                $this->OriginUrl = $HashUrl;
        }

        if ($this->OriginUrl->getScriptName() == $this->getActionLink())
            $this->OriginUrl = Url::parse('/');

        $View = $this->setView(new ElcaLoginView());
        $View->assign('origin', (string)$this->OriginUrl);

        if ($this->Request->isMethodPost()) {
            $authName = \trim($this->Request->authName);
            $authKey = \trim($this->Request->authKey);

            $Validator = $this->getViewByName('Elca\View\ElcaLoginView')->assign('Validator', new Validator($this->Request));
            $Validator->assertNotEmpty('authName', null, t('Bitte überprüfen Sie den Benutzernamen.'));
            $Validator->assertNotEmpty('authKey', null, t('Bitte überprüfen Sie das Passwort.'));

            if ($Validator->isValid()) {
                $User = User::findByAuthName($authName);
                if (!$User->isInitialized() && Environment::getInstance()->getConfig()->elca->auth->uniqueEmail)
                    $User = User::findExtendedByEmail($authName);

                $Validator->assertTrue('authName', $User->isInitialized() && !$User->isLocked(), t('Bitte überprüfen Sie Benutzernamen und Passwort'));
            }

            if ($Validator->isValid())
                $Validator->assertTrue('authName', $User->getStatus() != User::STATUS_REQUESTED, t('Bitte bestätigen Sie Ihre E-Mail-Adresse bevor Sie sich anmelden können.'));

            if ($Validator->isValid()) {
                $Auth = new Auth($User->getAuthMethod(), 'authName', 'authKey');
                $Auth->authName = $authName;
                $Auth->authKey = $authKey;

                if (!$User->authenticate($Auth)) {
                    $Auth = new Auth($User->getAuthMethod(), 'email', 'authKey');
                    $Auth->authName = $authName;
                    $Auth->authKey = $authKey;
                    $Validator->assertTrue('authName', $User->authenticate($Auth), t('Bitte überprüfen Sie Benutzernamen und Passwort'));
                }

                if ($Validator->isValid()) {
                    $hasRootRole = $User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN);

                    if (!$hasRootRole) {
                        $Validator->assertTrue('authName', $User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD), t('Zugang wurde verweigert'));

                        $Config = Environment::getInstance()->getConfig();
                        if ($Config->elca->isBeta)
                            $Validator->assertTrue('authName', $User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_BETA),
                                t('Zugang wurde verweigert'));
                    }

                    if ($Validator->isValid()) {
                        if ($User->getStatus() == User::STATUS_LEGACY && !$User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ORGA)) {
                            $this->Namespace->cryptId = $User->getCryptId();
                            throw new ForwardException('Account information need update', 0, 'Elca\Controller\UpdateProfileCtrl', 'default');
                            return;
                        }


                        UserStore::getInstance()->setUser($User);
                        $this->Response->setHeader('X-Redirect: ' . (string)$this->OriginUrl);

                        $this->Log->notice('User `' . $Auth->authName . '\'', 'Login');
                        $this->Namespace->baseUrl = null;
                    }
                }
            }

            if (!$Validator->isValid()) {
                $this->Log->error('User `' . $authName . '\'', 'Login failed: `' . join(', ', $Validator->getErrors()) . '\'');

                foreach ($Validator->getErrors() as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }
        }
    }
    // End defaultAction


    /**
     * logout action - checks userauth
     *
     * @param  -
     *
     * @return -
     */
    protected function logoutAction()
    {
        $this->Log->notice('User `' . UserStore::getInstance()->getUser()->getAuthName() . '\'', 'Logout');

        /**
         * Logout user
         */
        UserStore::getInstance()->unsetUser();

        /**
         * Clean session
         */
        $this->Session->destroy();

        /**
         * Go to login screen
         */
        $this->redirect(IndexCtrl::class);
    }
    // End logoutAction


    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End isPublic
}
// End LoginCtrl
