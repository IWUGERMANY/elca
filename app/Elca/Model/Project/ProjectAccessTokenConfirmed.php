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

use Elca\Model\Common\Token\TokenId;
use Elca\Model\Event\Event;
use Elca\Model\User\UserId;

class ProjectAccessTokenConfirmed implements Event
{
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
     * @var bool
     */
    private $canEdit;

    /**
     * @var \DateTimeImmutable
     */
    private $confirmedAt;

    /**
     * ProjectAccessTokenConfirmed constructor.
     *
     * @param TokenId            $tokenId
     * @param ProjectId          $projectId
     * @param UserId             $userId
     * @param bool               $canEdit
     * @param \DateTimeImmutable $confirmedAt
     */
    public function __construct(TokenId $tokenId, ProjectId $projectId, UserId $userId, $canEdit, \DateTimeImmutable $confirmedAt)
    {
        $this->tokenId = $tokenId;
        $this->projectId = $projectId;
        $this->userId = $userId;
        $this->canEdit = $canEdit;
        $this->confirmedAt = $confirmedAt;
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
