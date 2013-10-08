<?php

class Core_ArraysTest extends PHPUnit_Framework_TestCase {
	
	public function test_keys() {
		$arr = array('key1' => 'value1', 'key2' => 'value2');
		$this->assertEquals(Core_Arrays::keys($arr),array('key1', 'key2'));
	}

	public function test_shift() {
		$arr = array('key1' => 'value1', 'key2' => 'value2');
		$this->assertEquals(Core_Arrays::shift($arr), 'value1');
		$this->assertEquals($arr, array('key2' => 'value2'));
	}

	public function test_pop() {
		$arr = array('key1' => 'value1', 'key2' => 'value2');
		$this->assertEquals(Core_Arrays::pop($arr), 'value2');
		$this->assertEquals($arr, array('key1' => 'value1'));
	}

	public function test_pick() {
		$arr = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3');
		$this->assertEquals(Core_Arrays::pick($arr, 'key2'), 'value2');
		$this->assertEquals($arr, array('key1' => 'value1', 'key3' => 'value3'));
	}

	public function test_reverse() {
		$arr = array('value1', 'value2');
		$this->assertEquals(Core_Arrays::reverse($arr), array('value2', 'value1'));
		$this->assertEquals(Core_Arrays::reverse($arr, true), array(1 => 'value2', 0 => 'value1'));
	}
	
	public function test_flatten() {
		$arr = array(
		  'value1',
		  'value2',
		  'arr' => array(1,2,3, 'a' => array(1, 2, 'value'))
		);
		$this->assertEquals(
			Core_Arrays::flatten($arr),
			array (
				0 => 'value1',
				1 => 'value2',
				2 => 1,
				3 => 2,
				4 => 3,
				'a' => array (
					0 => 1,
					1 => 2,
					2 => 'value',
				),
			)
		);
	}

	public function test_map() {
		$this->assertEquals(
			Core_Arrays::map('return "$x"."1";', $arr = array(1,2,3)),
			array('11', '21', '31')
		);
	}

	public function test_merge() {
		$this->assertEquals(
			Core_Arrays::merge(
				array("color" => "red", 2, 4),
				array("a", "b", "color" => "green", "shape" => "trapezoid", 4)
			),
			array("color" => "green", 0 => 2, 1 => 4, 2 => "a", "b", "shape" => "trapezoid", 4 => 4 )
		);
	}

	public function test_deep_merge_update() {
		$arr1 = array(
			"color" => array("favorite" => "red"),
			5
		);
		$arr2 = array(
			10,
			"color" => array("favorite" => "green", "blue")
		);
		
		$this->assertEquals(
			Core_Arrays::deep_merge_update($arr1, $arr2),
			array (
				'color' => array (
					'favorite' => 'green',
					0 => 'blue',
				),
				0 => 10,
			)
		);
	}

	public function test_deep_merge_append() {
		$arr1 = array(
			"color" => array("favorite" => "red"),
			5
		);
		$arr2 = array(
			10,
			"color" => array("favorite" => "green", "blue")
		);
		
		$this->assertEquals(
			Core_Arrays::deep_merge_append($arr1, $arr2),
			array (
				'color' => array (
					'favorite' => array(
						'red',
						'green'
					),
					0 => 'blue',
				),
				0 => array(
					5,
					10
				)
			)
		);
	}

	public function test_deep_merge_update_inplace() {
		$arr1 = array(
			"color" => array("favorite" => "red"),
			5
		);
		$arr2 = array(
			10,
			"color" => array("favorite" => "green", "blue")
		);
		
		Core_Arrays::deep_merge_update_inplace($arr1, $arr2);
		$this->assertEquals(
			$arr1,
			array (
				'color' => array (
					'favorite' => 'green',
					0 => 'blue',
				),
				0 => 10,
			)
		);
	}

	public function test_update() {
		$arr1 = array(1, 2, 3, 'color' => 'red', 'shape'=> 'circle');
		$arr2 = array(4, 5, 'color' => 'blue', 'height' => '100');

		Core_Arrays::update($arr1, $arr2);
		$this->assertEquals(
			$arr1,
			array(4, 5, 3, 'color' => 'blue', 'shape' => 'circle')
		);
	}

	public function test_expand() {
		$arr1 = array(1, 2, 3, 'color' => 'red', 'shape'=> 'circle');
		$arr2 = array(4, 5, 'color' => 'blue', 'height' => '100');

		Core_Arrays::expand($arr1, $arr2);
		$this->assertEquals(
			$arr1,
			array(1, 2, 3, 'color' => 'red', 'shape' => 'circle', 'height' => '100')
		);
	}

	public function test_join_with() {
		$arr1 = array(1, 2, 3, 'color' => 'red', 'shape'=> 'circle');
		$this->assertEquals(
			Core_Arrays::join_with(':', $arr1),
			'1:2:3:red:circle'
		);
	}

	public function test_search() {
		$array = array(0 => 'blue', 1 => 'red', 2 => 'green', 3 => 'red');
		$array0 = array(1, 2, 3);
		$this->assertEquals(Core_Arrays::search('red', $array), 1);
		$this->assertEquals(Core_Arrays::search('green', $array), 2);
		$this->assertEquals(Core_Arrays::search(2, $array0), 1);
		$this->assertEquals(Core_Arrays::search('2', $array0, true), false);
	}

	public function test_contains() {
		$array = array(0 => 'blue', 1 => 'red', 2 => 'green', 3 => 'red');
		$this->assertTrue(Core_Arrays::contains($array, 'red'));
		$this->assertFalse(Core_Arrays::contains($array, 'black'));
	}

}
