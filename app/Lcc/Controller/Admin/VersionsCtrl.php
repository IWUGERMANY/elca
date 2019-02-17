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
namespace Lcc\Controller\Admin;

use Beibob\Blibs\Url;
use Elca\Controller\AppCtrl;
use Elca\Model\Navigation\ElcaOsitItem;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaAdminNavigationLeftView;
use Lcc\Db\LccProjectVersionSet;
use Lcc\Db\LccVersion;
use Lcc\Db\LccVersionSet;
use Lcc\LccModule;
use Lcc\View\Admin\LccVersionsView;

/**
 * LccVersions controller
 *
 * @package lcc
 * @author  Tobias Lode <tobias@beibob.de>
 */
class VersionsCtrl extends AppCtrl
{
    /**
     * Will be called on initialization
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init();

        // set active controller in navigation
        $this->addView(new ElcaAdminNavigationLeftView())->assign('activeCtrlName', $this->ident());
    }
    // End init


    /**
     * Default action
     *
     * @param  -
     *
     * @return -
     */
    protected function defaultAction($addNavigationViews = true, $Validator = null, $calcMethod = null)
    {
        if (!$this->isAjax())
            return;

        $calcMethod = $calcMethod !== null ? $calcMethod : (int)$this->Request->get('calcMethod');
        if (!in_array($calcMethod, [LccModule::CALC_METHOD_GENERAL, LccModule::CALC_METHOD_DETAILED], true)) {
            return;
        }

        $Data = new \stdClass();
        $Data->calcMethod = $calcMethod;
        foreach (LccVersionSet::find(['calc_method' => $calcMethod], ['id' => 'ASC']) as $Version) {
            $key = $Version->getId();
            $Data->name[$key] = $Version->getName();
            $Data->created[$key] = date_create($Version->getCreated())->format('d.m.Y, H:i');
        }

        $View = $this->addView(new LccVersionsView());
        $View->assign('Data', $Data);
        $View->assign('currentVersionId', LccVersion::findRecent($calcMethod)->getId());

        if ($Validator)
            $View->assign('Validator', $Validator);

        /**
         * Add navigation
         */
        if ($addNavigationViews)
            $this->Osit->add(new ElcaOsitItem(t('LCC-Versionen verwalten'), null, t('LCC')));
    }
    // End defaultAction


    /**
     * Default action
     *
     * @param  -
     *
     * @return -
     */
    protected function saveAction()
    {
        if (!$this->isAjax() || !$this->Request->isPost())
            return;

        if ($this->Request->has('save')) {
            $Validator = new ElcaValidator($this->Request);

            $names = $this->Request->getArray('name');

            foreach ($names as $key => $name) {
                $Version = LccVersion::findById($key);

                if (!$Version->isInitialized())
                    continue;

                if ($Validator->assertNotEmpty('name[' . $key . ']', null, t('Der Name darf nicht leer bleiben'))) {
                    if ($name != $Version->getName()) {
                        $Version->setName(\trim($name));
                        $Version->update();
                    }
                }
            }

            /**
             * Check validator and add error messages
             */
            if (!$Validator->isValid()) {
                foreach ($Validator->getErrors() as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->defaultAction(false, $Validator, $this->Request->calcMethod);
        } elseif ($this->Request->has('cancel')) {
            $this->defaultAction(false, null, $this->Request->calcMethod);
        }
    }
    // End saveAction


    /**
     * Copy a version with all its data
     *
     * @param  -
     *
     * @return -
     */
    protected function copyAction()
    {
        if (!$this->isAjax() || !is_numeric($this->Request->id))
            return;

        $Version = LccVersion::findById($this->Request->id);
        if (!$Version->isInitialized())
            return;

        $Version->copy();

        $this->defaultAction(false, null, $Version->getCalcMethod());
    }
    // End copyAction


    /**
     * Deletes project costs
     *
     * @param  -
     *
     * @return -
     */
    protected function deleteAction()
    {
        if (!$this->isAjax() || !is_numeric($this->Request->id))
            return;

        $Version = LccVersion::findById($this->Request->id);
        if (!$Version->isInitialized())
            return;

        $calcMethod = $Version->getCalcMethod();
        if ($this->Request->has('confirmed')) {
            $Version->delete();
            $this->defaultAction(false, null, $calcMethod);
        } else {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $count = LccProjectVersionSet::dbCount(['version_id' => $Version->getId()]);

            $message = t('Diese Version wird in %count% %projects% verwendet.', null, ['%count%'    => ($count > 0 ? ($count == 1 ? t('einem') : $count) : t('keinem')),
                                                                                       '%projects%' => $count > 1 ? t('Projekten') : t('Projekt')])
                        . ' '
                       . t('Sind Sie sicher, dass Sie die Werte für "%version%" löschen wollen?', null, ['%version%' => $Version->getName()]);

            $this->messages->add($message, ElcaMessages::TYPE_CONFIRM, (string)$Url);
        }
    }
    // End deleteAction

}
// End ProjectDataCtrl