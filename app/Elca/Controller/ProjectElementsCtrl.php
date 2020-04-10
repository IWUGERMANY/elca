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
namespace Elca\Controller;

use Beibob\Blibs\DataObjectSet;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\FloatCalc;
use Beibob\Blibs\Url;
use Beibob\Blibs\UserStore;
use Elca\Db\ElcaCacheElement;
use Elca\Db\ElcaCacheElementType;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Exception\AbstractException;
use Elca\Model\Processing\ElcaLcaProcessor;
use Elca\Model\Project\ProjectId;
use Elca\Service\Element\ElementService;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\ProjectElementService;
use Elca\Validator\ElcaValidator;
use Elca\View\ElcaElementsView;
use Elca\View\ElcaElementView;
use Elca\View\ElcaProjectElementsNavigationView;
use Elca\View\ElcaProjectElementsView;
use Elca\View\ElcaProjectNavigationView;
use Exception;

/**
 * Handles and builds project elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ProjectElementsCtrl extends ElementsCtrl
{
    /**
     * Context
     */
    const CONTEXT = 'project-elements';

    /**
     * Current context
     */
    protected $context = self::CONTEXT;

    /**
     * Default view
     */
    protected $defaultViewName = 'elca_project_elements';


    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        /**
         * Check permissions
         */
        if(!$this->checkProjectAccess())
            return;

        /**
         * Handle event variant-change
         */
        if($this->hasEvent('variant-change'))
        {
            /**
             * If not list action, then re-route to list
             */
            if($this->getAction() != 'list') {
                $Element = ElcaElement::findById($this->getAction());

                if ($Element->isInitialized()) {
                    $this->setAction('list');
                    $this->Request->set('t', $Element->getElementTypeNodeId());

                    /**
                     * Update browser url to ensure reload
                     */
                    $this->updateHashUrl($this->getActionLink('list', ['t' => $Element->getElementTypeNodeId()]));
                } else {
                    $this->updateHashUrl($this->getActionLink());
                }
            }
        }

        $this->readOnly = !$this->Access->canEditProject($this->Elca->getProject());
    }
    // End init
    protected function defaultAction($elementId = null)
    {
        parent::defaultAction($elementId);

        $this->handleIfcViewerSelectionRequest($elementId);

    }

    /**
     * Sets the osit scenario
     */
    protected function setOsitElementScenario($elementTypeNodeId, $elementId = null, $compositeElementId = null)
    {
        $this->Osit->setProjectElementScenario($elementTypeNodeId, $elementId, $compositeElementId, $this->getActiveTabIdent());
    }
    // End setOsitElementScenarioView



    /**
     * Lists all elements for the given element type node id
     */
    protected function listAction($elementTypeNodeId = null, $page = 0)
    {
        $elementTypeNodeId = $elementTypeNodeId? $elementTypeNodeId : $this->Request->t;
        if(!$elementTypeNodeId)
            return;

        if($this->Request->has('page'))
            $page = $this->Request->getNumeric('page');

        $View = $this->setView(new ElcaProjectElementsView());
        $View->assign('elementTypeNodeId', $elementTypeNodeId);
        $View->assign('action', '/project-elements/list/');
        $View->assign('context', $this->context);
        $View->assign('page', $page);
        $View->assign('assistantRegistry', $this->assistantRegistry);
        $View->assign('readOnly', !$this->Access->canEditProject($this->Elca->getProject()));


        $DO = $View->assign('FilterDO', $this->getFilterDO($this->context.'list', ['search' => null]));

        if($this->Request->isPost())
        {
            $View->assign('returnResultList', true);
            $View->assign('page', 0);
        }
        elseif(!$page)
        {
            /**
             * Empty some filters on GET and first page requests
             */
            if(!$this->Request->has('back'))
                $DO->search = null;

            $this->Osit->setListScenario($elementTypeNodeId, true);
            $this->addNavigationView($elementTypeNodeId);
        }
    }
    // End listAction

    protected function compareWithReferenceProjectsAction()
    {
        if (!$this->isAjax()) {
            return;
        }

        $changed = false;

        $config = $this->Namespace->compareWithReferenceProjects ?? new \stdClass();

        if ($config->compare !== $this->Request->has('compare')) {
            $config->compare = $this->Request->has('compare');
            $changed = true;
        }

        if ($config->indicatorId !== $this->Request->get('indicatorId')) {
            $config->indicatorId = $this->Request->get('indicatorId');
            $changed = true;
        }

        $this->Namespace->compareWithReferenceProjects = $config;

        if ($changed) {
            $this->addNavigationView($this->Request->get('activeElementTypeId'));
        }
    }

    /**
     * Creates an element from another element
     */
    protected function createFromTemplateAction()
    {
        if(!$this->Request->t)
            return;

        $elementTypeNodeId = (int)$this->Request->t;
        $page = $this->Request->has('page')? $this->Request->getNumeric('page'): 0;

        $project             = $this->Elca->getProject();
        $ProjectConstruction = $project->getProjectConstruction();
        $View                = $this->setView(new ElcaElementsView());
        $View->assign('elementTypeNodeId', $elementTypeNodeId);
        $View->assign('buildMode', ElcaElementsView::BUILDMODE_SELECT);
        $View->assign('assistantRegistry', $this->assistantRegistry);
        $View->assign('action', '/project-elements/createFromTemplate/');
        $View->assign('page', $page);

        $filterDO = $this->getFilterDO('elementslist', [
            'search' => null, 'scope'  => null,
            'constrCatalogId' =>$ProjectConstruction->getConstrCatalogId(),
            'constrDesignId' =>  $ProjectConstruction->getConstrDesignId(),
        ]);
        $filterDO->processDbId = $project->getProcessDbId();

        $View->assign('FilterDO', $filterDO);

        if($this->Request->isPost())
        {
            $View->assign('returnResultList', true);
            $View->assign('page', 0);
        }
        elseif(!$page)
        {
            /**
             * Empty some filters on GET and first page requests
             */
            $filterDO->search = null;
            $filterDO->scope = null;

            $this->Osit->setSelectorScenario($elementTypeNodeId);
        }
    }
    // End createFromTemplateAction



    /**
     * Creates a copy of a template element for the current project variant
     */
    protected function elementCopyAction()
    {
        if(!$this->Request->id)
            return;

        /**
         * Create a copy
         *  use current variantId
         *  keep ownerId, project accessGroupId
         */
        $Element = ElcaElement::findById($this->Request->id);
        $User = UserStore::getInstance()->getUser();
        $copy = $this->container->get(ElementService::class)->copyElementFrom(
            $Element,
            $User->getId(),
            $this->Elca->getProjectVariantId(),
            $this->Elca->getProject()->getAccessGroupId(),
            true
        );

        if(null === $copy || !$copy->isInitialized())
            return;

        /**
         * Compute lca
         * this implicitly computes all assigned elements as well
         */
        $this->container->get(ElcaLcaProcessor::class)
            ->computeElement($copy)
            ->updateCache($copy->getProjectVariant()->getProjectId());

        $this->addNavigationView($copy->getElementTypeNodeId());
        $this->updateHashUrl('/project-elements/' . $copy->getId() . '/');
        $this->invokeActionMethod($copy->getId());
    }
    // End elementCopyAction



    /**
     * Creates a copy of the given element
     */
    protected function copyAction($copyCacheItems = false)
    {
        if(($copiedElement = parent::copyAction(true)) &&
           $copiedElement->isInitialized())
        {
            $this->container->get(ElcaLcaProcessor::class)
                ->updateCache($copiedElement->getProjectVariant()->getProjectId());

            $this->updateHashUrl('/project-elements/'.$copiedElement->getId().'/');
        }

        return $copiedElement;
    }
    // End copyAction



    /**
     * Save config action
     */
    protected function saveAction()
    {
        if(!$this->Request->isPost())
            return;

        $element = ElcaElement::findById($this->Request->elementId);
        $ElementType = $this->Request->elementTypeNodeId? ElcaElementType::findByNodeId($this->Request->elementTypeNodeId) : $element->getElementTypeNode();

        if(isset($this->Request->saveElement))
        {
            /**
             * Check project variant matches current project
             */
            if(ElcaProjectVariant::findById($this->Request->projectVariantId)->getProjectId() != Elca::getInstance()->getProjectId())
                return;

            $Validator = new ElcaValidator($this->Request);
            $Validator->assertNotEmpty('name', null, 'Bitte geben Sie einen Namen ein');

            if($this->Request->has('refUnit'))
                $Validator->assertNotEmpty('refUnit', null, 'Bitte wählen Sie eine Bezugsgröße');

            $Validator->assertNumber('quantity', null, 'Es sind nur numerische Werte erlaubt');
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_EOL.']', 0, 5, 'Der Wert für Rückbau ist ungültig und muss zwischen 0 und 5 liegen');
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_SEPARATION.']', 0, 5, 'Der Wert für Trennung ist ungültig und muss zwischen 0 und 5 liegen');
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_RECYCLING.']', 0, 5, 'Der Wert für Verwerung ist ungültig und muss zwischen 0 und 5 liegen');

            if($Validator->isValid())
            {
                $quantity = ElcaNumberFormat::fromString($this->Request->quantity, 3);
                $oldQuantity = null;

                /**
                 * Set quantity default to 1 on non-composite elements
                 */
                if(!$quantity && !$ElementType->isCompositeLevel())
                    $quantity = 1;

                if($element->isInitialized())
                {
                    $CompositeElement = null;
                    if($element->isComposite())
                    {
                        $oldOpaqueArea = round($element->getOpaqueArea(), 3);
                        $oldQuantity = $element->getQuantity();
                    }
                    elseif($element->getElementTypeNode()->isOpaque() === false &&
                           $element->hasCompositeElement())
                    {
                        $Assignments = $element->getCompositeElements();
                        $CompositeElement = $Assignments[0]->getCompositeElement();

                        $oldOpaqueArea = $CompositeElement->getOpaqueArea();
                    }

                    $NeedLcaProcessing = null;
                    $Dbh = DbHandle::getInstance();
                    try
                    {
                        $Dbh->begin();

                        $element->setName($this->Request->name);
                        $element->setDescription($this->Request->description);

                        if($element->getQuantity() != $quantity)
                            $NeedLcaProcessing = $element;

                        $element->setQuantity($quantity);

                        if($this->Request->has('refUnit') && $this->Request->refUnit != $element->getRefUnit())
                        {
                            if($CompositeElement instanceOf ElcaElement)
                                $NeedLcaProcessing = $CompositeElement;

                            $element->setRefUnit($this->Request->refUnit);
                        }

                        /**
                         * Check area and element ref unit
                         */
                        if (false === $element->geometryAndRefUnitMatches()) {
                            $element->setRefUnit(Elca::UNIT_STK);

                            $this->Response->setHeader('X-Reload-Hash: true');
                            $this->messages->add(t('Die Geometrie hat eine Fläche abweichend von 1 m². Die Bezugsgröße wurde deshalb auf Stück angepasst.'), ElcaMessages::TYPE_INFO);
                        }

                        $element->setIsPublic(false);
                        $element->setProjectVariantId($this->Request->projectVariantId);
                        $element->update();

                        /**
                         * Descend into elements if this is a composite
                         */
                        if($element->isComposite())
                        {
                            if($element->getRefUnit() == Elca::UNIT_M2 && $this->updateAffectedOpaqueElements($element, $oldOpaqueArea)->count())
                                $NeedLcaProcessing = $element;
                            elseif ($this->updateQuantityOfAffectedElements($element, $oldQuantity)->count())
                                $NeedLcaProcessing = $element;
                        }
                        else
                        {
                            if($CompositeElement instanceOf ElcaElement)
                            {
                                $this->updateAffectedOpaqueElements($CompositeElement, $oldOpaqueArea);
                                $NeedLcaProcessing = $CompositeElement;
                            }
                        }

                        foreach ($this->container->get('elca.element_observers') as $observer) {
                            if (!$observer instanceof ElementObserver)
                                continue;

                            $observer->onElementUpdate($element);
                        }

                        if($NeedLcaProcessing instanceOf ElcaElement)
                        {
                            $this->container->get(ElcaLcaProcessor::class)
                                            ->computeElement($NeedLcaProcessing)
                                            ->updateCache($NeedLcaProcessing->getProjectVariant()->getProjectId());
                        }

                        $Dbh->commit();
                        $this->messages->add('Das Bauteil wurde gespeichert');
                    }
                    catch(Exception $Exception)
                    {
                        $Dbh->rollback();
                        throw $Exception;
                    }

                    /**
                     * Save element attributes
                     */
                    if($this->Request->has('attr'))
                    {
                        $elementAttributes = $this->Request->get('attr');

                        if(is_array($elementAttributes))
                            $this->saveElementAttributes($element, $elementAttributes);
                    }
                }
                else
                {
                    $isPublic = $this->Request->has('isPublic');
                    $accessGroupId = $this->Elca->getProject()->getAccessGroupId();
                    $element = ElcaElement::create($ElementType->getNodeId(),
                                                   $this->Request->name,
                                                   $this->Request->description,
                                                   $isPublic,
                                                   $isPublic? null : $accessGroupId,
                                                   $this->Elca->getProjectVariantId(),
                                                   $quantity,
                                                   $this->Request->refUnit,
                                                   null, // copyOfElementId
                                                   $this->Access->getUserId() // ownerId
                                                   );

                    /**
                     * Save element attributes
                     */
                    if($this->Request->has('attr'))
                    {
                        $elementAttributes = $this->Request->get('attr');

                        if(is_array($elementAttributes))
                            $this->saveElementAttributes($element, $elementAttributes);
                    }

                    $this->container->get(ElcaLcaProcessor::class)
                        ->computeElement($element)
                        ->updateCache($element->getProjectVariant()->getProjectId());

                    foreach ($this->container->get('elca.element_observers') as $observer) {
                        if (!$observer instanceof ElementObserver)
                            continue;

                        $observer->onElementCreate($element);
                    }

                    /**
                     * Update action and osit view
                     */
                    $this->Response->setHeader('X-Update-Hash: /'.$this->context.'/'. $element->getId() .'/');
                    $this->setOsitElementScenario($ElementType->getNodeId(), $element->getId());
                }

                $Validator = null;
            }
            else
            {
                foreach($Validator->getErrors() as $property => $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->generalAction($element->getId(), $Validator);
            $this->addNavigationViewOnCompareWithReferenceProject($ElementType->getNodeId());
        }
        elseif(isset($this->Request->cancel))
        {
            /**
             * In case the insert action was canceled
             */
            $this->listAction($ElementType->getNodeId());
        }
        elseif(isset($this->Request->proposeElement))
        {
            /**
             * Check if user is allowed to edit this element
             */
            if(!$this->Access->canEditElement($element))
                return false;

            if($element->isInitialized())
            {
                $this->messages->add('Soll dieses Bauteil einem Administrator vorgeschlagen werden?',
                                     ElcaMessages::TYPE_CONFIRM,
                                     '/project-elements/propose/?id='.$element->getId().'&confirmed');
            }
        }
        elseif(isset($this->Request->addAsTemplate))
        {
            /**
             * Check if user is allowed to edit this element
             */
            if(!$this->Access->canAccessElement($element))
                return false;

            if($element->isInitialized())
            {
                $this->messages->add('Soll dieses Bauteil als Vorlage kopiert werden?',
                                     ElcaMessages::TYPE_CONFIRM,
                                     '/project-elements/createAsTemplate/?id='.$element->getId().'&confirmed');
            }
        }
        else
        {
            $this->generalAction($element->getId());
        }
    }
    // End saveAction



    /**
     * Creates a template element from the current project element
     */
    protected function createAsTemplateAction()
    {
        if(!is_numeric($this->Request->id) || !$this->Request->has('confirmed'))
            return false;

        /**
         * Create a copy
         */
        $Element = ElcaElement::findById($this->Request->id);
        $User = UserStore::getInstance()->getUser();
        $Copy = $this->container->get(ElementService::class)->copyElementFrom(
            $Element,
            $User->getId(),
           null,
           $User->getGroupId(),
           true
        );

        if(null === $Copy || !$Copy->isInitialized())
            return;

        $this->messages->add('Die Vorlage wurde erstellt');
    }
    // End createAsTemplateAction


    /**
     * Deletes an element
     *
     * @param string $confirmMsg
     * @param bool   $addViews
     * @return boolean
     */
    protected function deleteAction($addViews = true)
    {
        if (!$this->Request->id) {
            return false;
        }

        $element = ElcaElement::findById((int)$this->Request->id);

        if (!$this->Access->canEditElement($element)) {
            return false;
        }

        if ($this->Request->has('confirmed')) {
            $elementTypeNodeId = $element->getElementTypeNodeId();

            $elementService = $this->container->get(ProjectElementService::class);

            if (!$elementService->deleteElement($element, $this->Request->has('recursive'))) {
                return false;
            }

            /**
             * Forward to list
             */
            if ($addViews || !$this->Request->has('composite')) {
                $this->listAction($elementTypeNodeId);
            }

            $this->messages->add(t('Der Datensatz wurde gelöscht'));

            return true;
        }

        if ($element->isComposite() && $this->Request->has('recursive')) {
            $confirmMsg = 'Soll das Bauteil und seine Komponenten wirklich gelöscht werden?';
        } elseif ($element->isComposite()) {
            $confirmMsg = 'Soll das Bauteil wirklich gelöscht werden?';
        } else {
            $confirmMsg = 'Soll die Bauteilkomponente wirklich gelöscht werden?';
        }

        $url = Url::parse($this->Request->getURI());
        $url->addParameter(['confirmed' => null]);

        if (!$addViews) {
            $url->addParameter(['composite' => null]);
        }

        $this->messages->add($confirmMsg, ElcaMessages::TYPE_CONFIRM, (string)$url);

        return false;
    }

    /**
     * Save elements within composite context
     */
    protected function saveElementsAction()
    {
        if(!$this->Request->isPost()) {
            return;
        }

        if($this->Request->has('saveElements')) {
            $Validator = new ElcaValidator($this->Request);
            $relElement = ElcaElement::findById($this->Request->relId);

            $elementIds = $this->Request->getArray('elementId');
            $quantities = $this->Request->getArray('quantity');
            $extantElements = $this->Request->getArray('isExtant');

            /**
             * Remember old area of opaque elements
             */
            $oldOpaqueArea = round($relElement->getOpaqueArea(), 3);

            $needLcaProcessing = false;
            $elementsToUpdate = [];
            /** @var ElcaCompositeElement $Assignment */
            foreach($relElement->getCompositeElements() as $Assignment)
            {
                $key = $Assignment->getPosition();

                if(!isset($elementIds[$key]))
                    continue;

                $suffix = '['.$key.']';

                $Validator->assertNotEmpty('quantity'.$suffix, null, 'Menge ist nicht numerisch');
                $Validator->assertNumber('quantity'.$suffix, null, 'Menge ist nicht numerisch');

                $Element = $Assignment->getElement();

                /**
                 * Handle extant elements with respect
                 * to the third state 'indeterminate'
                 *
                 * skip state indeterminate
                 */
                if (!isset($extantElements[$key]) ||
                    isset($extantElements[$key]) && $extantElements[$key] !== 'indeterminate') {

                    $eltIsExtant = $Element->isExtant();
                    $eltHasExtants = $Element->hasExtants();

                    /**
                     * if not set, reverse hasExtants state
                     */
                    if (!isset($extantElements[$key]) && $eltHasExtants) {
                        $needLcaProcessing |= $Element->setIsExtant(!$eltHasExtants);

                    }
                    /**
                     * if isset and neither isExtant nor hasExtants, reverse isExtant state
                     */
                    elseif (isset($extantElements[$key]) && (!$eltIsExtant || !$eltHasExtants)) {
                        $needLcaProcessing |= $Element->setIsExtant(!$eltIsExtant);
                    }
                }

                /**
                 * Handle quantity
                 */
                $qty = ElcaNumberFormat::fromString($quantities[$key], 3);
                if (FloatCalc::cmp($Element->getQuantity(), $qty))
                    continue;

                $Element->setQuantity($qty);
                $elementsToUpdate[$Element->getId()] = $Element;
                $needLcaProcessing = true;
            }

            /**
             * Update elements
             */
            if ($Validator->isValid()) {
                foreach($elementsToUpdate as $element) {
                    $element->update();

                    foreach ($this->container->get('elca.element_observers') as $observer) {
                        if (!$observer instanceof ElementObserver)
                            continue;

                        $observer->onElementUpdate($element);
                    }
                }

                $this->messages->add('Änderungen an Bauteilkomponenten wurden gespeichert.');
            }

            /**
             * Recalculate area of opaque elements with the exact old value
             */
            if ($needLcaProcessing) {

                $this->updateAffectedOpaqueElements($relElement, $oldOpaqueArea);

                $this->container->get(ElcaLcaProcessor::class)
                                ->computeElement($relElement)
                                ->updateCache($relElement->getProjectVariant()->getProjectId());
            }

            $View = $this->addCompositeView($relElement->getId(), null, true);
            if (!$Validator->isValid())
            {
                foreach($Validator->getErrors() as $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);

                $View->assign('Validator', $Validator);
            }

            $needExtantFix = $Validator->checkExtantElements($elementIds, $extantElements);

            /**
             * If needed, add confirm message to fix missing extants
             */
            if ($needExtantFix) {
                $highlightElts = [];

                foreach ($needExtantFix as $pos) {
                    $highlightElts['isExtant['. $pos .']'] = true;
                }
                $View->assign('highlightedElements', $highlightElts);

                /**
                 * warn about non-extant in between extant layers
                 */
                if (count($needExtantFix) > 1) {
                    $needExtantFix = array_reverse($needExtantFix);
                    $last = array_pop($needExtantFix);
                    $msg = 'Die Komponenten '. join(', ', $needExtantFix) .' und '. $last .' werden von Bestandskomponenten umschlossen. ';
                } else {
                    $msg = 'Komponente '. join(', ', $needExtantFix) .' wird von Bestandskomponenten umschlossen. ';
                }
                $Url = Url::factory('/' . $this->context . '/fixExtants/', ['id' => $relElement->getId()]);
                $this->messages->add($msg .'Soll dies korrigiert werden?', ElcaMessages::TYPE_CONFIRM, (string)$Url);
            }

            $this->addNavigationViewOnCompareWithReferenceProject($relElement->getElementTypeNodeId());
        }
        elseif ($this->Request->has('refreshOpaqueElements')) {
            $relElement = ElcaElement::findById($this->Request->relId);
            $this->updateAffectedOpaqueElements($relElement);

            $this->container->get(ElcaLcaProcessor::class)
                            ->computeElement($relElement)
                            ->updateCache($relElement->getProjectVariant()->getProjectId());

            $this->addCompositeView($relElement->getId(), null, true);
        }
        elseif ($this->Request->has('addElement')) {
            $this->selectElementAction();
        }
    }
    // End saveElementsAction



    /**
     * Action selectProcessConfig
     */
    protected function selectElementAction()
    {
        if(!parent::selectElementAction())
            return false;

        /**
         * Total results have changed, add summary view
         */
        if(isset($this->Request->selectElement))
        {
            $view = $this->addView(new ElcaElementView(), 'ElementSummary');
            $view->assign('buildMode', ElcaElementView::BUILDMODE_SUMMARY);
            $view->assign('context', $this->context);
            $view->assign('elementId', $this->Request->relId);
            $view->assign(
                'lifeCycleUsages',
                $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                    new ProjectId($this->Elca->getProjectId())
                )
            );
        }

        return true;
    }
    // ENd selectElementAction



    /**
     * Action moveElementAction
     */
    protected function moveElementAction()
    {
        if(!$Element = parent::moveElementAction())
            return null;

        /**
         * Get CacheElement and update parent itemId to new element type
         */
        $CElement = ElcaCacheElement::findByElementId($Element->getId());
        $CElementType = ElcaCacheElementType::findByProjectVariantIdAndElementTypeNodeId($Element->getProjectVariantId(), $Element->getElementTypeNodeId());

        if(!$CElementType->isInitialized())
            $CElementType = ElcaCacheElementType::create($Element->getProjectVariantId(), $Element->getElementTypeNodeId());

        $CItem = $CElement->getItem();
        $CItem->setParentId($CElementType->getItemId());
        $CItem->update();

        $this->container->get(ElcaLcaProcessor::class)
            ->updateElementTypeTree($Element->getProjectVariantId(), $this->Request->t)
            ->updateElementTypeTree($Element->getProjectVariantId(), $Element->getElementTypeNodeId());

        return $Element;
    }
    // End moveElementAction



    /**
     * Saves components
     */
    protected function saveComponentsAction()
    {
        $element = ElcaElement::findById($this->Request->elementId);

        /**
         * Check if the element is part of a composite element
         * and if the opaque area needs to be recomputed.
         * Remember old opaque area value
         */
        $oldMaxSurface = $oldOpaqueArea = $CompositeElement = null;
        if($element->getElementTypeNode()->isOpaque() === false &&
           $element->hasCompositeElement())
        {
            $CompositeElement = $element->getCompositeElement();
            $oldOpaqueArea = round($CompositeElement->getOpaqueArea(), 3);
            $oldMaxSurface = $element->getMaxSurface();
        }

        $LcaProcessor = $this->container->get(ElcaLcaProcessor::class);
        $Dbh = DbHandle::getInstance();

        try {
            $Dbh->begin();

            /**
             * Saving the components is done by the elements controller
             */
            if(!parent::saveComponentsAction())
                return false;

            /**
             * Recalculate area of opaque elements when the surface area of this element
             * has changed
             */
            if(null !== $oldMaxSurface &&
                ($maxSurface = $element->getMaxSurface(true)) != $oldMaxSurface)
            {
                $this->updateAffectedOpaqueElements($CompositeElement, $oldOpaqueArea);
                $LcaProcessor->computeElement($CompositeElement);
            }
            else
                $LcaProcessor->computeElement($element);

            $LcaProcessor->updateCache($element->getProjectVariant()->getProjectId());

            $Dbh->commit();

        } catch (Exception $exception) {
            $Dbh->rollback();

            if ($exception instanceof AbstractException) {
                $message = t($exception->messageTemplate(), null, $exception->parameters());
            }
            else {
                $message = $exception->getMessage();
            }

            $this->messages->clear(ElcaMessages::TYPE_NOTICE);
            $this->messages->add(t('Ein Fehler ist bei der Berechnung der Komponenten aufgetreten') .': '. $message, ElcaMessages::TYPE_ERROR);
            $this->Log->fatal($exception->getMessage(), get_class($exception));
            $this->Log->fatal($exception->getTraceAsString(), get_class($exception));

            $this->Response->setHeader('X-Reload-Hash: true');
            return false;
        }

        $view = $this->addView(new ElcaElementView(), 'ElementSummary');
        $view->assign('buildMode', ElcaElementView::BUILDMODE_SUMMARY);
        $view->assign('context', $this->context);
        $view->assign('elementId', $element->getId());
        $view->assign(
            'lifeCycleUsages',
            $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                new ProjectId($this->Elca->getProjectId())
            )
        );

        $this->addNavigationViewOnCompareWithReferenceProject($element->getElementTypeNodeId());

        return true;
    }
    // End saveComponentsAction



    /**
     * Adds a new component sibling
     */
    protected function addComponentSiblingAction()
    {
        if(!parent::addComponentSiblingAction())
            return false;

        $elementComponent = ElcaElementComponent::findById($this->Request->componentId);

        $this->container->get(ElcaLcaProcessor::class)
            ->computeElementComponent($elementComponent)
            ->computeElementComponent($elementComponent->getLayerSibling())
            ->updateCache($elementComponent->getElement()->getProjectVariant()->getProjectId());

        return true;
    }
    // End addComponentSibling

    /**
     * Copies a component
     */
    protected function cloneComponentAction()
    {
        if (!$copiedComponentId = parent::cloneComponentAction())
            return null;

        $copiedComponent = ElcaElementComponent::findById($copiedComponentId);

        $this->container->get(ElcaLcaProcessor::class)
                        ->computeElementComponent($copiedComponent)
                        ->updateCache($copiedComponent->getElement()->getProjectVariant()->getProjectId());

        return true;
    }

    /**
     * Deletes components
     */
    protected function deleteComponentAction($forwardToGeneralAction = true)
    {
        if(!is_numeric($this->Request->id))
            return false;

        $ElementComponent = ElcaElementComponent::findById($this->Request->id);
        $element = $ElementComponent->getElement();

        /**
         * Delete component without reloading the view
         */
        if(!parent::deleteComponentAction(false))
            return false;

        /**
         * Recalculate element
         */
        $this->container->get(ElcaLcaProcessor::class)
            ->computeElement($element)
            ->updateCache($element->getProjectVariant()->getProjectId());

        /**
         * Reload the view
         */
        if($forwardToGeneralAction)
            $this->generalAction($element->getId());

        return true;
    }
    // End deleteComponentAction



    /**
     * Deletes an element in composite context
     *
     * @return boolean
     */
    protected function deleteElementAction()
    {
        if (!$this->Request->id || !$this->Request->compositeElementId) {
            return false;
        }

        $Element = ElcaElement::findById((int)$this->Request->id);
        $CompositeElement = ElcaElement::findById((int)$this->Request->compositeElementId);

        if(!$Element->isInitialized() || !$CompositeElement->isInitialized())
            return false;

        if (!$this->removeFromCompositeElement($CompositeElement, null, $Element, true))
            return false;

        $CompositeElement->reindexCompositeElements();

        $this->addCompositeView($CompositeElement->getId(), null, true);
        $this->imageCache->clear($CompositeElement->getId());
        $this->elementImageAction($CompositeElement->getId());
        $this->addNavigationView($CompositeElement->getElementTypeNodeId());

        return true;
    }
    // End deleteElementAction



    /**
     * Fixes non-extant element components
     */
    protected function fixExtantsAction()
    {
        if (!parent::fixExtantsAction())
            return false;

        $element = ElcaElement::findById($this->Request->id);

        /**
         * Compute lca
         */
        $this->container->get(ElcaLcaProcessor::class)
                        ->computeElement($element)
                        ->updateCache($element->getProjectVariant()->getProjectId());

        return true;
    }
    // End fixExtantsAction



    /**
     * Assigns an element into a composite element
     *
     * @param ElcaElement $compositeElement
     * @param ElcaElement $element
     * @param  int        $position
     * @return boolean
     */
    protected function addToCompositeElement(ElcaElement $compositeElement, ElcaElement $element, $position = null)
    {
       return $this->container->get(ProjectElementService::class)->addToCompositeElement($compositeElement, $element, $position);
    }


    /**
     * Unassigns or deletes a element from a composite element
     * Re-Calculates the lca
     *
     * @param ElcaElement  $compositeElement
     * @param  int         $position
     * @param  ElcaElement $element       - element to unassign or delete
     * @param  boolean     $deleteElement - if true, the element will be deleted instead of unassigned
     * @return boolean
     */
    protected function removeFromCompositeElement(ElcaElement $compositeElement, $position, ElcaElement $element, $deleteElement = false)
    {
        return $this->container->get(ProjectElementService::class)->removeFromCompositeElement($compositeElement, $position, $element, $deleteElement);
    }

    /**
     * Render navigation
     */
    protected function addNavigationView($activeElementTypeId = null)
    {
        $view = $this->getViewByName(ElcaProjectElementsNavigationView::class);

        if(!$view)
        {
            $view = $this->addView(new ElcaProjectElementsNavigationView());
            $view->assign('context', $this->context);
            $view->assign('activeElementTypeId', $activeElementTypeId);
            $view->assign('controller', \get_class($this));
            $view->assign('projectVariantId', $this->Elca->getProjectVariantId());
        }

        $view->assign('compareWithReferenceProjects', $this->Namespace->compareWithReferenceProjects);

        $this->addView(new ElcaProjectNavigationView());
    }
    // End addNavigationView


    /**
     * Updates affected opaque elements with new area
     *
     * @param  ElcaElement $compositeElement
     * @param  float       $oldCalculatedOpaqueArea
     * @return DataObjectSet
     */
    protected function updateAffectedOpaqueElements(ElcaElement $compositeElement, $oldCalculatedOpaqueArea = null)
    {
        return $this->container->get(ProjectElementService::class)->updateAffectedOpaqueElements($compositeElement, $oldCalculatedOpaqueArea);
    }

    /**
     * Updates quantity of affected elements with new quantity
     *
     * @param  ElcaElement $CompositeElement
     * @param  float       $oldQuantity
     * @return DataObjectSet
     */
    protected function updateQuantityOfAffectedElements(ElcaElement $compositeElement, $oldQuantity)
    {
        return $this->container->get(ProjectElementService::class)->updateQuantityOfAffectedElements($compositeElement, $oldQuantity);
    }

    protected function addNavigationViewOnCompareWithReferenceProject($elementTypeNodeId = null): void
    {
        if ($this->Namespace->compareWithReferenceProjects->compare ?? false) {
            $this->addNavigationView($elementTypeNodeId);
        }
    }

    private function handleIfcViewerSelectionRequest($elementId): void
    {
        /**
         * Prevent sending an event if this element was loaded by the viewer
         */
        if ($this->Request->has('via') && $this->Request->get('via') === 'ifcViewer') {
            return;
        }


        // TODO resolve
        $someGuids = [
            '2O2Fr$t4X7Zf8NOew3FNhv',
            '3ThA22djr8AQQ9eQMA5s7I',
            '1CZILmCaHETO8tf3SgGEWh',
            '1hOSvn6df7F8_7GcBWlSXO',
            '1hOSvn6df7F8_7GcBWlR72',
        ];

        $elementId = $elementId ? $elementId : $this->getAction();

        if (\is_numeric($elementId)) {
            $msg = [
                'guid'      => $someGuids[random_int(0, count($someGuids) - 1)],
                'elementId' => $elementId,
            ];

            $this->runJs(sprintf('elca.msgBus.submit(\'elca.element-loaded\', %s);', \json_encode($msg)));
        }
    }
}
// End ProjectElementsCtrl
