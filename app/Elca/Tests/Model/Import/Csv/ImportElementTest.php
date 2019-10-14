<?php

namespace Elca\Tests\Model\Import\Csv;

use Beibob\Blibs\Environment;
use Elca\Db\ElcaElement;
use Elca\Db\ElcaElementType;
use Elca\Model\Common\Unit;
use Elca\Model\Import\Csv\ImportElement;
use Elca\Service\ElcaLocale;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ImportElementTest extends TestCase
{
    const EMPTY = '';

    public function test_fromCsv_nameWillBeTrimmed()
    {
        $importElement = ImportElement::fromCsv(" Name With whitespace and linefeed \n",
            '123', '123.12', 'm2');

        $this->assertEquals('Name With whitespace and linefeed', $importElement->name());

    }

    public function test_fromCsv_unitWillBeMapped()
    {
        $importElement = ImportElement::fromCsv('test', '123', '123.12', 'm²');

        $this->assertEquals(Unit::m2(), $importElement->quantity()->unit());
    }

    public function test_fromCsv_deLocaleQuantity()
    {
        $locale = Environment::getInstance()->getContainer()->get(ElcaLocale::class);
        $locale->setLocale('de');

        $valueString           = '123,12';
        $importElement = ImportElement::fromCsv('test', '123', $valueString, 'm²');

        $this->assertEquals(123.12, $importElement->quantity()->value(), 'value differs', 0.01);
    }

    public function test_fromCsv_localeQuantityFormatException()
    {
        $locale = Environment::getInstance()->getContainer()->get(ElcaLocale::class);
        $locale->setLocale('en');

        $valueString = '123,12';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Given value ' . self::EMPTY .' is not a numeric value');

        $importElement = ImportElement::fromCsv('test', '123', $valueString, 'm²');

        $this->assertEquals(123.12, $importElement->quantity()->value(), 'value differs', 0.01);
    }

    public function test_harmonizeWithTemplateElement_returnUnmodifiedElement()
    {
        $expecedDinCode = 123;
        $expectedRefUnit = 'm2';

        $elementMock = $this->givenTplElementWithDinCodeAndRefUnit($expecedDinCode, $expectedRefUnit);

        $importElement = ImportElement::fromCsv('test', (string)$expecedDinCode, '123.12', $expectedRefUnit);

        $returnedImportElement = $importElement->harmonizeWithTemplateElement($elementMock);

        $this->assertEquals($expecedDinCode, $returnedImportElement->dinCode());
        $this->assertEquals($expectedRefUnit, $returnedImportElement->quantity()->unit()->value());
        $this->assertFalse($returnedImportElement->isModified());
        $this->assertEmpty($returnedImportElement->modificationReason());
    }

    public function test_harmonizeWithTemplateElement_dinCodeWillBeOverwrittenByTplElementDinCode()
    {
        $expecedDinCode = 123;
        $expectedRefUnit = 'm2';

        $elementMock = $this->givenTplElementWithDinCodeAndRefUnit($expecedDinCode, $expectedRefUnit);

        $importElement = ImportElement::fromCsv('test', '234', '123.12', $expectedRefUnit);

        $returnedImportElement = $importElement->harmonizeWithTemplateElement($elementMock);

        $this->assertEquals($expecedDinCode, $returnedImportElement->dinCode());
        $this->assertTrue($returnedImportElement->isModified());
        $this->assertTrue($returnedImportElement->hasModificationReason(ImportElement::DINCODE_MISMATCH));
        $this->assertEquals(ImportElement::DINCODE_MISMATCH, $returnedImportElement->modificationReason());

    }

    public function test_harmonizeWithTemplateElement_unitWillBeOverwrittenByTplElementDinCode()
    {
        $expecedDinCode = 123;
        $expectedRefUnit = 'm2';

        $elementMock = $this->givenTplElementWithDinCodeAndRefUnit(123, $expectedRefUnit);

        $importElement = ImportElement::fromCsv('test', (string)$expecedDinCode, '123.12', 'Stück');

        $returnedImportElement = $importElement->harmonizeWithTemplateElement($elementMock);

        $this->assertEquals($expecedDinCode, $returnedImportElement->dinCode());
        $this->assertTrue($returnedImportElement->isModified());
        $this->assertTrue($returnedImportElement->hasModificationReason(ImportElement::UNIT_MISMATCH));
        $this->assertEquals(ImportElement::UNIT_MISMATCH, $returnedImportElement->modificationReason());
    }

    public function test_harmonizeWithTemplateElement_unitAndDinCodeWillBeOverwrittenByTplElement()
    {
        $expecedDinCode = 123;
        $expectedRefUnit = 'm2';

        $elementMock = $this->givenTplElementWithDinCodeAndRefUnit(123, $expectedRefUnit);

        $importElement = ImportElement::fromCsv('test', '234', '123.12', 'Stück');

        $returnedImportElement = $importElement->harmonizeWithTemplateElement($elementMock);

        $this->assertEquals($expecedDinCode, $returnedImportElement->dinCode());
        $this->assertTrue($returnedImportElement->isModified());
        $this->assertTrue($returnedImportElement->hasModificationReason(ImportElement::UNIT_MISMATCH));
        $this->assertTrue($returnedImportElement->hasModificationReason(ImportElement::DINCODE_MISMATCH));
    }

    protected function givenTplElementWithDinCodeAndRefUnit(int $dinCode, $refUnit): \PHPUnit_Framework_MockObject_MockObject
    {
        $elementTypeMock = $this->getMockBuilder(ElcaElementType::class)->disableOriginalConstructor()->getMock();
        $elementTypeMock->method('getDinCode')
                        ->willReturn($dinCode);

        $elementMock = $this->getMockBuilder(ElcaElement::class)->disableOriginalConstructor()->getMock();
        $elementMock->method('getElementTypeNode')
                    ->willReturn($elementTypeMock);
        $elementMock->method('getRefUnit')
                    ->willReturn($refUnit);

        return $elementMock;
    }
}
