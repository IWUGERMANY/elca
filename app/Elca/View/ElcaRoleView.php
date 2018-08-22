<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\NestedNode;
use Beibob\Blibs\Role;
use Beibob\Blibs\User;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElementLabel;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @author     Fabian Moeller <fab@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 *
 * $Id $
 */
class ElcaRoleView extends HtmlView
{
    /**
     * Active user
     */
    private $User;

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->User = User::findById($this->get('userId'));
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-role users']));

        $Form = new HtmlForm('roleForm', '/users/saveRole/');
        $User = new \stdClass;
        $User->roles = [];

        $RootRoleNode = NestedNode::findRootByIdent(Elca::ELCA_ROLES);

        if($this->User && $this->User->isInitialized())
        {
            $Form->add(new HtmlHiddenField('id', $this->User->getId()));

            foreach($RootRoleNode->getChildNodes() as $RoleNode)
                $User->roles[$RoleNode->getIdent()] = $this->User->hasRole(Elca::ELCA_ROLES, $RoleNode->getIdent());

            $Form->setDataObject($User);

        }
        else
            $User->roles[Elca::ELCA_ROLE_STANDARD] = true;

        $Form->setAttribute('id', 'roleForm');
        $Form->addClass('highlight-changes clearfix');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        if($this->User && $this->User->isInitialized())
        {
            $Roles = $Form->add(new HtmlFormGroup(t('Verfügbare Rollen')));
            $Roles->setAttribute('id', 'user-roles');
            $Roles->addClass('clearfix column user-roles');

            $readOnly = $this->User->getId() == ElcaAccess::getInstance()->getUserId();

            foreach($RootRoleNode->getChildNodes() as $RoleNode)
            {
                $Role = Role::findByNodeId($RoleNode->getId());
                $Roles->add(new HtmlFormElementLabel(t($Role->getRoleName()), new HtmlCheckbox('roles['.$RoleNode->getIdent().']', null, null, $readOnly)));
            }

            if(!$readOnly)
            {
                $ButtonGroup = $Form->add(new HtmlFormGroup(''));
                $ButtonGroup->addClass('clearfix buttons');
                $ButtonGroup->add(new ElcaHtmlSubmitButton('saveRole', t('Speichern'), true));
            }
        }

        $Form->appendTo($Content);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaRoleView
