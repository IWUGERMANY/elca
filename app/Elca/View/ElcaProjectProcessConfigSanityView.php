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

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\DbObjectSet;
use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Elca\Controller\ProjectData\ProjectElementSanityCtrl;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\View\helpers\ElcaHtmlProjectElementSanity;
use Elca\Db\ElcaProjectProcessConfigSanitySet;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds a list of elements with sanity check *
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaProjectProcessConfigSanityView extends HtmlView
{
    private $context;

    /**
     * @var DataObjectSet
     */
    private $elements;

    /**
     * @var ElcaProjectVariant
     */
    private $projectVariant;

    /**
     * @var []
     */
    private $newProcessConfigIds;

    private $readOnly;

    /**
     * Init
     *
     * @param  array $args
     */
    protected function init(array $args = array())
    {
        parent::init($args);

        $this->projectVariant = Elca::getInstance()->getProjectVariant();

        $this->context = $this->get('context');

        $this->elements = ElcaProjectProcessConfigSanitySet::findByProjectVariant(
            $this->projectVariant,
            $this->context
        );

        if ($this->has('newProcessConfigIds')) {
            $this->newProcessConfigIds = $this->get('newProcessConfigIds', []);
        } else {
            $namespace = Environment::getInstance()->getSession()->getNamespace(
                ProjectElementSanityCtrl::CONTEXT,
                true
            );

            $this->newProcessConfigIds = is_array($namespace->newProcessConfigIds) ? $namespace->newProcessConfigIds
                : [];
        }

        $this->readOnly = (bool)$this->get('readOnly');
    }
    // End init

    /**
     * Callback triggered before rendering the template
     *
     */
    protected function beforeRender()
    {
        $processDb = $this->projectVariant->getProject()->getProcessDb();

        $content = $this->appendChild(
            $this->getDiv(['id' => 'projectProcessConfigSanity', 'class' => 'elca-project-process-config-sanity'])
        );

        $content->appendChild(
            $this->getH3(
                t(
                    'Datenbankbezogene Baustoffanalyse-Funktion auf Basis der aktuell eingestellten Baustoffdatenbank "%name%"',
                    null,
                    ['%name%' => $processDb->getName()]
                )
            )
        );
        $content->appendChild(
            $this->getP(
                t(
                    'eLCA bietet Ihnen die Möglichkeit alle vorhanden ÖKOBAUDAT Version für die Erstellung einer Gebäudeökobilanz zu nutzen.
         Um sicherzustellen, dass die Materialdatensätze, die Sie z.B. aus einer Bauteilvorlage in Ihr Projekt übernehmen, auch in der von Ihnen für Ihr Projekt eingestellten ÖKOBAUDAT Version vorhanden sind,
         prüft eLCA die verwendeten Datensätze auf Konsistenz.'
                )
            )
        );

        $p = $content->appendChild(
            $this->getP(
                t(
                    'Sollte Sie in Ihrem Projekt eine Bauteilvorlage verwenden, die ein Material enthält, das der für Ihr Projekt zugewiesenen ÖKOBAUDAT Version unbekannt ist,
        weist eLCA Sie auf diesen Umstand hin und gibt Ihnen die Möglichkeit, den betreffenden Baustoff gegen einen, für Ihre unter Stammdaten eingestellte Datenbank, gültigen Datensatz auszutauschen.'
                )
            )
        );
        $p->appendChild($this->getBr());
        $p->appendChild(
            $this->getStrong(
                t(
                    'Sollten Sie diesem Hinweis nicht folgen, kann dem Material kein gültiger Wertebereich zugewiesen werden und wird somit nicht Gegenstand Ihrer Bilanzierung.'
                )
            )
        );

        if ($this->elements->count()) {
            $frontController = FrontController::getInstance();
            $request         = $frontController->getRequest();

            foreach ($this->elements as $element) {
                $element->newProcessConfigId[$element->process_config_id] = isset($this->newProcessConfigIds[$element->process_config_id])
                    ? $this->newProcessConfigIds[$element->process_config_id] : null;
            }

            $p = $content->appendChild($this->getP(t('Ergebnis der Analyse:').' '));
            $p->appendChild(
                $this->getStrong(
                    t(
                        'Bei der Analyse der Bauteile wurden Probleme in folgenden Bauteilen festgestellt. Diese Baustoffe werden nicht bilanziert!'
                    ),
                    array('class' => 'warning')
                )
            );

            $form = new HtmlForm(
                'project-process-config-sanity-form',
                $frontController->getUrlTo(ProjectElementSanityCtrl::class, 'replaceInvalidProcesses')
            );
            $form->addClass('highlight-changes');
            $form->setReadonly($this->readOnly);

            $form->add($table = new HtmlTable('elca-project-process-config-sanity'));
            $table->addColumn('context', t('Kontext'))->addClass('context');
            $table->addColumn('parent_context', t('Bauteil'))->addClass('context-name');
            $table->addColumn('context_name', t('Komponente'))->addClass('context-name');
            $table->addColumn('process_db_names', t('Vefügbar in'))->addClass('db-names');
            $table->addColumn('current_process_db', t('Nicht vefügbar in'))->addClass('current-process-db');
            $table->addColumn('process_config_name', t('Verwendeter Baustoff'))->addClass('process-config-name');
            $table->addColumn('newProcessConfigId', t('Ersetzen durch'))->addClass('replace-by process-config-selector');

            $head = $table->createTableHead();
            $tableRow = $head->addTableRow(new HtmlTableHeadRow());
            $tableRow->addClass('table-headlines');

            $body = $table->createTableBody();
            $row  = $body->addTableRow();
            $row->getColumn('context')->setOutputElement(new ElcaHtmlProjectElementSanity('context'));
            $row->getColumn('context_name')->setOutputElement(new ElcaHtmlProjectElementSanity('context_name'));
            $row->getColumn('current_process_db')->setOutputElement(new HtmlStaticText($processDb->getName()));
            $row->getColumn('process_db_names')->setOutputElement(new ElcaHtmlProjectElementSanity('process_db_names'));
            $row->getColumn('newProcessConfigId')->setOutputElement(
                $newProcessConfig = new ElcaHtmlProjectElementSanity('newProcessConfigId')
            );
            $newProcessConfig->setForm($form);

            $body->setDataSet($this->elements);

            if (!$this->readOnly && count($this->newProcessConfigIds) > 0) {
                $buttonGroup = $form->add(new HtmlFormGroup(''));
                $buttonGroup->addClass('clearfix buttons');
                $buttonGroup->add(new ElcaHtmlSubmitButton('cancel', t('abbrechen')));
                $buttonGroup->add(new ElcaHtmlSubmitButton('replace', t('Baustoffe ersetzen'), true));
            }

            $form->appendTo($content);
        } else {
            $p = $content->appendChild($this->getP(t('Ergebnis der Analyse:').' '));
            $p->appendChild($this->getStrong(t('Es wurden keine Probleme festgestellt.'), array('class' => 'ok')));
        }
    }
    // End beforeRender

}
// End ElcaProjectProcessConfigSanityView
