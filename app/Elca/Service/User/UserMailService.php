<?php
/**
 *  This file is part of the eLCA project
 *
 *  eLCA
 *  A web based life cycle assessment application
 *
 *  Copyright (c) 2017 Tobias Lode <tobias@beibob.de>
 *                BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 *  eLCA is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  eLCA is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Elca\Service\User;


use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Interfaces\Logger;
use Beibob\Blibs\Log;
use Beibob\Blibs\User;
use Beibob\Blibs\Environment;
use Elca\Controller\ForgotCtrl;
use Elca\Controller\SubscribeCtrl;
use Elca\Controller\UpdateProfileCtrl;
use Elca\Service\Mailer;
use Elca\Service\UrlGenerator;
use Elca\View\UserMailView;


class UserMailService
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Logger
     */
    private $log;

    public function __construct(Mailer $mailer, UrlGenerator $urlGenerator, Logger $log)
    {
        $this->mailer       = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->log          = $log;
    }

    public function sendConfirmationMail(User $user, string $to = null) : void
    {
        $url = $this->urlGenerator->absoluteUrlTo(SubscribeCtrl::class, $user->getCryptId());

        $userMailView = new UserMailView('mail/confirmation', $user);
        $userMailView->assign('url', (string)$url);

        if (null === $to) {
            $to = $user->getCandidateEmail() ?: $user->getEmail();
        }

        $this->sendMail(t('eLCA | E-Mail-Adresse bestätigen'), $userMailView, $to, $user);
    }

    public function sendInvitationMail(User $user): void
    {
        $url = $this->urlGenerator->absoluteUrlTo(UpdateProfileCtrl::class, $user->getCryptId());

        $userMailView = new UserMailView('mail/invitation', $user);
        $userMailView->assign('url', (string)$url);

        $this->sendMail(
            t('eLCA | Ihre Zugangsdaten'),
            $userMailView,
            $user->getCandidateEmail() ?: $user->getEmail(),
            $user
        );
    }
	
	public function sendDeactivationMail(User $user): void
    {
        
		switch($user->getStatus())
		{
			case -1: $urlProfil = $this->urlGenerator->absoluteUrlTo(UpdateProfileCtrl::class, $user->getCryptId());
					break;
		}

		$urlForgetPassword = $this->urlGenerator->absoluteUrlTo(ForgotCtrl::class, $user->getCryptId());
		
        $userMailView = new UserMailView('mail/deactivation', $user);
        $userMailView->assign('urlProfil', (string)$urlProfil);
		$userMailView->assign('urlForgetPassword', (string)$urlForgetPassword);
		

		$environment = Environment::getInstance();
        $config = $environment->getConfig();
		
		$userMailView->assign('hostname', $environment->getServerHostName());
		
		// Debug-TEST-Version
		// Versand einer Mail an hinterlegte E-Mailadresse
		// $MailTo = $user->getCandidateEmail() ?: $user->getEmail();
		// ----------------------------------------------------------
		
        if (isset($config->elca) &&  isset($config->elca->mailAddress)) 
		{
            $MailTo = $config->elca->mailAddress;
        }
		else
		{
			$MailTo = "bauteileditor@online-now.de";
		}	
		// ----------------------------------------------------------
		
        $this->sendMail(
            t('eLCA | Account Deaktivierung'),
            $userMailView,
            $MailTo,
            $user
        ); // $user->getCandidateEmail() ?: $user->getEmail(),
    }
	

    public function sendForgotMail(User $user): void
    {
        $url = $this->urlGenerator->absoluteUrlTo(ForgotCtrl::class, $user->getCryptId());

        $userMailView = new UserMailView('mail/forgot', $user);
        $userMailView->assign('url', (string)$url);

        $this->sendMail(t('eLCA | Passwort zurücksetzen'), $userMailView, $user->getEmail(), $user);
    }




    private function sendMail(string $subject, HtmlView $view, string $to, User $user): void
    {
        try {
            $view->process();

            $this->mailer->setSubject($subject);
            $this->mailer->setHtmlContent((string)$view);
            $this->mailer->send(
                $to ?: $user->getEmail(),
                trim($user->getFirstname().' '.$user->getLastname())
            );
        } catch (\Exception $Exception) {
            $this->log->fatal($Exception->getMessage(), __METHOD__);
            throw $Exception;
        }
    }
}