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

namespace Elca\View\Modal;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlPasswordInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ModalProjectAccess extends HtmlView
{
    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->setTplName('elca_modal_project_access');
    }
    // End init

    /**
     *
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('elca-modal-content');

        $form = new HtmlForm('projectAccessForm', '/projects/passwordPrompt');
        $form->setAttribute('id', 'projectAccessForm');
        $form->setRequest(FrontController::getInstance()->getRequest());
        $form->setAttribute('class', 'xhr');

        if($this->has('validator'))
            $form->setValidator($this->get('validator'));

        $form->add(new HtmlHiddenField('id', $this->get('projectId')));

        if ($this->get('origin')) {
            $form->add(new HtmlHiddenField('origin', $this->get('origin')));
            $form->add(new HtmlHiddenField('originCtrl', $this->get('originCtrl')));
            $form->add(new HtmlHiddenField('originAction', $this->get('originAction')));
            $form->add(new HtmlHiddenField('originArgs', $this->get('originArgs')));
        }

        $Group = $form->add(new HtmlFormGroup(''));

        $Group->add(new ElcaHtmlFormElementLabel(t('Passwort'), new HtmlPasswordInput('pwProject', ''), true));

        $Group = $form->add(new HtmlFormGroup(''));
        $Group->addClass('buttons');
        $Link = $Group->add(new HtmlLink(t('Abbrechen'), '/projects/'));
        $Link->setAttribute('rel', 'page');
        $Group->add(new ElcaHtmlSubmitButton('checkAccess', t('Absenden')));

        $form
            ->appendTo($container);
    }
    // End beforeRender
}
