<?php
namespace Lcc\View;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\HtmlView;
use Beibob\Blibs\StringFactory;
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
use DOMElement;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\ElcaNumberFormat;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlCurrencyText;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\HtmlNumericTextWithUnit;
use Lcc\Db\LccElementCostProgressionsSet;

/**
 * ElementView
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElementCostsView extends HtmlView
{
    /**
     * Read only
     */
    private $readOnly;

    private $context;

    private $elementId;

    /**
     * @var object
     */
    private $data;

    private $activeTabIdent;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->context        = $this->get('context', ElementsCtrl::CONTEXT);
        $this->elementId      = $this->get('elementId');
        $this->activeTabIdent = $this->get('activeTabIdent', null);

        $this->data = $this->get('data');

        if (!$this->data) {
            $this->data = new \stdClass();
        }

        $this->readOnly = $this->get('readOnly', false);
    }
    // End init


    /**
     * Callback triggered before rendering the template
     *
     */
    protected function beforeRender()
    {
        $container = $this->appendChild($this->getDiv(['id' => 'tabContent', 'class' => 'tab-lcc '.$this->context]));

        $form = new HtmlForm('elementForm', '/lcc/elements/save/');
        $form->setAttribute('id', 'elementForm');
        $form->addClass('costs clearfix');
        $form->setRequest(FrontController::getInstance()->getRequest());

        if ($this->readOnly) {
            $form->setReadonly();
        }

        if ($this->has('validator')) {
            $form->setValidator($this->get('validator'));
        }

        if (is_object($this->data)) {
            $form->addClass('highlight-changes');
            $form->setDataObject($this->data);
            $form->add(new HtmlHiddenField('elementId', $this->elementId));
        }
        $form->add(new HtmlHiddenField('context', $this->context));
        $form->add(new HtmlHiddenField('tab', $this->activeTabIdent));

        $this->appendDefault($form);
        $form->appendTo($container);
        $this->appendSections($container);
        $this->appendSummary($container, $this->elementId);
    }
    // End beforeRender

    /**
     * Appends the sections to the content
     *
     * @param  DOMElement $content
     * @return -
     */
    protected function appendSections(DOMElement $content)
    {
        if ($this->data->isComposite) {
            $content->appendChild(
                $this->getH2(t('Verknüpfte Bauteilkomponenten (von innen nach außen)'), ['class' => 'clearfix'])
            );

            /**
             * Append composite elements
             */
            $this->appendCompositeSection($content);
        } else {
            $h2 = $content->appendChild($this->getH2(t('Baustoffkosten').' ', ['class' => 'clearfix']));
            $h2->appendChild(
                $this->getSpan(t('bezogen auf').' 1 '.ElcaNumberFormat::formatUnit($this->data->refUnit))
            );

            $elementType = ElcaElementType::findByNodeId($this->data->elementTypeNodeId);

            if ($elementType->getPrefHasElementImage()) {
                $this->appendGeometrySection($content);
            }

            $this->appendComponentsSection($content);
        }
    }
    // End appendDefault

    /**
     * @param HtmlForm $form
     */
    private function appendDefault(HtmlForm $form)
    {
        $lftGroup = $form->add(new HtmlFormGroup(''));
        $lftGroup->addClass('clearfix column clear');

        $label = $lftGroup->add(new ElcaHtmlFormElementLabel(t('Name')));
        $label->addClass('name');
        $label->add(new HtmlText('name'));

        if ($this->data->description) {
            $lftGroup->add(new ElcaHtmlFormElementLabel(t('Beschreibung'), new HtmlText('description')));
        }

        if ($this->data->isComposite) {
            $lftGroup->add(
                new ElcaHtmlFormElementLabel(
                    t('Verbaute Menge'),
                    new HtmlNumericTextWithUnit('elementQuantity', $this->data->refUnit)
                )
            );

            if ($this->context === ProjectElementsCtrl::CONTEXT) {

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Herstellung / ME'),
                        new ElcaHtmlCurrencyText(
                            'prodCostsPerUnit',
                            '€ / '.t(ElcaNumberFormat::formatUnit($this->data->refUnit))
                        )
                    )
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(t('Herstellung gesamt'), new ElcaHtmlCurrencyText('prodCostsTotal'))
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Barwert / ME'),
                        new ElcaHtmlCurrencyText(
                            'calculatedQuantity',
                            '€ / '.t(ElcaNumberFormat::formatUnit($this->data->refUnit))
                        )
                    )
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(t('Barwert gesamt'), new ElcaHtmlCurrencyText('totalQuantity'))
                );
            }
        } else {
            if ($this->context === ProjectElementsCtrl::CONTEXT) {

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Verbaute Menge'),
                        new HtmlNumericTextWithUnit('elementQuantity', $this->data->refUnit)
                    )
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Herstellung / ME'),
                        new ElcaHtmlCurrencyText(
                            'prodCostsPerUnit',
                            '€ / '.t(ElcaNumberFormat::formatUnit($this->data->refUnit))
                        )
                    )
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(t('Herstellung gesamt'), new ElcaHtmlCurrencyText('prodCostsTotal'))
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(
                        t('Barwert / ME'),
                        new ElcaHtmlCurrencyText(
                            'calculatedQuantity',
                            '€ / '.t(ElcaNumberFormat::formatUnit($this->data->refUnit))
                        )
                    )
                );

                $lftGroup->add(
                    new ElcaHtmlFormElementLabel(t('Barwert gesamt'), new ElcaHtmlCurrencyText('totalQuantity'))
                );

                if ($this->data->quantity) {
                    $lftGroup->add(
                        $label = new ElcaHtmlFormElementLabel(
                            '',
                            new HtmlStaticText(
                                t('Die Barwertberechnung erfolgt auf Grundlage eigener Angaben am Bauteil!')
                            )
                        )
                    );
                    $label->addClass('clear own-quantity-note');
                }
            }
        }

        /**
         * Element image
         */
        if ($eltImgContainer = $this->getElementImage()) {
            $form->add($eltImgContainer);
        }


        $element = ElcaElement::findById($this->elementId);

        /**
         * Composite element info
         */
        if ($element->hasCompositeElement()) {
            /**
             * Get distinct list of composite elements
             */
            $CompositeElementSet = $element->getCompositeElements();
            $compositeElements   = array_unique($CompositeElementSet->getArrayBy('compositeElementId'));

            /**
             * Check user access to composite elements
             */
            $Access = ElcaAccess::getInstance();
            if (!$Access->hasAdminPrivileges()) {
                foreach ($compositeElements as $index => $compositeElementId) {
                    if (!$Access->canAccessElement($CompositeElementSet[$index]->getCompositeElement())) {
                        unset($compositeElements[$index]);
                    }
                }
            }

            /**
             * Show all remaining
             */
            if (count($compositeElements)) {
                $Composite = $form->add(
                    new ElcaHtmlFormElementLabel(t('Verknüpft mit Bauteil').' ', new HtmlTag('div'))
                );
                $Composite->addClass('composite-element clear');

                foreach ($compositeElements as $index => $compositeElementId) {
                    $CompositeElement     = $CompositeElementSet[$index];
                    $compositeElementName = $CompositeElement->getCompositeElement()->getName();
                    $url                  = '/'.$this->context.'/'.$CompositeElement->getCompositeElementId().'/';

                    if ($this->activeTabIdent) {
                        $url .= '?tab='.$this->activeTabIdent;
                    }
                    $Link = $Composite->add(
                        new HtmlLink(StringFactory::stringMidCut($compositeElementName, 40).' ', $url)
                    );
                    $Link->setAttribute('title', $compositeElementName);
                    $Link->addClass('page');
                }
            }
        }
    }
    // End appendSections

    /**
     * @param $container
     */
    private function appendCompositeSection($container)
    {
        $View = new ElementCompositeCostsView();
        $View->assign('readOnly', $this->readOnly);
        $View->assign('context', $this->context);
        $View->assign('compositeElementId', $this->elementId);
        $View->assign('activeTabIdent', $this->activeTabIdent);
        $View->assign('data', $this->data->elements);
        $View->process();
        $View->appendTo($container);
    }
    // End appendCompositeSection


    /**
     * @param $container
     */
    private function appendGeometrySection($container)
    {
        if (!$this->data->layers) {
            return;
        }

        $View = new ElementComponentCostsView();
        $View->assign('buildMode', ElementComponentCostsView::BUILDMODE_LAYERS);
        $View->assign('readOnly', $this->readOnly);
        $View->assign('context', $this->context);
        $View->assign('elementId', $this->elementId);
        $View->assign('activeTabIdent', $this->activeTabIdent);
        $View->assign('data', $this->data->layers);
        $View->process();
        $View->appendTo($container);
    }
    // End appendGeometrySection


    /**
     * @param $container
     */
    private function appendComponentsSection($container)
    {
        if (!$this->data->components) {
            return;
        }

        $View = new ElementComponentCostsView();
        $View->assign('buildMode', ElementComponentCostsView::BUILDMODE_COMPONENTS);
        $View->assign('readOnly', $this->readOnly);
        $View->assign('context', $this->context);
        $View->assign('elementId', $this->elementId);
        $View->assign('activeTabIdent', $this->activeTabIdent);
        $View->assign('data', $this->data->components);
        $View->process();
        $View->appendTo($container);
    }
    // End appendComponentsSection


    /**
     * @return  HtmlTag
     */
    private function getElementImage()
    {
        $canvas = new HtmlTag(
            'div', null,
            [
                'class'           => 'element-image',
                'data-url'        => '/'.$this->context.'/elementImage/',
                'data-element-id' => $this->elementId,
            ]
        );

        return $canvas;
    }

    /**
     * @param DOMElement $container
     * @param            $elementId
     */
    private function appendSummary(DOMElement $container, $elementId)
    {
        $div         = $container->appendChild(
            $this->getDiv(['id' => 'section-summary', 'class' => 'element-section element-costs costs-table-width'])
        );
        $fieldsetDiv = $div->appendChild($this->getDiv(['class' => 'clear fieldset']));
        $Legend      = $fieldsetDiv->appendChild($this->getDiv(['class' => 'legend']));
        $Legend->appendChild($this->getText(t('Gesamteinsatz').' '));

        $hasOwnCosts = $this->data->quantity !== null;

        $outer = new HtmlTag('div');

        $DataTableOuterContainer = $outer->add(
            new HtmlTag('div', null, ['class' => 'costs-table composite', 'id' => 'costs-table-total'])
        );

        if ($this->data->isComposite) {
            $dataSet = LccElementCostProgressionsSet::findByCompositeElementId($elementId);
        } else {
            $dataSet = LccElementCostProgressionsSet::findByElementId($elementId);
        }

        /**
         * Reorder dataset
         */
        $collector = [];
        $hasData   = false;

        $grandTotal = (object)['process_config_name' => 'Gesamt', 'total' => 0];
        foreach ($dataSet as $data) {
            $obj = isset($collector[$data->key]) ? $collector[$data->key] : (object)null;

            list(, $eltId) = explode('_', $data->key);

            if (!is_null($data->life_time)) {
                $hasData = true;
            }

            $qty = $this->data->isComposite ? $data->quantity * $this->data->elements->quantity[$eltId]
                : $data->quantity;

            $col       = 'c'.$data->life_time;
            $obj->$col = $qty;
            if (!isset($obj->total)) {
                $obj->total = 0;
            }

            if ('c0' !== $col) {
                $obj->total += $qty;
            }
            $obj->key = $data->key;
            if ($hasOwnCosts) {
                $obj->process_config_name = ElcaElement::findById($elementId)->getName();
            } else {
                $obj->process_config_name = !$data->process_config_name ? t('Gesamt') : $data->process_config_name;
            }

            $collector[$data->key] = $obj;

            if (!isset($grandTotal->$col)) {
                $grandTotal->$col = 0;
            }

            if ('c0' !== $col) {
                $grandTotal->$col += $qty;
                $grandTotal->total += $qty;
            }
        }

        if ($hasData) {
            if ($hasOwnCosts) {
                $DataObjectSet = new DataObjectSet();
                $DataObjectSet->add(array_pop($collector));
            } else {
                $DataObjectSet = new DataObjectSet($collector);
            }

            if ($this->data->isComposite) {
                $DataObjectSet->add($grandTotal);
            }


            /**
             * Define table if data is available
             */
            $DataTableContainer = $DataTableOuterContainer->add(
                new HtmlTag('div', null, ['class' => 'scroll-wrapper'])
            );
            $Table              = new HtmlTable('');
            $Table->addColumn('process_config_name', t('Komponente'));
            $Table->addColumn('total', t('Barwert'));

            $lifeTimeColumns = [];
            foreach ($DataObjectSet as $obj) {
                foreach ($obj as $prop => $value) {
                    switch ($prop) {
                        case 'process_config_name':
                        case 'key':
                        case 'total':
                        case 'c':
                            break;
                        default:
                            $year                   = str_replace('c', '', $prop);
                            if (0 === (int)$year) {
                                break;
                            }
                            $lifeTimeColumns[$year] = $prop;
                            break;
                    }
                }
            }

            ksort($lifeTimeColumns, SORT_NUMERIC);
            $firstLifeTimeColumn = 'c' . key($lifeTimeColumns);

            foreach ($lifeTimeColumns as $year => $prop) {
                $Table->addColumn($prop, $year);
            }

            /**
             * colspaning table head (wording 'Jahre')
             */
            $Head    = $Table->createTableHead();
            $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
            $HeadRow->setAttribute('class', 'legendRow');

            $columns = [
                'process_config_name' => new HtmlTableColumn('process_config_name', ''),
                'total'               => new HtmlTableColumn('total', ''),
            ];

            if (\count($lifeTimeColumns)) {
                $columns[$firstLifeTimeColumn] = new HtmlTableColumn($firstLifeTimeColumn, t('Jahre'));

            }
            $HeadRow->setColumns($columns);

            $HeadRow->getColumn('process_config_name')->addClass('fixed');
            $HeadRow->getColumn('total')->addClass('fixed');
            if (count($lifeTimeColumns) > 1) {
                $HeadRow->getColumn($firstLifeTimeColumn)->setColSpan(count($lifeTimeColumns));
            }

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
            $Row  = $Body->addTableRow();
            foreach ($lifeTimeColumns as $prop) {
                $Row->getColumn($prop)->setOutputElement(new ElcaHtmlCurrencyText($prop));
            }

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
        } else {
            $DataTableOuterContainer->add(
                new HtmlTag(
                    'div',
                    t('Für die Berechnung der Barwertentwicklung müssen Herstellungskosten angegeben werden.'),
                    ['class' => 'no-data-available']
                )
            );
        }


        $outer->appendTo($fieldsetDiv);

    }
    // End getElementImage
}
// End ElementCostsView
