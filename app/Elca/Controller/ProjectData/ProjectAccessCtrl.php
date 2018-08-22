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

declare(strict_types=1);

namespace Elca\Controller\ProjectData;

use Beibob\Blibs\Url;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Elca\Controller\AppCtrl;
use Elca\Controller\IndexCtrl;
use Elca\Controller\ProjectsCtrl;
use Elca\Db\ElcaProject;
use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\Project\InvalidUserForProjectAccessTokenException;
use Elca\Service\Messages\ElcaMessages;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Model\Project\ProjectAccessTokenNotFoundException;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Model\Project\ProjectId;
use Elca\Model\Project\UnconfirmedProjectAccessToken;
use Elca\Model\User\UserId;
use Elca\Service\Event\EventPublisher;
use Elca\Service\Messages\FlashMessages;
use Elca\Service\Project\AccessToken\ProjectAccessTokenDto;
use Elca\Service\Project\AccessToken\ProjectAccessTokenService;
use Elca\Service\Project\AccessToken\ProjectAccessTokenValidator;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaProjectNavigationLeftView;
use Elca\View\ElcaProjectNavigationView;
use Elca\View\MailView;
use Elca\View\ProjectData\ProjectAccessView;
use phpmailerException;
use Symfony\Component\CssSelector\Parser\Token;

class ProjectAccessCtrl extends AppCtrl
{
    /**
     * @var ProjectAccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var ProjectId
     */
    private $projectId;

    /**
     *
     */
    public function showInvitationMailAction()
    {
        if (!$this->Access->hasAdminPrivileges() || $this->isAjax()) {
            return;
        }

        $mailView = $this->setBaseView(new MailView('mail/project_access_invitation', 'elca'));
        $mailView->assign('invitationUrl', (string)$this->Request->getUri());
        $mailView->assign('senderName', $this->Access->getUser()->getFullname());
        $mailView->assign('projectName', $this->Elca->getProject()->getName());
    }

    /**
     *
     */
    protected function defaultAction()
    {
        try {
            $tokenId = new TokenId($this->getAction());

            $this->get(ProjectAccessTokenService::class)
                 ->confirmToken(
                     $tokenId,
                     new UserId($this->Access->getUserId())
                 );

            $confirmedToken = $this->accessTokenRepository->findConfirmedToken($tokenId);

            $this->flashMessages->add(
                t(
                    'Sie haben ab sofort Zugriff auf das Projekt :projectName:',
                    null,
                    [
                        ':projectName:' => ElcaProject::findById($confirmedToken->projectId()->value())->getName(),
                    ]
                ),
                ElcaMessages::TYPE_INFO
            );
        }
        catch (ProjectAccessTokenNotFoundException $exception) {
            $this->get(FlashMessages::class)
                 ->add(t($exception->messageTemplate(), null, $exception->parameters()), ElcaMessages::TYPE_ERROR);

            $this->redirect(IndexCtrl::class);

            return;
        }
        catch (InvalidUserForProjectAccessTokenException $exception) {
            $this->redirect(IndexCtrl::class);

            return;
        }

        $this->redirect(ProjectsCtrl::class);
    }

    /**
     * @param ProjectAccessTokenValidator $validator
     * @param bool                        $addNavigationViews
     */
    protected function tokensAction(ProjectAccessTokenValidator $validator = null, $addNavigationViews = true)
    {
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        $data = (object)[
            'projectId'         => $this->projectId,
            'unconfirmedTokens' => [],
            'confirmedTokens'   => [],
        ];

        foreach ($this->accessTokenRepository->findUnconfirmedTokensForProjectId($this->projectId) as $accessToken) {
            $data->unconfirmedTokens[] = ProjectAccessTokenDto::build($accessToken);
        }

        foreach ($this->accessTokenRepository->findConfirmedTokensForProjectId($this->projectId) as $accessToken) {
            $data->confirmedTokens[] = ProjectAccessTokenDto::build($accessToken);;
        }

        $view = $this->setView(new ProjectAccessView());
        $view->assign('data', $data);

        if (null !== $validator) {
            $view->assign('validator', $validator);
        }

        $this->Osit->add(new ElcaOsitItem(t('Projektfreigaben'), null, t('Stammdaten')));

        /**
         * Render complete navigation on reload
         */
        if ($addNavigationViews) {
            $view = $this->addView(new ElcaProjectNavigationView());
            $view->assign('activeCtrlName', get_class());

            $this->addView(new ElcaProjectNavigationLeftView());
        }
    }

    /**
     *
     */
    protected function saveAction()
    {
        if (!$this->Request->isPost()) {
            return;
        }

        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if ($this->Request->has('addAccess')) {
            $validator = $this->get(ProjectAccessTokenValidator::class);
            $validator->assertInvitationCommand($this->Request, $this->Access->getUser()->getEmail());

            if ($validator->isValid()) {

                try {
                    $this->get(ProjectAccessTokenService::class)
                         ->invite(
                             $this->projectId,
                             new Email($this->Request->grantAccessToEmail),
                             $this->Request->has('canEdit')
                         );

                    $this->messages->add(
                        t('Die Freigabe wurde erteilt. Der Anwender wurde per E-Mail informiert'),
                        ElcaMessages::TYPE_INFO
                    );

                    $this->tokensAction($validator, false);
                }
                catch (phpmailerException $exception) {
                    $this->messages->add(
                        t(
                            'An die Adresse :email: konnte keine E-Mail versendet werden. Bitte prüfen Sie die Schreibweise.',
                            null,
                            [':email:' => $this->Request->getString('grantAccessToEmail')]
                        ),
                        ElcaMessages::TYPE_ERROR
                    );
                }

            } else {
                foreach ($validator->getErrors() as $message) {
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);

                    $this->tokensAction($validator, false);
                }
            }
        }
    }

    /**
     *
     */
    protected function toggleEditAction()
    {
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$tokenId = $this->Request->getString('tokenId')) {
            return;
        }

        $this->get(ProjectAccessTokenService::class)
             ->toggleEditForToken(new TokenId($tokenId));

        $this->messages->add(t('Die Berechtigung wurden geändert'), ElcaMessages::TYPE_INFO);

        $this->tokensAction(null, false);
    }


    /**
     *
     */
    protected function removeAction()
    {
        if (!$this->checkProjectAccess()) {
            return;
        }

        if (!$this->Access->isProjectOwnerOrAdmin($this->Elca->getProject())) {
            return;
        }

        if (!$tokenId = $this->Request->getString('tokenId')) {
            return;
        }

        if ($this->Request->has('confirmed')) {
            $this->get(ProjectAccessTokenService::class)
                 ->removeToken(new TokenId($tokenId));

            $this->messages->add(t('Die Berechtigung wurden geändert'), ElcaMessages::TYPE_INFO);

            $this->tokensAction(null, false);

        } else {
            $url = Url::parse($this->Request->getURI());
            $url->addParameter(['confirmed' => null]);

            if (!$token = $this->accessTokenRepository->findById(new TokenId($tokenId))) {
                return;
            }

            $this->messages->add(
                t(
                    'Soll die Freigabe für :userMail: wirklich entfernt werden?',
                    null,
                    [':userMail:' => $token->userEmail()]
                ),
                ElcaMessages::TYPE_CONFIRM,
                (string)$url
            );
        }
    }

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->projectId             = $this->Elca->hasProjectId() ? new ProjectId($this->Elca->getProjectId()) : null;
        $this->accessTokenRepository = $this->container->get(ProjectAccessTokenRepository::class);
    }
}
