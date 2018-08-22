<?php declare(strict_types=1);
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

namespace ImportAssistant\View\Admin;

use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Elca\Db\ElcaProcessDbSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;

class MappingsView extends HtmlView
{
    private $processDbId;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        $this->setTplName('admin/mappings', 'importAssistant');

        $this->processDbId = $this->get('processDbId');
    }

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $this->setCaptions();
        $this->appendForm();
    }

    protected function setCaptions(): void
    {
        $table = $this->getElementById('adminMaterialMappings');
        $table->setAttribute('data-edit-caption', t('Bearbeiten'));
        $table->setAttribute('data-delete-caption', t('Löschen'));
        $table->setAttribute('data-per-page-caption', t('Zeige _MENU_ Datensätze pro Seite'));
        $table->setAttribute('data-zero-records-caption', t('Keine Datensätze'));
        $table->setAttribute('data-current-page-caption', t('Seite _PAGE_ von _PAGES_'));
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
    }

    private function appendForm()
    {
        $container = $this->getElementById('formWrapper');

        $form   = new HtmlForm('processDbForm', '/importAssistant/admin/mappings/setProcessDb/');
        $form->addClass('clearfix');
        $form->setDataObject((object)['processDbId' => $this->processDbId]);

        $form->add(new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), $select = new HtmlSelectbox('processDbId')));
        $select->setAttribute('onchange', '$(this.form).submit();');

        $option = null;
        foreach (ElcaProcessDbSet::findEn15804Compatibles([], ['version' => 'ASC']) as $processDb) {
            $option = $select->add(new HtmlSelectOption($processDb->getName(), $processDb->getId()));

            if ($this->processDbId === $processDb->getId()) {
                $option->setAttribute('selected', 'selected');
            }
        }

        $form->appendTo($container);
    }
}
