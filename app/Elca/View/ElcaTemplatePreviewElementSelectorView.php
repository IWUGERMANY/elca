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
use Beibob\HtmlTools\HtmlStaticText;
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
class ElcaTemplatePreviewElementSelectorView extends HtmlView
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
        $form->add(new HtmlHiddenField('elementTypeNodeId', $elementTypeNodeId));
        $form->add(new HtmlHiddenField('url', $this->url));
        $form->add(new HtmlHiddenField('relId', $this->get('relId')));

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
        //$this->appendAutoCompleteSearch($form, $filterByProcessDbIds, $elementTypeNodeId);

        /**
         * Elements
         */
        $this->appendElementSelect(
            $form,
            $elementTypeNodeId,
            $activeElement,
            $dataObject->scope,
            $filterByProcessDbIds
        );

        /**
         * Buttons
         */
        $group = $form->add(new HtmlFormGroup(''));
        $group->addClass('buttons');
        $group->add(new ElcaHtmlSubmitButton('selectElement', t('Übernehmen')));

        $form->appendTo($container);
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

    protected function appendElementSelect(
        $form, $elementTypeNodeId, $activeElement, $scope, $filterByProcessDbIds) {

        $elementTypeNode = ElcaElementType::findByNodeId($elementTypeNodeId);
        $elementTypeName = $elementTypeNode->getDinCode() .' '. $elementTypeNode->getName();

        $selectElement = $form->add(
            new ElcaHtmlFormElementLabel(t('Bauteil in').' '.$elementTypeName, new HtmlSelectbox('id'), true)
        );
        $selectElement->add(new HtmlSelectOption('-- '.t('Bitte wählen').' --', ''));
        $selectElement->setAttribute('onchange', '$(this.form).submit();');

        list($isPublicFilter, $isReferenceFilter) = $this->filterScope($scope ?? null);

        if ($elementTypeNode->isCompositeLevel()) {

            $elementSet = ElcaElementSet::findCompositesByElementTypeNodeId(
                $elementTypeNodeId,
                null,
                $this->access->hasAdminPrivileges(),
                $this->access->getUserGroupIds(),
                null,
                $isPublicFilter,
                $isReferenceFilter,
                $filterByProcessDbIds
            );
        }
        else {
            $elementSet = ElcaElementSet::findUnassignedByElementTypeNodeId(
                $elementTypeNodeId,
                null,
                $this->access->hasAdminPrivileges(),
                $this->access->getUserGroupIds(),
                null,
                true,
                $isPublicFilter,
                $isReferenceFilter,
                null,
                $filterByProcessDbIds
            );
        }

        foreach ($elementSet as $Element) {
            $opt = $selectElement->add(
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
