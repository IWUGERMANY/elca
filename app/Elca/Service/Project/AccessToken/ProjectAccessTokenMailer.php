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

namespace Elca\Service\Project\AccessToken;

use Elca\Controller\ProjectData\ProjectAccessCtrl;
use Elca\Db\ElcaProject;
use Elca\Model\Event\EventSubscriber;
use Elca\Model\Event\InvokeEventMethodTrait;
use Elca\Model\Project\UnconfirmedProjectAccessTokenCreated;
use Elca\Service\Mailer;
use Elca\Service\UrlGenerator;
use Elca\View\MailView;

class ProjectAccessTokenMailer implements EventSubscriber
{
    use InvokeEventMethodTrait;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * ProjectAccessTokenMailer constructor.
     *
     * @param Mailer       $mailer
     * @param UrlGenerator $urlGenerator
     * @internal param FrontController $frontController
     */
    public function __construct(Mailer $mailer, UrlGenerator $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param UnconfirmedProjectAccessTokenCreated $event
     */
    public function onUnconfirmedProjectAccessTokenCreated(UnconfirmedProjectAccessTokenCreated $event)
    {
        $project = ElcaProject::findById($event->projectId()->value());
        $owner = $project->getOwner();

        $url = $this->urlGenerator->absoluteUrlTo(ProjectAccessCtrl::class, $event->tokenId());

        $mailView = new MailView('mail/project_access_invitation', 'elca');
        $mailView->assign('invitationUrl', (string) $url);
        $mailView->assign('senderName', $owner->getFullname());
        $mailView->assign('projectName', $project->getName());
        $mailView->process();

        $this->sendMail(t('eLCA | Einladung zur Mitwirkung an einem Projekt'), $event->userEmail(), $mailView);
    }

    private function sendMail($subject, $to, $content)
    {
        $this->mailer->setSubject($subject);
        $this->mailer->setHtmlContent((string)$content);
        $this->mailer->send($to);
    }
}
