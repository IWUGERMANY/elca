<?php
namespace Lcc\View;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\Url;
use Beibob\HtmlTools\HtmlElement;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlLink;
use Beibob\HtmlTools\HtmlStaticText;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableColumn;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlTag;
use Beibob\HtmlTools\HtmlText;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlCurrencyText;
use Elca\View\helpers\ElcaHtmlElementSelectorLink;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlNumericInput;
use Elca\View\helpers\ElcaHtmlNumericText;
use Elca\View\helpers\ElcaHtmlSubmitButton;
use Lcc\Db\LccElementCostProgressionsSet;

/**
 * ElementCompositeCostsView
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElementCompositeCostsView extends HtmlView
{
    /**
     * Read only
     */
    private $readOnly;

    /**
     * @var ElcaElement
     */
    private $compositeElement;

    /**
     * Data
     */
    private $data;

    /**
     * @var boolean $isExtantBuilding
     */
    private $isExtantBuilding = false;

    private $activeTabIdent;
    private $context;

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
        $this->data = $this->get('data');
        $this->compositeElement = ElcaElement::findById($this->get('compositeElementId'));
        $this->context = $this->get('context', ElementsCtrl::CONTEXT);
        $this->activeTabIdent = $this->get('activeTabIdent', null);

        /**
         * Readonly
         */
        if($this->get('readOnly', false))
            $this->readOnly = true;

        /**
         * extant building
         */
        if ($this->context == ProjectElementsCtrl::CONTEXT) {
            $ProjectConstruction = $this->compositeElement->getProjectVariant()->getProjectConstruction();
            $this->isExtantBuilding = $ProjectConstruction->isExtantBuilding();
        }
    }
    // End init



    /**
     * Callback triggered after rendering the template
     */
    protected function afterRender()
    {
        $formId = 'compositeElementForm';
        $form = new HtmlForm($formId, '/lcc/elements/saveElements/');
        $form->setAttribute('id', $formId);
        $form->addClass('costs clearfix highlight-changes');

        $form->setDataObject($this->data);

        $form->add(new HtmlHiddenField('relId', $this->compositeElement->getId()));
        $form->add(new HtmlHiddenField('context', $this->context));
        $form->add(new HtmlHiddenField('tab', $this->activeTabIdent));

        if($this->readOnly)
            $form->setReadonly();

        if($this->has('Validator'))
            $form->setValidator($this->get('Validator'));

        $container = $this->appendChild($this->getDiv(['id' => 'section-composite',
                                                       'class' => 'composite-elements element-section element-costs']));

        if ($this->isExtantBuilding) {
            $this->addClass($container, 'is-extant-building');
        }

        $this->appendCompositeSection($form);
        $form->appendTo($container);
    }
    // End afterRender



    /**
     * Appends the composite section
     *
     * @param  HtmlForm $form
     */
    protected function appendCompositeSection(HtmlForm $form)
    {
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('clear');

        /**
         * Headline
         */
        $row = $group->add(new HtmlTag('div'));
        $row->addClass('hl-row clearfix');

        $row->add(new HtmlTag('h5', t('Bauteilkomponente').' ('. t('opak'). ')', ['class' => 'hl-element']));
        $row->add(new HtmlTag('h5', $this->compositeElement->isTemplate()? t('Bezugsgröße') : t('Menge'), ['class' => 'hl-quantity']));
        $row->add(new HtmlTag('h5', 'DIN 276', ['class' => 'hl-dinCode']));
        $row->add(new HtmlTag('h5', t('Berechnet'), ['class' => 'hl-costs-calculated']));
        $row->add(new HtmlTag('h5', t('Herstellung / ME'), ['class' => 'hl-costs']));
        $row->add(new HtmlTag('h5', t('Austausch in Jahren'), ['class' => 'hl-replacements']));

        $container = $group->add(new HtmlTag('div', null, ['id' => 'element-composite']));

        $ol = $container->add(new HtmlTag('ol'));
        $counter = 0;
        $nonOpaqueStartIndex = null;
        $nonOpaqueElements = [];
        if(isset($this->data->elementId) &&
           is_array($this->data->elementId) &&
           count($this->data->elementId))
        {
            foreach($this->data->elementId as $key => $elementId)
            {
                if($this->data->isOpaque[$key] === false)
                {
                    if(is_null($nonOpaqueStartIndex))
                        $nonOpaqueStartIndex = (int)($this->data->position[$key]) - 1;

                    $nonOpaqueElements[$key] = $key;
                }
                else
                {
                    $li = $ol->add(new HtmlTag('li', null, ['id' => 'composite-group-'.$elementId]));
                    $li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                    $this->appendElement($li, $key);
                    $counter++;
                }
            }
        }

        /**
         * Add non opaque elements
         */
        if(count($nonOpaqueElements))
        {
            $row = $group->add(new HtmlTag('div'));
            $row->addClass('hl-row clearfix');

            $row->add(new HtmlTag('h5', t('Bauteilkomponente').' ('. t('nicht-opak'). ')', ['class' => 'hl-element']));

            $container = $group->add(new HtmlTag('div', null, ['id' => 'element-composite-non-opaque']));

            $ol = $container->add(new HtmlTag('ol'));
            foreach($nonOpaqueElements as $key)
            {
                $li = $ol->add(new HtmlTag('li', null, ['id' => 'composite-group-'.$key]));
                $li->add(new HtmlTag('span', null, ['class' => 'clearfix']));

                $this->appendElement($li, $key, true);
            }
        }

        if ($counter > 0)
            $this->appendButtons($container);
    }
    // End appendComponentsSection


    /**
     * Appends a single element
     *
     * @param HtmlElement $li
     * @param             $key
     * @param bool        $isNonOpaque
     */
    protected function appendElement(HtmlElement $li, $key, $isNonOpaque = false)
    {
        $element = ElcaElement::findById($key);
        $canEditElement = ElcaAccess::getInstance()->canEditElement($element);

        $container = $li->add(new HtmlTag('div', null, ['class' => 'element costs-table-width clearfix']));
        $container->setAttribute('id', 'element-'.$key);



        /**
         * Toggle link
         */
        if (!$this->compositeElement->isTemplate()) {
            $container->add(
                new HtmlTag(
                    'a',
                    t('schließen'),
                    ['class' => 'toggle-link no-xhr', 'title' => t('Barwertentwicklung anzeigen'), 'href' => '#']
                )
            );
        }

        /**
         * Element select
         */
        $Selector = $container->add(new ElcaHtmlElementSelectorLink('elementId['.$key.']', null, true));
        $Selector->addClass('element-selector');

        if(isset($this->data->elementId[$key]) && $this->data->elementId[$key]) {

            $refUnitStr = ElcaNumberFormat::formatUnit($this->data->refUnit[$key]);
            $container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('quantity['.$key.']')))->addClass('quantity');

            if($isNonOpaque && $this->context == ProjectElementsCtrl::CONTEXT && $element->getRefUnit() != Elca::UNIT_M2)
            {
                $txt = sprintf("%s m² / %s m²",
                    ElcaNumberFormat::toString($element->getMaxSurface(), 3),
                    ElcaNumberFormat::toString($element->getMaxSurface(true), 3));
                $container->add(new ElcaHtmlFormElementLabel('', new HtmlStaticText($txt)))->addClass('surface');
            }

            $container->add(new ElcaHtmlFormElementLabel('', new HtmlTag('span', $refUnitStr, ['class' => 'refUnit'])));
            $container->add(new ElcaHtmlFormElementLabel('', new HtmlText('dinCode['.$key.']')))->addClass('dinCode');
            $container->add(new ElcaHtmlFormElementLabel('', new HtmlText('elementType['.$key.']')))->addClass('elementType');

            $calculatedCosts = $container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericText('prodCosts['.$key.']', 2, false, '?')));
            if ($this->data->prodCosts[$key]) {
                $container->add($refUnit = new HtmlTag('span', '€ / ' . $refUnitStr, ['class' => 'prodCosts-refUnit']));

                if (null !== $this->data->costs[$key]) {
                    $calculatedCosts->addClass('shade');
                    $refUnit->addClass('shade');
                }
            }

            if(!$this->readOnly && $canEditElement) {
                $container->add(new ElcaHtmlFormElementLabel('', $numInput = new ElcaHtmlNumericInput('costs['.$key.']')));
                $numInput->setPrecision(2);

                $container->add(new ElcaHtmlFormElementLabel('', new ElcaHtmlNumericInput('replacements['.$key.']')));
            }
            else
            {
                if($isNonOpaque)
                    $this->data->quantity[$key] = 0;

                $container->add(new HtmlTag('span', ElcaNumberFormat::toString($this->data->replacements[$key]), ['class' => 'replacements']));
                $container->add(new HtmlTag('span', ElcaNumberFormat::toString($this->data->costs[$key], 2), ['class' => 'costs']));
            }
            $container->add(new HtmlTag('span', '€ / '. $refUnitStr, ['class' => 'costs-refUnit']));

            $urlParams = [
                'rel' => $this->compositeElement->getId()
            ];

            if ($this->activeTabIdent)
                $urlParams['tab'] = $this->activeTabIdent;

            $container->add(new HtmlLink($this->readOnly || !$canEditElement? t('Ansehen') : t('Bearbeiten'),
                Url::factory('/'.$this->context.'/'.$this->data->elementId[$key].'/',
                    $urlParams
                )))
                      ->addClass('page function-link edit-link');

            /**
             * Costs table
             */
            $this->appendCostsTable($key, $container, is_numeric($this->data->costs[$key]));
        }

    }
    // End appendElement


    /**
     *
     */
    protected function appendCostsTable($elementId, $appendTo, $hasOwnCosts = false)
    {
        $DataTableOuterContainer = $appendTo->add(new HtmlTag('div', null, ['class' => 'costs-table composite hidden', 'id' => 'costs-table-' . $elementId]));
        $DataSet = LccElementCostProgressionsSet::findByElementId($elementId);

        /**
         * Reorder dataset
         */
        $collector = [];
        $hasData = false;

        foreach ($DataSet as $data) {
            $obj = isset($collector[$data->key]) ? $collector[$data->key] : (object)null;

            if (!is_null($data->life_time))
                $hasData = true;

            $col = 'c' . $data->life_time;
            $obj->$col = $data->quantity * $this->data->quantity[$elementId];
            if (!isset($obj->total))
                $obj->total = 0;

            if ('c0' !== $col) {
                $obj->total += $data->quantity * $this->data->quantity[$elementId];
            }
            $obj->key = $data->key;
            if ($hasOwnCosts)
                $obj->process_config_name = ElcaElement::findById($elementId)->getName();
            else
                $obj->process_config_name = !$data->process_config_name ? t('Gesamt') : $data->process_config_name;

            $collector[$data->key] = $obj;
        }

        if ($hasData) {

            if ($hasOwnCosts)
            {
                $DataObjectSet = new DataObjectSet();
                $DataObjectSet->add(array_pop($collector));
            }
            else
                $DataObjectSet = new DataObjectSet($collector);


            /**
             * Define table if data is available
             */
            $DataTableContainer = $DataTableOuterContainer->add(new HtmlTag('div', null, ['class' => 'scroll-wrapper']));
            $Table = new HtmlTable('');
            $Table->addColumn('process_config_name', t('Komponente'));
            $Table->addColumn('total', t('Barwert'));

            $lifeTimeColumns = [];
            foreach ($DataObjectSet as $obj)
            {
                foreach ($obj as $prop => $value)
                {
                    switch ($prop) {
                        case 'process_config_name':
                        case 'key':
                        case 'total':
                        case 'c':
                            continue;
                            break;
                        default:
                            if ('c0' === $prop) {
                                break;
                            }
                            $Table->addColumn($prop, str_replace('c', '', $prop));
                            $lifeTimeColumns[$prop] = $prop;
                            break;
                    }
                }
            }
            reset($lifeTimeColumns);
            $firstLifeTimeColumn = key($lifeTimeColumns);

            /**
             * colspaning table head (wording 'Jahre')
             */
            $Head = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->setAttribute('class', 'legendRow');
            $columns = [
                'process_config_name' => new HtmlTableColumn('process_config_name', ''),
                'total' => new HtmlTableColumn('total', ''),
            ];

            if (\count($lifeTimeColumns)) {
                $columns[$firstLifeTimeColumn] = new HtmlTableColumn($firstLifeTimeColumn, t('Jahre'));
            }

            $HeadRow->setColumns($columns);

            $HeadRow->getColumn('process_config_name')->addClass('fixed');
            $HeadRow->getColumn('total')->addClass('fixed');
            if (\count($lifeTimeColumns) > 1)
                $HeadRow->getColumn($firstLifeTimeColumn)->setColSpan(count($lifeTimeColumns));

            /**
             * default table head (one column per year)
             */
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->getColumn('process_config_name')->addClass('fixed');
            $HeadRow->getColumn('total')->addClass('fixed');


            /**
             * Table body, with currency formater
             */
            $Body = $Table->createTableBody();
            $Row = $Body->addTableRow();
            foreach ($lifeTimeColumns as $prop)
                $Row->getColumn($prop)->setOutputElement(new ElcaHtmlCurrencyText($prop));

            $Row->getColumn('total')->setOutputElement(new ElcaHtmlCurrencyText('total'));
            $Row->getColumn('total')->setTableCellType('th');
            $Row->getColumn('total')->setAttribute('class', 'fixed total');
            $Row->getColumn('process_config_name')->setTableCellType('th');
            $Row->getColumn('process_config_name')->setAttribute('class', 'fixed process_config_name');


            $Body->setDataSet($DataObjectSet);

            /**
             * append table
             */
            $DataTableContainer->add($Table);
        }
        else
            $DataTableOuterContainer->add(new HtmlTag('div', t('Für die Berechnung der Barwertentwicklung müssen Herstellungskosten angegeben werden.'), ['class' => 'no-data-available']));

    }
    // End appendCostsTable



    /**
     * Appends submit button
     *
     * @param  HtmlElement $Container
     * @return HtmlElement
     */
    private function appendButtons(HtmlElement $Container)
    {
        if($this->readOnly)
            return;

        $ButtonGroup = $Container->add(new HtmlFormGroup(''));
        $ButtonGroup->addClass('buttons');

        /**
         * Add save button
         */
        $Button = $ButtonGroup->add(new ElcaHtmlSubmitButton('saveElements', t('Speichern'), true));
        $Button->addClass('save-elements');
    }
}
// End ElementCompositeCostsView
