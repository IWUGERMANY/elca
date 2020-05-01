<?php

namespace Elca\Tests\Db;

use Beibob\Blibs\DbHandle;
use Beibob\Blibs\DbObject;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementAttribute;
use Elca\Db\ElcaElementType;
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

    public function test_findByAttributeIdentAndTextValue()
    {
        // GIVEN
        $elementType = ElcaElementType::findByIdent('330');
        $element     = ElcaElement::create(
            $elementType->getNodeId(),
            'TestElement',
            null
        );

        $elementAttribute = ElcaElementAttribute::create(
            $element->getId(),
            self::TEST_IDENT,
            'IfcGuid',
            null,
            self::TEST_GUID
        );

        // WHEN
        $foundElement = ElcaElement::findByAttributeIdentAndTextValue(self::TEST_IDENT, self::TEST_GUID);

        // THEN
        $this->assertEquals($element->getId(), $foundElement->getId());
        $foundAttribute = $foundElement->getAttribute(self::TEST_IDENT);
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
