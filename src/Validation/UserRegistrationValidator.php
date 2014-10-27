<?php
/**
 * UserRegistrationValidator
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Validation;

use Cake\Validation\Validator;

class UserRegistrationValidator extends Validator {

/**
 * Constructor
 */
	public function __construct() {
		//$this->validatePresence('email', 'create');
		//$this->validatePresence('password', 'create');

		$this->add('username', [
			'notEmpty' => [
				'rule' => 'notEmpty',
				'message' => __d('user_tools', 'An username is required.')
			],
			'length' => [
				'rule' => ['lengthBetween', 3, 32],
				'message' => __d('user_tools', 'The username must be between 3 and 32 characters.')
			],
			'unique' => [
				'rule' => ['validateUnique', ['scope' => 'username']],
				'provider' => 'table',
				'message' => __d('user_tools', 'The username is already in use.')
			],
			'alphaNumeric' => [
				'rule' => 'alphaNumeric',
				'message' => __d('user_tools', 'The username must be alpha numeric.')
			]
		]);

		$this->add('email', [
			'notEmpty' => [
				'rule' => 'notEmpty',
				'message' => __d('user_tools', 'An email is required.')
			],
			'unique' => [
				'rule' => ['validateUnique', ['scope' => 'username']],
				'provider' => 'table',
				'message' => __d('user_tools', 'The email is already in use.')
			],
			'validEmail' => [
				'rule' => 'email',
				'message' => __d('user_tools', 'Must be a valid email address.')
			]
		]);

		$this->add('password', [
			'notEmpty' => [
				'rule' => 'notEmpty',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => __d('user_tools', 'The password must have at least 6 characters.')
			],
			'confirmPassword' => [
				'rule' => ['compareFields', 'confirm_password'],
				'message' => __d('user_tools', 'The passwords don\'t match!'),
				'provider' => 'myself',
			]
		]);

		$this->provider('myself', $this);

		$this->add('confirm_password', [
			'notEmpty' => [
				'rule' => 'notEmpty',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => __d('user_tools', 'The password must have at least 6 characters.')
			],
			'confirmPassword' => [
				'rule' => ['compareFields', 'password'],
				'message' => __d('user_tools', 'The passwords don\'t match!'),
				'provider' => 'myself',
			]
		]);
	}

	public function compareFields($value, $field, $context) {
		if ($value === $context['data'][$field]) {
			return true;
		}
		return false;
	}
}
