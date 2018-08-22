<?php declare(strict_types=1);
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

namespace Elca\Service\Assistant\Pillar;

use Elca\Controller\Assistant\PillarCtrl;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\Model\Assistant\Configuration;
use Elca\Model\Assistant\Pillar\Pillar;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Import\ImportObserver;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Processing\ElcaProjectVariantObserver;
use Elca\Service\Assistant\AbstractAssistant;
use Elca\Service\Assistant\ElementAssistant;
use Elca\View\Assistant\PillarElementImageView;
use Elca\View\DefaultElementImageView;

class PillarAssistant extends AbstractAssistant implements ElementObserver, ImportObserver
{
    const IDENT = 'pillar-assistant';
    const ELEMENT_IMAGE_VIEW = PillarElementImageView::class;

    /**
     * @var ElcaLcaProcessor
     */
    private $lcaProcessor;

    /**
     * Constructor
     *
     * @param ElcaLcaProcessor $lcaProcessor
     */
    public function __construct(ElcaLcaProcessor $lcaProcessor)
    {
        $this->setConfiguration(
            new Configuration(
                [
                    333, 343,
                ],
                static::IDENT,
                'Stützenassistent',
                PillarCtrl::class,
                'default',
                [
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_SIZE,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_AREA_RATIO,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_LENGTH,
                    ElementAssistant::PROPERTY_COMPONENT_LAYER_WIDTH,
                    ElementAssistant::PROPERTY_COMPONENT_PROCESS_CONFIG_ID,
                    ElementAssistant::PROPERTY_COMPONENT_QUANTITY,
                    ElementAssistant::PROPERTY_COMPONENT_CONVERSION_ID,
                    ElementAssistant::PROPERTY_REF_UNIT,
                ],
                [
                    ElementAssistant::FUNCTION_COMPONENT_DELETE,
                    ElementAssistant::FUNCTION_COMPONENT_DELETE_COMPONENT,
                    ElementAssistant::FUNCTION_COMPONENT_ADD_COMPONENT_SIBLING,
                    //ElementAssistant::FUNCTION_COMPONENT_ADD_LAYER,
                ]
            )
        );

        $this->lcaProcessor = $lcaProcessor;
    }

    /**
     * @return string
     */
    public function getIdent()
    {
        return static::IDENT;
    }

    /**
     * @param             $property
     * @param ElcaElement $element
     * @return bool
     */
    public function isLockedProperty($property, ElcaElement $element = null)
    {
        return in_array($property, $this->getConfiguration()->getLockedProperties());
    }

    /**
     * @param                           $name
     * @param ElcaElement|null          $element
     * @param ElcaElementComponent|null $component
     * @return bool
     */
    public function isLockedFunction($name, ElcaElement $element = null, ElcaElementComponent $component = null)
    {
        return in_array($name, $this->getConfiguration()->getLockedFunctions());
    }

    /**
     * @param $elementId
     * @return Pillar|null
     */
    public function getPillarFromElement($elementId = null)
    {
        $pillar = null;

        if (null !== $elementId) {
            $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, static::IDENT);

            if ($textValue = $attr->getTextValue()) {
                $pillar = unserialize(base64_decode($textValue));
            }
        }

        if ($pillar === null || !$pillar instanceof Pillar) {

            $initName = $this->getDefaultName($elementId);
            $pillar = Pillar::createDefault($initName);
        }

        return $pillar;
    }

    /**
     * @param $pillarElementId
     * @param $pillar
     * @throws \Exception
     */
    public function savePillarForElement($pillarElementId, $pillar)
    {
        $pillarElementId = $this->getPillarElementId($pillarElementId);

        $element = ElcaElement::findById($pillarElementId);
        if (!$element->isInitialized() )
            throw new \Exception('Trying to save pillar data for an uninitialized element');

        $serializedPillar = base64_encode(serialize($pillar));

        // save attribute with data
        $attr = ElcaElementAttribute::findByElementIdAndIdent($element->getId(), static::IDENT);

        if ($attr->isInitialized()) {
            $attr->setTextValue($serializedPillar);
            $attr->update();
        }
        else {
            // save attribute with data
            ElcaElementAttribute::create($pillarElementId,
                static::IDENT,
                static::IDENT,
                null,
                $serializedPillar
            );
        }
    }

    /**
     * Checks if given elementId is a pillar element.
     *
     * @param $elementId
     * @return int
     */
    public function getPillarElementId($elementId)
    {
        if ($elementId) {
            $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, static::IDENT);

            if ($attr->isInitialized()) {
                if ($attr->getNumericValue() !== null) {
                    $elementId = $attr->getNumericValue();
                }
            }
        }

        return $elementId;
    }

    /**
     * @param $elementId
     * @return bool
     */
    public function isPillarElement($elementId)
    {
        $attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, static::IDENT);

        if (!$attr->isInitialized())
            return false;

        return $attr->getNumericValue() === null;
    }

    public function computeLcaForPillarElement($pillarElementId)
    {
        $element = ElcaElement::findById($pillarElementId);

        if (!$element->getProjectVariantId())
            return;

        $this->lcaProcessor
            ->computeElement($element)
            ->updateCache($element->getProjectVariant()->getProjectId());
    }

    /**
     * @param int $elementId
     *
     * @return DefaultElementImageView
     */
    public function getElementImageView($elementId)
    {
        $viewName = self::ELEMENT_IMAGE_VIEW;

        $view = new $viewName();
        $view->assign('pillar', $this->getPillarFromElement($elementId));

        return $view;
    }

    /**
     * @param ElcaElement $elementToDelete
     * @return bool
     */
    public function onElementDelete(ElcaElement $elementToDelete)
    {
        return true;
    }

    /**
     * @param int $elementId
     * @param     $projectVariantId
     */
    public function afterDeletion($elementId, $projectVariantId = null)
    {
    }

    /**
     * @param ElcaElement $createdElement
     * @return mixed
     */
    public function onElementCreate(ElcaElement $createdElement)
    {
    }

    /**
     * @param ElcaElement $updatedElement
     * @return mixed
     */
    public function onElementUpdate(ElcaElement $updatedElement)
    {
        if (!$this->isPillarElement($updatedElement->getId())) {
            return;
        }

        $pillar = $this->getPillarFromElement($updatedElement->getId());

        if ($pillar->amount() !== $updatedElement->getQuantity()) {
            if ($updatedElement->getRefUnit() === Elca::UNIT_M) {
                $pillar->changeHeight($updatedElement->getQuantity());
                $this->savePillarForElement($updatedElement->getId(), $pillar);
            }
        }
    }

    /**
     * @param ElcaElement $element
     * @param ElcaElement $copiedElement
     * @return mixed
     */
    public function onElementCopy(ElcaElement $element, ElcaElement $copiedElement)
    {
    }

    /**
     * @param ElcaProject $project
     * @return void
     */
    public function onProjectImport(ElcaProject $project)
    {
        // TODO: Implement onProjectImport() method.
    }

    /**
     * @param ElcaElement $element
     * @return void
     */
    public function onElementImport(ElcaElement $element)
    {
    }

    /**
     * @param $elementId
     * @return null|string
     */
    protected function getDefaultName($elementId)
    {
        return $elementId ? ElcaElement::findById($elementId)->getName() : null;
    }
}
