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
use Beibob\Blibs\Exception;
use Beibob\Blibs\User;
use Beibob\Blibs\Validator;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\User\UserMailService;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaLoginView;

/**
 * Login controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 *
 */
class ForgotCtrl extends AppCtrl
{
    /**
     * Namespace
     */
    protected $namespace;

    /**
     * @var UserMailService
     */
    private $userMailService;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if($this->hasBaseView())
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);

        $this->namespace = $this->Session->getNamespace('elca.subscribe', true);

        $this->userMailService = $this->get(UserMailService::class);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Default action
     */
    protected function defaultAction()
    {
        $Config = Environment::getInstance()->getConfig();

        $View = $this->addView(new ElcaLoginView(), 'ForgotView');
        if ($this->isBaseRequest())
        {
            if (!$this->checkToken($View))
                $View->setBuildMode(ElcaLoginView::BUILDMODE_FORCED_FORGOT);
        }
        else
        {
            $View->setBuildMode(ElcaLoginView::BUILDMODE_FORGOT);
            if (!$this->Request->isMethodPost())
                $this->updateHashUrl('/forgot/');
        }

        $View->assign('isPostRequest', false);

        if($this->Request->isMethodPost())
        {
            $Validator = $View->assign('Validator', new Validator($this->Request));
            if ($this->Request->has('userId'))
            {
                $View->setBuildMode(ElcaLoginView::BUILDMODE_FORGOT_SET_PASSWORD);

                $User = User::findByCryptId($this->Request->userId);
                if (!$User->isInitialized())
                {
                    $View->setBuildMode(ElcaLoginView::BUILDMODE_INVALID_CALL);
                    $View->assign('context', 'forgot');
                    return;
                }

                $View->assign('User', $User);
                $Validator->assertTrue('authKey', $User->isInitialized(), t('Ungültiger Aufruf'));

                if ($Validator->isValid())
                {
                    $Validator->assertNotEmpty('authKey', null, SubscribeCtrl::$requiredFieldEmptyMessage);
                    $Validator->assertNotEmpty('confirmKey', null, SubscribeCtrl::$requiredFieldEmptyMessage);
                }

                if ($Validator->isValid())
                {
                    $minLength = $Config->elca->auth->authKeyMinLength ? $Config->elca->auth->authKeyMinLength : SubscribeCtrl::AUTHKEY_MIN_LENGTH;
                    $Validator->assertMinLength('authKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));
                    $Validator->assertMinLength('confirmKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));

                    $Validator->assertTrue('authKey', $this->Request->authKey == $this->Request->confirmKey, t('Die Passworte stimmen nicht überein'));
                    $Validator->assertTrue('confirmKey', $this->Request->authKey == $this->Request->confirmKey, t('Die Passworte stimmen nicht überein'));
                }

                if ($Validator->isValid())
                {
                    try
                    {
                        $Auth = new Auth();
                        $Auth->authName = $User->getAuthName();
                        $Auth->authKey = $this->Request->authKey;

                        $User->setAuthentication($Auth);
                        $User->update();

                        $View->setBuildMode(ElcaLoginView::BUILDMODE_FORGOT_SUCCESS);
                    }
                    catch(Exception $Exception)
                    {
                        $Validator->setError('authName', t('Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal'));
                        //   $Validator->setError('authKey', $Exception->getMessage());
                    }
                }
            }
            else
            {
                $Validator->assertNotEmpty('authName', null, t('Bitte geben Sie Ihren Benutzernamen an.'));

                if ($Validator->isValid())
                {
                    $User = User::findByAuthName($this->Request->authName);
                    if (!$User->isInitialized() && $Config->elca->auth->uniqueEmail)
                        $User = User::findExtendedByEmail(\utf8_strtolower($this->Request->authName));

                    $Validator->assertTrue('authName', $User->isInitialized(), t('Dieser Benutzername ist unbekannt.'));
                }

                if ($Validator->isValid())
                    $Validator->assertTrue('authName', !is_null($User->getEmail()), t('Zu Ihrem Account liegt keine bestätigte E-Mail-Adresse vor. Bitte wenden Sie sich an %admin% um ein neues Passwort zu setzen.', null, ['%admin%' => $Config->elca->auth->helpDesk]));

                if ($Validator->isValid()) {
                    try {
                        $this->userMailService->sendForgotMail($User);

                        $View->setBuildMode(ElcaLoginView::BUILDMODE_FORGOT_EMAIL_SENT);
                        $View->assign('email', $User->getEmail());

                        $this->updateHashUrl('/login/');
                    } catch (Exception $Exception) {
                        $Validator->setError('authName', t('Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal'));
                    }
                }
            }

            if(!$Validator->isValid())
            {
                $clean = [];
                foreach($Validator->getErrors() as $message)
                    $clean[$message] = $message;

                foreach ($clean as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }
        }
    }
    // End defaultAction


    /**
     * @param $View
     *
     * @return bool
     */
    protected function checkToken($View)
    {
        if (!$this->getAction())
            return false;

        $token = $this->getAction();
        $parts = explode(".", $token);
        if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]))
        {
            $User = User::findByCryptId($token);
            if (!$User->isInitialized())
            {
                $View->setBuildMode(ElcaLoginView::BUILDMODE_INVALID_CALL);
                return true;
            }

            $View->setBuildMode(ElcaLoginView::BUILDMODE_FORGOT_SET_PASSWORD);
            $View->assign('User', $User);
            return true;
        }
    }


    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End isPublic
}
// End ForgotCtrl
