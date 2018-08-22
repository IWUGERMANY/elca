<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 *
 * Copyright (c) 2010-2011 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 * Licensed under Creative Commons license CC BY-NC 3.0
 * http://creativecommons.org/licenses/by-nc/3.0/de/
 */
namespace Elca\View;

use Beibob\HtmlTools\HtmlForm;
use Beibob\HtmlTools\HtmlHiddenField;
use Beibob\HtmlTools\HtmlSelectbox;
use Beibob\HtmlTools\HtmlSelectOption;
use Elca\Db\ElcaCacheReferenceProjectSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaElementTypeSet;
use Elca\Db\ElcaIndicatorSet;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\ElcaNumberFormat;
use Elca\Model\Indicator\IndicatorIdent;
use Elca\Model\Indicator\IndicatorValue;
use Elca\Model\Indicator\ReferenceIndicatorValue;
use Elca\Model\Navigation\ElcaNavItem;
use Elca\Model\Processing\ReferenceIndicator\ReferenceIndicatorComparator;
use Elca\Security\ElcaAccess;
use Elca\View\helpers\ElcaHtmlFormElementLabel;
use Elca\View\helpers\HtmlToggleButton;

/**
 * Builds the project content head with title, phases and variants
 *
 * @package    elca
 * @author     Tobias Lode <tobias@beibob.com>
 * @author     Fabian Moeller <fab@beibob.com>
 * @copyright  2008 BEIBOB Medienfreunde
 *
 * $Id$
 */
class ElcaProjectElementsNavigationView extends ElcaElementsNavigationView
{
    private $projectVariantId;

    private $formData;

    private $referenceData;

    private $userHasAccess;

    /**
     * Inits the view.
     */
    protected function init(array $args = [])
    {
        /**
         * This lines needs to be placed above parent::init!
         */
        $this->projectVariantId = $this->get('projectVariantId');

        $this->formData = $this->get('compareWithReferenceProjects');

        if ($this->formData && $this->formData->compare) {
            $this->referenceData = ElcaCacheReferenceProjectSet::findByProjectVariantId(
                $this->projectVariantId,
                $this->formData->indicatorId
            )->getArrayCopy('element_type_node_id');
        }

        $elcaAccess = ElcaAccess::getInstance();
        $this->userHasAccess  = $elcaAccess->hasAdminPrivileges() || $elcaAccess->hasBetaPrivileges();

        parent::init($args);
        $this->setTplName('elca_project_elements_navigation_left');
    }

    protected function beforeRender()
    {
        $this->prepareReferenceProjectToggle();
    }

    protected function getElementTypes($type): ElcaElementTypeSet
    {
        return ElcaElementTypeSet::findNavigationByParentType(
            $type,
            $this->projectVariantId,
            $this->access->hasAdminPrivileges(),
            $this->access->getUserGroupIds()
        );
    }

    protected function setNavItemData(ElcaNavItem $item, ElcaElementType $elementType): void
    {
        parent::setNavItemData($item, $elementType);

        if ($this->userHasAccess &&
            null !== $this->referenceData &&
            isset($this->referenceData[$elementType->getNodeId()])
        ) {
            $referenceData = $this->referenceData[$elementType->getNodeId()];

            $indicatorIdent = new IndicatorIdent($referenceData->indicator_ident);
            $comparator     = new ReferenceIndicatorComparator(
                new ReferenceIndicatorValue(
                    $indicatorIdent,
                    $referenceData->ref_min,
                    $referenceData->ref_value,
                    $referenceData->ref_max
                ),
                new IndicatorValue(
                    $indicatorIdent,
                    $referenceData->value
                )
            );

            $item->setDataValue('ref-project-deviation',
                t('Abweichung ∅-Referenzprojekt').': '. ElcaNumberFormat::toString($comparator->deviation() * 100, 2)
            );

            $result = $comparator->compare();

            if ($result > 0) {
                $item->setDataValue('ref-project', 'positive');
            }
            elseif ($result < 0) {
                $item->setDataValue('ref-project', 'negative');
            }
            else {
                $item->setDataValue('ref-project', 'neutral');
            }
        }
    }


    private function prepareReferenceProjectToggle(): void
    {
        $container = $this->getElementById('compareWithReferenceProject');

        if (false === $this->userHasAccess ||
            null === ElcaProject::findByProjectVariantId($this->projectVariantId)->getBenchmarkVersionId()) {
            return;
        }

        $form = new HtmlForm('compareWithReferenceProjectForm', '/project-elements/compareWithReferenceProjects');

        if ($this->formData instanceof \stdClass) {
            $form->setDataObject($this->formData);
        }

        $form->add(new HtmlHiddenField('projectVariantId', $this->projectVariantId));
        $form->add(new HtmlHiddenField('activeElementTypeId', $this->activeElementTypeId));

        $label = $form->add(new ElcaHtmlFormElementLabel(t('Referenzprojektvergleich')));
        $label->addClass('toggle-button inline');
        $label->add(new HtmlToggleButton('compare'));


        $label = $form->add(new ElcaHtmlFormElementLabel(t('Indikator')));
        $label->addClass('inline');
        $label->add($select = new HtmlSelectbox('indicatorId'));

        $indicators = ElcaIndicatorSet::findByProcessDbId(ElcaProjectVariant::findById($this->projectVariantId)->getProject()->getProcessDbId());

        foreach ($indicators as $indicator) {
            $select->add(new HtmlSelectOption($indicator->getName(), $indicator->getId()));
        }

        $form->appendTo($container);

        // add class to navLeft container
        if ($this->formData && $this->formData->compare) {
            $this->addClass($this->getElementById('navLeft'), ' compare-with-reference-projects');
        }
    }
}
