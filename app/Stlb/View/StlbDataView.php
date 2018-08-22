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
namespace Stlb\View;

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Stlb\View\helpers\StlbConverter;

/**
 * @package elca
 * @author  Patrick Kocurek <patrick@kocurek.de>
 */
class StlbDataView extends HtmlView
{
    /**
     * Captions
     */
    public static $formCaptions = ['importFile' => 'Datei (.X81, .csv)'];

    /**
     * @translate array Stlb\View\StlbDataView::$captions
     */
    public static $captions = ['din_code'       => 'DIN276 Code',
                               'name'           => 'Name',
                               'description'    => 'Langtext',
                               'quantity'       => 'Menge',
                               'ref_unit'       => 'ME',
                               'oz'             => 'THE OZ',
                               'lb_nr'          => 'LB-Nr',
                               'price_per_unit' => 'Einheitspreis',
                               'price'          => 'Gesamtbetrag'
    ];


    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('stlb_data', 'stlb');
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
        $StlbElementSet = $this->get('StlbElementSet', []);

        $Container = $this->getElementById('content');
        $Form = new HtmlForm('stlbForm', '/stlb/data/save/');
        $Form->setAttribute('id', 'stlbForm');

        if ($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Group = $Form->add(new HtmlFormGroup(''));
        $Group->addClass('column clear');
        $Group->add(new ElcaHtmlFormElementLabel(t(self::$formCaptions['importFile']), new HtmlUploadInput('importFile'), true));

        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clear buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        if (count($StlbElementSet))
            $ButtonGroup->add(new ElcaHtmlSubmitButton('delete', t('Daten löschen')));

        $Form->appendTo($Container);

        $Container->appendChild($this->getH3(t('Daten des Standardleistungsbuches für das aktuelle Projekt'), ['class' => 'stlb-header']));

        $Table = new HtmlTable('stlb-data');
        $Table->addColumn('oz', t('OZ'))->addClass('oz');
        $Table->addColumn('name', t('Name'))->addClass('name');;
        $Table->addColumn('quantity', t('Menge'));
        $Table->addColumn('refUnit', t('Einheit'));
        $Table->addColumn('dinCode', t('DIN276'));
        $Table->addColumn('lbNr', t('LB-Nr'));
        $Table->addColumn('description', t('Beschreibung'))->addClass('description');
        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Converter = new StlbConverter();

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();
        $Row->getColumn('description')->addAttrFormatter($Converter);

        $Row->getColumn('refUnit')->setOutputElement(new HtmlText('refUnit', $Converter));

        $Body->setDataSet($StlbElementSet);
        $Table->appendTo($Container);
    }
    // End beforeRender

}
// End StlbDataView
