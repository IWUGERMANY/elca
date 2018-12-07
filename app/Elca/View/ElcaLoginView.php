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
 * Builds the login form
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaLoginView extends HtmlView
{
    /**
     * buildmode constants
     */
    const BUILDMODE_LOGIN = 'login';

    /**
     * Displays form to subscribe, returns just the form
     */
    const BUILDMODE_SUBSCRIBE = 'subscribe';

    /**
     * Displays form to subscribe, renders complete screen with visible subscribe form
     */
    const BUILDMODE_FORCED_SUBSCRIBE = 'forced_subscribe';

    /**
     * Displays success message after user had submit his data successfully
     */
    const BUILDMODE_SUBSCRIBE_SUCCESS = 'success';

    /**
     * Displays success message, after user had followed the confirmation link
     */
    const BUILDMODE_CONFIRMED = 'confirmed';

    /**
     * Queries a authname to deliver the forgot mail. Returns just the form
     */
    const BUILDMODE_FORGOT = 'forgot';

    /**
     * Queries a authname to deliver the forgot mail. Renders complete screen with visible forgot form
     */
    const BUILDMODE_FORCED_FORGOT = 'forced_forgot';

    /**
     * Displays message with hint to sent forgot mail
     */
    const BUILDMODE_FORGOT_EMAIL_SENT = 'forgot_sucess';

    /**
     * Returns the form to set an new password
     */
    const BUILDMODE_FORGOT_SET_PASSWORD = 'forgot-setpassword';

    /**
     * Display the sucess message after user had successfully set a new password
     */
    const BUILDMODE_FORGOT_SUCCESS = 'forgot_success';

    /**
     * Displays a 'Invalid call'-Message (userId not set, or invalid usertoken given)
     */
    const BUILDMODE_INVALID_CALL = 'invalid';

    /**
     * current buildmode
     * @var string
     */
    protected $buildMode = self::BUILDMODE_LOGIN;

    /**
     * Loads template
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_login', 'elca');
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
        switch ($this->buildMode)
        {
            case self::BUILDMODE_SUBSCRIBE:
                $Container = $this->appendChild($this->getElementById('subscribeForm')->parentNode->removeChild($this->getElementById('subscribeForm')));
                $this->getElementById('content')->parentNode->removeChild($this->getElementById('content'));
                $this->getSubscribeForm()->appendTo($Container);
                break;
            case self::BUILDMODE_FORCED_SUBSCRIBE:
                $this->buildLoginForm();
                $Container = $this->getElementById('subscribeForm');
                $this->getSubscribeForm()->appendTo($Container);

                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-subscribe-form');
                break;
            case self::BUILDMODE_FORGOT:
                $Container = $this->appendChild($this->getElementById('forgotForm')->parentNode->removeChild($this->getElementById('forgotForm')));
                $this->getElementById('content')->parentNode->removeChild($this->getElementById('content'));
                $this->getForgotForm()->appendTo($Container);
                break;
            case self::BUILDMODE_FORCED_FORGOT:
                $this->buildLoginForm();
                $Container = $this->getElementById('forgotForm');
                $this->getForgotForm()->appendTo($Container);

                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-forgot-form');
                break;

            case self::BUILDMODE_FORGOT_EMAIL_SENT:
                $Container = $this->appendChild($this->getElementById('forgotForm')->parentNode->removeChild($this->getElementById('forgotForm')));
                $this->getElementById('content')->parentNode->removeChild($this->getElementById('content'));

                $Container->appendChild($P = $this->getP(t('Es wurde eine E-Mail an '), ['class' => 'success']));
                $P->appendChild($this->getStrong($this->get('email')));
                $P->appendChild($this->createTextNode(' ' . t('gesendet.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Bitte folgen Sie dem in der E-Mail enthaltenen Link um ein neues Passwort zu setzen.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getBr());
                $P->appendChild($this->getA(['href' => '/', 'class'=>'no-xhr'], t('Zurück zur Anmeldung')));
                break;

            case self::BUILDMODE_FORGOT_SET_PASSWORD:
                $this->buildLoginForm();
                $Container = $this->getElementById('forgotForm');
                $this->getForgotSetPasswordForm()->appendTo($Container);

                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-forgot-form');
                break;

            case self::BUILDMODE_FORGOT_SUCCESS:
                $this->buildLoginForm();
                $Container = $this->getElementById('forgotForm');

                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-forgot-form');

                $this->removeChildNodes($Container);
                $Container->appendChild($this->getH1(t('Passwort gespeichert!')));
                $Container->appendChild($P = $this->getP(''));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Das Passwort wurde gespeichert.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Sie können sich nun mit Ihrem neuen Passwort anmelden.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getA(['href' => '/login/', 'class'=>'no-xhr'], t('Jetzt anmelden')));
                break;
            case self::BUILDMODE_CONFIRMED:
                $this->buildLoginForm();
                $Container = $this->getElementById('subscribeForm');

                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-subscribe-form');

                $this->removeChildNodes($Container);
                $Container->appendChild($this->getH1(t('Vielen Dank!')));
                $Container->appendChild($P = $this->getP(''));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Ihre E-Mailaddresse wurde bestätigt.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Sie können eLCA nun verwenden.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getA(['href' => '/login/', 'class'=>'no-xhr'], t('Jetzt anmelden')));

                break;
            case self::BUILDMODE_SUBSCRIBE_SUCCESS:
                $Container = $this->appendChild($this->getElementById('subscribeForm')->parentNode->removeChild($this->getElementById('subscribeForm')));
                $this->getElementById('content')->parentNode->removeChild($this->getElementById('content'));
                $this->removeChildNodes($Container);

                $Container->appendChild($this->getH1(t('Zugang wurde erstellt')));
                $Container->appendChild($P = $this->getP(t('Ihr Zugang zu eLCA wurde erstellt.'), ['class' => 'success']));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Bevor Sie eLCA benutzen können müssen Sie Ihre E-Mailaddresse bestätigen.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Es wurde eine Email an') . ' '));
                $P->appendChild($this->getStrong($this->get('email')));
                $P->appendChild($this->createTextNode(' ' . t('versendet.')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Bitte klicken Sie auf den in der Email enthaltenen Link, um Ihren Zugang zu eLCA zu aktivieren.')));
                break;

            case self::BUILDMODE_INVALID_CALL:
                $this->buildLoginForm();
                $context = $this->has('context') ? $this->get('context') : 'subscribe';
                $Container = $this->getElementById($context . 'Form');


                $elcaLogin = $this->getElementById('elcaLogin');
                $elcaLogin->setAttribute('class', $elcaLogin->getAttribute('class') . ' show-' . $context . '-form');

                $this->removeChildNodes($Container);
                $Container->appendChild($this->getH1(t('Ungültiger Aufruf')));
                $Container->appendChild($P = $this->getP(''));
                $P->appendChild($this->getBr());
                $P->appendChild($this->createTextNode(t('Bei der Verarbeitung Ihrer Anfrage ist ein Fehler aufgetreten!')));
                $P->appendChild($this->getBr());
                $P->appendChild($this->getA(['href' => '/', 'class'=>'no-xhr'], t('Zurück zur Anmeldung')));

                break;

            case self::BUILDMODE_LOGIN:
            default:
                $this->buildLoginForm();
                break;
        }

        $SubscribeNotification = $this->getElementById('SubscribeNotification', true);
        $SubscribeLink = $this->getElementById('SubscribeLink', true);

        if ($this->buildMode == self::BUILDMODE_LOGIN)
        {
            if ($SubscribeNotification && !Environment::getInstance()->getConfig()->elca->auth->disableSubscriptionFeature)
                $SubscribeNotification->parentNode->removeChild($SubscribeNotification);

            if ($SubscribeLink && Environment::getInstance()->getConfig()->elca->auth->disableSubscriptionFeature)
                $SubscribeLink->parentNode->removeChild($SubscribeLink);
        }
        else
        {
            if (!is_null($SubscribeNotification))
                $SubscribeNotification->parentNode->removeChild($SubscribeNotification);

            if (!is_null($SubscribeLink))
                $SubscribeLink->parentNode->removeChild($SubscribeLink);
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

        $Form = new HtmlForm('subscribeform', '/subscribe/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setAttribute('class', 'xhr');

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Group = $Form->add(new HtmlFormGroup(t('Zugangsdaten')));
        $Group->addClass('access-data');

        $Group->add(new ElcaHtmlFormElementLabel(t('Email'), new HtmlTextInput('email', ''), true));
        $caption = $Config->elca->auth->uniqueEmail ? t('Benutzername (optional)') : t('Benutzername');
        $Group->add(new ElcaHtmlFormElementLabel($caption, new HtmlTextInput('authName', ''), !$Config->elca->auth->uniqueEmail));
        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('authKey', ''), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort bestätigen'), new HtmlPasswordInput('confirmKey', ''), true));

        $Group = $Form->add(new HtmlFormGroup(t('Persönliche Daten')));
        $Group->addClass('personal-data');

        $Group->add(new ElcaHtmlFormElementLabel('Anrede', $RadioGroup = new HtmlRadioGroup('gender'), true));
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
     * Builds and returns the form to enter the authName for password recovery
     *
     * @return HtmlForm
     */
    protected function getForgotForm()
    {
        $Form = new HtmlForm('forgotform', '/forgot/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setAttribute('class', 'xhr');

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Group = $Form->add(new HtmlFormGroup(''));

        $caption = Environment::getInstance()->getConfig()->elca->auth->uniqueEmail ? t('Benutzername/ E-Mail') : t('Benutzername');
        $Group->add(new ElcaHtmlFormElementLabel($caption, new HtmlTextInput('authName', ''), true));

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('buttons');
        $Group->add(new HtmlTag('p', t('Nach dem Absenden erhalten Sie eine E-Mail in der Sie weitere Anweisungen zum zurücksetzten Ihres Passwortes finden.'), ['class' => 'notice']));
        $Link = $Group->add(new HtmlLink(t('Abbrechen'), '/login/'));
        $Link->setAttribute('rel', 'page');
        $Group->add(new ElcaHtmlSubmitButton('login', t('Absenden')));

        return $Form;
    }
    // GetForgotform

    /**
     * Builds and returns the form to set a new password
     *
     * @return HtmlForm
     */
    protected function getForgotSetPasswordForm()
    {
        $Form = new HtmlForm('forgotform', '/forgot/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setAttribute('class', 'xhr');

        $Form->add(new HtmlHiddenField('userId', $this->get('User')->getCryptId()));
        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Group = $Form->add(new HtmlFormGroup(''));

        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('authKey', ''), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort bestätigen'), new HtmlPasswordInput('confirmKey', ''), true));

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('buttons');
        $Group->add(new HtmlTag('p', t('Sie können nun ein neues Passwort setzen.'), ['class' => 'notice']));
        $Link = $Group->add(new HtmlLink(t('Abbrechen'), '/login/'));
        $Link->setAttribute('rel', 'page');
        $Group->add(new ElcaHtmlSubmitButton('login', t('Absenden')));

        return $Form;
    }
    // End getForgotSetPasswordForm


    /**
     * Builds and append loginForm
     */
    protected function buildLoginForm()
    {
        $Container = $this->getElementById('loginForm', true);

        $Form = new HtmlForm('loginform', '/login/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setAttribute('class', 'xhr');

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        if($this->has('origin'))
            $Form->add(new HtmlHiddenField('origin', $this->get('origin', '/')));

        $Group = $Form->add(new HtmlFormGroup(t('Anmelden')));

        $caption = Environment::getInstance()->getConfig()->elca->auth->uniqueEmail ? t('Benutzername/ E-Mail') : t('Benutzername');
        $Group->add(new ElcaHtmlFormElementLabel($caption, new HtmlTextInput('authName', ''), true));
        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('authKey', ''), true));

        $Group = $Form->add(new HtmlFormGroup(''));
        $pTag = $Group->add(new HtmlTag('p', t('Mit dem Absenden erklären Sie sich mit den nebenstehenden Nutzungsbedingungen und der aktuellen '), ['class' => 'notice']));
        $pTag->add(new HtmlTag('a', t('Datenschutzvereinbarung'), ['href' => '/privacy/', 'target' => '_blank', 'class' => 'page no-xhr']));
        $pTag->add(new HtmlTag('span', t(' einverstanden.')));

        $Group->add(new ElcaHtmlSubmitButton('login', t('Absenden')));

        $Form->appendTo($Container);
    }
    // End buildLoginForm
}
// End ElcaLoginView
