<?php
// тестируемые файлы
require_once ('lib/Time.php');

// подключаем файлы с тестами
require_once 'Time/ModuleTest.php';
require_once 'Time/DateTimeTest.php';


class TimeSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('TimeSuite');
        // добавляем тест в набор
        $suite->addTestSuite('Time_ModuleTest'); 
        $suite->addTestSuite('Time_DateTimeTest');

		return $suite; 
    }
}