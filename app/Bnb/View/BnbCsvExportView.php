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
class BnbCsvExportView extends HtmlView
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

    /**
     *
     */
    protected function afterRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'bnb-export']));

        if (is_object($this->get('ProjectVariant')) && $this->get('ProjectVariant')->isInitialized())
        {
            $Container->appendChild($this->getH3(t('CSV-Export herunterladen')));

            $P = $Container->appendChild($this->getP(t('Exportierte Projektvariante') . ' "'));
            $P->appendChild($this->getI($this->get('ProjectVariant')->getName()));
            $P->appendChild($this->getText('"'));
            $P->appendChild($this->getBr());
            $P->appendChild($this->getA(['href' => $this->get('downloadUrl'), 'class' => 'no-xhr'], $this->get('filename')));

            $Form = new HtmlForm('bnbExport', '/bnb/csv-export/');
            $Form->addClass('clearfix highlight-changes');

            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('clearfix column buttons');

            $ButtonGroup->add(new ElcaHtmlSubmitButton('back', t('Zurück'), true));

            $Form->appendTo($Container);
        }
        else
        {
            $Form = new HtmlForm('bnbExport', '/bnb/csv-export/create/');
            $Form->addClass('clearfix highlight-changes');

            if($this->has('Validator'))
            {
                $Form->setValidator($this->get('Validator'));
                $Form->setRequest(FrontController::getInstance()->getRequest());
            }

            $Data = (object) null;
            $Data->projectVariantId = Elca::getInstance()->getProjectVariantId();
            $Form->setDataObject($Data);
            $Form->add(new HtmlHiddenField('projectId', Elca::getInstance()->getProjectId()));

            $Group = $Form->add(new HtmlFormGroup(''));
            $Group->addClass('column clear');


            $SelectVariant = $Group->add(new ElcaHtmlFormElementLabel(t('Exportiere Projektvariante'), new HtmlSelectbox('projectVariantId', null, false), true));
            $SelectVariant->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
            foreach ($this->get('ProjectVariantSet') as $ProjectVariant)
                $SelectVariant->add(new HtmlSelectOption($ProjectVariant->getName(), $ProjectVariant->getId()));

            /**
             * Buttons
             */
            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('clearfix column buttons');

            $ButtonGroup->add(new ElcaHtmlSubmitButton('export', t('Export'), true));

            $Form->appendTo($Container);
        }
    }
    // End afterRender
}
// End BnbCsvExportView