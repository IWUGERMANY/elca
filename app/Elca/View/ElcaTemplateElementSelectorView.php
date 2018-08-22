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
use Beibob\Blibs\HttpRequest;
use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlFormGroup;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlRadiobox;
use Beibob\HtmlTools\HtmlRadioGroup;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Beibob\HtmlTools\HtmlTextInput;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProcessDbSet;
use Elca\Db\ElcaProjectVariant;
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
class ElcaTemplateElementSelectorView extends HtmlView
{
    private $projectVariantId;

    private $url;

    /**
     * @var ElcaAccess
     */
    private $access;

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * Inits the view
     *
     * @param  array $args
     */
    public function init(array $args = [])
    {
        $this->setTplName('elca_template_element_selector');

        $this->access           = ElcaAccess::getInstance();
        $this->request          = FrontController::getInstance()->getRequest();
        $this->projectVariantId = $this->get('projectVariantId');
        $this->url              = $this->get('url');
    }

    protected function beforeRender()
    {
        // init variables
        $container        = $this->getElementById('elca-template-element-selector-form-holder');
        $currentElementId = $this->get('currentElementId');

        if (!$currentElementId) {
            $currentElementId = $this->get('elementId');
        }

        $activeElement     = ElcaElement::findById($currentElementId);
        $elementTypeNodeId = $this->get('elementTypeNodeId', $activeElement->getElementTypeNodeId());

        $compatibleProcessDbs = new ElcaProcessDbSet();
        $activeProcessDbId    = $this->get('db') ?: null;

        if ($activeProcessDbId) {
            $compatibleProcessDbs->add(ElcaProcessDb::findById($activeProcessDbId));
        } else {
            $compatibleProcessDbs->add(
                ElcaProcessDb::findById(
                    ElcaProjectVariant::findById($this->projectVariantId)->getProject()->getProcessDbId()
                )
            );
        }
        $filterByProcessDbIds = $activeProcessDbId ? [$activeProcessDbId] : $compatibleProcessDbs->getArrayBy('id');

        /**
         * Form
         */
        $form = new HtmlForm('elementSelectorForm', $this->url);
        $form->setAttribute('id', 'templateElementSelectorForm');
        $form->setAttribute('class', 'clearfix modal-selector-form modal-element-selector');
        $form->setRequest($this->request);

        $form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));
        $form->add(new HtmlHiddenField('url', $this->url));

        if ($activeElement->isInitialized()) {
            $dataObject = $activeElement->getDataObject();
        } else {
            $dataObject                    = new \stdClass();
            $dataObject->elementTypeNodeId = $elementTypeNodeId;
            $dataObject->id                = $currentElementId;
        }

        $form->setDataObject($dataObject);

        /**
         * Filter scope
         */
        $this->appendScopeFilter($dataObject, $form);


        /**
         * Autocomplete search field
         */
        $this->appendAutoCompleteSearch($form, $filterByProcessDbIds, $elementTypeNodeId);

        /**
         * Element types
         */
        $elementTypeNodeId = $this->appendElementTypeSelect(
            $form,
            $elementTypeNodeId,
            $dataObject->scope,
            $filterByProcessDbIds
        );

        /**
         * Elements
         */
        if ($elementTypeNodeId) {
            $this->appendElementSelect(
                $form,
                $elementTypeNodeId,
                $activeElement,
                $dataObject->scope,
                $filterByProcessDbIds
            );
        } else // this for the js to insert an id on autocomplete search
        {
            $form->add(new HtmlHiddenField('id', ''));
        }

        /**
         * Buttons
         */
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('buttons');
        $group->add(new ElcaHtmlSubmitButton('selectElement', t('Übernehmen')));

        $form->appendTo($container);
    }

    protected function appendElementTypeSelect(
        $form, $elementTypeNodeId, $scope, $filterByProcessDbIds
    ) {
        $selectElementType = $form->add(
            new ElcaHtmlFormElementLabel(t('Bauteilgruppe'), new HtmlSelectbox('elementTypeNodeId'), true)
        );

        /**
         * List all available element types.
         */
        $elementTypeNode = ElcaElementType::findByNodeId($elementTypeNodeId);

        if ($elementTypeNode->isCompositeLevel()) {
            $selectElementType->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
            $selectElementType->setAttribute('onchange', '$(this.form).submit();');

            list($isPublicFilter, $isReferenceFilter) = $this->filterScope($scope ?? null);

            $elementTypeSet = ElcaElementTypeSet::findWithElementsByParentType(
                $elementTypeNode,
                $this->projectVariantId,
                $this->access->hasAdminPrivileges(),
                $this->access->getUserGroupIds(),
                false,
                $this->get('elementId'),
                true,
                null,
                $isPublicFilter,
                $isReferenceFilter,
                null,
                $filterByProcessDbIds,
                ['t.din_code' => 'ASC']
            );
        } else {
            $elementTypeSet = new ElcaElementTypeSet();
            $elementTypeSet->add($elementTypeNode);
        }


        foreach ($elementTypeSet as $Type) {
            $selectElementType->add(
                new HtmlSelectOption($Type->getDinCode().' '.t($Type->getName()), $Type->getNodeId())
            );
        }

        /**
         * unset elementTypeNodeId if id is not in ElementTypeSet
         */
        if ($elementTypeNodeId && !$elementTypeSet->search('nodeId', $elementTypeNodeId)) {
            $elementTypeNodeId = null;
        }

        return $elementTypeNodeId;
    }

    protected function appendScopeFilter($dataObject, $form): void
    {
        $dataObject->scope = $this->get('searchScope', '');

        $radioGroup = $form->add(new ElcaHtmlFormElementLabel(t('Filter'), new HtmlRadioGroup('scope')));
        $radioGroup->getParent()->addClass('clearfix');
        $radio = $radioGroup->add(new HtmlRadiobox(t('alle'), ''));
        $radio->setAttribute('onchange', '$(this).closest("form").submit();');
        $radio = $radioGroup->add(new HtmlRadiobox(t('privat'), 'private'));
        $radio->setAttribute('onchange', '$(this).closest("form").submit();');
        $radio = $radioGroup->add(new HtmlRadiobox(t('öffentlich'), 'public'));
        $radio->setAttribute('onchange', '$(this).closest("form").submit();');
        $radio = $radioGroup->add(new HtmlRadiobox(t('Referenz'), 'reference'));
        $radio->setAttribute('onchange', '$(this).closest("form").submit();');
    }

    protected function appendAutoCompleteSearch($form, $filterByProcessDbIds, $elementTypeNodeId): void
    {
        $search = $form->add(new ElcaHtmlFormElementLabel(t('Suche'), new HtmlTextInput('search')));
        $search->setAttribute('id', 'elca-element-search');
        $search->setAttribute('data-url', $this->url);
        $search->setAttribute('data-element-type-node-id', $elementTypeNodeId);
        $search->setAttribute('data-compatdbs', \json_encode($filterByProcessDbIds));
    }

    protected function appendElementSelect(
        $form, $elementTypeNodeId, $activeElement, $scope, $filterByProcessDbIds) {
        $selectElementType = $form->add(
            new ElcaHtmlFormElementLabel(t('Bauteilkomponente'), new HtmlSelectbox('id'), true)
        );
        $selectElementType->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
        $selectElementType->setAttribute('onchange', '$(this.form).submit();');

        list($isPublicFilter, $isReferenceFilter) = $this->filterScope($scope ?? null);

        $elementSet = ElcaElementSet::findUnassignedByElementTypeNodeId(
            $elementTypeNodeId,
            null,
            $this->access->hasAdminPrivileges(),
            $this->access->getUserGroupIds(),
            $activeElement->getId(),
            true,
            $isPublicFilter,
            $isReferenceFilter,
            null,
            $filterByProcessDbIds
        );

        foreach ($elementSet as $Element) {
            $opt = $selectElementType->add(
                new HtmlSelectOption($Element->getName().' ['.$Element->getId().']', $Element->getId())
            );
        }

        if ($elementSet->count() == 1) {
            $opt->setAttribute('selected', 'selected');
        }
    }

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
