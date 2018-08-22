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

use Elca\Model\Project\ConfirmedProjectAccessToken;
use Elca\Model\Project\ProjectAccessToken;
use Elca\Model\Project\UnconfirmedProjectAccessToken;
use Elca\Model\Project\UnconfirmedProjectAccessTokenCreated;

class ProjectAccessTokenDto
{
    public $tokenId;
    public $userId;
    public $userEmail;
    public $canEdit;
    public $createdAt;
    public $confirmedAt;

    /**
     * @param ProjectAccessToken $accessToken
     * @return static
     */
    public static function build(ProjectAccessToken $accessToken)
    {
        $dto = new static();
        $dto->tokenId = $accessToken->tokenId()->value();
        $dto->userId = $accessToken instanceof ConfirmedProjectAccessToken ? $accessToken->userId()->value() : null;
        $dto->userEmail = $accessToken->userEmail()->value();
        $dto->canEdit = $accessToken->canEdit();
        $dto->createdAt = $accessToken instanceof UnconfirmedProjectAccessToken ? $accessToken->createdAt() : null;
        $dto->confirmedAt = $accessToken instanceof ConfirmedProjectAccessToken ? $accessToken->confirmedAt() : null;

        return $dto;
    }

    /**
     *
     */
    public function isConfirmed()
    {
        return null !== $this->confirmedAt;
    }

}
