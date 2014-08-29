<?php
namespace Crud\Test\TestSuite;

use Cake\TestSuite\TestSuite;
use Cake\Core\App;

class AllUserToolsTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new TestSuite('All User Tools Plugin Tests');
		$basePath = App::pluginPath('UserTools') . DS . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($basePath);
		return $suite;
	}

}