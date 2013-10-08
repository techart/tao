<?php
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-03-05 at 15:02:12.
 */
class Object_WrapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Object_Wrapper
     */
    protected $object;
    protected $obj_param = array('prop1' => 'prop1_val', 'prop2' => 'prop2_val');
    protected $arr_param = array('attr1' => 'attr1_val', 'attr2' => 'attr2_val');

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Object_Wrapper((object) $this->obj_param,$this->arr_param);
    }

    /**
     * @expectedException Core_InvalidArgumentValueException
     */
	public function testCreateNotObjectFailure()
	{
		$t = new Object_Wrapper('1111',$this->arr_param);
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testCreateNotArrayFailure()
	{
		$t = new Object_Wrapper((object) $this->obj_param,(object) $this->arr_param);
	}
	 
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Object_Wrapper::__get
     */
    public function test__get()
    {
		$this->assertEquals((object)$this->obj_param,$this->object->__object);
		$this->assertEquals($this->arr_param,$this->object->__attrs);
		$this->assertEquals('prop1_val',$this->object->prop1);
		$this->assertEquals('attr1_val',$this->object->attr1);
		$this->assertNull($this->object->attr0);
    }

	
    /**
     * @covers Object_Wrapper::__set
     * @todo   Implement test__set().
     */
    public function test__set()
    {
		$obj = (object)$this->obj_param;
		$obj->prop3 = 'prop3_val';
		//$this->assertInstanceOf('Object_Wrapper',$this->object->prop3 = 'prop3_val');
		$this->object->prop3 = 'prop3_val';
		$this->assertEquals($obj,$this->object->__object);
		
		$obj->attr3 = 'attr3_val';
		$this->arr_param['attr3'] = 'attr3_val';
		//$this->assertInstanceOf('Object_Wrapper',$this->object->attr3 = 'attr3_val');
		$this->object->attr3 = 'attr3_val';
		$this->assertNotEquals($this->arr_param,$this->object->__attrs);
		$this->assertEquals($obj,$this->object->__object);
    }

    /**
     * @covers Object_Wrapper::__isset
     */
    public function test__isset()
    {
		$this->assertTrue(isset($this->object->prop1));
		$this->assertTrue(isset($this->object->attr1));

		$this->assertFalse(isset($this->object->aaaa));
    }

    /**
     * @covers Object_Wrapper::__unset
     */
    public function test__unset()
    {
		unset($this->object->prop1);
		$this->assertFalse(isset($this->object->prop1));
		
		unset($this->object->attr1);
		$this->assertFalse(isset($this->object->attr1));

		unset($this->object->aaaa);
		$this->assertFalse(isset($this->object->aaaa));
    }

    /**
     * @covers Object_Wrapper::__call
     */
    public function test__call()
    {
		$wrapper = Object::Wrapper(new ForTest__call, array('a' => 1));
		
		$wrapper = Object::Wrapper(new ForTest__call, array('a' => 1, 'func2' => 
			function($arg1,$arg2) {return "$arg1,$arg2";})
		);
		
		$this->assertEquals('1,2',$wrapper->func2(1,2));

		$this->object->func1 = new ForTest__call;
		$this->assertEquals($this->object->func1->func10(1,2),$wrapper->func10(1,2));

		
		$func3 = $this->object->func3 = function($arg1,$arg2) {
			return "$arg1,$arg2";
		};
		
		$this->assertEquals('1,2',$func3(1,2));
		//var_dump($this->object->__object);die;

    }
}

/**
 * Для тестирования Object_Wrapper::__call
 */
class ForTest__call
{
	public function func10($arg1,$arg2)
	{
		return "$arg1,$arg2";
	}
}