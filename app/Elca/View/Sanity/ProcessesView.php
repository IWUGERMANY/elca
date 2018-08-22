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

namespace Elca\View\Sanity;

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use DOMElement;
use Elca\Db\ElcaProcessConfigSanitySet;
use Elca\Db\ElcaProcessDbSet;
use Elca\View\helpers\ElcaHtmlProcessConfigSanity;

/**
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ProcessesView extends HtmlView
{
    /**
     * Show false positives
     */
    private $showFalsePositives;

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
        $this->setTplName('sanity/elca_processes');

        $this->showFalsePositives = $this->get('falsePositives', false);
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    protected function beforeRender()
    {
        $table = $this->getElementById('processSanityTable');
        $table->setAttribute('data-edit-caption', t('Bearbeiten'));
        $table->setAttribute('data-delete-caption', t('Löschen'));
        $table->setAttribute('data-per-page-caption', t('Zeige _MENU_ Datensätze pro Seite'));
        $table->setAttribute('data-zero-records-caption', t('Keine Datensätze'));
        $table->setAttribute('data-current-page-caption', t('Seite _PAGE_ von _PAGES_ (_TOTAL_ von _MAX_ Datensätzen)'));
        $table->setAttribute('data-info-filtered-caption', '');
        $table->setAttribute('data-search-caption', t('Suche'));
        $table->setAttribute('data-first-caption', t('Anfang'));
        $table->setAttribute('data-last-caption', t('Ende'));
        $table->setAttribute('data-next-caption', t('Vor'));
        $table->setAttribute('data-previous-caption', t('Zurück'));
        $table->setAttribute('data-select-none-caption', t('Selektion aufheben'));
        $table->setAttribute('data-delete-selected-caption', t('Selektion löschen'));
        $table->setAttribute('data-selected-rows-caption', t('%d Zeilen selektiert'));
        $table->setAttribute('data-selected-row-caption', t('Eine Zeile selektiert'));
        $table->setAttribute('data-no-row-selected-caption', t('Keine Zeile selektiert'));
        $table->setAttribute('data-clear-all-filters-caption', t('Filter zurücksetzen'));
        $table->setAttribute('data-save-caption', t('Speichern'));
        $table->setAttribute('data-all-caption', t('Alle'));

        $processDbFilter = $this->getElementById('processDbFilter');

        $processDbs = ElcaProcessDbSet::find(null,['id' => 'ASC']);

        foreach ($processDbs as $processDb) {
            $processDbFilter->appendChild($this->getOption(['value' => $processDb->getName(), 'data-en-15085' => (int)$processDb->isEn15804Compliant()], $processDb->getName()));
        }
    }

    /**
     * @deprecated
     * @param $content
     */
    protected function appendProcessConfigSanityTable(\DOMElement $content)
    {
        /**
         * ProcessConfigs that need configuration
         */
        $ProcessConfigs = ElcaProcessConfigSanitySet::find($this->showFalsePositives);
        $H3             = $content->appendChild(
            $this->getH3(t('Folgende Baustoffkonfigurationen sollten überprüft werden'))
        );

        $H3->appendChild($this->getA(['href' => '/sanity/processes/refreshSanities/'], t('Liste aktualisieren')));

        if ($ProcessConfigs->count()) {

            if ($this->showFalsePositives) {
                $H3->appendChild(
                    $this->getA(
                        ['href' => '/sanity/processes/sanityFalsePositives/?hide'],
                        t('Ignorierte Zeilen ausblenden')
                    )
                );
            } else {
                $H3->appendChild(
                    $this->getA(
                        ['href' => '/sanity/processes/sanityFalsePositives/?show'],
                        t('Ignorierte Zeilen einblenden')
                    )
                );
            }


            $Table = new HtmlTable('elca-process-configs-sanity');
            $Table->addColumn('ref_num', t('Kategorie'))->addClass('ref-num');
            $Table->addColumn('name', t('Name'))->addClass('name');
            $Table->addColumn('process_db_name', t('Baustoffdatenbank'))->addClass('db-name');
            $Table->addColumn('epd_modules', t('EPD Module'))->addClass('epd-modules');
            $Table->addColumn('status', t('Status'))->addClass('status');
            $Table->addColumn('is_false_positive', t('Aktion'))->addClass('is-false-positive');
            $Head    = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->addClass('table-headlines');

            $Body = $Table->createTableBody();
            $Row  = $Body->addTableRow();
            $Row->addAttrFormatter(new ElcaHtmlProcessConfigSanity('isReference'));
            $Row->getColumn('ref_num')->setOutputElement(new ElcaHtmlProcessConfigSanity('ref_num'));
            $Row->getColumn('name')->setOutputElement(new ElcaHtmlProcessConfigSanity('name'));
            $Row->getColumn('status')->setOutputElement(new ElcaHtmlProcessConfigSanity('status'));

            $Row->getColumn('is_false_positive')->setOutputElement(
                new ElcaHtmlProcessConfigSanity('is_false_positive')
            );

            $Body->setDataSet($ProcessConfigs);
            $Table->appendTo($content);
        }
    }

    /**
     * @param $td
     * @param $dataObject
     */
    protected function appendCheckbox(DOMElement $td, $dataObject, $property): void
    {
        $attributes = ['type' => 'checkbox', 'value' => $dataObject->id];

        if ($dataObject->$property) {
            $attributes['checked'] = 'checked';
        }

        $td->appendChild($this->getInput($attributes));
    }
}

