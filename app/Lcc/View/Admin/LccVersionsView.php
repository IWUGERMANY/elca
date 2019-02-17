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
namespace Lcc\View\Admin;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Lcc\Db\LccVersionSet;

/**
 * @package lcc
 * @author Tobias Lode <tobias@beibob.de>
 */
class LccVersionsView extends HtmlView
{
    /**
     * Properties
     */
    private $Data;
    private $addNewVersion;


    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->Data = $this->get('Data', new \stdClass);
        $this->addNewVersion = $this->get('addNewVersion', false);
        $this->currentVersionId = $this->get('currentVersionId', null);
    }
    // End init


    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'lcc-versions']));

        $Form = new HtmlForm('lccVersionsForm', '/lcc/admin/versions/save/');
        $Form->addClass('clearfix highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->Data);

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $Form->add(new HtmlHiddenField('calcMethod', $this->Data->calcMethod));
        $Form->add(new HtmlHiddenField('a', $this->addNewVersion));

        $Group = $Form->add(new HtmlFormGroup(''));

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Name'), ['class' => 'hl-name']));
        $Row->add(new HtmlTag('h5', t('Erstellt am'), ['class' => 'hl-created']));
        $Row->add(new HtmlTag('h5', t('Aktionen'), ['class' => 'hl-actions']));

        $Ul = $Group->add(new HtmlTag('ul'));
        foreach($this->Data->name as $key => $name)
        {
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'version-row']));
            $this->appendVersion($Li, $key);
        }

        /**
         * Submit button
         */
        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');
        $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

        $Form->appendTo($Container);
    }
    // End afterRender


    /**
     * Appends a variant row
     *
     * @param  HtmlForm $Form
     */
    protected function appendVersion(HtmlElement $Li, $key)
    {
        if($this->currentVersionId == $key)
            $Li->addClass('active');

        $Container = $Li->add(new HtmlTag('div'));
        $Container->addClass('clearfix version');

        if((isset($this->Data->name[$key])) && isset($this->Data->created[$key]))
        {
            $Container->add(new ElcaHtmlFormElementLabel('', new HtmlTextInput('name['.$key.']',$this->Data->name[$key])) );
            $Label = $Container->add(new ElcaHtmlFormElementLabel('', new HtmlStaticText($this->Data->created[$key] . ' ' . t('Uhr'))));
            $Label->addClass('created');
        }

        $Container->add(new HtmlLink(t('Bearbeiten'), Url::factory('/lcc/admin/energySourceCosts/', ['versionId' => $key])))
                  ->addClass('function-link edit-link page');

        /**
         * Delete and create version
         */
        $Container->add(new HtmlLink(t('Kopieren'), Url::factory('/lcc/admin/versions/copy/', ['id' => $key])))
            ->addClass('function-link copy-link');


        if (LccVersionSet::dbCount(['calc_method' => $this->Data->calcMethod]) > 1) {
            $Container->add(new HtmlLink(t('Löschen'), Url::factory('/lcc/admin/versions/delete/', ['id' => $key])))
                      ->addClass('function-link delete-link');
        }
    }
    // End appendVersion
}
// End LccVersionsView

