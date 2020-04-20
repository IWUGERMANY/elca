<?php

namespace Elca\Model\Common;


use PHPUnit\Framework\TestCase;

class OptionalTest extends TestCase
{

    public function test_of()
    {
        $optional = Optional::of(1);

        $this->assertNotNull($optional);
        $this->assertEquals(1, $optional->get());
        $this->assertFalse($optional->isEmpty());
    }

    /**
     * @expectedException \Elca\Model\Exception\InvalidArgumentException
     * @throws \Elca\Model\Exception\InvalidArgumentException
     */
    public function test_of_throws_exception_on_null_value()
    {
        $optional = Optional::of(null);
    }

    public function test_ofNullable_with_value()
    {
        $optional = Optional::ofNullable(1);

        $this->assertNotNull($optional);
        $this->assertEquals(1, $optional->get());
        $this->assertFalse($optional->isEmpty());
    }

    public function test_ofNullable_without_value_is_empty()
    {
        $optional = Optional::ofNullable(null);

        $this->assertNotNull($optional);
        $this->assertNull($optional->get());
        $this->assertTrue($optional->isEmpty());
    }

    public function test_get()
    {
        $optional = Optional::of('Test');

        $this->assertNotNull($optional);
        $this->assertEquals('Test', $optional->get());
    }

    public function test_map()
    {
        $optional = Optional::of('Test')->map(function($value) {
            return $value .' mapped';
        });

        $this->assertNotNull($optional);
        $this->assertEquals('Test mapped', $optional->get());
    }

    public function test_isEmpty()
    {
        $optional = Optional::ofEmpty();

        $this->assertNotNull($optional);
        $this->assertTrue($optional->isEmpty());
    }

    public function test_isPresent()
    {
        $optional = Optional::of('Test');

        $this->assertNotNull($optional);
        $this->assertFalse($optional->isEmpty());
        $this->assertTrue($optional->isPresent());
    }

    public function test_ifPresent_when_value_is_set()
    {
        $presentValue = null;

        Optional::of('Test')->ifPresent(function($value) use (&$presentValue) {
            $presentValue = $value;
        });

        $this->assertEquals('Test', $presentValue);

    }

    public function test_ifPresent_on_empty_optional()
    {
        $presentValue = null;

        Optional::ofEmpty()->ifPresent(function($value) use (&$presentValue) {
            $presentValue = $value;
        });

        $this->assertNull($presentValue);
    }


    public function test_ofEmpty()
    {
        $optional = Optional::ofEmpty();

        $this->assertNull($optional->get());
        $this->assertTrue($optional->isEmpty());
    }

    public function test_orElse_on_empty_with_elseValue()
    {
        $value = Optional::ofEmpty()->orElse(2);

        $this->assertEquals(2, $value);
    }

    public function test_orElse_on_empty_with_elseCallable()
    {
        $value = Optional::ofEmpty()->orElse(function() {
            return 2;
        });

        $this->assertEquals(2, $value);
    }

    public function test_orElse_onNonEmpty_does_not_use_elseValue()
    {
        $value = Optional::of(1)->orElse(2);

        $this->assertEquals(1, $value);
    }

    public function test_orElse_onNonEmpty_does_not_call_elseCallable()
    {
        $wasCalled = false;
        $value = Optional::of(1)->orElse(function() use (&$wasCalled) {return 2;});

        $this->assertFalse($wasCalled);
    }

}
