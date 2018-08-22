<?php
namespace Lcc\Controller;

use Elca\Controller\ProjectElementsCtrl;
use Elca\Controller\TabsCtrl;
use Elca\Db\ElcaCacheElementComponent;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\ElcaNumberFormat;
use Elca\Service\Messages\ElcaMessages;
use Elca\Validator\ElcaValidator;
use Lcc\Db\LccElementComponentCost;
use Lcc\Db\LccElementComponentCostSet;
use Lcc\Db\LccElementCost;
use Lcc\Db\LccElementCostProgressionsSet;
use Lcc\Db\LccElementCostSet;
use Lcc\Db\LccProjectVersion;
use Lcc\Db\LccVersion;
use Lcc\LccModule;
use Lcc\View\ElementCostsView;

/**
 * ElementsCtrl
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class ElementsCtrl extends TabsCtrl
{
    protected $context;
    protected $elementTypeNodeId;
    protected $elementId;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        $this->context = isset($args['context'])? $args['context'] : $this->Request->get('context');
        $this->elementTypeNodeId = isset($args['t'])? $args['t'] : $this->Request->get('t');
        $this->elementId = isset($args['e'])? $args['e'] : $this->Request->get('e');
    }
    // End init

    /**
     * @param null $context
     * @param null $elementId
     */
    public function defaultAction($context = null, $elementId = null, ElcaValidator $validator = null)
    {
        $force = $elementId !== null;
        $elementId = $elementId ?: $this->elementId;

        $view = $this->setView(new ElementCostsView());
        $view->assign('context', $context ?: $this->context);
        $view->assign('elementId', $elementId);
        $view->assign('activeTabIdent', $this->getActiveTabIdent());
        $view->assign('validator', $validator);
        $view->assign('data', $this->getData($elementId, $force));

        $view->assign(
            'readOnly',
            $elementId
            ? !$this->Access->canEditElement(ElcaElement::findById($elementId))
            : false
        );
    }

    /**
     *
     */
    public function saveAction()
    {
        if (!$elementId = $this->Request->elementId) {
            return;
        }
        if (!$context = $this->Request->context) {
            return;
        }

        if (!$this->checkProjectAccess()) {
            return;
        }

        $element = ElcaElement::findById($elementId);
        if (!$element->isInitialized()) {
            return;
        }

        if (!$this->Access->canEditElement($element)) {
            $this->noAccessRedirect();
            return;
        }

        $validator = new ElcaValidator($this->Request);
        $validator->assertNumber('quantity', null, t('Der Wert ist nicht numerisch'));
        $validator->assertNumber('lifeTime', null, t('Der Wert ist nicht numerisch'));

        if ($validator->isValid() && $this->Request->quantity) {
            $validator->assertNotEmpty('lifeTime', null, t('Bei eigener Angabe ist ein Wert für den Austausch erforderlich'));
        }

        if ($validator->isValid()) {
            $quantity = ElcaNumberFormat::fromString($this->Request->quantity, 2);
            $lifeTime = ElcaNumberFormat::fromString($this->Request->lifeTime, 0);

            if (!$lifeTime) {
                $lifeTime = LccElementCost::getDefaultLifeTime($element);
            }

            $needLccProcessing = false;
            $elementCost = LccElementCost::findByElementId($elementId);
            if ($elementCost->isInitialized()) {

                if ($quantity != $elementCost->getQuantity() || $lifeTime != $elementCost->getLifeTime()) {
                    $elementCost->setQuantity($quantity);
                    $elementCost->setLifeTime($lifeTime);
                    $elementCost->update();
                    $needLccProcessing = true;
                }

            } else {
                LccElementCost::create($elementId, $quantity, $lifeTime);
                $needLccProcessing = true;
            }

            if ($this->context === ProjectElementsCtrl::CONTEXT && $needLccProcessing) {
                $projectVersion = LccProjectVersion::findByPK($element->getProjectVariantId(), LccModule::CALC_METHOD_DETAILED);
                if (!$projectVersion->isInitialized()) {
                    $projectVersion = LccProjectVersion::create(
                        $element->getProjectVariantId(),
                        LccModule::CALC_METHOD_DETAILED,
                        LccVersion::findRecent(LccModule::CALC_METHOD_DETAILED)->getId()
                    );
                }

                $projectVersion->computeLcc();
            }

            // clear validator
            $validator = null;
        } else {
            foreach($validator->getErrors() as $property => $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
        }

        $this->defaultAction($context, $elementId, $validator);
    }


    /**
     *
     */
    public function saveComponentsAction()
    {
        if (!$elementId = $this->Request->elementId) {
            return;
        }

        if (!$context = $this->Request->context) {
            return;
        }

        if (!$this->checkProjectAccess()) {
            return;
        }

        $element = ElcaElement::findById($elementId);
        if (!$element->isInitialized()) {
            return;
        }

        if (!$this->Access->canEditElement($element)) {
            $this->noAccessRedirect();
            return;
        }

        $validator = new ElcaValidator($this->Request);

        $costs = $this->Request->getArray('costs');

        foreach ($costs as $componentId => $value) {
            $validator->assertNumber('costs['. $componentId .']', $value, t('Der Wert ist nicht numerisch'));
        }

        if ($validator->isValid()) {
            $needElementCostsUpdate = false;

            foreach ($costs as $componentId => $value) {
                $quantity = ElcaNumberFormat::fromString($value, 2);

                $componentCosts = LccElementComponentCost::findByElementComponentId($componentId);
                if ($componentCosts->isInitialized()) {

                    if ($quantity !== null) {
                        if ($quantity != $componentCosts->getQuantity()) {
                            $componentCosts->setQuantity($quantity);
                            $componentCosts->update();
                            $needElementCostsUpdate = true;
                        }
                    } else {
                        $componentCosts->delete();
                        $needElementCostsUpdate = true;
                    }
                } else {
                    LccElementComponentCost::create($componentId, $quantity);
                    $needElementCostsUpdate = true;
                }
            }

            $elementCosts = LccElementCost::findByElementId($elementId);

            if ($needElementCostsUpdate) {
                if ($elementCosts->isInitialized()) {
                    $elementCosts->update();
                } else {
                    LccElementCost::create($element->getId());
                }

                if ($this->context === ProjectElementsCtrl::CONTEXT) {
                    $ProjectVersion = LccProjectVersion::findByPK(
                        $element->getProjectVariantId(),
                        LccModule::CALC_METHOD_DETAILED
                    );
                    if (!$ProjectVersion->isInitialized()) {
                        $ProjectVersion = LccProjectVersion::create(
                            $element->getProjectVariantId(),
                            LccModule::CALC_METHOD_DETAILED,
                            LccVersion::findRecent(LccModule::CALC_METHOD_DETAILED)->getId()
                        );
                    }

                    $ProjectVersion->computeLcc();
                }
            }

            $this->defaultAction($context, $elementId);
        } else {
            foreach($validator->getErrors() as $property => $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
        }
    }

    /**
     *
     */
    public function saveElementsAction()
    {
        if (!$compositeElementId = $this->Request->relId)
            return;

        if (!$context = $this->Request->context)
            return;

        if (!$this->checkProjectAccess()) {
            return;
        }

        $compositeElement = ElcaElement::findById($compositeElementId);

        if (!$compositeElement->isInitialized())
            return;

        if (!$this->Access->canEditElement($compositeElement)) {
            $this->noAccessRedirect();
            return;
        }

        $validator = new ElcaValidator($this->Request);

        $costs = $this->Request->getArray('costs');
        $replacements = $this->Request->getArray('replacements');

        foreach ($costs as $elementId => $value) {
            $validator->assertNumber('costs['. $elementId .']', $value, t('Der Wert ist nicht numerisch'));
            $validator->assertNumber('replacements['. $elementId .']', $replacements[$elementId], t('Der Wert ist nicht numerisch'));

            if (!isset($replacements[$elementId]) || !is_numeric($replacements[$elementId]))
                $replacements[$elementId] = $this->Elca->getProject()->getLifeTime();
        }

        if ($validator->isValid()) {
            $needElementCostsUpdate = false;

            foreach ($costs as $elementId => $value) {
                $quantity = ElcaNumberFormat::fromString($value, 2);
                $lifeTime = ElcaNumberFormat::fromString($replacements[$elementId]);

                $elementCost = LccElementCost::findByElementId($elementId);
                if ($elementCost->isInitialized()) {

                    if ($quantity !== $elementCost->getQuantity()) {
                        $elementCost->setQuantity($quantity);
                        if ($quantity == 0)
                            $lifeTime = $this->Elca->getProject()->getLifeTime();

                        $elementCost->update();
                        $needElementCostsUpdate = true;
                    }
                    if ($lifeTime !== $elementCost->getLifeTime()) {
                        $elementCost->setLifeTime($lifeTime);
                        $elementCost->update();
                        $needElementCostsUpdate = true;
                    }


                } else {
                    LccElementCost::create($elementId, $quantity, $lifeTime);
                    $needElementCostsUpdate = true;
                }
            }

            $compositeElementCosts = LccElementCost::findByElementId($compositeElementId);

            if ($needElementCostsUpdate) {
                if ($compositeElementCosts->isInitialized()) {
                    $compositeElementCosts->update();
                } else {
                    LccElementCost::create($compositeElementId);
                }

                if ($this->context === ProjectElementsCtrl::CONTEXT) {
                    $ProjectVersion = LccProjectVersion::findByPK(
                        $compositeElement->getProjectVariantId(),
                        LccModule::CALC_METHOD_DETAILED
                    );
                    if (!$ProjectVersion->isInitialized()) {
                        $ProjectVersion = LccProjectVersion::create(
                            $compositeElement->getProjectVariantId(),
                            LccModule::CALC_METHOD_DETAILED,
                            LccVersion::findRecent(LccModule::CALC_METHOD_DETAILED)->getId()
                        );
                    }

                    $ProjectVersion->computeLcc();
                }
            }

            $this->defaultAction($context, $compositeElementId);
        } else {
            foreach($validator->getErrors() as $property => $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);

            $this->defaultAction($context, $compositeElementId, $validator);
        }
    }


    /**
     * @param $elementId
     * @return \stdClass
     */
    private function getData($elementId, $force = false)
    {
        $element = ElcaElement::findById($elementId);

        $elementCosts = LccElementCost::findByElementId($elementId, $force);

        $data = new \stdClass();

        $data->name = $element->getName();
        $data->description = $element->getDescription();
        $data->elementTypeNodeId = $element->getElementTypeNodeId();
        $data->isComposite = $element->isComposite();
        $data->elementQuantity = $element->getQuantity();
        $data->refUnit = $element->getRefUnit();
        $data->lifeTime = $elementCosts->hasLifeTime()? $elementCosts->getLifeTime() : LccElementCost::getDefaultLifeTime($element);

        if ($elementCosts->isInitialized()) {
            $data->quantity = $elementCosts->getQuantity();
            $data->calculatedQuantity = (int)$elementCosts->getCalculatedQuantity();
            $data->totalQuantity = $data->calculatedQuantity * $element->getQuantity();
        } else {
            $data->quantity = null;
            $data->calculatedQuantity = null;
            $data->totalQuantity = null;
        }

        $data->layers = new \stdClass();
        $data->components = new \stdClass();
        $data->elements = new \stdClass();

        if ($data->isComposite) {
            $elements = $element->getCompositeElements();
            $elementCosts = LccElementCostSet::findByCompositeElementId($elementId, $force)->getArrayCopy('elementId');
            $elementProdCosts = LccElementCostProgressionsSet::findProductionCostsByCompositeElementId($elementId)->getArrayCopy('element_id');

            $totalCosts = 0;
            $data->prodCostsTotal = 0;
            /**
             * @var ElcaCompositeElement $assignment
             */
            foreach ($elements as $assignment) {
                $compositeElement = $assignment->getElement();
                $elementType = $compositeElement->getElementTypeNode();
                $key = $compositeElement->getId();

                /**
                 * Production costs of sublevel element (will be overwritten if this
                 * composite element has specified costs)
                 */
                $data->elements->prodCosts[$key] = isset($elementProdCosts[$key])
                    ? $elementProdCosts[$key]->quantity
                    : null;

                $data->elements->elementId[$key] = $key;
                $data->elements->position[$key] = $assignment->getPosition();
                $data->elements->isOpaque[$key] = $compositeElement->isOpaque();
                $data->elements->quantity[$key] = $compositeElement->getQuantity();
                $data->elements->refUnit[$key] = $compositeElement->getRefUnit();
                $data->elements->dinCode[$key] = $elementType->getDinCode();
                $data->elements->isExtant[$key] = $compositeElement->isExtant();

                if (isset($elementCosts[$key])) {
                    $data->elements->costs[$key] = $elementCosts[$key]->getQuantity();
                    $totalCosts += $elementCosts[$key]->getCalculatedQuantity() * $compositeElement->getQuantity();

                    $data->elements->replacements[$key] = $elementCosts[$key]->getLifeTime() ?: $this->Elca->getProject()->getLifeTime();
                } else {
                    $data->elements->costs[$key] = null;
                    $data->elements->costsCalculated[$key] = null;
                    $data->elements->replacements[$key] = $this->Elca->getProject()->getLifeTime();
                }

                /**
                 * Calculate total production costs either from composite elements costs or
                 * the costs of the sublevel element
                 */
                if (isset($elementCosts[$key]) && $elementCosts[$key]->quantity !== null) {
                    $prodCosts = $elementCosts[$key]->quantity;
                } else {
                    $prodCosts = $data->elements->prodCosts[$key];
                }
                $data->prodCostsTotal += ($prodCosts * $compositeElement->getQuantity());
            }
            $data->totalQuantity = $totalCosts;
            $data->calculatedQuantity = $totalCosts / $data->elementQuantity;

            $data->prodCostsPerUnit = $data->prodCostsTotal / $data->elementQuantity;

        }
        else {
            $layerComponents = ElcaElementComponentSet::findLayers($elementId);
            $singleComponents = ElcaElementComponentSet::findSingleComponents($elementId);

            /**
             * @var LccElementComponentCost[] $layerCosts
             */
            $layerCosts = LccElementComponentCostSet::findLayersByElementId($elementId, $force)->getArrayCopy('elementComponentId');

            /**
             * @var LccElementComponentCost[] $singleComponentCosts
             */
            $singleComponentCosts = LccElementComponentCostSet::findComponentsByElementId($elementId, $force)->getArrayCopy('elementComponentId');

            $data->prodCostsPerUnit = 0;

            /**
             * @var ElcaElementComponent $component
             */
            foreach ($layerComponents as $component) {
                $key = $component->getId();

                $data->layers->processConfigId[$key] = $component->getProcessConfigId();
                $data->layers->layerPosition[$key] = $component->getLayerPosition();
                $data->layers->layerSiblingId[$key] = $component->getLayerSiblingId();
                $data->layers->isExtant[$key] = $component->isExtant();
                $data->layers->lifeTime[$key] = $component->getLifeTime();
                $data->layers->numReplacements[$key] = (int)ElcaCacheElementComponent::findByElementComponentId($key)->getNumReplacements();
                $data->layers->calcLca[$key] = $component->getCalcLca();
                $data->layers->layerArea[$key] = $component->getLayerArea();

                $data->layers->costs[$key] = isset($layerCosts[$key])? $layerCosts[$key]->getQuantity() : null;
                $data->layers->calculatedCosts[$key] = $data->layers->costs[$key];

                if (null !== $data->layers->costs[$key]) {
                    $data->prodCostsPerUnit += $data->layers->costs[$key];
                }
            }

            /**
             * @var ElcaElementComponent $component
             */
            foreach ($singleComponents as $component) {
                $key = $component->getId();

                $data->components->processConfigId[$key] = $component->getProcessConfigId();
                $data->components->quantity[$key] = $component->getQuantity();
                $data->components->conversionId[$key] = $component->getProcessConversionId();
                $data->components->isExtant[$key] = $component->isExtant();
                $data->components->lifeTime[$key] = $component->getLifeTime();
                $data->components->numReplacements[$key] = (int)ElcaCacheElementComponent::findByElementComponentId($key)->getNumReplacements();
                $data->components->calcLca[$key] = $component->getCalcLca();

                $data->components->costs[$key] = isset($singleComponentCosts[$key])? $singleComponentCosts[$key]->getQuantity() : null;
                $data->components->calculatedCosts[$key] = $data->components->costs[$key];

                if (null !== $data->components->costs[$key]) {
                    $data->prodCostsPerUnit += $data->components->costs[$key];
                }
            }

            if ($elementCosts->isInitialized() && $elementCosts->getQuantity() !== null) {
                $data->prodCostsPerUnit = $elementCosts->getQuantity();
                $data->prodCostsTotal = $elementCosts->getQuantity() * $data->elementQuantity;
            }
            else {
                $data->prodCostsTotal = $data->prodCostsPerUnit * $data->elementQuantity;
            }
        }
        return $data;
    }
    // End getData
}
// End ElementsCtrl
