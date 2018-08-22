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
use Elca\Model\Exception\AbstractException;
use Elca\Model\User\UserId;

class InvalidUserForProjectAccessTokenException extends AbstractException
{
    /**
     * @var TokenId
     */
    private $tokenId;

    /**
     * @var UserId
     */
    private $userId;

    /**
     * AbstractException constructor.
     *
     * @param UserId  $userId
     * @param TokenId $tokenId
     */
    public function __construct(UserId $userId, TokenId $tokenId)
    {
        parent::__construct('Token :token: could not be confirmed for this user :userId:', [':token:' => $tokenId, ':userId:' => $userId]);

        $this->tokenId = $tokenId;
        $this->userId = $userId;
    }

    /**
     * @return TokenId
     */
    public function tokenId()
    {
        return $this->tokenId;
    }

    /**
     * @return UserId
     */
    public function userId()
    {
        return $this->userId;
    }
}
