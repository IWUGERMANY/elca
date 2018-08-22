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

namespace ImportAssistant\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\Db\ElcaProcessDbSet;
use ImportAssistant\Model\Import\Project;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the import screen
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ProjectImportView extends HtmlView
{
    /**
     * @var Project|null
     */
    private $project;

    private $context;

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('project_import', 'importAssistant');

        $this->context = $this->get('context');
        $this->project = $this->get('project');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('content');

        $form = new HtmlForm('projectImportForm', '/importAssistant/projects/import/');
        $form->addClass('clearfix');

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        $group = $form->add(new HtmlFormGroup(t('Importdatei laden')));
        $group->addClass('import-group');

        $this->appendFileUploadForm($group);

        $this->appendProcessDbOption($group);

        $this->appendButtons($form);

        $form->appendTo($container);
    }

    protected function appendFileUploadForm(HtmlElement $element) : void
    {
        $element->add(new ElcaHtmlFormElementLabel(t('Datei (.xml)'), new HtmlUploadInput('importFile')));
    }

    protected function appendProcessDbOption(HtmlElement $element): void
    {
        $processDbSet = ElcaProcessDbSet::findEn15804Compatibles(['is_active' => true], ['version' => 'ASC']);
        $dbLabel = t('Baustoffdatenbank');

        if ($processDbSet->count() > 1) {
            $element->add(
                new ElcaHtmlFormElementLabel($dbLabel, $select = new HtmlSelectbox('processDbId'))
            );

            foreach ($processDbSet as $processDb) {
                $select->add(new HtmlSelectOption($processDb->getName(), $processDb->getId()));
            }
        }
        else {
            $processDbName = $processDbSet->current()->getName();

            $element->add(
                new ElcaHtmlFormElementLabel($dbLabel, new HtmlStaticText($processDbName))
            )->addClass('processDbId');

            $element->add(
                new HtmlHiddenField('processDbId', $processDbSet->current()->getId())
            );
        }
    }

    /**
     * @param HtmlForm $form
     * @throws \DI\NotFoundException
     */
    protected function appendButtons(HtmlForm $form): void
    {
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');
        $buttonGroup->add(new ElcaHtmlSubmitButton('upload', t('Importieren')));
        $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen'), false));
    }
}

