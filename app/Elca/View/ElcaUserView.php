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
namespace Elca\View;

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\GroupSet;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\User;
use Beibob\Blibs\UserProfile;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlPasswordInput;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectboxChooser;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Controller\UsersCtrl;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Beibob\HtmlTools\HtmlTag;

/**
 *
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaUserView extends HtmlView
{
    /**
     * @var User $User
     */
    private $User;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->User = User::findById($this->get('userId'));
    }
    // End init

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-general users']));

        $Form = new HtmlForm('userForm', '/users/save/');
        $User = new \stdClass;

        if($this->User && $this->User->isInitialized())
        {
            $Form->add(new HtmlHiddenField('id', $this->User->getId()));


            $UserProfile = $this->User->getProfile();
            $User->email = $UserProfile->getEmail() ? $UserProfile->getEmail() : $UserProfile->getCandidateEmail();
            $User->gender = $this->User->getGender();
            $User->firstname = $UserProfile->getFirstname();
            $User->lastname = $UserProfile->getLastname();
            $User->notice = $UserProfile->getNotice();
            $User->birthday = $UserProfile->getBirthday();
            $User->company = $UserProfile->getCompany();

            $User->authName = $this->User->getAuthName() == $User->email ? '' : $this->User->getAuthName();
            $User->authKey = $User->confirmKey = UsersCtrl::USER_CONST_KEY;

            $Form->setDataObject($User);
        }

        $myAccount = ElcaAccess::getInstance()->getUser()->getId() == $this->User->getId();
        $insertMode = !($this->User && $this->User->isInitialized());


        $Form->setAttribute('id', 'userForm');
        $Form->addClass('highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $AccessData = $Form->add(new HtmlFormGroup(t('Zugangsdaten')));
        $AccessData->addClass('user-access clearfix column');
        $AccessData->add($Label = new ElcaHtmlFormElementLabel(t('E-Mail-Adresse'), new HtmlTextInput('email', $this->User && $this->User->isInitialized() && $this->User->getCandidateEmail() ? $this->User->getCandidateEmail() : null), true));
        if ($this->User && $this->User->isInitialized() && $this->User->getCandidateEmail(true))
        {
            $message = t('Diese E-Mailadresse wurde noch nicht bestätigt.');
            if ($UserProfile->getEmail())
                $message .= ' ' . t('Es wird weiterhin %email% verwendet.', null, ['%email%' => $UserProfile->getEmail()]);
            $Label->add(new HtmlTag('p', $message));
        }

        $Config = Environment::getInstance()->getConfig();
        $AccessData->add(new ElcaHtmlFormElementLabel($Config->elca->auth->uniqueEmail ? t('Benutzername (optional)') : t('Benutzername'), new HtmlTextInput('authName'), !$Config->elca->auth->uniqueEmail));

        /**
         * If Admins should NOT define password of users of type ORGA use ...
         */
//        if ($myAccount)
        if ($myAccount || (ElcaAccess::getInstance()->hasAdminPrivileges() && $this->User->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ORGA))) {
            $AccessData->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('authKey'), true));
            $AccessData->add(new ElcaHtmlFormElementLabel(t('Passwort bestätigen'), new HtmlPasswordInput('confirmKey'), true));
        }


        if (!$myAccount)
        {
            if ($insertMode)
                $caption = t('Einladungs-E-Mail versenden');
            else
                $caption = $this->User && $this->User->isInitialized() && is_null($this->User->getEmail()) && !is_null($this->User->getCandidateEmail()) ? t('Bestätigungs-E-Mail versenden') : t('Einladungs-E-Mail versenden');

            $AccessData->add($Label = new ElcaHtmlFormElementLabel('', new HtmlCheckbox('invite', false, $caption)));
        }

        if (!$myAccount && !$insertMode) {
            $AccessData->add(new ElcaHtmlFormElementLabel('', new HtmlCheckbox('isLocked', ($this->User->isLocked() ? 1 : 0), t('Zugang sperren'))));
        }

        $UserData = $Form->add(new HtmlFormGroup(t('Benutzerdaten')));
        $UserData->addClass('user-data clearfix column');


        $UserData->add(new ElcaHtmlFormElementLabel(t('Anrede'), $RadioGroup = new HtmlRadioGroup('gender'), $myAccount));
        $Radio = $RadioGroup->add(new HtmlRadiobox(t('Frau'), UserProfile::GENDER_FEMALE));
        if (isset($User->gender) && $User->gender == UserProfile::GENDER_FEMALE)
            $Radio->setAttribute('selected', 'selected');

        $Radio = $RadioGroup->add(new HtmlRadiobox(t('Herr'), UserProfile::GENDER_MALE));
        if (isset($User->gender) && $User->gender == UserProfile::GENDER_MALE)
            $Radio->setAttribute('selected', 'selected');

        $UserData->add(new ElcaHtmlFormElementLabel(t('Vorname'), new HtmlTextInput('firstname'), $myAccount));
        $UserData->add(new ElcaHtmlFormElementLabel(t('Nachname'), new HtmlTextInput('lastname'), $myAccount));
        $UserData->add(new ElcaHtmlFormElementLabel(t('Organistation'), new HtmlTextInput('company')));

        if($this->User && $this->User->isInitialized() && ElcaAccess::getInstance()->hasAdminPrivileges())
        {
            $Groups = GroupSet::findByUserId($this->User->getId());
            $AllGroups = GroupSet::find(['is_usergroup' => false], ['name' => 'ASC']);

            $SelectBoxChooser = new HtmlSelectboxChooser('id','name');
            $SelectBoxChooser->setDbObjectSets($AllGroups, $Groups);
            $SelectBoxChooser->setCaptions(t('Verfügbare Gruppen'),t('Mitglied in Gruppe'));

            $GroupsChooser = $Form->add(new HtmlFormGroup(t('Verfügbare Gruppen')));
            $GroupsChooser->add($SelectBoxChooser);
            $GroupsChooser->addClass('clear clearfix column user-groups');
        }



        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix buttons');

        if ($myAccount)
            $ButtonGroup->add(new ElcaHtmlSubmitButton('delete', t('Zugang löschen'), true));

        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        $Form->appendTo($Content);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaUserView
