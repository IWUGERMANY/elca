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
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * BnbCsvExportView
 *
 * @package
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Möller <fab@beibob.de>
 */
class ProjectExportView extends HtmlView
{
    /**
     * Initialize view
     *
     * @param array $args
     */
    public function init(array $args = [])
    {
        parent::init($args);
    }
    // End __construct

    protected function afterRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'bnb-export']));

        $form = new HtmlForm('bnbExport', '/bnb/project-export/export');
        $form->addClass('clearfix highlight-changes');

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clearfix column buttons');

        $form->add(new HtmlHiddenField('id', Elca::getInstance()->getProjectId()));

        $ButtonGroup = $form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('clearfix column buttons');

        $ButtonGroup->add(new ElcaHtmlSubmitButton('export', t('Projekt exportieren'), true));

        $form->appendTo($container);
    }
    // End afterRender
}
// End BnbCsvExportView