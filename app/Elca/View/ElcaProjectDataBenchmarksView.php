<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */
namespace Elca\View;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTag;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProjectVariant;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the project data benchmarks view
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @copyright  BEIBOB medienfreunde GbR
 */
class ElcaProjectDataBenchmarksView extends HtmlView
{
    /**
     * ProjectVariandtId
     */
    private $projectVariantId;

    /**
     * Data
     */
    private $Data;

    private $readOnly;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Init
     *
     * @param  array $args
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->projectVariantId = $this->get('projectVariantId');
        $this->Data = $this->get('Data');
        $this->readOnly = $this->get('readOnly');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        /**
         * Add Container
         */
        $Container = $this->appendChild($this->getDiv(['id' => 'content',
                                                            'class' => 'project-benchmarks']));

        $formId = 'projectDataBenchmarksForm';
        $Form = new HtmlForm($formId, '/project-data/saveBenchmarks/');
        $Form->setAttribute('id', $formId);
        $Form->addClass('clearfix highlight-changes');
        $Form->setReadonly($this->readOnly);

        $Form->setDataObject($this->Data);

        if($this->has('Validator'))
        {
            $Form->setValidator($this->get('Validator'));
            $Form->setRequest(FrontController::getInstance()->getRequest());
        }

        /**
         * Add hidden projectVariantId
         */
        $Form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));

        $this->appendBenchmarks($Form);

        $Form->appendTo($Container);
    }
    // End afterRender

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Appends the benchmarks form
     *
     * @param  HtmlForm $Form
     */
    protected function appendBenchmarks(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(t('Zielwertvereinbarungstabelle')));

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');
        $Row->add(new HtmlTag('h5', t('Wirkungskategorie'), ['class' => 'hl-indicator']));
        $Row->add(new HtmlTag('h5', t('BNB Zielwert / Mindest-Erfüllungsgrad'), ['class' => 'hl-benchmark']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'indicatorBenchmarks']));
        $Ul = $Container->add(new HtmlTag('ul', null));

        $ProjectVariant = ElcaProjectVariant::findById($this->projectVariantId);
        $processDbId = $ProjectVariant->getProject()->getProcessDbId();

        foreach(ElcaIndicatorSet::findWithPetByProcessDbId($processDbId) as $Indicator)
        {
            $key = $Indicator->getId();

            $Li = $Ul->add(new HtmlTag('li', null, ['class' => 'indicator-benchmark']));
            $Li->add(new ElcaHtmlFormElementLabel(t($Indicator->getName()), $Input = new ElcaHtmlNumericInput('benchmark['.$key.']'), false, null, t($Indicator->getDescription())));
            $Input->setAttribute('maxlength', 3);
        }

        if (!$this->readOnly) {
            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('buttons');
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveBenchmarks', t('Speichern')));
        }
    }
    // End appendBenchmarks

    //////////////////////////////////////////////////////////////////////////////////////
}
// End ElcaProjectDataBenchmarksView
