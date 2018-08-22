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
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\Group;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\UserSet;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectboxChooser;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaGroupView extends HtmlView
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
        $Content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-general users']));

        $Form = new HtmlForm('groupForm', '/groups/save/');
        $Group = new \stdClass;

        if($this->Group && $this->Group->isInitialized())
        {
            $Form->add(new HtmlHiddenField('id', $this->Group->getId()));

            $Group->name = $this->Group->getName();

            // roles
            $Group->roleAdmin = $this->Group->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_ADMIN);
            $Group->roleStandard = $this->Group->hasRole(Elca::ELCA_ROLES, Elca::ELCA_ROLE_STANDARD);
            $Form->setDataObject($Group);
        }
        else
        {
            $Group->roleStandard = true;
        }

        $Form->setAttribute('id', 'groupForm');
        $Form->addClass('highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Form->add(new ElcaHtmlFormElementLabel(t('Gruppenname'), new HtmlTextInput('name'), true));

        $Users = UserSet::findByGroupId($this->Group->getId());
        $AllUsers = UserSet::find(null, ['fullname' => 'ASC']);

        $SelectBoxChooser = new HtmlSelectboxChooser('id', 'fullname');
        $SelectBoxChooser->setDbObjectSets($AllUsers, $Users);
        $SelectBoxChooser->setCaptions(t('Verfügbare Nutzer'),t('Mitglieder'));

        $GroupsChooser = $Form->add(new HtmlFormGroup(t('Gruppenmitglieder')));
        $GroupsChooser->addClass('column');
        $GroupsChooser->add($SelectBoxChooser);

        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        $Form->appendTo($Content);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaGroupView
