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
use Beibob\Blibs\GroupMember;
use Beibob\Blibs\Log;
use Beibob\Blibs\NestedNode;
use Beibob\Blibs\Role;
use Beibob\Blibs\RoleMember;
use Beibob\Blibs\Url;
use Beibob\Blibs\User;
use Beibob\Blibs\UserProfile;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProjectSet;
use Elca\Elca;
use Elca\Service\Messages\ElcaMessages;
use Elca\Security\ElcaAccess;
use Elca\Service\User\UserMailService;
use Elca\Validator\ElcaValidator;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaRoleView;
use Elca\View\ElcaUsersView;
use Elca\View\ElcaUserView;
use Exception;

/**
 * Main user controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class UsersCtrl extends TabsCtrl
{
    /**
     * userId
     */
    private $userId;

    /**
     * @var UserMailService
     */
    private $userMailService;

    /*
     * We do not know how many character the users password has.
     * Therefor a constant password dummy
     */
    const USER_CONST_KEY = '********';


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
            $this->getBaseView()->setContext(ElcaBaseView::CONTEXT_USERS);

        if(isset($args['initialAction']) && is_numeric($args['initialAction']))
            $this->userId = (int)$args['initialAction'];

        $this->Osit->clear();

        // set active controller in navigation
        if ($this->isAjax() && $this->Access->hasAdminPrivileges())
            $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', $this->ident());

        $this->userMailService = $this->get(UserMailService::class);
    }
    // End init


    /**
     * Default action
     */
    protected function defaultAction($userId = null)
    {
        if(!$this->isAjax())
            return;

        $userId = is_numeric($userId) ? $userId : $this->getAction();

        if(is_numeric($userId))
        {
            $this->userId = $userId;
            $User = User::findById($this->userId);

            if($this->Access->canEditUser($User))
            {
                $this->addTabItem('general', t('Allgemein'), null, 'Elca\Controller\UsersCtrl', 'general', ['u' => $User->getId()]);

                if($this->Access->hasAdminPrivileges()) {
                    $this->addTabItem('role', t('Rollen'), null, 'Elca\Controller\UsersCtrl', 'role', ['u' => $User->getId()]);
                }

                $this->invokeTabActionController();

                if ($this->Access->hasAdminPrivileges()) {
                    $this->Osit->add(new ElcaOsitItem(t('Benutzer'), '/users/', t('Nutzerverwaltung')));
                }

                $this->Osit->add(new ElcaOsitItem($User->getFullname(), null, t('Benutzer')));
            }
            else
            {
                if ($this->Access->isOrganisation() && $this->userId == $this->Access->getUserId())
                    $this->noAccessRedirect('/');
                else
                    $this->noAccessRedirect('/users/' . $this->Access->getUserId() . '/');
            }
        }
        else
        {
            if(!$this->Access->hasAdminPrivileges())
                $this->noAccessRedirect('/users/'.$this->Access->getUserId().'/');

            else
            {
				$this->setView(new ElcaUsersView(['status'=>$this->Request->status]));
                $this->Osit->add(new ElcaOsitItem(t('Benutzer'), null, t('Nutzerverwaltung')));
            }
        }
    }
    // End defaultAction


    /**
     * Save action
     */
    protected function saveAction()
    {
        if(!$this->Request->isMethodPost())
           return;

        if ($this->Request->has('delete'))
            return $this->deleteAction();

        $user = User::findById($this->Request->id);

        $Config = Environment::getInstance()->getConfig();

        /**
         * Some helper flags
         */
        $insertMode = !$user->isInitialized();
        $myAccountMode = $user->isInitialized() && $user->getId() == ElcaAccess::getInstance()->getUserId();
        $adminMode = !$myAccountMode && $this->Access->hasAdminPrivileges();
        $emailChanged = !$insertMode && $user->isInitialized() && \utf8_strtolower(\trim($this->Request->email)) != $user->getEmail();

        // Don't detect email as changed if given email is equal to already stored candidate email
        if ($emailChanged && \utf8_strtolower(\trim($this->Request->email)) == $user->getCandidateEmail())
            $emailChanged = false;

        if ($Config->elca->auth->uniqueEmail)
        {
            $useAuthName = \trim($this->Request->authName) && \utf8_strtolower(\trim($this->Request->authName)) != \utf8_strtolower(\trim($this->Request->email));
            $authName = $useAuthName ? \trim($this->Request->authName) : \utf8_strtolower(\trim($this->Request->email));
        }
        else
        {
            $useAuthName = true;
            $authName = \trim($this->Request->authName);
        }


        $Validator = new ElcaValidator($this->Request);

        if(!$adminMode && $this->Request->authKey != self::USER_CONST_KEY)
        {
            $Validator->assertNotEmpty('authKey', null, t('Bitte geben Sie ein Passwort an'));

            if ($Validator->isValid())
            {
                $minLength = $Config->elca->auth->authKeyMinLength ? $Config->elca->auth->authKeyMinLength : self::AUTHKEY_MIN_LENGTH;
                $Validator->assertMinLength('authKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));
                $Validator->assertMinLength('confirmKey', $minLength, null, t('Das Passwort muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));
            }

            if ($Validator->isValid())
                $Validator->assertTrue('authKey', $this->Request->authKey == $this->Request->confirmKey, t('Die Passwörter stimmen nicht überein'));
        }
        if ($Validator->hasError('authKey'))
            $Validator->setError('confirmKey', $Validator->getError('authKey'));


        $Validator->assertNotEmpty('email', null, t('Bitte geben Sie eine gültige E-Mail-Adresse an'));
        if ($Validator->isValid())
            $Validator->assertEmail('email', null, false, t('Bitte geben Sie eine gültige E-Mail-Adresse an'));

        if (!$insertMode) {
//            $Validator->assertNotEmpty('gender', null, t('Bitte wählen Sie eine Anrede'));
//            $Validator->assertTrue('gender', in_array($this->Request->gender, [UserProfile::GENDER_FEMALE, UserProfile::GENDER_MALE]), t('Bitte wählen Sie eine Anrede'));
        }

        /**
         * Check if authName is unique - in case it is in use
         */
        if($useAuthName && $Validator->isValid())
        {
            $Validator->assertNotEmpty('authName', null, t('Bitte geben Sie einen Benutzernamen an'));

            if ($Validator->isValid())
            {
                $minLength = $Config->elca->auth->authNameMinLength ? $Config->elca->auth->authNameMinLength : SubscribeCtrl::AUTHNAME_MIN_LENGTH;
                $Validator->assertMinLength('authName', $minLength, null, t('Der Benutzername muss mindestens %minLength% Zeichen lang sein', null, ['%minLength%' => $minLength]));

                $UserByName = User::findByAuthName($authName, true);
                if($UserByName->isInitialized() && $UserByName->getId() != $user->getId())
                    $Validator->setError('authName', t('Dieser Benutzername wird bereits verwendet'));

                if ($Validator->isValid())
                {
                    $UserByName = User::findByAuthName($authName, false);
                    if($UserByName->isInitialized() && $UserByName->getId() != $user->getId())
                        $Validator->setError('authName', t('Ein ähnlicher Benutzername wird bereits verwendet'));
                }
            }
        }

        /**
         * Check if email is unique
         */
        if($Config->elca->auth->uniqueEmail && $Validator->isValid())
        {
            $UserByEmail = User::findExtendedByEmail($this->Request->email);
            if($UserByEmail->isInitialized() && $UserByEmail->getId() != $user->getId())
                $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));

            $UserByEmail = User::findExtendedByCandidateEmail($this->Request->email);
            if($UserByEmail->isInitialized() && $UserByEmail->getId() != $user->getId())
                $Validator->setError('email', t('Diese E-Mail-Adresse wird bereits verwendet'));
        }

        if($Validator->isValid())
        {
            $Dbh = DbHandle::getInstance();

            try
            {

                if($user->isInitialized())
                {
                    $Dbh->begin();
                    $reloadView = false;

                    $Auth = new Auth();
                    $Auth->authName = $useAuthName ? $authName : $user->getAuthName();

                    if($this->Request->authKey != self::USER_CONST_KEY)
                        $Auth->authKey = $this->Request->authKey;

                    $user->setAuthentication($Auth);

                    if($adminMode)
                        $user->setIsLocked($this->Request->has('isLocked'));
                        
                    $user->update();

                    $UserProfile = $user->getProfile();
                    if($UserProfile->isInitialized())
                    {
                        $UserProfile->setGender($this->Request->gender);
                        $UserProfile->setFirstname($this->Request->firstname);
                        $UserProfile->setLastname($this->Request->lastname);
                        $UserProfile->setCompany($this->Request->company);

                        if ($emailChanged)
                        {
                            if ($adminMode)
                                $UserProfile->setEmail(\utf8_strtolower(\trim($this->Request->email)));
                            else
                                $UserProfile->setCandidateEmail(\utf8_strtolower(\trim($this->Request->email)));
                        }
                        else {
                            if ($this->Request->email == $UserProfile->getEmail() && !is_null($UserProfile->getCandidateEmail()))
                                $UserProfile->setCandidateEmail(null);
                        }


                        $UserProfile->update();
                    }
                    else
                    {
                        $Profile = UserProfile::create($this->Request->id, $this->Request->company, $this->Request->gender, $this->Request->firstname, $this->Request->lastname, $this->Request->email);
                        if ($adminMode)
                        {
                            // UserProfile::create always stores email in property candidateEmail! Swap to property email in adminMode!
                            $Profile->setEmail($Profile->getCandidateEmail());
                            $Profile->setCandidateEmail(null);
                            $Profile->update();
                        }
                    }

                    if($this->Access->hasAdminPrivileges())
                    {
                        // add groups
                        $added = trim($this->Request->added);
                        if(isset($added) && $added != "")
                        {
                            $groupIds = explode(',',$added);
                            foreach($groupIds as $id)
                            {
                                if(!GroupMember::exists((int)$id , $user->getId()))
                                    GroupMember::create($user->getId(),(int)$id );
                            }
                        }

                        // remove groups
                        $removed = trim($this->Request->removed);
                        if(isset($removed) && $removed != "")
                        {
                            $groupIds = explode(',',$removed);
                            foreach($groupIds as $id)
                            {
                               if(GroupMember::exists((int)$id , $user->getId()))
                               {
                                  $group = GroupMember::findByPk((int)$id ,$user->getId());
                                  $group->delete();
                               }
                            }
                        }
                    }

                    $Dbh->commit();

                    // Refresh cached object
                    $user = User::findExtendedById($user->getId(), true);

                    $this->messages->add(t('Änderungen wurden übernommen'), ElcaMessages::TYPE_NOTICE);
                    if ($emailChanged && !$this->Request->has('invite'))
                    {
                        $this->userMailService->sendConfirmationMail($user);
                        $this->messages->add(t('Eine E-Mail wurde an "%email%" versendet', null, ['%email%' => $user->getCandidateEmail() ? $user->getCandidateEmail() : $user->getEmail()]), ElcaMessages::TYPE_NOTICE);
                    }

                    if ($this->Request->has('invite'))
                    {
                        if ($adminMode && !$emailChanged && is_null($UserProfile->getEmail()) && !is_null($UserProfile->getCandidateEmail()))
                            $this->userMailService->sendConfirmationMail($user);
                        else
                            $this->userMailService->sendInvitationMail($user);

                        $this->messages->add(t('Eine E-Mail wurde an "%email%" versendet', null, ['%email%' => $user->getCandidateEmail() ? $user->getCandidateEmail() : $user->getEmail()]), ElcaMessages::TYPE_NOTICE);
                    }

                    $this->updateHashUrl('/users/'. $user->getId() .'/', false, true);
                    $this->defaultAction($user->getId());


                    if ($reloadView)
                    {
                        /**
                         * Render view by default action
                         */
                        $this->getView()->assign('redirect', '/users/'. $user->getId() .'/');
                    }
                }
                else
                {
                    $Dbh->begin();

                    $Auth = new Auth();
                    $Auth->authName = $authName;
                    $user = User::create($Auth, $adminMode ? User::STATUS_CONFIRMED : User::STATUS_REQUESTED);

                    if($user)
                    {
                        $Profile = UserProfile::create($user->getId(), $this->Request->company, $this->Request->gender, $this->Request->firstname, $this->Request->lastname, \utf8_strtolower(\trim($this->Request->email)), 'debug');
                        if ($adminMode)
                        {
                            // UserProfile::create always stores email in property candidateEmail! Swap to property email in adminMode!
                            $Profile->setEmail($Profile->getCandidateEmail());
                            $Profile->setCandidateEmail(null);
                            $Profile->update();
                        }

                        RoleMember::grant(
                            Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD)->getId(),
                            $user->getGroupId()
                        );

                        $Dbh->commit();

                        /**
                         * Render view by default action
                         */
                        $this->updateHashUrl('/users/'. $user->getId() .'/', false, true);
                        $this->defaultAction($user->getId());
                    }

                    $this->messages->add(t('Der Benutzer wurde angelegt.'), ElcaMessages::TYPE_NOTICE);

                    if ($this->Request->has('invite'))
                    {
                        $this->userMailService->sendInvitationMail($user);
                        $this->messages->add(t('Eine E-Mail wurde an "%email%" versendet', null, ['%email%' => $user->getCandidateEmail() ? $user->getCandidateEmail() : $user->getEmail()]), ElcaMessages::TYPE_NOTICE);
                    }
                }

            }
            catch (Exception $Exception)
            {
                if ($Dbh->inTransaction())
                    $Dbh->rollback();

                Log::getInstance()->error($Exception->getMessage());
                $this->messages->add(t('Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es später wieder'));
            }
        }
        else
        {
            $clean = [];
            foreach($Validator->getErrors() as $message)
                $clean[$message] = $message;

            foreach ($clean as $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
        }
    }
    // End saveAction


    /**
     * Save role action
     */
    protected function saveRoleAction()
    {
        if(!$this->Access->hasAdminPrivileges())
            $this->noAccessRedirect('/users/'.$this->Access->getUserId().'/');

        else
        {
            if(!$this->Request->isMethodPost())
               return;

            $hasRoles = $this->Request->getArray('roles');

            $Validator = new ElcaValidator($this->Request);
            $Validator->assertTrue('roles',
                                   (isset($hasRoles[Elca::ELCA_ROLE_STANDARD]) && $hasRoles[Elca::ELCA_ROLE_STANDARD]) ||
                                   (isset($hasRoles[Elca::ELCA_ROLE_ADMIN]) && $hasRoles[Elca::ELCA_ROLE_ADMIN]),
                                   t('Es muss mindestens eine der beiden Rollen `Standard\' oder `Administrator\' zugeordnet werden'));

            $Validator->assertTrue('myRoles', $this->Access->getUserId() != $this->Request->id, t('Änderungen an den zugewiesenen Rollen nicht möglich'));

            if($Validator->isValid())
            {
                $Dbh = DbHandle::getInstance();

                try
                {
                    $Dbh->begin();
                    $User = User::findById($this->Request->id);

                    if($User->isInitialized())
                    {
                        $RootRoleNode = NestedNode::findRootByIdent(Elca::ELCA_ROLES);
                        foreach($RootRoleNode->getChildNodes() as $RoleNode)
                        {
                            $hasRole = RoleMember::isGranted($RoleNode->getId(), $User->getGroupId());

                            if(isset($hasRoles[$RoleNode->getIdent()]) && !$hasRole)
                                RoleMember::grant($RoleNode->getId(), $User->getGroupId());

                            elseif(!isset($hasRoles[$RoleNode->getIdent()]) && $hasRole)
                                RoleMember::cease($RoleNode->getId(), $User->getGroupId());
                        }
                    }

                    $Dbh->commit();
                    $this->messages->add(t('Änderungen wurden übernommen'), ElcaMessages::TYPE_NOTICE);
                }
                catch (Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }
            }
            else
            {
                foreach($Validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }
    }
    // End saveRoleAction


    /**
     * Create action
     */
    protected function createAction()
    {
        $this->addTabItem('general', t('Allgemein'), null, 'Elca\Controller\UsersCtrl', 'general');
        $View = $this->setView(new ElcaUserView());
    }
    // End createAction


    /**
     * general action
     */
    protected function generalAction()
    {
        $userId = $this->userId? $this->userId : $this->Request->u;
        if ($this->Request->isMethodPost() && !$userId)
            $userId = $this->Request->id;

        $View = $this->setView(new ElcaUserView());
        $View->assign('userId', $userId);
    }
    // End generalAction


    /**
     * role action
     */
    protected function roleAction()
    {
        $View = $this->setView(new ElcaRoleView());
        $View->assign('userId', $this->Request->u);
    }
    // End roleAction


    /**
     * Deletes an user
     */
    protected function deleteAction()
    {
        if(!is_numeric($this->Request->id))
            return;

        /**
         * Check if user tries to commit suicide
         */
        if (UserStore::getInstance()->getUserId() == $this->Request->id)
            return $this->suicideAction();

        /**
         * Check if user is allowed to edit user
         */
        $User = User::findById($this->Request->id);
        if(!$this->Access->canEditUser($User))
            return;

        if($this->Request->has('confirmed'))
        {
            if($User->isInitialized())
            {
                $Dbh = DbHandle::getInstance();
                try
                {
                    $Dbh->begin();

                    if($this->Request->has('recursive'))
                    {
                        /**
                         * Delete projects and elements owned by the user
                         */
                        ElcaProjectSet::deleteByOwnerId($User->getId());
                        ElcaElementSet::deleteByAccessGroupId($User->getGroupId());

                        $msg = t('Die Projektdaten des Benutzers wurden ebenfalls gelöscht.');
                    }
                    else
                    {
                        /**
                         * Assign access group of projects and elements to the current user
                         */
                        ElcaProjectSet::reassignOwnerId($User->getId(), $this->Access->getUserId());
                        ElcaProjectSet::reassignAccessGroupId($User->getGroupId(), $this->Access->getUserGroupId());
                        ElcaElementSet::reassignOwnerId($User->getId(), $this->Access->getUserId());
                        ElcaElementSet::reassignAccessGroupId($User->getGroupId(), $this->Access->getUserGroupId());

                        $msg = t('Die Projektdaten wurden Ihnen zugeordnet');
                    }

                    /**
                     * Delete user
                     */
                    $User->delete();
                    $Dbh->commit();

                    $this->messages->add($msg);
                }
                catch(Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }

                /**
                 * Forward to list
                 */
                $this->defaultAction();
            }
        }
        else
        {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            if($this->Request->has('recursive'))
                $confirmMsg = t('Soll der Benutzer mitsamt seinen Projektdaten gelöscht werden?');
            else
                $confirmMsg = t('Soll der Benutzer gelöscht und Ihnen die Projektdaten zugeordnet werden?');

            $this->messages->add($confirmMsg, ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteAction

    /**
     *
     */
    protected function suicideAction()
    {
        if(!is_numeric($this->Request->id))
            return;

        if (UserStore::getInstance()->getUserId() != $this->Request->id)
            return;

        $User = User::findById($this->Request->id);
        if (!$User->isInitialized())
            return $User;

        if ($this->Request->has('confirmed'))
        {
            $Dbh = DbHandle::getInstance();
            try
            {
                $Dbh->begin();

                ElcaProjectSet::deleteByOwnerId($User->getId());
                ElcaElementSet::deleteByAccessGroupId($User->getGroupId());

                /**
                 * Delete user
                 */
                $User->delete();
                $Dbh->commit();

                $this->messages->add(t('Ihr Zugang wurde gelöscht'));

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
                 $this->Response->setHeader('X-Redirect: /login/');
            }
            catch(Exception $Exception)
            {
                $Dbh->rollback();
                throw $Exception;
            }
        }
        else
        {
            $url = $this->FrontController->getUrlTo(null, 'suicide', ['confirmed' => null, 'id' => $this->Request->id]);

            $confirmMsg = t('Sind Sie sicher, dass Sie Ihren Zugang zu eLCA vollständig löschen möchten? Sämtliche von Ihnen erstellte Projektdaten gehen verloren.');

            $this->messages->add($confirmMsg, ElcaMessages::TYPE_CONFIRM, (string)$url);
        }

    }
    // End suicideAction
}
// End UsersCtrl
