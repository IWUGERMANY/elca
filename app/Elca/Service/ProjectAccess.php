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

namespace Elca\Service;

use Beibob\Blibs\Environment;
use Beibob\Blibs\SessionNamespace;
use Elca\Db\ElcaProject;
use Elca\Security\EncryptedPassword;

class ProjectAccess
{
    const NAMESPACE_NAME = 'elca.project.access';

    /**
     * @var SessionNamespace
     */
    private $namespace;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->namespace = $environment->getSession()->getNamespace(self::NAMESPACE_NAME, true);
    }

    /**
     * @param ElcaProject $project
     */
    public function updateEncryptedPasswordInSessionForProject(ElcaProject $project)
    {
        $hashes = $this->passwordHashes();

        if ($project->hasPassword()) {
            $hashes[$project->getId()] = (string)$project->getPassword();
        } else {
            unset($hashes[$project->getId()]);
        }

        $this->namespace->passwordHashes = $hashes;
    }

    /**
     * @param ElcaProject $project
     * @return EncryptedPassword|null
     */
    public function retrieveEncryptedPasswordFromSessionForProject(ElcaProject $project)
    {
        $hashes = $this->passwordHashes();

        return isset($hashes[$project->getId()])
            ? new EncryptedPassword($hashes[$project->getId()])
            : null;
    }

    /**
     *
     */
    public function clear()
    {
        $this->namespace->passwordHashes = [];
    }


    /**
     * @return array
     */
    private function passwordHashes()
    {
        $hashes = isset($this->namespace->passwordHashes) && is_array($this->namespace->passwordHashes)
            ? $this->namespace->passwordHashes
            : [];

        return $hashes;
    }
}
