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
use Beibob\HtmlTools\HtmlDataLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlText;
use Stlb\View\helpers\StlbConverter;

/**
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de>
 */
class StlbFooterView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_OPEN   = 'open';
    const BUILDMODE_CLOSED = 'closed';

    /**
     * Buildmode
     */
    private $buildMode;


    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('stlb_footer','stlb');

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_CLOSED);
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
        $countTotal   = $this->get('countTotal', 0);
        $countVisible = $this->get('countVisible', 0);
        $entries = $countTotal != 1? t('Einträge') : t('Eintrag');

        if(!$countTotal)
        {
            $this->assign('transparency', 'semi-transparent empty');
            $ShowLink = $this->getElementById('bar-toggle-visibility', true);
            $ShowLink->parentNode->removeChild($ShowLink);
            $this->buildMode = self::BUILDMODE_CLOSED;
        }

        /**
         * Bar
         */
        $ClosedSpan = $this->getElementById('closed', true);
        $OpenedSpan = $this->getElementById('open', true);

        if($this->buildMode == self::BUILDMODE_CLOSED)
            $OpenedSpan->parentNode->removeChild($OpenedSpan);
        else
            $ClosedSpan->parentNode->removeChild($ClosedSpan);

        if($this->buildMode == self::BUILDMODE_OPEN && $countVisible)
        {
            $entries = $countTotal > 1  ? t('Einträgen') : t('Eintrag');

            /**
             * Table
             */
            $Container = $this->getElementById('stlb-content');

            $Elem = $this->getElementById('bar-show-all-elements');
            if ($countTotal == $countVisible)
                // entfernen falls alle sichtbar sind
                $Elem->parentNode->removeChild($Elem);

            $Table = new HtmlTable('stlb-data');
            $Table->addColumn('oz', t('OZ'))->addClass('oz');
            $Table->addColumn('name', t('Name'))->addClass('name');;
            $Table->addColumn('quantity', t('Menge'));
            $Table->addColumn('refUnit', t('Einheit'));
            $Table->addColumn('dinCode', t('DIN276'));
            $Table->addColumn('lbNr', t('LB-Nr'));
            $Table->addColumn('description', t('Beschreibung'))->addClass('description');
            $Table->addColumn('hide', t('Aktion'));

            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Converter = new StlbConverter();

            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            $Row->getColumn('description')->addAttrFormatter($Converter);

            $Row->getColumn('refUnit')->setOutputElement( new HtmlText('refUnit', $Converter));

            $HtmlLink = new HtmlDataLink( 'hide', $Converter );
            $HtmlLink->add(new HtmlStaticText(t('verbergen')));
            $Row->getColumn('hide')->setOutputElement($HtmlLink);

            $Body->setDataSet($this->get('StlbElementSet', []));
            $Table->appendTo($Container);
        }

        $this->assign('entries', $entries);
    }
    // End beforeRender


}
// End StlbFooterView
