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
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * @package lcc
 * @author  Tobias Lode <tobias@beibob.de>
 */
class EnergySourceCostsView extends HtmlView
{
    private $versionId;

    private $energySourceCosts;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->versionId         = $this->get('versionId');
        $this->energySourceCosts = $this->get('energySourceCosts', new \stdClass());
    }


    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'lcc-energy-costs']));

        $form = new HtmlForm('lccEnergyCostsForm', '/lcc/admin/energySourceCosts/save');
        $form->addClass('clearfix highlight-changes');
        $form->setRequest(FrontController::getInstance()->getRequest());
        $form->setDataObject($this->energySourceCosts);

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
        }

        $form->add(new HtmlHiddenField('versionId', $this->versionId));

        $group = $form->add(new HtmlFormGroup(''));

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');

        $row->add(new HtmlTag('h5', \t('Name'), ['class' => 'hl-name']));
        $row->add(new HtmlTag('h5', \t('Preis / kWh'), ['class' => 'hl-costs']));
        $row->add(new HtmlTag('h5', '', ['class' => 'hl-actions']));

        $ul = $group->add(new HtmlTag('ul'));
        foreach ($this->energySourceCosts->name as $key => $name) {
            $li = $ul->add(new HtmlTag('li', null, ['class' => 'version-row']));
            $this->appendEnergyCosts($li, $key);
        }

        if ($this->get('add', false) || 0 === \count($this->energySourceCosts->name)) {
            $li = $ul->add(new HtmlTag('li', null, ['class' => 'version-row']));
            $this->appendEnergyCosts($li);
        }

        /**
         * Submit button
         */
        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');

        if (\count($this->energySourceCosts->name) > 0) {
            $buttonGroup->add(new ElcaHtmlSubmitButton('add', \t('Hinzufügen'), false));
        }

        $buttonGroup->add(new ElcaHtmlSubmitButton('save', \t('Speichern'), true));


        $form->appendTo($container);
    }
    // End afterRender


    /**
     * Appends a variant row
     *
     * @throws \DI\NotFoundException
     * @param HtmlElement $li
     * @param null        $key
     */
    protected function appendEnergyCosts(HtmlElement $li, $key = null)
    {
        $isNew = false;
        if (null === $key) {
            $key = 'new';
            $isNew = true;
        }

        $container = $li->add(new HtmlTag('div'));
        $container->addClass('clearfix version');

        $container->add(
            new ElcaHtmlFormElementLabel(
                '',
                new HtmlTextInput('name[' . $key . ']', $this->energySourceCosts->name[$key] ?? null)
            )
        );

        $container->add(
            new ElcaHtmlFormElementLabel(
                '',
                $input = new ElcaHtmlNumericInput('costs[' . $key . ']', $this->energySourceCosts->costs[$key] ?? null)
            )
        );
        $input->setPrecision(2);

        if (\is_numeric($key)) {
            $container->add(
                new HtmlLink(
                    t('Löschen'),
                    Url::factory('/lcc/admin/energySourceCosts/delete', ['id' => $key, 'versionId' => $this->versionId])
                )
            )
                      ->addClass('function-link delete-link');
        }
        else {
            $container->add(
                new HtmlLink(
                    t('Abbrechen'),
                    Url::factory('/lcc/admin/energySourceCosts/', ['versionId' => $this->versionId])
                )
            )
                      ->addClass('function-link cancel-link');
        }

    }
}

