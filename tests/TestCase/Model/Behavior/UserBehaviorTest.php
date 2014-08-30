<?php
namespace UserTools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * UserBehaviorTest
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
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
		$this->User = TableRegistry::get('UserToolUser');
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
		$data = array(
			'User' => array(
				'username' => 'foobar',
				'email' => 'foobar@foobar.com',
				'password' => 'password',
				'confirm_password' => 'password'
			)
		);

		$result = $this->User->register($data);
		$this->assertTrue($result);
	}

/**
 * testGeneratePassword
 *
 * @return void
 */
	public function testGeneratePassword() {
		$result = $this->User->generatePassword();
		$this->assertTrue(is_string($result));
		$this->assertEqual(strlen($result), 8);

		$result = $this->User->generatePassword(5);
		$this->assertTrue(is_string($result));
		$this->assertEqual(strlen($result), 5);
	}

/**
 * testGeneratePassword
 *
 * @return void
 */
	public function testGenerateToken() {
		$result = $this->User->generateToken();
		$this->assertTrue(is_string($result));
		$this->assertEqual(strlen($result), 10);

		$result = $this->User->generateToken(5);
		$this->assertTrue(is_string($result));
		$this->assertEqual(strlen($result), 5);
	}

/**
 * testVerifyToken
 *
 * @return void
 */
	public function testVerifyToken() {
		$this->User->save(array(
			'User' => array(
				'email_token_expires' => date('Y-m-d H:i:s', strtotime('-12 hours')),
				'id' => 2
			),
		), array(
			'validate' => false
		));
		$result = $this->User->verifyToken('secondusertesttoken');
		$this->assertTrue($result);

		$result = $this->User->verifyToken('secondusertesttoken', array(
			'returnData' => true
		));
		$this->assertTrue(is_array($result));
	}

/**
 * testVerifyTokenNotFoundException
 *
 * @expectedException NotFoundException
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
		$this->User->removeExpiredRegistrations();
	}

/**
 * testSetupValidationDefaults
 *
 * @return void
 */
	public function testSetupValidationDefaults() {
		$this->User->validate = array(
			'something' => array(
				'rule' => 'notEmpty'
			),
		);
		$this->loadBehaviour();
		$this->assertEqual($this->User->validate, array(
			'password' => array(
				'notEmpty' => array(
					'rule' => array('notEmpty'),
					'message' => 'You must fill this field.',
				),
				'between' => array(
					'rule' => array('between', 6, 64),
					'message' => 'Between 3 to 16 characters'
				),
				'confirmPassword' => array(
					'rule' => array('confirmPassword'),
					'message' => 'The passwords don\'t match!',
				)
			),
			'username' => array(
				'notEmpty' => array(
					'rule' => array(
						0 => 'notEmpty'
					),
					'message' => 'You must fill this field.'
				),
				'alphaNumeric' => array(
					'rule' => array(
						0 => 'alphaNumeric'
					),
					'message' => 'The username must be alphanumeric.'
				),
				'between' => array(
					'rule' => array(
						0 => 'between',
						1 => 3,
						2 => 16
					),
					'message' => 'Between 3 to 16 characters'
				),
				'unique' => array(
					'rule' => array(
						0 => 'isUnique',
						1 => 'username'
					),
					'message' => 'This username is already in use.'
				)
			),
			'email' => array(
				'email' => array(
					'rule' => array(
						0 => 'email'
					),
					'message' => 'This is not a valid email'
				),
				'unique' => array(
					'rule' => array(
						0 => 'isUnique',
						1 => 'email'
					),
					'message' => 'The email is already in use'
				)
			),
			'confirm_password' => array(
				'notEmpty' => array(
					'rule' => array(
						0 => 'notEmpty'
					),
					'message' => 'You must fill this field.'
				),
				'confirmPassword' => array(
					'rule' => array(
						0 => 'confirmPassword'
					),
					'message' => 'The passwords don\'t match!'
				)
			),
			'something' => array(
				'rule' => 'notEmpty'
			)
		));
	}

/**
 * loadBehaviour helper method
 *
 * @param array $options
 * @return void
 */
	public function loadBehaviour($options = []) {
		$this->User->Behaviors->load('UserTools.User', $options);
	}

}