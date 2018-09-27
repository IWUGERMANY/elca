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

use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessViewSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;

/**
 * Builds a process config sheet
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigSheetView extends ElcaSheetView
{
    /**
     * Process config
     */
    private $ProcessConfig;

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

        // itemId
        $this->ProcessConfig = ElcaProcessConfig::findById($this->get('itemId'));
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'elca-process-config-sheet');

        if (!$this->get('isReference')) {
            $this->addClass($Container, 'inactive');
        }

        $processConfigUrl = '/processes/$$itemId$$/';

        if ($this->get('backReference')) {
            $processConfigUrl .= '?back=' . $this->get('backReference');
        }

        if($this->get('readOnly'))
        {
            $this->addFunction('view', $processConfigUrl, 'Ansehen', 'default page');
        }
        else
        {
            $this->addFunction('edit', $processConfigUrl, 'Bearbeiten', 'default page');
            $this->addFunction('copy', '/processes/copy/?id=$$itemId$$', 'Kopieren');
            $this->addFunction('delete', '/processes/delete/?id=$$itemId$$', 'Löschen');
        }

        /**
         * Append individual content
         */
        $this->addInfo($this->ProcessConfig->getDefaultLifeTime(), 'Nutzungsdauer', 'a');

        if($density = $this->ProcessConfig->getDensity())
            $this->addInfo(ElcaNumberFormat::toString($density, 2), 'Rohdichte', 'kg / m³');

        $Processes = ElcaProcessViewSet::findWithProcessDbByProcessConfigId($this->get('itemId'));
        $materials = [];
        foreach($Processes as $Process)
            $materials[$Process->lifeCyclePhase][] = \processName($Process->processId) .' ['.$Process->processDb.']';

        foreach($materials as $phase => $processes)
            $this->addInfo(implode(', ', $processes), Elca::$lcPhases[$phase], null, true);
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaSheetView
