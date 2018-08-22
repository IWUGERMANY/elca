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

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

class MappingsImportView extends HtmlView
{
    private $processDbId;

    /**
     * @var ElcaProcessDb
     */
    private $processDb;

    protected function init(array $args = [])
    {
        $this->setTplName('admin/mappings_import', 'importAssistant');

        $this->processDbId = $this->get('processDbId');
        $this->processDb = ElcaProcessDb::findById($this->processDbId);
    }

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('content');

        $form = new HtmlForm('mappingsImportForm', '/importAssistant/admin/mappings/import/');
        $form->addClass('clearfix highlight-changes');

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $this->appendCleanupOption($form);

        $this->appendFileUpload($form);

        $this->appendCopyFrom($form);

        $this->appendButtons($form);

        $form->appendTo($container);
    }

    /**
     * @param $form
     * @throws \DI\NotFoundException
     */
    protected function appendCleanupOption(HtmlForm $form): void
    {
        $group = $form->add(
            new HtmlFormGroup(
                t('Datenbestand von :dbName: löschen', null, [':dbName:' => $this->processDb->getVersion()])
            )
        );
        $group->add(
            new ElcaHtmlFormElementLabel(t('Den aktuellen Datenbestand löschen'), new HtmlCheckbox('removeMappings'))
        );
    }

    /**
     * @param $form
     */
    private function appendFileUpload(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Von Importdatei nach :dbName: laden', null, [':dbName:' => $this->processDb->getVersion()])));
        $group->addClass('column');
        $group->add(new ElcaHtmlFormElementLabel(t('Datei (.csv)'), new HtmlUploadInput('importFile')));
    }


    private function appendCopyFrom(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Datensätze einer anderen Version nach :dbName: kopieren', null, [':dbName:' => $this->processDb->getVersion()])));
        $group->addClass('column');
        $group->add(new ElcaHtmlFormElementLabel(t('Baustoffdatenbank'), $select = new HtmlSelectbox('copyFromProcessDbId')));
        $select->add(new HtmlSelectOption(t('-- Bitte wählen --'), ''));

        foreach (ElcaProcessDbSet::findEn15804Compatibles([], ['version' => 'ASC']) as $processDb) {
            if ($this->processDbId === $processDb->getId()) {
                continue;
            }

            $select->add(new HtmlSelectOption($processDb->getName(), $processDb->getId()));
        }

    }

    private function appendButtons(HtmlForm $form): void
    {
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('upload', t('Anwenden'), true));
        $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen')));
    }

}
