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

use Beibob\Blibs\BlibsDateTime;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAccessTokenSet;
use Elca\Security\ElcaAccess;

/**
 * Builds a project sheet
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProjectSheetView extends ElcaSheetView
{
    /**
     * Project
     *
     * @var ElcaProject
     */
    private $Project;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        // itemId
        $this->Project = ElcaProject::findById($this->get('itemId'));
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     *
     * @return -
     */
    protected function beforeRender()
    {
        $access = ElcaAccess::getInstance();
        $isProjectOwnerOrAdmin = $access->isProjectOwnerOrAdmin($this->Project);
        $canEditProject = $access->canEditProject($this->Project);

        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-project-sheet');

        $this->addFunction($canEditProject ? 'edit' : 'view', '/projects/edit/?id=$$itemId$$', $canEditProject ? t('Bearbeiten') : t('Ansehen'), 'default');

        if ($isProjectOwnerOrAdmin) {
            if ($this->get('canCreateProjects')) {
                $this->addFunction('copy', '/projects/copy/?id=$$itemId$$', t('Kopieren'), null, 'base');
            }
            $this->addFunction('export', '/projects/export/?id=$$itemId$$', t('Exportieren'));
        }

        if ($canEditProject) {
            $this->addFunction('recompute', '/projects/recompute/?id=$$itemId$$', t('Neu berechnen'));
        }

        if ($isProjectOwnerOrAdmin) {
            $this->addFunction('delete', '/projects/delete/?id=$$itemId$$', t('Löschen'));
        }

        if ($this->get('hasAdminPrivileges', false)) {
            if ($this->Project->isReference()) {
                $this->addClass($Container, 'reference');
                $this->addFunction('unstar', '/projects/markAsReference/?id=$$itemId$$&unset', t('Nicht mehr als Referenzprojekt markieren'));
            } else
                $this->addFunction('star', '/projects/markAsReference/?id=$$itemId$$', t('Als Referenzprojekt markieren'));
        }

        if ($this->get('isShared', false)) {
            $this->addFunction('cancel', '/projects/removeProjectAccess/?id=$$itemId$$', t('Freigabe entfernen'));
        }

        if ($this->Project->hasPassword() && !$access->passwordIsExpired($this->Project)) {
            $this->getHeadline()->appendChild($this->getSpan(null, ['class' => 'protected']));
            $this->addClass($Container, 'is-protected');
        }

        /**
         * Append individual content
         */
        if ($projectNr = $this->Project->getProjectNr())
            $this->addInfo($projectNr, t('Projektnummer'));

        if ($location = $this->get('location'))
            $this->addInfo($location, t('Standort'));

        $this->addInfo($this->get('constrMeasure'), t('Baumaßnahme'));
        $this->addInfo($this->Project->getLifeTime() . ' ' . t('Jahre'), t('Bilanzierungszeitraum'));
        $this->addInfo($this->Project->getProcessDb()->getName(), t('Datenbank'), null, true);

        $benchmarkSystemName = '-';
        if ($this->Project->getBenchmarkVersionId()) {
            $benchmarkVersion = $this->Project->getBenchmarkVersion();
            $benchmarkSystemName = $benchmarkVersion->getBenchmarkSystem()->getName().' '. $benchmarkVersion->getName();
        }
        $this->addInfo($benchmarkSystemName, t('Benchmarksystem'));

        if ($owner = $this->Project->getOwner()) {
            $dateTime = BlibsDateTime::factory($this->Project->getCreated());

            $ownerName = $owner->getId() !== $access->getUserId()
                ? $owner->getIdentifier()
                : t('Ihnen');

            $this->addInfo($ownerName, t('Erstellt von'), null, true);
            $this->addInfo($dateTime->getDate()->getDateString(t('DATETIME_FORMAT_DMY') . ', ')  .  $dateTime->getTime()->getTimeString(t('DATETIME_FORMAT_HI')).' '. t('Uhr'), t('am'));

            if ($owner->getGroupId() !== $this->Project->getAccessGroupId()) {

                if ($owner->getId() === $access->getUserId()) {

                    if (ElcaProjectAccessTokenSet::dbCount(['project_id' => $this->Project->getId(), 'is_confirmed' => true]) > 0) {
                        $this->addInfo(t('ja'), t('Freigegeben'), null, true);
                    }
                }
            }
        }
    }
    // End beforeRender
}
// End ElcaSheetView
