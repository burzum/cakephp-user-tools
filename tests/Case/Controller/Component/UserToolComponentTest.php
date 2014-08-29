<?php
App::uses('Controller', 'Controller');
App::uses('UserTools', 'Controller/Component');

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @license MIT
 */
class UserToolComponent extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.UserTools.User'
	);

/**
 * setup
 *
 * @return void
 */
	public function setUp() {

	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {

		ClassRegistry::flush();
	}

/**
 *
 */
	public function testMapAction() {

	}

}