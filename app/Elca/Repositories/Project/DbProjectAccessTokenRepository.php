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

namespace Elca\Repositories\Project;

use Elca\Db\ElcaProjectAccessToken;
use Elca\Db\ElcaProjectAccessTokenSet;
use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\Project\ConfirmedProjectAccessToken;
use Elca\Model\Project\ProjectAccessToken;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Model\Project\ProjectId;
use Elca\Model\Project\UnconfirmedProjectAccessToken;
use Elca\Model\User\UserId;
use Utils\Model\FactoryHelper;

class DbProjectAccessTokenRepository implements ProjectAccessTokenRepository
{
    public function findById(TokenId $tokenId): ?ProjectAccessToken
    {
        $token = ElcaProjectAccessToken::findByToken($tokenId);

        if (!$token->isValid()) {
            return null;
        }

        return $token->isConfirmed()
            ? $this->buildConfirmed($token)
            : $this->buildUnconfirmed($token);
    }

    public function findUnconfirmedToken(TokenId $tokenId): ?UnconfirmedProjectAccessToken
    {
        $token = ElcaProjectAccessToken::findByToken($tokenId);

        if (!$token->isValid() || true === $token->isConfirmed()) {
            return null;
        }

        return $this->buildUnconfirmed($token);
    }

    public function findConfirmedToken(TokenId $tokenId): ?ConfirmedProjectAccessToken
    {
        $token = ElcaProjectAccessToken::findByToken($tokenId);

        if (!$token->isValid() || false === $token->isConfirmed()) {
            return null;
        }

        return $this->buildConfirmed($token);
    }

    public function hasConfirmedTokensForProject(ProjectId $projectId): bool
    {
        return ElcaProjectAccessTokenSet::dbCount(
                ['project_id' => $projectId->value(), 'is_confirmed' => true]
            ) > 0;
    }

    public function findConfirmedTokensForProjectId(ProjectId $projectId): array
    {
        $tokens = ElcaProjectAccessTokenSet::find(
            ['project_id' => $projectId->value(), 'is_confirmed' => true],
            ['modified' => 'ASC']
        );

        $result = [];
        foreach ($tokens as $token) {
            $result[] = $this->buildConfirmed($token);
        }

        return $result;
    }

    public function findConfirmedTokenForProjectIdAndUserId(ProjectId $projectId, UserId $userId
    ): ?ConfirmedProjectAccessToken {
        $token = ElcaProjectAccessToken::findByProjectIdAndUserId($projectId->value(), $userId->value());

        if (!$token->isValid() || false === $token->isConfirmed()) {
            return null;
        }

        return $this->buildConfirmed($token);
    }

    public function findUnconfirmedTokensForProjectId(ProjectId $projectId): array
    {
        $tokens = ElcaProjectAccessTokenSet::find(
            ['project_id' => $projectId->value(), 'is_confirmed' => false],
            ['created' => 'ASC']
        );

        $result = [];
        foreach ($tokens as $token) {
            $result[] = $this->buildUnconfirmed($token);
        }

        return $result;
    }

    public function tokenWithEmailExistsForProject(ProjectId $projectId, Email $email): bool
    {
        return ElcaProjectAccessTokenSet::dbCount(
                ['project_id' => $projectId->value(), 'user_email' => \utf8_strtolower($email->value())]
            ) > 0;
    }


    public function add(UnconfirmedProjectAccessToken $accessToken): void
    {
        ElcaProjectAccessToken::create(
            $accessToken->tokenId()->value(),
            $accessToken->projectId()->value(),
            \utf8_strtolower($accessToken->userEmail()->value()),
            null,
            $accessToken->canEdit(),
            false
        );
    }

    public function save(ConfirmedProjectAccessToken $accessToken): void
    {
        $dbToken = ElcaProjectAccessToken::findByToken($accessToken->tokenId()->value());

        if (!$dbToken->isValid()) {
            return;
        }

        $dbToken->setCanEdit($accessToken->canEdit());

        if (false === $dbToken->isConfirmed()) {
            $dbToken->setIsConfirmed(true);
            $dbToken->setUserId($accessToken->userId()->value());
        }

        $dbToken->update();
    }

    public function remove(ProjectAccessToken $token): void
    {
        $dbToken = ElcaProjectAccessToken::findByToken($token->tokenId()->value());

        if (!$dbToken->isValid()) {
            return;
        }

        $dbToken->delete();
    }

    private function buildUnconfirmed(ElcaProjectAccessToken $token): UnconfirmedProjectAccessToken
    {
        return FactoryHelper::createInstanceWithoutConstructor(
            UnconfirmedProjectAccessToken::class,
            [
                'tokenId'   => new TokenId($token->getToken()),
                'projectId' => new ProjectId($token->getProjectId()),
                'userEmail' => new Email($token->getUserEmail()),
                'canEdit'   => (bool)$token->getCanEdit(),
                'createdAt' => new \DateTimeImmutable($token->getCreated()),
            ]
        );
    }

    private function buildConfirmed(ElcaProjectAccessToken $token): ConfirmedProjectAccessToken
    {
        return FactoryHelper::createInstanceWithoutConstructor(
            ConfirmedProjectAccessToken::class,
            [
                'tokenId'     => new TokenId($token->getToken()),
                'projectId'   => new ProjectId($token->getProjectId()),
                'userEmail'   => new Email($token->getUserEmail()),
                'userId'      => new UserId($token->getUserId()),
                'canEdit'     => (bool)$token->getCanEdit(),
                'confirmedAt' => new \DateTimeImmutable($token->getModified()),
            ]
        );
    }
}
