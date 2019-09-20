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

use Beibob\Blibs\Environment;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlCheckbox;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlEntityReference;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormElement;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Beibob\HtmlTools\HtmlTextArea;
use Beibob\HtmlTools\HtmlTextInput;
use DOMElement;
use DOMNode;
use Elca\Db\ElcaLifeCycle;
use Elca\Db\ElcaProcessCategory;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigAttribute;
use Elca\Db\ElcaProcessConfigName;
use Elca\Db\ElcaProcessConfigVariantSet;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Common\Unit;
use Elca\Model\Process\ProcessDbRepository;
use Elca\Model\ProcessConfig\Conversion\Conversion;
use Elca\Model\ProcessConfig\Conversion\ConversionType;
use Elca\Model\ProcessConfig\Conversion\ImportedLinearConversion;
use Elca\Model\ProcessConfig\Conversion\LinearConversion;
use Elca\Model\ProcessConfig\Conversion\RecommendedConversion;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycle;
use Elca\Model\ProcessConfig\LifeCycle\ProcessLifeCycleRepository;
use Elca\Model\ProcessConfig\ProcessConfigId;
use Elca\Service\ElcaLocale;
use Elca\Service\ProcessConfig\Conversions;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Elca\View\helpers\ElcaHtmlSvgPatternSelect;
use Elca\View\helpers\ElcaNumberFormatConverter;
use Elca\View\helpers\ElcaProcessesConverter;

/**
 * Builds the general tab content for process configs
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaProcessConfigGeneralView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_DEFAULT = 'default';
    const BUILDMODE_INSERT = 'insert';
    const BUILDMODE_CONVERSIONS = 'conv';

    /**
     * @translate value 'Ergänzt durch das BBSR'
     */
    const NO_IDENT = 'Ergänzt durch das BBSR';

    /**
     * Conversion ident string map
     *
     * @translate array Elca\View\ElcaProcessConfigGeneralView::$conversionIdents
     */
    public static $conversionIdents = [
        ElcaProcessConversion::IDENT_INITIAL    => 'Umrechnung nach Baustoffdatenbank',
        ElcaProcessConversion::IDENT_PRODUCTION => 'Bezugsgröße aus Baustoffdatenbank',
        ConversionType::GROSS_DENSITY           => 'Rohdichte',
        ConversionType::BULK_DENSITY            => 'Schüttdichte',
        ConversionType::AVG_MPUA                => 'Flächengewicht',
        ConversionType::LAYER_THICKNESS         => 'Schichtdicke',
        ConversionType::PRODUCTIVENESS          => 'Ergiebigkeit',
        ConversionType::LINEAR_DENSITY          => 'Längengewicht',
        ConversionType::ENERGY_EQUIVALENT       => 'Energieäquivalent',
    ];

    /**
     * Process config
     *
     * @var ElcaProcessConfig $processConfig
     */
    private $processConfig;

    /**
     * Read only
     */
    private $readOnly;

    /**
     * Current buildmode
     */
    private $buildMode;

    private $processCategoryNodeId;

    /**
     * @var Conversions
     */
    private $conversionService;

    /**
     * @var ProcessLifeCycleRepository
     */
    private $processLifeCycleRepository;

    /**
     * @var ProcessDbRepository
     */
    private $processDbRepository;

    /**
     * Init
     *
     * @param  array $args
     *
     * @return -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->processConfig = ElcaProcessConfig::findById($this->get('processConfigId'));

        $this->buildMode             = $this->get('buildMode', self::BUILDMODE_DEFAULT);
        $this->processCategoryNodeId = $this->get(
            'processCategoryNodeId',
            $this->processConfig->getProcessCategoryNodeId()
        );

        if ($this->get('readOnly', false)) {
            $this->readOnly = true;
        }

        $container                        = Environment::getInstance()->getContainer();
        $this->conversionService          = $container->get(Conversions::class);
        $this->processLifeCycleRepository = $container->get(ProcessLifeCycleRepository::class);
        $this->processDbRepository        = $container->get(ProcessDbRepository::class);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     * @return void -
     */
    protected function beforeRender()
    {
        $form = new HtmlForm('processConfigForm', '/processes/saveConfig/');
        $form->setAttribute('id', 'processConfig');

        if ($this->readOnly) {
            $form->setReadonly();
        }

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
            $form->setRequest(FrontController::getInstance()->getRequest());
        }

        if ($this->processConfig instanceOf ElcaProcessConfig && $this->processConfig->isInitialized()) {
            $form->addClass('highlight-changes');
            $form->setDataObject($this->processConfig);
            $form->add(new HtmlHiddenField('processConfigId', $this->processConfig->getId()));
        } else {
            $form->add(new HtmlHiddenField('processCategoryNodeId', $this->processCategoryNodeId));
        }

        switch ($this->buildMode) {
            case self::BUILDMODE_CONVERSIONS:
                $this->appendConversions($form, $this->get('addConversion'));

                // append form to dummy container
                $DummyContainer = $this->appendChild($this->getDiv());
                $form->appendTo($DummyContainer);

                // extract conversion element and replace it with the dummy container
                $content = $this->getElementById('conversions');
                $this->replaceChild($content, $DummyContainer);
                break;

            case self::BUILDMODE_INSERT:
                $this->appendDefault($form);
                $this->appendButtons($form);

                $content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-general']));
                $form->appendTo($content);
                break;

            default:
            case self::BUILDMODE_DEFAULT:
                $this->appendDefault($form);
                $this->appendConversions($form);
                $this->appendButtons($form);

                $content = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-general']));
                $this->appendIdInfo($content);

                $form->appendTo($content);

                $this->appendVariants($content);
                break;
        }
    }
    // End beforeRender


    /**
     * Callback triggered before rendering the template
     *
     * @param HtmlForm $form
     *
     * @return void -
     */
    protected function appendDefault(HtmlForm $form)
    {
        /**
         * Name, reference (left column)
         */
        $lftGroup = $form->add(new HtmlFormGroup(''));
        $lftGroup->addClass('clearfix properties column');

        $lftGroup->add(new ElcaHtmlFormElementLabel(t('Name'), new HtmlTextInput('name'), true));
		
		if($this->processConfig->isInitialized()) {
			$processConfigName = ElcaProcessConfigName::findByProcessConfigIdAndLang(
				$this->processConfig->getId(),
				Environment::getInstance()->getContainer()->get(ElcaLocale::class)->getLocale()
			);
			$lftGroup->add(
				new ElcaHtmlFormElementLabel(
					t('Übersetzung'), new HtmlStaticText($processConfigName->getName() .' ['. $processConfigName->getLang() .']')
				)
			);
		} else {
			$lftGroup->add(
				new ElcaHtmlFormElementLabel(t('Übersetzung'), new HtmlStaticText('keine Übersetzung'))
			);			
		}
        $lftGroup->add(new ElcaHtmlFormElementLabel(t('Notizen'), new HtmlTextarea('description')));
        $lftGroup->add(new ElcaHtmlFormElementLabel(t('Sichtbar für Anwender'), new HtmlCheckbox('isReference')));

        $is4108Compat = ElcaProcessConfigAttribute::findValue(
            $this->processConfig->getId(),
            ElcaProcessConfigAttribute::IDENT_4108_COMPAT
        );
        $lftGroup->add(
            new ElcaHtmlFormElementLabel(
                t('4108 Baustoff'),
                new HtmlCheckbox('is4108Compat', $is4108Compat, '', $this->readOnly)
            )
        );

        $rgtGroup = $form->add(new HtmlFormGroup(t(t('Nutzungsdauern'))));
        $rgtGroup->addClass('clearfix life-times column right');
        $rgtGroup->add(
            new ElcaHtmlFormElementLabel(
                t('Allgemeine Information zur Nutzungsdauer'),
                new HtmlTextInput('lifeTimeInfo')
            )
        );
        $rgtGroup->add(new ElcaHtmlFormElementLabel(t('Min.'), new ElcaHtmlNumericInput('minLifeTime'), false, 'a'));
        $rgtGroup->add(
            new ElcaHtmlFormElementLabel(t('Info zur minimalen Nutzungsdauer'), new HtmlTextInput('minLifeTimeInfo'))
        );
        $rgtGroup->add(new ElcaHtmlFormElementLabel(t('Mittel'), new ElcaHtmlNumericInput('avgLifeTime'), false, 'a'));
        $rgtGroup->add(
            new ElcaHtmlFormElementLabel(t('Info zur mittleren Nutzungsdauer'), new HtmlTextInput('avgLifeTimeInfo'))
        );
        $rgtGroup->add(new ElcaHtmlFormElementLabel(t('Max.'), new ElcaHtmlNumericInput('maxLifeTime'), false, 'a'));
        $rgtGroup->add(
            new ElcaHtmlFormElementLabel(t('Info zur maximalen Nutzungsdauer'), new HtmlTextInput('maxLifeTimeInfo'))
        );

        $numberFormatConverter = new ElcaNumberFormatConverter(2);
        $lftGroup->add(
            new ElcaHtmlFormElementLabel(
                t('Rohdichte'),
                new ElcaHtmlNumericInput('density', null, $this->readOnly, $numberFormatConverter),
                false,
                'kg / m³'
            )
        );
        $lftGroup->add(
            new ElcaHtmlFormElementLabel(
                t('Dicke'),
                new ElcaHtmlNumericInput(
                    'defaultSize',
                    $this->processConfig->getDefaultSize() * 1000,
                    $this->readOnly,
                    $numberFormatConverter
                ),
                false,
                'mm',
                t('Vorgabewert für die Dicke')
            )
        );
        $lftGroup->add(
            new ElcaHtmlFormElementLabel(
                t('Faktor Hs/Hi'),
                new ElcaHtmlNumericInput('fHsHi', null, $this->readOnly, $numberFormatConverter),
                false,
                null,
                t('Vorgabewert für Nutzungsbaustoffe')
            )
        );
		
		$numberFormatConverter = new ElcaNumberFormatConverter(0);
		
		$wasteCodeElement = new ElcaHtmlNumericInput('wasteCode', null, $this->readOnly, $numberFormatConverter);
		$wasteCodeElement->setAttribute('size', 6);
		$wasteCodeElement->setAttribute('maxlength', 6);
		
		$lftGroup->add(
			new ElcaHtmlFormElementLabel(
				t('AVV'),
				$wasteCodeElement,
				false,
				null,
				t('Abfallschlüssel gemäß Abfallverzeichnis-Verordnung')
			)
		);
		
		// admin user only
		if(!$this->readOnly) {
			
			$wasteCodeSuffixElement = new ElcaHtmlNumericInput('wasteCodeSuffix', null, $this->readOnly, $numberFormatConverter);
			$wasteCodeSuffixElement->setAttribute('size', 3);
			$wasteCodeSuffixElement->setAttribute('maxlength', 3);

			$lftGroup->add(
				new ElcaHtmlFormElementLabel(
					t('AVV Suffix'),
					$wasteCodeSuffixElement,
					false,
					null,
					t('Suffix für Abfallschlüssel gemäß Abfallverzeichnis-Verordnung')
				)
			);		
		}
		
		$numberFormatConverter = new ElcaNumberFormatConverter(3);
		
		$lambdaElement = new ElcaHtmlNumericInput('lambdaValue', null, $this->readOnly, $numberFormatConverter);
	
		$lftGroup->add(
			new ElcaHtmlFormElementLabel(
				t('Lamda (λ)'),
				$lambdaElement,
				false,
				'W/mK'				
			)
		);
		
		if(!$this->readOnly) {
	
			$lftGroup->add(new ElcaHtmlFormElementLabel(t('Stoffgruppe A'), new HtmlCheckbox('elementGroupA')));
			$lftGroup->add(new ElcaHtmlFormElementLabel(t('Stoffgruppe B'), new HtmlCheckbox('elementGroupB')));	

		}
		
        //$LftGroup->add(new ElcaHtmlFormElementLabel(t('Wärmeleitfähigkeit'), new ElcaHtmlNumericInput('thermalConductivity'), false, 'W / mk'));
        //$LftGroup->add(new ElcaHtmlFormElementLabel(t('Wärmedurchgangswiderstand'), new ElcaHtmlNumericInput('thermalResistance'), false, 'Km² / W'));

        $ProcessCategory = ElcaProcessCategory::findByNodeId($this->processCategoryNodeId);

        if ($ProcessCategory->isInitialized()) {
            list($mainCatNo, $subCatNo) = explode('.', $ProcessCategory->getRefNum());

            // only show in transport category
            if ($ProcessCategory->getRefNum() == '9.03') {
                $payLoad    = ElcaProcessConfigAttribute::findValue(
                    $this->processConfig->getId(),
                    ElcaProcessConfigAttribute::IDENT_TRANSPORT_PAYLOAD
                );
                $efficiency = ElcaProcessConfigAttribute::findValue(
                    $this->processConfig->getId(),
                    ElcaProcessConfigAttribute::IDENT_TRANSPORT_EFFICIENCY
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Nutzlast'),
                        new ElcaHtmlNumericInput('transportPayLoad', $payLoad, $this->readOnly),
                        false,
                        't',
                        t('Vorgabewert für Nutzlast')
                    )
                );
                $NumberConverter = new ElcaNumberFormatConverter(1, true);
                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Auslastungsgrad'),
                        new ElcaHtmlNumericInput('transportEfficiency', $efficiency, $this->readOnly, $NumberConverter),
                        false,
                        '%'
                    )
                );
            }

            // SVG pattern select only in categories 1-7
            if ($mainCatNo < 8) {
                $PatternSelect = $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Schraffur'),
                        new ElcaHtmlSvgPatternSelect(
                            'svgPatternId',
                            null,
                            $this->readOnly
                        ),
                        false, null,
                        t('Schraffur für die Bauteildarstellung')
                    )
                );
                $PatternSelect->setDefaultSvgPatternId($ProcessCategory->getSvgPatternId());
            }
        }

        /**
         * Add operational attributes
         */
        if ($this->processConfig &&
            $this->processConfig->isInitialized() &&
            ElcaProcessSet::dbCountByProcessConfigId(
                $this->processConfig->getId(),
                ['life_cycle_phase' => ElcaLifeCycle::PHASE_OP]
            ) > 0
        ) {

            $opAsSupply     = ElcaProcessConfigAttribute::findValue(
                $this->processConfig->getId(),
                ElcaProcessConfigAttribute::IDENT_OP_AS_SUPPLY
            );
            $opInvertValues = ElcaProcessConfigAttribute::findValue(
                $this->processConfig->getId(),
                ElcaProcessConfigAttribute::IDENT_OP_INVERT_VALUES
            );

            $lftGroup->add(new HtmlTag('h5', t('Endenergiebereitstellung regenerativ')));
            $lftGroup->add(
                new ElcaHtmlFormElementLabel(
                    t('Freigabe für Endenergiebereitstellung'),
                    new HtmlCheckbox('opAsSupply', $opAsSupply, '', $this->readOnly),
                    false,
                    null,
                    t('Kann für die Endenergiebereitstellung ausgewählt werden')
                )
            );
            $lftGroup->add(
                new ElcaHtmlFormElementLabel(
                    t('Indikatorenwerte negieren'),
                    new HtmlCheckbox('opInvertValues', $opInvertValues, '', $this->readOnly),
                    false,
                    null,
                    t('Die Indikatorenwerte werden mit -1 multipliziert')
                )
            );

        }

        if ($this->processConfig && $this->processConfig->isInitialized()) {
            $rgtGroup->add(new HtmlTag('h5', t('Bezugsgrößen')));

            $processConfigId   = new ProcessConfigId($this->processConfig->getId());
            $processLifeCycles = $this->processLifeCycleRepository->findAllByProcessConfigId($processConfigId);

            usort(
                $processLifeCycles,
                function (ProcessLifeCycle $a, ProcessLifeCycle $b) {
                    return $a->processDbId()->value() <=> $b->processDbId()->value();
                }
            );

            $table = new HtmlTable('quantitative-references');
            $table->addColumn('processDb', t('Baustoffdatenbank'))->addClass('process-db');
            $table->addColumn('quantitativeReference', t('Quantitative Referenz'))->addClass('quantitative-reference');

            $head = $table->createTableHead();
            $head->addTableRow(new HtmlTableHeadRow());

            $body = $table->createTableBody();
            $row  = $body->addTableRow();
            $row->getColumn('quantitativeReference')->setOutputElement(
                new ElcaHtmlNumericText('quantitativeReference')
            );

            $dataSet = [];
            foreach ($processLifeCycles as $processLifeCycle) {
                if (!$processDb = $this->processDbRepository->findById($processLifeCycle->processDbId())) {
                    continue;
                }
                if (!$quantitativeReference = $processLifeCycle->quantitativeReference()) {
                    continue;
                }

                $dataSet[] = (object)[
                    'processDb'             => $processDb->name(),
                    'quantitativeReference' => ElcaNumberFormat::formatQuantity(
                        $quantitativeReference->value(),
                        (string)$quantitativeReference->unit()
                    ),
                ];
            }

            $body->setDataSet($dataSet);

            $rgtGroup->add($table);
        }
    }
    // End appendDefault


    /**
     * Appends the buttons
     *
     * @param  HtmlForm $Form
     *
     * @return void -
     */
    protected function appendButtons(HtmlForm $Form)
    {
        /**
         * Buttons
         */
        $ButtonGroup = $Form->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        if (!$Form->isReadonly()) {
            $ButtonGroup->add(new ElcaHtmlSubmitButton('saveGeneral', t('Speichern'), true));

            if (!$this->processConfig instanceOf ElcaProcessConfig || !$this->processConfig->isInitialized()) {
                $ButtonGroup->add(new ElcaHtmlSubmitButton('cancel', t('Abbruch')));
            }
        }
    }
    // End appendButtons


    /**
     * Appends the conversion form group
     *
     * @param HtmlForm $Form
     * @param bool     $addNew
     */
    private function appendConversions(HtmlForm $Form, $addNew = false)
    {
        if (!$this->processConfig instanceOf ElcaProcessConfig || !$this->processConfig->isInitialized()) {
            return;
        }

        $rightGroup = $Form->add(new HtmlFormGroup(t('Umrechnungsfaktoren')));
        $rightGroup->setAttribute('id', 'conversions');
        $rightGroup->addClass('clearfix column clear');

        if (!$this->readOnly) {
            $Link = $rightGroup->add(
                new HtmlLink(
                    '+ ' . t('Hinzufügen'),
                    Url::factory('/processes/addConversion/', ['p' => $this->processConfig->getId()])
                )
            );
            $Link->addClass('function-link add-conversion');
            $Link->setAttribute('title', t('Einen neuen Umrechnungsfaktor hinzufügen'));
        }

        $Row = $rightGroup->add(new HtmlTag('div'));
        $Row->addClass('hl-row');

        $Row->add(new HtmlTag('h5', t('Eingangsgröße'), ['class' => 'hl-input']));
        $Row->add(new HtmlTag('h5', t('Ausgangsgröße'), ['class' => 'hl-output']));
        $Row->add(new HtmlTag('h5', t('Informationen'), ['class' => 'hl-ident']));

        if ($this->processConfig instanceOf ElcaProcessConfig && $this->processConfig->isInitialized()) {
            $processConfigId        = new ProcessConfigId($this->processConfig->getId());
            $requiredConversions    = $this->conversionService->findAllRequiredConversions($processConfigId);
            $requiredUnits          = $this->conversionService->findAllRequiredUnits($processConfigId);
            $additionalConversions  = $this->conversionService->findAllAdditionalConversions($processConfigId);
            $recommendedConversions = $this->conversionService->findRecommendedConversions($processConfigId);

            foreach ($requiredConversions as $requiredConversion) {
                $this->appendConversionRow($rightGroup, $requiredConversion, $requiredUnits, true);
            }

            if (!$additionalConversions->isEmpty()) {
                $rightGroup->add(new HtmlTag('br'));

                foreach ($additionalConversions as $additionalConversion) {
                    $this->appendConversionRow($rightGroup, $additionalConversion, $requiredUnits, false);
                }
            }

            if (!$recommendedConversions->isEmpty()) {
                $rightGroup->add(new HtmlTag('br'));

                foreach ($recommendedConversions as $recommendedConversion) {
                    $this->appendConversionRow($rightGroup, $recommendedConversion, $requiredUnits, false);
                }
            }

            if ($addNew) {
                $this->appendConversionRow($rightGroup, null, $requiredUnits);
            }
        }
    }


    private function appendConversionRow(
        HtmlElement $container,
        Conversion $conversion = null,
        array $requiredUnits = [],
        bool $isRequired = false
    ) {
        if (null === $conversion) {
            $this->appendNewConversionRow($container, $requiredUnits, $isRequired);

            return;
        }

        if ($conversion->isTrivial()) {
            return;
        }

        $isImported     = $conversion instanceof ImportedLinearConversion;
        $isInUse        = $this->conversionService->isBeingUsed($conversion);
        $inUnit         = (string)$conversion->fromUnit();
        $outUnit        = (string)$conversion->toUnit();
        $conversionId   = $conversion instanceof LinearConversion && $conversion->hasSurrogateId()
            ? $conversion->surrogateId() : 'new_' . $inUnit . '_' . $outUnit;
        $isGrossDensity = $conversion->type()->isGrossDensity();

        $row = $container->add(new HtmlTag('div'));
        $row->addClass('elt-row');

        /**
         * InUnit
         */
        if ($isRequired || $isImported || $isGrossDensity) {
            $row->add(new HtmlHiddenField('inUnit_' . $conversionId, $inUnit));
            $this->appendElement($row, new HtmlStaticText(ElcaNumberFormat::formatQuantity(1, $inUnit)))->addClass(
                'elt-in-unit'
            );
        } else {
            $this->appendElement(
                $row,
                $inSelect = new HtmlSelectbox('inUnit_' . $conversionId, $conversion ? $inUnit : null)
            )->addClass('elt-in-unit');
            $inSelect->add(new HtmlSelectOption('', ''));

            foreach (Elca::$units as $unit => $prettyUnit) {
                $inSelect->add(new HtmlSelectOption('1 ' . $prettyUnit, $unit));
            }
        }

        $this->appendElement($row, new HtmlEntityReference('rArr'))->addClass('elt-arrow');

        /**
         * Factor
         */
        if (!$conversion instanceof RecommendedConversion && ($isImported || $isGrossDensity)) {
            $row->add(new HtmlHiddenField('factor_' . $conversionId, $conversion->factor()));
            $this->appendElement(
                $row,
                new HtmlStaticText(ElcaNumberFormat::toString($conversion->factor(), 3))
            )->addClass('elt-factor');
        } else {
            $this->appendElement(
                $row,
                new ElcaHtmlNumericInput(
                    'factor_' . $conversionId,
                    $conversion instanceof LinearConversion ? $conversion->factor() : null
                )
            )->addClass('elt-factor');
        }

        /**
         * OutUnit
         */
        if ($isRequired || $isImported || $isGrossDensity) {
            $row->add(new HtmlHiddenField('outUnit_' . $conversionId, $outUnit));
            $this->appendElement($row, new HtmlStaticText(ElcaNumberFormat::formatUnit($outUnit)))->addClass(
                'elt-out-unit'
            );
        } else {
            $this->appendElement(
                $row,
                $outSelect = new HtmlSelectbox('outUnit_' . $conversionId, $conversion ? $outUnit : null)
            )->addClass('elt-out-unit');

            if (\count($requiredUnits) !== 1) {
                $outSelect->add(new HtmlSelectOption('', ''));
            }

            $units = $requiredUnits;
            $units += [$outUnit => Elca::$units[$outUnit] ?? $outUnit];

            foreach ($units as $unit => $foo) {
                $outSelect->add(new HtmlSelectOption(ElcaNumberFormat::formatUnit($unit), $unit));
            }
        }

        $info = $this->prepareConversionInfoText($conversion, $isRequired, $isInUse);

        $this->appendElement($row, new HtmlStaticText($info))->addClass('elt-ident');

        if (!($isRequired || $isImported || $isInUse || $isGrossDensity || $conversion instanceof RecommendedConversion)) {
            if (!$this->readOnly && !$isRequired) {
                $row->add(new HtmlLink(t('Löschen'), '/processes/deleteConversion/?id=' . $conversionId))->addClass(
                    'delete-link no-history'
                );
            }
        }

        if (!$this->readOnly && $isImported) {
            $row->add(new HtmlLink(t('Bearbeiten'), '/processes/editImportedConversion/?id=' . $conversionId))->addClass(
                'edit-link no-history'
            );
        }
    }

    private function appendNewConversionRow(HtmlElement $container, array $requiredUnits, bool $isRequired)
    {
        $row = $container->add(new HtmlTag('div'));
        $row->addClass('elt-row');

        $this->appendElement($row, $inSelect = new HtmlSelectbox('inUnit_new'))->addClass('elt-in-unit');
        $inSelect->add(new HtmlSelectOption('', ''));

        foreach (Elca::$units as $unit => $prettyUnit) {
            $inSelect->add(new HtmlSelectOption('1 ' . $prettyUnit, $unit));
        }

        $this->appendElement($row, new HtmlEntityReference('rArr'))->addClass('elt-arrow');
        $this->appendElement($row, new ElcaHtmlNumericInput('factor_new'))->addClass('elt-factor');
        $this->appendElement($row, $outSelect = new HtmlSelectbox('outUnit_new'))->addClass('elt-out-unit');

        if (\count($requiredUnits) !== 1) {
            $outSelect->add(new HtmlSelectOption('', ''));
        }

        foreach ($requiredUnits as $unit => $foo) {
            $outSelect->add(new HtmlSelectOption(ElcaNumberFormat::formatUnit($unit), $unit));
        }

        $this->appendElement($row, new HtmlStaticText(t(self::NO_IDENT)))->addClass('elt-ident');

        if (!$this->readOnly && !$isRequired) {
            $row->add(new HtmlLink(t('Abbrechen'), '/processes/' . $this->processConfig->getId() . '/'))->addClass(
                'cancel-link no-history'
            );
        }
    }

    /**
     * Adds an element to form
     *
     * @param HtmlElement $container
     * @param HtmlElement $element
     * @param array       $changedElements
     * @param string      $caption
     *
     * @return HtmlElement -
     */
    private function appendElement(
        HtmlElement $container,
        HtmlElement $element,
        array $changedElements = [],
        $caption = ''
    ): HtmlElement {
        $elt = $container->add(new ElcaHtmlFormElementLabel($caption, $element));
        if ($element instanceof HtmlFormElement && isset($changedElements[$element->getName()])) {
            $elt->addClass('changed');
        }

        return $elt;
    }

    /**
     * Appends processConfig variants to the given container
     *
     * @param DOMElement $Context
     *
     * @return void -
     */
    private function appendVariants(DOMElement $Context)
    {
        if (!$processConfigId = $this->processConfig->getId()) {
            return;
        }

        $ProcessConfigVariants = ElcaProcessConfigVariantSet::find(
            ['process_config_id' => $processConfigId],
            ['name' => 'ASC']
        );

        if (!$ProcessConfigVariants->count()) {
            return;
        }

        $Container = $Context->appendChild($this->getDiv(['id' => 'processConfigVariants']));

        $Container->appendChild($this->getH3(t('Verfügbare Varianten')));

        $Table = new HtmlTable('elca-process-config-variants');
        $Table->addColumn('name', t('Name'))->addClass('name');
        $Table->addColumn('refValue', t('Bezugsgröße'))->addClass('refValue');
        $Head    = $Table->createTableHead();
        $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
        $HeadRow->addClass('table-headlines');

        $Body = $Table->createTableBody();
        $Row  = $Body->addTableRow();
        $Row->getColumn('refValue')->setOutputElement(new HtmlText('refValue', new ElcaProcessesConverter()));

        $Body->setDataSet($ProcessConfigVariants);
        $Table->appendTo($Container);
    }

    private function prepareConversionInfoText(Conversion $conversion, bool $isRequired, bool $inUse)
    {
        $conversionType = $conversion->type();

        $parts = [];

        if ($conversionType->isKnown()) {
            $parts[] = t(self::$conversionIdents[$conversionType->value()]);
        }

        if ($isRequired) {
            $parts[] = t('Umrechnung im Lebenszyklus');
        }

        if ($conversion instanceof ImportedLinearConversion) {
            $parts[] = t('Umrechnung nach Baustoffdatenbank');
        } elseif ($conversion instanceof RecommendedConversion) {
            $parts[] = t('Empfohlene Umrechnung');
        } else {
            $parts[] = self::NO_IDENT;
        }

        if (!$isRequired && $inUse) {
            $parts[] = t('wird verwendet');
        }

        return implode('; ', $parts);
    }

    private function appendIdInfo(DOMNode $content)
    {
        if (!$this->processConfig->getId()) {
            return;
        }

        $container = $content->appendChild($this->getUl(['class' => 'id-info']));
        $container->appendChild($this->getLi([], 'ID=' . $this->processConfig->getId()));
        $container->appendChild($this->getLi([], 'UUID=' . $this->processConfig->getUuid()));
    }
}

