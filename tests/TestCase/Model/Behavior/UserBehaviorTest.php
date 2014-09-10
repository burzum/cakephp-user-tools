<?php
namespace UserTools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * UserBehaviorTest
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
class UserToolUser extends Table {
	public $name = 'User';
	public $alias = 'User';
	public $useTable = 'users';
	public $actsAs = array(
		'UserTools.User'
	);
}

/**
 * UserBehaviorTest
 */
class UserBehaviorTest extends TestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.user_tools.user'
	);

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->User = TableRegistry::get('Users');
		$this->User->addBehavior('UserTools.User');
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->User);
	}

/**
 * testRegister
 *
 * @return void
 */
	public function testRegister() {
		$data = new Entity([
			'username' => 'foobar',
			'email' => 'foobar@foobar.com',
			'password' => 'password',
			'confirm_password' => 'password'
		]);

		$result = $this->User->register($data);
		$this->assertTrue(is_a($result, '\Cake\ORM\Entity'));
	}

/**
 * testGeneratePassword
 *
 * @return void
 */
	public function testGeneratePassword() {
		$result = $this->User->generatePassword();
		$this->assertTrue(is_string($result));
		$this->assertEquals(strlen($result), 8);

		$result = $this->User->generatePassword(5);
		$this->assertTrue(is_string($result));
		$this->assertEquals(strlen($result), 5);
	}

/**
 * testGeneratePassword
 *
 * @return void
 */
	public function testGenerateToken() {
		$result = $this->User->generateToken();
		$this->assertTrue(is_string($result));
		$this->assertEquals(strlen($result), 10);

		$result = $this->User->generateToken(5);
		$this->assertTrue(is_string($result));
		$this->assertEquals(strlen($result), 5);
	}

/**
 * testVerifyToken
 *
 * @return void
 */
	public function testVerifyToken() {
		$this->User->save(new Entity([
			'email_token_expires' => date('Y-m-d H:i:s', strtotime('-12 hours')),
			'id' => 2
		]), array(
			'validate' => false
		));
		$result = $this->User->verifyToken('secondusertesttoken');
		$this->assertTrue($result);

		$this->User->save(new Entity([
			'email_token_expires' => date('Y-m-d H:i:s', strtotime('-12 hours')),
			'id' => 3
		]), array(
			'validate' => false
		));
		$result = $this->User->verifyToken('thirdusertesttoken', array(
			'returnData' => true
		));
		$this->assertTrue(is_a($result, '\Cake\ORM\Entity'));
		$this->assertTrue($result->token_is_expired);
	}

/**
 * testVerifyTokenNotFoundException
 *
 * @expectedException \Cake\ORM\Exception\RecordNotFoundException
 * @return void
 */
	public function testVerifyTokenNotFoundException() {
		$this->User->verifyToken('DOES-NOT-EXIST');
	}

/**
 * testRemoveExpiredRegistrations
 *
 * @return void
 */
	public function testRemoveExpiredRegistrations() {
		$result = $this->User
			->find()
			->where([
				'email_verified' => 0,
				'email_token_expires <' => date('Y-m-d H:is:'
			)])
			->count();
		$this->assertEquals($result, 1);

		$result = $this->User->removeExpiredRegistrations();
		$this->assertEquals($result, 1);

		$result = $this->User
			->find()
			->where([
				'email_verified' => 0,
				'email_token_expires <' => date('Y-m-d H:is:')
			])
			->count();
		$this->assertEquals($result, 0);
	}

/**
 * testGetUser
 *
 * @return void
 */
	public function testGetUser() {
		$result = $this->User->getUser('1');
		$this->assertEquals($result->id, '1');
		$this->assertEquals($result->username, 'adminuser');
	}

/**
 * testGetUserRecordNotFoundException
 *
 * @expectedException \Cake\ORM\Exception\RecordNotFoundException
 * @return void
 */
	public function testGetUserRecordNotFoundException() {
		$this->User->getUser('DOES-NOT-EXIST');
	}

/**
 * loadBehaviour helper method
 *
 * @param array $options
 * @return void
 */
	public function loadBehaviour($options = []) {
		$this->User->addBehavior('UserTools.User', $options);
	}

}