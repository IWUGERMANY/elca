<?php

namespace Elca\Tests\Integration\Assistant;

use Elca\Db\ElcaAssistantElement;
use Elca\Db\ElcaAssistantSubElement;
use Elca\Db\ElcaCompositeElement;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProjectVariant;
use Elca\Model\Assistant\Stairs\Assembler;
use Elca\Model\Assistant\Stairs\SolidStaircase;
use Elca\Service\Assistant\Stairs\StaircaseAssistant;
use Elca\Service\ElcaLocale;
use Elca\Service\ElcaTranslator;
use Elca\Service\Element\ElementService;
use Elca\Service\Messages\ElcaMessages;
use Elca\Service\Project\ProjectElementService;
use Elca\Service\Project\ProjectVariant\ProjectVariantService;

class StaircaseAssistantTest extends AbstractAssistantTest
{

    /**
     * @var StaircaseAssistant
     */
    private $staircaseAssistant;

    /**
     * @var ElementService
     */
    private $elementService;

    /**
     * @var ProjectElementService
     */
    private $projectElementService;

    /**
     * @var ProjectVariantService
     */
    private $projectVariantService;

    /**
     * @var ElcaMessages|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messages;


    public function test_copyMainElement_copiesAssistantElement()
    {
        // GIVEN
        $user             = $this->givenUser();
        $projectVariant   = $this->givenProjectVariant($user);
        $staircaseElement = $this->createStaircaseAssistantElement($projectVariant);

        $this->assertElementCount($projectVariant, 3);

        // WHEN
        $copiedMainElement = $this->projectElementService->copyElementFrom(
            $staircaseElement,
            $user->getId(),
            $projectVariant->getId(),
            $user->getGroupId(),
            false,
            false
        );

        // THEN
        $this->assertElementCount($projectVariant, 6);

        $copiedAssistantElement = ElcaAssistantElement::findByElementId($copiedMainElement->getId());
        $this->assertTrue($copiedAssistantElement->isInitialized());

        $this->assertAssistantSubElement($copiedAssistantElement, Assembler::IDENT_CONSTRUCTION);
        $this->assertAssistantSubElement($copiedAssistantElement, Assembler::IDENT_COVER_RISER);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->translator = new ElcaTranslator(new ElcaLocale());

        $this->messages = $this->createMock(ElcaMessages::class);

        $this->elementService = new ElementService([], $this->dbh);

        $this->staircaseAssistant = new StaircaseAssistant($this->lcaProcessor, $this->messages, $this->elementService,
            $this->logger);
        $this->elementService->addElementObserver($this->staircaseAssistant);

        $this->projectElementService = new ProjectElementService([$this->staircaseAssistant], $this->dbh,
            $this->elementService,
            $this->lcaProcessor);

        $this->projectVariantService = new ProjectVariantService([$this->staircaseAssistant], $this->dbh,
            $this->lcaProcessor,
            $this->elementService);
    }

    protected function assertAssistantSubElement(ElcaAssistantElement $copiedAssistantElement, $ident): void
    {
        $copiedAssistantSubElement = ElcaAssistantSubElement::findByAssistantElementIdAndIdent($copiedAssistantElement->getId(),
            $ident);
        $this->assertTrue($copiedAssistantSubElement->isInitialized());

        $copiedSubElement = ElcaAssistantSubElement::findByPk($copiedAssistantElement->getId(),
            $copiedAssistantSubElement->getElementId());
        $this->assertTrue($copiedSubElement->isInitialized());
    }

    private function createStaircaseAssistantElement(ElcaProjectVariant $projectVariant): ElcaElement
    {
        $elementType = ElcaElementType::findByIdent('350');
        $staircaseElement = ElcaElement::create(
            $elementType->getNodeId(),
            'TestStaircase',
            null,
            false, null, $projectVariant->getId()
        );

        $staircase        = SolidStaircase::getDefault();
        $assistantElement = ElcaAssistantElement::create($staircaseElement->getId(), StaircaseAssistant::IDENT,
            $projectVariant->getId(), \base64_encode(\serialize($staircase)));
        ElcaAssistantSubElement::create($assistantElement->getId(), $staircaseElement->getId(), Assembler::IDENT_STAIRCASE);

        $elementType = ElcaElementType::findByIdent('351');
        $staircaseSubElement1 = ElcaElement::create(
            $elementType->getNodeId(),
            'TestStaircase / Construktion',
            null,
            false, null, $projectVariant->getId()
        );
        ElcaCompositeElement::create($staircaseElement->getId(), 1, $staircaseSubElement1->getId());
        ElcaAssistantSubElement::create($assistantElement->getId(), $staircaseSubElement1->getId(), Assembler::IDENT_CONSTRUCTION);

        $elementType = ElcaElementType::findByIdent('351');
        $staircaseSubElement2 = ElcaElement::create(
            $elementType->getNodeId(),
            'TestStaircase / Cover Riser',
            null,
            false, null, $projectVariant->getId()
        );
        ElcaCompositeElement::create($staircaseElement->getId(), 2, $staircaseSubElement2->getId());
        ElcaAssistantSubElement::create($assistantElement->getId(), $staircaseSubElement2->getId(), Assembler::IDENT_COVER_RISER);

        return $staircaseElement;
    }
}
