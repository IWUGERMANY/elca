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

namespace Elca\Security;

use Beibob\Blibs\Environment;
use Beibob\Blibs\GroupSet;
use Beibob\Blibs\Role;
use Beibob\Blibs\RoleMember;
use Beibob\Blibs\User;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectSet;
use Elca\Elca;
use Elca\Model\Project\ProjectAccessTokenRepository;
use Elca\Model\Project\ProjectId;
use Elca\Model\User\UserId;

/**
 * Helps with access permissions
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 */
class ElcaAccess
{
    /**
     * Singleton instance
     */
    private static $Instance;

    /**
     * Indicates if the current user has admin privileges
     */
    private $hasAdminPrivileges;

    /**
     * Curent user id
     */
    private $userId;

    /**
     * Group id of the current user
     */
    private $groupId;

    /**
     * @var array
     */
    private $memberInGroups;

    /**
     * @var array
     */
    private $userGroupIds;

    /**
     * @var ProjectAccessTokenRepository
     */
    private $projectAccessTokenRepository;

    /**
     * Returns the singelton
     *
     * @return ElcaAccess
     */
    public static function getInstance()
    {
        if (!self::$Instance) {
            self::$Instance = new ElcaAccess();
        }

        return self::$Instance;
    }
    // End getInstance

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->setUser(UserStore::getInstance()->getUser());

        $this->projectAccessTokenRepository = Environment::getInstance()->getContainer()->get(
            ProjectAccessTokenRepository::class
        );
    }
    // End hasAdminPrivileges

    /**
     * Current user has admin privileges
     *
     * @param  -
     *
     * @return boolean
     */
    public function hasAdminPrivileges()
    {
        if (null === $this->hasAdminPrivileges) {
            $this->hasAdminPrivileges =
                RoleMember::isGranted(
                    Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN)->getNodeId(),
                    $this->groupId
                );
        }

        return $this->hasAdminPrivileges;
    }

    /**
     * Current user has beta privileges
     *
     * @param  -
     *
     * @return boolean
     */
    public function hasBetaPrivileges()
    {
        if (!$this->groupId) {
            return false;
        }

        return RoleMember::isGranted(
            Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_BETA)->getNodeId(),
            $this->groupId
        );
    }
    // End isOrganisation

    /**
     * Checks if current user account is of type 'Organisation'
     *
     * @return bool
     * @throws \Beibob\Blibs\Exception
     */
    public function isOrganisation()
    {
        if (!$this->groupId) {
            return false;
        }

        if (!Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ORGA)->isInitialized()) {
            Role::create(Role::findRootByIdent(Elca::ELCA_ROLES)->getNode(), Elca::ELCA_ROLE_ORGA, 'Hochschule');
        }

        return RoleMember::isGranted(
            Role::findByIdent(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ORGA)->getNodeId(),
            $this->groupId
        );
    }
    // End hasRole

    /**
     * Current user has beta privileges
     *
     * @param  string $roleIdent
     *
     * @return boolean
     */
    public function hasRole($roleIdent)
    {
        if (!$this->groupId) {
            return false;
        }

        return RoleMember::isGranted(Role::findByIdent(Elca::ELCA_ROLES, $roleIdent)->getNodeId(), $this->groupId);
    }
    // End canAccessProject

    /**
     * Current user can access a project
     *
     * @param  ElcaProject      $project
     *
     * @param EncryptedPassword $encryptedPassword
     * @return bool
     */
    public function canAccessProject(ElcaProject $project, EncryptedPassword $encryptedPassword = null)
    {
        if (!$project->isInitialized()) {
            return false;
        }

        /**
         * Either user has admin privileges or project is owned by the current user
         */
        if ($this->hasAdminPrivileges()) {
            return true;
        }

        if ($project->hasPassword()) {
            if (null === $encryptedPassword) {
                return false;
            }

            if (!$encryptedPassword->equals($project->getPassword())) {
                return false;
            }
        }

        if ($project->getOwnerId() === $this->userId ||
            $project->getAccessGroupId() === $this->groupId) {
            return true;
        }

        return $this->isInGroup($project->getAccessGroupId());
    }

    /**
     * Current user can create a project
     */
    public function canCreateProject()
    {
        if ($this->hasAdminPrivileges() || $this->hasBetaPrivileges() || $this->isOrganisation()) {
            return true;
        }

        return ElcaProjectSet::countByOwnerId($this->getUserId()) < Elca::getInstance()->getProjectLimit();
    }

    /**
     * Current user can create a project
     *
     * @param ElcaProject $project
     * @return bool
     */
    public function canEditProject(ElcaProject $project)
    {
        if ($this->userId === $project->getOwnerId() || $this->hasAdminPrivileges()) {
            return true;
        }

        if ($this->isInGroup($project->getAccessGroupId())) {
            return $this->hasProjectAccessTokenAndCanEdit(new ProjectId($project->getId()));
        }

        return false;
    }

    /**
     * @param ElcaProject $project
     * @return bool
     */
    public function canDeleteProject(ElcaProject $project)
    {
        return $this->isProjectOwnerOrAdmin($project);
    }

    /**
     * @param ElcaProject $project
     * @return bool
     */
    public function isProjectOwnerOrAdmin(ElcaProject $project)
    {
        return $this->userId === $project->getOwnerId() || $this->hasAdminPrivileges();
    }
    // End canAccessElement

    /**
     * Current user can access an element
     *
     * @param  ElcaElement $element
     *
     * @return boolean
     */
    public function canAccessElement(ElcaElement $element)
    {
        if (!$element->isInitialized()) {
            return false;
        }

        /**
         * Either user has admin privileges or element is owned by the current user and is no reference element
         */
        return $this->hasAdminPrivileges() ||
               $element->isPublic() ||
               $element->getOwnerId() == $this->userId
               || $this->isInGroup($element->getAccessGroupId());
    }
    // End canEditElement

    /**
     * Current user can edit element
     *
     * @param  ElcaElement $element
     *
     * @return boolean
     */
    public function canEditElement(ElcaElement $element)
    {
        if (!$element->isInitialized()) {
            return false;
        }

        /**
         * Either user has admin privileges or element is owned by the current user and it is no reference element
         */
        if ($this->hasAdminPrivileges()) {
            return true;
        }

        if ($element->isPublic()) {
            return false;
        }

        if ($element->isTemplate()) {
            if ($element->getOwnerId() === $this->userId) {
                return true;
            }

            return false;
        }

        return $this->canEditProject($element->getProjectVariant()->getProject());
    }
    // End canProposeElement

    /**
     * Current user can propose an element
     *
     * @param  ElcaElement $element
     *
     * @return boolean
     */
    public function canProposeElement(ElcaElement $element)
    {
        if (!$element->isInitialized() || $this->hasAdminPrivileges() || $element->isPublic() ||
            !$this->hasRole(Elca::ELCA_ROLE_PROPOSE_ELEMENTS)
        ) {
            return false;
        }

        return $element->getAccessGroupId() == $this->groupId;
    }

    /**
     * Current user can edit users
     *
     * @param  User $User
     *
     * @return boolean
     */
    public function canEditUser(User $User)
    {
        if (!$User->isInitialized()) {
            return false;
        }

        if ($this->hasAdminPrivileges()) {
            return true;
        }

        if ($this->userId == $User->getId() && $this->isOrganisation()) {
            return false;
        }

        if ($this->userId == $User->getId()) {
            return true;
        }

        return false;
    }
    // End canEditFinalEnergySupplies

    /**
     * Current user can edit final energy supplies
     *
     * @return boolean
     */
    public function canEditFinalEnergySupplies()
    {
        return true;
    }
    // End getUserId

    /**
     * Current user id
     *
     * @param  -
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
    // End getUser

    /**
     * @return User
     */
    public function getUser()
    {
        return UserStore::getInstance()->getUser();
    }
    // End getUserGroupId

    /**
     * Current user group id
     *
     * @param  -
     *
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param $groupId
     * @return bool
     */
    public function isInGroup($groupId)
    {
        $memberInGroups = $this->memberInGroups();

        return isset($memberInGroups[$groupId]);
    }

    /**
     * @return array
     */
    public function getUserGroupIds()
    {
        if (null === $this->userGroupIds) {
            $this->userGroupIds = array_keys($this->memberInGroups());
        }

        return $this->userGroupIds;
    }

    /**
     * @param $user
     */
    public function setUser(User $user)
    {
        $this->userId  = (int)$user->getId();
        $this->groupId = (int)$user->getGroupId();
    }

    /**
     * @param ProjectId $projectId
     * @return bool
     * @internal param ElcaProject $project
     */
    private function hasProjectAccessTokenAndCanEdit(ProjectId $projectId)
    {
        $token = $this->projectAccessTokenRepository->findConfirmedTokenForProjectIdAndUserId(
            $projectId,
            new UserId($this->userId)
        );

        if (null !== $token && $token->canEdit()) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    private function memberInGroups()
    {
        if (null === $this->memberInGroups) {
            $this->memberInGroups = GroupSet::findByUserId($this->userId)->getArrayBy('name', 'id');

            $user                                      = UserStore::getInstance()->getUser();
            $this->memberInGroups[$user->getGroupId()] = $user->getAuthName();
        }

        return $this->memberInGroups;
    }
}
// End ElcaAccess
