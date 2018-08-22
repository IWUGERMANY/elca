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
use Beibob\HtmlTools\HtmlFormElement;
use DOMDocument;
use Elca\Controller\ProjectData\ProjectElementSanityCtrl;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProjectProcessConfigSanitySet;
use Elca\Elca;
use Elca\View\ElcaProcessConfigSelectorView;

/**
 * Formats fields for a ElcaHtmlProjectElementSanity
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 */
class ElcaHtmlProjectElementSanity extends HtmlFormElement
{
    /**
     * @translate Elca\View\helpers\ElcaHtmlProjectElementSanity::$captions
     */
    public static $captions = [
        ElcaProjectProcessConfigSanitySet::CONTEXT_ELEMENTS              => 'Baukonstruktion',
        ElcaProjectProcessConfigSanitySet::CONTEXT_FINAL_ENERGY_DEMANDS  => 'Endenergiebedarf',
        ElcaProjectProcessConfigSanitySet::CONTEXT_FINAL_ENERGY_SUPPLIES => 'Endenergiebereitstellung',
    ];

    /**
     * Builds this element
     *
     * @see HtmlElement::build()
     */
    public function build(DOMDocument $Document)
    {
        $htmlDOMFactory = new HtmlDOMFactory($Document);

        $DataObject = $this->getDataObject();
        $name       = $this->getName();

        $value = isset($DataObject->$name) ? $DataObject->$name : null;

        $context = $DataObject->context;

        switch ($name) {
            case 'context':
                return $htmlDOMFactory->getText(t(self::$captions[$context]));

            case 'context_name':
                switch ($context) {
                    case ElcaProjectProcessConfigSanitySet::CONTEXT_ELEMENTS:
                        $url = '/project-elements/'.$DataObject->context_id.'/';
                        break;
                    case ElcaProjectProcessConfigSanitySet::CONTEXT_FINAL_ENERGY_DEMANDS:
                    case ElcaProjectProcessConfigSanitySet::CONTEXT_FINAL_ENERGY_SUPPLIES:
                        $url = '/project-data/enEv/';
                        break;
                }

                $elt = $htmlDOMFactory->getA(array('href' => $url, 'class' => 'page'), $value);
                break;

            case 'process_db_names':

                if (!$value) {
                    $elt = $htmlDOMFactory->getText(t('Keiner Datenbank zugeordnet!'));
                } else {
                    $elt = $htmlDOMFactory->getText($value);
                }
                break;

            case 'newProcessConfigId':
                $elt = $htmlDOMFactory->getDiv();

                if ($context !== ElcaProjectProcessConfigSanitySet::CONTEXT_ELEMENTS) {
                    return $htmlDOMFactory->getSpan(t('nicht möglich'), ['class' => 'text-mute']);
                }

                $processConfigId           = $DataObject->process_config_id;
                $processConfigSelectorLink = new ElcaHtmlProcessConfigSelectorLink(
                    'newProcessConfigId['.$processConfigId.']'
                );
                $processConfigSelectorLink->setForm($this->getForm());
                $processConfigSelectorLink->setReadonly($this->getForm()->isReadonly());
                $processConfigSelectorLink->setDataObject($DataObject);
                $processConfigSelectorLink->setProcessCategoryNodeId(
                    ElcaProcessConfig::findById($processConfigId)->getProcessCategoryNodeId()
                );
                $processConfigSelectorLink->setContext(ProjectElementSanityCtrl::CONTEXT);
                $processConfigSelectorLink->setRelId($processConfigId);
                $processConfigSelectorLink->setCaption(t('auswählen'));
                $processConfigSelectorLink->setBuildMode(ElcaProcessConfigSelectorView::BUILDMODE_DEFAULT);
                $processConfigSelectorLink->setDisableDataSheet();
                $processConfigSelectorLink->setData($DataObject->context_id);

                if (isset($value[$processConfigId])) {
                    $processConfigSelectorLink->addClass('changed');
                }

                $processConfigSelectorLink->appendTo($elt);

                break;

            default:
                return $htmlDOMFactory->getText($value);
        }

        /**
         * Set remaining attributes
         */
        $this->buildAndSetAttributes($elt, $DataObject, $name);

        return $elt;
    }
    // End build
}
// End ElcaHtmlProcessConfigSanity
