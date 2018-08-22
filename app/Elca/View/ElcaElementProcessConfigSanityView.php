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

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Elca\Db\ElcaElementProcessConfigSanitySet;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlElementSanity;

/**
 * Builds a list of elements with sanity check *
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
  */
class ElcaElementProcessConfigSanityView extends HtmlView
{
    /**
     * Callback triggered before rendering the template
     *
     */
    protected function beforeRender()
    {
        $access = ElcaAccess::getInstance();
        $hasAdminPrivileges = $access->hasAdminPrivileges();

        $elements = ElcaElementProcessConfigSanitySet::findByAccessGroupId($hasAdminPrivileges? null : $access->getUserGroupId());
        if($elements->count())
        {
            $Content = $this->appendChild($this->getDiv(array('class' => 'elca-element-process-config-sanity')));
            $Content->appendChild($this->getH3(t('Es wurden ungültige Datensätze in Ihren Bauteilvorlagen gefunden')));
            $Content->appendChild($this->getP(t('Die im folgenden aufgeführten Bauteilkomponenten enthalten Baustoffe, für die es keine Datensätze in der ÖKOBAUDAT mehr gibt.')));
            $P = $Content->appendChild($this->getP(''));
            $P->appendChild($this->getStrong(t('Die betroffen Baustoffe haben keinen gültigen Wertebereich zugewiesen und werden somit nicht Gegenstand Ihrer Bilanzierung.')));

            $wrapper = $Content->appendChild($this->getDiv(['class' => 'element-sanity-table-wrapper']));
            $Table = new HtmlTable('elca-element-process-config-sanity');
            $Table->addColumn('din_code', 'DIN 276')->addClass('din-code');
            $Table->addColumn('element_name', t('Bauteil'))->addClass('element-name');
            $Table->addColumn('layer_position', t('Position'))->addClass('layer-position');
            $Table->addColumn('process_config_name', t('Verwendeter Baustoff'))->addClass('process-config-name');

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            $Row->getColumn('element_name')->setOutputElement(new ElcaHtmlElementSanity('element_name'));

            $Body->setDataSet($elements);
            $Table->appendTo($wrapper);
        } else {
            $this->appendChild($this->getText(''));
        }
    }
    // End beforeRender

    //////////////////////////////////////////////////////////////////////////////////////
}
