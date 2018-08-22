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
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\User;
use Beibob\Blibs\UserProfile;
use Beibob\Blibs\Validator;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\User\UserMailService;
use Elca\View\ElcaBaseView;
use Elca\View\UpdateProfileView;
use Exception;

/**
 * Login controller
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Möller <fab@beibob.de>
 *
 */
class UpdateProfileCtrl extends AppCtrl
{
    protected $Namespace;

    /**
     * @var UserMailService
     */
    private $userMailService;

    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End init

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->Namespace = $this->Session->getNamespace(LoginCtrl::NAMESPACE_NAME, true);

        if ($this->hasBaseView()) {
            $this->getBaseView()->disableSidebar(ElcaBaseView::SIDEBAR_LEFT);
        }

        $this->userMailService = $this->get(UserMailService::class);
    }
    // End defaultAction

    /**
     * Default action
     */
    protected function defaultAction()
    {
        $View = $this->setView(new UpdateProfileView());
        $View->assign('setAuth', true);

        /** @var User $User */
        $User = $this->checkToken();
        if (!$User || !$User->isInitialized()) {
            $User = User::findByCryptId(
                $this->Request->has('cryptId') ? $this->Request->cryptId : $this->Namespace->cryptId
            );
            $View->assign('setAuth', $this->Request->has('setAuth'));
        }

        if ($User->isInitialized()) {
            $View->assign('User', $User);
            $View->setBuildMode(UpdateProfileView::BUILDMODE_FORM);
        } else {
            $View->setBuildMode(UpdateProfileView::BUILDMODE_INVALID);

            return;
        }

        $oldStatus = $User->getStatus();
        $View->assign('oldStatus', $oldStatus == User::STATUS_LEGACY ? 'Legacy' : 'Requested');

        if ($this->Request->has('cryptId')) {

            $Config = Environment::getInstance()->getConfig();

            /** @var Validator $Validator */
            $Validator = $View->assign('Validator', new Validator($this->Request));
            $Validator->assertNotEmpty('gender', null, SubscribeCtrl::$requiredFieldEmptyMessage);
            $Validator->assertNotEmpty('firstname', null, SubscribeCtrl::$requiredFieldEmptyMessage);
            $Validator->assertNotEmpty('lastname', null, SubscribeCtrl::$requiredFieldEmptyMessage);
            $Validator->assertNotEmpty('email', null, SubscribeCtrl::$requiredFieldEmptyMessage);

            if ($Validator->isValid()) {
                $Validator->assertEmail('email', null, false, t('Bitte geben Sie eine gültige E-Mail-Adresse an'));
            }

            if (!$User->getEmail())
                $emailChanged = $User->getCandidateEmail() != \utf8_strtolower(\trim($this->Request->email));
            else
                $emailChanged = $User->getEmail() != \utf8_strtolower(\trim($this->Request->email));

            $oldStatus = $User->getStatus();
            $View->assign('oldStatus', $oldStatus == User::STATUS_LEGACY ? 'Legacy' : 'Requested');

            if ($Config->elca->auth->uniqueEmail)
                $useAuthName = \trim($this->Request->authName) && \utf8_strtolower(\trim($this->Request->authName)) != \utf8_strtolower(\trim($this->Request->email));
            else {
                $useAuthName = true;
            }

            if ($emailChanged && $Config->elca->auth->uniqueEmail) {

                /**
                 * Check if email is unique
                 */
                if ($Validator->isValid()) {
                    $UserByEmail = User::findExtendedByEmail($this->Request->email);
                    if ($UserByEmail->isInitialized() && $UserByEmail->getId() != $User->getId()) {
                        $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));
                    }

                    $UserByEmail = User::findExtendedByCandidateEmail($this->Request->email);
                    if ($UserByEmail->isInitialized() && $UserByEmail->getId() != $User->getId()) {
                        $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));
                    }
                }
            }

            if ($useAuthName) {
                $UserByAuthName = User::findByAuthName(\utf8_strtolower(\trim($this->Request->authName)));
                if ($UserByAuthName->isInitialized() && $UserByAuthName->getId() != $User->getId()) {
                    $Validator->setError('authName', t('Dieser Benutzername wird bereits verwendet'));
                }


                if ($Validator->isValid() && \trim($this->Request->authName) != \utf8_strtolower(\trim($this->Request->email)))
                    $Validator->assertTrue('authName', !Validator::isEmail($this->Request->authName), t('Bitte verwenden Sie keine E-Mail-Adresse als Benutzernamen'));

                $minLength = $Config->elca->auth->authNameMinLength ? $Config->elca->auth->authNameMinLength
                    : SubscribeCtrl::AUTHNAME_MIN_LENGTH;
                $Validator->assertMinLength(
                    'authName',
                    $minLength,
                    null,
                    t(
                        'Der Benutzername muss mindestens %minLength% Zeichen lang sein',
                        null,
                        ['%minLength%' => $minLength]
                    )
                );
            }

            if ($this->Request->has('setAuth')) {
                $Validator->assertNotEmpty('authKey', null, SubscribeCtrl::$requiredFieldEmptyMessage);
                $Validator->assertNotEmpty('confirmKey', null, SubscribeCtrl::$requiredFieldEmptyMessage);

                $minLength = $Config->elca->auth->authKeyMinLength ? $Config->elca->auth->authKeyMinLength
                    : self::AUTHKEY_MIN_LENGTH;
                $Validator->assertMinLength(
                    'authKey',
                    $minLength,
                    null,
                    t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength])
                );
                $Validator->assertMinLength(
                    'confirmKey',
                    $minLength,
                    null,
                    t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength])
                );

                $Validator->assertTrue(
                    'authKey',
                    $this->Request->authKey != UsersCtrl::USER_CONST_KEY,
                    t('Bitte vergeben Sie ein Passwort')
                );
                $Validator->assertTrue(
                    'confirmKey',
                    $this->Request->confirmKey != UsersCtrl::USER_CONST_KEY,
                    t('Bitte vergeben Sie ein Passwort')
                );

                $Validator->assertTrue(
                    'authKey',
                    $this->Request->authKey == $this->Request->confirmKey,
                    t('Die Passworte stimmen nicht überein')
                );
                $Validator->assertTrue(
                    'confirmKey',
                    $this->Request->authKey == $this->Request->confirmKey,
                    t('Die Passworte stimmen nicht überein')
                );
            }


            if ($Validator->isValid()) {
                $Dbh = DbHandle::getInstance();

                try {
                    /** @var DbHandle $Dbh */
                    $Dbh->begin();

                    /** @var UserProfile $UserProfile */
                    $UserProfile = $User->getProfile();
                    $UserProfile->setGender($this->Request->gender);
                    $UserProfile->setFirstname($this->Request->firstname);
                    $UserProfile->setLastname($this->Request->lastname);
                    $UserProfile->setCompany($this->Request->company);

                    $Auth = new Auth();
                    if ($useAuthName) {
                        $Auth->authName = $this->Request->authName;
                    } else {
                        $Auth->authName = $emailChanged ? $User->getAuthName() : $this->Request->email;
                    }

                    if ($this->Request->has('setAuth')) {
                        $Auth->authKey = $this->Request->authKey;
                    }
                    $User->setAuthentication($Auth);

                    $User->setStatus(User::STATUS_CONFIRMED);
                    $User->update();

                    $UserProfile = $User->getProfile();

                    if ($emailChanged || $oldStatus == User::STATUS_LEGACY)
                        $UserProfile->setCandidateEmail(\utf8_strtolower(\trim($this->Request->email)));
                    else {
                        $UserProfile->setCandidateEmail(null);
                        $UserProfile->setEmail(\utf8_strtolower(\trim($this->Request->email)));
                    }

                    $UserProfile->update();
                    $Dbh->commit();

                    if ($emailChanged || $oldStatus == User::STATUS_LEGACY) {
                        $View->assign(
                            'mailSendTo',
                            $UserProfile->getCandidateEmail() ? $UserProfile->getCandidateEmail()
                                : $UserProfile->getEmail()
                        );
                        $this->userMailService->sendConfirmationMail($User);
                    }

                    $View->setBuildMode(UpdateProfileView::BUILDMODE_MAIL_SENT);
                } catch (Exception $Exception) {
                    if ($Dbh->inTransaction()) {
                        $Dbh->rollback();
                    }

                    $msg = t('Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es später wieder');
                    $Validator->setError('authName', $msg);
                    $Validator->setError('email', $msg);
                    Log::getInstance()->error(__METHOD__.'() - Exception: '.$Exception->getMessage());
                    $View->setBuildMode(UpdateProfileView::BUILDMODE_FORM);
                }
            }

            if (!$Validator->isValid()) {
                $clean = [];
                foreach ($Validator->getErrors() as $message) {
                    $clean[$message] = $message;
                }

                foreach ($clean as $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
                }
            }
        }
    }

    /**
     * @return User|string
     */
    protected function checkToken()
    {
        if (!$this->getAction()) {
            return User::findById(false);
        }

        $token = $this->getAction();
        $parts = explode(".", $token);
        if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            return User::findByCryptId($token);
        }

        return User::findById(false);
    }
    // End isPublic
}
// End UpdateProfileCtrl
