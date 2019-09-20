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

namespace Elca\View\Import\Csv;

use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlTextInput;
use Beibob\HtmlTools\HtmlUploadInput;
use Elca\Db\ElcaBenchmarkSystemSet;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Db\ElcaConstrClassSet;
use Elca\Elca;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\View\ElcaProjectDataGeneralView;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 * @author  Fabian Moeller <fab@beibob.de>
 *
 */
class ProjectImportView extends HtmlView
{
    const BUILDMODE_DEFAULT = 'default';

    private $readOnly;

    /**
     * @var BenchmarkSystemsService
     */
    private $benchmarkSystemsService;

    private $validator;

    /**
     * Inits the view
     *
     * @param array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->benchmarkSystemsService = $this->get('benchmarkSystemsService');
        $this->validator               = $this->get('validator');

        $this->readOnly = $this->get('readOnly');
    }

    /**
     * Callback triggered after rendering the template
     *
     * @return void -
     * @internal param $ -
     */
    protected function beforeRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'project project-csv-import']));

        $form = new HtmlForm('csvImportForm', '/project-csv/validate/');
        $form->setAttribute('id', 'csvImportForm');
        $form->setAttribute('autocomplete', 'off');
        $form->setReadonly($this->readOnly);

        $form->setRequest(FrontController::getInstance()->getRequest());

        if ($this->has('validator')) {
            $form->setValidator($this->validator);
        }

        $form->addClass('highlight-changes');
        $form->addClass('projectForm');

        $this->appendProjectGroup($form);
        $this->appendImportGroup($form);

        $buttonGroup = $form->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('clear buttons');

        $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbruch')));
        $buttonGroup->add(new ElcaHtmlSubmitButton('upload', t('Absenden'), true));

        $form->appendTo($container);
    }

    protected function appendProjectGroup(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(t('Projektdaten')));
        $group->addClass('import-group');

        $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['name']),
                new HtmlTextInput('name'),
                true
            )
        );

        $selectMeasure = $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['constrMeasure']),
                new HtmlSelectbox('constrMeasure'),
                true
            )
        );
        $selectMeasure->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
        $selectMeasure->setAttribute('id', 'constrMeasure');
        foreach (Elca::$constrMeasures as $key => $val) {
            $selectMeasure->add(new HtmlSelectOption(t($val), $key));
        }

        $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['postcode']),
                new HtmlTextInput('postcode'),
                true,
                null,
                t('Geben Sie bitte mindestens die erste Stelle der Postleitzahl an')
            )
        );

        $select = $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['constrClassId']),
                new HtmlSelectbox('constrClassId'),
                true
            )
        );
        $select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));

        foreach (ElcaConstrClassSet::find(null, ['ref_num' => 'ASC']) as $ConstrClass) {
            $select->add(
                $option = new HtmlSelectOption(
                    $ConstrClass->getRefNum() . ' - ' . t($ConstrClass->getName()),
                    $ConstrClass->getId()
                )
            );
        }

        $selectBenchmark = $group->add(
            new ElcaHtmlFormElementLabel(
                t('Benchmarksystem'),
                new HtmlSelectbox('benchmarkVersionId'),
                false,
                null,
                t('Mit der Festlegung des Benchmarksystems wird die Baustoff-Datenbank bestimmt.')
            )
        );
        $selectBenchmark->setAttribute('id', 'selectBenchmarkSystem');

        $benchmarkSystems = ElcaBenchmarkSystemSet::find(['is_active' => true], ['name' => 'ASC']);
        foreach ($benchmarkSystems as $benchmarkSystem) {
            foreach (
                ElcaBenchmarkVersionSet::findActiveByBenchmarkSystemId(
                    $benchmarkSystem->getId(),
                    ['process_db_id' => 'DESC', 'name' => 'ASC']
                ) as $benchmarkVersion
            ) {
                $selectBenchmark->add(
                    $selectOpt = new HtmlSelectOption(
                        $benchmarkSystem->getName() . ' - ' . $benchmarkVersion->getName(),
                        $benchmarkVersion->getId()
                    )
                );
            }
        }

        $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['netFloorSpace']),
                new ElcaHtmlNumericInput('netFloorSpace'),
                true,
                'm2'
            )
        );
        $group->add(
            new ElcaHtmlFormElementLabel(
                t(ElcaProjectDataGeneralView::$captions['grossFloorSpace']),
                new ElcaHtmlNumericInput('grossFloorSpace'),
                true,
                'm2'
            )
        );
    }

    protected function appendImportGroup(HtmlForm $form): void
    {
        $group = $form->add(new HtmlFormGroup(t('Importdatei laden')));
        $group->addClass('import-group');
        $group->add(
            new HtmlTag(
                'a',
                t('Download CSV-Vorlage'),
                [
                    'href' => '/docs/downloads/Beispiel-CSV-Importdatei.csv',
                    'target' => 'blank',
                    'class' => 'no-xhr download-link',
                ]
            )
        );

        $group->add(new ElcaHtmlFormElementLabel(t('Datei (.csv, komma-separiert, UTF-8)'), new HtmlUploadInput('importFile')));

        $group->add(new HtmlTag('p', t('Die erste Zeile der Importdatei wird ignoriert!')));

    }
}
