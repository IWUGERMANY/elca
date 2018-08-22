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
namespace Elca\View\helpers;

use Beibob\Blibs\HtmlDOMFactory;
use Beibob\HtmlTools\HtmlDataElement;
use Beibob\HtmlTools\HtmlTable;
use Beibob\HtmlTools\HtmlTableHeadRow;
use Beibob\HtmlTools\HtmlText;
use DOMDocument;
use Elca\ElcaNumberFormat;

/**
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlComponentAssets extends HtmlDataElement
{
    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $DataObject = $this->getDataObject();
        $Factory = new HtmlDOMFactory($Document);

        switch($name = $this->getName())
        {
            case 'component_layer_position':
                $Container = $Factory->getDiv();
                $Container->setAttribute('class', 'component-details');
                $processes = $DataObject->processes;

                $Table = new HtmlTable('report-assets-details');
                $Table->addColumn('process_life_cycle_description', t('Lebenszyklus'));
                $Table->addColumn('process_ratio', t('Anteil'));
                $Table->addColumn('process_name_orig', t('Prozess'));
                $Table->addColumn('process_ref_value', t('Bezugsgröße'));
                $Table->addColumn('process_uuid', t('UUID'));

                $Head = $Table->createTableHead();
                $HeadRow = $Head->addTableRow(new HtmlTableHeadRow());
                $HeadRow->addClass('table-headlines');

                $Converter = new ElcaReportAssetsConverter();

                $Body = $Table->createTableBody();
                $Row = $Body->addTableRow();
                $Row->getColumn('process_ratio')->setOutputElement(new HtmlText('process_ratio', $Converter));
                $Row->getColumn('process_ref_value')->setOutputElement(new HtmlText('process_ref_value', $Converter));
                $Row->getColumn('process_life_cycle_description')->setOutputElement(new HtmlText('process_life_cycle_description', new ElcaTranslatorConverter()));
                $Body->setDataSet($processes);
                $Table->appendTo($Container);
                break;

            case 'process_config_name':
                $Container = $Factory->getSpan();
                $Container->appendChild($Factory->getSpan($DataObject->process_config_name, ['class' => 'process-config-name']));

                // add extant info
                if ($DataObject->component_is_extant) {
                    $Container->appendChild($Factory->getSpan(t('Altsubstanz'), ['class' => 'info info-is-extant']));
                } else {
                    $Container->appendChild($Factory->getSpan(t('Neusubstanz'), ['class' => 'info info-is-extant']));
                }

                // add quantity info
                $quantity = ElcaNumberFormat::toString($DataObject->cache_component_quantity) .' '. ElcaNumberFormat::formatUnit($DataObject->cache_component_ref_unit);
                $Span = $Container->appendChild($Factory->getSpan(t('Menge '), ['class' => 'info info-quantity']));
                $Span->appendChild($Factory->getSpan($quantity));

                if($DataObject->component_layer_area_ratio != 1) {
                    $Span = $Container->appendChild($Factory->getSpan('Flächenanteil ', ['class' => 'info info-area-ratio']));
                    $Span->appendChild($Factory->getSpan(ElcaNumberFormat::toString($DataObject->component_layer_area_ratio, 1, true) .'%'));
                }

                // add maintenance info
                if ($DataObject->component_is_extant && $DataObject->component_life_time_delay > 0) {

                    if ($DataObject->component_life_time_delay > 1)
                        $label = t('Restnutzung für %years% Jahre', null, ['%years%' => $DataObject->component_life_time_delay]);
                    else
                        $label = t('Restnutzung für ein Jahr');

                    $Container->appendChild($Factory->getSpan($label, ['class' => 'info info-life-time-delay']));
                }

                $lifeTime = $DataObject->component_life_time;

                if ($lifeTime > 1)
                    $label = t('Austausch nach %years% Jahren', null, ['%years%' => $lifeTime]);
                else
                    $label = t('Austauch nach einem Jahr');

                if($DataObject->cache_component_num_replacements)
                    $label .= ' (' . t('%count% mal', null, ['%count%' => $DataObject->cache_component_num_replacements]) . ')';

                $Container->appendChild($Factory->getSpan($label, ['class' => 'info info-life-time']));

                if ($DataObject->has_non_default_life_time) {
                    $span = $Container->appendChild($Factory->getSpan(t('Hinterlegte Nutzungsdauern') . ' ', ['class' => 'info info-default-life-times']));
                    $span->appendChild($Factory->getSpan(t('min') . ': '. $DataObject->min_life_time));
                    if ($DataObject->avg_life_time)
                        $span->appendChild($Factory->getSpan(t('mittel') . ': '. $DataObject->avg_life_time));
                    if ($DataObject->max_life_time)
                        $span->appendChild($Factory->getSpan(t('max') . ': '. $DataObject->max_life_time));

                    $span = $Container->appendChild($Factory->getSpan(t('Grund') . ' ', ['class' => 'info info-life-time-info']));
                    $span->appendChild($Factory->getSpan($DataObject->component_life_time_info?  $DataObject->component_life_time_info : '-', ['class' => $DataObject->component_life_time_info ? '' : 'no-value']));
                }

                break;
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($Container, $DataObject, $name);

        foreach($this->getChildren() as $Child)
            $Child->appendTo($Container);

        return $Container;
    }
    // End build
}
// End ElcaHtmlComponentAssets
