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
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Controller\ElementsCtrl;
use Elca\Controller\ProjectElementsCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Elca;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\ElcaHtmlSubmitButton;

/**
 * Builds the element selector
 *
 * @package elca
 * @author  Tobias Lode <tobias@beibob.de>
 *
 */
class ElcaElementSelectorView extends HtmlView
{
    /**
     * Buildmodes
     */
    const BUILDMODE_ELEMENTS = 'element';
    const BUILDMODE_COMPOSITES = 'composite';

    /**
     * Context
     */
    private $context;

    /**
     * BuildMode
     */
    private $buildMode;

    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Inits the view
     *
     * @param  array $args
     */
    public function init(array $args = [])
    {
        $this->setTplName('elca_element_selector');
        $this->context   = $this->get('context');
        $this->buildMode = $this->get('buildMode', self::BUILDMODE_ELEMENTS);

        if ($this->buildMode == self::BUILDMODE_ELEMENTS) {
            $this->assign('headline', t('Bauteilkomponente suchen und wählen'));
            $this->assign(
                'info',
                t(
                    'Es werden nur Bauteilkomponenten zur Auswahl gestellt, die noch nicht in einem zusammengesetzten Bauteil verwendet werden.'
                )
            );
        } else {
            $this->assign('headline', t('Bauteil wählen'));
            $this->assign('info', t('Es werden nur Bauteil der übergeordneten Bauteilgruppe angeboten.'));
        }
    }
    // End __construct

    //////////////////////////////////////////////////////////////////////////////////////
    // protected
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Callback triggered before rendering the template
     *
     * @param  -
     * @return -
     */
    protected function beforeRender()
    {
        // init variables
        $Access              = ElcaAccess::getInstance();
        $Request             = FrontController::getInstance()->getRequest();
        $Container           = $this->getElementById('elca-element-selector-form-holder');
        $currentElementId    = $this->get('currentElementId');
        $refUnit             = $this->get('refUnit');
        $forceProjectContext = $this->get('forceProjectContext');

        if (!$currentElementId) {
            $currentElementId = $this->get('elementId');
        }

        $activeElement     = ElcaElement::findById($currentElementId);
        $elementTypeNodeId = $this->get('elementTypeNodeId', $activeElement->getElementTypeNodeId());
        $relElement        = ElcaElement::findById($this->get('relId'));

        $activeProcessDbId    = $this->get('db') ?: null;
        $compatibleProcessDbs = new ElcaProcessDbSet();
        if ($relElement->isTemplate()) {
            $compatibleProcessDbs = ElcaProcessDbSet::findElementCompatibles($relElement);

            if (0 === $compatibleProcessDbs->count()) {
                $compatibleProcessDbs = ElcaProcessDbSet::findActive();
            }
        } elseif ($activeProcessDbId) {
            $compatibleProcessDbs->add(ElcaProcessDb::findById($activeProcessDbId));
        }
        $filterByProcessDbIds = $activeProcessDbId ? [$activeProcessDbId] : $compatibleProcessDbs->getArrayBy('id');

        /**
         * Form
         */
        $form = new HtmlForm('elementSelectorForm', '/'.$this->context.'/selectElement/');
        $form->setAttribute('id', 'elementSelectorForm');
        $form->setAttribute('class', 'clearfix modal-selector-form modal-element-selector');
        $form->setRequest($Request);

        $form->add(new HtmlHiddenField('relId', $this->get('relId')));
        $form->add(new HtmlHiddenField('pos', $this->get('pos')));
        $form->add(new HtmlHiddenField('e', $this->get('elementId')));
        $form->add(new HtmlHiddenField('b', $this->buildMode));
        $form->add(new HtmlHiddenField('u', $refUnit));
        $form->add(new HtmlHiddenField('context', $this->context));


        if ($activeElement->isInitialized()) {
            $DataObject = $activeElement->getDataObject();
        } else {
            $DataObject                    = new \stdClass();
            $DataObject->elementTypeNodeId = $elementTypeNodeId;
            $DataObject->id                = $currentElementId;
        }

        $form->setDataObject($DataObject);

        if ($this->buildMode == self::BUILDMODE_ELEMENTS) {
            /**
             * Search mode
             * Set default search to projects, when changing an unassigned element,
             * and to template elements, when adding a new element
             */
            if ($this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext) {
                $DataObject->mode = $this->get(
                    'searchMode',
                    $currentElementId ? ProjectElementsCtrl::CONTEXT : ElementsCtrl::CONTEXT
                );

                $RadioGroup = $form->add(new ElcaHtmlFormElementLabel(t('Suche in '), new HtmlRadioGroup('mode')));
                $RadioGroup->getParent()->addClass('clearfix');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('Vorlagen'), ElementsCtrl::CONTEXT));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('Projekt'), ProjectElementsCtrl::CONTEXT));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
            } else {
                $DataObject->mode = $this->get('searchMode', ElementsCtrl::CONTEXT);
                $form->add(new HtmlHiddenField('mode'));
            }

            /**
             * Filter scope
             */
            if ($DataObject->mode == ElementsCtrl::CONTEXT) {
                $DataObject->scope = $this->get('searchScope', '');

                $RadioGroup = $form->add(new ElcaHtmlFormElementLabel(t('Filter'), new HtmlRadioGroup('scope')));
                $RadioGroup->getParent()->addClass('clearfix');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('alle'), ''));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('privat'), 'private'));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('öffentlich'), 'public'));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
                $Radio = $RadioGroup->add(new HtmlRadiobox(t('Referenz'), 'reference'));
                $Radio->setAttribute('onchange', '$(this).closest("form").submit();');
            } else {
                $DataObject->scope = null;
            }

            /**
             * Autocomplete search field
             */
            $Search = $form->add(new ElcaHtmlFormElementLabel(t('Suche'), new HtmlTextInput('search')));
            $Search->setAttribute('id', 'elca-element-search');
            $Search->setAttribute('data-url', '/'.$this->context.'/selectElement/');
            $Search->setAttribute('data-rel-id', $relElement->getId());
            $Search->setAttribute('data-compatdbs', \json_encode($filterByProcessDbIds));

            list($isPublicFilter, $isReferenceFilter) = $this->filterScope($DataObject->scope ?? null);

            /**
             * Element types
             */
            $Select = $form->add(
                new ElcaHtmlFormElementLabel(t('Bauteilgruppe'), new HtmlSelectbox('elementTypeNodeId'), true)
            );
            $Select->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
            $Select->setAttribute('onchange', '$(this.form).submit();');

            /**
             * List all available element types.
             */
            $ElementTypeSet = ElcaElementTypeSet::findWithElementsByParentType(
                $relElement->getElementTypeNode(),
                $DataObject->mode == ProjectElementsCtrl::CONTEXT ? $relElement->getProjectVariantId() : null,
                $Access->hasAdminPrivileges(),
                $Access->getUserGroupIds(),
                false,
                $this->get('elementId'), //$ActiveElement->getId(),
                $this->context == ElementsCtrl::CONTEXT || (($this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext) && $DataObject->mode == ElementsCtrl::CONTEXT),
                $this->context == ElementsCtrl::CONTEXT || (($this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext) && $DataObject->mode == ElementsCtrl::CONTEXT)
                    ? null : $relElement->getId(),
                $isPublicFilter,
                $isReferenceFilter,
                $refUnit,
                $filterByProcessDbIds,
                ['t.din_code' => 'ASC']
            );

            foreach ($ElementTypeSet as $Type) {
                $Select->add(new HtmlSelectOption($Type->getDinCode().' '.t($Type->getName()), $Type->getNodeId()));
            }

            /**
             * unset elementTypeNodeId if id is not in ElementTypeSet
             */
            if ($elementTypeNodeId && !$ElementTypeSet->search('nodeId', $elementTypeNodeId)) {
                $elementTypeNodeId = null;
            }
        } else {
            $DataObject->scope = null;
        }

        /**
         * Elements
         */
        if ($elementTypeNodeId) {
            $what = $this->buildMode == self::BUILDMODE_ELEMENTS
                ? t('Bauteilkomponente')
                : t(
                      'Bauteil in'
                  ).' '.ElcaElementType::findByNodeId(
                    $elementTypeNodeId
                )->getDinCode().' '.ElcaElementType::findByNodeId($elementTypeNodeId)->getName();

            $Select = $form->add(new ElcaHtmlFormElementLabel($what, new HtmlSelectbox('id'), true));
            $Select->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));

            list($isPublicFilter, $isReferenceFilter) = $this->filterScope($DataObject->scope ?? null);

            if ($this->buildMode == self::BUILDMODE_ELEMENTS) {
                $Select->setAttribute('onchange', '$(this.form).submit();');

                $ElementSet = ElcaElementSet::findUnassignedByElementTypeNodeId(
                    $elementTypeNodeId,
                    $DataObject->mode == ProjectElementsCtrl::CONTEXT ? $relElement->getProjectVariantId() : null,
                    $Access->hasAdminPrivileges(),
                    $Access->getUserGroupIds(),
                    $activeElement->getId(),
                    $this->context == ElementsCtrl::CONTEXT || ($this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext) && $DataObject->mode == ElementsCtrl::CONTEXT,
                    $isPublicFilter,
                    $isReferenceFilter,
                    $refUnit,
                    $filterByProcessDbIds
                );

                $suffix = ($this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext) && $DataObject->mode == ElementsCtrl::CONTEXT
                    ? ' (Vorlage)' : '';
            } else {
                $ElementSet = ElcaElementSet::findCompositesByElementTypeNodeId(
                    $elementTypeNodeId,
                    $this->context == ProjectElementsCtrl::CONTEXT || $forceProjectContext ? Elca::getInstance(
                    )->getProjectVariantId() : null,
                    $Access->hasAdminPrivileges(),
                    $Access->getUserGroupIds(),
                    null,
                    $isPublicFilter,
                    $isReferenceFilter,
                    $filterByProcessDbIds
                );

                $suffix = '';
            }

            foreach ($ElementSet as $Element) {
                $Opt = $Select->add(
                    new HtmlSelectOption($Element->getName().' ['.$Element->getId().']'.$suffix, $Element->getId())
                );
            }

            if ($ElementSet->count() == 1) {
                $Opt->setAttribute('selected', 'selected');
            }
        } else // this for the js to insert an id on autocomplete search
        {
            $form->add(new HtmlHiddenField('id', ''));
        }

        /**
         * Buttons
         */
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('buttons');
        $group->add(new ElcaHtmlSubmitButton('select'.ucfirst($this->buildMode), t('Übernehmen')));

        $form->appendTo($Container);
    }
    // End beforeRender


    public function filterScope($scope): array
    {
        $isPublic = $isReference = null;

        switch ($scope) {
            case 'private':
                $isPublic = false;
                break;
            case 'public':
                $isPublic = true;
                break;
            case 'reference':
                $isPublic = true;
                $isReference = true;
                break;
        }

        return [$isPublic, $isReference];
    }

}
// End ElcaElementSelectorView
