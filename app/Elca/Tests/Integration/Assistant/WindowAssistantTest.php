<?php

namespace Elca\Tests\Integration\Assistant;

use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaAssistantElementSet;
use Elca\Db\ElcaAssistantSubElement;
use Elca\Db\ElcaAssistantSubElementSet;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Assistant\Window\Assembler;
use Elca\Model\Assistant\Window\Sill;
use Elca\Model\Assistant\Window\Window;
use Elca\Model\Common\Geometry\Rectangle;
use Elca\Service\Assistant\Window\WindowAssistant;
use Elca\Service\Element\ElementService;
use Elca\Service\Project\ProjectElementService;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;

class WindowAssistantTest extends AbstractAssistantTest
{
    /**
     * @var ProjectVariantService
     */
    private $projectVariantService;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @var ProjectElementService
     */
    private $projectElementService;

    /**
     * @var WindowAssistant
     */
    private $windowAssistant;

    public function test_provideForElement_migratesWindowIntoNewModel()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createLegacyWindowAssistantElement($projectVariant);

        $this->assertTrue(ElcaElementAttribute::findByElementIdAndIdent($windowElement->getId(),
            WindowAssistant::IDENT)->isInitialized());

        // WHEN
        $this->windowAssistant->provideForElement($windowElement);

        // THEN
        $assistantElement = ElcaAssistantElement::findByElementId($windowElement->getId());
        $this->assertTrue($assistantElement->isInitialized());

        $assistantSubElement = ElcaAssistantSubElement::findByPk($assistantElement->getId(), $windowElement->getId());
        $this->assertTrue($assistantSubElement->isInitialized());

        $this->assertFalse(ElcaElementAttribute::findByElementIdAndIdent($windowElement->getId(),
            WindowAssistant::IDENT)->isInitialized());
    }

    public function test_copyMainElement_copiesAssistantElement()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createWindowAssistantElementWithAdditionalSubElement($projectVariant->getId());

        $this->assertElementCount($projectVariant, 2);

        // WHEN
        $copiedWindowElement = $this->projectElementService->copyElementFrom(
            $windowElement,
            $user->getId(),
            $projectVariant->getId(),
            $user->getGroupId(),
            false,
            false
        );

        // THEN
        $this->assertElementCount($projectVariant, 4);

        $copiedAssistantElement = ElcaAssistantElement::findByElementId($copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantElement->isInitialized());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByPk($copiedAssistantElement->getId(),
            $copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantSubElement->isInitialized());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByAssistantElementIdAndIdent($copiedAssistantElement->getId(),
            Assembler::IDENT_OUTDOOR_SILL);
        $this->assertTrue($copiedAssistantSubElement->isInitialized());
    }

    public function test_copyCompositeElementWithAssignedAssistantElement_copiesAssistantElement()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createWindowAssistantElementWithAdditionalSubElement($projectVariant->getId());

        $compositeElement = ElcaElement::create(ElcaElementType::findByIdent('330')->getNodeId(), 'CompositeElement',
            null, false, null, $projectVariant->getId());
        ElcaCompositeElement::create($compositeElement->getId(), 1, $windowElement->getId());

        $this->assertElementCount($projectVariant, 3);

        // WHEN
        $copiedCompositeElement = $this->projectElementService->copyElementFrom(
            $compositeElement,
            $user->getId(),
            $projectVariant->getId(),
            $user->getGroupId(),
            false,
            false
        );

        // THEN
        $this->assertElementCount($projectVariant, 6);

        $copiedCompositeWindowElement = $copiedCompositeElement->getCompositeElements()->current();
        $this->assertNotNull($copiedCompositeWindowElement);
        $this->assertNotFalse($copiedCompositeWindowElement);

        $copiedWindowElement = $copiedCompositeWindowElement->getElement();

        $copiedAssistantElement = ElcaAssistantElement::findByElementId($copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantElement->isInitialized());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByPk($copiedAssistantElement->getId(),
            $copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantSubElement->isInitialized());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByAssistantElementIdAndIdent($copiedAssistantElement->getId(),
            Assembler::IDENT_OUTDOOR_SILL);
        $this->assertTrue($copiedAssistantSubElement->isInitialized());
    }

    public function test_deleteCompositeElementWithAssignedAssistantElement_deleteAssistantElement()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createWindowAssistantElementWithAdditionalSubElement($projectVariant->getId());

        $compositeElement = ElcaElement::create(ElcaElementType::findByIdent('330')->getNodeId(), 'CompositeElement',
            null, false, null, $projectVariant->getId());
        ElcaCompositeElement::create($compositeElement->getId(), 1, $windowElement->getId());

        $this->assertElementCount($projectVariant, 3);

        // WHEN
        $copiedCompositeElement = $this->projectElementService->deleteElement($compositeElement, true);

        // THEN
        $this->assertElementCount($projectVariant, 0);
    }

    public function test_copyProjectVariant_copiesAssistantElement()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createWindowAssistantElementWithAdditionalSubElement($projectVariant->getId());

        // WHEN
        $copiedProjectVariant = $this->projectVariantService->copy($projectVariant, $projectVariant->getProjectId(),
            null);

        // THEN
        $copiedAssistantElement = ElcaAssistantElementSet::find(['project_variant_id' => $copiedProjectVariant->getId()])
                                                         ->current();
        $this->assertNotNull($copiedAssistantElement);
        $this->assertNotFalse($copiedAssistantElement);
        $this->assertTrue($copiedAssistantElement->isInitialized());

        $copiedAssistantSubElementSet = ElcaAssistantSubElementSet::find(['assistant_element_id' => $copiedAssistantElement->getId()]);

        $this->assertNotNull($copiedAssistantSubElementSet);
        $this->assertEquals(2, $copiedAssistantSubElementSet->count());

        $copiedAssistantSubElement = $this->findAssistantSubElementByIdent($copiedAssistantSubElementSet, Assembler::IDENT_WINDOW);
        $this->assertNotNull($copiedAssistantSubElement);
        $this->assertTrue($copiedAssistantSubElement->isInitialized());

        $this->assertNotEquals($windowElement->getId(), $copiedAssistantElement->getMainElementId());
        $this->assertNotEquals($windowElement->getId(), $copiedAssistantSubElement->getElementId());

        $copiedAssistantSubElement = $this->findAssistantSubElementByIdent($copiedAssistantSubElementSet, Assembler::IDENT_OUTDOOR_SILL);
        $this->assertNotNull($copiedAssistantSubElement);
        $this->assertTrue($copiedAssistantSubElement->isInitialized());

        // also assert old assistant sub elements still exist
        $originalAssistantElement = ElcaAssistantElementSet::find(['project_variant_id' => $projectVariant->getId()])
                                                         ->current();

        $this->assertNotNull($originalAssistantElement);
        $this->assertNotFalse($originalAssistantElement);
        $this->assertTrue($originalAssistantElement->isInitialized());

        $originalAssistantSubElementSet = ElcaAssistantSubElementSet::find(['assistant_element_id' => $originalAssistantElement->getId()]);

        $this->assertNotNull($originalAssistantSubElementSet);
        $this->assertEquals(2, $originalAssistantSubElementSet->count());

        $originalAssistantSubElement = $this->findAssistantSubElementByIdent($originalAssistantSubElementSet, Assembler::IDENT_WINDOW);
        $this->assertNotNull($originalAssistantSubElement);
        $this->assertTrue($originalAssistantSubElement->isInitialized());

        $this->assertEquals($windowElement->getId(), $originalAssistantElement->getMainElementId());
        $this->assertEquals($windowElement->getId(), $originalAssistantSubElement->getElementId());

        $originalAssistantSubElement = $this->findAssistantSubElementByIdent($originalAssistantSubElementSet, Assembler::IDENT_OUTDOOR_SILL);
        $this->assertNotNull($originalAssistantSubElement);
        $this->assertTrue($originalAssistantSubElement->isInitialized());
    }

    public function test_createTemplateFromElement_copiesAssistantElementWithoutProjectVariantId()
    {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowElement  = $this->createWindowAssistantElement($projectVariant);

        // WHEN
        $copiedWindowElement = $this->projectElementService->copyElementFrom(
            $windowElement,
            $user->getId(),
            null,
            $user->getGroupId(),
            true,
            false
        );

        // THEN
        $copiedAssistantElement = ElcaAssistantElement::findByElementId($copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantElement->isInitialized());
        $this->assertEquals($copiedWindowElement->getId(), $copiedAssistantElement->getMainElementId());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByPk($copiedAssistantElement->getId(),
            $copiedWindowElement->getId());
        $this->assertTrue($copiedAssistantSubElement->isInitialized());
        $this->assertEquals($copiedWindowElement->getId(), $copiedAssistantSubElement->getElementId());


        $this->assertNull($copiedWindowElement->getProjectVariantId());
        $this->assertNull($copiedAssistantElement->getProjectVariantId());
    }

    public function test_createFromTemplateAssistantElement() {
        // GIVEN
        $user           = $this->givenUser();
        $projectVariant = $this->givenProjectVariant($user);
        $windowTemplateElement  = $this->createWindowAssistantElementWithAdditionalSubElement(null);

        // WHEN
        $newfromTemplateElement = $this->projectElementService->copyElementFrom(
            $windowTemplateElement,
            $user->getId(),
            $projectVariant->getId(),
            $user->getGroupId(),
            true,
            false
        );

        // THEN
        $copiedAssistantElement = ElcaAssistantElement::findByElementId($newfromTemplateElement->getId());
        $this->assertTrue($copiedAssistantElement->isInitialized());
        $this->assertEquals($newfromTemplateElement->getId(), $copiedAssistantElement->getMainElementId());

        $copiedAssistantSubElement = ElcaAssistantSubElement::findByPk($copiedAssistantElement->getId(),
            $newfromTemplateElement->getId());
        $this->assertTrue($copiedAssistantSubElement->isInitialized());
        $this->assertEquals($newfromTemplateElement->getId(), $copiedAssistantSubElement->getElementId());

        $this->assertEquals($projectVariant->getId(), $newfromTemplateElement->getProjectVariantId());
        $this->assertEquals($projectVariant->getId(), $copiedAssistantElement->getProjectVariantId());
    }


    protected function setUp()
    {
        parent::setUp();

        $this->windowAssistant = new WindowAssistant($this->lcaProcessor, $this->logger);

        $observers                   = [$this->windowAssistant];
        $this->elementService        = new ElementService($observers, $this->dbh);
        $this->projectElementService = new ProjectElementService($observers, $this->dbh, $this->elementService,
            $this->lcaProcessor);

        $this->projectVariantService = new ProjectVariantService($observers, $this->dbh, $this->lcaProcessor,
            $this->elementService);
    }


    protected function createWindowAssistantElement(ElcaProjectVariant $projectVariant): ElcaElement
    {
        $elementType   = ElcaElementType::findByIdent('334');
        $windowElement = ElcaElement::create(
            $elementType->getNodeId(),
            'TestWindow',
            null,
            false, null, $projectVariant->getId()
        );

        $window           = Window::getDefault();
        $assistantElement = ElcaAssistantElement::create($windowElement->getId(), WindowAssistant::IDENT,
            $projectVariant->getId(), \base64_encode(\serialize($window)));
        ElcaAssistantSubElement::create($assistantElement->getId(), $windowElement->getId(), Assembler::IDENT_WINDOW);

        return $windowElement;
    }

    protected function createWindowAssistantElementWithAdditionalSubElement($projectVariantId = null): ElcaElement
    {
        $elementType   = ElcaElementType::findByIdent('334');
        $windowElement = ElcaElement::create(
            $elementType->getNodeId(),
            'TestWindow',
            null,
            false, null, $projectVariantId
        );

        $windowSubElement = ElcaElement::create(
            ElcaElementType::findByIdent('336')->getNodeId(),
            'TestWindow [SubElement]',
            null,
            false, null, $projectVariantId
        );

        $window           = Window::getDefault();
        $window->setOutdoorSill(new Sill(new Rectangle(10, 10), 1));

        $assistantElement = ElcaAssistantElement::create($windowElement->getId(), WindowAssistant::IDENT,
            $projectVariantId, \base64_encode(\serialize($window)));
        ElcaAssistantSubElement::create($assistantElement->getId(), $windowElement->getId(), Assembler::IDENT_WINDOW);
        ElcaAssistantSubElement::create($assistantElement->getId(), $windowSubElement->getId(), Assembler::IDENT_OUTDOOR_SILL);

        return $windowElement;
    }

    protected function createLegacyWindowAssistantElement(ElcaProjectVariant $projectVariant): ElcaElement
    {
        $elementType   = ElcaElementType::findByIdent('334');
        $windowElement = ElcaElement::create(
            $elementType->getNodeId(),
            'TestWindow',
            null,
            false, null, $projectVariant->getId()
        );

        $window = Window::getDefault();

        $elementAttribute = ElcaElementAttribute::create(
            $windowElement->getId(),
            WindowAssistant::IDENT,
            'Window',
            null,
            \base64_encode(\serialize($window))
        );

        return $windowElement;
    }

    protected function findAssistantSubElementByIdent(ElcaAssistantSubElementSet $copiedAssistantSubElementSet,
        string $ident): ?ElcaAssistantSubElement
    {
        $foundSubElement = $copiedAssistantSubElementSet->filter(function (ElcaAssistantSubElement $subElement) use ($ident) {
            return $ident === $subElement->getIdent();
        })->current();

        if (!$foundSubElement) {
            return null;
        }

        return $foundSubElement;
    }
}
