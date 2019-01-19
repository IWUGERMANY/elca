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
namespace Soda4Lca\View;

use DateTime;
use Elca\View\ElcaSheetView;
use Soda4Lca\Db\Soda4LcaImport;
use Soda4Lca\Db\Soda4LcaProcessSet;

/**
 * Builds a database sheet
 *
 * @package soda4lca
 * @author Tobias Lode <tobias@beibob.de>
 */
class Soda4LcaDatabaseSheetView extends ElcaSheetView
{
    /**
     * DatabaseDO
     */
    private $DatabaseDO;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->DatabaseDO = $this->get('Db', new \stdClass());
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getContainer();
        $this->addClass($Container, 'soda4lca-database-sheet');

        $this->addFunction('edit', '/soda4Lca/databases/$$itemId$$/', t('Bearbeiten'), 'default page');
        $this->addFunction('delete', '/soda4Lca/databases/delete/?id=$$itemId$$', t('Löschen'));

        if($this->DatabaseDO->is_active)
            $this->addClass($Container, 'is-active');

        /**
         * Append individual content
         */
        $this->addInfo(t(Soda4LcaDatabaseView::$importStatusMap[$this->DatabaseDO->status]), t('Status'));
        $this->addInfo($this->DatabaseDO->data_stock, t('DataStock'));

        if($this->DatabaseDO->status != Soda4LcaImport::STATUS_INIT)
        {
            $this->addInfo(Soda4LcaProcessSet::dbCount(['import_id' => $this->DatabaseDO->import_id]), t('Datensätze gesamt'), null, true);
            $this->addInfo(Soda4LcaProcessSet::dbCountImported($this->DatabaseDO->import_id), t('Importierte Datensätze'));
            $this->addInfo($this->DatabaseDO->version, t('Version'));

            if($this->DatabaseDO->date_of_import)
            {
                $ImportDate = new DateTime($this->DatabaseDO->date_of_import);
                $this->addInfo($ImportDate->format(t('DATETIME_FORMAT_DMY') . ', ' . t('DATETIME_FORMAT_HI')), t('Importiert am'));
            }
        }
    }
    // End beforeRender
}
// End Soda4LcaDatabaseSheetView
