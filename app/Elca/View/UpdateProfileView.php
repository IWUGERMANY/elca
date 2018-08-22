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
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\UserProfile;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlPasswordInput;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the form to check account settings
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class UpdateProfileView extends HtmlView
{
    /**
     * buildmode constants
     */
    const BUILDMODE_FORM = 'form';
    const BUILDMODE_INVALID = 'invalid';
    const BUILDMODE_MAIL_SENT = 'sent';


    /**
     * current buildmode
     * @var string
     */
    protected $buildMode = self::BUILDMODE_FORM;

    /**
     * Loads template
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('update_profile', 'elca');
    }
    // End __construct


    /**
     * sets buildmode
     *
     * @param $buildMode
     */
    public function setBuildMode($buildMode)
    {
        $this->buildMode = $buildMode;
    }
    // End setBuildmode

    /**
     * Renders the view
     */
    protected function beforeRender()
    {
        $DoneMessage = $this->getElementById('DoneMessage', true);
        $DoneMessage = $DoneMessage->parentNode->removeChild($DoneMessage);
        $DoneMessageEmailSent = $this->getElementById('DoneMessageEmailSent', true);
        $DoneMessageEmailSent = $DoneMessageEmailSent->parentNode->removeChild($DoneMessageEmailSent);
        $InvalidCallMessage = $this->getElementById('InvalidCallMessage', true);
        $InvalidCallMessage = $InvalidCallMessage->parentNode->removeChild($InvalidCallMessage);


        switch ($this->buildMode)
        {
            case self::BUILDMODE_INVALID:
                $Container = $this->getElementById('subscribeForm');
                $this->removeChildNodes($Container);
                $Container->appendChild($InvalidCallMessage);
                break;
            case self::BUILDMODE_MAIL_SENT:
                $Container = $this->getElementById('subscribeForm');
                $this->removeChildNodes($Container);

                if ($this->has('mailSendTo'))
                    $Container->appendChild($DoneMessageEmailSent);
                else
                    $Container->appendChild($DoneMessage);
                break;
            case self::BUILDMODE_FORM:
            default:
                $Container = $this->getElementById('subscribeForm');

            $MessageRequested = $this->getElementById('MessageRequested', true);
            $MessageLegacy = $this->getElementById('MessageLegacy', true);

            if ($this->get('oldStatus') == 'Legacy')
                $MessageRequested->parentNode->removeChild($MessageRequested);
            else
                $MessageLegacy->parentNode->removeChild($MessageLegacy);

            $this->getSubscribeForm()->appendTo($Container);
            break;
        }
    }
    // End beforeRender

    /**
     * Builds and return the form to subscribe
     *
     * @return HtmlForm
     */
    protected function getSubscribeForm()
    {
        $Config = Environment::getInstance()->getConfig();

        $Form = new HtmlForm('subscribeform', '/update-profile/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setAttribute('class', 'xhr');
        $Form->add(new HtmlHiddenField('cryptId', $this->get('User')->getCryptId()));
        if ($this->get('setAuth'))
            $Form->add(new HtmlHiddenField('setAuth', $this->get('setAuth')));

        $User = new \stdClass;


        $UserProfile = $this->get('User')->getProfile();
        $User->gender = $UserProfile->getGender();
        $User->firstname = $UserProfile->getFirstname();
        $User->lastname = $UserProfile->getLastname();
        $User->email = $UserProfile->getCandidateEmail() ? $UserProfile->getCandidateEmail() : $UserProfile->getEmail();
        $User->notice = $UserProfile->getNotice();
        $User->birthday = $UserProfile->getBirthday();
        $User->company = $UserProfile->getCompany();


        $User->authName = $this->get('User')->getAuthName();
        if ($User->authName == $User->email)
            $User->authName = '';

        $User->authKey = $User->confirmKey = '';

        $Form->setDataObject($User);


        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Group = $Form->add(new HtmlFormGroup(t('Zugangsdaten')));
        $Group->addClass('access-data');

        $email =
        $Group->add($Label = new ElcaHtmlFormElementLabel(t('Email'), new HtmlTextInput('email', $UserProfile->getCandidateEmail() ? $UserProfile->getCandidateEmail() : $UserProfile->getEmail()), true));
        if ($UserProfile->getCandidateEmail())
        {
            $message = t('Diese E-Mailadresse wurde noch nicht bestätigt.');
            if ($UserProfile->getEmail())
                $message .= ' ' . t('Es wird weiterhin %email% verwendet.', null, ['%email%' => $UserProfile->getEmail()]);
            $Label->add(new HtmlTag('p', $message));
        }


        $caption = $Config->elca->auth->uniqueEmail ? t('Benutzername (optional)') : t('Benutzername');
        $Group->add(new ElcaHtmlFormElementLabel($caption, $authInput = new HtmlTextInput('authName', $User->authName)));
        if ($User->authName == '')
        {
            $authInput->setAttribute('rel', 'empty');
            $authInput->setAttribute('id', 'authNameInput');
        }

        if ($this->get('setAuth'))
        {
            $Group->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('authKey'), true));
            $Group->add(new ElcaHtmlFormElementLabel(t('Passwort bestätigen'), new HtmlPasswordInput('confirmKey'), true));
        }

        $Group = $Form->add(new HtmlFormGroup(t('Persönliche Daten')));
        $Group->addClass('personal-data');

        $Group->add(new ElcaHtmlFormElementLabel(t('Anrede'), $RadioGroup = new HtmlRadioGroup('gender'), true));
        $RadioGroup->add(new HtmlRadiobox(t('Frau'), UserProfile::GENDER_FEMALE));
        $RadioGroup->add(new HtmlRadiobox(t('Herr'), UserProfile::GENDER_MALE));

        $Group->add(new ElcaHtmlFormElementLabel(t('Vorname'), new HtmlTextInput('firstname', ''), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Nachname'), new HtmlTextInput('lastname', ''), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Organisation'), new HtmlTextInput('company', '')));

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('buttons');
        $Group->add(new HtmlTag('p', t('Mit dem Absenden erklären Sie sich mit den nebenstehenden Nutzungsbedingungen einverstanden.'), ['class' => 'notice']));
        $Link = $Group->add(new HtmlLink(t('Abbrechen'), '/login/'));
        $Link->setAttribute('rel', 'page');
        $Group->add(new ElcaHtmlSubmitButton('login', t('Absenden')));


        return $Form;
    }
    // getSubscribeform

    /**
     * Makes sure that the authName input field is empty if user get redirected to update profile page from
     * login page. (Request-authName ist set)
     */
    protected function afterRender()
    {
        $Input = $this->getElementById('authNameInput');
        if (is_object($Input) && $Input->getAttribute('rel') == 'empty')
        {
            $Input->removeAttribute('rel');
            $Input->setAttribute('value', '');
        }
    }
}
// End UpdateProfileView
