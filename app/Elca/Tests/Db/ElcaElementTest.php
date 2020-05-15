<?php

namespace Elca\Tests\Db;

use Beibob\Blibs\Auth;
use Beibob\Blibs\DbHandle;
use Beibob\Blibs\DbObject;
use Beibob\Blibs\IdFactory;
use Beibob\Blibs\User;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementType;
use Elca\Db\ElcaProcessDb;
use Elca\Db\ElcaProject;
use Elca\Db\ElcaProjectPhase;
use Elca\Db\ElcaProjectVariant;
use Elca\Elca;
use PHPUnit\Framework\TestCase;

class ElcaElementTest extends TestCase
{

    const TEST_GUID = '1234';
    const TEST_IDENT = 'ifcGuid';

    /**
     * @var array|DbObject[]
     */
    private $dbObjectsToRemove = [];

    /**
     * @var DbHandle
     */
    private $dbh;

    public function test_findForIfcGuid()
    {
        // GIVEN
        $auth = new Auth(Auth::METHOD_MD5);
        $auth->authName = IdFactory::getUniqueId();
        $auth->authKey = IdFactory::getUniqueId();
        $user = User::create($auth, User::STATUS_CONFIRMED);

        $project = ElcaProject::create(ElcaProcessDb::findMostRecentVersion(true)->getId(), $user->getId(), $user->getGroupId(), "TestProject", 50);
        $projectVariant = ElcaProjectVariant::create($project->getId(), ElcaProjectPhase::findMinIdByConstrMeasure(Elca::CONSTR_MEASURE_PRIVATE)->getId(), "Variant1");

        $elementType = ElcaElementType::findByIdent('330');
        $element     = ElcaElement::create(
            $elementType->getNodeId(),
            'TestElement',
            null,
            false, null, $projectVariant->getId()
        );

        $elementAttribute = ElcaElementAttribute::create(
            $element->getId(),
            Elca::ELEMENT_ATTR_IFCGUID,
            'IfcGuid',
            null,
            self::TEST_GUID
        );

        // WHEN
        $foundElement = ElcaElement::findForIfcGuid(self::TEST_GUID, $projectVariant->getId());

        // THEN
        $this->assertEquals($element->getId(), $foundElement->getId());
        $foundAttribute = $foundElement->getAttribute(Elca::ELEMENT_ATTR_IFCGUID);
        $this->assertNotNull($foundAttribute);
        $this->assertEquals($elementAttribute->getId(), $foundAttribute->getId());
    }

    protected function setUp()
    {
        $this->dbh = DbHandle::getInstance();
        $this->dbh->begin();
    }

    protected function tearDown()
    {
        $this->dbh->rollback();
    }
}
