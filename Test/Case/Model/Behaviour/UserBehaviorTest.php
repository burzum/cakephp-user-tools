<?php
App::uses('Model', 'Model');

/**
 * UserBehaviorTest
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @license MIT
 */
class UserToolUser extends Model {
	public $name = 'User';
	public $useTable = 'users';
}

/**
 *
 */
class UserBehaviorTest extends CakeTestCase {

/**
 *
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
		$this->User = ClassRegistry::init('User');
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
		$this->assertTrue(is_array($result));
	}

/**
 * testGeneratePassword
 *
 * @return void
 */
	public function generatePassword() {
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
			'username' => array(
				'alphaNumeric' => array(
					'rule' => 'alphaNumeric',
					'required' => true,
					'message' => 'Alphabets and numbers only'
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
						0 => 'isUnique'
					),
					'message' => 'The username is already taken'
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
						0 => 'isUnique'
					),
					'message' => 'The email is already in use'
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
	public function loadBehaviour($options = array()) {
		$this->User->Behaviors->load('UserTools.User', $options);
	}

}