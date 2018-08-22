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
namespace Elca\Controller\Traits;

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Log;
use Beibob\Blibs\Url;
use Beibob\Blibs\User;
use Elca\Service\Mailer;
use Elca\View\UserMailView;
use Exception;

trait UserMailsTrait
{
    /**
     *
     * @var
     */
    protected $Mailer;

    /**
     * @param          $subject
     * @param HtmlView $View
     * @param          $to
     * @param          $User
     *
     * @return bool
     * @throws Exception
     */
    protected function sendMail($subject, HtmlView $View, $to, $User)
    {
        try
        {
            $View->process();

            /** @var Mailer $Mail */
            $Mail = FrontController::getInstance()->getEnvironment()->getContainer()->get('Elca\Service\Mailer');
            $Mail->setSubject($subject);
            $Mail->setHtmlContent((string) $View);
            $Mail->send($to ? $to : $User->getEmail(), \trim($User->getFirstname() . ' ' . $User->getLastname()));
            return true;
        }
        catch(Exception $Exception)
        {
            Log::getInstance()->error(__METHOD__ . '() - Exception: ' . $Exception->getMessage());
            throw $Exception;
        }
        return false;
    }
    // End sendMail


    /**
     * @param User $User
     *
     * @return bool
     */
    protected function sendConfirmationMail(User $User, $to = null)
    {
        $url = new Url(FrontController::getInstance()->getUrlTo('Elca\Controller\SubscribeCtrl', $User->getCryptId()), null, null, Environment::getInstance()->getServerHostName());
        $MailView = new UserMailView('mail/confirmation', $User);
        $MailView->assign('url', (string) $url);

        if (is_null($to))
            $to = $User->getCandidateEmail() ? $User->getCandidateEmail() : $User->getEmail();

        return $this->sendMail(t('eLCA | E-Mail-Adresse bestätigen'), $MailView, $to, $User);
    }
    // End sendConfirmationMail


    /**
     * @param User $User
     *
     * @return bool
     */
    protected function sendInvitationMail(User $User)
    {
        $url = new Url(FrontController::getInstance()->getUrlTo('Elca\Controller\UpdateProfileCtrl', $User->getCryptId()), null, null, Environment::getInstance()->getServerHostName());
        $MailView = new UserMailView('mail/invitation', $User);
        $MailView->assign('url', (string) $url);
        return $this->sendMail(t('eLCA | Ihre Zugangsdaten'), $MailView, $User->getCandidateEmail() ? $User->getCandidateEmail() : $User->getEmail(), $User);
    }
    // End sendConfirmationMail


    /**
     * @param User $User
     *
     * @return bool
     */
    protected function sendForgotMail (User $User)
    {
        $url = new Url(FrontController::getInstance()->getUrlTo('Elca\Controller\ForgotCtrl', $User->getCryptId()), null, null, Environment::getInstance()->getServerHostName());
        $MailView = new UserMailView('mail/forgot', $User);
        $MailView->assign('url', (string) $url);
        return $this->sendMail(t('eLCA | Passwort zurücksetzen'), $MailView, $User->getEmail(), $User);
    }
    // End sendForgotMail
}
// End UserMailsTrait