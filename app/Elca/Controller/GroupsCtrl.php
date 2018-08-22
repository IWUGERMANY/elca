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
namespace Elca\Controller;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Group;
use Beibob\Blibs\GroupMember;
use Beibob\Blibs\NestedNode;
use Beibob\Blibs\RoleMember;
use Beibob\Blibs\Url;
use Elca\Elca;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\View\ElcaAdminNavigationLeftView;
use Elca\View\ElcaBaseView;
use Elca\View\ElcaGroupRoleView;
use Elca\View\ElcaGroupsView;
use Elca\View\ElcaGroupView;
use Exception;

/**
 * Main groups controller
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class GroupsCtrl extends TabsCtrl
{
    /**
     * groupId
     */
    private $groupId;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if($this->hasBaseView())
            $this->getBaseView()->setContext(ElcaBaseView::CONTEXT_USERS);

        if(isset($args['initialAction']) && is_numeric($args['initialAction']))
            $this->groupId = (int)$args['initialAction'];

        $this->Osit->clear();

        // set active controller in navigation
        $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', $this->ident());
     }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Default action
     */
    protected function defaultAction()
    {
        if(!$this->isAjax())
            return;

        if(!$this->Access->hasAdminPrivileges())
            $this->noAccessRedirect('/users/'.$this->Access->getUserId().'/');

        else
        {
            if(is_numeric($this->getAction()))
            {
                $this->userId = (int)$this->getAction();
                $Group = Group::findById((int)$this->getAction());
                if($Group->isInitialized())
                {
                    $this->addTabItem('general', t('Allgemein'), null, 'Elca\Controller\GroupsCtrl', 'general', ['g' => $Group->getId()]);
                    $this->addTabItem('role', t('Rollen'), null, 'Elca\Controller\GroupsCtrl', 'role', ['g' => $Group->getId()]);

                    $this->invokeTabActionController();

                    $this->Osit->add(new ElcaOsitItem(t('Gruppen'), '/groups/', t('Nutzerverwaltung')));
                    $this->Osit->add(new ElcaOsitItem($Group->getName(), null, t('Gruppe')));
                }
            }
            else
            {
                $this->setView(new ElcaGroupsView());
                $this->Osit->add(new ElcaOsitItem(t('Gruppe'), null, t('Nutzerverwaltung')));
            }
        }
    }
    // End defaultAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Save action
     */
    protected function saveAction()
    {
        if(!$this->Access->hasAdminPrivileges())
            $this->noAccessRedirect('/users/'.$this->Access->getUserId().'/');

        else
        {
            if(!$this->Request->isMethodPost())
               return;

            $Validator = new ElcaValidator($this->Request);

            $Validator->assertNotEmpty('name', null, t('Kein Gruppenname angegeben'));

            if($Validator->isValid())
            {
                $Dbh = DbHandle::getInstance();

                try
                {
                    $Dbh->begin();
                    $Group = Group::findById($this->Request->id);

                    if($Group->isInitialized())
                    {
                        $Group->name = $this->Request->name;
                        $Group->update();

                        if(!is_null($this->Request->added))
                        {
                            $addedMembers = explode(',',$this->Request->added);
                            foreach ($addedMembers as $key => $userId)
                                if(!GroupMember::exists($Group->getId(), $userId, true))
                                    GroupMember::create($userId , $Group->getId());
                        }

                        if(!is_null($this->Request->removed))
                        {
                            $removedMembers = explode(',',$this->Request->removed);
                            foreach ($removedMembers as $key => $userId)
                            {
                                $toDelete = GroupMember::findByPk($Group->getId() ,$userId );
                                $toDelete->delete();
                            }
                        }
                    }
                    else
                    {
                        $Group = Group::create($this->Request->name);

                        /**
                        * Render view by default action
                        */
                       $this->getView()->assign('redirect', '/groups/'. $Group->getId() .'/');
                    }

                    $Dbh->commit();
                    $this->messages->add(t('Änderungen wurden übernommen'), ElcaMessages::TYPE_NOTICE);
                }
                catch (Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }
            }
            else
            {
                foreach($Validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }
    }
    // End saveAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Save action
     */
    protected function saveRoleAction()
    {
        if(!$this->Access->hasAdminPrivileges())
            $this->noAccessRedirect('/users/'.$this->Access->getUserId().'/');

        else
        {
            if(!$this->Request->isMethodPost())
               return;

            $hasRoles = $this->Request->getArray('roles');

            $Validator = new ElcaValidator($this->Request);
            if($Validator->isValid())
            {
                $Dbh = DbHandle::getInstance();

                try
                {
                    $Dbh->begin();
                    $Group = Group::findById($this->Request->id);

                    $RootRoleNode = NestedNode::findRootByIdent(Elca::ELCA_ROLES);
                    foreach($RootRoleNode->getChildNodes() as $RoleNode)
                    {
                        $hasRole = RoleMember::isGranted($RoleNode->getId(), $Group->getId());

                        if(isset($hasRoles[$RoleNode->getIdent()]) && !$hasRole)
                            RoleMember::grant($RoleNode->getId(), $Group->getId());

                        elseif(!isset($hasRoles[$RoleNode->getIdent()]) && $hasRole)
                            RoleMember::cease($RoleNode->getId(), $Group->getId());
                    }

                    $Dbh->commit();
                    $this->messages->add(t('Änderungen wurden übernommen'), ElcaMessages::TYPE_NOTICE);
                }
                catch (Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }
            }
            else
            {
                foreach($Validator->getErrors() as $error)
                    $this->messages->add($error, ElcaMessages::TYPE_ERROR);
            }
        }
    }
    // End saveRoleAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a group
     */
    protected function deleteAction()
    {
        if(!is_numeric($this->Request->id))
            return;

        /**
         * Check if user has admin privileges
         */
        $Group = Group::findById($this->Request->id);
        if(!$this->Access->hasAdminPrivileges())
            return;

        if($this->Request->has('confirmed'))
        {
            if($Group->isInitialized())
            {
                $Dbh = DbHandle::getInstance();
                try
                {
                    $Dbh->begin();
                    $Group->delete();
                    $Dbh->commit();

                    $this->messages->add(t('Die Gruppe wurde gelöscht'));
                }
                catch(Exception $Exception)
                {
                    $Dbh->rollback();
                    throw $Exception;
                }

                /**
                 * Forward to list
                 */
                $this->defaultAction();
            }
        }
        else
        {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(t('Soll die Gruppe wirklich gelöscht werden?'), ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Create action
     */
    protected function createAction()
    {
        $this->addTabItem('general', t('Allgemein'), null, 'Elca\Controller\GroupsCtrl', 'general');
        $View = $this->setView(new ElcaGroupView());
    }
    // End createAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * general action
     */
    protected function generalAction()
    {
        $groupId = $this->groupId? $this->groupId : $this->Request->g;

        $View = $this->setView(new ElcaGroupView());
        $View->assign('groupId', $groupId);
    }
    // End generalAction

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * role action
     */
    protected function roleAction()
    {
        $View = $this->setView(new ElcaGroupRoleView());
        $View->assign('groupId', $this->Request->g);
    }
    // End roleAction

    //////////////////////////////////////////////////////////////////////////////////////
    // private
    //////////////////////////////////////////////////////////////////////////////////////
}
// End GroupsCtrl
