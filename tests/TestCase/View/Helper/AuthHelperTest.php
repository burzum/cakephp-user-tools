<?php
namespace Burzum\UserTools\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\ORM\Entity;
use Burzum\UserTools\View\Helper\AuthHelper;

/**
 * AuthHelperTestCase
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2016 Florian Krämer
 * @license MIT
 */
class AuthHelperTestCase extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);
		$this->View->request = $this->getMock('\Cake\Network\Request', ['session']);
		$this->View->viewVars = array(
			'userData' => new Entity([
				'id' => 'user-1',
				'username' => 'florian',
				'role' => 'admin',
				'something' => 'some value',
				'data' => [
					'field1' => 'field one'
				]
			])
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
 * testUser
 *
 * @return void
 */
	public function testUser() {
		// Testing accessing the data with an entity.
		$Auth = new AuthHelper($this->View);
		$result = $Auth->user('something');
		$this->assertEquals($result, 'some value');
		$result = $Auth->user('data.field1');
		$this->assertEquals($result, 'field one');

		// Testing accessing it with an array.
		$this->View->viewVars['userData'] = [
			'id' => 'user-1',
			'username' => 'florian',
			'role' => 'admin',
			'something' => 'some value'
		];
		$Auth = new AuthHelper($this->View);
		$result = $Auth->user('something');
		$this->assertEquals($result, 'some value');

		$result = $Auth->user();
		$this->assertEquals($result, $this->View->viewVars['userData']);
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

		$this->View->viewVars['userData']['role'] = array(
			'manager', 'user'
		);
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->hasRole('manager'));
		$this->assertFalse($Auth->hasRole('doesnotexist'));

		try {
			$object = new \stdClass();
			$Auth->hasRole($object);
			$this->fail('No \InvalidArgumentException thrown!');
		} catch (\InvalidArgumentException $e) {
			// Pass
		}
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

		$this->View->viewVars['userData'] = [];
		$Auth = new AuthHelper($this->View);
		$this->assertFalse($Auth->isLoggedin());
	}

	/**
	 * testSetupUserData
	 *
	 * @return void
	 */
	public function testSetupUserData() {
		$session = $this->getMock('\Cake\Network\Session');
		$session->expects($this->at(0))
			->method('read')
			->with('SomeUserData')
			->will($this->returnValue([
				'username' => 'SomeUser'
			]));

		$this->View->request->expects($this->at(0))
			->method('session')
			->will($this->returnValue($session));

		$Auth = new AuthHelper($this->View, [
			'session' => 'SomeUserData'
		]);

		$result = $Auth->user('username');
		$this->assertEquals($result, 'SomeUser');

		try {
			$this->View->viewVars = [];
			$Auth = new AuthHelper($this->View);
			$this->fail('No \RuntimeException thrown!');
		} catch (\RuntimeException $e) {
			// Pass
		}

		try {
			$this->View->viewVars = [];
			$Auth = new AuthHelper($this->View, [
				'viewVar' => 'doesNotExist'
			]);
			$this->fail('No \RuntimeException thrown!');
		} catch (\RuntimeException $e) {
			// Pass
		}
	}
}
