<?php

class Core_TypesTest extends PHPUnit_Framework_TestCase {
	
	public function test_is_array() {
		$this->assertTrue(Core_types::is_array($arr = array(1,2,3)));
		$this->assertFalse(Core_types::is_array($obj = (object) array(1,2,3,4)));
	}
	
	public function test_is_string() {
		$this->assertTrue(Core_types::is_string($str ='1,2,3'));
		$this->assertTrue(Core_types::is_string($emp = ''));
		$this->assertTrue(Core_types::is_string($self = 'self'));
		$this->assertFalse(Core_types::is_string($arr = array(1,2,3,4)));
	}
	
	public function test_is_number() {
		$this->assertTrue(Core_types::is_number($zero = 0));
		$this->assertTrue(Core_types::is_number($zero_str = '0'));
		$this->assertTrue(Core_types::is_number($str = '+0123.45e6'));
		$this->assertTrue(Core_types::is_number($str = '0xa'));
		$this->assertFalse(Core_types::is_number($str = '0xz'));
		$this->assertFalse(Core_types::is_number($str = '0a'));
	}
	
	public function test_is_object() {
		$this->assertTrue(Core_types::is_object($obj = new ArrayObject(array(1,2,3))));
		$this->assertFalse(Core_types::is_object($arr = array(1,2,3,4)));
	}
	
	public function test_is_resource() {
		$this->assertTrue(Core_types::is_resource($f = fopen("_for_test", 'r')));
		$this->assertFalse(Core_types::is_resource($str = "1.0"));
	}
	
	public function test_is_iterable() {
		$this->assertTrue(Core_types::is_iterable($arr = array(1,2,3)));
		$this->assertTrue(Core_types::is_iterable($obj = new ArrayObject(array(1,2,3))));
		$this->assertFalse(Core_types::is_iterable($std = (object) array(1,2,3)));
	}
	
	public function test_is_subclass_of() {
		$this->assertTrue(Core_types::is_subclass_of('stdClass', (object) array(1,2)));
		$this->assertTrue(Core_types::is_subclass_of('PHPUnit.Framework.TestCase', $this));
		$this->assertTrue(Core_types::is_subclass_of('PHPUnit_Framework_TestCase', $this));
		$this->assertTrue(Core_types::is_subclass_of('PHPUnit_Framework_TestCase', 'CoreTypesTest'));
		$this->assertTrue(Core_types::is_subclass_of('IteratorAggregate', 'ArrayObject'));
		$this->assertFalse(Core_types::is_subclass_of('IteratorAggregate', array(1,2)));
	}
	
	public function test_class_name_for() {
      $this->assertEquals(Core_Types::class_name_for($this), 'CoreTypesTest');
//      $this->assertEquals(Core_Types::class_name_for($this, true), 'CoreTest.TypesCase');
	}
	
	public function test_virtual_class_name_for() {
//		$this->assertEquals(Core_Types::virtual_class_name_for($this), 'Core.Types');
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

	}
	
	public function test_real_class_name_for() {
//		$this->assertEquals(Core_Types::real_class_name_for($this), 'Core_Types');
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

	}
	
	public function test_module_name_for() {
//		$this->assertEquals(Core_Types::module_name_for($this), 'CoreTest');
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

	}
	
	public function test_reflection_for() {
		$this->assertInstanceOf('ReflectionClass', Core_Types::reflection_for($this));
		$this->assertInstanceOf('ReflectionClass', Core_Types::reflection_for('CoreTypesTest'));
	}
	
	public function test_class_hierarchy_for() {
		$this->assertEquals(
			Core_Types::class_hierarchy_for($this, true),
			array (
				0 => 'CoreTypesTest',
				1 => 'PHPUnit.Framework.TestCase',
				2 => 'PHPUnit.Framework.Assert'
			)
		);
		$this->assertEquals(
			Core_Types::class_hierarchy_for($this),
			array (
				0 => 'CoreTypesTest',
				1 => 'PHPUnit_Framework_TestCase',
				2 => 'PHPUnit_Framework_Assert'
			)
		);
	}
	
	public function test_class_exists() {
		$this->assertTrue(Core_Types::class_exists('CoreTest'));
		$this->assertTrue(Core_Types::class_exists('CoreTest'));
		$this->assertFalse(Core_Types::class_exists('TestNOMODULE'));
	}
}
