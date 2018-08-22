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
use Beibob\Blibs\Group;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\NestedNode;
use Beibob\Blibs\Role;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElementLabel;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @author     Fabian Moeller <fab@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 *
 * $Id $
 */
class ElcaGroupRoleView extends HtmlView
{
    /**
     * Active group
     */
    private $Group;

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

        $this->Group = Group::findById($this->get('groupId'));
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

        $Form = new HtmlForm('roleForm', '/groups/saveRole/');
        $Group = new \stdClass;
        $Group->roles = [];

        $RootRoleNode = NestedNode::findRootByIdent(Elca::ELCA_ROLES);

        if($this->Group && $this->Group->isInitialized())
        {
            $Form->add(new HtmlHiddenField('id', $this->Group->getId()));

            foreach($RootRoleNode->getChildNodes() as $RoleNode)
                $Group->roles[$RoleNode->getIdent()] = $this->Group->hasRole(Elca::ELCA_ROLES, $RoleNode->getIdent());

            $Form->setDataObject($Group);
        }

        $Form->setAttribute('id', 'roleForm');
        $Form->addClass('highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Roles = $Form->add(new HtmlFormGroup(t('Verfügbare Rollen')));

        foreach($RootRoleNode->getChildNodes() as $RoleNode)
        {
            $Role = Role::findByNodeId($RoleNode->getId());
            $Roles->add(new HtmlFormElementLabel(t($Role->getRoleName()), new HtmlCheckbox('roles['.$RoleNode->getIdent().']')));
        }

        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));
        $Form->appendTo($Content);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaGroupRoleView
