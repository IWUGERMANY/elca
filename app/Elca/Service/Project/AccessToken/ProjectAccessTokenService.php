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

declare(strict_types = 1);
namespace Elca\Service\Project\AccessToken;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Group;
use Beibob\Blibs\GroupMember;
use Beibob\Blibs\User;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectSet;
use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\Project\ConfirmedProjectAccessToken;
use Elca\Model\Project\InvalidUserForProjectAccessTokenException;
use Elca\Model\Project\ProjectAccessTokenEmailAddressDoesNotMatch;
use Elca\Model\Project\ProjectAccessTokenNotFoundException;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Model\Project\ProjectId;
use Elca\Model\Project\UnconfirmedProjectAccessToken;
use Elca\Model\User\UserId;
use Elca\Service\Event\EventPublisher;
use Elca\Service\ProjectAccess;

class ProjectAccessTokenService
{
    /**
     * @var ProjectAccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var EventPublisher
     */
    private $eventPublisher;

    /**
     * @var DbHandle
     */
    private $dbHandle;

    /**
     * ProjectAccessTokenService constructor.
     *
     * @param ProjectAccessTokenRepository $accessTokenRepository
     * @param DbHandle                     $dbHandle
     * @param EventPublisher               $eventPublisher
     */
    public function __construct(
        ProjectAccessTokenRepository $accessTokenRepository,
        DbHandle $dbHandle,
        EventPublisher $eventPublisher
    ) {
        $this->accessTokenRepository = $accessTokenRepository;
        $this->eventPublisher        = $eventPublisher;
        $this->dbHandle              = $dbHandle;
    }

    /**
     * @param TokenId $tokenId
     * @param UserId  $userId
     * @throws \Exception
     */
    public function confirmToken(TokenId $tokenId, UserId $userId)
    {
        $this->dbHandle->atomic(
            function () use ($tokenId, $userId) {

                $unconfirmedToken = $this->accessTokenRepository->findUnconfirmedToken($tokenId);

                if (null === $unconfirmedToken) {
                    throw new ProjectAccessTokenNotFoundException($tokenId);
                }

                /**
                 * If project owner and current user are the same do nothing
                 */
                if (ElcaProject::findById($unconfirmedToken->projectId()->value())->getOwnerId() === $userId->value()) {
                    throw new InvalidUserForProjectAccessTokenException($userId, $unconfirmedToken->tokenId());
                }

                $user = User::findById($userId->value());

                $confirmedToken = $unconfirmedToken->confirm($userId);
                $this->accessTokenRepository->save($confirmedToken);

                $accessGroup = $this->provideProjectGroup($confirmedToken->projectId());

                if (GroupMember::exists($accessGroup->getId(), $user->getId())) {
                    return;
                }

                $accessGroup->addUser($user);
                $this->eventPublisher->publishAll($confirmedToken->releaseEvents());
            }
        );
    }

    /**
     * @param ProjectId $projectId
     * @param Email     $userMail
     * @param bool      $canEdit
     * @throws \Exception
     */
    public function invite(ProjectId $projectId, Email $userMail, $canEdit = false)
    {
        $this->dbHandle->atomic(
            function () use ($projectId, $userMail, $canEdit) {
                $this->provideProjectGroup($projectId);

                $accessToken = new UnconfirmedProjectAccessToken(
                    TokenId::nextIdentity(),
                    $projectId,
                    $userMail,
                    $canEdit
                );

                $this->accessTokenRepository->add($accessToken);

                $this->eventPublisher->publishAll($accessToken->releaseEvents());
            }
        );
    }

    /**
     * @param TokenId $tokenId
     */
    public function toggleEditForToken(TokenId $tokenId)
    {
        $this->dbHandle->atomic(
            function () use ($tokenId) {

                $confirmedToken = $this->accessTokenRepository->findConfirmedToken($tokenId);

                if (null === $confirmedToken) {
                    throw new ProjectAccessTokenNotFoundException($tokenId);
                }

                if ($confirmedToken->canEdit()) {
                    $confirmedToken->denyEdit();
                }
                else {
                    $confirmedToken->allowEdit();
                }

                $this->accessTokenRepository->save($confirmedToken);
                $this->eventPublisher->publishAll($confirmedToken->releaseEvents());
            }
        );
    }


    /**
     * @param TokenId $tokenId
     */
    public function removeToken(TokenId $tokenId)
    {
        $this->dbHandle->atomic(
            function () use ($tokenId) {
                $token = $this->accessTokenRepository->findById($tokenId);

                if (null === $token) {
                    throw new ProjectAccessTokenNotFoundException($tokenId);
                }

                if ($token instanceof ConfirmedProjectAccessToken) {
                    GroupMember::findByPk(
                        ElcaProject::findById($token->projectId()->value())->getAccessGroupId(),
                        $token->userId()->value()
                    )->delete();
                }

                $this->accessTokenRepository->remove($token);

                $this->eventPublisher->publishAll($token->releaseEvents());
            }
        );
    }

    /**
     * @param ProjectId $projectId
     * @param UserId    $userId
     */
    public function removeAccessTokenForProjectAndUser(ProjectId $projectId, UserId $userId)
    {
        $token = $this->accessTokenRepository->findConfirmedTokenForProjectIdAndUserId($projectId, $userId);

        if (null === $token) {
            return;
        }

        $this->removeToken($token->tokenId());
    }

    /**
     * @param ProjectId $projectId
     * @return Group
     */
    private function provideProjectGroup(ProjectId $projectId)
    {
        $project = ElcaProject::findById($projectId->value());

        $accessGroup = $project->getAccessGroup();

        if (false === $accessGroup->isUsergroup()) {
            return $accessGroup;
        }

        $newAccessGroup = Group::create(sprintf('%s[%s]', $project->getName(), $project->getId()));
        $newAccessGroup->addUser($project->getOwner());

        ElcaProjectSet::reassignAccessGroupIdForProjectId($project->getId(), $accessGroup->getId(), $newAccessGroup->getId());
        ElcaElementSet::reassignAccessGroupIdForProjectId($project->getId(), $accessGroup->getId(), $newAccessGroup->getId());

        return $newAccessGroup;
    }
}
