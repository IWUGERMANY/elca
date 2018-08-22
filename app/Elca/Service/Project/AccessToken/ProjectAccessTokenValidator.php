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

use Beibob\Blibs\Interfaces\Request;
use Beibob\Blibs\Validator;
use Elca\Model\Common\Email;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Model\Project\ProjectId;

class ProjectAccessTokenValidator extends Validator
{
    /**
     * @var ProjectAccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * ProjectAccessTokenValidator constructor.
     *
     * @param ProjectAccessTokenRepository $accessTokenRepository
     */
    public function __construct(ProjectAccessTokenRepository $accessTokenRepository)
    {
        parent::__construct();

        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * @param Request $request
     */
    public function assertInvitationCommand(Request $request, string $projectOwnerEmail)
    {
        $this->setDataObject($request);

        $this->assertNotEmpty('grantAccessToEmail', null, t('Bitte geben Sie eine E-Mailadresse an'));
        $this->assertEmail('grantAccessToEmail', null, false, t('Die E-Mailadresse ist ungültig'));

        if (!$this->isValid()) {
            return false;
        }

        $email = new Email($this->getValue('grantAccessToEmail'));

        $this->assertTrue('grantAccessToEmail', false === $email->isEquivalent(new Email($projectOwnerEmail)), t('Diese E-Mailadresse wird von Ihnen selbst verwendet'));

        $projectId = new ProjectId($this->getValue('projectId'));

        return $this->assertTrue(
            'grantAccessToEmail',
            false === $this->accessTokenRepository->tokenWithEmailExistsForProject(
                $projectId,
                $email
            ),
            t('Eine Freigabe für diese E-Mailadresse existiert bereits.')
        );
    }
}
