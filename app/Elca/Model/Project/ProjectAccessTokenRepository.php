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

use Elca\Db\ElcaProjectAccessToken;
use Elca\Model\Common\Email;
use Elca\Model\Common\Token\TokenId;
use Elca\Model\User\UserId;

interface ProjectAccessTokenRepository
{
    /**
     * @param TokenId $token
     * @return UnconfirmedProjectAccessToken|ConfirmedProjectAccessToken|null
     */
    public function findById(TokenId $token);

    /**
     * @param TokenId $token
     * @return UnconfirmedProjectAccessToken|null
     */
    public function findUnconfirmedToken(TokenId $token);

    /**
     * @param TokenId $token
     * @return ConfirmedProjectAccessToken|null
     */
    public function findConfirmedToken(TokenId $token);

    /**
     * @param ProjectId $projectId
     * @return bool
     */
    public function hasConfirmedTokensForProject(ProjectId $projectId);

    /**
     * @param ProjectId $projectId
     * @return ConfirmedProjectAccessToken[]
     */
    public function findConfirmedTokensForProjectId(ProjectId $projectId);

    /**
     * @param ProjectId $projectId
     * @param UserId    $userId
     * @return ConfirmedProjectAccessToken
     */
    public function findConfirmedTokenForProjectIdAndUserId(ProjectId $projectId, UserId $userId);

    /**
     * @param ProjectId $projectId
     * @return UnconfirmedProjectAccessToken[]
     */
    public function findUnconfirmedTokensForProjectId(ProjectId $projectId);

    /**
     * @param ProjectId $projectId
     * @param Email     $email
     * @return bool
     */
    public function tokenWithEmailExistsForProject(ProjectId $projectId, Email $email);

    /**
     * @param UnconfirmedProjectAccessToken $accessToken
     */
    public function add(UnconfirmedProjectAccessToken $accessToken);

    /**
     * @param ConfirmedProjectAccessToken $accessToken
     */
    public function save(ConfirmedProjectAccessToken $accessToken);

    /**
     * @param ProjectAccessToken $token
     */
    public function remove(ProjectAccessToken $token);


}
