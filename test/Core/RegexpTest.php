<?php

//require_once ('Core.php');

class Core_RegexpTest extends PHPUnit_Framework_TestCase {
/*
	public function test_true() {
		$this->assertTrue(true);
	}
*/


    /**
     * @dataProvider provider_match_true
     */
	public function test_match_true($regexp,$string) {
        $this->assertTrue(Core_Regexps::match($regexp,$string));
    }

	public function provider_match_true() {
		return array(
			array('{php}i', "PHP is the scripting language of choice."),
			array("{\bweb\b}i", "PHP is the web scripting language of choice.")
		);
	}
	
    /**
     * @dataProvider provider_match_false
     */
	public function test_match_false($regexp,$string) {
        $this->assertFalse(Core_Regexps::match($regexp,$string));
    }
    
    public function provider_match_false() {
		return array(
			array('{php}i', "Perl is the scripting language of choice."),
		);
	}

    /**
     * @dataProvider provider_match_with_results
     */
	public function test_match_with_results($regexp,$string,$result) {
		$this->assertEquals(Core_Regexps::match_with_results($regexp,$string),$result);
	}
	
	public function provider_match_with_results() {
		return array(
			array('@^(?:http://)?([^/]+)@i',"http://www.php.net/index.html",array (0 => 'http://www.php.net', 1 => 'www.php.net')),
		);
	}
	
    /**
     * @dataProvider provider_match_all
     */
	public function test_match_all($regexp,$string,$result) {
		$this->assertEquals(Core_Regexps::match_all($regexp,$string),$result);
	}
	
	public function provider_match_all() {
		return array(
			array(
				"/\(?  (\d{3})?  \)?  (?(1)  [\-\s] ) \d{3}-\d{4}/x",
				"Call 555-1212 or 1-800-555-1212",
				 array (
				  0 => array (
					0 => '555-1212',
					1 => '800-555-1212',
				  ),
				  1 => array (
					0 => '',
					1 => '800',
				  ),
				)
			),
		);
	}
	
    /**
     * @dataProvider provider_quote
     */
	public function test_quote($src_string,$quoted_string) {
		$this->assertEquals(Core_Regexps::quote($src_string),$quoted_string);
	}
	
	public function provider_quote() {
		return array(
			array('$40 for a g3/400','\\$40 for a g3/400'),
		);
	}
	
	/**
	 * @dataProvider provider_replace
	 */
	public function test_replace($pattern,$replace,$subject,$expected) {
		$this->assertEquals(Core_Regexps::replace($pattern,$replace,$subject),$expected);
	}
	
	public function provider_replace() {
		return array(
			array(
				'/(\w+) (\d+), (\d+)/i',
				'${1}1,$3',
				'April 15, 2003',
				"April1,2003"
			),
		);
	}
	
	/**
	 * @dataProvider provider_replace_using_callback
	 */
	public function test_replace_using_callback($pattern,$callback,$subject,$expected) {
		$this->assertEquals(Core_Regexps::replace_using_callback($pattern,$callback,$subject),$expected);
	}

	public function provider_replace_using_callback() {
		return array(
			array(
				"|(\d{2}/\d{2}/)(\d{4})|", 
				array($this,'call_back'), 
				"April fools day is 04/01/2002",
				"April fools day is 04/01/2003"
			),
		);
	}
	
	public function call_back($matches) { return $matches[1].($matches[2]+1); }
	
	/**
	 * @dataProvider provider_replace_ref
	 */
	public function test_replace_ref_count($search_str,$replace_str,$subject,$count_replaced,$expected) {
		$this->assertEquals(Core_Regexps::replace_ref($search_str,$replace_str, $subject),$count_replaced);
		$this->assertEquals($subject, $expected);
	}
	
	public function provider_replace_ref() {
		return array(
			//array('/string/','replace','Test to search',0,'Test to search'),
			array('/string/','replace','Test string to search',1,'Test replace to search'),
			array('/string/','replace','Test string string to search',2,'Test replace replace to search'),
		);
	}

	/**
	 * @dataProvider provider_split_by
	 */
	public function test_split_by($delimiter,$string,$expected) {
		$this->assertEquals(Core_Regexps::split_by($delimiter, $string),$expected);
	}
	
	public function provider_split_by() {
		return array(
			array('/ /', 'Test string to search',array('Test', 'string', 'to', 'search')),
		);
	}

}
