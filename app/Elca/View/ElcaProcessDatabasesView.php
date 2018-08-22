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
use Beibob\HtmlTools\HtmlText;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessViewSet;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaProcessesConverter;

/**
 * Builds the table for all process database records
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessDatabasesView extends HtmlView
{

    // protected


    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'process-databases']));

        $Buttons = $Container->appendChild($this->getDiv(['class' => 'buttons clearfix']));
        $ButtonContainer = $Buttons->appendChild($this->getDiv(['class' => 'button export']));
        $A = $ButtonContainer->appendChild($this->getA(['href' => '/exports/processes/?id='.$this->get('processDbId')], t('Nach CSV exportieren')));
        $A->setAttribute('class', 'no-xhr');

        $ProcessDb = ElcaProcessDb::findById($this->get('processDbId'));

        $ButtonContainer = $Buttons->appendChild($this->getDiv(['class' => 'button export']));
        $A = $ButtonContainer->appendChild($this->getA(['href' => '/exports/configs/?id='.$this->get('processDbId')], t('Konfiguration nach CSV exportieren')));
        $A->setAttribute('class', 'no-xhr');


        $IndicatorSet = ElcaIndicatorSet::findByProcessDbId($this->get('processDbId'), false, false, ['p_order' => 'ASC']);
        $ProcessData = ElcaProcessViewSet::findWithIndicators($this->get('processDbId'));

        $Table = new HtmlTable('process-databases');
        $Table->addColumn('nameOrig', t('Datensatzname'));
        $Table->addColumn('lifeCycleName', t('Modul'));
        $Table->addColumn('refValue', t('Bezugsgröße'));

        foreach($IndicatorSet as $Indicator)
            $Table->addColumn($Indicator->getIdent(), t($Indicator->getName()));

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Converter = new ElcaProcessesConverter();

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('refValue')->setOutputElement(new HtmlText('refValue', $Converter));

        foreach($IndicatorSet as $Indicator)
            $Row->getColumn($Indicator->getIdent())->setOutputElement(new ElcaHtmlNumericText($Indicator->getIdent(), 10));

        $Body->setDataSet($ProcessData);

        $ProcessDataContainer = $this->getDiv();
        $ProcessDataContainer->setAttribute('id', 'processData');
        $Table->appendTo($ProcessDataContainer);

        $Container->appendChild($ProcessDataContainer);
    }
    // End beforeRender
}
// End ElcaProcessDatabasesView
