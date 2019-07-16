<?php
namespace Burzum\UserTools\Test\TestCase\View\Helper;

use Burzum\UserTools\View\Helper\AuthHelper;
use Cake\Http\ServerRequest as Request;
use Cake\Http\Session;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * AuthHelperTestCase
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @license MIT
 */
class AuthHelperTest extends TestCase {

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);

		$this->View->setRequest($this->getMockBuilder(Request::class)
			->setMethods(['getSession'])
			->getMock());

		$this->View->set([
			'userData' => new Entity([
				'id' => 'user-1',
				'username' => 'florian',
				'role' => 'admin',
				'something' => 'some value',
				'data' => [
					'field1' => 'field one'
				]
			])
		]);
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
		$this->View->set('userData', [
			'id' => 'user-1',
			'username' => 'florian',
			'role' => 'admin',
			'something' => 'some value'
		]);
		$Auth = new AuthHelper($this->View);
		$result = $Auth->user('something');
		$this->assertEquals($result, 'some value');

		$result = $Auth->user();
		$this->assertEquals($result, $this->View->get('userData'));
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

		$this->View->set('userData', [
			'role' => 'manager'
		]);
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->hasRole('manager'));
		$this->assertFalse($Auth->hasRole('doesnotexist'));

		$this->View->set('userData', [
			'role' => [
				'manager', 'user'
			]
		]);
		$Auth = new AuthHelper($this->View);
		$this->assertTrue($Auth->hasRole('manager'));
		$this->assertFalse($Auth->hasRole('doesnotexist'));

		try {
			$object = new \stdClass();
			$Auth->hasRole($object);
			$this->fail('No \InvalidArgumentException thrown!');
		} catch (InvalidArgumentException $e) {
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

		$this->View->set('userData', []);
		$Auth = new AuthHelper($this->View);
		$this->assertFalse($Auth->isLoggedin());
	}

	/**
	 * testSetupUserData
	 *
	 * @return void
	 */
	public function testSetupUserData() {
		$session = $this->getMockBuilder(Session::class)->getMock();
		$session->expects($this->at(0))
			->method('read')
			->with('SomeUserData')
			->will($this->returnValue([
				'username' => 'SomeUser'
			]));

		$this->View->getRequest()->expects($this->at(0))
			->method('getSession')
			->will($this->returnValue($session));

		$Auth = new AuthHelper($this->View, [
			'session' => 'SomeUserData'
		]);

		$result = $Auth->user('username');
		$this->assertEquals($result, 'SomeUser');

		try {
			$Auth = new AuthHelper($this->View);
			$this->fail('No \RuntimeException thrown!');
		} catch (RuntimeException $e) {
			// Pass
		}

		try {
			$Auth = new AuthHelper($this->View, [
				'viewVar' => 'doesNotExist'
			]);
			$this->fail('No \RuntimeException thrown!');
		} catch (RuntimeException $e) {
			// Pass
		}
	}
}
