<?php

class Core_StringsTest extends PHPUnit_Framework_TestCase {
	public function test_concat() {
		$this->assertEquals(Core_Strings::concat('1', '2', '3'), '123');
		$this->assertEquals(Core_Strings::concat(array('1', '2', '3')), '123');
	}
	
	public function test_concat_with() {
		$this->assertEquals(Core_Strings::concat_with('/', '1', '2', '3'), '1/2/3');
		$this->assertEquals(Core_Strings::concat_with('"', '1', '2', '3'), '1"2"3');
		$this->assertEquals(Core_Strings::concat_with(array('/', '1', '2', '3')), '1/2/3');
	}
	
	public function test_substr() {
		//echo mb_internal_encoding();
		$this->assertEquals(Core_Strings::substr('string', 2), 'ring');
		$this->assertEquals(Core_Strings::substr('string', 2, 2), 'ri');
		$this->assertEquals(Core_Strings::substr('string', 2, 1), 'r');
//		$enc = mb_internal_encoding();
//		mb_internal_encoding("UTF-8");
//		echo mb_internal_encoding();
		$this->assertEquals(Core_Strings::substr('Яблочный сироп', 2, 3, 'UTF-8'), 'лоч');
//		mb_internal_encoding($enc);
	}
	
	public function test_replace() {
		$this->assertEquals(Core_Strings::replace('string', 's', 'S'), 'String');
	}
	
	public function test_chop() {
		$this->assertEquals(Core_Strings::chop('chop   '), 'chop');
	}
	
	public function test_trim() {
		$this->assertEquals(Core_Strings::trim('  trim  '), 'trim');
		$this->assertEquals(Core_Strings::trim('a  trim  a ', 'a '), 'trim');
	}
	
	public function test_split() {
		$this->assertEquals(
			Core_Strings::split("piece1 piece2 piece3 piece4"),
			array("piece1", "piece2", "piece3", "piece4")
		);
	}
	
	public function test_split_by() {
		$this->assertEquals(
			Core_Strings::split_by(',', "piece1,piece2,piece3,piece4"),
			array("piece1", "piece2", "piece3", "piece4")
		);
		//var_dump(Core_Strings::split_by('', "piece1,piece2,piece3,piece4"));
		
		$this->assertEquals(
			Core_Strings::split_by('', "piece1,piece2,piece3,piece4"),
			array("piece1,piece2,piece3,piece4")
		);
		
		$this->assertEquals(
			Core_Strings::split_by(' ', "piece1,piece2,piece3,piece4"),
			array("piece1,piece2,piece3,piece4")
		);
		$this->assertEquals(
			Core_Strings::split_by(',', ""),
			array()
		);
	}
	
	public function test_format() {
		$this->assertEquals(
			Core_Strings::format("%04d-%02d-%02d", '1988', '08', '01'),
			"1988-08-01"
		);
	}
	
	public function test_starts_with() {
		$this->assertTrue(
			Core_Strings::starts_with('Start', 'St')
		);
		$this->assertTrue(
			Core_Strings::starts_with('Яблочный сироп', 'Яб')
		);

		$this->assertFalse(
			Core_Strings::starts_with('Start', 'rt')
		);
	}
	
	public function test_ends_with() {
		$this->assertTrue(
			Core_Strings::ends_with('Start', 'rt')
		);
		$this->assertFalse(
			Core_Strings::ends_with('Start', 'St')
		);

		$this->assertTrue(
			Core_Strings::ends_with('Яблочный сироп',  'оп')
		);
		$this->assertFalse(
			Core_Strings::ends_with('Яблочный сироп',  'Яб')
		);
	}
	
	public function test_contains() {
		$this->assertTrue(
			Core_Strings::contains('Start', 'ar')
		);
		$this->assertFalse(
			Core_Strings::contains('Start', 'www')
		);
	}
	
	public function test_downcase() {
		$this->assertEquals(Core_Strings::downcase('StRiNG'), 'string');
		$this->assertEquals(Core_Strings::downcase('ЯблоЧныЙ сироП'), 'яблочный сироп');
	}
	
	public function test_upcase() {
		$this->assertEquals(Core_Strings::upcase('StRiNG'), 'STRING');
		$this->assertEquals(Core_Strings::upcase('ЯблоЧныЙ сироП'), 'ЯБЛОЧНЫЙ СИРОП');
	}
	
	public function test_capitalize() {
		$this->assertEquals(Core_Strings::capitalize('string'), 'String');
		$this->assertEquals(Core_Strings::capitalize('яблочный сироп'), 'Яблочный сироп');
	}
	
	public function test_lcfirst() {
		$this->assertEquals(Core_Strings::lcfirst('String'), 'string');
		$this->assertEquals(Core_Strings::lcfirst('Яблочный сироп'), 'яблочный сироп');
	}
	
	public function test_capitalize_words() {
		$this->assertEquals(Core_Strings::capitalize_words('string string s'),'String String S');
		$this->assertEquals(Core_Strings::capitalize_words('яблочный сироп'),'Яблочный Сироп');
	}
	
	public function test_to_camel_case() {
		$this->assertEquals(Core_Strings::to_camel_case('to_camel_case'),'ToCamelCase');
	}
	
	public function test_encode64_decode64() {
		$this->assertEquals(Core_Strings::decode64(Core_Strings::encode64("Coding me")),"Coding me");
	}
}
