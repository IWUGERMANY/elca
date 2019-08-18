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

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\Environment;
use Beibob\Blibs\File;
use Beibob\Blibs\FrontController;
use Beibob\Blibs\Url;
use Beibob\Blibs\UserStore;
use Beibob\Blibs\Validator;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaCompositeElementSet;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementComponent;
use Elca\Db\ElcaElementComponentSet;
use Elca\Db\ElcaElementSearchSet;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessConfig;
use Elca\Db\ElcaProcessConfigSearchSet;
use Elca\Db\ElcaProcessConfigVariant;
use Elca\Db\ElcaProcessConversion;
use Elca\Db\ElcaProcessConversionSet;
use Elca\Db\ElcaProcessDbSet;
use Elca\Elca;
use Elca\ElcaNumberFormat;
use Elca\Model\Element\ElementObserver;
use Elca\Model\Import\Xml\Importer;
use Elca\Model\Navigation\ElcaTabItem;
use Elca\Model\Project\ProjectId;
use Elca\Service\Assistant\ElementAssistantRegistry;
use Elca\Service\ElcaElementImageCache;
use Elca\Service\Element\ElementService;
use Elca\Service\Mailer;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\LifeCycleUsageService;
use Elca\Validator\ElcaValidator;
use Elca\View\DefaultElementImageView;
use Elca\View\ElcaElementComponentsView;
use Elca\View\ElcaElementCompositeView;
use Elca\View\ElcaElementSelectorView;
use Elca\View\ElcaElementsNavigationView;
use Elca\View\ElcaElementsView;
use Elca\View\ElcaElementTypeSelectorView;
use Elca\View\ElcaElementView;
use Elca\View\ElcaOsitView;
use Elca\View\ElcaProcessConfigSelectorView;
use Elca\View\Modal\ModalElementImage;
use Exception;

/**
 * Handles and builds elements
 *
 * @package elca
 * @author Tobias Lode <tobias@beibob.de>
 *
 */
class ElementsCtrl extends TabsCtrl
{
    /**
     * Context
     */
    const CONTEXT = 'elements';

    /**
     * Namespace
     */
    protected $Namespace;

    /**
     * element id
     */
    protected $elementId;

    /**
     * @var ElcaElement $element
     */
    protected $element;

    /**
     * Current context
     */
    protected $context = self::CONTEXT;

    /**
     * Default view
     */
    protected $defaultViewName = 'elca_elements';

    /**
     * @var bool
     */
    protected $readOnly;

    /**
     * @var  ElementAssistantRegistry
     */
    protected $assistantRegistry;

    /**
     * @var ElcaElementImageCache
     */
    protected $imageCache;

    /**
     * @var LifeCycleUsageService
     */
    protected $lifeCycleUsageService;

    /**
     * Will be called on initialization.
     *
     * @param  array $args
     * @return void -
     */
    protected function init(array $args = [])
    {
        parent::init($args);

        if($this->hasBaseView())
            $this->getBaseView()->setContext($this->context);

        /**
         * Session namespace
         */
        $this->Namespace = $this->Session->getNamespace('elca.'. $this->context, true);

        if(isset($args['initialAction']) && is_numeric($args['initialAction']))
            $this->elementId = (int)$args['initialAction'];

        $this->assistantRegistry = $this->container->get(ElementAssistantRegistry::class);
        $this->imageCache = $this->container->get(ElcaElementImageCache::class);
        $this->lifeCycleUsageService = $this->container->get(LifeCycleUsageService::class);
    }
    // End init



    /**
     * Default action
     */
    protected function defaultAction($elementId = null)
    {
        if(!$this->isAjax())
            return;

        $elementId = $elementId? $elementId : $this->getAction();

        $elementTypeNodeId = $compositeElementId = null;
        if($elementId && is_numeric($elementId))
        {
            // Element initialisieren und Editor View einbinden
            $this->elementId = (int)$elementId;
            $this->element = ElcaElement::findById($this->elementId);

            if($this->element->isInitialized())
            {
                /**
                 * Add osit
                 */
                if($this->Request->has('rel') && ElcaCompositeElement::relationExists($this->Request->rel, $this->element->getId()))
                {
                    $compositeElementId = $this->Request->rel;
                    $CompositeElement = ElcaElement::findById($compositeElementId);
                    $elementTypeNodeId = $CompositeElement->getElementTypeNodeId();
                }
                else
                    $elementTypeNodeId = $this->element->getElementTypeNodeId();

                $this->setOsitElementScenario($elementTypeNodeId, $this->elementId, $compositeElementId);

                /**
                 * Add tabs
                 */
                if ($this->assistantRegistry->hasAssistantForElement($this->element)) {

                    $assistantConfig = $this->assistantRegistry
                        ->getAssistantForElement($this->element)
                        ->getConfiguration();

                    $this->addTabItem(
                        $assistantConfig->getIdent(),
                        t($assistantConfig->getCaption()),
                        null,
                        $assistantConfig->getController(),
                        $assistantConfig->getControllerAction(),
                        ['e' => $elementId, 'context' => $this->context]
                    );
                }

                /**
                 * Default tab
                 */
                $this->addTabItem('general', t('Allgemein'), null, get_class($this), 'general', ['e' => $this->element->getId()]);

                /**
                 * Add additional extension tabs from modules
                 */
                foreach($this->Elca->getAdditionalNavigations() as $Navigation)
                {
                    $tabs = $Navigation->getElementEditorTabs($this->context, $this->element->getId());
                    if(!is_array($tabs))
                        continue;

                    foreach($tabs as $Tab)
                    {
                        if(!$Tab instanceOf ElcaTabItem)
                            continue;

                        $this->addTabItemInstance($Tab);
                    }
                }

                /**
                 * invoke action controller
                 */
                $this->invokeTabActionController();

                /**
                 * On reload add navigation view
                 */
                if($this->isBaseRequest())
                    $this->addNavigationView($elementTypeNodeId);

                return;
            }
            else
                $this->messages->add(t('Das aufgerufene Bauteil kann nicht geöffnet werden'), ElcaMessages::TYPE_ERROR);
        }

        // set standard default view
        $View = $this->setView(new ElcaElementsView());
        $View->assign('buildMode', ElcaElementsView::BUILDMODE_OVERVIEW);
        $View->assign('tplName', $this->defaultViewName);
        $View->assign('context', $this->context);
        $View->assign('assistantRegistry', $this->assistantRegistry);
        $View->assign('readOnly', $this->readOnly);

        $this->Osit->clear();
        $this->addView(new ElcaOsitView());

        $this->addNavigationView($elementTypeNodeId);
    }
    // End defaultAction



    /**
     * Sets the osit scenario
     */
    protected function setOsitElementScenario($elementTypeNodeId, $elementId = null, $compositeElementId = null)
    {
        $this->Osit->setElementScenario($elementTypeNodeId, $elementId, $compositeElementId, $this->getActiveTabIdent());
    }
    // End setOsitElementScenarioView



    /**
     * Lists all elements for the given element type node id
     */
    protected function listAction($elementTypeNodeId = null, $page = null)
    {
        if(!$this->isAjax())
            return;

        $elementTypeNodeId = $elementTypeNodeId? $elementTypeNodeId : $this->Request->t;
        if(!$elementTypeNodeId)
            return;

        if($this->Request->has('page'))
            $page = $this->Request->getNumeric('page');

        $View = $this->setView(new ElcaElementsView());
        $View->assign('elementTypeNodeId', $elementTypeNodeId);
        $View->assign('context', $this->context);
        $View->assign('action', '/elements/list/');
        $View->assign('page', $page);
        $View->assign('assistantRegistry', $this->assistantRegistry);

        $DO = $View->assign('FilterDO', $this->getFilterDO($this->context.'list', ['search' => null,
                                                                                        'scope'  => null,
                                                                                        'constrCatalogId' => null,
                                                                                        'constrDesignId' => null,
                                                                                        'processDbId' => null,
                                                                                        ]));

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
            {
                $DO->search = null;
                //$DO->scope = null;
            }

            $this->Osit->setListScenario($elementTypeNodeId);
            $this->addNavigationView($elementTypeNodeId);
        }
    }
    // End listAction


    /**
     * general action
     */
    protected function generalAction($elementId = null, Validator $Validator = null)
    {
        $elementId = $elementId? $elementId : ($this->elementId? $this->elementId : $this->Request->e);

        /**
         * Check if user is allowed to access this element
         */
        $Element = ElcaElement::findById($elementId);
        if($Element->isInitialized() && !$this->Access->canAccessElement($Element))
            return $this->noAccessRedirect('/'.$this->context.'/list/?t='.$Element->getElementTypeNodeId());

        /**
         * Set view
         */
        $view = $this->setView(new ElcaElementView());
        $view->assign('context', $this->context);
        $view->assign('elementId', $elementId);
        $view->assign('activeTabIdent', $this->getActiveTabIdent());

        if ($projectId = $this->Elca->getProjectId()) {
            $view->assign(
                'lifeCycleUsages',
                $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                    new ProjectId($projectId)
                )
            );
        }

        if ($assistant = $this->assistantRegistry
            ->getAssistantForElement($Element))
            $view->assign('assistant', $assistant);

        if($this->Request->has('rel'))
            $view->assign('compositeElementId', $this->Request->get('rel'));

        $proposedElements = $this->Namespace->proposedElements;
        if(!isset($proposedElements[$elementId]) &&
           $this->Access->canProposeElement($Element))
            $view->assign('enableProposeElement', true);

        $view->assign('readOnly', $Element->isInitialized() && !$this->Access->canEditElement($Element));

        if($Validator)
            $view->assign('Validator', $Validator);

        if($Element->getElementTypeNode()->isCompositeLevel())
        {
            /**
             * Setup data for composite
             */
            $Data = $view->assign('Elements', new \stdClass());

            $DOSet = $Element->getCompositeElements();
            $compositeElementId = $Element->getId();
            /** @var ElcaCompositeElement $Elt */
            foreach($DOSet as $Elt)
            {
                $key = $Elt->getPosition();
                $Element = $Elt->getElement();
                $Type = ElcaElementType::findByNodeId($Element->getElementTypeNodeId());
                $Data->toggleState[$key] = isset($this->Namespace->toggleStates[$compositeElementId][$key])? $this->Namespace->toggleStates[$compositeElementId][$key] : false;
                $Data->elementId[$key] = $Element->getId();
                $Data->quantity[$key] = $Element->getQuantity();
                $Data->refUnit[$key] = ElcaNumberFormat::formatUnit($Element->getRefUnit());
                $Data->dinCode[$key] = $Type->getDinCode();
                $Data->elementType[$key] = $Type->getName();
                $Data->position[$key] = $key;
                $Data->isOpaque[$key] = $Type->isOpaque();

                if (is_bool($isExtant = $Element->isExtant()))
                    $Data->isExtant[$key] = $isExtant;
            }
        }
        else
        {
            /**
             * Setup data
             */
            $Layers = $view->assign('Layers', new \stdClass());
            $Components = $view->assign('Components', new \stdClass());

            $ComponentSet = ElcaElementComponentSet::findLayers($elementId);
            /** @var ElcaElementComponent $Component */
            foreach($ComponentSet as $Component)
            {
                $key = $Component->getId();
                $Layers->toggleState[$key] = isset($this->Namespace->toggleStates[$key])? $this->Namespace->toggleStates[$key] : false;
                $Layers->processConfigId[$key] = $Component->getProcessConfigId();
                $Layers->size[$key] = $Component->getLayerSize() * 1000; // in mm
                $Layers->lifeTime[$key] = $Component->getLifeTime();
                $Layers->lifeTimeDelay[$key] = $Component->getLifeTimeDelay();
                $Layers->lifeTimeInfo[$key] = $Component->getLifeTimeInfo();
                $Layers->calcLca[$key] = $Component->getCalcLca();
                $Layers->isExtant[$key] = $Component->isExtant();
                $Layers->position[$key] = $Component->getLayerPosition();
                $Layers->siblingId[$key] = $Component->getLayerSiblingId();
                $Layers->areaRatio[$key] = $Component->getLayerAreaRatio();
                $Layers->length[$key] = $Component->getLayerLength();
                $Layers->width[$key] = $Component->getLayerWidth();
            }
            $ComponentSet = ElcaElementComponentSet::findSingleComponents($elementId);
            foreach($ComponentSet as $Component)
            {
                $key = $Component->getId();
                $Components->toggleState[$key] = isset($this->Namespace->toggleStates[$key])? $this->Namespace->toggleStates[$key] : false;
                $Components->processConfigId[$key] = $Component->getProcessConfigId();
                $Components->quantity[$key] = $Component->getQuantity();
                $Components->conversionId[$key] = $Component->getProcessConversionId();
                $Components->calcLca[$key] = $Component->getCalcLca();
                $Components->isExtant[$key] = $Component->isExtant();
                $Components->lifeTime[$key] = $Component->getLifeTime();
                $Components->lifeTimeDelay[$key] = $Component->getLifeTimeDelay();
                $Components->lifeTimeInfo[$key] = $Component->getLifeTimeInfo();
            }
        }
    }
    // End generalAction



    /**
     * Shows the form to create a new element
     */
    protected function createAction()
    {
        if(!$this->Request->t)
            return;

        $assistant = null;
        if ($this->Request->has('assistant')) {
            $assistant = $this->assistantRegistry->getAssistantByIdent($this->Request->get('assistant'));
        }

        $tabArgs = ['t' => $this->Request->t];

        if ($assistant === null) {
            $this->addTabItem('general', t('Allgemein'), null, get_class($this), 'general', $tabArgs);

            $view = $this->addView(new ElcaElementView());
            $view->assign('context', $this->context);
            $view->assign('elementTypeNodeId', $this->Request->t);
            $view->assign('activeTabIdent', $this->getActiveTabIdent());

            if ($projectId = $this->Elca->getProjectId()) {
                $view->assign(
                    'lifeCycleUsages',
                    $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                        new ProjectId($projectId)
                    )
                );
            }

        } else {
            $tabArgs['context'] = $this->context;

            $assistantConfig = $assistant->getConfiguration();

            $this->addTabItem(
                $assistantConfig->getIdent(),
                t($assistantConfig->getCaption()),
                null,
                $assistantConfig->getController(),
                $assistantConfig->getControllerAction(),
                $tabArgs
            );

            $this->invokeTabActionController();
        }

        $this->setOsitElementScenario($this->Request->t);
    }
    // End createAction



    /**
     * Creates a copy of the given element
     */
    protected function copyAction($copyCacheItems = false)
    {
        if(!$this->Request->id)
            return null;

        $Element = ElcaElement::findById($this->Request->id);

        /**
         * Check if user is allowed to copy this element
         */
        if(!$this->Access->canAccessElement($Element))
            return null;

        if($this->Access->hasAdminPrivileges() && $Element->getAccessGroupId() != $this->Access->getUserGroupId())
            $accessGroupId = $Element->getAccessGroupId(); // keep origin access group
        else
            $accessGroupId = $Element->isTemplate() ? $this->Access->getUserGroupId() : $this->Elca->getProject()->getAccessGroupId();

        $Copy = $this->container->get(ElementService::class)->copyElementFrom(
            $Element,
            $this->Access->getUserId(),
            $Element->getProjectVariantId(),
            $accessGroupId,
            false, // copyName
            $copyCacheItems
        );

        if(null === $Copy || !$Copy->isInitialized()) {
            return $Copy;
        }

        $this->invokeActionMethod($Copy->getId());
        return $Copy;
    }
    // End copyAction



    /**
     * Save config action
     */
    protected function saveAction()
    {
        if(!$this->Request->isPost()) {
            return;
        }

        $element = ElcaElement::findById($this->Request->elementId);
        $ElementType = $this->Request->elementTypeNodeId? ElcaElementType::findByNodeId($this->Request->elementTypeNodeId) : $element->getElementTypeNode();

        if(isset($this->Request->saveElement))
        {
            /**
             * Check if user is allowed to edit this element
             */
            if($element->isInitialized() && !$this->Access->canEditElement($element))
                return false;

            $Validator = new ElcaValidator($this->Request);
            $Validator->assertNotEmpty('name', null, t('Bitte geben Sie einen Namen ein'));
            $Validator->assertNotEmpty('refUnit', null, t('Bitte wählen Sie eine Bezugsgröße'));
            $Validator->assertNumber('quantity', null, t('Es sind nur numerische Werte erlaubt'));
            $Validator->assertNotEmpty('constrCatalogId', null, t('Bitte wählen Sie mindestens einen Katalog'));
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_EOL.']', 0, 5, t('Der Wert für Rückbau ist ungültig und muss zwischen 0 und 5 liegen'));
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_SEPARATION.']', 0, 5, t('Der Wert für Trennung ist ungültig und muss zwischen 0 und 5 liegen'));
            $Validator->assertNumberRange('attr['. Elca::ELEMENT_ATTR_RECYCLING.']', 0, 5, t('Der Wert für Verwertung ist ungültig und muss zwischen 0 und 5 liegen'));

            if($Validator->isValid())
            {
                $quantity = ElcaNumberFormat::fromString($this->Request->quantity, 2);

                if(!$quantity)
                    $quantity = 1;

                if($element->isInitialized())
                {
                    $Dbh = DbHandle::getInstance();
                    try
                    {
                        $Dbh->begin();
                        $element->setName($this->Request->name);
                        $element->setDescription($this->Request->description);
                        $element->setQuantity($quantity);
                        $element->setRefUnit($this->Request->refUnit);

                        /**
                         * Check area and element ref unit
                         */
                        if (false === $element->geometryAndRefUnitMatches()) {
                            $element->setRefUnit(Elca::UNIT_STK);

                            $this->Response->setHeader('X-Reload-Hash: true');
                            $this->messages->add(t('Die Geometrie hat eine Fläche abweichend von 1 m². Die Bezugsgröße wurde deshalb auf Stück angepasst.'), ElcaMessages::TYPE_INFO);
                        }

                        /**
                         * If this element is not public but is assigned to an composite element and
                         * check public state of composite element.
                         */
                        $isPublic = $this->Request->has('isPublic');
                        if (!$isPublic && $element->hasCompositeElement()) {
                            foreach($element->getCompositeElements() as $assignment) {
                                if ($isPublic |= $assignment->getCompositeElement()->isPublic()) {
                                    break;
                                }
                            }
                        }
                        $isReference = $this->Request->has('isReference');
                        if (!$isReference && $element->hasCompositeElement()) {
                            foreach($element->getCompositeElements() as $assignment) {
                                if ($isReference |= $assignment->getCompositeElement()->isReference()) {
                                    break;
                                }
                            }
                        }

                        $element->setIsPublic($isReference || $isPublic);
                        $element->setIsReference($isReference);
                        $element->update();

                        /**
                         * Descend into elements if this is a composite
                         */
                        if($element->isComposite())
                        {
                            $hasAdminPrivileges = $this->Access->hasAdminPrivileges();
                            /** @var ElcaCompositeElement $CompositeElt */
                            foreach($element->getCompositeElements() as $CompositeElt)
                            {
                                $elt = $CompositeElt->getElement();

                                if ($elt->getQuantity() != $element->getQuantity() ||
                                    $elt->isPublic()    != $element->isPublic() ||
                                    $elt->isReference() != $element->isReference()
                                ) {
                                    /**
                                     * Change quantity only on assigned elements with same refUnit
                                     */
                                    if ($elt->getRefUnit() == $element->getRefUnit()) {
                                        $elt->setQuantity($element->getQuantity());
                                    }

                                    /**
                                     * Merge reference state from other composite elements
                                     */
                                    if($hasAdminPrivileges) {
                                        foreach($elt->getCompositeElements() as $assignment) {
                                            /**
                                             * Exclude this composite element
                                             */
                                            if ($assignment->getCompositeElementId() == $element->getId()) {
                                                continue;
                                            }

                                            $isPublic |= $assignment->getCompositeElement()->isPublic();

                                            if ($isReference |= $assignment->getCompositeElement()->isReference()) {
                                                break;
                                            }
                                        }

                                        $elt->setIsPublic($isReference || $isPublic);
                                        $elt->setIsReference($isReference);
                                    }

                                    $elt->update();
                                }
                            }
                        }

                        foreach ($this->container->get('elca.element_observers') as $observer) {
                            if (!$observer instanceof ElementObserver)
                                continue;

                            $observer->onElementUpdate($element);
                        }

                        $Dbh->commit();

                        $this->messages->add(t('Die Bauteilvorlage wurde gespeichert'));
                    }
                    catch(Exception $Exception)
                    {
                        $Dbh->rollback();
                        throw $Exception;
                    }
                }
                else
                {
                    $isPublic = $this->Request->has('isPublic');
                    $isReference = $this->Request->has('isReference');
                    $element = ElcaElement::create(
                        $ElementType->getNodeId(),
                        $this->Request->name,
                        $this->Request->description,
                        $isReference || $isPublic,
                        $isPublic ? null : $this->Access->getUserGroupId(),
                        null, // projectVariantId
                        $quantity,
                        $this->Request->refUnit,
                        null, // copyOfElementId
                        $this->Access->getUserId(), // ownerId
                        null,
                        $isReference
                    );

                    foreach ($this->container->get('elca.element_observers') as $observer) {
                        if (!$observer instanceof ElementObserver) {
                            continue;
                        }

                        $observer->onElementCreate($element);
                    }

                    /**
                     * Update action and osit view
                     */
                    $this->Response->setHeader('X-Update-Hash: /'.$this->context.'/'. $element->getId() .'/');
                    $this->setOsitElementScenario($ElementType->getNodeId(), $element->getId());
                }

                /**
                 * Construction designs
                 */
                $currentConstrDesignIds = $element->getConstrDesigns()->getArrayBy('id');
                $selectedConstrDesignIds = is_array($this->Request->constrDesignId)? $this->Request->constrDesignId : [];

                $toAdd = array_diff($selectedConstrDesignIds, $currentConstrDesignIds);
                $toRemove = array_diff($currentConstrDesignIds, $selectedConstrDesignIds);

                foreach($toAdd as $constrDesignId)
                    $element->assignConstrDesignId($constrDesignId);

                foreach($toRemove as $constrDesignId)
                    $element->unassignConstrDesignId($constrDesignId);

                // refresh list
                $element->getConstrDesigns(null, null, null, true);

                /**
                 * Construction catalogs
                 */
                $currentConstrCatalogIds = $element->getConstrCatalogs()->getArrayBy('id');
                $selectedConstrCatalogIds = is_array($this->Request->constrCatalogId)? $this->Request->constrCatalogId : [];

                $toAdd = array_diff($selectedConstrCatalogIds, $currentConstrCatalogIds);
                $toRemove = array_diff($currentConstrCatalogIds, $selectedConstrCatalogIds);

                foreach($toAdd as $constrCatalogId)
                    $element->assignConstrCatalogId($constrCatalogId);

                foreach($toRemove as $constrCatalogId)
                    $element->unassignConstrCatalogId($constrCatalogId);

                // refresh list
                $element->getConstrCatalogs(null, null, null, true);

                /**
                 * Save element attributes
                 */
                if($this->Request->has('attr'))
                {
                    $elementAttributes = $this->Request->get('attr');

                    if(is_array($elementAttributes))
                        $this->saveElementAttributes($element, $elementAttributes);
                }

                $Validator = null;
            }
            else
            {
                foreach($Validator->getErrors() as $property => $message)
                    $this->messages->add($message, ElcaMessages::TYPE_ERROR);
            }

            $this->generalAction($element->getId(), $Validator);
        }
        elseif(isset($this->Request->cancel))
        {
            /**
             * In case the insert action was canceled
             */
            $this->Response->setHeader('X-Redirect: '.(string)Url::factory('/'.$this->context.'/list/?t='.$ElementType->getNodeId()));
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
                $this->messages->add(t('Soll diese Bauteilvorlage einem Administrator vorgeschlagen werden?'),
                                     ElcaMessages::TYPE_CONFIRM,
                                     '/elements/propose/?id='.$element->getId().'&confirmed');
            }
        }
        else
        {
            $this->generalAction($element->getId());
        }
    }
    // End saveAction



    /**
     * Proposes an element to the administrator
     */
    protected function proposeAction()
    {
        if(!is_numeric($this->Request->id) || !$this->Request->has('confirmed'))
            return false;

        $Element = ElcaElement::findById($this->Request->id);
        if(!$this->Access->canProposeElement($Element))
            return false;

        /**
         * Init array for proposedElements in session
         */
        if(!isset($this->Namespace->proposedElements) || !is_array($this->Namespace->proposedElements))
            $this->Namespace->proposedElements = [];

        $proposedElements = $this->Namespace->proposedElements;

        $Config = Environment::getInstance()->getConfig();
        if(isset($Config->elca))
            $Config = $Config->elca;

        if(!isset($proposedElements[$Element->getId()]) && isset($Config->mailAddress) && Validator::isEmail($Config->mailAddress))
        {
            $User = UserStore::getInstance()->getUser();

            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/plain; charset=UTF-8";

            if($Config->mailFrom)
                $headers[] = "From: ". $Config->mailFrom;

            $msg = [];
            $msg[] = t('Hallo') . '.';
            $msg[] = '';
            $msg[] = t('Der folgende Benutzer schlägt ein Bauteil / eine Vorlage als öffentliche Vorlage vor:');
            $msg[] = '';
            $msg[] = t('Name') . ': ' . $User->getIdentifier();
            $msg[] = t('Organistation') . ': '. $User->getCompany();
            $msg[] = t('Benutzername') . ': '. $User->getAuthName();

            if($mail = $User->getEmail())
            {
                $msg[] = t('E-Mail-Adresse') . ': '.$mail;
                $headers[] = "Reply-To: " . $mail;
            }

            $msg[] = '';
            $msg[] = t('Bauteil / Vorlage') . ': '.$Element->getElementTypeNode()->getDinCode().' ' . t($Element->getElementTypeNode()->getName()) . ' / '. $Element->getName();
            $msg[] = '';

            $proto = Environment::sslActive() ? 'https' : 'http';

            if($Element->isTemplate())
                $msg[] = $proto .'://'.Environment::getServerHost().'/elements/'.$Element->getId().'/';
            else
            {
                $ProjectVariant = $Element->getProjectVariant();
                $msg[] = $proto .'://'.Environment::getServerHost().'/projects/'. $ProjectVariant->getProjectId(). '/#!/project-elements/'.$Element->getId().'/';
            }

            $msg[] = '';
            $msg[] = t('Freundliche Grüße,');
            $msg[] = 'eLCA';

            $subject = 'eLCA: ' . t('Vorschlag für eine neue Bauteilvorlage');
            $messageContent    = \implode("\r\n", $msg);

            try {

                /** @var Mailer $mail */
                $mail = FrontController::getInstance()->getEnvironment()->getContainer()->get('Elca\Service\Mailer');
                $mail->setSubject($subject);
                $mail->setTextContent($messageContent);
                $mail->send($Config->mailAddress);

                $this->messages->add(t('Der Administrator wurde per E-Mail informiert'));

                /**
                 * Mark as proposed in this session
                 */
                $proposedElements[$Element->getId()] = true;
                $this->Namespace->proposedElements = $proposedElements;
            }
            catch (\Exception $exception) {
                $this->messages->add(t('Ein Fehler ist aufgetreten: ' . $exception->getMessage()), ElcaMessages::TYPE_ERROR);
                $this->Log->error($exception);
            }
        }

        $this->generalAction($Element->getId());
    }
    // End proposeElementAction



    /**
     * Adds a new component
     */
    protected function addComponentAction()
    {
        if(!$this->Request->elementId || !$this->Request->b)
            return;

        /**
         * Check if user is allowed to add a component
         */
        if(!$this->Access->canEditElement(ElcaElement::findById($this->Request->elementId)))
            return false;

        return $this->selectProcessConfigAction();
    }
    // End addComponent



    /**
     * Adds a new component sibling
     */
    protected function addComponentSiblingAction()
    {
        if(!$this->Request->componentId)
            return false;

        $ElementComponent = ElcaElementComponent::findById($this->Request->componentId);

        if(!$ElementComponent->isInitialized())
            return false;

        /**
         * Check if user is allowed to add a component sibling
         */
        $Element = $ElementComponent->getElement();
        if(!$this->Access->canEditElement($Element))
            return false;

        $Sibling = $ElementComponent->createSibling();

        if(!$Sibling->isInitialized())
            return false;

        $this->generalAction($ElementComponent->getElementId());

        return true;
    }
    // End addComponentSibling


    /**
     * Copies a component
     */
    protected function cloneComponentAction()
    {
        if(!$this->Request->componentId)
            return null;

        $elementComponent = ElcaElementComponent::findById($this->Request->componentId);

        if (!$elementComponent->isInitialized())
            return null;

        /**
         * Check if user is allowed to add a component sibling
         */
        $element = $elementComponent->getElement();
        if (!$this->Access->canEditElement($element))
            return null;

        $copy = $elementComponent->copy(
            $element->getId(),
            null,
            true,
            true,
            ElcaElementComponent::getMaxLayerPosition($element->getId()) + 1
        );

        if (!$copy->isInitialized())
            return null;

        $this->generalAction($elementComponent->getElementId());

        return $copy->getId();
    }

    /**
     * Sorts the components
     */
    protected function sortComponentsAction()
    {
        if(!$this->Request->isPost() ||
           !isset($this->Request->positions) ||
           !is_array($this->Request->positions) ||
           !isset($this->Request->elementId))
            return;

        /**
         * Check if user is allowed to sort components
         */
        $Element = ElcaElement::findById($this->Request->elementId);
        if(!$this->Access->canEditElement($Element))
            return false;

        $updated = false;
        $Layers = ElcaElementComponentSet::findLayers($this->Request->elementId);
        foreach($this->Request->positions as $index => $eltId)
        {
            $pos = $index + 1;
            $parts = explode('-', $eltId);
            $layerId = array_pop($parts);

            if(!$Layer = $Layers->search('id', $layerId))
                continue;

            if($Layer->getLayerPosition() === $pos)
                continue;

            $Layer->setLayerPosition($pos);
            $Layer->update();

            if($Layer->hasLayerSibling())
            {
                $Sibling = $Layer->getLayerSibling();
                $Sibling->setLayerPosition($pos);
                $Sibling->update();
            }
            $updated = true;
        }

        if($updated)
        {
            $this->imageCache->clear($Element->getId());
            $this->elementImageAction($Element->getId());
        }
    }
    // End sortComponentsAction



    /**
     * Sorts the elements
     */
    protected function sortElementsAction()
    {
        if(!$this->Request->isPost() ||
           !isset($this->Request->positions) ||
           !is_array($this->Request->positions) ||
           !isset($this->Request->elementId))
            return false;

        /**
         * Check if user is allowed to sort components
         */
        $CompositeElement = ElcaElement::findById($this->Request->elementId);
        if(!$this->Access->canEditElement($CompositeElement))
            return false;

        $Dbh = DbHandle::getInstance();
        $Dbh->begin();

        $updated = false;
        $startIndex = $this->Request->get('startIndex', 0);
        $Assignments = ElcaCompositeElementSet::findByCompositeElementId($CompositeElement->getId());
        foreach($Assignments as $index => $Assignment)
        {
            if($index < $startIndex)
                continue;

            $blockIndex = ($index - $startIndex);

            if(!isset($this->Request->positions[$blockIndex]))
                continue;

            $eltId = $this->Request->positions[$blockIndex];
            $pos = $index + 1;
            $parts = explode('-', $eltId);
            $elementId = array_pop($parts);

            $Assignment->setPosition($pos);
            $Assignment->setElementId($elementId);
            $Assignment->update();
            $updated = true;
        }
        $Dbh->commit();

        if($updated)
        {
            $this->imageCache->clear($CompositeElement->getId());
            $this->addCompositeView($CompositeElement->getId());
            $this->elementImageAction($CompositeElement->getId());
        }

        return $updated;
    }
    // End sortElementsAction



    /**
     * Removes a new component
     */
    protected function deleteComponentAction($forwardToGeneralAction = true)
    {
        if(!is_numeric($this->Request->id))
            return false;

        $ElementComponent = ElcaElementComponent::findById($this->Request->id);
        if(!$ElementComponent->isInitialized())
            return false;

        /**
         * Check if user is allowed to delete components
         */
        $Element = $ElementComponent->getElement();
        if(!$this->Access->canEditElement($Element))
            return false;

        if($this->Request->has('confirmed'))
        {
            $Dbh = DbHandle::getInstance();
            if($ElementComponent->isInitialized())
            {
                $elementId = $ElementComponent->getElementId();
                $Dbh->begin();

                /**
                 * First handle sibling
                 */
                if($ElementComponent->hasLayerSibling())
                {
                    $Sibling = $ElementComponent->getLayerSibling();
                    $Sibling->setLayerSiblingId(null);
                    $Sibling->setLayerAreaRatio(1);
                    $Sibling->update();
                }

                /**
                 * Delete component
                 */
                $ElementComponent->delete();

                /**
                 * Reindex layer positions
                 */
                $Element->reindexLayers();

                $Dbh->commit();

                /**
                 * Clear image cache
                 */
                $this->imageCache->clear($Element->getId());
                $this->messages->add(t('Die Komponente wurde gelöscht'));

                /**
                 * Refresh view
                 */
                if($forwardToGeneralAction)
                    $this->generalAction($elementId);

                return true;
            }
        }
        else
        {
            $Url = Url::parse($this->Request->getURI());
            $Url->addParameter(['confirmed' => null]);

            $this->messages->add(t('Soll die Bauteilkomponente wirklich gelöscht werden?'),
                                 ElcaMessages::TYPE_CONFIRM,
                                 (string)$Url);
        }

        return false;
    }
    // End deleteComponentSibling



    /**
     * Action selectProcessConfig
     */
    protected function selectProcessConfigAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if(isset($this->Request->term))
        {
            $compatDbs = $this->Request->compatdbs ?: [];
            $activeProcessDbIds = $this->Request->db ? [$this->Request->db] : $compatDbs;

            $keywords          = explode(' ', \trim((string)$this->Request->term));
            $inUnit            = $this->Request->has('u')? $this->Request->get('u') : null;
            $Results           = ElcaProcessConfigSearchSet::findByKeywords($keywords, $this->Elca->getLocale(), $inUnit, !$this->Access->hasAdminPrivileges(),
                $this->context == self::CONTEXT? $activeProcessDbIds : [$this->Elca->getProject()->getProcessDbId()], null, $this->Request->epdSubType);

            $returnValues = [];
            foreach($Results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->process_category_node_id;
                $DO->label = \processConfigName($Result->id);
                $DO->category = $Result->process_category_parent_node_name .' > '. $Result->process_category_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        elseif(!isset($this->Request->select))
        {
            $view = $this->setView(new ElcaProcessConfigSelectorView());
            $view->assign('db', $this->Request->db);
            $view->assign('processConfigId', $this->Request->sp? $this->Request->sp : ($this->Request->id? $this->Request->id : $this->Request->p));
            $view->assign('elementId', $this->Request->elementId);
            $view->assign('relId', $this->Request->relId);
            $view->assign('processCategoryNodeId', $this->Request->processCategoryNodeId);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('context', $this->context);
            $view->assign('epdSubType', $this->Request->epdSubType);

            // clear selected search process config
            $this->Request->set('sp', null);
        }
        /**
         * If user pressed select button, assign the new process
         */
        elseif(isset($this->Request->select))
        {
            $Element = ElcaElement::findById($this->Request->elementId);
            if(!$this->Access->canEditElement($Element))
                return false;

            /**
             * Set view
             */
            $view = $this->setView(new ElcaElementComponentsView());
            $view->assign('context', $this->context);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('elementId', $this->Request->elementId);
            if ($assistant = $this->assistantRegistry
                ->getAssistantForElement($Element))
                $view->assign('assistant', $assistant);

            if ($projectId = $this->Elca->getProjectId()) {
                $view->assign(
                    'lifeCycleUsages',
                    $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                        new ProjectId($projectId)
                    )
                );
            }

            $componentId = $this->Request->relId;

            // in id is the newProcessConfigId, in p the old
            $ProcessConfig = ElcaProcessConfig::findById($this->Request->id);

            $quantity = $conversionId = null;
            if($variantUuid = $this->Request->get('processConfigVariantUuid'))
            {
                $Variant = ElcaProcessConfigVariant::findByPk($ProcessConfig->getId(), $variantUuid);

                if($Variant->isInitialized())
                {
                    $quantity = ElcaNumberFormat::toString($Variant->getRefValue());

                    $Conversion = ElcaProcessConversion::findProductionByProcessConfigIdAndRefUnit($ProcessConfig->getId(), $Variant->getRefUnit());
                    $conversionId = $Conversion->getId();
                }
            }

            /**
             * Build data
             */
            $DO = new \stdClass();

            if (!is_numeric($componentId))
            {
                $view->assign('addNewComponent', true);

                if($this->Request->b == ElcaElementComponentsView::BUILDMODE_COMPONENTS)
                    $Components = ElcaElementComponentSet::findSingleComponents($this->Request->elementId);
                else
                    $Components = ElcaElementComponentSet::findLayers($this->Request->elementId);

                foreach($Components as $component)
                {
                    $key = $component->getId();
                    $DO->toggleState[$key] = isset($this->Namespace->toggleStates[$key])? $this->Namespace->toggleStates[$key] : false;
                    $DO->processConfigId[$key] = $component->getProcessConfigId();
                    $DO->size[$key] = $component->getLayerSize() * 1000; // in mm
                    $DO->quantity[$key] = $component->getQuantity();
                    $DO->conversionId[$key] = $component->getProcessConversionId();
                    $DO->lifeTime[$key] = $component->getLifeTime();
                    $DO->lifeTimeDelay[$key] = $component->getLifeTimeDelay();
                    $DO->lifeTimeInfo[$key] = $component->getLifeTimeInfo();
                    $DO->calcLca[$key] = $component->getCalcLca();
                    $DO->isExtant[$key] = $component->isExtant();
                    $DO->position[$key] = $component->getLayerPosition();
                    $DO->siblingId[$key] = $component->getLayerSiblingId();
                    $DO->areaRatio[$key] = $component->getLayerAreaRatio();
                    $DO->length[$key] = $component->getLayerLength();
                    $DO->width[$key] = $component->getLayerWidth();
                }

                /**
                 * Add new component to request
                 */
                $key = $this->Request->b === ElcaElementComponentsView::BUILDMODE_COMPONENTS? 'new_components' : 'new_layers';
                $this->Request->toggleStates = [$key => false];
                $this->Request->processConfigId = [$key => $ProcessConfig->getId()];
                $this->Request->calcLca = [$key => true];
                $this->Request->isExtant = [$key => false];
                $this->Request->lifeTime = [$key => $ProcessConfig->getDefaultLifeTime()];
                $this->Request->lifeTimeDelay = [$key => 0];
                $this->Request->lifeTimeInfo = [$key => ''];
                $this->Request->length = [$key => 1];
                $this->Request->width = [$key => 1];
                $this->Request->areaRatio = [$key => 100];

                $changedElts = [];

                if ($this->Request->b === ElcaElementComponentsView::BUILDMODE_LAYERS &&
                    $ProcessConfig->getDefaultSize()
                ) {
                    $this->Request->size = [$key => $ProcessConfig->getDefaultSize() * 1000];
                    $changedElts['size['.$key.']'] = true;
                }

                if($quantity && $conversionId)
                {
                    $this->Request->quantity = [$key => $quantity];
                    $this->Request->conversionId = [$key => $conversionId];

                    $changedElts['quantity['.$key.']'] = true;
                    $changedElts['conversionId['.$key.']'] = true;
                }

                if($this->Request->id != $this->Request->p)
                    $changedElts['processConfigId['.$key.']'] = true;

                $view->assign('changedElements', $changedElts);
            }
            else
            {
                $key = $componentId;
                $component = ElcaElementComponent::findById($key);
                $DO->toggleState[$key] = false; // keep closed
                $DO->position[$key] = $this->Request->pos;
                $DO->quantity[$key] = $quantity? $quantity : $component->getQuantity();
                $DO->conversionId[$key] = $conversionId? $conversionId : $component->getProcessConversionId();
                $DO->areaRatio[$key] = $component->getLayerAreaRatio();
                $DO->size[$key] = $component->getLayerSize()? $component->getLayerSize() * 1000 : null; // in mm
                $DO->calcLca[$key] = $component->getCalcLca();
                $DO->isExtant[$key] = $component->isExtant();
                $DO->processConfigId[$key] = $ProcessConfig->getId();
                $DO->lifeTime[$key] = $ProcessConfig->getDefaultLifeTime();
                $DO->lifeTimeDelay[$key] = 0;
                $DO->lifeTimeInfo[$key] = '';
                $DO->length[$key] = $component->getLayerLength();
                $DO->width[$key] = $component->getLayerWidth();

                $changedElts = [];

                if($this->Request->id != $this->Request->p)
                    $changedElts['processConfigId['.$componentId.']'] = true;

                if($component->getLifeTime() != $ProcessConfig->getDefaultLifeTime())
                    $changedElts['lifeTime['.$componentId.']'] = true;

                if($component->getLifeTimeDelay() !== 0)
                    $changedElts['lifeTimeDelay['.$componentId.']'] = true;

                if($conversionId && $quantity)
                {
                    $changedElts['quantity['.$componentId.']'] = true;
                    $changedElts['conversionId['.$componentId.']'] = true;
                }

                if ($this->Request->b === ElcaElementComponentsView::BUILDMODE_LAYERS &&
                    $ProcessConfig->getDefaultSize()
                ) {
                    $this->Request->size = [$key => $ProcessConfig->getDefaultSize() * 1000];
                    $changedElts['size['.$key.']'] = true;
                }

                $view->assign('changedElements', $changedElts);

                /**
                 * Keep closed
                 */
                $toggleStates = $this->Namespace->toggleStates;
                $toggleStates[$key] = false;
                $this->Namespace->toggleStates = $toggleStates;

                /**
                 * Build single life cycle row
                 */
                $view->assign('elementComponentId', $componentId);
            }

            $view->assign('Data', $DO);
        }
    }
    // End selectProcessConfigAction



    /**
     * Action moveElementAction
     */
    protected function moveElementAction()
    {
        if(!isset($this->Request->select))
        {
            $View = $this->setView(new ElcaElementTypeSelectorView());
            $View->assign('elementId', $this->Request->id);
            $View->assign('elementTypeNodeId', $this->Request->nodeId);
            $View->assign('action', $this->getActionLink($this->getAction()));
        }
        elseif(isset($this->Request->select))
        {
            $elementTypeNodeId = $this->Request->t;
            if($elementTypeNodeId != $this->Request->nodeId)
            {
                $ElementType = ElcaElementType::findByNodeId($this->Request->nodeId);

                if($ElementType->isInitialized())
                {
                    $Element = ElcaElement::findById($this->Request->id);
                    if($Element->isInitialized())
                    {
                        $Element->setElementTypeNodeId($ElementType->getNodeId());
                        $Element->update();

                        $this->listAction($elementTypeNodeId);
                        $this->addNavigationView($elementTypeNodeId);

                        return $Element;
                    }
                }
            }
        }

        return null;
    }
    // End moveElementAction



    /**
     *
     */
    protected function saveElementsAction()
    {
        if(!$this->Request->isPost() || !$this->Request->has('addElement'))
            return;

        $this->selectElementAction();
    }
    // End saveElementsAction



    /**
     * Action selectProcessConfig
     */
    protected function selectElementAction()
    {
        /**
         * If a term was send, autocomplete term
         */
        if(isset($this->Request->term))
        {
            $compatDbs = $this->Request->compatdbs ?: [];
            $keywords = explode(' ', \trim((string)$this->Request->term));
            $searchMode = $this->Request->has('m')? $this->Request->get('m') : null;
            $searchScope = $this->Request->has('scope')? $this->Request->get('scope') : null;
            $compositeElement = ElcaElement::findById($this->Request->ce);

            list($isPublicFilter, $isReferenceFilter) = $this->filterScope($searchScope ?? null);

            $Results = ElcaElementSearchSet::findByKeywordsAndCompositeElementTypeNodeId(
                $keywords,
                $compositeElement->getElementTypeNodeId(),
                $searchMode == self::CONTEXT ? null : $compositeElement->getProjectVariantId(),
                $this->Access->hasAdminPrivileges(),
                $this->Access->getUserGroupId(),
                $this->context == self::CONTEXT || $searchMode == ElementsCtrl::CONTEXT,
                $this->context == self::CONTEXT || $searchMode == ElementsCtrl::CONTEXT ? null
                    : $compositeElement->getId(),
                $isPublicFilter,
                $isReferenceFilter,
                null,
                $compatDbs
            );

            $returnValues = [];
            $suffix = $searchMode == ElementsCtrl::CONTEXT? ' (' . t('Vorlage') . ')' : '';
            foreach($Results as $Result)
            {
                $DO = $returnValues[] = new \stdClass();
                $DO->id = $Result->id;
                $DO->catId = $Result->element_type_node_id;
                $DO->label = $Result->name . $suffix;
                $DO->category = $Result->element_type_node_name;
            }

            $this->getView()->assign('results', $returnValues);
        }
        /**
         * This selects and assigns an element in composite element context,
         * if a user pressed the select button
         */
        elseif(isset($this->Request->selectElement))
        {
            $compositeElement = ElcaElement::findById($this->Request->relId);
            if(!$this->Access->canEditElement($compositeElement))
                return false;

            /**
             * Delete old element (e)
             */
            if($this->Request->pos && $this->Request->e && $this->Request->id != $this->Request->e)
            {
                /**
                 * Implicit delete old element if in project context
                 */
                $deleteOldElement = $this->context != self::CONTEXT;
                $this->unassignElementAction($this->Request->pos, $this->Request->e, $compositeElement->getId(), $deleteOldElement, false, true);
            }

            /**
             * Assign new element(id)
             */
            if(($elementId = $this->Request->id) && $this->Request->id != $this->Request->e)
            {
                $element = ElcaElement::findById($elementId);

                /**
                 * Assign new element
                 */
                $this->addToCompositeElement($compositeElement, $element, $this->Request->pos ?: null);
            }

            $this->generalAction($compositeElement->getId());
            $this->addNavigationView($compositeElement->getElementTypeNodeId());
            $this->imageCache->clear($compositeElement->getId());

            return true;
        }
        /**
         * Select composite element in list context
         */
        elseif(isset($this->Request->selectComposite))
        {
            $element = ElcaElement::findById($this->Request->relId);

            $compositeElementId = $this->Request->id;

            /**
             * Unassign from old composite element
             */
            if($this->Request->pos && $this->Request->e && $this->Request->id != $this->Request->e)
                $this->unassignCompositeAction($this->Request->pos, $element->getId(), $this->Request->e, false, false);

            /**
             * Assign to new composite element
             */
            if($compositeElementId)
            {
                $compositeElement = ElcaElement::findById($compositeElementId);
                if(!$this->Access->canEditElement($compositeElement))
                    return false;

                /**
                 * Assign new element
                 */
                $this->addToCompositeElement($compositeElement, $element);
            }

            $this->listAction($element->getElementTypeNodeId());

            return true;
        }
        /**
         * If request not contains the select argument, rebuild the view
         */
        else
        {
            $view = $this->setView(new ElcaElementSelectorView());
            $view->assign('elementId', $this->Request->e);
            $view->assign('currentElementId', $this->Request->id);
            $view->assign('relId', $this->Request->relId);
            $view->assign('pos', $this->Request->pos);
            $view->assign('elementTypeNodeId', $this->Request->elementTypeNodeId? $this->Request->elementTypeNodeId : $this->Request->t);
            $view->assign('context', $this->context);
            $view->assign('buildMode', $this->Request->b);
            $view->assign('searchMode', $this->Request->has('mode')? $this->Request->get('mode') : null);
            $view->assign('searchScope', $this->Request->has('scope')? $this->Request->get('scope') : null);

            $relElement = ElcaElement::findById($this->Request->relId);
            if ($relElement->getProjectVariantId()) {
                $view->assign('db', $relElement->getProjectVariant()->getProject()->getProcessDbId());
            }
        }

        return false;
    }
    // End selectElementAction


    /**
     * Unassigns an element from a composite element
     *
     * @param null $position
     * @param null $elementId
     * @param null $compositeElementId
     * @param bool $deleteElement
     * @param bool $addViews
     * @param bool $keepIndex
     * @return bool -
     */
    protected function unassignElementAction($position = null, $elementId = null, $compositeElementId = null, $deleteElement = false, $addViews = true, $keepIndex = false)
    {
        $compositeElementId = $compositeElementId? $compositeElementId : $this->Request->compositeElementId;
        $position = $position? $position : $this->Request->pos;
        $elementId = $elementId? $elementId : $this->Request->e;
        if(!$position || !$elementId || !$compositeElementId)
            return false;

        $OldElement = ElcaElement::findById($elementId);
        $CompositeElement = ElcaElement::findById($compositeElementId);
        if(!$this->removeFromCompositeElement($CompositeElement, $position, $OldElement, $deleteElement))
            return false;

        if (!$keepIndex)
            $CompositeElement->reindexCompositeElements();

        if($addViews)
        {
            /**
             * Add composite and summary view
             */
            $this->generalAction($compositeElementId);
            $this->imageCache->clear($CompositeElement->getId());
        }

        return true;
    }
    // End unassignElementAction


    /**
     * Unassigns an element from a composite element
     *
     * @param null $position
     * @param null $elementId
     * @param null $compositeElementId
     * @param bool $addViews
     * @return bool -
     */
    protected function unassignCompositeAction($position = null, $elementId = null, $compositeElementId = null, $addViews = true)
    {
        $compositeElementId = $compositeElementId? $compositeElementId : $this->Request->compositeElementId;
        $position = $position? $position : $this->Request->pos;
        $elementId = $elementId? $elementId : $this->Request->id;
        if(!$position || !$elementId || !$compositeElementId)
            return false;

        $Element = ElcaElement::findById($elementId);
        $CompositeElement = ElcaElement::findById($compositeElementId);
        if(!$this->removeFromCompositeElement($CompositeElement, $position, $Element))
            return false;

        $CompositeElement->reindexCompositeElements();

        if($addViews)
            $this->listAction($Element->getElementTypeNodeId());

        return true;
    }
    // End unassignCompositeAction



    /**
     * Shows the element image
     */
    protected function elementImageAction($elementId = null, $width = null, $height = null)
    {
        $elementId = $elementId? $elementId : (int)$this->Request->elementId;
        if(!$elementId)
            return;

        $width = $width? $width : (int)$this->Request->w;
        $height = $height? $height : (int)$this->Request->h;

        $assistant = null;
        $element = ElcaElement::findById($elementId);
        if ($this->assistantRegistry->hasAssistantForElement($element)) {

            $assistant = $this->assistantRegistry->getAssistantForElement($element);
        }

        if($this->Request->m)
        {
            $View = $this->addView(new ModalElementImage());
            $elementImageViewName = null;

            if ($assistant)
                $View->setElementImageView($assistant->getElementImageView($elementId));
            else
                $View->setElementImageView(new DefaultElementImageView());

            $View->assign('elementId', $this->Request->elementId);
            $View->assign('width', $width);
            $View->assign('height', $height);
            $View->assign('showTotalSize', true);
        }
        else
        {
            if($width)
                $this->Namespace->imageWidth = $width;

            if($height)
                $this->Namespace->imageHeight = $height;

            $elementImageView = $this->addView(new ElcaElementView());
            $elementImageView->assign('buildMode', ElcaElementView::BUILDMODE_ELEMENT_IMAGE);
            $elementImageView->assign('imageShowTotalSize', true);
            $elementImageView->assign('elementId', $elementId);
            $elementImageView->assign('imageWidth',  $this->Namespace->imageWidth);
            $elementImageView->assign('imageHeight', $this->Namespace->imageHeight);

            if ($assistant) {
                $elementImageView->assign('assistant', $assistant);
            }
        }
    }
    // End elementImageAction



    /**
     * Saves components
     */
    protected function saveComponentsAction()
    {
        if(!$this->Request->isPost())
            return false;

        /**
         * Check if user is allowed to edit element
         */
        $element = ElcaElement::findById($this->Request->elementId);
        if(!$this->Access->canEditElement($element))
            return false;

        $modified = false;

        $Validator = new ElcaValidator($this->Request);
        $addNewComponent = isset($this->Request->a)? (bool)$this->Request->a : false;

        $previousAvailableProcessDbIds = null;
        if ($element->isTemplate()) {
            $previousAvailableProcessDbIds = ElcaProcessDbSet::findElementCompatibles(
                $element
            )->getArrayBy('id');
        }

        /**
         * Set view
         */
        $view = new ElcaElementComponentsView();
        $view->assign('context', $this->context);
        $view->assign('elementId', $element->getId());
        $view->assign('buildMode', $this->Request->b);
        $view->assign('readOnly', !$this->Access->canEditElement($element));

        if ($assistant = $this->assistantRegistry->getAssistantForElement($element)) {
            $view->assign('assistant', $assistant);
        }

        if ($projectId = $this->Elca->getProjectId()) {
            $view->assign(
                'lifeCycleUsages',
                $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                    new ProjectId($projectId)
                )
            );
        }

        /**
         * Component data
         */
        $DO = new \stdClass();

        /**
         * Either save layers or single components
         */
        if(isset($this->Request->saveLayers))
        {
            $Validator->assertLayers();

            if($Validator->isValid())
            {
                $positions = $this->Request->getArray('position');

                /**
                 * Save Layers
                 */
                foreach ($positions as $key => $pos) {
                    $modified |= $this->saveLayer($element, $key);
                }
                $this->messages->add(t('Die geometrische Bauteilkomponente wurde gespeichert'));

                /**
                 * Check area and element ref unit
                 */
                if (false === $element->geometryAndRefUnitMatches()) {
                    $element->setRefUnit(Elca::UNIT_STK);
                    $element->update();
                    $this->Response->setHeader('X-Reload-Hash: true');
                    $this->messages->add(t('Die Geometrie hat eine Fläche abweichend von 1 m². Die Bezugsgröße wurde deshalb auf Stück angepasst.'), ElcaMessages::TYPE_INFO);
                }

                /**
                 * Check condition for extant components:
                 */
                $needExtantFix = $Validator->checkExtantComponents($positions, $this->Request->getArray('isExtant'));

                /**
                 * If needed, add confirm message to fix missing extants
                 */
                $highlightElts = [];

                if ($needExtantFix) {
                    $highlightElts = array_merge($highlightElts, $this->highlightInvalidExtantComponents($positions, $needExtantFix));

                    /**
                     * warn about non-extant in between extant layers
                     */
                    if (count($needExtantFix) > 1) {
                        $needExtantFix = array_reverse($needExtantFix);
                        $last = array_pop($needExtantFix);
                        $msg = t('Die Schichten %needExtantFix% und %last% werden von Bestandsbaustoffen umschlossen.', null, ['%needExtantFix%' => join(', ', $needExtantFix), '%last%' => $last]);
                    } else {
                        $msg = t('Schicht %needExtantFix% wird von Bestandsbaustoffen umschlossen. ', null, ['%needExtantFix%' => join(', ', $needExtantFix)]);
                    }

                    $url = Url::factory('/' . $this->context . '/fixExtants/', ['id' => $element->getId()]);
                    $this->messages->add($msg . t('Soll dies korrigiert werden?'), ElcaMessages::TYPE_CONFIRM, (string)$url);
                }

                $needLimeTimeFix = $Validator->checkLifeTimeComponents($positions, $this->Request->getArray('lifeTime'));

                if ($needLimeTimeFix) {
                    $highlightElts = \array_merge($highlightElts, $this->highlightInvalidLifeTimeComponents($needLimeTimeFix));
                    $this->messages->add(t('Es sind Schichten mit geringeren Nutzungsdauern von höheren eingeschlossen!'), ElcaMessages::TYPE_INFO);
                }


                $view->assign('highlightedElements', $highlightElts);

                $this->elementImageAction($element->getId());
                $this->imageCache->clear($element->getId());
            }
        }
        elseif(isset($this->Request->saveComponents))
        {
            $Validator->assertSingleComponents();

            if($Validator->isValid())
            {
                /**
                 * Components
                 */
                if(is_array($this->Request->processConfigId))
                {
                    foreach($this->Request->processConfigId as $key => $processConfigId)
                        $modified |= $this->saveComponent($element, $key);

                    $this->messages->add(t('Die Einzelkomponente wurden gespeichert'));
                }

                $this->elementImageAction($element->getId());
                $this->imageCache->clear($element->getId());
            }
        }
        elseif(isset($this->Request->addLayer))
        {
            $key = 'new_layers';
            if (isset($this->Request->processConfigId[$key]))
                $Validator->assertLayer($key, ElcaProcessConfig::findById($this->Request->processConfigId[$key])->getLifeTimes());

            if($Validator->isValid())
            {
                /**
                 * Save previously added layer and add select process action views
                 */
                if (isset($this->Request->processConfigId[$key]))
                    $modified = $this->saveLayer($element, $key);
                $this->selectProcessConfigAction();
            }
            else
                $addNewComponent = true;
        }
        elseif(isset($this->Request->addComponent))
        {
            $key = 'new_components';
            if (isset($this->Request->processConfigId[$key]))
                $Validator->assertSingleComponent($key, ElcaProcessConfig::findById($this->Request->processConfigId[$key])->getLifeTimes());

            if($Validator->isValid())
            {
                /**
                 * Save previously added layer and add select process action views
                 */
                if (isset($this->Request->processConfigId[$key]))
                    $modified = $this->saveComponent($element, $key);
                $this->selectProcessConfigAction();
            }
            else
                $addNewComponent = true;
        }

        if ($Validator->isValid()) {
            $addNewComponent = false;

            /**
             * Check if available processDbIds have changed after
             */
            if ($element->isTemplate()) {
                $availableProcessDbIds = ElcaProcessDbSet::findElementCompatibles(
                    $element,
                    null,
                    null,
                    null,
                    true
                )->getArrayBy('id');

                if ($availableProcessDbIds != $previousAvailableProcessDbIds) {
                    $this->generalAction($element->getId());
                    return $modified;
                }
            }
        }
        else {
            foreach ($Validator->getErrors() as $property => $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);

            $view->assign('Validator', $Validator);
        }

        /**
         * Assign addNewComponent to keep new components at view
         */
        $view->assign('addNewComponent', $addNewComponent);

        if($this->Request->b == ElcaElementComponentsView::BUILDMODE_COMPONENTS)
            $Components = ElcaElementComponentSet::findSingleComponents($element->getId());
        else
            $Components = ElcaElementComponentSet::findLayers($element->getId());

        /** @var ElcaElementComponent $Component */
        foreach($Components as $Component)
        {
            $key = $Component->getId();

            $DO->toggleState[$key] = isset($this->Namespace->toggleStates[$key])? $this->Namespace->toggleStates[$key] : false;
            $DO->processConfigId[$key] = $Component->getProcessConfigId();
            $DO->size[$key] = $Component->getLayerSize() * 1000; // in mm
            $DO->quantity[$key] = $Component->getQuantity();
            $DO->conversionId[$key] = $Component->getProcessConversionId();
            $DO->lifeTime[$key] = $Component->getLifeTime();
            $DO->lifeTimeDelay[$key] = $Component->getLifeTimeDelay();
            $DO->lifeTimeInfo[$key] = $Component->getLifeTimeInfo();
            $DO->calcLca[$key] = $Component->getCalcLca();
            $DO->isExtant[$key] = $Component->isExtant();
            $DO->position[$key] = $Component->getLayerPosition();
            $DO->siblingId[$key] = $Component->getLayerSiblingId();
            $DO->areaRatio[$key] = $Component->getLayerAreaRatio();
            $DO->length[$key] = $Component->getLayerLength();
            $DO->width[$key] = $Component->getLayerWidth();
        }
        $view->assign('Data', $DO);

        $this->setView($view);

        return $modified;
    }
    // End saveComponentsAction


    /**
     * Fixes non-extant element components
     */
    protected function fixExtantsAction()
    {
        if (!is_numeric($this->Request->id))
            return false;

        $Element = ElcaElement::findById($this->Request->id);

        if (!$Element->isInitialized())
            return false;

        $previousExtantPosition = null;

        /**
         * Handle either composite elements or element components
         */
        if ($Element->isComposite()) {
            $Assignments = $Element->getCompositeElements();
            $positions = $Assignments->getArrayBy('elementId', 'position');

            /** @var ElcaCompositeElement $Assignment */
            foreach ($Assignments as $Assignment) {
                $Elt = $Assignment->getElement();
                if($Elt->isOpaque() || !$Elt->isExtant())
                    continue;

                $pos = $Assignment->getPosition();

                if (!is_null($previousExtantPosition) && ($pos - $previousExtantPosition > 1)) {
                    for ($x = 1; $x < ($pos - $previousExtantPosition); $x++) {
                        /** @var ElcaCompositeElement $FixElement */
                        if ($FixAssignment = $Assignments->search('elementId', $positions[$pos - $x])) {
                            $FixAssignment->getElement()->setIsExtant(true);
                        }
                    }
                }
                $previousExtantPosition = $pos;
            }

            $this->addCompositeView($Element->getId(), null, true);
        } else {

            $Layers = ElcaElementComponentSet::findLayers($Element->getId());
            $positions = $Layers->getArrayBy('id', 'layerPosition');
            $extantPositions = [];

            /** @var ElcaElementComponent $Layer */
            foreach ($Layers as $Layer) {
                if (!$Layer->isExtant())
                    continue;

                $extantPositions[$Layer->getId()] = $Layer->getLayerPosition();
            }

            $firstExtantPos = reset($extantPositions);
            $lastExtantPos  = end($extantPositions);

            /** @var ElcaElementComponent $Layer */
            foreach ($Layers as $Layer) {
                if (!$Layer->isExtant())
                    continue;

                $pos = $Layer->getLayerPosition();

                if ($pos > $firstExtantPos && $pos < $lastExtantPos &&
                    $Layer->hasLayerSibling() && !$Layer->getLayerSibling()->isExtant()) {
                    $FixSibling = $Layer->getLayerSibling();
                    $FixSibling->setIsExtant(true);
                    $FixSibling->update();
                }

                if (!is_null($previousExtantPosition) && ($pos - $previousExtantPosition > 1)) {
                    for ($x = 1; $x < ($pos - $previousExtantPosition); $x++) {
                        /** @var ElcaElementComponent $FixElement */
                        if ($FixElement = $Layers->search('id', $positions[$pos - $x])) {
                            $FixElement->setIsExtant(true);
                            $FixElement->update();

                            // check for sibling
                            if ($FixElement->hasLayerSibling()) {
                                $FixSibling = $FixElement->getLayerSibling();
                                $FixSibling->setIsExtant(true);
                                $FixSibling->update();
                            }
                        }
                    }
                }
                $previousExtantPosition = $pos;
            }

            $this->generalAction($this->Request->id);
        }

        return true;
    }
    // End fixExtantsAction


    /**
     * Deletes an element
     *
     * @param string $confirmMsg
     * @param bool   $addViews
     * @return boolean
     */
    protected function deleteAction($addViews = true)
    {
        if (!is_numeric($this->Request->id)) {
            return false;
        }

        /**
         * Check if user is allowed to edit element
         */
        $element = ElcaElement::findById($this->Request->id);
        if (!$this->Access->canEditElement($element)) {
            return false;
        }

        if ($this->Request->has('confirmed')) {
            $elementService = $this->container->get(ElementService::class);

            $elementTypeNodeId = $element->getElementTypeNodeId();

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

        $url = Url::parse($this->Request->getURI());
        $url->addParameter(['confirmed' => null]);

        if (!$addViews) {
            $url->addParameter(['composite' => null]);
        }

        if ($element->isComposite() && $this->Request->has('recursive')) {
            $confirmMsg = t('Soll die Bauteilvorlage und ihre Komponenten wirklich gelöscht werden?');
        } elseif ($element->isComposite()) {
            $confirmMsg = t('Soll die Bauteilvorlage wirklich gelöscht werden?');
        } else {
            $confirmMsg = t('Soll die Bauteilkomponentenvorlage wirklich gelöscht werden?');
        }

        $this->messages->add($confirmMsg, ElcaMessages::TYPE_CONFIRM, (string)$url);

        return false;
    }


    /**
     * Imports an element
     *
     * @return boolean
     */
    protected function importAction()
    {
        if(!$this->Request->isPost())
            return;

        $Validator = new ElcaValidator($this->Request);
        $Validator->assertTrue('importFile', File::uploadFileExists('importFile'), t('Bitte geben Sie eine Datei für den Import an!'));

        if(isset($_FILES['importFile']))
            $Validator->assertTrue('importFile', preg_match('/\.xml$/iu', (string)$_FILES['importFile']['name']), t('Bitte nur XML Dateien importieren.'));

        if($Validator->isValid())
        {
            $Config = Environment::getInstance()->getConfig();
            if(isset($Config->tmpDir))
            {
                $baseDir = $Config->toDir('baseDir');
                $tmpDir  = $baseDir . $Config->toDir('tmpDir', true);
            }
            else
                $tmpDir = '/tmp';

            $File = File::fromUpload('importFile', $tmpDir);

            $Importer = $this->container->get(Importer::class);
            $Dbh = $this->container->get(DbHandle::class);

            try
            {
                $Dbh->begin();
                if(!$Element = $Importer->importElement($File))
                    throw new Exception(t('Keine Bauteilvorlage in Importdatei vorhanden'));
                $Dbh->commit();

                $this->messages->add(t('Vorlage "%name%" wurde erfolgreich importiert', null, ['%name%' => $Element->getName()]));
                $this->defaultAction();
            }
            catch(Exception $Exception)
            {
                $Dbh->rollback();
                $this->messages->add($Exception->getMessage(), ElcaMessages::TYPE_ERROR);
            }
        }
        else
        {
            foreach($Validator->getErrors() as $property => $message)
                $this->messages->add($message, ElcaMessages::TYPE_ERROR);
        }
    }
    // End importAction



    /**
     * Deletes an element in composite context
     *
     * @return boolean
     */
    protected function deleteElementAction()
    {
        if(!$this->Request->id || !$this->Request->compositeElementId)
            return false;

        $compositeElement = ElcaElement::findById((int)$this->Request->compositeElementId);

        if (!$this->deleteAction(false)) {
            return false;
        }

        $compositeElement->reindexCompositeElements();

        $this->generalAction($compositeElement->getId());
        $this->imageCache->clear($compositeElement->getId());
        $this->addNavigationView($compositeElement->getElementTypeNodeId());

        return true;
    }
    // End deleteElementAction



    /**
     * Stores the toggle state
     *
     */
    protected function toggleComponentAction()
    {
        if(!$this->Request->componentId)
            return;

        $key = $this->Request->componentId;

        /**
         * save new toggle state
         */
        $toggleStates = is_array($this->Namespace->toggleStates)? $this->Namespace->toggleStates : [];

        if(isset($toggleStates[$key]))
            $toggleStates[$key] = !$toggleStates[$key];
        else
            $toggleStates[$key] = true;

        $this->Namespace->toggleStates = $toggleStates;

        /**
         * Build DataObject
         */
        $Component = ElcaElementComponent::findById($key);

        $DO = new \stdClass();
        $DO->toggleState[$key] = $toggleStates[$key];
        $DO->position[$key] = $Component->getLayerPosition();
        $DO->quantity[$key] = $Component->getQuantity();
        $DO->conversionId[$key] = $Component->getProcessConversionId();
        $DO->size[$key] = $Component->getLayerSize()? $Component->getLayerSize() * 1000 : null; // in mm
        $DO->areaRatio[$key] = $Component->getLayerAreaRatio();
        $DO->lifeTime[$key] = $Component->getLifeTime();
        $DO->lifeTimeDelay[$key] = $Component->getLifeTimeDelay();
        $DO->lifeTimeInfo[$key] = $Component->getLifeTimeInfo();
        $DO->calcLca[$key] = $Component->getCalcLca();
        $DO->isExtant[$key] = $Component->isExtant();
        $DO->processConfigId[$key] = $Component->getProcessConfigId();
        $DO->length[$key] = $Component->getLayerLength();
        $DO->width[$key] = $Component->getLayerWidth();

        /**
         * Build single life cycle row
         */
        $view = $this->setView(new ElcaElementComponentsView());
        $view->assign('context', $this->context);
        $view->assign('buildMode', $this->Request->b);
        $view->assign('elementId', $Component->getElementId());
        $view->assign('elementComponentId', $key);
        $view->assign('Data', $DO);

        $Element = $Component->getElement();
        $view->assign('readOnly', !$this->Access->canEditElement($Element));

        if ($assistant = $this->assistantRegistry
            ->getAssistantForElement($Element))
            $view->assign('assistant', $assistant);

        if ($projectId = $this->Elca->getProjectId()) {
            $view->assign(
                'lifeCycleUsages',
                $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                    new ProjectId($projectId)
                )
            );
        }

    }
    // End toggleComponentAction



    /**
     * Toggles the element info
     */
    protected function toggleElementAction()
    {
        if(!$this->Request->pos || !$this->Request->compositeElementId)
            return;

        $compositeElementId = $this->Request->compositeElementId;
        $key = $pos = $this->Request->pos;

        /**
         * save new toggle state
         */
        $states = is_array($this->Namespace->toggleStates)? $this->Namespace->toggleStates : [];

        if(!isset($states[$compositeElementId]))
            $states[$compositeElementId] = [];

        if(isset($states[$compositeElementId][$key]))
            $states[$compositeElementId][$key] = !$states[$compositeElementId][$key];
        else
            $states[$compositeElementId][$key] = true;

        $this->Namespace->toggleStates = $states;
        $this->addCompositeView($compositeElementId, $pos);
    }
    // End toggleElementAction



    /**
     * Saves a layer component
     *
     * @param ElcaElement $Element
     * @param             $key
     * @return bool -
     */
    protected function saveLayer(ElcaElement $Element, $key)
    {
        if(!isset($this->Request->processConfigId[$key]))
            return false;

        $size = ElcaNumberFormat::fromString($this->Request->size[$key], 2) / 1000; // in m
        $isExtant = isset($this->Request->isExtant[$key]);

        $lifeTime = ElcaNumberFormat::fromString($this->Request->lifeTime[$key], 0);
        $lifeTimeDelay = $isExtant? ElcaNumberFormat::fromString($this->Request->lifeTimeDelay[$key], 0) : 0;

        if (isset($this->Request->lifeTimeInfo[$key])) {
            $lifeTimeInfo = \trim($this->Request->lifeTimeInfo[$key]) ?: null;
        } else {
            $lifeTimeInfo = null;
        }
        $calcLca = isset($this->Request->calcLca[$key]);
        $areaRatio = isset($this->Request->areaRatio[$key])? ElcaNumberFormat::fromString($this->Request->areaRatio[$key], 3, true) : 1;
        $processConfigId = $this->Request->processConfigId[$key];
        $ConversionSet = ElcaProcessConversionSet::findByProcessConfigIdAndInUnit($processConfigId, 'm3', ['id' => 'ASC'], 1);
        $processConversionId = $ConversionSet[0]->getId();
        $length = ElcaNumberFormat::fromString($this->Request->length[$key], 2);
        $width = ElcaNumberFormat::fromString($this->Request->width[$key], 2);

        $modified = false;

        if(is_numeric($key))
        {
            $Component = ElcaElementComponent::findById($key);

            if($Component->getProcessConfigId() != $processConfigId)
            {
                $Component->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if($Component->getProcessConversionId() != $processConversionId)
            {
                $Component->setProcessConversionId($processConversionId);
                $modified = true;
            }
            if($Component->getLayerSize() != $size)
            {
                $Component->setLayerSize($size);
                $modified = true;
            }
            if($Component->getLifeTime() != $lifeTime)
            {
                $Component->setLifeTime($lifeTime);
                $modified = true;
            }
            if($Component->getLifeTimeDelay() != $lifeTimeDelay || $Component->isExtant() != $isExtant)
            {
                $Component->setLifeTimeDelay($isExtant? $lifeTimeDelay : 0);
                $modified = true;
            }

            if ($Component->getLifeTimeInfo() != $lifeTimeInfo) {
                $Component->setLifeTimeInfo($lifeTimeInfo);
                $modified = true;
            }

            if($Component->getCalcLca() != $calcLca)
            {
                $Component->setCalcLca($calcLca);
                $modified = true;
            }
            if($Component->isExtant() != $isExtant)
            {
                $Component->setIsExtant($isExtant);
                $modified = true;
            }
            if($Component->getLayerAreaRatio() != $areaRatio)
            {
                $Component->setLayerAreaRatio($areaRatio);
                $modified = true;
            }

            if($Component->getLayerLength() != $length)
            {
                $Component->setLayerLength($length);
                $modified = true;
            }

            if($Component->getLayerWidth() != $width)
            {
                $Component->setLayerWidth($width);
                $modified = true;
            }

            if($modified)
                $Component->update();
        }
        else
        {
            ElcaElementComponent::create($Element->getId(), $processConfigId, $processConversionId,
                                         $lifeTime, true, 1, $calcLca, $isExtant,
                                         (ElcaElementComponent::getMaxLayerPosition($Element->getId()) + 1), $size, null, $areaRatio, $length,
                                         $width, $isExtant? $lifeTimeDelay : 0, $lifeTimeInfo
                                        );
            $modified = true;
        }

        return $modified;
    }
    // End saveLayer



    /**
     * Saves a single component
     *
     * @param ElcaElement $Element
     * @param             $key
     * @return bool -
     */
    protected function saveComponent(ElcaElement $Element, $key)
    {
        if(!isset($this->Request->processConfigId[$key]))
            return false;

        $processConfigId = $this->Request->processConfigId[$key];
        $quantity = ElcaNumberFormat::fromString($this->Request->quantity[$key], 4);
        $isExtant = isset($this->Request->isExtant[$key]);

        $lifeTime = ElcaNumberFormat::fromString($this->Request->lifeTime[$key], 0);
        $lifeTimeDelay = $isExtant? ElcaNumberFormat::fromString($this->Request->lifeTimeDelay[$key], 0) : 0;

        if (isset($this->Request->lifeTimeInfo[$key])) {
            $lifeTimeInfo = \trim($this->Request->lifeTimeInfo[$key]) ?: null;
        } else {
            $lifeTimeInfo = null;
        }

        $calcLca = isset($this->Request->calcLca[$key]);
        $conversionId = $this->Request->conversionId[$key];
        $modified = false;

        if(is_numeric($key))
        {
            $Component = ElcaElementComponent::findById($key);

            if($Component->getProcessConfigId() != $processConfigId)
            {
                $Component->setProcessConfigId($processConfigId);
                $modified = true;
            }
            if($Component->getProcessConversionId() != $conversionId)
            {
                $Component->setProcessConversionId($conversionId);
                $modified = true;
            }
            if($Component->getQuantity() != $quantity)
            {
                $Component->setQuantity($quantity);
                $modified = true;
            }
            if($Component->getLifeTime() != $lifeTime)
            {
                $Component->setLifeTime($lifeTime);
                $modified = true;
            }
            if($Component->getLifeTimeDelay() != $lifeTimeDelay || $Component->isExtant() != $isExtant)
            {
                $Component->setLifeTimeDelay($isExtant? $lifeTimeDelay : 0);
                $modified = true;
            }
            if ($Component->getLifeTimeInfo() != $lifeTimeInfo) {
                $Component->setLifeTimeInfo($lifeTimeInfo);
                $modified = true;
            }
            if($Component->getCalcLca() != $calcLca)
            {
                $Component->setCalcLca($calcLca);
                $modified = true;
            }
            if($Component->isExtant() != $isExtant)
            {
                $Component->setIsExtant($isExtant);
                $modified = true;
            }
            if($modified)
                $Component->update();
        }
        else
        {
            ElcaElementComponent::create($Element->getId(), $processConfigId, $conversionId, $lifeTime,
                                         false, $quantity, $calcLca, $isExtant, null, null, null, 1, 1, 1, $isExtant? $lifeTimeDelay : 0, $lifeTimeInfo);
            $modified = true;
        }

        return $modified;
    }
    // End saveComponent


    /**
     * Save element attributes
     *
     * @param  ElcaElement $Element
     * @param array        $attributes
     * @return boolean
     */
    protected function saveElementAttributes(ElcaElement $Element, array $attributes)
    {
        if(!$Element->isInitialized() || empty($attributes))
            return;

        $elementId = $Element->getId();

        foreach((Elca::$elementAttributes + Elca::$elementBnbAttributes) as $ident => $caption)
        {
            $value = $numValue = $txtValue = null;

            if(!isset($attributes[$ident]))
                continue;

            $value = \trim($attributes[$ident]);
            if($ident != Elca::ELEMENT_ATTR_OZ && preg_match('/^\d+[,.]?\d{0,}$/', $value))
                $numValue = ElcaNumberFormat::fromString($value);
            else
                $txtValue = $value;

            $Attr = ElcaElementAttribute::findByElementIdAndIdent($elementId, $ident);
            if($Attr->isInitialized())
            {
                $Attr->setNumericValue($numValue);
                $Attr->setTextValue($txtValue);
                $Attr->update();
            }
            else
                ElcaElementAttribute::create($elementId, $ident, $caption, $numValue, $txtValue);
        }


    }
    // End saveElementAttributes


    /**
     * Assigns an element to a composite element
     *
     * @param  ElcaElement $compositeElement - composite element
     * @param  ElcaElement $element          - element to assign
     * @param null         $position
     * @throws Exception
     * @return boolean
     */
    protected function addToCompositeElement(ElcaElement $compositeElement, ElcaElement $element, $position = null)
    {
        return $this->get(ElementService::class)->addToCompositeElement($compositeElement, $element, $position);
    }
    // End addToCompositeElement


    /**
     * Unassigns or deletes a element from a composite element
     *
     * @param  ElcaElement $compositeElement - composite element
     * @param  int         $position
     * @param  ElcaElement $element          - element to unassign or delete
     * @param  boolean     $deleteElement    - if true, the element will be deleted instead of unassigned
     * @throws Exception
     * @return boolean
     */
    protected function removeFromCompositeElement(ElcaElement $compositeElement, $position, ElcaElement $element, $deleteElement = false)
    {
        return $this->container->get(ElementService::class)->removeFromCompositeElement($compositeElement, $position, $element, $deleteElement);
    }
    // End removeFromCompositeElement



    /**
     * Render navigation
     */
    protected function addNavigationView($activeElementTypeId = null)
    {
        /**
         * Add left navigation
         */
        if(!$this->hasViewByName(ElcaElementsNavigationView::class))
        {
            $view = $this->addView(new ElcaElementsNavigationView());
            $view->assign('context', $this->context);
            $view->assign('activeElementTypeId', $activeElementTypeId);
            $view->assign('controller', get_class($this));
        }
    }


    /**
     * Render composite view
     *
     * @param int  $compositeElementId
     * @param null $position
     * @param bool $addSummaryView
     * @return \View|\Viewable
     */
    protected function addCompositeView($compositeElementId, $position = null, $addSummaryView = false)
    {
        if(!$compositeElementId || $this->hasViewByName('Elca\View\ElcaElementCompositeView'))
            return null;

        $CompositeElement = ElcaElement::findById($compositeElementId);

        if(!$CompositeElement->isComposite())
            return null;

        /**
         * Build view data
         */
        $CompositeElements = $CompositeElement->getCompositeElements(null, true);
        $DO = new \stdClass();
        /** @var ElcaCompositeElement $CompositeElt */
        foreach($CompositeElements as $CompositeElt)
        {
            $Elt = $CompositeElt->getElement();
            $key = $CompositeElt->getPosition();
            $Type = $Elt->getElementTypeNode();
            $DO->toggleState[$key] = isset($this->Namespace->toggleStates[$compositeElementId][$key])? $this->Namespace->toggleStates[$compositeElementId][$key] : false;
            $DO->elementId[$key] = $Elt->getId();
            $DO->quantity[$key] = $Elt->getQuantity();
            $DO->refUnit[$key] = ElcaNumberFormat::formatUnit($Elt->getRefUnit());
            $DO->dinCode[$key] = $Type->getDinCode();
            $DO->elementType[$key] = $Type->getName();
            $DO->position[$key] = $key;
            $DO->isOpaque[$key] = $Type->isOpaque();

            if (is_bool($isExtant = $Elt->isExtant()))
                $DO->isExtant[$key] = $isExtant;
        }

        /**
         * Set view
         */
        $view = $this->setView(new ElcaElementCompositeView());
        $view->assign('readOnly', !$this->Access->canEditElement($CompositeElement));
        $view->assign('context', $this->context);
        $view->assign('compositeElementId', $compositeElementId);
        $view->assign('activeTabIdent', $this->getActiveTabIdent());

        if($position)
            $view->assign('position', $position);

        $view->assign('Data', $DO);

        if ($projectId = $this->Elca->getProjectId()) {
            $lifeCycleUsages = $this->lifeCycleUsageService->findLifeCycleUsagesForProject(
                new ProjectId($projectId)
            );
            $view->assign('lifeCycleUsages', $lifeCycleUsages);
        }
        /**
         * Add element summary view
         */
        if($addSummaryView)
        {
            $sumView = $this->addView(new ElcaElementView(), 'ElementSummary');
            $sumView->assign('buildMode', ElcaElementView::BUILDMODE_SUMMARY);
            $sumView->assign('context', $this->context);
            $sumView->assign('elementId', $compositeElementId);

            if ($lifeCycleUsages) {
                $sumView->assign('lifeCycleUsages', $lifeCycleUsages);
            }
        }

        if ($assistant = $this->assistantRegistry
            ->getAssistantForElement($CompositeElement))
            $view->assign('assistant', $assistant);

        return $view;
    }
    // End addCompositeView



    /**
     * Returns the context
     */
    protected function getContext()
    {
        return $this->context;
    }
    // End getContext


    /**
     * Returns a filter data object form request or session
     *
     * @param  string $key
     * @param array   $defaults
     * @return object
     */
    protected function getFilterDO($key, array $defaults = [])
    {
        if(!$filterDOs = $this->Namespace->filterDOs)
            $filterDOs = [];

        $filterDO = $filterDOs[$key] ?? new \stdClass();

        foreach($defaults as $name => $defaultValue)
            $filterDO->$name = $this->Request->has($name)? $this->Request->get($name) : ($filterDO->$name ?? $defaultValue);

        $filterDOs[$key] = $filterDO;

        $this->Namespace->filterDOs = $filterDOs;

        return $filterDO;
    }
    // End getFilterDO



    /**
     * Stores a filter data object in session
     *
     * @param  string $key
     * @param  object $FilterDO
     * @return object
     */
    protected function setFilterDO($key, $FilterDO)
    {
        if(!$filterDOs = $this->Namespace->filterDOs)
            $this->Namespace->filterDOs = $filterDOs = [];

        $this->Namespace->filterDOs = $filterDOs[$key] = $FilterDO;

        return $FilterDO;
    }
    // End setFilterDO

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

    private function highlightInvalidExtantComponents(array $positions, array $needExtantFix): array
    {
        $highlightElts = [];
        $keyPositions  = array_flip($positions);

        foreach ($needExtantFix as $pos) {
            $layer = ElcaElementComponent::findById($keyPositions[$pos]);
            if (!$layer->isInitialized()) {
                continue;
            }

            $highlightElts['isExtant[' . $layer->getId() . ']'] = true;
            if ($layer->hasLayerSibling()) {
                $highlightElts['isExtant[' . $layer->getLayerSiblingId() . ']'] = true;
            }
        }

        return $highlightElts;
    }

    private function highlightInvalidLifeTimeComponents(array $needLifeTimeFix): array
    {
        $highlightElts = [];

        foreach ($needLifeTimeFix as $key) {
            $layer = ElcaElementComponent::findById($key);
            if (!$layer->isInitialized()) {
                continue;
            }

            $highlightElts['lifeTime[' . $key . ']'] = true;
            if ($layer->hasLayerSibling()) {
                $highlightElts['lifeTime[' . $layer->getLayerSiblingId() . ']'] = true;
            }
        }

        return $highlightElts;
    }

}
// End ElementsCtrl
