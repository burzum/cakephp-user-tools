<?php
App::uses('View', 'View');
App::uses('AuthHelper', 'UserTools.View/Helper');

/**
 * AuthHelperTestCase
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @license MIT
 */
class AuthHelperTestCase extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);
		$this->View->viewVars = array(
			'userData' => array(
				'id' => 'user-1',
				'username' => 'florian',
				'role' => 'admin',
				'something' => 'some value'
			)
		);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->View);
		parent::tearDown();
	}

/**
 * testHasRole
 *
 * @return void
 */
	public function testHasRole() {
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->hasRole('admin'));
		$this->assertFalse($Auth->hasRole('doesnotexist'));

		$this->View->viewVars['userData']['role'] = array(
			'manager'
		);
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->hasRole('manager'));
		$this->assertFalse($Auth->hasRole('doesnotexist'));
	}

/**
 * testIsMe
 *
 * @return void
 */
	public function testIsMe() {
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->isMe('user-1'));
		$this->assertFalse($Auth->isMe('user-2'));
	}

/**
 * testIsLoggedIn
 *
 * @return void
 */
	public function testIsLoggedIn() {
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->isLoggedin());

		$this->View->viewVars['userData'] = array();
		$Auth = new AuthHelper($this->View);
		$this->assertFalse($Auth->isLoggedin());
	}

}