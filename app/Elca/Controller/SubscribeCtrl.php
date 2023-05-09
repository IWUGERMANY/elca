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
use Beibob\Blibs\Exception;
use Beibob\Blibs\Role;
use Beibob\Blibs\RoleMember;
use Beibob\Blibs\User;
use Beibob\Blibs\UserProfile;
use Beibob\Blibs\Validator;
use Elca\Elca;
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
class SubscribeCtrl extends AppCtrl
{
    /**
     * Namespace
     */
    protected $Namespace;

    /**
     * @var UserMailService
     */
    private $userMailService;

    /**
     * @translate value 'Bitte füllen Sie alle Pflichtfelder aus'
     */
    public static $requiredFieldEmptyMessage = 'Bitte füllen Sie alle Pflichtfelder aus';

    /**
     * Min length for auth_name, can be overwritten by config value elca.auth.authNameMinLength
     */
    const AUTHNAME_MIN_LENGTH = 4;

    /**
     * Min length for auth_key, can be overwritten by config value elca.auth.authKeyMinLength
     */
    const AUTHKEY_MIN_LENGTH = 4;

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

        $this->Namespace = $this->Session->getNamespace('elca.subscribe', true);
        $this->userMailService = $this->get(UserMailService::class);

    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Default action
     */
    protected function defaultAction()
    {
        $View = $this->addView(new ElcaLoginView(), 'SubscribeView');
        if ($this->isBaseRequest())
        {
            if (!$this->checkConfirmation($View))
                $View->setBuildMode(ElcaLoginView::BUILDMODE_FORCED_SUBSCRIBE);
        }
        else
        {
            $View->setBuildMode(ElcaLoginView::BUILDMODE_SUBSCRIBE);
            $this->updateHashUrl('/subscribe/');
        }

        $View->assign('isPostRequest', false);

        if($this->Request->isMethodPost())
        {
            $Config = Environment::getInstance()->getConfig();

            $View->assign('isPostRequest', true);




            $Validator = $View->assign('Validator', new Validator($this->Request));

            if ($Config->elca->auth->disableSubscriptionFeature)
                $Validator->assertTrue('authName', false, t('Bitte wenden Sie sich an %email% um sich für eLCA zu registrieren', null, ['%email%' => $Config->elca->auth->helpDesk]));

            if ($Validator->isValid()) {
                $Validator->assertNotEmpty('authKey', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertNotEmpty('confirmKey', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertNotEmpty('firstname', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertNotEmpty('lastname', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertNotEmpty('gender', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertTrue('gender', in_array($this->Request->gender, [UserProfile::GENDER_FEMALE, UserProfile::GENDER_MALE]), t(self::$requiredFieldEmptyMessage));

                $Validator->assertNotEmpty('email', null, t(self::$requiredFieldEmptyMessage));
                $Validator->assertEmail('email', null, false, t('Bitte geben Sie eine gültige E-Mail-Adresse an'));

                $useAuthName = \trim($this->Request->authName) && \utf8_strtolower(\trim($this->Request->authName)) != \utf8_strtolower(\trim($this->Request->email));

                if (!$Config->elca->auth->uniqueEmail)
                    $useAuthName = true;

                if ($Validator->isValid() && $Config->elca->auth->uniqueEmail) {
                    /**
                     * Check if email is unique
                     */
                    if ($Validator->isValid()) {
                        $UserByEmail = User::findExtendedByEmail($this->Request->email);
                        if ($UserByEmail->isInitialized())
                            $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));

                        $UserByEmail = User::findExtendedByCandidateEmail($this->Request->email);
                        if ($UserByEmail->isInitialized())
                            $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));
                    }
                }

                if ($Validator->isValid() && $useAuthName) {
                    $UserByAuthName = User::findByAuthName(\utf8_strtolower(\trim($this->Request->authName)));
                    if ($UserByAuthName->isInitialized())
                        $Validator->setError('authName', t('Dieser Benutzername wird bereits verwendet'));

                    if ($Validator->isValid() && \trim($this->Request->authName) != \utf8_strtolower(\trim($this->Request->email)))
                        $Validator->assertTrue('authName', !Validator::isEmail($this->Request->authName), t('Bitte verwenden Sie keine E-Mail-Adresse als Benutzernamen.'));

                    $minLength = $Config->elca->auth->authNameMinLength ? $Config->elca->auth->authNameMinLength : self::AUTHNAME_MIN_LENGTH;
                    $Validator->assertMinLength('authName', $minLength, null, t('Der Benutzername muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));
                }

                if ($Validator->isValid()) {
                    $Validator->assertNotEmpty('authKey', null, t(self::$requiredFieldEmptyMessage));
                    $Validator->assertNotEmpty('confirmKey', null, t(self::$requiredFieldEmptyMessage));

                    $minLength = $Config->elca->auth->authKeyMinLength ? $Config->elca->auth->authKeyMinLength : self::AUTHKEY_MIN_LENGTH;
                    $Validator->assertMinLength('authKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));
                    $Validator->assertMinLength('confirmKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));

                    $Validator->assertTrue('authKey', $this->Request->authKey != UsersCtrl::USER_CONST_KEY, t('Bitte vergeben Sie ein Passwort'));
                    $Validator->assertTrue('confirmKey', $this->Request->confirmKey != UsersCtrl::USER_CONST_KEY, t('Bitte vergeben Sie ein Passwort'));

                    $Validator->assertTrue('authKey', $this->Request->authKey == $this->Request->confirmKey, t('Die Passworte stimmen nicht überein'));
                    $Validator->assertTrue('confirmKey', $this->Request->authKey == $this->Request->confirmKey, t('Die Passworte stimmen nicht überein'));
                }
            }


            if($Validator->isValid())
            {
                try {
                    $Dbh = DbHandle::getInstance();

                    $Dbh->beginTransaction();

                    $Auth = new Auth();
                    $Auth->authName = $useAuthName ? \trim($this->Request->authName) : \utf8_strtolower(\trim($this->Request->email));
                    $Auth->authKey = $this->Request->authKey;
                    $user = User::create($Auth);

                    if($user)
                    {
                        $UserProfile = UserProfile::create($user->getId(), $this->Request->company, $this->Request->gender, $this->Request->firstname, $this->Request->lastname, $this->Request->email);

                        RoleMember::grant(
                            // Gruppe für IFC-Projekt Import / -Viewer im Registrierungsprozess setzen / erweitern
                            // Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD)->getId(),
                            Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD, ELCA::ELCA_ROLE_IFC_VIEWER)->getId(),
                            $user->getGroupId()
                        );

                        $this->userMailService->sendConfirmationMail($user);

                        $View->setBuildMode(ElcaLoginView::BUILDMODE_SUBSCRIBE_SUCCESS);
                        $View->assign('email', $UserProfile->getEmail());

                        $this->updateHashUrl('/login/');

                        $Dbh->commit();
                    }
                }
                catch (Exception $Exception)
                {
                    $Dbh->rollback();

                    $Validator->setError('authName', t('Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal'));
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
    protected function checkConfirmation($View)
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

            $UserProfile = $User->getProfile();
            if ($User->getStatus() == User::STATUS_REQUESTED)
                $User->setStatus(User::STATUS_CONFIRMED);

            if ($UserProfile->getCandidateEmail())
            {
                if ($User->authName == $UserProfile->getEmail())
                {
                    $Auth = new Auth();
                    $Auth->authName = $UserProfile->getCandidateEmail();
                    $User->setAuthentication($Auth);
                }

                $UserProfile->setEmail($UserProfile->getCandidateEmail());
                $UserProfile->setCandidateEmail(null);
            }

            $User->update();
            $UserProfile->update();

            $View->setBuildMode(ElcaLoginView::BUILDMODE_CONFIRMED);


            return true;
        }
    }
    // End checkConfirmation

    /**
     * @return bool
     */
    public static function isPublic()
    {
        return true;
    }
    // End isPublic
}
// End SubscribeCtrl
