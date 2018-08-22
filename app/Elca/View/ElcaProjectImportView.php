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

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the import screen
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectImportView extends HtmlView
{
    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->setTplName('elca_project_import');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        $Container = $this->getElementById('content');

        $Form = new HtmlForm('projectImportForm', '/projects/import/');
        $Form->addClass('clearfix');

        if($this->has('Validator'))
        {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        $group = $Form->add(new HtmlFormGroup('Importdatei laden'));
        $group->add(new ElcaHtmlFormElementLabel(t('Datei (.xml)'), new HtmlUploadInput('importFile')));

        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('upload', t('Importieren')));
        $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbrechen'), false));

        $Form->appendTo($Container);
    }
    // End beforeRender

	//////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaProjectImportView
