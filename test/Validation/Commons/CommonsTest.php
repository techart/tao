<?php
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-03-12 at 09:16:14.
 */
class Validation_Commons_CommonsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var object Validation_AttributeTest
     */
    protected $object;
    
    /**
     * @var Validation_Errors 
     */
    protected $errors;
    
	/**
	 * @var string Имя атрибута
	 */
	protected $attribute = 'attr_name';
	
	/**
	 * @var string Сообщение об ошибке
	 */
	protected $message = 'Error message';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
		$this->errors = new Validation_Errors;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    /**
     * @covers Validation_Commons_FormatTest::test
     */
    public function test_FormatTest()
    {
		$regexp = '{php}i';
		$value = 'PHP is the scripting language of choice.';
        $this->object = new Validation_Commons_FormatTest($this->attribute, $regexp, $this->message);
        
		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $value),
			$this->errors)
		);

		$this->assertTrue($this->object->test(
			array($this->attribute => $value),
			$this->errors, true)
		);

		$this->assertTrue($this->object->test(
			$value,
			$this->errors)
		);

		$this->assertTrue($this->object->test(
			$value,
			$this->errors, true)
		);

		$this->assertFalse($this->object->test(
			$this->attribute,
			$this->errors)
		);

	}

    /**
     * @covers Validation_Commons_EmailTest::test
     */
    public function test_EmailTest()
    {
		$valid_mail = 'mail@domain.com';
		$invalid_mail = '"mail"@domain.com';
        $this->object = new Validation_Commons_EmailTest($this->attribute, $this->message);
        
		$this->assertTrue($this->object->test(
			$valid_mail,
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			$invalid_mail,
			$this->errors)
		);
	}

    /**
     * @covers Validation_Commons_ConfirmationTest::test
     */
    public function test_ConfirmationTest()
    {
		$value = 'aaaa';
		$confirmation = $this->attribute.'_confirmation';
		$incorrect_value = 'bbbb';
        $this->object = new Validation_Commons_ConfirmationTest($this->attribute, $confirmation, $this->message);
        
		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $value, $confirmation => $value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $value, $confirmation => $incorrect_value),
			$this->errors)
		);
	}

    /**
     * @covers Validation_Commons_ContentTypeTest::test
     */
    public function test_ContentTypeTest()
    {
    	$content_type = 'text';
    	$value = IO_FS::File('memory://test.txt');
    	$incorrect_value = 'not file';
    	$object = new Validation_Commons_ContentTypeTest($this->attribute, $content_type, $this->message);

    	$this->assertTrue($object->test((object) array($this->attribute => $value), $this->errors));
    	$this->assertFalse($object->test((object) array($this->attribute => $incorrect_value), $this->errors));

	}

    /**
     * @covers Validation_Commons_PresenceTest::test
     */
    public function test_PresenceTest()
    {
		$correct_value = 'aaaa';
		$empty_value = '';
		$space_value = "\t \n";
		$null_value = null;
		$array_value = array();
		
        $this->object = new Validation_Commons_PresenceTest($this->attribute, $this->message);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $correct_value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $space_value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $array_value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $empty_value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $null_value),
			$this->errors)
		);
	}

    /**
     * @covers Validation_Commons_NumericalityTest::test
     */
    public function test_NumericalityTest()
    {
		$incorrect_value = 'aaaa';
		$correct_value = '1';
		$null_value = null;
		
        $this->object = new Validation_Commons_NumericalityTest($this->attribute, $this->message);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $correct_value),
			$this->errors)
		);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $null_value),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $incorrect_value),
			$this->errors)
		);
	}

    /**
     * @covers Validation_Commons_NumericRangeTest::test
     */
    public function test_NumericRangeTest()
    {
		$upper_bound = '100a';
		$lower_bound = 10;

		$incorrect_value = 'aaaa';
		$correct_value_not_into_range = '1';
		$correct_value_into_range = 20;
		
        $this->object = new Validation_Commons_NumericRangeTest(
			$this->attribute, $lower_bound, $upper_bound, $this->message);
		
		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $correct_value_into_range),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $correct_value_not_into_range),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $incorrect_value),
			$this->errors)
		);
	}

    /**
     * @covers Validation_Commons_InclusionTest::test
     */
    public function test_InclusionTestForScalar()
    {
		$options = array();
		
		$values_scalar = array(1,3,5,7);
		$value_scalar_inclusion = 1;
		$value_scalar_not_inclusion = 2;

        $this->object = new Validation_Commons_InclusionTest(
			$this->attribute, $values_scalar, $this->message, $options
		);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $value_scalar_inclusion),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $value_scalar_not_inclusion),
			$this->errors)
		);
	}
	
    /**
     * @covers Validation_Commons_InclusionTest::test
     */
    public function test_InclusionTestForObject()
    {
		$options = array();
		
		$values_object = (object)array((object)array(1),(object)array(3),(object)array(5),(object)array(7));
		$value_object_inclusion = (object)array(1);
		$value_object_not_inclusion = (object)array(2);
		
        $this->object = new Validation_Commons_InclusionTest(
			$this->attribute, $values_object, $this->message, $options
		);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $value_object_inclusion),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $value_object_not_inclusion),
			$this->errors)
		);
	}
	
    /**
     * @covers Validation_Commons_InclusionTest::test
     */
    public function test_InclusionTestForArray()
    {
		$options = array();
		
		$values_array = array(array(1),array(3),array(5),array(7));
		$value_array_inclusion = array(1);
		$value_array_not_inclusion = array(2);

        $this->object = new Validation_Commons_InclusionTest(
			$this->attribute, $values_array, $this->message, $options
		);

		$this->assertTrue($this->object->test(
			(object) array($this->attribute => $value_array_inclusion),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => $value_array_not_inclusion),
			$this->errors)
		);
	}
	
	/**
     * @covers Validation_Commons_InclusionTest::test
     */
    public function test_InclusionTestForPropertyObject()
    {
    	$options = array('attribute' => $this->attribute);

		$values = array(
			(object)array($this->attribute => 1, 'aaaaa' => 11),
			(object)array($this->attribute => 3),
			(object)array($this->attribute => 5),
			(object)array($this->attribute => 7),
		);
		$value_inclusion = array($this->attribute => 1);
		$value_not_inclusion = array($this->attribute => 2);
		
        $this->object = new Validation_Commons_InclusionTest(
			$this->attribute, $values, $this->message, $options
		);
		
		$this->assertTrue($this->object->test(
			(object) array($this->attribute => (object)$value_inclusion),
			$this->errors)
		);

		$this->assertFalse($this->object->test(
			(object) array($this->attribute => (object)$value_not_inclusion),
			$this->errors)
		);
		
	}
}
