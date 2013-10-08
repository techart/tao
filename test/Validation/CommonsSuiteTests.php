<?php
// тестируемые файлы
require_once 'lib/Validation/Commons.php';

// подключаем файл с набором тестов
/** 
 * Для этого модуля не создаются отдельные файлы для каждого класса, потому 
 * что тестировать таким образом там нечего. Вместо этого создается класс 
 * наследник Validation_AttributeTest, в котором тестируется функция test 
 * для каждого класса из модуля Commons. Функция называется как  
 * test_ИмяКлассаБезПрефикса.
 */
require_once 'Commons/CommonsTest.php';

/*
require_once 'Commons/FormatTestTest.php';
require_once 'Commons/EmailTestTest.php';
require_once 'Commons/ConfirmationTestTest.php';
require_once 'Commons/ContentTypeTestTest.php';
require_once 'Commons/PresenceTestTest.php';
require_once 'Commons/NumericalityTestTest.php';
require_once 'Commons/NumericRangeTestTest.php';
require_once 'Commons/InclusionTestTest.php';
*/

class Validation_CommonsSuiteTests
{
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('CommonsSuite');
        
        // добавляем тест в набор
		$suite->addTestSuite('Validation_Commons_CommonsTest');
        /*
		$suite->addTestSuite('Validation_Commons_FormatTestTest');
		$suite->addTestSuite('Validation_Commons_EmailTestTest');
		$suite->addTestSuite('Validation_Commons_ConfirmationTestTest');
		$suite->addTestSuite('Validation_Commons_ContentTypeTestTest');
		$suite->addTestSuite('Validation_Commons_PresenceTestTest');
		$suite->addTestSuite('Validation_Commons_NumericalityTestTest');
		$suite->addTestSuite('Validation_Commons_NumericRangeTestTest');
		$suite->addTestSuite('Validation_Commons_InclusionTestTest');
		*/
        return $suite; 
    }
}
?>

