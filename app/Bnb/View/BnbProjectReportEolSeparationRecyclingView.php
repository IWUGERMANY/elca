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
namespace Bnb\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\UserStore;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormatLink;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Bnb\Model\Processing\BnbProcessor;
use DOMElement;
use Elca\Db\ElcaProjectVariant;
use Elca\ElcaNumberFormat;
use Elca\View\Report\ElcaReportsView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericText;

/**
 * Builds the summary report for life cycle costs
 *
 * @package bnb
 * @author  Tobias Lode <tobias@beibob.de>
 */
class BnbProjectReportEolSeparationRecyclingView extends ElcaReportsView
{
    /**
     * @var array $data
     */
    private $data;

    /**
     * @var string $calcMethod
     */
    private $calcMethod;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->data = $this->get('data');
        $this->calcMethod = $this->get('calcMethod');
    }
    // End init


    /**
     * Renders the report
     *
     * @param  DOMElement        $Container
     * @param DOMElement         $InfoDl
     * @param ElcaProjectVariant $ProjectVariant
     * @param int                $lifeTime
     */
    protected function renderReports(DOMElement $Container, DOMElement $InfoDl, ElcaProjectVariant $ProjectVariant, $lifeTime)
    {
        $this->addClass($Container, 'bnb-reports bnb-report-4-1-4');

        $Form = new HtmlForm('reportForm', '/bnb/project-report-eol-separation-recycling/');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject((object)['calcMethod' => $this->calcMethod]);

        if (UserStore::getInstance()->hasAdminPrivileges()) {
            $Radio = $Form->add(new ElcaHtmlFormElementLabel(t('Berechnungsmethode'), new HtmlRadioGroup('calcMethod')));
            $Radio->add(new HtmlRadiobox(t('Masse'), BnbProcessor::BNB414_CALC_METHOD_MASS));
            $Radio->add(new HtmlRadiobox(t('Fläche'), BnbProcessor::BNB414_CALC_METHOD_SURFACE));
        }

        $Form->appendTo($Container);

        $TdContainer = $this->appendPrintTable($Container);
        $this->buildData($TdContainer, $ProjectVariant);
    }
    // End beforeRender


    /**
     * Builds the summary
     *
     * @param  DOMElement $Container
     *
     * @return void -
     */
    private function buildData(DOMElement $Container)
    {
        $Table = new HtmlTable('report report-bnb-4-1-4');
        $Table->addColumn('dinCode', t('KG'));
        $Table->addColumn('elementName', t('Bauteil'));
        $Table->addColumn('eol', t('Rückbau'))->addClass('centered');
        $Table->addColumn('separation', t('Trennung'))->addClass('centered');
        $Table->addColumn('recycling', t('Verwertung'))->addClass('centered');
        $Table->addColumn('ratio', t('Anteil'))->addClass('align-right');
        $Table->addColumn('value', '')->addClass('align-right');
        $Table->addColumn('benchmark', t('Benchmark'))->addClass('align-right');

        $Head = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        /**
         * Add
         */
        $Span = $HeadRow->getColumn('value')->setOutputElement(new HtmlTag('span', $this->calcMethod == BnbProcessor::BNB414_CALC_METHOD_MASS ? (t('Masse') . ' ') : (t('Fläche') . ' ')));
        $Span->add(new HtmlTag('sub', $this->calcMethod == BnbProcessor::BNB414_CALC_METHOD_MASS ? 'kg' : 'm²'));

        $Span = $HeadRow->getColumn('ratio')->setOutputElement(new HtmlTag('span', t('Anteil') . ' '));
        $Span->add(new HtmlTag('sub', '%'));

        $Body = $Table->createTableBody();
        $Row = $Body->addTableRow();

        $Span = new HtmlTag('span');
        $FormatLink = $Span->add(new HtmlFormatLink('elementName', 'elementId', '/project-elements/%d/'));
        $FormatLink->add(new HtmlStaticText(' ['));
        $FormatLink->add(new HtmlText('elementId'));
        $FormatLink->add(new HtmlStaticText('] '));
        $Row->getColumn('elementName')->setOutputElement($Span)->addClass('page');

        $Row->getColumn('value')->setOutputElement(new ElcaHtmlNumericText('value', 2, false));
        $Row->getColumn('ratio')->setOutputElement(new ElcaHtmlNumericText('ratio', 2, true));
        $Row->getColumn('benchmark')->setOutputElement(new ElcaHtmlNumericText('benchmark', 2));

        $Body->setDataSet($this->data['elements']);

        $Footer = $Table->createTableFoot();
        $Row = $Footer->addTableRow();
        $Row->getColumn('dinCode')->setColSpan(5);
        $Row->getColumn('dinCode')->setOutputElement(new HtmlTag('span', t('Summe (KG 300)')));
        $Row->getColumn('ratio')->setOutputElement(new HtmlTag('span', ElcaNumberFormat::toString($this->data['ratio'], 2, true)));
        $Row->getColumn('value')->setOutputElement(new HtmlTag('span', ElcaNumberFormat::toString($this->data['total'], 2)));
        $Row->getColumn('benchmark')->setOutputElement(new HtmlTag('span', ElcaNumberFormat::toString($this->data['benchmark'], 2)));

        $Table->appendTo($Container);
    }
    // End buildData

}
// End BnbProjectReportEolSeparationRecyclingView
