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

namespace Elca\Model\Project;

use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\Event\EventGeneratorTrait;
use Elca\Model\User\UserId;

class UnconfirmedProjectAccessToken implements ProjectAccessToken
{
    use EventGeneratorTrait;

    /**
     * @var TokenId
     */
    private $tokenId;

    /**
     * @var ProjectId
     */
    private $projectId;

    /**
     * @var Email
     */
    private $userEmail;

    /**
     * @var bool
     */
    private $canEdit;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * UnconfirmedAccessToken constructor.
     *
     * @param TokenId            $token
     * @param ProjectId          $projectId
     * @param Email              $userEmail
     * @param bool               $canEdit
     * @param \DateTimeImmutable $createdAt
     */
    public function __construct(
        TokenId $token,
        ProjectId $projectId,
        Email $userEmail,
        $canEdit = false,
        \DateTimeImmutable $createdAt = null
    ) {
        $this->tokenId   = $token;
        $this->projectId = $projectId;
        $this->userEmail = $userEmail;
        $this->createdAt = null === $createdAt ? new \DateTimeImmutable('now') : $createdAt;
        $this->canEdit   = (bool)$canEdit;

        $this->addEvent(
            new UnconfirmedProjectAccessTokenCreated($this->tokenId, $this->projectId, $this->userEmail, $this->canEdit)
        );
    }

    /**
     * @param UserId $userId
     * @return ConfirmedProjectAccessToken
     */
    public function confirm(UserId $userId)
    {
        return new ConfirmedProjectAccessToken(
            $this,
            $userId,
            $this->userEmail,
            $this->canEdit
        );
    }

    /**
     * @return TokenId
     */
    public function tokenId()
    {
        return $this->tokenId;
    }

    /**
     * @return ProjectId
     */
    public function projectId()
    {
        return $this->projectId;
    }

    /**
     * @return Email
     */
    public function userEmail()
    {
        return $this->userEmail;
    }

    /**
     * @return bool
     */
    public function canEdit()
    {
        return $this->canEdit;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function createdAt()
    {
        return $this->createdAt;
    }

    /**
     * @return null
     */
    public function userId()
    {
        return null;
    }
}
