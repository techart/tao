<?php
// тестируемые файлы
require_once('lib/Object.php');

// подключаем файлы с тестами
require_once 'Object/ModuleTest.php';
require_once 'Object/AbstractDelegatorTest.php';
require_once 'Object/AggregatorTest.php';
require_once 'Object/AttrListTest.php';
require_once 'Object/FactoryTest.php';
require_once 'Object/FilterTest.php';
require_once 'Object/ListenerTest.php';
require_once 'Object/StructTest.php';
require_once 'Object/WrapperTest.php';

class ObjectSuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('ObjectSuite');
        
        // добавляем тест в набор
        $suite->addTestSuite('Object_AbstractDelegatorTest'); 
        $suite->addTestSuite('Object_AggregatorTest'); 
        $suite->addTestSuite('Object_AttrListTest'); 
        $suite->addTestSuite('Object_FactoryTest'); 
        $suite->addTestSuite('Object_FilterTest'); 
        $suite->addTestSuite('Object_ListenerTest'); 
        $suite->addTestSuite('Object_StructTest'); 
        $suite->addTestSuite('Object_WrapperTest'); 
        $suite->addTestSuite('Object_ModuleTest'); 
		return $suite; 
    }
}
