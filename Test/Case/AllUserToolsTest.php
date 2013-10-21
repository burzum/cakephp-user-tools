<?php
class AllUserToolsTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Tags Plugin Tests');
		$basePath = CakePlugin::path('UserTools') . DS . 'Test' . DS . 'Case' . DS;

		$suite->addTestFile($basePath . 'Controller' . DS . 'Component' . DS . 'UserToolComponentTest.php');
		$suite->addTestFile($basePath . 'Model' . DS . 'Behavior' . DS . 'UserBehaviorTest.php');
		return $suite;
	}

}