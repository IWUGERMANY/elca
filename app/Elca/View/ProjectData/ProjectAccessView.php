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

namespace Elca\View\ProjectData;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\DateTimeConverter;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Controller\ProjectData\ProjectAccessCtrl;
use Elca\View\helpers\BooleanConverter;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ProjectAccessTokenActions;

class ProjectAccessView extends HtmlView
{
    private $data;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        $this->setTplName('elca_project_access');

        parent::init($args);

        $this->data = $this->get('data');
    }

    /**
     * Callback triggered before rendering the template
     */
    protected function beforeRender()
    {
        $container = $this->getElementById('content');

        $form = new HtmlForm(
            'accessTokens', FrontController::getInstance()->getUrlTo(ProjectAccessCtrl::class, 'save')
        );
        $form->setAttribute('id', 'accessTokens');
        $form->addClass('clearfix highlight-changes project-access-token-form');

        if ($validator = $this->get('validator')) {
            $form->setValidator($validator);

            if (!$validator->isValid()) {
                $form->setRequest(FrontController::getInstance()->getRequest());
            }
        }

        $form->setDataObject($this->data);
        $form->add(new HtmlHiddenField('projectId', $this->data->projectId));

        $this->appendConfirmedTokens($form);
        $this->appendUnconfirmedTokens($form);

        $newShareGroup = $form->add(new HtmlFormGroup(t('Eine neue Freigabe einrichten')));
        $newShareGroup->addClass('new-access');
        $newShareGroup->add(
            new ElcaHtmlFormElementLabel(t('Einladung versenden an folgende E-Mailadresse'), new HtmlTextInput('grantAccessToEmail'), true)
        );
        $newShareGroup->add($pElt = new HtmlTag('p', t('Das Projekt wird mit einem Lesezugriff freigegeben.')));
        $pElt->add(new HtmlTag('br', t('Erweiterten Zugriff einrichten:')));
        $pElt->add(new HtmlStaticText(t('Erweiterten Zugriff einrichten:')));
        $newShareGroup->add(
            new ElcaHtmlFormElementLabel(t('Erlauben, Änderungen am Projekt vorzunehmen'), new HtmlCheckbox('canEdit'), true)
        );

        $newShareGroup->add(new ElcaHtmlSubmitButton('addAccess', t('Einladen')))->addClass('add-access');

        $form->appendTo($container);
    }

    private function appendConfirmedTokens(HtmlElement $container)
    {
        $group = $container->add(new HtmlFormGroup(t('Aktive Freigaben')));

        $table = new HtmlTable('project-access-tokens-unconfirmed');
        $table->addClass('project-access-tokens');
        $group->add($table);

        $table->addColumn('userEmail', t('E-Mailadresse'));
        $table->addColumn('canEdit', t('Darf editieren'))->addClass('canEdit');
        $table->addColumn('confirmedAt', t('Bestätigt am'))->addClass('confirmedAt');
        $table->addColumn('actions', t('Aktionen'));

        $head    = $table->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $table->createTableBody();
        $row  = $body->addTableRow();
        $row->getColumn('canEdit')->setOutputElement(new HtmlText('canEdit', new BooleanConverter(t('nein'), t('ja'))));
        $row->getColumn('confirmedAt')->setOutputElement(
            new HtmlText('confirmedAt', (new DateTimeConverter())->set('format', 'd.m.Y'))
        );
        $row->getColumn('actions')->setOutputElement(new ProjectAccessTokenActions('actions'));

        $body->setDataSet($this->data->confirmedTokens);

        if (!$this->data->confirmedTokens) {
            $tfoot = $table->createTableFoot();
            $row = $tfoot->addTableRow();
            $column = $row->getColumn('userEmail');
            $column->setColSpan(4);
            $column->add(new HtmlStaticText('Keine Freigaben'));
        }
    }

    private function appendUnconfirmedTokens(HtmlElement $container)
    {
        if (0 === count($this->data->unconfirmedTokens)) {
            return;
        }

        $group = $container->add(new HtmlFormGroup(t('Unbestätigte Freigaben')));

        $table = new HtmlTable('project-access-tokens-unconfirmed');
        $table->addClass('project-access-tokens');
        $group->add($table);

        $table->addColumn('userEmail', t('E-Mailadresse'));
        $table->addColumn('canEdit', t('Darf editieren'))->addClass('canEdit');
        $table->addColumn('createdAt', t('Eingeladen am'))->addClass('createdAt');
        $table->addColumn('actions', t('Aktionen'));

        $head    = $table->createTableHead();
        $headRow = $head->addTableRow(new HtmlTableHeadRow());
        $headRow->addClass('table-headlines');

        $body = $table->createTableBody();
        $row  = $body->addTableRow();
        $row->getColumn('canEdit')->setOutputElement(new HtmlText('canEdit', new BooleanConverter(t('nein'), t('ja'))));
        $row->getColumn('createdAt')->setOutputElement(
            new HtmlText('createdAt', (new DateTimeConverter())->set('format', 'd.m.Y'))
        );
        $row->getColumn('actions')->setOutputElement(new ProjectAccessTokenActions('actions'));

        $body->setDataSet($this->data->unconfirmedTokens);
    }
}
