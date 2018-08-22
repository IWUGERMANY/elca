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
use Elca\Model\Exception\AbstractException;
use Elca\Model\User\UserId;

class ProjectAccessTokenEmailAddressDoesNotMatch extends AbstractException
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
     * @var Email
     */
    private $email;

    /**
     * AbstractException constructor.
     *
     * @param TokenId $tokenId
     * @param UserId  $userId
     * @param Email   $email
     */
    public function __construct(TokenId $tokenId, UserId $userId, Email $email)
    {
        parent::__construct('Email address :email: does not match for token :token: and :userId:', [
            ':token:' => $tokenId,
            ':email:' => $email,
            ':userId:' => $userId,
        ]);

        $this->tokenId = $tokenId;
        $this->userId = $userId;
        $this->email = $email;
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

    /**
     * @return Email
     */
    public function email()
    {
        return $this->email;
    }
}
