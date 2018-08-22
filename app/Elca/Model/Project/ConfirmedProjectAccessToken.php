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
namespace Elca\Model\Project;

use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\Event\EventGeneratorTrait;
use Elca\Model\User\UserId;

class ConfirmedProjectAccessToken implements ProjectAccessToken
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
     * @var UserId
     */
    private $userId;

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
    private $confirmedAt;

    /**
     * AccessToken constructor.
     *
     * @param UnconfirmedProjectAccessToken|string $accessToken
     * @param UserId                               $userId
     * @param Email                                $userEmail
     * @param bool                                 $canEdit
     * @param \DateTimeImmutable                   $confirmedAt
     */
    public function __construct(
        UnconfirmedProjectAccessToken $accessToken,
        UserId $userId,
        Email $userEmail,
        $canEdit = false,
        \DateTimeImmutable $confirmedAt = null
    ) {
        $this->tokenId     = $accessToken->tokenId();
        $this->projectId   = $accessToken->projectId();
        $this->userId      = $userId;
        $this->userEmail = $userEmail;
        $this->confirmedAt = null === $confirmedAt ? new \DateTimeImmutable('now') : null;
        $this->canEdit     = $canEdit;

        $this->addEvent(
            new ProjectAccessTokenConfirmed($this->tokenId, $this->projectId, $this->userId, $this->canEdit, $this->confirmedAt)
        );
    }

    /**
     *
     */
    public function allowEdit()
    {
        $this->canEdit = true;
    }

    /**
     *
     */
    public function denyEdit()
    {
        $this->canEdit = false;
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
     * @return UserId
     */
    public function userId()
    {
        return $this->userId;
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
    public function confirmedAt()
    {
        return $this->confirmedAt;
    }
}
