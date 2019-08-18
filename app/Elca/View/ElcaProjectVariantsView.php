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
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * @package elca
 * @author  Patrick Kocurek <patrick@kocurek.de>
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectVariantsView extends HtmlView
{
    /**
     * Properties
     */
    private $Data;

    private $Project;

    private $addNewVariant;

    private $context;

    private $sumVariants;

    private $phaseName;

    /**
     * Current projectVariantId
     */
    private $currentVariantId;

    private $readOnly;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->Data          = $this->get('Data');
        $this->Project       = $this->get('Project');
        $this->addNewVariant = $this->get('addNewVariant', false);
        $this->context       = 'project-data';
        $this->sumVariants   = count($this->Data->name);
        $this->phaseName     = $this->get('phaseName');

        $this->currentVariantId = Elca::getInstance()->getProjectVariantId();
        $this->readOnly         = $this->get('readOnly');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'projectVariantForm';
        $Form   = new HtmlForm($formId, '/project-data/saveVariants/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');
        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->setDataObject($this->Data);
        $Form->setReadonly($this->readOnly);

        $Form->add(new HtmlHiddenField('projectId', $this->Project->getId()));

        if ($this->has('Validator')) {
            $Form->setValidator($this->get('Validator'));
        }

        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'project-variants']));
        $this->appendVariants($Form);
        $Form->appendTo($Container);
    }
    // End afterRender

    //////////////////////////////////////////////////////////////////////////////////////


    /**
     * Appends the geometry section
     *
     * @param  HtmlForm $Form
     */
    protected function appendVariants(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(t('Alle Projektvarianten für die Phase').' : '.$this->phaseName));
        $Group->addClass('clear');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Name'), ['class' => 'hl-name']));
        $Row->add(new HtmlTag('h5', t('Erstellt am'), ['class' => 'hl-created']));
        $Row->add(new HtmlTag('h5', t('Ersetzen'), ['class' => 'hl-replace']));
        $Row->add(new HtmlTag('h5', t('Aktionen'), ['class' => 'hl-actions']));

        $Container = $Group->add(new HtmlTag('div'));
        $Container->add(new HtmlHiddenField('a', $this->addNewVariant));
        $Ul = $Container->add(new HtmlTag('ul'));

        $counter = 0;
        foreach ($this->Data->name as $key => $processConfigId) {
            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'variant-row']));
            $this->appendVariant($Li, $key);
            $counter++;
        }

        if (!$this->readOnly) {
            $this->appendButtons($Container, $counter > 0);
        }
    }
    // End appendVariants

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends a variant row
     *
     * @param  HtmlForm $Form
     */
    protected function appendVariant(HtmlElement $Li, $key)
    {
        if ($this->currentVariantId == $key) {
            $Li->addClass('active');
        }

        $container = $Li->add(new HtmlTag('div'));
        $container->addClass('clearfix variant');

        $Request = FrontController::getInstance()->getRequest();

        if ((isset($this->Data->name[$key])) && isset($this->Data->created[$key])) {
            $container->add(
                new ElcaHtmlFormElementLabel('', new HtmlTextInput('name['.$key.']', $this->Data->name[$key]))
            );
            $Label = $container->add(new ElcaHtmlFormElementLabel('', new HtmlStaticText($this->Data->created[$key])));
            $Label->addClass('created');
        }

        if (!$this->readOnly) {
            /**
             * Search & Replace
             */
            $container->add(
                new HtmlLink(
                    t('Baustoffe'),
                    Url::factory('/project-data/replaceProcesses/', ['projectVariantId' => $key, 'init' => null])
                )
            )
                      ->addClass('function-link replace-material-link page');

            /**
             * Search & Replace
             */
            $container->add(
                new HtmlLink(
                    t('Komponenten'),
                    Url::factory('/project-data/replace-components/replaceComponents/', ['projectVariantId' => $key, 'init' => null])
                )
            )
                      ->addClass('function-link replace-components-link page');

            /**
             * Search & Replace
             */
            $container->add(
                new HtmlLink(
                    t('Bauteile'),
                    Url::factory('/project-data/replace-elements/replaceElements/', ['projectVariantId' => $key, 'init' => null])
                )
            )
                      ->addClass('function-link replace-elements-link page');

            /**
             * Delete and create Variant
             */
            $container->add(
                new HtmlLink(t('Kopieren'), Url::factory('/'.$this->context.'/copyVariant/', ['id' => $key]))
            )
                      ->addClass('function-link copy-link');

            if ($this->sumVariants > 1 && $this->currentVariantId != $key) {
                $container->add(
                    new HtmlLink(t('Löschen'), Url::factory('/'.$this->context.'/deleteVariant/', ['id' => $key]))
                )
                          ->addClass('function-link delete-link');
            }
        }
    }
    // End appendVariant


    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends submit button
     *
     * @param  HtmlElement $Container
     * @param  string      $name
     * @param  string      $caption
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $Container, $showSaveButton = false)
    {
        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');


        /**
         * Submit button
         */
        if ($showSaveButton) {
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveVariants', t('Speichern'), true));
        }
    }
    // End appendSubmitButton

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaProjectVariantsView
