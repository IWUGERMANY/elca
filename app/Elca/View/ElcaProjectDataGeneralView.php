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
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlPasswordInput;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaBenchmarkSystemSet;
use Elca\Db\ElcaBenchmarkVersion;
use Elca\Db\ElcaBenchmarkVersionSet;
use Elca\Db\ElcaConstrClassSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectAttribute;
use Elca\Db\ElcaProjectAttributeSet;
use Elca\Db\ElcaProjectVariantSet;
use Elca\Elca;
use Elca\ElcaTimeFormat;
use Elca\Service\Admin\BenchmarkSystemsService;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 *
 *
 * @package elca
 * @author Patrick Kocurek <patrick@kocurek.de>
 * @author Tobias Lode <tobias@beibob.de>
 * @author Fabian Moeller <fab@beibob.de>
 *
 */
class ElcaProjectDataGeneralView extends HtmlView
{
    const BUILDMODE_CREATE  = 'create';
    const BUILDMODE_DEFAULT = 'default';

    /**
     * Captions
     *
     * @translate array Elca\View\ElcaProjectDataGeneralView::$captions
     */
    public static $captions = ['name'              => 'Projektname',
                                    'projectNr'         => 'Projektnummer',
                                    'lifeTime'          => 'Gebäude Nutzungsdauer',
                                    'constrMeasure'     => 'Baumaßnahme',
                                    'constrClassId'     => 'Bauwerkszuordnung',
                                    'street'            => 'Straße',
                                    'postcode'          => 'PLZ',
                                    'city'              => 'Stadt',
                                    'country'           => 'Land',
                                    'regionId'          => 'Region',
                                    // flächen
                                    'areas'             => 'Flächen',
                                    'floorSpace'        => 'Nutzfläche NF',
                                    'grossFloorSpace'   => 'Brutto-Grundfläche BGF',
                                    'netFloorSpace'     => 'Netto-Grundfläche NGF',
                                    'propertySize'      => 'Grundstücksfläche',
                                    'livingSpace'       => 'Wohnfläche',
                                    'isReference'       => 'Referenzprojekt',
                                    'isExtantBulding'   => 'Bestandsgebäude',
                                    'isListed'          => 'Denkmalgeschützt',
                                    'bnbNr'             => 'BNB Nummer',
                                    'eGisNr'            => 'eGis Nummer',
                                    'editor'            => 'Bearbeiter',
                                    'protectProject'    => 'Projekt schützen',
                                    'projectAttributes'    => 'Projekt Attribute',
                                    'pw'          => 'Passwort',
                                    'pwRepeat'    => 'Wiederholen',
                               'pwSetDate' => 'Passwort gesetzt am',
                                    ];


    /**
     * Properties
     */
    private $ElcaProcessDbSet;
    private $ElcaConstrCatalogSet;
    private $ElcaConstrDesignSet;
    private $buildMode;
    private $readOnly;

    /**
     * @var BenchmarkSystemsService
     */
    private $benchmarkSystemsService;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);
        $this->ElcaProcessDbSet     = $this->get('ElcaProcessDbSet');
        $this->ElcaConstrCatalogSet = $this->get('ElcaConstrCatalogSet');
        $this->ElcaConstrDesignSet  = $this->get('ElcaConstrDesignSet');

        $this->benchmarkSystemsService = $this->get('benchmarkSystemsService');

        $this->buildMode = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->readOnly = $this->get('readOnly');
    }
    // End init

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered after rendering the template
     *
     * @internal param $ -
     * @return void -
     */
    protected function beforeRender()
    {
        $Container = $this->appendChild($this->getDiv(['id' => 'content', 'class' => 'project general-'. $this->buildMode]));

        $Form = new HtmlForm('generalForm', $this->get('formAction'));
        $Form->setAttribute('id', 'generalForm');
        $Form->setAttribute('autocomplete', 'off');

        $Form->setReadonly($this->readOnly);

        $Form->setRequest(FrontController::getInstance()->getRequest());
        $Form->addClass('highlight-changes');
        $Form->addClass('projectForm');

        $DO = new \stdClass();

        if ($this->has('DataObject'))
            $DO = $Form->setDataObject($this->get('DataObject'));

        if($this->has('Validator'))
            $Form->setValidator($this->get('Validator'));

        $benchmarkVersion = ElcaBenchmarkVersion::findById($DO->benchmarkVersionId);

        $group = $Form->add(new HtmlFormGroup(''));
        $group->addClass('column clear');
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['name']), new HtmlTextInput('name'), true));

        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['projectNr']), new HtmlTextInput('projectNr')));

        $SelectMeasure = $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['constrMeasure']), new HtmlSelectbox('constrMeasure', null, $this->buildMode == self::BUILDMODE_DEFAULT), true));
        $SelectMeasure->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
        $SelectMeasure->setAttribute('id','constrMeasure');
        foreach (Elca::$constrMeasures as $key => $val)
            $SelectMeasure->add(new HtmlSelectOption(t($val), $key));

        if (self::BUILDMODE_CREATE === $this->buildMode) {
            $group->add(
                new ElcaHtmlFormElementLabel(
                    t('Wollen Sie mit einer überschlägigen Prognose starten?'),
                    new HtmlCheckbox('startWithProjection')
                )
            )->addClass('start-with-projection');
        }


        $lifeTimeIsReadOnly = null !== $benchmarkVersion->getProjectLifeTime();

        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['lifeTime']), $projectLifeTimeInput = new ElcaHtmlNumericInput('lifeTime'),true, 'Jahre'));
        $projectLifeTimeInput->setReadonly($this->readOnly || $lifeTimeIsReadOnly, false);

        $select = $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['constrClassId']), new HtmlSelectbox('constrClassId'), true));
        $select->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));

        foreach(ElcaConstrClassSet::find(null, ['ref_num' => 'ASC']) as $ConstrClass) {
            $select->add(
                $option = new HtmlSelectOption($ConstrClass->getRefNum().' - '.t($ConstrClass->getName()), $ConstrClass->getId())
            );
        }

        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['isExtantBulding']), new HtmlCheckbox('isExtantBuilding')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['isListed']), new HtmlCheckbox('isListed')));


        ////// left column ////
        $group = $Form->add(new HtmlFormGroup(''));
        $group->addClass('column');
        $group->add(new ElcaHtmlFormElementLabel(t('Beschreibung'), new HtmlTextArea('description')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['street']), new HtmlTextInput('street')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['postcode']), new HtmlTextInput('postcode'), true, null, t('Geben Sie bitte mindestens die erste Stelle der Postleitzahl an')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['city']), new HtmlTextInput('city')));
        //$Group->add(new ElcaHtmlFormElementLabel(t(self::$captions['country']), new HtmlTextInput('country')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['editor']), new HtmlTextInput('editor')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['bnbNr']), new HtmlTextInput('bnbNr')));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['eGisNr']), new HtmlTextInput('eGisNr')));

        ////// Projektvorgaben ////
        $group = $Form->add(new HtmlFormGroup(t('Projektvorgaben')));
        $group->addClass('column clear');

        $selectBenchmark = $group->add(new ElcaHtmlFormElementLabel(t('Benchmarksystem'), new HtmlSelectbox('benchmarkVersionId'), false, null, t('Mit der Festlegung des Benchmarksystems wird die Baustoff-Datenbank bestimmt.')));
        $selectBenchmark->setAttribute('id', 'selectBenchmarkSystem');
        $selectBenchmark->add($selectOpt = new HtmlSelectOption('-- ' . t('Kein Benchmark verwenden') . ' --', ''));
        $selectOpt->setAttribute('data-display-living-space', true);

        $BenchmarkSystems = ElcaBenchmarkSystemSet::find(['is_active' => true], ['name' => 'ASC']);
        foreach ($BenchmarkSystems as $benchmarkSystem) {
            $benchmarkSystemModel = $this->benchmarkSystemsService->benchmarkSystemModelByClassName($benchmarkSystem->getModelClass());

            foreach (ElcaBenchmarkVersionSet::findWithConstrClassIds(['benchmark_system_id' => $benchmarkSystem->getId(), 'is_active' => true], ['name' => 'ASC', 'id' => 'ASC']) as $benchmarkVersion) {
                $selectOpt = $selectBenchmark->add(new HtmlSelectOption($benchmarkSystem->getName() .' - '.$benchmarkVersion->getName(), $benchmarkVersion->getId()));
                $selectOpt->setAttribute('data-process-db-id', $benchmarkVersion->getProcessDbId());
                $selectOpt->setAttribute('data-constr-class-ids', json_encode($benchmarkVersion->getConstrClassIds()));
                $selectOpt->setAttribute('data-display-living-space', null !== $benchmarkSystemModel ? (int)$benchmarkSystemModel->displayLivingSpace() : null);
                $selectOpt->setAttribute('data-project-life-time', null !== $benchmarkSystemModel ? $benchmarkVersion->getProjectLifeTime() : null);
            }
        }

        $SelectDb = $group->add(new ElcaHtmlFormElementLabel(t('Baustoff Datenbank'), new HtmlSelectbox('processDbId', null, $this->readOnly || (bool)$DO->benchmarkVersionId), true));
        $SelectDb->setAttribute('id', 'selectProcessDb');
        $SelectDb->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
        foreach ($this->ElcaProcessDbSet as $ElcaProcessDb) {
            $SelectDb->add(new HtmlSelectOption($ElcaProcessDb->name, $ElcaProcessDb->id));
        }
        $projectId = Elca::getInstance()->getProjectId();
        if ($this->buildMode == self::BUILDMODE_DEFAULT)
        {
            $variantLabel = $group->add(new ElcaHtmlFormElementLabel(t('Aktive Projektvariante'), new HtmlSelectbox('currentVariantId')));
            $variantLabel->add(new HtmlSelectOption('-- ' . t('Bitte wählen') . ' --', ''));
            foreach( ElcaProjectVariantSet::findByProjectId($projectId) as $variant) {
                $variantLabel->add(new HtmlSelectOption($variant->getName().' ['. t($variant->getPhase()->getName()).']', $variant->getId()));
            }

            $CatalogLabel = $group->add(new ElcaHtmlFormElementLabel(t('Bevorzugter Bauteilkatalog'), new HtmlSelectbox('constrCatalogId')));
            $CatalogLabel->add(new HtmlSelectOption('-- ' . t('Alle') . ' --', ''));
            foreach( $this->ElcaConstrCatalogSet as $ConstrCatalog )
                $CatalogLabel->add(new HtmlSelectOption(t($ConstrCatalog->name), $ConstrCatalog->id));

            $DesignLabel = $group->add(new ElcaHtmlFormElementLabel(t('Bevorzugte Bauweise'), new HtmlSelectbox('constrDesignId')));
            $DesignLabel->add(new HtmlSelectOption('-- ' . t('Alle') . ' --', ''));
            foreach( $this->ElcaConstrDesignSet as $DesignCatalog )
                $DesignLabel->add(new HtmlSelectOption(t($DesignCatalog->name), $DesignCatalog->id));
        }

        ////// project constructions ////

        $group = $Form->add(new HtmlFormGroup(t(self::$captions['areas'])));
        $group->addClass('column');
        $group->setAttribute('id','projectConstructions');
        $livingSpaceLabel = $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['livingSpace']), null, true, 'm2'));
        $livingSpaceInput = $livingSpaceLabel->add(new ElcaHtmlNumericInput('livingSpace'));

        $benchmarkSystemModel = $this->benchmarkSystemsService->benchmarkSystemModelByVersionId($benchmarkVersion->getId());
        if ($benchmarkVersion->isInitialized() && $benchmarkSystemModel && $benchmarkSystemModel->displayLivingSpace()) {
            $livingSpaceLabel->addClass('hidden');
            $livingSpaceInput->setReadonly(true, false);
        }
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['netFloorSpace']), new ElcaHtmlNumericInput('netFloorSpace'), true, 'm2'));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['grossFloorSpace']), new ElcaHtmlNumericInput('grossFloorSpace'), true, 'm2'));

        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['floorSpace']), new ElcaHtmlNumericInput('floorSpace'), false, 'm2'));
        $group->add(new ElcaHtmlFormElementLabel(t(self::$captions['propertySize']), new ElcaHtmlNumericInput('propertySize'), false, 'm2'));

        $group = $Form->add(new HtmlFormGroup(t(self::$captions['protectProject'])));
        $group->addClass('column');
        $group->add(
            new ElcaHtmlFormElementLabel(
                t(self::$captions['pw']),
                $passwordField = new HtmlPasswordInput('pw')
            )
        );
        $passwordField->setAttribute('autocomplete', 'new-password');

        $group->add(
            new ElcaHtmlFormElementLabel(
                t(self::$captions['pwRepeat']),
                $repeatField = new HtmlPasswordInput('pwRepeat')
            )
        );
        $repeatField->setAttribute('autocomplete', 'new-password');

        $projectAttributes = ElcaProjectAttributeSet::find(['project_id' => $projectId], ['ident' => 'ASC']);

        /**
         * @var ElcaProjectAttribute[] $filteredAttributes
         */
        $filteredAttributes = [];
        foreach ($projectAttributes as $projectAttribute) {
            if (\utf8_substr($projectAttribute->getIdent(), 0, 5) === 'elca.') {
                continue;
            }

            $filteredAttributes[] = $projectAttribute;
        }

        if (count($filteredAttributes)) {
            $group = $Form->add(new HtmlFormGroup(t(self::$captions['projectAttributes'])));
            $group->addClass('column');

            foreach ($filteredAttributes as $projectAttribute) {
                $group->add(
                    new ElcaHtmlFormElementLabel(
                        t($projectAttribute->getCaption()),
                        new HtmlStaticText($projectAttribute->getValue())
                    )
                );
            }
        }

        if (!$this->readOnly) {
            $ButtonGroup = $Form->add(new HtmlFormGroup(''));
            $ButtonGroup->addClass('clear buttons');

            if ($this->buildMode == self::BUILDMODE_DEFAULT) {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('save', t('Speichern'), true));

            } else {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbruch')));
                $ButtonGroup->add(new ElcaHtmlSubmitButton('create', t('Erzeugen'), true));
            }
        }

        $Form->appendTo($Container);

    }
    // End beforeRender
}
// End ElcaProjectDataGeneralView
