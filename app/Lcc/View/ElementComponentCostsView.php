<?php
namespace Lcc\View;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableColumn;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaProcessConversion;
use Elca\ElcaNumberFormat;
use Elca\View\helpers\ElcaHtmlCurrencyText;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlProcessConfigSelectorLink;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Lcc\Db\LccElementCost;
use Lcc\Db\LccElementCostProgressionsSet;

/**
 * ElementComponentCostsView
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElementComponentCostsView extends HtmlView
{
    const BUILDMODE_COMPONENTS= 'components';
    const BUILDMODE_LAYERS = 'layers';

    /**
     * Read only
     */
    private $readOnly;

    /**
     * Current buildmode
     */
    private $buildMode;

    /**
     * Current context
     */
    private $context;

    /**
     * @var bool
     */
    private $isExtantBuilding;

    /**
     * @var ElcaElement
     */
    private $element;

    /**
     * @var \stdClass
     */
    private $data;
    private $activeTabIdent;

    /**
     * @var LccElementCost
     */
    private $elementCost;

    /**
     * Init
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Init arguments and options
         */
        $this->data      = $this->get('data');
        $this->element   = ElcaElement::findById($this->get('elementId'));
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_COMPONENTS);
        $this->context   = $this->get('context', ElementsCtrl::CONTEXT);
        $this->activeTabIdent = $this->get('activeTabIdent', null);

        $this->elementCost = LccElementCost::findByElementId($this->element->getId());

        /**
         * Readonly
         */
        $this->readOnly = $this->get('readOnly', false);

        /**
         * extant building
         */
        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            $ProjectConstruction    = $this->element->getProjectVariant()->getProjectConstruction();
            $this->isExtantBuilding = $ProjectConstruction->isExtantBuilding();
        }
    }

    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'elementComponentForm_' . $this->buildMode;
        $form   = new HtmlForm($formId, '/lcc/elements/saveComponents/');
        $form->setAttribute('id', $formId);
        $form->addClass('clearfix highlight-changes');
        $form->setRequest(FrontController::getInstance()->getRequest());

        $form->setDataObject($this->data);

        $form->add(new HtmlHiddenField('context', $this->context));
        $form->add(new HtmlHiddenField('elementId', $this->element->getId()));
        $form->add(new HtmlHiddenField('b', $this->buildMode));
        $form->add(new HtmlHiddenField('tab', $this->activeTabIdent));

        if ($this->readOnly) {
            $form->setReadonly();
        }

        if ($this->has('Validator')) {
            $form->setValidator($this->get('Validator'));
        }

        $container = $this->appendChild($this->getDiv(['id' => 'section-'. $this->buildMode,
                                                       'class' => 'element-components element-section element-costs']));

        if ($this->isExtantBuilding) {
            $this->addClass($container, 'is-extant-building');
        }
        if($this->buildMode == self::BUILDMODE_COMPONENTS)
            $this->appendComponentsSection($form);
        else
            $this->appendGeometrySection($form);

        $form->appendTo($container);
    }

    /**
     * Appends the geometry section
     *
     * @param  HtmlForm $Form
     */
    protected function appendGeometrySection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(t('Bauteilgeometrie (von innen nach außen)')));
        $Group->addClass('clear');

        if($this->element->getElementTypeNode()->isOpaque() === false && ($maxSurface = $this->element->getMaxSurface()))
            $Group->add(new HtmlTag('span', t('Abzugsfläche %area% m²', null, ['%area%' => ElcaNumberFormat::toString($maxSurface, 3)]), ['class' => 'area']));

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Schicht'), ['class' => 'hl-layer']));

        //$Row->add(new HtmlTag('h5', t('Fläche'), ['class' => 'hl-layer-area']));

        $Row->add(new HtmlTag('h5', t('Bilanzieren'), ['class' => 'hl-is-active']));
        $Row->add(new HtmlTag('h5', t('Ersatz'), ['class' => 'hl-life-time']));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT)
            $Row->add(new HtmlTag('h5', t('Bestand'), ['class' => 'hl-is-extant']));

        $Row->add(new HtmlTag('h5', t('Herstellung € / ') . t(ElcaNumberFormat::formatUnit($this->element->getRefUnit())), ['class' => 'hl-costs']));
        //$Row->add(new HtmlTag('h5', t('Gesamt € / ') . t(ElcaNumberFormat::formatUnit($this->element->getRefUnit())), ['class' => 'hl-costs-total']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-'.$this->buildMode]));

        // sortable container
        $ol = $Container->add(new HtmlTag('ol'));

        $counter = 0;
        if(isset($this->data->processConfigId) &&
           is_array($this->data->processConfigId) &&
           count($this->data->processConfigId))
        {
            $siblings = [];
            foreach($this->data->processConfigId as $key => $foo)
            {
                $isSibling = false;
                if($siblingId = $this->data->layerSiblingId[$key])
                {
                    if(isset($siblings[$siblingId]) && $siblings[$siblingId] instanceof HtmlElement)
                    {
                        $li = $siblings[$siblingId];
                        $isSibling = true;
                    }
                    else
                        $li = $siblings[$key] = $ol->add(new HtmlTag('li', null, ['id' => 'component-group-'.$key]));

                    $li->addClass('siblings');
                }
                else
                    $li = $ol->add(new HtmlTag('li', null, ['id' => 'component-group-'.$key]));

                $li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                $this->appendLayer($li, $key, $isSibling);
                $counter++;
            }
        }

        if ($counter > 0 && !$this->elementCost->getQuantity())
            $this->appendButtons($Container);
    }
    // End appendGeometrySection


    /**
     * Appends a layer component
     *
     * @param HtmlElement          $li
     * @param                      $key
     * @param bool                 $isSibling
     */
    protected function appendLayer(HtmlElement $li, $key, $isSibling = false)
    {
        $container = $li->add(new HtmlTag('div', null, ['class' => 'element-component']));
        $container->setAttribute('id', 'component-'.$key);
        $container->addClass('clearfix costs-table-width '.$this->buildMode);

        if (isset($this->data->isExtant[$key]) && $this->data->isExtant[$key]) {
            $container->addClass('is-extant');
        }

        if($isSibling) {
            $container->addClass('sibling');
        }

        /**
         * Position
         */
        $container->add(new HtmlHiddenField('position['.$key.']', $this->data->layerPosition[$key]));


        /**
         * Toggle link
         */
        if (!$this->element->isTemplate()) {
            $container->add(new HtmlTag('a', t('schließen'), ['class' => 'toggle-link no-xhr', 'title' => t('Barwertentwicklung anzeigen'), 'href' => '#']));
        }

        /**
         * ProcessConfig selector
         */
        $container->add($Selector = new ElcaHtmlProcessConfigSelectorLink('processConfigId['.$key.']', null, true));
        $Selector->addClass('process-config-selector');

        $request = FrontController::getInstance()->getRequest();
        if((isset($this->data->processConfigId[$key]) && $this->data->processConfigId[$key]) || (isset($request->processConfigId[$key]) && $request->processConfigId[$key]))
        {
            /**
             * Component properties
             */
            $container->add(new HtmlTag('span', ElcaNumberFormat::toString($this->data->numReplacements[$key]), ['class' => 'lifeTime']));

            if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
                $container->add(new HtmlTag('span', $this->data->isExtant[$key]? t('ja') : t('nein'), ['class' => 'isExtant']));
            }

            $container->add(new HtmlTag('span', $this->data->calcLca[$key]? t('ja') : t('nein'), ['class' => 'calcLca']));

            $container->add(new ElcaHtmlFormElementLabel('', $numInput = new ElcaHtmlNumericInput('costs['.$key.']', null, (bool)$this->elementCost->getQuantity())));
            $numInput->setPrecision(2);

            /**
             * Costs table
             */
            $this->appendCostsTable($key, $container);
        }
    }
    // End appendLayer



    /**
     * Appends the geometry section
     *
     * @param  HtmlForm $Form
     */
    protected function appendComponentsSection(HtmlForm $Form)
    {
        $Group = $Form->add(new HtmlFormGroup(t('Sonstige Baustoffe')));
        $Group->addClass('clear');

        /**
         * Headline
         */
        $Row = $Group->add(new HtmlTag('div'));
        $Row->addClass('hl-row clearfix');

        $Row->add(new HtmlTag('h5', t('Baustoff'), ['class' => 'hl-processConfig']));
        $Row->add(new HtmlTag('h5', t('Menge'), ['class' => 'hl-quantity']));

        $Row->add(new HtmlTag('h5', t('Ersatz'), ['class' => 'hl-life-time']));
        $Row->add(new HtmlTag('h5', t('Bilanz.'), ['class' => 'hl-is-active', 'title' => t('In die Bilanz einbeziehen')]));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $Row->add(new HtmlTag('h5', t('Bestand'), ['class' => 'hl-is-extant']));
        }

        $Row->add(new HtmlTag('h5', t('Herstellung') .  ' € / ' . t(ElcaNumberFormat::formatUnit($this->element->getRefUnit())), ['class' => 'hl-costs']));

        $Container = $Group->add(new HtmlTag('div', null, ['id' => 'element-'.$this->buildMode]));
        $Ul = $Container->add(new HtmlTag('ul'));

        $counter = 0;
        if(isset($this->data->processConfigId) &&
           is_array($this->data->processConfigId) &&
           count($this->data->processConfigId))
        {
            foreach($this->data->processConfigId as $key => $processConfigId)
            {
                $Li = $Ul->add(new HtmlTag('li', null, ['id' => 'component-group-'.$key]));
                $this->appendSingleComponent($Li, $key);
                $counter++;
            }
        }

        if ($counter > 0 && !$this->elementCost->getQuantity())
            $this->appendButtons($Container);
    }
    // End appendComponentsSection


    /**
     * Appends a single component
     *
     * @param  HtmlForm $Form
     */
    protected function appendSingleComponent(HtmlElement $li, $key)
    {
        $li->add(new HtmlTag('span'));
        $container = $li->add(new HtmlTag('div', null, ['class' => 'element-component']));
        $container->setAttribute('id', 'component-'.$key);
        $container->addClass('clearfix costs-table-width element-component '.$this->buildMode);


        /**
         * Toggle link
         */
        $container->add(new HtmlTag('a', t('schließen'), ['class' => 'toggle-link no-xhr', 'title' => t('Barwertentwicklung anzeigen'), 'href' => '#']));


        /**
         * ProcessConfig selector
         * @var ElcaHtmlProcessConfigSelectorLink $Selector
         */
        $Selector = $container->add(
            new ElcaHtmlProcessConfigSelectorLink(
                'processConfigId['.$key.']',
                null,
                true
            )
        );

        $Selector->addClass('process-config-selector');

        /**
         * Component properties
         */
        $quantityStr = ElcaNumberFormat::toString($this->data->quantity[$key]).' ';
        $quantityStr .= t(ElcaNumberFormat::formatUnit(ElcaProcessConversion::findById($this->data->conversionId[$key])->getInUnit()));
        $container->add(new HtmlTag('span', $quantityStr, ['class' => 'quantity']));

        $container->add(new HtmlTag('span', $this->data->numReplacements[$key], ['class' => 'lifeTime']));

        if ($this->isExtantBuilding && $this->context == ProjectElementsCtrl::CONTEXT) {
            $container->add(new HtmlTag('span', $this->data->isExtant[$key]? t('ja') : t('nein'), ['class' => 'isExtant']));
        }

        $container->add(new HtmlTag('span', $this->data->calcLca[$key]? t('ja') : t('nein'), ['class' => 'calcLca']));

        $container->add(new ElcaHtmlFormElementLabel('', $numInput = new ElcaHtmlNumericInput('costs['.$key.']', null, (bool)$this->elementCost->getQuantity())));
        $numInput->setPrecision(2);

//        $container->add(new ElcaHtmlFormElementLabel('', $numInput = new ElcaHtmlNumericInput('costs['.$key.']')));
//        $numInput->setPrecision(2);

        /**
         * Costs table
         */
        $this->appendCostsTable($key, $container);
    }
    // End appendSingleComponent

    /**
     *
     */
    protected function appendCostsTable($elementComponentId, $appendTo)
    {
        $DataTableOuterContainer = $appendTo->add(new HtmlTag('div', null, ['class' => 'costs-table hidden', 'id' => 'costs-table-' . $elementComponentId]));
        $DataSet = LccElementCostProgressionsSet::findByElementComponentId($elementComponentId);


        if ($this->elementCost->getQuantity() && $this->elementCost->getLifeTime())
        {
            $noDataMessage = t('Es werden eigene Angaben für die Berechnung der Kosten verwendet.');
            $DataTableOuterContainer->add(new HtmlTag('div', $noDataMessage, ['class' => 'no-data-available']));
        }
        else
        {
            if (!$DataSet->count())
            {
                $noDataMessage = t('Für die Berechnung der Barwertentwicklung müssen Herstellungskosten angegeben werden.');
                $DataTableOuterContainer->add(new HtmlTag('div', $noDataMessage, ['class' => 'no-data-available']));
            }
            else
            {
                /**
                 * Define table if data is available
                 */
                $DataTableContainer = $DataTableOuterContainer->add(new HtmlTag('div', null, ['class' => 'scroll-wrapper']));
                $Table = new HtmlTable('');
                $Table->addColumn('years');
                $Table->addColumn('total', t('Barwert'));

                $firstLifeTimeCol = null;
                $lifeTimeColumns = [];
                foreach ($DataSet as $data) {
                    if (0 === $data->life_time) {
                        continue;
                    }

                    $colName = 'c'.$data->life_time;
                    $lifeTimeColumns[] = $colName;

                    if (null === $firstLifeTimeCol) {
                        $firstLifeTimeCol = $colName;
                    }

                    $Table->addColumn($colName, $data->life_time);
                }

                /**
                 * colspaning table head (wording 'Jahre')
                 */
                $Head = $Table->createTableHead();
                $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
                $HeadRow->setAttribute('class', 'legendRow');

                $columns = [
                    'years' => new HtmlTableColumn('years', ''),
                    'total' => new HtmlTableColumn('total', ''),
                ];

                if (\count($lifeTimeColumns)) {
                    $columns[$firstLifeTimeCol] = new HtmlTableColumn($firstLifeTimeCol, t('Jahre'));
                }

                $HeadRow->setColumns($columns);

                $HeadRow->getColumn('years')->addClass('fixed');
                $HeadRow->getColumn('total')->addClass('fixed');

                if (\count($lifeTimeColumns)) {
                    $HeadRow->getColumn($firstLifeTimeCol)->setColSpan($DataSet->count() - 1);
                }

                /**
                 * default table head (one column per year)
                 */
                $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
                $HeadRow->getColumn('years')->addClass('fixed');
                $HeadRow->getColumn('total')->addClass('fixed');


                /**
                 * Table body, with currency formater
                 */
                $Body = $Table->createTableBody();
                $Row = $Body->addTableRow();
                foreach ($DataSet as $data) {
                    if (0 === $data->life_time) {
                        continue;
                    }
                    $Row->getColumn('c'.$data->life_time)->setOutputElement(
                        new ElcaHtmlCurrencyText('c'.$data->life_time)
                    );
                }


                $Row->getColumn('total')->setOutputElement(new ElcaHtmlCurrencyText('total'));
                $Row->getColumn('total')->setTableCellType('th');
                $Row->getColumn('total')->setAttribute('class', 'fixed total');
                $Row->getColumn('years')->setTableCellType('th');
                $Row->getColumn('years')->setAttribute('class', 'fixed years');

                /**
                 * Reorder dataset and set in table body
                 */
                $flippedSet = new DataObjectSet();
                $obj = (object)null;
                $obj->years = t('Gesamt');
                $obj->total = 0;
                foreach ($DataSet as $data) {
                    if (0 === $data->life_time) {
                        continue;
                    }

                    $col = 'c' . $data->life_time;
                    $obj->$col = $data->quantity;
                    $obj->total += $data->quantity;
                }
                $flippedSet->add($obj);
                $Body->setDataSet($flippedSet);

                /**
                 * append table
                 */
                $DataTableContainer->add($Table);
            }
        }
    }
    // End appendCostsTable


    /**
     * Appends submit button
     *
     * @param  HtmlElement $container
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $container)
    {
        if($this->readOnly) {
            return;
        }

        $buttonGroup = $container->add(new HtmlFormGroup(''));
        $buttonGroup->addClass('buttons');

        /**
         * Submit button
         */
        $buttonGroup->add(new ElcaHtmlSubmitButton($this->buildMode == self::BUILDMODE_COMPONENTS? 'saveComponents' : 'saveLayers', t('Speichern'), true));
    }
    // End appendSubmitButton
}
// End ElementComponentCosts
