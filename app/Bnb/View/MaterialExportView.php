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
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaCacheDataObjectSet;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\HtmlNumericTextWithUnit;

/**
 * BnbCsvExportView
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class MaterialExportView extends HtmlView
{
    private $downloadUrl;

    private $projectVariantId;

    /**
     * Initialize view
     *
     * @param array $args
     */
    public function init(array $args = [])
    {
        parent::init($args);

        $this->projectVariantId = $this->get('projectVariantId');
        $this->downloadUrl = $this->get('downloadUrl');
    }
    // End __construct

    protected function afterRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'bnb-material-export']));

        $data = ElcaCacheDataObjectSet::findProcessConfigMassByProjectVariantId($this->projectVariantId,
            ['mass' => 'DESC']);

        $table = new HtmlTable('material-data');
        $table->addColumn('name', t('Name'))->addClass('name');
        $table->addColumn('mass', t('Masse'));
        $head = $table->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $table->createTableBody();
        $row = $body->addTableRow();
        $row->getColumn('mass')->setOutputElement(new HtmlNumericTextWithUnit('mass', 'kg', null, null, 1));

        $body->setDataSet($data);

        $foot = $table->createTableFoot();
        $row = $foot->addTableRow();
        $row->getColumn('name')->setColSpan(2);
        $div = $row->getColumn('name')->setOutputElement(new HtmlTag('div'));
        $div->add(new HtmlTag('a', t('Download als CSV'), [
            'href' => $this->downloadUrl, 'class' => 'no-xhr'
        ]));

        $table->appendTo($container);
    }
}
